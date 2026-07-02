<?php

namespace App\Controllers;

use App\Models\FormModel;
use App\Models\FieldModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionDataModel;

class ApiController extends BaseController
{
    protected $formModel;
    protected $fieldModel;
    protected $submissionModel;
    protected $submissionDataModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->fieldModel = new FieldModel();
        $this->submissionModel = new SubmissionModel();
        $this->submissionDataModel = new SubmissionDataModel();
    }

    /**
     * Create form
     */
    public function createForm()
    {
        if (!$this->hasPermission('forms.create')) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        $rules = [
            'title' => 'required|min_length[3]|max_length[255]',
            'description' => 'permit_empty|string',
        ];

        if (!$this->validate($rules)) {
            return $this->jsonResponseError('Validation failed', 400, $this->validator->getErrors());
        }

        $data = [
            'user_id' => $this->getCurrentUserId(),
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'status' => 'draft',
        ];

        $formId = $this->formModel->insert($data);

        if ($formId) {
            return $this->jsonResponseSuccess(['form_id' => $formId]);
        }

        return $this->jsonResponseError('Failed to create form', 500);
    }

    /**
     * List forms
     */
    public function listForms()
    {
        if (!$this->hasPermission('forms.read')) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        $userId = $this->getCurrentUserId();
        $isAdmin = $this->isAdmin();

        if ($isAdmin) {
            $forms = $this->formModel->findAll();
        } else {
            $forms = $this->formModel->getFormsByUser($userId);
        }

        return $this->jsonResponseSuccess($forms);
    }

    /**
     * Get form
     */
    public function getForm($id)
    {
        if (!$this->hasPermission('forms.read')) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        $form = $this->formModel->getFormWithFields($id);

        if (!$form) {
            return $this->jsonResponseError('Form not found', 404);
        }

        if (!$this->isFormOwner($id)) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        return $this->jsonResponseSuccess($form);
    }

    /**
     * Update form
     */
    public function updateForm($id)
    {
        $form = $this->formModel->find($id);

        if (!$form) {
            return $this->jsonResponseError('Form not found', 404);
        }

        if (!$this->isFormOwner($id)) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        $rules = [
            'title' => 'permit_empty|min_length[3]|max_length[255]',
            'description' => 'permit_empty|string',
            'status' => 'permit_empty|in_list[draft,published,archived]',
        ];

        if (!$this->validate($rules)) {
            return $this->jsonResponseError('Validation failed', 400, $this->validator->getErrors());
        }

        $data = [];
        if ($this->request->getPost('title')) {
            $data['title'] = $this->request->getPost('title');
        }
        if ($this->request->getPost('description') !== null) {
            $data['description'] = $this->request->getPost('description');
        }
        if ($this->request->getPost('status') && $this->hasPermission('forms.publish')) {
            $data['status'] = $this->request->getPost('status');
        }

        $updated = $this->formModel->update($id, $data);

        if ($updated) {
            return $this->jsonResponseSuccess([]);
        }

        return $this->jsonResponseError('Failed to update form', 500);
    }

    /**
     * Delete form
     */
    public function deleteForm($id)
    {
        $form = $this->formModel->find($id);

        if (!$form) {
            return $this->jsonResponseError('Form not found', 404);
        }

        if (!$this->isFormOwner($id)) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        $deleted = $this->formModel->delete($id);

        if ($deleted) {
            return $this->jsonResponseSuccess([]);
        }

        return $this->jsonResponseError('Failed to delete form', 500);
    }

    /**
     * Add field
     */
    public function addField($formId)
    {
        if (!$this->isFormOwner($formId)) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        $rules = [
            'field_type' => 'required',
            'name' => 'required|min_length[2]|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->jsonResponseError('Validation failed', 400, $this->validator->getErrors());
        }

        $data = [
            'form_id' => $formId,
            'field_type' => $this->request->getPost('field_type'),
            'label' => $this->request->getPost('label'),
            'name' => $this->request->getPost('name'),
            'placeholder' => $this->request->getPost('placeholder'),
            'default_value' => $this->request->getPost('default_value'),
            'options' => $this->request->getPost('options') ? json_encode($this->request->getPost('options')) : null,
            'required' => $this->request->getPost('required') ? true : false,
            'validation_rules' => $this->request->getPost('validation_rules') ? json_encode($this->request->getPost('validation_rules')) : null,
            'width' => $this->request->getPost('width') ?: 100,
            'order_index' => $this->fieldModel->getMaxOrderIndex($formId) + 1,
            'conditional_logic' => $this->request->getPost('conditional_logic') ? json_encode($this->request->getPost('conditional_logic')) : null,
        ];

        $fieldId = $this->fieldModel->insert($data);

        if ($fieldId) {
            return $this->jsonResponseSuccess(['field_id' => $fieldId]);
        }

        return $this->jsonResponseError('Failed to add field', 500);
    }

    /**
     * Update field
     */
    public function updateField($id)
    {
        $field = $this->fieldModel->find($id);

        if (!$field) {
            return $this->jsonResponseError('Field not found', 404);
        }

        if (!$this->isFormOwner($field['form_id'])) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        $data = [
            'label' => $this->request->getPost('label'),
            'placeholder' => $this->request->getPost('placeholder'),
            'default_value' => $this->request->getPost('default_value'),
            'options' => $this->request->getPost('options') ? json_encode($this->request->getPost('options')) : null,
            'required' => $this->request->getPost('required') ? true : false,
            'validation_rules' => $this->request->getPost('validation_rules') ? json_encode($this->request->getPost('validation_rules')) : null,
            'width' => $this->request->getPost('width') ?: 100,
            'conditional_logic' => $this->request->getPost('conditional_logic') ? json_encode($this->request->getPost('conditional_logic')) : null,
        ];

        $updated = $this->fieldModel->update($id, $data);

        if ($updated) {
            return $this->jsonResponseSuccess([]);
        }

        return $this->jsonResponseError('Failed to update field', 500);
    }

    /**
     * Delete field
     */
    public function deleteField($id)
    {
        $field = $this->fieldModel->find($id);

        if (!$field) {
            return $this->jsonResponseError('Field not found', 404);
        }

        if (!$this->isFormOwner($field['form_id'])) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        $deleted = $this->fieldModel->delete($id);

        if ($deleted) {
            return $this->jsonResponseSuccess([]);
        }

        return $this->jsonResponseError('Failed to delete field', 500);
    }

    /**
     * Get submissions
     */
    public function getSubmissions($formId)
    {
        if (!$this->hasPermission('submissions.read')) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        if (!$this->isFormOwner($formId)) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        $submissions = $this->submissionModel->getSubmissionsByForm($formId);

        return $this->jsonResponseSuccess($submissions);
    }

    /**
     * Get submission
     */
    public function getSubmission($id)
    {
        if (!$this->hasPermission('submissions.read')) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        $submission = $this->submissionModel->getSubmissionWithData($id);

        if (!$submission) {
            return $this->jsonResponseError('Submission not found', 404);
        }

        if (!$this->isFormOwner($submission['form_id'])) {
            return $this->jsonResponseError('Permission denied', 403);
        }

        return $this->jsonResponseSuccess($submission);
    }

    /**
     * Submit form (public)
     */
    public function submitForm($formId)
    {
        $form = $this->formModel->find($formId);

        if (!$form) {
            return $this->jsonResponseError('Form not found', 404);
        }

        if ($form['status'] !== 'published') {
            return $this->jsonResponseError('Form is not published', 400);
        }

        if (!$this->formModel->canAcceptSubmissions($formId)) {
            return $this->jsonResponseError('Form is closed for submissions', 400);
        }

        $fields = $this->fieldModel->getFieldsByForm($formId);
        $validationRules = [];
        $validationMessages = [];

        foreach ($fields as $field) {
            if ($field['field_type'] === 'paragraph' || $field['field_type'] === 'divider') {
                continue;
            }

            $fieldName = $field['name'];
            $rules = [];

            if ($field['required']) {
                $rules[] = 'required';
            }

            if ($field['field_type'] === 'email') {
                $rules[] = 'valid_email';
            }

            if ($field['field_type'] === 'number') {
                $rules[] = 'numeric';
            }

            if (!empty($rules)) {
                $validationRules[$fieldName] = implode('|', $rules);
                $validationMessages[$fieldName] = [
                    'required' => $field['label'] . ' is required',
                ];
            }
        }

        $this->validation->setRules($validationRules, $validationMessages);

        if (!$this->validation->withRequest($this->request)->run()) {
            return $this->jsonResponseError('Validation failed', 400, $this->validation->getErrors());
        }

        $submissionData = [
            'form_id' => $formId,
            'submitter_name' => $this->request->getPost('submitter_name'),
            'submitter_email' => $this->request->getPost('submitter_email'),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'status' => 'new',
        ];

        $submissionId = $this->submissionModel->insert($submissionData);

        if (!$submissionId) {
            return $this->jsonResponseError('Failed to create submission', 500);
        }

        $formData = $this->request->getPost();
        $formData['form_id'] = $formId;

        $this->submissionDataModel->saveSubmissionData($submissionId, $formData);

        return $this->jsonResponseSuccess([], 'Form submitted successfully');
    }

    /**
     * Upload file
     */
    public function uploadFile()
    {
        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid()) {
            return $this->jsonResponseError('Invalid file', 400);
        }

        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file->getClientMimeType(), $allowedTypes)) {
            return $this->jsonResponseError('File type not allowed', 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) { // 5MB
            return $this->jsonResponseError('File size exceeds limit', 400);
        }

        $newName = $file->getRandomName();
        $file->move(WRITEPATH . 'uploads/form-files/', $newName);

        return $this->jsonResponseSuccess([
            'file_path' => 'form-files/' . $newName,
            'file_name' => $file->getClientName(),
        ]);
    }
}