<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'description',
        'permissions',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[50]|is_unique[roles.name,id,{id}]',
        'description' => 'permit_empty|string',
        'permissions' => 'permit_empty',
    ];
    protected $validationMessages = [
        'name' => [
            'is_unique' => 'Role name already exists',
        ],
    ];
    protected $skipValidation = false;

    /**
     * Get role by name
     */
    public function getRoleByName($name)
    {
        return $this->where('name', $name)->first();
    }

    /**
     * Check if role has permission
     */
    public function hasPermission($roleId, $permission)
    {
        $role = $this->find($roleId);
        if (!$role) {
            return false;
        }

        $permissions = json_decode($role['permissions'], true);
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * Get all permissions for a role
     */
    public function getPermissions($roleId)
    {
        $role = $this->find($roleId);
        if (!$role) {
            return [];
        }

        return json_decode($role['permissions'], true) ?? [];
    }

    /**
     * Update permissions for a role
     */
    public function updatePermissions($roleId, $permissions)
    {
        return $this->update($roleId, [
            'permissions' => json_encode($permissions),
        ]);
    }

    /**
     * Get all available permissions
     */
    public static function getAvailablePermissions()
    {
        return [
            'forms.create' => 'Create Forms',
            'forms.read' => 'View Forms',
            'forms.update' => 'Edit Forms',
            'forms.delete' => 'Delete Forms',
            'forms.publish' => 'Publish Forms',
            'submissions.read' => 'View Submissions',
            'submissions.export' => 'Export Submissions',
            'users.manage' => 'Manage Users',
            'roles.manage' => 'Manage Roles',
        ];
    }
}