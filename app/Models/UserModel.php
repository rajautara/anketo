<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'email',
        'password',
        'role_id',
        'status',
        'remember_token',
        'reset_token',
        'reset_token_expires',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[100]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[6]',
        'role_id' => 'required|integer',
        'status' => 'required|in_list[active,inactive,suspended]',
    ];
    protected $validationMessages = [
        'email' => [
            'is_unique' => 'Email already exists',
        ],
    ];
    protected $skipValidation = false;

    /**
     * Get user with role
     */
    public function getUserWithRole($id)
    {
        return $this->select('users.*, roles.name as role_name, roles.permissions')
                    ->join('roles', 'roles.id = users.role_id')
                    ->where('users.id', $id)
                    ->first();
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email)
    {
        return $this->select('users.*, roles.name as role_name, roles.permissions')
                    ->join('roles', 'roles.id = users.role_id')
                    ->where('users.email', $email)
                    ->first();
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($userId, $permission)
    {
        $user = $this->getUserWithRole($userId);
        if (!$user) {
            return false;
        }

        $permissions = json_decode($user['permissions'], true);
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * Get users by role
     */
    public function getUsersByRole($roleId)
    {
        return $this->where('role_id', $roleId)->findAll();
    }

    /**
     * Get active users
     */
    public function getActiveUsers()
    {
        return $this->where('status', 'active')->findAll();
    }

    /**
     * Hash password before insert
     */
    protected function beforeInsert(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_BCRYPT);
        }
        return $data;
    }

    /**
     * Hash password before update
     */
    protected function beforeUpdate(array $data)
    {
        if (isset($data['data']['password']) && !empty($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_BCRYPT);
        }
        return $data;
    }
}