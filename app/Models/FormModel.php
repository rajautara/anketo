<?php

namespace App\Models;

use CodeIgniter\Model;

class FormModel extends Model
{
    protected $table = 'forms';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'title',
        'description',
        'status',
        'theme_id',
        'allow_anonymous',
        'require_login',
        'limit_submissions',
        'expiry_date',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|integer',
        'title' => 'required|min_length[3]|max_length[255]',
        'description' => 'permit_empty|string',
        'status' => 'required|in_list[draft,published,archived]',
        'theme_id' => 'permit_empty|integer',
        'allow_anonymous' => 'permit_empty|boolean',
        'require_login' => 'permit_empty|boolean',
        'limit_submissions' => 'permit_empty|integer|greater_than[0]',
        'expiry_date' => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];
    protected $skipValidation = false;

    /**
     * Get forms by user
     */
    public function getFormsByUser($userId)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get form with fields
     */
    public function getFormWithFields($formId)
    {
        $form = $this->find($formId);
        if (!$form) {
            return null;
        }

        $fieldModel = new FieldModel();
        $form['fields'] = $fieldModel->getFieldsByForm($formId);

        return $form;
    }

    /**
     * Get published forms
     */
    public function getPublishedForms()
    {
        return $this->where('status', 'published')
                    ->where('expiry_date >=', date('Y-m-d H:i:s'))
                    ->findAll();
    }

    /**
     * Get form by unique slug (for public access)
     */
    public function getFormBySlug($slug)
    {
        // For now, using ID as slug. Can be enhanced later
        return $this->find($slug);
    }

    /**
     * Get form statistics
     */
    public function getFormStats($formId)
    {
        $form = $this->find($formId);
        if (!$form) {
            return null;
        }

        $submissionModel = new SubmissionModel();
        $totalSubmissions = $submissionModel->where('form_id', $formId)->countAllResults();
        $newSubmissions = $submissionModel->where('form_id', $formId)
                                          ->where('status', 'new')
                                          ->countAllResults();

        return [
            'total_submissions' => $totalSubmissions,
            'new_submissions' => $newSubmissions,
            'form' => $form,
        ];
    }

    /**
     * Duplicate form
     */
    public function duplicateForm($formId, $userId)
    {
        $form = $this->find($formId);
        if (!$form) {
            return false;
        }

        // Create new form
        $newForm = [
            'user_id' => $userId,
            'title' => $form['title'] . ' (Copy)',
            'description' => $form['description'],
            'status' => 'draft',
            'theme_id' => $form['theme_id'],
            'allow_anonymous' => $form['allow_anonymous'],
            'require_login' => $form['require_login'],
            'limit_submissions' => $form['limit_submissions'],
        ];

        $newFormId = $this->insert($newForm);

        if ($newFormId) {
            // Duplicate fields
            $fieldModel = new FieldModel();
            $fields = $fieldModel->getFieldsByForm($formId);

            foreach ($fields as $field) {
                $newField = [
                    'form_id' => $newFormId,
                    'field_type' => $field['field_type'],
                    'label' => $field['label'],
                    'name' => $field['name'],
                    'placeholder' => $field['placeholder'],
                    'default_value' => $field['default_value'],
                    'options' => $field['options'],
                    'required' => $field['required'],
                    'validation_rules' => $field['validation_rules'],
                    'width' => $field['width'],
                    'order_index' => $field['order_index'],
                    'conditional_logic' => $field['conditional_logic'],
                ];
                $fieldModel->insert($newField);
            }
        }

        return $newFormId;
    }

    /**
     * Check if form can accept submissions
     */
    public function canAcceptSubmissions($formId)
    {
        $form = $this->find($formId);
        if (!$form || $form['status'] !== 'published') {
            return false;
        }

        // Check expiry date
        if ($form['expiry_date'] && strtotime($form['expiry_date']) < time()) {
            return false;
        }

        // Check submission limit
        if ($form['limit_submissions']) {
            $submissionModel = new SubmissionModel();
            $count = $submissionModel->where('form_id', $formId)->countAllResults();
            if ($count >= $form['limit_submissions']) {
                return false;
            }
        }

        return true;
    }
}