<?php

namespace App\Models;

use CodeIgniter\Model;

class SubmissionModel extends Model
{
    protected $table = 'form_submissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'form_id',
        'submitter_name',
        'submitter_email',
        'ip_address',
        'user_agent',
        'status',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'submitted_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'form_id' => 'required|integer',
        'submitter_name' => 'permit_empty|max_length[100]',
        'submitter_email' => 'permit_empty|valid_email|max_length[150]',
        'ip_address' => 'permit_empty|max_length[45]',
        'user_agent' => 'permit_empty',
        'status' => 'required|in_list[new,read,archived]',
    ];
    protected $skipValidation = false;

    /**
     * Get submissions by form
     */
    public function getSubmissionsByForm($formId, $limit = null, $offset = 0)
    {
        $builder = $this->where('form_id', $formId)
                       ->orderBy('submitted_at', 'DESC');

        if ($limit) {
            $builder->limit($limit, $offset);
        }

        return $builder->findAll();
    }

    /**
     * Get submission with data
     */
    public function getSubmissionWithData($submissionId)
    {
        $submission = $this->find($submissionId);
        if (!$submission) {
            return null;
        }

        $submissionDataModel = new SubmissionDataModel();
        $submission['data'] = $submissionDataModel->getDataBySubmission($submissionId);

        return $submission;
    }

    /**
     * Get new submissions count
     */
    public function getNewSubmissionsCount($formId = null)
    {
        $builder = $this->where('status', 'new');
        
        if ($formId) {
            $builder->where('form_id', $formId);
        }

        return $builder->countAllResults();
    }

    /**
     * Mark submission as read
     */
    public function markAsRead($submissionId)
    {
        return $this->update($submissionId, ['status' => 'read']);
    }

    /**
     * Mark all submissions as read for a form
     */
    public function markAllAsRead($formId)
    {
        return $this->where('form_id', $formId)
                   ->where('status', 'new')
                   ->set(['status' => 'read'])
                   ->update();
    }

    /**
     * Archive submission
     */
    public function archiveSubmission($submissionId)
    {
        return $this->update($submissionId, ['status' => 'archived']);
    }

    /**
     * Get submissions for export
     */
    public function getSubmissionsForExport($formId)
    {
        $submissions = $this->getSubmissionsByForm($formId);
        $submissionDataModel = new SubmissionDataModel();
        $fieldModel = new FieldModel();
        $fields = $fieldModel->getFieldsByForm($formId);

        $exportData = [];

        foreach ($submissions as $submission) {
            $row = [
                'Submission ID' => $submission['id'],
                'Submitted At' => $submission['submitted_at'],
                'Submitter Name' => $submission['submitter_name'] ?? '',
                'Submitter Email' => $submission['submitter_email'] ?? '',
                'IP Address' => $submission['ip_address'] ?? '',
            ];

            $data = $submissionDataModel->getDataBySubmission($submission['id']);
            $dataMap = [];

            foreach ($data as $item) {
                $dataMap[$item['field_id']] = $item['value'];
            }

            foreach ($fields as $field) {
                if ($field['field_type'] !== 'paragraph' && $field['field_type'] !== 'divider') {
                    $row[$field['label']] = $dataMap[$field['id']] ?? '';
                }
            }

            $exportData[] = $row;
        }

        return $exportData;
    }

    /**
     * Get submission statistics
     */
    public function getSubmissionStats($formId)
    {
        $total = $this->where('form_id', $formId)->countAllResults();
        $new = $this->where('form_id', $formId)->where('status', 'new')->countAllResults();
        $read = $this->where('form_id', $formId)->where('status', 'read')->countAllResults();
        $archived = $this->where('form_id', $formId)->where('status', 'archived')->countAllResults();

        return [
            'total' => $total,
            'new' => $new,
            'read' => $read,
            'archived' => $archived,
        ];
    }
}