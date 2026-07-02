<?php

namespace App\Controllers;

use App\Models\FieldModel;
use App\Models\FormModel;

class FieldController extends BaseController
{
    protected $fieldModel;
    protected $formModel;

    public function __construct()
    {
        $this->fieldModel = new FieldModel();
        $this->formModel = new FormModel();
    }

    /**
     * Store new field
     */
    public function store()
    {
        $formId = $this->request->getPost('form_id');

        // Check permission
        if (!$this->isFormOwner($formId)) {
            return $this->jsonResponseError('You do not have permission to edit this form', 403);
        }

        $rules = [
            'form_id' => 'required|integer',
            'field_type' => 'required|in_list[text,email,number,textarea,checkbox,radio,select,file,date,time,datetime,password,hidden,paragraph,divider]',
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
            return $this->jsonResponseSuccess(['field_id' => $fieldId], 'Field added successfully');
        }

        return $this->jsonResponseError('Failed to add field', 500);
    }

    /**
     * Update field
     */
    public function update($id)
    {
        $field = $this->fieldModel->find($id);

        if (!$field) {
            return $this->jsonResponseError('Field not found', 404);
        }

        // Check permission
        if (!$this->isFormOwner($field['form_id'])) {
            return $this->jsonResponseError('You do not have permission to edit this form', 403);
        }

        $rules = [
            'label' => 'permit_empty|max_length[255]',
            'placeholder' => 'permit_empty|max_length[255]',
            'width' => 'permit_empty|integer|greater_than[0]|less_than_equal[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->jsonResponseError('Validation failed', 400, $this->validator->getErrors());
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
            return $this->jsonResponseSuccess([], 'Field updated successfully');
        }

        return $this->jsonResponseError('Failed to update field', 500);
    }

    /**
     * Delete field
     */
    public function delete($id)
    {
        $field = $this->fieldModel->find($id);

        if (!$field) {
            return $this->jsonResponseError('Field not found', 404);
        }

        // Check permission
        if (!$this->isFormOwner($field['form_id'])) {
            return $this->jsonResponseError('You do not have permission to edit this form', 403);
        }

        $deleted = $this->fieldModel->delete($id);

        if ($deleted) {
            return $this->jsonResponseSuccess([], 'Field deleted successfully');
        }

        return $this->jsonResponseError('Failed to delete field', 500);
    }

    /**
     * Reorder fields
     */
    public function reorder()
    {
        $fieldIds = $this->request->getPost('field_ids');

        if (!is_array($fieldIds)) {
            return $this->jsonResponseError('Invalid field IDs', 400);
        }

        // Get first field to check form ownership
        $firstField = $this->fieldModel->find($fieldIds[0]);
        if (!$firstField) {
            return $this->jsonResponseError('Field not found', 404);
        }

        // Check permission
        if (!$this->isFormOwner($firstField['form_id'])) {
            return $this->jsonResponseError('You do not have permission to edit this form', 403);
        }

        $reordered = $this->fieldModel->reorderFields($fieldIds);

        if ($reordered) {
            return $this->jsonResponseSuccess([], 'Fields reordered successfully');
        }

        return $this->jsonResponseError('Failed to reorder fields', 500);
    }
}