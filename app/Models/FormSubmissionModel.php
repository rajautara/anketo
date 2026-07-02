<?php

namespace App\Models;

use CodeIgniter\Model;

class FormSubmissionModel extends Model
{
    protected $table            = 'form_submissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'form_id',
        'ip_address',
        'user_agent',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getForForm(int $formId): array
    {
        return $this->where('form_id', $formId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function countForForm(int $formId): int
    {
        return $this->where('form_id', $formId)->countAllResults();
    }
}
