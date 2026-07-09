<?php

namespace App\Libraries;

use App\Models\FormCollaboratorModel;

class FormAccess
{
    public const FORM_VIEW = 'form.view';
    public const FORM_EDIT = 'form.edit';
    public const FORM_DELETE = 'form.delete';
    public const FORM_MANAGE_COLLABORATORS = 'form.collaborators';
    public const SUBMISSION_VIEW = 'submission.view';
    public const SUBMISSION_EXPORT = 'submission.export';
    public const SUBMISSION_RESET = 'submission.reset';

    public function __construct(
        private ?FormCollaboratorModel $collaborators = null,
    ) {
        $this->collaborators ??= new FormCollaboratorModel();
    }

    public function permissions(array $form, int $userId): array
    {
        $isOwner = (int) $form['user_id'] === $userId;

        if ($isOwner) {
            return [
                'isOwner'                => true,
                'canViewForm'            => true,
                'canEditForm'            => true,
                'canDeleteForm'          => true,
                'canManageCollaborators' => true,
                'canViewSubmissions'     => true,
                'canExportSubmissions'   => true,
                'canResetSubmissions'    => true,
                'formAccess'             => 'owner',
                'submissionAccess'       => 'owner',
            ];
        }

        $collaborator = $this->collaborators->findForFormAndUser((int) $form['id'], $userId);
        $formAccess = $collaborator['form_access'] ?? 'none';
        $submissionAccess = $collaborator['submission_access'] ?? 'none';

        $canViewForm = in_array($formAccess, ['view', 'edit'], true);
        $canEditForm = $formAccess === 'edit';
        $canViewSubmissions = in_array($submissionAccess, ['view', 'export'], true);
        $canExportSubmissions = $submissionAccess === 'export';

        return [
            'isOwner'                => false,
            'canViewForm'            => $canViewForm,
            'canEditForm'            => $canEditForm,
            'canDeleteForm'          => false,
            'canManageCollaborators' => false,
            'canViewSubmissions'     => $canViewSubmissions,
            'canExportSubmissions'   => $canExportSubmissions,
            'canResetSubmissions'    => false,
            'formAccess'             => $formAccess,
            'submissionAccess'       => $submissionAccess,
        ];
    }

    public function allows(array $form, int $userId, string $capability): bool
    {
        $permissions = $this->permissions($form, $userId);

        return match ($capability) {
            self::FORM_VIEW => $permissions['canViewForm'],
            self::FORM_EDIT => $permissions['canEditForm'],
            self::FORM_DELETE => $permissions['canDeleteForm'],
            self::FORM_MANAGE_COLLABORATORS => $permissions['canManageCollaborators'],
            self::SUBMISSION_VIEW => $permissions['canViewSubmissions'],
            self::SUBMISSION_EXPORT => $permissions['canExportSubmissions'],
            self::SUBMISSION_RESET => $permissions['canResetSubmissions'],
            default => false,
        };
    }
}
