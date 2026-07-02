<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class BaseController extends Controller
{
    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = ['url', 'form', 'html', 'session'];

    /**
     * Constructor
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission($permission)
    {
        $permissions = session()->get('permissions') ?? [];
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * Require permission
     */
    protected function requirePermission($permission)
    {
        if (!$this->hasPermission($permission)) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access this page');
        }
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin()
    {
        return session()->get('role_name') === 'admin';
    }

    /**
     * Require admin
     */
    protected function requireAdmin()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Admin access required');
        }
    }

    /**
     * Get current user ID
     */
    protected function getCurrentUserId()
    {
        return session()->get('user_id');
    }

    /**
     * Check if user owns form
     */
    protected function isFormOwner($formId)
    {
        $formModel = new \App\Models\FormModel();
        $form = $formModel->find($formId);
        
        if (!$form) {
            return false;
        }

        return $form['user_id'] === $this->getCurrentUserId() || $this->isAdmin();
    }

    /**
     * Require form ownership
     */
    protected function requireFormOwnership($formId)
    {
        if (!$this->isFormOwner($formId)) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access this form');
        }
    }

    /**
     * JSON response
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        return $this->response->setStatusCode($statusCode)
                             ->setJSON($data);
    }

    /**
     * Error JSON response
     */
    protected function jsonResponseError($message, $statusCode = 400, $errors = [])
    {
        return $this->response->setStatusCode($statusCode)
                             ->setJSON([
                                 'success' => false,
                                 'message' => $message,
                                 'errors' => $errors,
                             ]);
    }

    /**
     * Success JSON response
     */
    protected function jsonResponseSuccess($data = [], $message = 'Success')
    {
        return $this->response->setStatusCode(200)
                             ->setJSON([
                                 'success' => true,
                                 'message' => $message,
                                 'data' => $data,
                             ]);
    }
}