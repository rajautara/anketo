<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
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
