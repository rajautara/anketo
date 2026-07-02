<?php

namespace App\Controllers;

use App\Libraries\SubmissionNotifier;
use App\Models\FormFieldModel;
use App\Models\FormModel;
use App\Models\FormSubmissionModel;
use App\Models\SubmissionDataModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PublicFormController extends BaseController
{
    private const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB

    protected FormModel $formModel;
    protected FormFieldModel $fieldModel;
    protected FormSubmissionModel $submissionModel;
    protected SubmissionDataModel $submissionDataModel;

    public function __construct()
    {
        $this->formModel           = new FormModel();
        $this->fieldModel          = new FormFieldModel();
        $this->submissionModel     = new FormSubmissionModel();
        $this->submissionDataModel = new SubmissionDataModel();
    }

    public function show(string $token): string
    {
        $form = $this->formModel->findByShareToken($token);

        if ($form === null) {
            throw new PageNotFoundException('This form is not available.');
        }

        $fields    = $this->fieldModel->getForForm($form['id']);
        $submitted = (bool) session()->getFlashdata('submitted');

        return view('public/show', [
            'form'      => $form,
            'fields'    => $fields,
            'submitted' => $submitted,
            'errors'    => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function submit(string $token)
    {
        $form = $this->formModel->findByShareToken($token);

        if ($form === null) {
            throw new PageNotFoundException('This form is not available.');
        }

        // Always re-fetch the form's current fields/options from the database —
        // never trust anything about field structure from the client request.
        $fields      = $this->fieldModel->getForForm($form['id']);
        $postAnswers = $this->request->getPost('answers');
        $postAnswers = is_array($postAnswers) ? $postAnswers : [];

        $errors = [];
        $toSave = [];

        foreach ($fields as $field) {
            $key  = $field['field_key'];
            $type = $field['field_type'];

            if ($type === 'file') {
                [$value, $filePath, $fileError] = $this->handleFileField($form['id'], $field);
                if ($fileError !== null) {
                    $errors[$key] = $fileError;
                }
                $toSave[] = $this->answerRow($field, $value, $filePath);

                continue;
            }

            if ($type === 'checkbox') {
                [$value, $checkboxError] = $this->handleCheckboxField($field, $postAnswers[$key] ?? []);
                if ($checkboxError !== null) {
                    $errors[$key] = $checkboxError;
                }
                $toSave[] = $this->answerRow($field, $value, null);

                continue;
            }

            $raw = trim((string) ($postAnswers[$key] ?? ''));
            $fieldError = $this->validateScalarField($field, $raw);
            if ($fieldError !== null) {
                $errors[$key] = $fieldError;
            }

            $toSave[] = $this->answerRow($field, $raw !== '' ? $raw : null, null);
        }

        if ($errors !== []) {
            return redirect()->to(site_url('f/' . $token))->withInput()->with('errors', $errors);
        }

        $db = db_connect();
        $db->transStart();

        $submissionId = $this->submissionModel->insert([
            'form_id'    => $form['id'],
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) ($this->request->getUserAgent()?->getAgentString() ?? ''),
        ]);

        $this->submissionDataModel->saveAnswers($submissionId, $toSave);

        $db->transComplete();

        if ($db->transStatus() !== false) {
            (new SubmissionNotifier())->notify($form, (int) $submissionId, $toSave);
        }

        session()->setFlashdata('submitted', true);

        return redirect()->to(site_url('f/' . $token));
    }

    private function answerRow(array $field, ?string $value, ?string $filePath): array
    {
        return [
            'field_id'    => $field['id'],
            'field_key'   => $field['field_key'],
            'field_label' => $field['label'],
            'value'       => $value,
            'file_path'   => $filePath,
        ];
    }

    private function validateScalarField(array $field, string $value): ?string
    {
        if ($value === '') {
            return $field['is_required'] ? ($field['label'] . ' is required.') : null;
        }

        switch ($field['field_type']) {
            case 'email':
                if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    return $field['label'] . ' must be a valid email address.';
                }
                break;

            case 'number':
                if (! is_numeric($value)) {
                    return $field['label'] . ' must be a number.';
                }
                break;

            case 'date':
                $date = \DateTime::createFromFormat('Y-m-d', $value);
                if ($date === false || $date->format('Y-m-d') !== $value) {
                    return $field['label'] . ' must be a valid date.';
                }
                break;

            case 'select':
            case 'radio':
                $allowed = array_column($field['options'] ?? [], 'value');
                if (! in_array($value, $allowed, true)) {
                    return 'Invalid selection for ' . $field['label'] . '.';
                }
                break;
        }

        return null;
    }

    /**
     * @return array{0: ?string, 1: ?string} [json-encoded selected values or null, error message or null]
     */
    private function handleCheckboxField(array $field, $submitted): array
    {
        $submitted = is_array($submitted) ? $submitted : [];
        $allowed   = array_column($field['options'] ?? [], 'value');
        $selected  = array_values(array_intersect($submitted, $allowed));

        if ($field['is_required'] && $selected === []) {
            return [null, $field['label'] . ' is required.'];
        }

        return [$selected === [] ? null : json_encode($selected), null];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string} [display value, stored relative path, error message]
     */
    private function handleFileField(int $formId, array $field): array
    {
        $file = $this->request->getFile('answers.' . $field['field_key']);

        if ($file === null || ! $file->isValid()) {
            if ($field['is_required']) {
                return [null, null, $field['label'] . ' is required.'];
            }

            return [null, null, null];
        }

        if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            return [null, null, $field['label'] . ' must be smaller than 10 MB.'];
        }

        $directory = WRITEPATH . 'uploads/forms/' . $formId;
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($directory, $newName);

        $relativePath = 'forms/' . $formId . '/' . $newName;

        return [$file->getClientName(), $relativePath, null];
    }
}
