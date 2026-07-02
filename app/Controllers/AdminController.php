<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\FormModel;
use App\Models\SubmissionModel;

class AdminController extends BaseController
{
    protected $userModel;
    protected $roleModel;
    protected $formModel;
    protected $submissionModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->formModel = new FormModel();
        $this->submissionModel = new SubmissionModel();
    }

    /**
     * Admin dashboard
     */
    public function index()
    {
        $this->requireAdmin();

        $stats = [
            'total_users' => $this->userModel->countAll(),
            'active_users' => $this->userModel->where('status', 'active')->countAllResults(),
            'total_forms' => $this->formModel->countAll(),
            'published_forms' => $this->formModel->where('status', 'published')->countAllResults(),
            'total_submissions' => $this->submissionModel->countAll(),
            'new_submissions' => $this->submissionModel->where('status', 'new')->countAllResults(),
        ];

        return view('admin/index', [
            'stats' => $stats,
        ]);
    }

    /**
     * Users management
     */
    public function users()
    {
        $this->requireAdmin();

        $users = $this->userModel->select('users.*, roles.name as role_name')
                                 ->join('roles', 'roles.id = users.role_id')
                                 ->findAll();

        return view('admin/users', [
            'users' => $users,
        ]);
    }

    /**
     * Show create user page
     */
    public function createUser()
    {
        $this->requireAdmin();

        $roles = $this->roleModel->findAll();

        return view('admin/create_user', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store new user
     */
    public function storeUser()
    {
        $this->requireAdmin();

        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]',
            'role_id' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'role_id' => $this->request->getPost('role_id'),
            'status' => 'active',
        ];

        $userId = $this->userModel->insert($data);

        if ($userId) {
            return redirect()->to('/admin/users')->with('success', 'User created successfully');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to create user');
    }

    /**
     * Show edit user page
     */
    public function editUser($id)
    {
        $this->requireAdmin();

        $user = $this->userModel->find($id);
        $roles = $this->roleModel->findAll();

        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'User not found');
        }

        return view('admin/edit_user', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update user
     */
    public function updateUser($id)
    {
        $this->requireAdmin();

        $user = $this->userModel->find($id);

        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'User not found');
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'email' => "required|valid_email|is_unique[users.email,id,{$id}]",
            'role_id' => 'required|integer',
            'status' => 'required|in_list[active,inactive,suspended]',
        ];

        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[6]';
            $rules['confirm_password'] = 'matches[password]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'role_id' => $this->request->getPost('role_id'),
            'status' => $this->request->getPost('status'),
        ];

        if ($this->request->getPost('password')) {
            $data['password'] = $this->request->getPost('password');
        }

        $updated = $this->userModel->update($id, $data);

        if ($updated) {
            return redirect()->to('/admin/users')->with('success', 'User updated successfully');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to update user');
    }

    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        $this->requireAdmin();

        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->jsonResponseError('User not found', 404);
        }

        // Prevent deleting yourself
        if ($id === $this->getCurrentUserId()) {
            return $this->jsonResponseError('Cannot delete yourself', 400);
        }

        $deleted = $this->userModel->delete($id);

        if ($deleted) {
            return $this->jsonResponseSuccess([], 'User deleted successfully');
        }

        return $this->jsonResponseError('Failed to delete user', 500);
    }

    /**
     * Roles management
     */
    public function roles()
    {
        $this->requireAdmin();

        $roles = $this->roleModel->findAll();
        $availablePermissions = RoleModel::getAvailablePermissions();

        return view('admin/roles', [
            'roles' => $roles,
            'availablePermissions' => $availablePermissions,
        ]);
    }

    /**
     * Store role
     */
    public function storeRole()
    {
        $this->requireAdmin();

        $rules = [
            'name' => 'required|min_length[2]|max_length[50]|is_unique[roles.name]',
            'description' => 'permit_empty|string',
            'permissions' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'permissions' => json_encode($this->request->getPost('permissions')),
        ];

        $roleId = $this->roleModel->insert($data);

        if ($roleId) {
            return redirect()->to('/admin/roles')->with('success', 'Role created successfully');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to create role');
    }

    /**
     * Update role
     */
    public function updateRole($id)
    {
        $this->requireAdmin();

        $role = $this->roleModel->find($id);

        if (!$role) {
            return redirect()->to('/admin/roles')->with('error', 'Role not found');
        }

        $rules = [
            'name' => "required|min_length[2]|max_length[50]|is_unique[roles.name,id,{$id}]",
            'description' => 'permit_empty|string',
            'permissions' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'permissions' => json_encode($this->request->getPost('permissions')),
        ];

        $updated = $this->roleModel->update($id, $data);

        if ($updated) {
            return redirect()->to('/admin/roles')->with('success', 'Role updated successfully');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to update role');
    }

    /**
     * Delete role
     */
    public function deleteRole($id)
    {
        $this->requireAdmin();

        $role = $this->roleModel->find($id);

        if (!$role) {
            return $this->jsonResponseError('Role not found', 404);
        }

        // Check if role is in use
        $usersWithRole = $this->userModel->where('role_id', $id)->countAllResults();
        if ($usersWithRole > 0) {
            return $this->jsonResponseError('Cannot delete role that is in use', 400);
        }

        $deleted = $this->roleModel->delete($id);

        if ($deleted) {
            return $this->jsonResponseSuccess([], 'Role deleted successfully');
        }

        return $this->jsonResponseError('Failed to delete role', 500);
    }

    /**
     * Settings
     */
    public function settings()
    {
        $this->requireAdmin();

        return view('admin/settings');
    }

    /**
     * Update settings
     */
    public function updateSettings()
    {
        $this->requireAdmin();

        // Save settings to config file or database
        // For now, just return success

        return redirect()->to('/admin/settings')->with('success', 'Settings updated successfully');
    }
}