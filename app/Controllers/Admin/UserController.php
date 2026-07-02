<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class UserController extends BaseController
{
    public function index(): string
    {
        $users = (new UserModel())->orderBy('id', 'ASC')->findAll();

        $rows = array_map(static function ($user) {
            return [
                'id'         => $user->id,
                'email'      => $user->getEmailIdentity()?->secret,
                'groups'     => $user->getGroups() ?? [],
                'active'     => (bool) $user->active,
                'created_at' => $user->created_at,
            ];
        }, $users);

        return view('admin/users/index', [
            'users'         => $rows,
            'currentUserId' => $this->currentUserId(),
        ]);
    }

    public function new(): string
    {
        return view('admin/users/new');
    }

    public function create()
    {
        if (! $this->validate([
            'email'    => 'required|valid_email|max_length[254]',
            'password' => 'required|min_length[8]|max_length[255]',
            'role'     => 'permit_empty|in_list[user,admin]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email     = trim((string) $this->request->getPost('email'));
        $password  = (string) $this->request->getPost('password');
        $makeAdmin = $this->request->getPost('role') === 'admin';

        $userModel = new UserModel();

        if ($userModel->findByCredentials(['email' => $email]) !== null) {
            return redirect()->back()->withInput()->with('error', 'A user with that email already exists.');
        }

        $user = new User([
            'username' => $this->uniqueUsernameFromEmail($email, $userModel),
            'email'    => $email,
            'password' => $password,
        ]);
        $userModel->save($user);

        $user = $userModel->findById($userModel->getInsertID());
        $user->addGroup($makeAdmin ? 'admin' : 'user');

        return redirect()->to('/admin/users')->with('message', 'User created.');
    }

    public function delete(int $userId)
    {
        $userModel = new UserModel();
        $user      = $userModel->findById($userId);

        if ($user === null) {
            throw new PageNotFoundException('User not found.');
        }

        if ((int) $user->id === $this->currentUserId()) {
            return redirect()->to('/admin/users')->with('error', 'You cannot delete your own account.');
        }

        if ($user->inGroup('admin')) {
            $groupsTable = config('Auth')->tables['groups_users'];
            $adminCount  = db_connect()->table($groupsTable)->where('group', 'admin')->countAllResults();

            if ($adminCount <= 1) {
                return redirect()->to('/admin/users')->with('error', 'Cannot delete the last admin.');
            }
        }

        // Hard delete: cascades to the user's forms/submissions via the
        // forms.user_id foreign key (ON DELETE CASCADE).
        $userModel->delete($userId, true);

        return redirect()->to('/admin/users')->with('message', 'User deleted.');
    }

    /**
     * A valid, unique Shield username derived from the email local-part
     * (Anketo logs in by email; username is required by Shield but unused in UI).
     */
    private function uniqueUsernameFromEmail(string $email, UserModel $userModel): string
    {
        $base = preg_replace('/[^a-zA-Z0-9.]/', '', explode('@', $email)[0]) ?? '';
        if (strlen($base) < 3) {
            $base = 'user' . $base;
        }
        $base = substr($base, 0, 25);

        $name = $base;
        $i    = 1;
        while ($userModel->where('username', $name)->first() !== null) {
            $name = substr($base, 0, 22) . $i;
            $i++;
        }

        return $name;
    }

    public function updateGroup(int $userId)
    {
        $userModel = new UserModel();
        $user      = $userModel->find($userId);

        if ($user === null) {
            throw new PageNotFoundException('User not found.');
        }

        $makeAdmin = $this->request->getPost('make_admin') === '1';

        if ($makeAdmin) {
            $user->addGroup('admin');

            return redirect()->to('/admin/users')->with('message', 'User promoted to admin.');
        }

        if ((int) $user->id === $this->currentUserId()) {
            return redirect()->to('/admin/users')->with('error', 'You cannot remove your own admin access.');
        }

        $groupsTable = config('Auth')->tables['groups_users'];
        $adminCount  = db_connect()->table($groupsTable)->where('group', 'admin')->countAllResults();

        if ($adminCount <= 1) {
            return redirect()->to('/admin/users')->with('error', 'Cannot remove the last admin.');
        }

        $user->removeGroup('admin');

        return redirect()->to('/admin/users')->with('message', 'Admin access removed.');
    }
}
