<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RoleModel;

class AuthController extends BaseController
{
    protected $userModel;
    protected $roleModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }

    /**
     * Show login page
     */
    public function login()
    {
        // Redirect if already logged in
        if (session()->get('user_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    /**
     * Attempt login
     */
    public function attemptLogin()
    {
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $remember = $this->request->getPost('remember');

        // Validate input
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Find user
        $user = $this->userModel->getUserByEmail($email);

        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials');
        }

        // Check password
        if (!password_verify($password, $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials');
        }

        // Check account status
        if ($user['status'] !== 'active') {
            return redirect()->back()->withInput()->with('error', 'Account is ' . $user['status']);
        }

        // Set session
        $sessionData = [
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'role_id' => $user['role_id'],
            'role_name' => $user['role_name'],
            'permissions' => json_decode($user['permissions'], true),
            'logged_in' => true,
        ];

        session()->set($sessionData);

        // Set remember me cookie
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $this->userModel->update($user['id'], ['remember_token' => $token]);
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
        }

        // Redirect to intended URL or dashboard
        $redirectUrl = session()->get('redirect_url') ?? '/dashboard';
        session()->remove('redirect_url');

        return redirect()->to($redirectUrl)->with('success', 'Welcome back, ' . $user['name']);
    }

    /**
     * Logout
     */
    public function logout()
    {
        // Clear remember token
        $userId = session()->get('user_id');
        if ($userId) {
            $this->userModel->update($userId, ['remember_token' => null]);
        }

        // Clear remember cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }

        // Destroy session
        session()->destroy();

        return redirect()->to('/auth/login')->with('success', 'You have been logged out');
    }

    /**
     * Show registration page
     */
    public function register()
    {
        // Redirect if already logged in
        if (session()->get('user_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/register');
    }

    /**
     * Attempt registration
     */
    public function attemptRegister()
    {
        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'role_id' => 2, // Default to 'user' role
            'status' => 'active',
        ];

        $userId = $this->userModel->insert($data);

        if ($userId) {
            return redirect()->to('/auth/login')->with('success', 'Registration successful. Please login.');
        }

        return redirect()->back()->withInput()->with('error', 'Registration failed. Please try again.');
    }

    /**
     * Show forgot password page
     */
    public function forgotPassword()
    {
        return view('auth/forgot_password');
    }

    /**
     * Send password reset link
     */
    public function sendResetLink()
    {
        $email = $this->request->getPost('email');

        $user = $this->userModel->getUserByEmail($email);

        if (!$user) {
            return redirect()->back()->with('error', 'Email not found');
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $this->userModel->update($user['id'], [
            'reset_token' => $token,
            'reset_token_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        ]);

        // In a real application, send email with reset link
        // For now, just show success message
        return redirect()->back()->with('success', 'Password reset link sent to your email');
    }

    /**
     * Show reset password page
     */
    public function resetPassword($token)
    {
        $user = $this->userModel->where('reset_token', $token)
                               ->where('reset_token_expires >', date('Y-m-d H:i:s'))
                               ->first();

        if (!$user) {
            return redirect()->to('/auth/login')->with('error', 'Invalid or expired reset token');
        }

        return view('auth/reset_password', ['token' => $token]);
    }

    /**
     * Attempt password reset
     */
    public function attemptResetPassword()
    {
        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');
        $confirmPassword = $this->request->getPost('confirm_password');

        $rules = [
            'password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = $this->userModel->where('reset_token', $token)
                               ->where('reset_token_expires >', date('Y-m-d H:i:s'))
                               ->first();

        if (!$user) {
            return redirect()->to('/auth/login')->with('error', 'Invalid or expired reset token');
        }

        // Update password
        $this->userModel->update($user['id'], [
            'password' => $password,
            'reset_token' => null,
            'reset_token_expires' => null,
        ]);

        return redirect()->to('/auth/login')->with('success', 'Password reset successful. Please login.');
    }
}