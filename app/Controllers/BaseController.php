<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    protected $helpers = ['form', 'url', 'auth', 'form_field'];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    protected function currentUserId(): int
    {
        return (int) auth()->id();
    }

    protected function isAdmin(): bool
    {
        return auth()->user()->inGroup('admin');
    }

    /**
     * Aborts with a 404 (rather than 403, to avoid revealing that a form
     * belonging to someone else exists) unless the given form is owned by
     * the current user or the current user is an admin.
     */
    protected function ensureFormAccess(array $form): void
    {
        if (! $this->isAdmin() && (int) $form['user_id'] !== $this->currentUserId()) {
            throw new PageNotFoundException('Form not found.');
        }
    }
}
