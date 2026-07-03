<?php

namespace App\Controllers;

use App\Libraries\SubmissionAnswerFormatter;
use App\Models\FormFieldModel;
use App\Models\FormModel;
use App\Models\FormSubmissionModel;
use App\Models\SubmissionDataModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class SubmissionController extends BaseController
{
    protected FormModel $formModel;
    protected FormFieldModel $fieldModel;
    protected FormSubmissionModel $submissionModel;
    protected SubmissionDataModel $submissionDataModel;
    protected SubmissionAnswerFormatter $answerFormatter;

    public function __construct()
    {
        $this->formModel           = new FormModel();
        $this->fieldModel          = new FormFieldModel();
        $this->submissionModel     = new FormSubmissionModel();
        $this->submissionDataModel = new SubmissionDataModel();
        $this->answerFormatter     = new SubmissionAnswerFormatter();
    }

    public function index(int $formId): string
    {
        $form = $this->findFormOrFail($formId);

        // Columns = the form's input fields (display-only types store no value).
        $formFields = $this->fieldModel->getForForm($form['id']);
        $columns = array_values(array_filter(
            $formFields,
            static fn ($f) => ! in_array($f['field_type'], FormFieldModel::DISPLAY_ONLY_TYPES, true)
        ));
        $fieldsByKey = $this->fieldsByKey($formFields);

        $submissions = $this->submissionModel
            ->where('form_id', $form['id'])
            ->orderBy('created_at', 'DESC')
            ->paginate(15);

        // Answers for just this page's submissions, keyed [submissionId][fieldKey].
        $answersById = [];
        $submissionIds = array_column($submissions, 'id');
        if ($submissionIds !== []) {
            $answers = $this->submissionDataModel->whereIn('submission_id', $submissionIds)->findAll();
            foreach ($answers as $answer) {
                $answersById[$answer['submission_id']][$answer['field_key']] = $answer;
            }
        }

        return view('submissions/index', [
            'form'        => $form,
            'columns'     => $columns,
            'submissions' => $submissions,
            'answersById' => $answersById,
            'fieldsByKey' => $fieldsByKey,
            'answerFormatter' => $this->answerFormatter,
            'pager'       => $this->submissionModel->pager,
        ]);
    }

    public function show(int $formId, int $submissionId): string
    {
        $form       = $this->findFormOrFail($formId);
        $submission = $this->findSubmissionOrFail($form['id'], $submissionId);
        $answers    = $this->submissionDataModel->getForSubmission($submission['id']);
        $fieldsByKey = $this->fieldsByKey($this->fieldModel->getForForm($form['id']));

        return view('submissions/show', [
            'form'       => $form,
            'submission' => $submission,
            'answers'    => $answers,
            'fieldsByKey' => $fieldsByKey,
            'answerFormatter' => $this->answerFormatter,
        ]);
    }

    public function export(int $formId): ResponseInterface
    {
        $form = $this->findFormOrFail($formId);

        // Display-only fields (e.g. paragraph) store no answer — exclude them so
        // they don't produce empty CSV columns.
        $formFields = $this->fieldModel->getForForm($form['id']);
        $fields = array_values(array_filter(
            $formFields,
            static fn ($f) => ! in_array($f['field_type'], FormFieldModel::DISPLAY_ONLY_TYPES, true)
        ));
        $fieldsByKey = $this->fieldsByKey($formFields);

        $submissions = $this->submissionModel->getForForm($form['id']);
        $allAnswers  = $this->submissionDataModel->getForForm($form['id']);

        $answersBySubmission = [];
        foreach ($allAnswers as $answer) {
            $answersBySubmission[$answer['submission_id']][$answer['field_key']] = $answer;
        }

        $buffer = fopen('php://temp', 'w+');

        $headers = ['Submitted At', 'IP Address'];
        foreach ($fields as $field) {
            $headers[] = $field['label'];
        }
        fputcsv($buffer, $headers);

        foreach ($submissions as $submission) {
            $row = [$submission['created_at'], $submission['ip_address']];

            foreach ($fields as $field) {
                $answer = $answersBySubmission[$submission['id']][$field['field_key']] ?? null;
                $row[]  = $this->answerFormatter->format($answer, $fieldsByKey[$field['field_key']] ?? null);
            }

            fputcsv($buffer, $row);
        }

        rewind($buffer);
        $csv = stream_get_contents($buffer);
        fclose($buffer);

        $filename = 'form-' . $form['id'] . '-submissions.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    public function downloadFile(int $formId, int $submissionId, int $dataId)
    {
        $form       = $this->findFormOrFail($formId);
        $submission = $this->findSubmissionOrFail($form['id'], $submissionId);

        $answer = $this->submissionDataModel->find($dataId);

        if ($answer === null || (int) $answer['submission_id'] !== $submission['id'] || empty($answer['file_path'])) {
            throw new PageNotFoundException('File not found.');
        }

        $path = WRITEPATH . 'uploads/' . $answer['file_path'];

        if (! is_file($path)) {
            throw new PageNotFoundException('File not found.');
        }

        $originalName = $answer['value'] !== null && $answer['value'] !== ''
            ? $answer['value']
            : basename($path);

        return $this->response->download($originalName, file_get_contents($path));
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     *
     * @return array<string,array<string,mixed>>
     */
    private function fieldsByKey(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $out[(string) $field['field_key']] = $field;
        }

        return $out;
    }

    private function findFormOrFail(int $id): array
    {
        $form = $this->formModel->find($id);

        if ($form === null) {
            throw new PageNotFoundException('Form not found.');
        }

        $this->ensureFormAccess($form);

        return $form;
    }

    private function findSubmissionOrFail(int $formId, int $submissionId): array
    {
        $submission = $this->submissionModel->find($submissionId);

        if ($submission === null || (int) $submission['form_id'] !== $formId) {
            throw new PageNotFoundException('Submission not found.');
        }

        return $submission;
    }
}
