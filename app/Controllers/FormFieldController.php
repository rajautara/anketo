<?php

namespace App\Controllers;

use App\Libraries\ConditionEvaluator;
use App\Libraries\ProductList;
use App\Libraries\UploadPath;
use App\Libraries\ValueUpdateEvaluator;
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
        $fieldType = $field['field_type'];
        $isStaticDisplay = in_array($fieldType, ['page_break', 'review_before_submit'], true);
        $fieldKey  = $isStaticDisplay
            ? (string) $field['field_key']
            : trim((string) ($body['field_key'] ?? $field['field_key']));

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
        if ($fieldType === 'page_break') {
            $options = null;
        } elseif ($fieldType === 'review_before_submit') {
            $options = $this->sanitizeReviewConfig($body['options'] ?? []);
        } elseif (in_array($fieldType, FormFieldModel::OPTION_FIELD_TYPES, true)) {
            $options = array_values(array_filter(
                is_array($body['options'] ?? null) ? $body['options'] : [],
                static fn ($opt) => isset($opt['value'], $opt['label']) && trim((string) $opt['label']) !== ''
            ));
        } elseif ($fieldType === 'text') {
            $options = $this->sanitizeTextConfig($body['options'] ?? []);
        } elseif ($fieldType === 'appointment') {
            $options = $this->sanitizeAppointmentConfig($body['options'] ?? []);
        } elseif ($fieldType === 'product_list') {
            $options = ProductList::sanitizeConfig($body['options'] ?? []);
        } elseif ($fieldType === 'paragraph') {
            $rawBody = (string) ($body['body'] ?? $body['options']['body'] ?? '');
            $options = ['body' => (new \App\Libraries\HtmlSanitizer())->clean($rawBody)];
        } else {
            $options = null;
        }

        $this->fieldModel->update($fieldId, [
            'label'            => $label,
            'field_key'        => $fieldKey,
            'placeholder'      => ($isStaticDisplay || $fieldType === 'address') ? null : (($body['placeholder'] ?? '') !== '' ? $body['placeholder'] : null),
            'help_text'        => $isStaticDisplay ? null : (($body['help_text'] ?? '') !== '' ? $body['help_text'] : null),
            'options'          => $options,
            'is_required'      => ($isStaticDisplay || ($fieldType === 'text' && (bool) ($options['is_hidden'] ?? false))) ? false : (bool) ($body['is_required'] ?? false),
            'validation_rules' => $isStaticDisplay ? null : ($body['validation_rules'] ?? null),
            'conditions'       => $isStaticDisplay ? null : $this->sanitizeConditions($form['id'], $fieldKey, $fieldType, $body['conditions'] ?? null),
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

    /**
     * Store an image for a paragraph field's rich-text editor and return its
     * public URL. Stored under writable/uploads (outside the webroot) and served
     * via the public `form-image/...` route.
     */
    public function uploadImage(int $formId): ResponseInterface
    {
        $form = $this->findFormOrFail($formId);

        return $this->storeImageUpload($form['id'], 'paragraph', 'form-image');
    }

    public function uploadProductImage(int $formId): ResponseInterface
    {
        $form = $this->findFormOrFail($formId);

        return $this->storeImageUpload($form['id'], 'products', 'product-image');
    }

    private function storeImageUpload(int $formId, string $folder, string $route): ResponseInterface
    {
        $file = $this->request->getFile('image');

        if ($file === null || ! $file->isValid()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'No valid image was uploaded.']);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Image must be smaller than 5 MB.']);
        }

        // Verify it is genuinely an image by reading the file header (not the
        // client-supplied type/extension), and derive the extension from that.
        $info = @getimagesize($file->getTempName());
        $extByType = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];

        if ($info === false || ! isset($extByType[$info[2]])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Only JPG, PNG, GIF, or WebP images are allowed.']);
        }

        $directory = UploadPath::base() . $folder . '/' . $formId;
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $name = bin2hex(random_bytes(8)) . '.' . $extByType[$info[2]];
        $file->move($directory, $name);

        return $this->response->setJSON([
            'name' => $name,
            'url'  => site_url($route . '/' . $formId . '/' . $name),
        ]);
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

        if ($fieldType === 'text') {
            return ['is_hidden' => false];
        }

        if ($fieldType === 'paragraph') {
            return ['body' => ''];
        }

        if ($fieldType === 'product_list') {
            return ProductList::DEFAULT_CONFIG;
        }

        if ($fieldType === 'review_before_submit') {
            return ['show_hidden_text' => false];
        }

        return null;
    }

    private function sanitizeTextConfig($raw): array
    {
        $raw = is_array($raw) ? $raw : [];

        return ['is_hidden' => (bool) ($raw['is_hidden'] ?? false)];
    }

    private function sanitizeReviewConfig($raw): array
    {
        $raw = is_array($raw) ? $raw : [];

        return ['show_hidden_text' => (bool) ($raw['show_hidden_text'] ?? false)];
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
    private function sanitizeConditions(int $formId, string $selfKey, string $selfType, $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $fields = $this->fieldModel->getForForm($formId);
        $validKeys = array_column($fields, 'field_key');
        $fieldsByKey = [];
        foreach ($fields as $field) {
            $fieldsByKey[$field['field_key']] = $field;
        }

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

        $updates = $this->sanitizeValueUpdates($raw['updates'] ?? null, $selfKey, $selfType, $fieldsByKey);
        if ($updates !== []) {
            $out['updates'] = $updates;
        }

        return $out === [] ? null : $out;
    }

    /**
     * @param array<string,array<string,mixed>> $fieldsByKey
     *
     * @return array<int,array<string,mixed>>
     */
    private function sanitizeValueUpdates($rawUpdates, string $selfKey, string $selfType, array $fieldsByKey): array
    {
        if (! in_array($selfType, ['text', 'email', 'number', 'textarea', 'date'], true) || ! is_array($rawUpdates)) {
            return [];
        }

        $updates = [];
        foreach ($rawUpdates as $rule) {
            if (! is_array($rule) || ! in_array($rule['action'] ?? '', ValueUpdateEvaluator::ACTIONS, true)) {
                continue;
            }

            $when = [];
            foreach (is_array($rule['when'] ?? null) ? $rule['when'] : [] as $cond) {
                if (! is_array($cond)) {
                    continue;
                }

                $field = (string) ($cond['field'] ?? '');
                $op    = (string) ($cond['operator'] ?? '');
                if (! $this->isUsableReferencedField($field, $selfKey, $fieldsByKey)
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

            $action = (string) $rule['action'];
            $clean = [
                'match'  => ($rule['match'] ?? 'all') === 'any' ? 'any' : 'all',
                'when'   => $when,
                'action' => $action,
            ];

            if ($action === 'copy') {
                $source = (string) ($rule['source'] ?? '');
                if (! $this->isUsableReferencedField($source, $selfKey, $fieldsByKey)) {
                    continue;
                }
                $clean['source'] = $source;
            } elseif ($action === 'set') {
                $clean['value'] = (string) ($rule['value'] ?? '');
            } elseif ($action === 'calculate') {
                if (! in_array($selfType, ['text', 'number'], true)) {
                    continue;
                }
                $formula = trim((string) ($rule['formula'] ?? ''));
                if ($formula === '') {
                    continue;
                }
                $clean['formula'] = $formula;
            }

            $updates[] = $clean;
        }

        return $updates;
    }

    /**
     * @param array<string,array<string,mixed>> $fieldsByKey
     */
    private function isUsableReferencedField(string $fieldKey, string $selfKey, array $fieldsByKey): bool
    {
        if ($fieldKey === '' || $fieldKey === $selfKey || ! isset($fieldsByKey[$fieldKey])) {
            return false;
        }

        return ! in_array($fieldsByKey[$fieldKey]['field_type'] ?? '', FormFieldModel::DISPLAY_ONLY_TYPES, true);
    }
}
