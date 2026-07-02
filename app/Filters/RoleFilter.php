<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    /**
     * Check if user has required role
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in')) {
            return redirect()->to('/auth/login')->with('error', 'Please login to access this page');
        }

        // If no arguments, just check if logged in
        if (empty($arguments)) {
            return;
        }

        $userRole = session()->get('role_name');
        $requiredRole = $arguments[0];

        // Check if user has required role
        if ($userRole !== $requiredRole) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access this page');
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}