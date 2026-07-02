<?php

namespace App\Controllers;

use App\Libraries\ConditionEvaluator;
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
            'options'          => $this->defaultOptionsFor($fieldType),
            'is_required'      => false,
            'validation_rules' => null,
            'conditions'       => null,
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

        // Options is a per-type config store. Each type shapes it differently —
        // NEVER run appointment/paragraph config through the {value,label} filter
        // (it would strip the config object to []).
        if (in_array($fieldType, FormFieldModel::OPTION_FIELD_TYPES, true)) {
            $options = array_values(array_filter(
                is_array($body['options'] ?? null) ? $body['options'] : [],
                static fn ($opt) => isset($opt['value'], $opt['label']) && trim((string) $opt['label']) !== ''
            ));
        } elseif ($fieldType === 'appointment') {
            $options = $this->sanitizeAppointmentConfig($body['options'] ?? []);
        } elseif ($fieldType === 'paragraph') {
            $rawBody = (string) ($body['body'] ?? $body['options']['body'] ?? '');
            $options = ['body' => (new \App\Libraries\HtmlSanitizer())->clean($rawBody)];
        } else {
            $options = null;
        }

        $this->fieldModel->update($fieldId, [
            'label'            => $label,
            'field_key'        => $fieldKey,
            'placeholder'      => ($body['placeholder'] ?? '') !== '' ? $body['placeholder'] : null,
            'help_text'        => ($body['help_text'] ?? '') !== '' ? $body['help_text'] : null,
            'options'          => $options,
            'is_required'      => (bool) ($body['is_required'] ?? false),
            'validation_rules' => $body['validation_rules'] ?? null,
            'conditions'       => $this->sanitizeConditions($form['id'], $fieldKey, $body['conditions'] ?? null),
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

    /**
     * Seed `options` for a freshly-created field of the given type.
     */
    private function defaultOptionsFor(string $fieldType)
    {
        if (in_array($fieldType, FormFieldModel::OPTION_FIELD_TYPES, true)) {
            return [['value' => 'option_1', 'label' => 'Option 1'], ['value' => 'option_2', 'label' => 'Option 2']];
        }

        if ($fieldType === 'appointment') {
            return \App\Libraries\AppointmentAvailability::DEFAULT_CONFIG;
        }

        if ($fieldType === 'paragraph') {
            return ['body' => ''];
        }

        return null;
    }

    /**
     * Whitelist + clamp an appointment availability config.
     */
    private function sanitizeAppointmentConfig($raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $cfg = \App\Libraries\AppointmentAvailability::DEFAULT_CONFIG;

        $weekdays = [];
        foreach (is_array($raw['weekdays'] ?? null) ? $raw['weekdays'] : [] as $d) {
            $d = (int) $d;
            if ($d >= 1 && $d <= 7 && ! in_array($d, $weekdays, true)) {
                $weekdays[] = $d;
            }
        }
        if ($weekdays !== []) {
            sort($weekdays);
            $cfg['weekdays'] = $weekdays;
        }

        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', (string) ($raw['start_time'] ?? ''))) {
            $cfg['start_time'] = $raw['start_time'];
        }
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', (string) ($raw['end_time'] ?? ''))) {
            $cfg['end_time'] = $raw['end_time'];
        }

        $slot = (int) ($raw['slot_minutes'] ?? 0);
        if ($slot >= 5 && $slot <= 480) {
            $cfg['slot_minutes'] = $slot;
        }

        $maxDays = (int) ($raw['date_max_days'] ?? 0);
        if ($maxDays >= 1 && $maxDays <= 365) {
            $cfg['date_max_days'] = $maxDays;
        }

        return $cfg;
    }

    /**
     * Validate/whitelist a field's conditional-logic config. Drops rules that
     * reference unknown or self fields, unknown actions/operators, and keeps
     * only a plain-string calc formula (re-validated at evaluation time).
     */
    private function sanitizeConditions(int $formId, string $selfKey, $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $validKeys = array_column($this->fieldModel->getForForm($formId), 'field_key');

        $rules = [];
        foreach (is_array($raw['rules'] ?? null) ? $raw['rules'] : [] as $rule) {
            if (! is_array($rule) || ! in_array($rule['action'] ?? '', ConditionEvaluator::ACTIONS, true)) {
                continue;
            }

            $when = [];
            foreach (is_array($rule['when'] ?? null) ? $rule['when'] : [] as $cond) {
                if (! is_array($cond)) {
                    continue;
                }
                $field = (string) ($cond['field'] ?? '');
                $op    = (string) ($cond['operator'] ?? '');
                if ($field === '' || $field === $selfKey
                    || ! in_array($field, $validKeys, true)
                    || ! in_array($op, ConditionEvaluator::OPERATORS, true)) {
                    continue;
                }
                $when[] = [
                    'field'    => $field,
                    'operator' => $op,
                    'value'    => (string) ($cond['value'] ?? ''),
                ];
            }

            if ($when === []) {
                continue;
            }

            $rules[] = [
                'action' => $rule['action'],
                'match'  => ($rule['match'] ?? 'all') === 'any' ? 'any' : 'all',
                'when'   => $when,
            ];
        }

        $out = [];
        if ($rules !== []) {
            $out['rules'] = $rules;
        }

        $formula = trim((string) ($raw['calc']['formula'] ?? ''));
        if ($formula !== '') {
            $out['calc'] = ['formula' => $formula];
        }

        return $out === [] ? null : $out;
    }
}
