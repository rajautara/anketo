<?php

namespace App\Models;

use CodeIgniter\Model;

class SubmissionDataModel extends Model
{
    protected $table            = 'submission_data';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'submission_id',
        'field_id',
        'field_key',
        'field_label',
        'value',
        'file_path',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Bulk-insert one row per answer for a submission.
     *
     * @param list<array{field_id:int,field_key:string,field_label:string,value:?string,file_path:?string}> $answers
     */
    public function saveAnswers(int $submissionId, array $answers): void
    {
        if ($answers === []) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $rows = array_map(
            static fn (array $answer): array => [
                'submission_id' => $submissionId,
                'field_id'      => $answer['field_id'],
                'field_key'     => $answer['field_key'],
                'field_label'   => $answer['field_label'],
                'value'         => $answer['value'],
                'file_path'     => $answer['file_path'] ?? null,
                'created_at'    => $now,
            ],
            $answers
        );

        $this->insertBatch($rows);
    }

    public function getForSubmission(int $submissionId): array
    {
        return $this->where('submission_id', $submissionId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * All answers for every submission of a form, in one query.
     * Group by `submission_id` in the caller to avoid an N+1 per submission.
     */
    public function getForForm(int $formId): array
    {
        return $this->select('submission_data.*')
            ->join('form_submissions', 'form_submissions.id = submission_data.submission_id')
            ->where('form_submissions.form_id', $formId)
            ->orderBy('submission_data.submission_id', 'ASC')
            ->orderBy('submission_data.id', 'ASC')
            ->findAll();
    }
}
