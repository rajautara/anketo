<?php

namespace App\Controllers;

use App\Models\SubmissionModel;
use App\Models\SubmissionDataModel;
use App\Models\FormModel;
use App\Models\FieldModel;

class SubmissionController extends BaseController
{
    protected $submissionModel;
    protected $submissionDataModel;
    protected $formModel;
    protected $fieldModel;

    public function __construct()
    {
        $this->submissionModel = new SubmissionModel();
        $this->submissionDataModel = new SubmissionDataModel();
        $this->formModel = new FormModel();
        $this->fieldModel = new FieldModel();
    }

    /**
     * List all submissions
     */
    public function index()
    {
        if (!$this->hasPermission('submissions.read')) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to view submissions');
        }

        $userId = $this->getCurrentUserId();
        $isAdmin = $this->isAdmin();

        // Get forms user can access
        if ($isAdmin) {
            $forms = $this->formModel->findAll();
        } else {
            $forms = $this->formModel->getFormsByUser($userId);
        }

        $formIds = array_column($forms, 'id');

        // Get submissions
        $submissions = [];
        foreach ($formIds as $formId) {
            $formSubmissions = $this->submissionModel->getSubmissionsByForm($formId, 50);
            foreach ($formSubmissions as $submission) {
                $form = $this->formModel->find($formId);
                $submission['form_title'] = $form['title'];
                $submissions[] = $submission;
            }
        }

        // Sort by submission date
        usort($submissions, fn($a, $b) => strtotime($b['submitted_at']) - strtotime($a['submitted_at']));

        return view('submissions/index', [
            'submissions' => $submissions,
        ]);
    }

    /**
     * Show submission details
     */
    public function show($id)
    {
        if (!$this->hasPermission('submissions.read')) {
            return redirect()->to('/submissions')->with('error', 'You do not have permission to view submissions');
        }

        $submission = $this->submissionModel->getSubmissionWithData($id);

        if (!$submission) {
            return redirect()->to('/submissions')->with('error', 'Submission not found');
        }

        $form = $this->formModel->find($submission['form_id']);

        // Check permission
        if (!$this->isFormOwner($submission['form_id'])) {
            return redirect()->to('/submissions')->with('error', 'You do not have permission to view this submission');
        }

        // Mark as read
        $this->submissionModel->markAsRead($id);

        // Get fields with data
        $fields = $this->fieldModel->getFieldsByForm($submission['form_id']);
        $dataMap = [];
        foreach ($submission['data'] as $data) {
            $dataMap[$data['field_id']] = $data;
        }

        return view('submissions/view', [
            'submission' => $submission,
            'form' => $form,
            'fields' => $fields,
            'dataMap' => $dataMap,
        ]);
    }

    /**
     * Delete submission
     */
    public function delete($id)
    {
        $submission = $this->submissionModel->find($id);

        if (!$submission) {
            return $this->jsonResponseError('Submission not found', 404);
        }

        // Check permission
        if (!$this->isFormOwner($submission['form_id'])) {
            return $this->jsonResponseError('You do not have permission to delete this submission', 403);
        }

        $deleted = $this->submissionModel->delete($id);

        if ($deleted) {
            return $this->jsonResponseSuccess([], 'Submission deleted successfully');
        }

        return $this->jsonResponseError('Failed to delete submission', 500);
    }

    /**
     * Get submissions by form
     */
    public function byForm($formId)
    {
        if (!$this->hasPermission('submissions.read')) {
            return $this->jsonResponseError('You do not have permission to view submissions', 403);
        }

        // Check permission
        if (!$this->isFormOwner($formId)) {
            return $this->jsonResponseError('You do not have permission to view submissions for this form', 403);
        }

        $submissions = $this->submissionModel->getSubmissionsByForm($formId);

        return $this->jsonResponseSuccess($submissions);
    }

    /**
     * Export submissions
     */
    public function export($formId)
    {
        if (!$this->hasPermission('submissions.export')) {
            return redirect()->to('/submissions')->with('error', 'You do not have permission to export submissions');
        }

        // Check permission
        if (!$this->isFormOwner($formId)) {
            return redirect()->to('/submissions')->with('error', 'You do not have permission to export submissions for this form');
        }

        $form = $this->formModel->find($formId);
        $exportData = $this->submissionModel->getSubmissionsForExport($formId);

        // Generate CSV
        $filename = 'submissions_' . $formId . '_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = WRITEPATH . 'uploads/' . $filename;

        $file = fopen($filepath, 'w');

        if (!empty($exportData)) {
            fputcsv($file, array_keys($exportData[0]));
            foreach ($exportData as $row) {
                fputcsv($file, $row);
            }
        }

        fclose($file);

        // Download file
        return $this->response->download($filepath, null)->setFileName($filename);
    }
}