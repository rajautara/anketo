<?php

namespace App\Models;

use CodeIgniter\Model;

class FieldModel extends Model
{
    protected $table = 'form_fields';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'form_id',
        'field_type',
        'label',
        'name',
        'placeholder',
        'default_value',
        'options',
        'required',
        'validation_rules',
        'width',
        'order_index',
        'conditional_logic',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'form_id' => 'required|integer',
        'field_type' => 'required|in_list[text,email,number,textarea,checkbox,radio,select,file,date,time,datetime,password,hidden,paragraph,divider]',
        'name' => 'required|min_length[2]|max_length[100]',
        'label' => 'permit_empty|max_length[255]',
        'placeholder' => 'permit_empty|max_length[255]',
        'default_value' => 'permit_empty',
        'options' => 'permit_empty',
        'required' => 'permit_empty|boolean',
        'validation_rules' => 'permit_empty',
        'width' => 'permit_empty|integer|greater_than[0]|less_than_equal[100]',
        'order_index' => 'required|integer',
        'conditional_logic' => 'permit_empty',
    ];
    protected $skipValidation = false;

    /**
     * Get fields by form
     */
    public function getFieldsByForm($formId)
    {
        return $this->where('form_id', $formId)
                    ->orderBy('order_index', 'ASC')
                    ->findAll();
    }

    /**
     * Get field by name within a form
     */
    public function getFieldByName($formId, $fieldName)
    {
        return $this->where('form_id', $formId)
                    ->where('name', $fieldName)
                    ->first();
    }

    /**
     * Get maximum order index for a form
     */
    public function getMaxOrderIndex($formId)
    {
        $result = $this->selectMax('order_index')
                      ->where('form_id', $formId)
                      ->first();
        return $result ? (int) $result['order_index'] : 0;
    }

    /**
     * Reorder fields
     */
    public function reorderFields($fieldIds)
    {
        foreach ($fieldIds as $index => $fieldId) {
            $this->update($fieldId, ['order_index' => $index]);
        }
        return true;
    }

    /**
     * Delete all fields for a form
     */
    public function deleteFieldsByForm($formId)
    {
        return $this->where('form_id', $formId)->delete();
    }

    /**
     * Get available field types
     */
    public static function getAvailableFieldTypes()
    {
        return [
            'text' => [
                'name' => 'Text Input',
                'icon' => 'fa-font',
                'has_options' => false,
                'supports_validation' => true,
            ],
            'email' => [
                'name' => 'Email Input',
                'icon' => 'fa-envelope',
                'has_options' => false,
                'supports_validation' => true,
            ],
            'number' => [
                'name' => 'Number Input',
                'icon' => 'fa-hashtag',
                'has_options' => false,
                'supports_validation' => true,
            ],
            'textarea' => [
                'name' => 'Text Area',
                'icon' => 'fa-align-left',
                'has_options' => false,
                'supports_validation' => true,
            ],
            'checkbox' => [
                'name' => 'Checkbox',
                'icon' => 'fa-check-square',
                'has_options' => true,
                'supports_validation' => false,
            ],
            'radio' => [
                'name' => 'Radio Button',
                'icon' => 'fa-dot-circle',
                'has_options' => true,
                'supports_validation' => false,
            ],
            'select' => [
                'name' => 'Dropdown',
                'icon' => 'fa-caret-down',
                'has_options' => true,
                'supports_validation' => false,
            ],
            'file' => [
                'name' => 'File Upload',
                'icon' => 'fa-upload',
                'has_options' => false,
                'supports_validation' => true,
            ],
            'date' => [
                'name' => 'Date Picker',
                'icon' => 'fa-calendar',
                'has_options' => false,
                'supports_validation' => false,
            ],
            'time' => [
                'name' => 'Time Picker',
                'icon' => 'fa-clock',
                'has_options' => false,
                'supports_validation' => false,
            ],
            'datetime' => [
                'name' => 'Date Time Picker',
                'icon' => 'fa-calendar-alt',
                'has_options' => false,
                'supports_validation' => false,
            ],
            'password' => [
                'name' => 'Password',
                'icon' => 'fa-lock',
                'has_options' => false,
                'supports_validation' => true,
            ],
            'hidden' => [
                'name' => 'Hidden Field',
                'icon' => 'fa-eye-slash',
                'has_options' => false,
                'supports_validation' => false,
            ],
            'paragraph' => [
                'name' => 'Paragraph',
                'icon' => 'fa-paragraph',
                'has_options' => false,
                'supports_validation' => false,
            ],
            'divider' => [
                'name' => 'Divider',
                'icon' => 'fa-minus',
                'has_options' => false,
                'supports_validation' => false,
            ],
        ];
    }

    /**
     * Generate unique field name
     */
    public static function generateFieldName($label, $formId = null)
    {
        $name = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $label), '_'));
        $name = preg_replace('/_+/', '_', $name);
        
        if (empty($name)) {
            $name = 'field_' . time();
        }

        // Check if name exists
        $model = new self();
        if ($formId) {
            $existing = $model->getFieldByName($formId, $name);
            if ($existing) {
                $name .= '_' . rand(1000, 9999);
            }
        }

        return $name;
    }
}