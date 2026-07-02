<?php

namespace App\Controllers;

use App\Models\FormModel;
use App\Models\FieldModel;
use App\Models\ThemeModel;

class FormController extends BaseController
{
    protected $formModel;
    protected $fieldModel;
    protected $themeModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->fieldModel = new FieldModel();
        $this->themeModel = new ThemeModel();
    }

    /**
     * List all forms
     */
    public function index()
    {
        $userId = $this->getCurrentUserId();
        $isAdmin = $this->isAdmin();

        if ($isAdmin) {
            $forms = $this->formModel->orderBy('created_at', 'DESC')->findAll();
        } else {
            $forms = $this->formModel->getFormsByUser($userId);
        }

        return view('forms/index', [
            'forms' => $forms,
        ]);
    }

    /**
     * Show create form page
     */
    public function create()
    {
        if (!$this->hasPermission('forms.create')) {
            return redirect()->to('/forms')->with('error', 'You do not have permission to create forms');
        }

        $themes = $this->themeModel->findAll();

        return view('forms/create', [
            'themes' => $themes,
        ]);
    }

    /**
     * Store new form
     */
    public function store()
    {
        if (!$this->hasPermission('forms.create')) {
            return $this->jsonResponseError('You do not have permission to create forms', 403);
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
            'theme_id' => $this->request->getPost('theme_id') ?: null,
            'allow_anonymous' => $this->request->getPost('allow_anonymous') ? true : false,
            'require_login' => $this->request->getPost('require_login') ? true : false,
        ];

        $formId = $this->formModel->insert($data);

        if ($formId) {
            return $this->jsonResponseSuccess(['form_id' => $formId], 'Form created successfully');
        }

        return $this->jsonResponseError('Failed to create form', 500);
    }

    /**
     * Show form details
     */
    public function show($id)
    {
        $form = $this->formModel->getFormWithFields($id);

        if (!$form) {
            return redirect()->to('/forms')->with('error', 'Form not found');
        }

        // Check permission
        if (!$this->isFormOwner($id) && !$this->hasPermission('forms.read')) {
            return redirect()->to('/forms')->with('error', 'You do not have permission to view this form');
        }

        $stats = $this->formModel->getFormStats($id);

        return view('forms/view', [
            'form' => $form,
            'stats' => $stats,
        ]);
    }

    /**
     * Show edit form page
     */
    public function edit($id)
    {
        $form = $this->formModel->find($id);

        if (!$form) {
            return redirect()->to('/forms')->with('error', 'Form not found');
        }

        // Check permission
        if (!$this->isFormOwner($id)) {
            return redirect()->to('/forms')->with('error', 'You do not have permission to edit this form');
        }

        $themes = $this->themeModel->findAll();

        return view('forms/edit', [
            'form' => $form,
            'themes' => $themes,
        ]);
    }

    /**
     * Update form
     */
    public function update($id)
    {
        $form = $this->formModel->find($id);

        if (!$form) {
            return $this->jsonResponseError('Form not found', 404);
        }

        // Check permission
        if (!$this->isFormOwner($id)) {
            return $this->jsonResponseError('You do not have permission to edit this form', 403);
        }

        $rules = [
            'title' => 'required|min_length[3]|max_length[255]',
            'description' => 'permit_empty|string',
            'status' => 'permit_empty|in_list[draft,published,archived]',
        ];

        if (!$this->validate($rules)) {
            return $this->jsonResponseError('Validation failed', 400, $this->validator->getErrors());
        }

        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'theme_id' => $this->request->getPost('theme_id') ?: null,
            'allow_anonymous' => $this->request->getPost('allow_anonymous') ? true : false,
            'require_login' => $this->request->getPost('require_login') ? true : false,
            'limit_submissions' => $this->request->getPost('limit_submissions') ?: null,
            'expiry_date' => $this->request->getPost('expiry_date') ?: null,
        ];

        // Only update status if user has permission
        if ($this->hasPermission('forms.publish') && $this->request->getPost('status')) {
            $data['status'] = $this->request->getPost('status');
        }

        $updated = $this->formModel->update($id, $data);

        if ($updated) {
            return $this->jsonResponseSuccess([], 'Form updated successfully');
        }

        return $this->jsonResponseError('Failed to update form', 500);
    }

    /**
     * Delete form
     */
    public function delete($id)
    {
        $form = $this->formModel->find($id);

        if (!$form) {
            return $this->jsonResponseError('Form not found', 404);
        }

        // Check permission
        if (!$this->isFormOwner($id)) {
            return $this->jsonResponseError('You do not have permission to delete this form', 403);
        }

        $deleted = $this->formModel->delete($id);

        if ($deleted) {
            return $this->jsonResponseSuccess([], 'Form deleted successfully');
        }

        return $this->jsonResponseError('Failed to delete form', 500);
    }

    /**
     * Show form builder
     */
    public function builder($id)
    {
        $form = $this->formModel->find($id);

        if (!$form) {
            return redirect()->to('/forms')->with('error', 'Form not found');
        }

        // Check permission
        if (!$this->isFormOwner($id)) {
            return redirect()->to('/forms')->with('error', 'You do not have permission to edit this form');
        }

        $fields = $this->fieldModel->getFieldsByForm($id);
        $fieldTypes = FieldModel::getAvailableFieldTypes();

        return view('forms/builder', [
            'form' => $form,
            'fields' => $fields,
            'fieldTypes' => $fieldTypes,
        ]);
    }

    /**
     * Preview form
     */
    public function preview($id)
    {
        $form = $this->formModel->getFormWithFields($id);

        if (!$form) {
            return redirect()->to('/forms')->with('error', 'Form not found');
        }

        // Check permission
        if (!$this->isFormOwner($id)) {
            return redirect()->to('/forms')->with('error', 'You do not have permission to view this form');
        }

        return view('forms/preview', [
            'form' => $form,
        ]);
    }

    /**
     * Publish form
     */
    public function publish($id)
    {
        if (!$this->hasPermission('forms.publish')) {
            return $this->jsonResponseError('You do not have permission to publish forms', 403);
        }

        $form = $this->formModel->find($id);

        if (!$form) {
            return $this->jsonResponseError('Form not found', 404);
        }

        if (!$this->isFormOwner($id)) {
            return $this->jsonResponseError('You do not have permission to publish this form', 403);
        }

        $updated = $this->formModel->update($id, ['status' => 'published']);

        if ($updated) {
            return $this->jsonResponseSuccess([], 'Form published successfully');
        }

        return $this->jsonResponseError('Failed to publish form', 500);
    }

    /**
     * Duplicate form
     */
    public function duplicate($id)
    {
        if (!$this->hasPermission('forms.create')) {
            return $this->jsonResponseError('You do not have permission to create forms', 403);
        }

        $form = $this->formModel->find($id);

        if (!$form) {
            return $this->jsonResponseError('Form not found', 404);
        }

        $newFormId = $this->formModel->duplicateForm($id, $this->getCurrentUserId());

        if ($newFormId) {
            return $this->jsonResponseSuccess(['form_id' => $newFormId], 'Form duplicated successfully');
        }

        return $this->jsonResponseError('Failed to duplicate form', 500);
    }
}