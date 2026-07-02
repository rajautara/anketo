<?php

namespace App\Controllers;

use App\Models\FormFieldModel;
use App\Models\FormModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class FormFieldController extends BaseController
{
    protected FormModel $formModel;
    protected FormFieldModel $fieldModel;

    public function __construct()
    {
        $this->formModel  = new FormModel();
        $this->fieldModel = new FormFieldModel();
    }

    public function store(int $formId): ResponseInterface
    {
        $form = $this->findFormOrFail($formId);
        $body = $this->request->getJSON(true) ?? [];

        $fieldType = $body['field_type'] ?? '';

        if (! in_array($fieldType, FormFieldModel::FIELD_TYPES, true)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid field type.']);
        }

        $label = $this->fieldModel->defaultLabelFor($fieldType);

        $data = [
            'form_id'          => $form['id'],
            'field_type'       => $fieldType,
            'label'            => $label,
            'field_key'        => $this->fieldModel->generateUniqueFieldKey($form['id'], $label),
            'placeholder'      => null,
            'help_text'        => null,
            'options'          => in_array($fieldType, FormFieldModel::OPTION_FIELD_TYPES, true)
                ? [['value' => 'option_1', 'label' => 'Option 1'], ['value' => 'option_2', 'label' => 'Option 2']]
                : null,
            'is_required'      => false,
            'validation_rules' => null,
            'sort_order'       => $this->fieldModel->nextSortOrder($form['id']),
        ];

        $id = $this->fieldModel->insert($data);

        return $this->response->setJSON($this->fieldModel->find($id));
    }

    public function update(int $formId, int $fieldId): ResponseInterface
    {
        $form  = $this->findFormOrFail($formId);
        $field = $this->findFieldOrFail($form['id'], $fieldId);
        $body  = $this->request->getJSON(true) ?? [];

        $label     = trim((string) ($body['label'] ?? $field['label']));
        $fieldKey  = trim((string) ($body['field_key'] ?? $field['field_key']));
        $fieldType = $field['field_type'];

        if ($label === '') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Label is required.']);
        }

        if ($fieldKey === '') {
            $fieldKey = $this->fieldModel->generateUniqueFieldKey($form['id'], $label);
        } elseif ($fieldKey !== $field['field_key']) {
            $exists = $this->fieldModel->where('form_id', $form['id'])
                ->where('field_key', $fieldKey)
                ->where('id !=', $fieldId)
                ->first();

            if ($exists !== null) {
                return $this->response->setStatusCode(422)->setJSON(['error' => 'That field key is already used on this form.']);
            }
        }

        $options = null;
        if (in_array($fieldType, FormFieldModel::OPTION_FIELD_TYPES, true)) {
            $options = array_values(array_filter(
                is_array($body['options'] ?? null) ? $body['options'] : [],
                static fn ($opt) => isset($opt['value'], $opt['label']) && trim((string) $opt['label']) !== ''
            ));
        }

        $this->fieldModel->update($fieldId, [
            'label'            => $label,
            'field_key'        => $fieldKey,
            'placeholder'      => ($body['placeholder'] ?? '') !== '' ? $body['placeholder'] : null,
            'help_text'        => ($body['help_text'] ?? '') !== '' ? $body['help_text'] : null,
            'options'          => $options,
            'is_required'      => (bool) ($body['is_required'] ?? false),
            'validation_rules' => $body['validation_rules'] ?? null,
        ]);

        return $this->response->setJSON($this->fieldModel->find($fieldId));
    }

    public function delete(int $formId, int $fieldId): ResponseInterface
    {
        $form = $this->findFormOrFail($formId);
        $this->findFieldOrFail($form['id'], $fieldId);

        $this->fieldModel->delete($fieldId);

        return $this->response->setJSON(['success' => true]);
    }

    public function reorder(int $formId): ResponseInterface
    {
        $form = $this->findFormOrFail($formId);
        $body = $this->request->getJSON(true) ?? [];

        $order = array_map('intval', $body['order'] ?? []);

        $this->fieldModel->reorder($form['id'], $order);

        return $this->response->setJSON(['success' => true]);
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

    private function findFieldOrFail(int $formId, int $fieldId): array
    {
        $field = $this->fieldModel->find($fieldId);

        if ($field === null || (int) $field['form_id'] !== $formId) {
            throw new PageNotFoundException('Field not found.');
        }

        return $field;
    }
}
