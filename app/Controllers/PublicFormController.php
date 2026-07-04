<?php

namespace App\Controllers;

use App\Libraries\AppointmentAvailability;
use App\Libraries\AddressField;
use App\Libraries\ConditionEvaluator;
use App\Libraries\FormulaEvaluator;
use App\Libraries\ProductList;
use App\Libraries\SubmissionAnswerFormatter;
use App\Libraries\SubmissionNotifier;
use App\Libraries\UploadPath;
use App\Libraries\ValueUpdateEvaluator;
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
    protected ConditionEvaluator $conditions;
    protected FormulaEvaluator $formula;
    protected ValueUpdateEvaluator $updates;
    protected SubmissionAnswerFormatter $answerFormatter;

    public function __construct()
    {
        $this->formModel           = new FormModel();
        $this->fieldModel          = new FormFieldModel();
        $this->submissionModel     = new FormSubmissionModel();
        $this->submissionDataModel = new SubmissionDataModel();
        $this->conditions          = new ConditionEvaluator();
        $this->formula             = new FormulaEvaluator();
        $this->updates             = new ValueUpdateEvaluator($this->conditions, $this->formula);
        $this->answerFormatter     = new SubmissionAnswerFormatter();
    }

    public function show(string $token): string
    {
        $form = $this->formModel->findByShareToken($token);

        if ($form === null) {
            throw new PageNotFoundException('This form is not available.');
        }

        $fields    = $this->fieldModel->getForForm($form['id']);
        $submitted = (bool) session()->getFlashdata('submitted');

        // Booked slots per appointment field (for client-side disabling) and the
        // slim per-field config the public JS needs for live conditional logic.
        $bookedSlots = [];
        $formConfig  = [];
        foreach ($fields as $field) {
            if ($field['field_type'] === 'appointment') {
                $bookedSlots[$field['field_key']] = $this->submissionDataModel->getBookedSlots($form['id'], $field['field_key']);
            }

            if (in_array($field['field_type'], ['page_break', 'review_before_submit'], true)) {
                continue;
            }

            $formConfig[] = [
                'key'           => $field['field_key'],
                'label'         => $field['label'],
                'type'          => $field['field_type'],
                'is_required'   => (bool) $field['is_required'],
                'conditions'    => $field['conditions'] ?? null,
                'options'       => in_array($field['field_type'], FormFieldModel::CONFIG_FIELD_TYPES, true)
                    ? ($field['options'] ?? [])
                    : [],
                'option_values' => in_array($field['field_type'], FormFieldModel::OPTION_FIELD_TYPES, true)
                    ? array_column($field['options'] ?? [], 'value')
                    : [],
                'option_labels' => in_array($field['field_type'], FormFieldModel::OPTION_FIELD_TYPES, true)
                    ? $this->optionLabelMap($field)
                    : [],
            ];
        }

        return view('public/show', [
            'form'        => $form,
            'fields'      => $fields,
            'submitted'   => $submitted,
            'errors'      => session()->getFlashdata('errors') ?? [],
            'bookedSlots' => $bookedSlots,
            'formConfig'  => $formConfig,
        ]);
    }

    /**
     * Publicly serve a paragraph image (embedded in a form's rich text).
     * Files live under writable/uploads (outside the webroot); the strict name
     * pattern + basename() prevent path traversal.
     */
    public function image(int $formId, string $name)
    {
        return $this->serveUploadedImage($formId, $name, 'paragraph');
    }

    public function productImage(int $formId, string $name)
    {
        return $this->serveUploadedImage($formId, $name, 'products');
    }

    private function serveUploadedImage(int $formId, string $name, string $folder)
    {
        $name = basename($name);

        if (preg_match('/^[a-f0-9]{16}\.(jpg|png|gif|webp)$/', $name) !== 1) {
            throw new PageNotFoundException('Image not found.');
        }

        $path = UploadPath::base() . $folder . '/' . $formId . '/' . $name;
        if (! is_file($path)) {
            throw new PageNotFoundException('Image not found.');
        }

        $mimes = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        $ext   = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return $this->response
            ->setHeader('Content-Type', $mimes[$ext] ?? 'application/octet-stream')
            ->setHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->setBody(file_get_contents($path));
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
        $logicAnswers = $this->logicAnswers($fields, $postAnswers);
        $formulaAnswers = $this->formulaAnswers($fields, $postAnswers);

        $errors = [];
        $toSave = [];
        $productRequests = [];

        foreach ($fields as $field) {
            $key  = $field['field_key'];
            $type = $field['field_type'];

            // Display-only fields are never stored.
            if (in_array($type, FormFieldModel::DISPLAY_ONLY_TYPES, true)) {
                continue;
            }

            // Server-side conditional logic (never trust the client). A hidden
            // field is neither validated nor stored; requiredness is the
            // effective value after evaluating any `require` rules.
            $flags = $this->conditions->evaluate(
                $field['conditions']['rules'] ?? [],
                $logicAnswers,
                (bool) $field['is_required']
            );
            if (! $flags['visible']) {
                continue;
            }
            // A disabled (read-only) field can't be filled by the visitor, so it
            // is never treated as required regardless of its base/require rules.
            $field['is_required'] = $flags['required'] && ! $flags['disabled'];

            // Automated update fields: recompute server-side and ignore any
            // client-submitted value. Updates supersede legacy calc formulas.
            if (! empty($field['conditions']['updates']) && $this->isValueUpdateTarget($type)) {
                $computed = $this->updates->evaluate($field['conditions']['updates'], $logicAnswers, $formulaAnswers);
                if ($computed !== null && $computed !== '') {
                    $fieldError = $this->validateScalarField($field, $computed);
                    if ($fieldError !== null) {
                        $errors[$key] = $fieldError;
                    }
                }
                $toSave[] = $this->answerRow($field, ($computed !== null && $computed !== '') ? $computed : null, null);

                continue;
            }

            // Calculated fields: recompute server-side, ignore any client value.
            if (! empty($field['conditions']['calc']['formula'])) {
                $computed = $this->formula->evaluate($field['conditions']['calc']['formula'], $formulaAnswers);
                $toSave[] = $this->answerRow($field, $computed !== '' ? $computed : null, null);

                continue;
            }

            if ($type === 'product_list') {
                [$value, $productError] = ProductList::selectionValue($field, $postAnswers[$key] ?? []);
                if ($productError !== null) {
                    $errors[$key] = $productError;
                }
                $toSave[] = ['__product_field_id' => (int) $field['id']];
                $productRequests[(int) $field['id']] = [
                    'field'     => $field,
                    'submitted' => $postAnswers[$key] ?? [],
                ];

                continue;
            }

            if ($type === 'appointment') {
                [$value, $apptError] = $this->handleAppointmentField($form['id'], $field, (string) ($postAnswers[$key] ?? ''));
                if ($apptError !== null) {
                    $errors[$key] = $apptError;
                }
                $toSave[] = $this->answerRow($field, $value, null);

                continue;
            }

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

            if ($type === 'address') {
                $parts = AddressField::sanitize($postAnswers[$key] ?? []);
                $addressError = AddressField::validationError($field, $parts);
                if ($addressError !== null) {
                    $errors[$key] = $addressError;
                }
                $toSave[] = $this->answerRow($field, AddressField::storedValue($parts), null);

                continue;
            }

            $raw = trim((string) ($postAnswers[$key] ?? ''));
            $fieldError = $this->validateScalarField($field, $raw);
            if ($fieldError !== null) {
                $errors[$key] = $fieldError;
            }

            $toSave[] = $this->answerRow($field, $this->answerFormatter->storedValueForField($field, $raw), null);
        }

        if ($errors !== []) {
            return redirect()->to(site_url('f/' . $token))->withInput()->with('errors', $errors);
        }

        $db = db_connect();
        $db->transBegin();

        $lockedProductAnswers = [];
        if ($productRequests !== []) {
            $lockedProductAnswers = $this->lockAndApplyProductSelections($form['id'], $productRequests);
            foreach ($lockedProductAnswers as $fieldId => $result) {
                if (($result['error'] ?? null) !== null) {
                    $db->transRollback();

                    $field = $productRequests[$fieldId]['field'];
                    return redirect()->to(site_url('f/' . $token))
                        ->withInput()
                        ->with('errors', [$field['field_key'] => $result['error']]);
                }
            }
        }

        $answersToSave = [];
        foreach ($toSave as $row) {
            if (isset($row['__product_field_id'])) {
                $answersToSave[] = $lockedProductAnswers[$row['__product_field_id']]['answer'];
                continue;
            }
            $answersToSave[] = $row;
        }

        $submissionId = $this->submissionModel->insert([
            'form_id'    => $form['id'],
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) ($this->request->getUserAgent()?->getAgentString() ?? ''),
        ]);

        $this->submissionDataModel->saveAnswers($submissionId, $answersToSave);

        $db->transCommit();

        if ($db->transStatus() !== false) {
            (new SubmissionNotifier())->notify($form, (int) $submissionId, $answersToSave);
        }

        session()->setFlashdata('submitted', true);

        return redirect()->to(site_url('f/' . $token));
    }

    /**
     * @param array<int,array{field:array,submitted:mixed}> $productRequests
     * @return array<int,array{answer?:array,error?:string}>
     */
    private function lockAndApplyProductSelections(int $formId, array $productRequests): array
    {
        $ids = array_keys($productRequests);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = db_connect()->query(
            'SELECT * FROM `form_fields` WHERE `form_id` = ? AND `id` IN (' . $placeholders . ') FOR UPDATE',
            array_merge([$formId], $ids)
        )->getResultArray();

        $byId = [];
        foreach ($rows as $row) {
            $row['options'] = is_string($row['options'] ?? null) && $row['options'] !== ''
                ? (json_decode($row['options'], true) ?: [])
                : ($row['options'] ?? []);
            $row['is_required'] = (bool) $row['is_required'];
            $byId[(int) $row['id']] = $row;
        }

        $out = [];
        foreach ($productRequests as $fieldId => $request) {
            $field = $byId[$fieldId] ?? null;
            if ($field === null || $field['field_type'] !== 'product_list') {
                $out[$fieldId] = ['error' => 'Product list changed. Please try again.'];
                continue;
            }

            [$value, $error] = ProductList::selectionValue($field, $request['submitted']);
            if ($error !== null) {
                $out[$fieldId] = ['error' => $error];
                continue;
            }

            if ($value !== null) {
                $this->fieldModel->update($fieldId, [
                    'options' => ProductList::decrementStock($field['options'], $value),
                ]);
            }

            $out[$fieldId] = ['answer' => $this->answerRow($field, $value, null)];
        }

        return $out;
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

    private function isValueUpdateTarget(string $type): bool
    {
        return in_array($type, ['text', 'email', 'number', 'textarea', 'date'], true);
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     * @param array<string,mixed>            $answers
     *
     * @return array<string,mixed>
     */
    private function logicAnswers(array $fields, array $answers): array
    {
        $out = $answers;

        foreach ($fields as $field) {
            if (($field['field_type'] ?? '') !== 'address') {
                continue;
            }

            $key = (string) ($field['field_key'] ?? '');
            if ($key === '') {
                continue;
            }

            $parts = AddressField::sanitize($answers[$key] ?? []);
            $out[$key] = AddressField::isBlank($parts) ? '' : AddressField::formatParts($parts, ', ');
        }

        return $out;
    }

    /**
     * Formula fields should see choice labels, not generated internal values
     * like option_1. Condition matching still uses the raw submitted values.
     *
     * @param array<int,array<string,mixed>> $fields
     * @param array<string,mixed>            $answers
     *
     * @return array<string,mixed>
     */
    private function formulaAnswers(array $fields, array $answers): array
    {
        $out = $answers;

        foreach ($fields as $field) {
            $key = (string) ($field['field_key'] ?? '');
            if ($key === '' || ! isset($answers[$key])) {
                continue;
            }

            $value = $answers[$key];

            if (($field['field_type'] ?? '') === 'product_list') {
                $total = ProductList::selectionTotal($field, $value);
                if ($total !== null) {
                    $out[$key] = (string) $total;
                    $out[(string) ($field['label'] ?? '')] = (string) $total;
                }

                continue;
            }

            if (($field['field_type'] ?? '') === 'address') {
                $parts = AddressField::sanitize($value);
                $displayValue = AddressField::isBlank($parts) ? '' : AddressField::formatParts($parts, ', ');
                $out[$key] = $displayValue;
                $out[(string) ($field['label'] ?? '')] = $displayValue;

                continue;
            }

            if (! in_array($field['field_type'] ?? '', ['radio', 'select'], true)) {
                $out[(string) ($field['label'] ?? '')] = $value;
                continue;
            }

            if (is_array($value)) {
                continue;
            }

            $labels = $this->optionLabelMap($field);
            $displayValue = $labels[(string) $value] ?? $value;
            $out[$key] = $displayValue;
            $out[(string) ($field['label'] ?? '')] = $displayValue;
        }

        return $out;
    }

    /**
     * @return array<string,string> option value => option label
     */
    private function optionLabelMap(array $field): array
    {
        $map = [];

        foreach (is_array($field['options'] ?? null) ? $field['options'] : [] as $option) {
            if (! is_array($option) || ! isset($option['value'], $option['label'])) {
                continue;
            }

            $map[(string) $option['value']] = (string) $option['label'];
        }

        return $map;
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

        return [$this->answerFormatter->storedValueForField($field, $selected), null];
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

        $directory = UploadPath::base() . 'forms/' . $formId;
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($directory, $newName);

        $relativePath = 'forms/' . $formId . '/' . $newName;

        return [$file->getClientName(), $relativePath, null];
    }

    /**
     * Validate a chosen appointment slot against the field's availability config
     * and re-check it isn't already booked. Stores the plain "Y-m-d H:i" string.
     *
     * @return array{0: ?string, 1: ?string} [value or null, error message or null]
     */
    private function handleAppointmentField(int $formId, array $field, string $value): array
    {
        $value  = trim($value);
        $config = is_array($field['options'] ?? null) ? $field['options'] : [];

        if ($value === '') {
            return [null, $field['is_required'] ? $field['label'] . ' is required.' : null];
        }

        if (! AppointmentAvailability::isValidSlot($value, $config)) {
            return [null, 'Please choose an available time slot for ' . $field['label'] . '.'];
        }

        // Reject past dates and dates beyond the booking window.
        $slot  = \CodeIgniter\I18n\Time::createFromFormat('Y-m-d H:i', $value);
        $today = \CodeIgniter\I18n\Time::today();
        $maxDays = (int) ($config['date_max_days'] ?? AppointmentAvailability::DEFAULT_CONFIG['date_max_days']);
        if ($slot->isBefore($today) || $slot->isAfter($today->addDays($maxDays)->setTime(23, 59, 59))) {
            return [null, 'Please choose a date within the available range for ' . $field['label'] . '.'];
        }

        if (in_array($value, $this->submissionDataModel->getBookedSlots($formId, $field['field_key']), true)) {
            return [null, 'That time slot for ' . $field['label'] . ' has just been booked. Please pick another.'];
        }

        return [$value, null];
    }
}
