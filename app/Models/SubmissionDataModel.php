<?php

namespace App\Models;

use CodeIgniter\Model;

class SubmissionDataModel extends Model
{
    protected $table = 'submission_data';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'submission_id',
        'field_id',
        'value',
        'file_path',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'submission_id' => 'required|integer',
        'field_id' => 'required|integer',
        'value' => 'permit_empty',
        'file_path' => 'permit_empty|max_length[255]',
    ];
    protected $skipValidation = false;

    /**
     * Get data by submission
     */
    public function getDataBySubmission($submissionId)
    {
        return $this->where('submission_id', $submissionId)->findAll();
    }

    /**
     * Get data by field
     */
    public function getDataByField($fieldId)
    {
        return $this->where('field_id', $fieldId)->findAll();
    }

    /**
     * Save submission data
     */
    public function saveSubmissionData($submissionId, $formData)
    {
        $fieldModel = new FieldModel();
        $fields = $fieldModel->getFieldsByForm($formData['form_id']);

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $value = isset($formData[$fieldName]) ? $formData[$fieldName] : null;

            // Handle checkbox arrays
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $data = [
                'submission_id' => $submissionId,
                'field_id' => $field['id'],
                'value' => $value,
            ];

            $this->insert($data);
        }

        return true;
    }

    /**
     * Delete data by submission
     */
    public function deleteDataBySubmission($submissionId)
    {
        return $this->where('submission_id', $submissionId)->delete();
    }

    /**
     * Get submission data as key-value pairs
     */
    public function getDataAsKeyValue($submissionId)
    {
        $data = $this->getDataBySubmission($submissionId);
        $result = [];

        foreach ($data as $item) {
            $result[$item['field_id']] = [
                'value' => $item['value'],
                'file_path' => $item['file_path'],
            ];
        }

        return $result;
    }
}