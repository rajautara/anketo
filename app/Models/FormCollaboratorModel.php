<?php

namespace App\Models;

use CodeIgniter\Model;

class FormCollaboratorModel extends Model
{
    public const FORM_ACCESS = ['none', 'view', 'edit'];
    public const SUBMISSION_ACCESS = ['none', 'view', 'export'];

    protected $table            = 'form_collaborators';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'form_id',
        'user_id',
        'form_access',
        'submission_access',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'form_id'           => 'required|is_natural_no_zero',
        'user_id'           => 'required|is_natural_no_zero',
        'form_access'       => 'required|in_list[none,view,edit]',
        'submission_access' => 'required|in_list[none,view,export]',
    ];

    public function findForFormAndUser(int $formId, int $userId): ?array
    {
        return $this->where('form_id', $formId)
            ->where('user_id', $userId)
            ->first();
    }

    public function getForForm(int $formId): array
    {
        return $this->where('form_id', $formId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}
