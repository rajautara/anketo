<?php

namespace App\Models;

use CodeIgniter\Model;

class FormFieldModel extends Model
{
    protected $table            = 'form_fields';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'form_id',
        'field_type',
        'label',
        'field_key',
        'placeholder',
        'help_text',
        'options',
        'is_required',
        'validation_rules',
        'conditions',
        'sort_order',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected array $casts = [
        'options'     => '?json-array',
        'is_required' => 'boolean',
        'conditions'  => '?json-array',
    ];

    protected $validationRules = [
        'field_type' => 'required|in_list[text,email,number,textarea,checkbox,radio,select,date,file,paragraph,appointment]',
        'label'      => 'required|max_length[255]',
        'field_key'  => 'required|max_length[100]',
    ];

    public const FIELD_TYPES = ['text', 'email', 'number', 'textarea', 'checkbox', 'radio', 'select', 'date', 'file', 'paragraph', 'appointment'];

    /** Field types that use an `options` list of {value,label} choices. */
    public const OPTION_FIELD_TYPES = ['checkbox', 'radio', 'select'];

    /** Display-only types: render no input, store no answer, excluded from CSV columns. */
    public const DISPLAY_ONLY_TYPES = ['paragraph'];

    /** Types whose `options` JSON holds a config object (not a {value,label} choice list). */
    public const CONFIG_FIELD_TYPES = ['appointment'];

    public function getForForm(int $formId): array
    {
        return $this->where('form_id', $formId)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    public function nextSortOrder(int $formId): int
    {
        $max = $this->where('form_id', $formId)->selectMax('sort_order')->first();

        return $max && $max['sort_order'] !== null ? ((int) $max['sort_order'] + 1) : 0;
    }

    /**
     * Persists a new sort_order for each field id, in the given order.
     * Ignores ids that don't belong to $formId.
     */
    public function reorder(int $formId, array $orderedIds): bool
    {
        // Cast to int: the DB driver returns ids as strings, so a strict
        // in_array() against an (int) id below would never match otherwise
        // (leaving sort_order unchanged — fields silently snap back on reload).
        $ownedIds = array_map('intval', array_column($this->where('form_id', $formId)->select('id')->findAll(), 'id'));

        $this->db->transStart();

        $position = 0;
        foreach ($orderedIds as $fieldId) {
            if (! in_array((int) $fieldId, $ownedIds, true)) {
                continue;
            }

            $this->update($fieldId, ['sort_order' => $position]);
            $position++;
        }

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    public function generateUniqueFieldKey(int $formId, string $label): string
    {
        helper('url');

        $base = url_title($label !== '' ? $label : 'field', '_', true);
        $base = $base !== '' ? $base : 'field';
        $key  = $base;
        $i    = 2;

        while ($this->where('form_id', $formId)->where('field_key', $key)->first() !== null) {
            $key = $base . '_' . $i;
            $i++;
        }

        return $key;
    }

    public function defaultLabelFor(string $fieldType): string
    {
        $labels = [
            'text'     => 'Text Field',
            'email'    => 'Email',
            'number'   => 'Number',
            'textarea' => 'Long Answer',
            'checkbox' => 'Checkboxes',
            'radio'    => 'Multiple Choice',
            'select'   => 'Dropdown',
            'date'     => 'Date',
            'file'     => 'File Upload',
            'paragraph'   => 'Paragraph',
            'appointment' => 'Appointment',
        ];

        return $labels[$fieldType] ?? 'Untitled Field';
    }
}
