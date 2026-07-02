<?php

namespace App\Controllers;

use App\Models\FormModel;
use App\Models\FieldModel;
use App\Models\SubmissionModel;
use App\Models\SubmissionDataModel;
use App\Models\ThemeModel;

class PublicFormController extends BaseController
{
    protected $formModel;
    protected $fieldModel;
    protected $submissionModel;
    protected $submissionDataModel;
    protected $themeModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->fieldModel = new FieldModel();
        $this->submissionModel = new SubmissionModel();
        $this->submissionDataModel = new SubmissionDataModel();
        $this->themeModel = new ThemeModel();
    }

    /**
     * View public form
     */
    public function view($formId)
    {
        $form = $this->formModel->getFormWithFields($formId);

        if (!$form) {
            return view('public/form_not_found');
        }

        // Check if form is published
        if ($form['status'] !== 'published') {
            return view('public/form_not_published');
        }

        // Check if form can accept submissions
        if (!$this->formModel->canAcceptSubmissions($formId)) {
            return view('public/form_closed');
        }

        // Check if login is required
        if ($form['require_login'] && !session()->get('logged_in')) {
            session()->set('redirect_url', current_url());
            return redirect()->to('/auth/login')->with('error', 'Please login to submit this form');
        }

        // Get theme CSS
        $theme = $this->themeModel->find($form['theme_id']);
        $themeCSS = $theme ? $this->themeModel->getThemeCSS($form['theme_id']) : '';

        return view('public/view_form', [
            'form' => $form,
            'themeCSS' => $themeCSS,
        ]);
    }

    /**
     * Submit public form
     */
    public function submit($formId)
    {
        $form = $this->formModel->find($formId);

        if (!$form) {
            return $this->jsonResponseError('Form not found', 404);
        }

        // Check if form is published
        if ($form['status'] !== 'published') {
            return $this->jsonResponseError('Form is not published', 400);
        }

        // Check if form can accept submissions
        if (!$this->formModel->canAcceptSubmissions($formId)) {
            return $this->jsonResponseError('Form is closed for submissions', 400);
        }

        // Get fields
        $fields = $this->fieldModel->getFieldsByForm($formId);

        // Validate fields
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

            // Add custom validation rules
            if ($field['validation_rules']) {
                $validationRulesData = json_decode($field['validation_rules'], true);
                if (isset($validationRulesData['min_length'])) {
                    $rules[] = 'min_length[' . $validationRulesData['min_length'] . ']';
                }
                if (isset($validationRulesData['max_length'])) {
                    $rules[] = 'max_length[' . $validationRulesData['max_length'] . ']';
                }
                if (isset($validationRulesData['pattern'])) {
                    $rules[] = 'regex_match[' . $validationRulesData['pattern'] . ']';
                }
            }

            if (!empty($rules)) {
                $validationRules[$fieldName] = implode('|', $rules);
                $validationMessages[$fieldName] = [
                    'required' => $field['label'] . ' is required',
                ];
            }
        }

        // Validate
        $this->validation->setRules($validationRules, $validationMessages);

        if (!$this->validation->withRequest($this->request)->run()) {
            return $this->jsonResponseError('Validation failed', 400, $this->validation->getErrors());
        }

        // Handle file uploads
        $formData = $this->request->getPost();
        $uploadedFiles = [];

        foreach ($fields as $field) {
            if ($field['field_type'] === 'file') {
                $file = $this->request->getFile($field['name']);
                if ($file && $file->isValid() && !$file->hasMoved()) {
                    $newName = $file->getRandomName();
                    $file->move(WRITEPATH . 'uploads/form-files/', $newName);
                    $uploadedFiles[$field['name']] = 'form-files/' . $newName;
                }
            }
        }

        // Create submission
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

        // Save submission data
        $formData['form_id'] = $formId;

        // Add uploaded file paths to form data
        foreach ($uploadedFiles as $fieldName => $filePath) {
            $formData[$fieldName] = $filePath;
        }

        $this->submissionDataModel->saveSubmissionData($submissionId, $formData);

        // TODO: Send email notifications

        return $this->jsonResponseSuccess([], 'Form submitted successfully');
    }
}