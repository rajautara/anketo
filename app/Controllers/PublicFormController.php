<?php

namespace App\Controllers;

use App\Libraries\AppointmentAvailability;
use App\Libraries\ConditionEvaluator;
use App\Libraries\FormulaEvaluator;
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
    protected ConditionEvaluator $conditions;
    protected FormulaEvaluator $formula;

    public function __construct()
    {
        $this->formModel           = new FormModel();
        $this->fieldModel          = new FormFieldModel();
        $this->submissionModel     = new FormSubmissionModel();
        $this->submissionDataModel = new SubmissionDataModel();
        $this->conditions          = new ConditionEvaluator();
        $this->formula             = new FormulaEvaluator();
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

            $formConfig[] = [
                'key'           => $field['field_key'],
                'type'          => $field['field_type'],
                'is_required'   => (bool) $field['is_required'],
                'conditions'    => $field['conditions'] ?? null,
                'option_values' => in_array($field['field_type'], FormFieldModel::OPTION_FIELD_TYPES, true)
                    ? array_column($field['options'] ?? [], 'value')
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
        $name = basename($name);

        if (preg_match('/^[a-f0-9]{16}\.(jpg|png|gif|webp)$/', $name) !== 1) {
            throw new PageNotFoundException('Image not found.');
        }

        $path = WRITEPATH . 'uploads/paragraph/' . $formId . '/' . $name;
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

        $errors = [];
        $toSave = [];

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
                $postAnswers,
                (bool) $field['is_required']
            );
            if (! $flags['visible']) {
                continue;
            }
            // A disabled (read-only) field can't be filled by the visitor, so it
            // is never treated as required regardless of its base/require rules.
            $field['is_required'] = $flags['required'] && ! $flags['disabled'];

            // Calculated fields: recompute server-side, ignore any client value.
            if (! empty($field['conditions']['calc']['formula'])) {
                $computed = $this->formula->evaluate($field['conditions']['calc']['formula'], $postAnswers);
                $toSave[] = $this->answerRow($field, $computed !== '' ? $computed : null, null);

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
