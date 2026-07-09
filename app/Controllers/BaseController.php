<?php

namespace App\Controllers;

use App\Libraries\FormAccess;
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
     * belonging to someone else exists) unless the current user has the
     * requested owner/collaborator capability.
     */
    protected function ensureFormAccess(array $form, string $capability = FormAccess::FORM_EDIT): array
    {
        $access = (new FormAccess())->permissions($form, $this->currentUserId());

        $allowed = match ($capability) {
            FormAccess::FORM_VIEW => $access['canViewForm'],
            FormAccess::FORM_EDIT => $access['canEditForm'],
            FormAccess::FORM_DELETE => $access['canDeleteForm'],
            FormAccess::FORM_MANAGE_COLLABORATORS => $access['canManageCollaborators'],
            FormAccess::SUBMISSION_VIEW => $access['canViewSubmissions'],
            FormAccess::SUBMISSION_EXPORT => $access['canExportSubmissions'],
            FormAccess::SUBMISSION_RESET => $access['canResetSubmissions'],
            default => false,
        };

        if (! $allowed) {
            throw new PageNotFoundException('Form not found.');
        }

        return $access;
    }
}
