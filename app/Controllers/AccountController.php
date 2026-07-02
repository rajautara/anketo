<?php

namespace App\Controllers;

use CodeIgniter\Shield\Models\UserModel;

/**
 * A signed-in user's own account: change email (profile) and password.
 */
class AccountController extends BaseController
{
    public function edit(): string
    {
        return view('account/edit', [
            'user' => auth()->user(),
        ]);
    }

    public function updateProfile()
    {
        if (! $this->validate([
            'email' => 'required|valid_email|max_length[254]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user  = auth()->user();
        $email = trim((string) $this->request->getPost('email'));

        $userModel = new UserModel();
        $existing  = $userModel->findByCredentials(['email' => $email]);
        if ($existing !== null && (int) $existing->id !== (int) $user->id) {
            return redirect()->back()->withInput()->with('error', 'That email address is already in use.');
        }

        $user->email = $email;
        $userModel->save($user);

        return redirect()->to('/account')->with('message', 'Your email has been updated.');
    }

    public function updatePassword()
    {
        if (! $this->validate([
            'current_password'     => 'required',
            'new_password'         => 'required|min_length[8]|max_length[255]',
            'new_password_confirm' => 'required|matches[new_password]',
        ])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $user = auth()->user();

        // Verify the current password before allowing a change.
        $check = auth()->check([
            'email'    => $user->email,
            'password' => (string) $this->request->getPost('current_password'),
        ]);

        if (! $check->isOK()) {
            return redirect()->back()->with('error', 'Your current password is incorrect.');
        }

        $user->password = (string) $this->request->getPost('new_password');
        (new UserModel())->save($user);

        return redirect()->to('/account')->with('message', 'Your password has been changed.');
    }
}
