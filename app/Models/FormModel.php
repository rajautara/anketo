<?php

namespace App\Models;

use CodeIgniter\Model;

class FormModel extends Model
{
    protected $table            = 'forms';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'title',
        'description',
        'share_token',
        'status',
        'submit_button_text',
        'success_message',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'title'  => 'required|max_length[255]',
        'status' => 'required|in_list[draft,published,archived]',
    ];

    /**
     * Forms visible to the given user: their own forms, or every form if $isAdmin.
     */
    public function getForUser(int $userId, bool $isAdmin = false): array
    {
        $builder = $this->select(
            'forms.*, (SELECT COUNT(*) FROM form_submissions WHERE form_submissions.form_id = forms.id) AS submission_count'
        );

        if (! $isAdmin) {
            $builder->where('forms.user_id', $userId);
        }

        return $builder->orderBy('forms.created_at', 'DESC')->findAll();
    }

    /**
     * A published form looked up by its public share token, or null if not found/not published.
     */
    public function findByShareToken(string $token): ?array
    {
        return $this->where('share_token', $token)
            ->where('status', 'published')
            ->first();
    }

    public function generateUniqueShareToken(): string
    {
        do {
            $token = bin2hex(random_bytes(12));
        } while ($this->where('share_token', $token)->first() !== null);

        return $token;
    }

    public function publish(int $id): bool
    {
        return $this->update($id, ['status' => 'published']);
    }

    public function unpublish(int $id): bool
    {
        return $this->update($id, ['status' => 'draft']);
    }

    /**
     * Whether $userId may view/manage a form owned by $ownerId.
     */
    public function userCanAccess(int $ownerId, int $userId, bool $isAdmin): bool
    {
        return $isAdmin || $ownerId === $userId;
    }
}
