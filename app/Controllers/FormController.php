<?php

namespace App\Controllers;

use App\Libraries\FormAccess;
use App\Models\FormFieldModel;
use App\Models\FormCollaboratorModel;
use App\Models\FormModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Models\UserModel;

class FormController extends BaseController
{
    protected FormModel $formModel;
    protected FormCollaboratorModel $collaboratorModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->collaboratorModel = new FormCollaboratorModel();
    }

    public function index()
    {
        return redirect()->to('/dashboard');
    }

    public function new(): string
    {
        return view('forms/create');
    }

    public function create()
    {
        if (! $this->validate([
            'title'       => 'required|max_length[255]',
            'description' => 'permit_empty|max_length[2000]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $id = $this->formModel->insert([
            'user_id'     => $this->currentUserId(),
            'title'       => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'share_token' => $this->formModel->generateUniqueShareToken(),
            'status'      => 'draft',
        ]);

        return redirect()->to("/forms/{$id}/builder")->with('message', 'Form created. Start adding fields below.');
    }

    public function edit(int $id): string
    {
        $form = $this->findFormOrFail($id, FormAccess::FORM_EDIT);
        $access = (new FormAccess())->permissions($form, $this->currentUserId());

        return view('forms/edit', [
            'form'          => $form,
            'access'        => $access,
            'collaborators' => $access['canManageCollaborators'] ? $this->collaboratorRows($form['id']) : [],
            'availableUsers' => $access['canManageCollaborators'] ? $this->availableCollaboratorUsers((int) $form['user_id']) : [],
        ]);
    }

    public function update(int $id)
    {
        $form = $this->findFormOrFail($id, FormAccess::FORM_EDIT);

        if (! $this->validate([
            'title'                => 'required|max_length[255]',
            'description'          => 'permit_empty|max_length[2000]',
            'submit_button_text'   => 'permit_empty|max_length[100]',
            'success_message'      => 'permit_empty|max_length[2000]',
            'notify_on_submission' => 'permit_empty|in_list[0,1]',
            'notification_email'   => 'permit_empty|valid_email|max_length[255]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $notificationEmail = trim((string) $this->request->getPost('notification_email'));

        $this->formModel->update($form['id'], [
            'title'                => $this->request->getPost('title'),
            'description'          => $this->request->getPost('description'),
            'submit_button_text'   => $this->request->getPost('submit_button_text'),
            'success_message'      => $this->request->getPost('success_message'),
            'notify_on_submission' => $this->request->getPost('notify_on_submission') ? 1 : 0,
            'notification_email'   => $notificationEmail !== '' ? $notificationEmail : null,
        ]);

        return redirect()->to('/dashboard')->with('message', 'Form updated.');
    }

    public function delete(int $id)
    {
        $form = $this->findFormOrFail($id, FormAccess::FORM_DELETE);

        $this->formModel->delete($form['id']);

        return redirect()->to('/dashboard')->with('message', 'Form deleted.');
    }

    public function builder(int $id): string
    {
        $form   = $this->findFormOrFail($id, FormAccess::FORM_VIEW);
        $access = (new FormAccess())->permissions($form, $this->currentUserId());
        $fields = (new FormFieldModel())->getForForm($form['id']);

        return view('forms/builder', [
            'form'       => $form,
            'fields'     => $fields,
            'fieldTypes' => FormFieldModel::FIELD_TYPES,
            'publicUrl'  => site_url('f/' . $form['share_token']),
            'access'     => $access,
        ]);
    }

    public function publish(int $id)
    {
        $form = $this->findFormOrFail($id, FormAccess::FORM_EDIT);

        $this->formModel->publish($form['id']);

        return redirect()->to("/forms/{$id}/builder")->with('message', 'Form published. Share the public link below.');
    }

    public function unpublish(int $id)
    {
        $form = $this->findFormOrFail($id, FormAccess::FORM_EDIT);

        $this->formModel->unpublish($form['id']);

        return redirect()->to("/forms/{$id}/builder")->with('message', 'Form unpublished. The public link is no longer accepting submissions.');
    }

    public function saveCollaborator(int $id)
    {
        $form = $this->findFormOrFail($id, FormAccess::FORM_MANAGE_COLLABORATORS);

        if (! $this->validate([
            'user_id'           => 'required|is_natural_no_zero',
            'form_access'       => 'required|in_list[none,view,edit]',
            'submission_access' => 'required|in_list[none,view,export]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = (int) $this->request->getPost('user_id');
        $formAccess = (string) $this->request->getPost('form_access');
        $submissionAccess = (string) $this->request->getPost('submission_access');

        if ($userId === (int) $form['user_id']) {
            return redirect()->back()->withInput()->with('error', 'The owner already has full access.');
        }

        if ($formAccess === 'none' && $submissionAccess === 'none') {
            return redirect()->back()->withInput()->with('error', 'Choose at least one access level.');
        }

        $user = (new UserModel())->findById($userId);
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', 'User not found.');
        }

        $existing = $this->collaboratorModel->findForFormAndUser($form['id'], $userId);
        $data = [
            'form_id'           => $form['id'],
            'user_id'           => $userId,
            'form_access'       => $formAccess,
            'submission_access' => $submissionAccess,
        ];

        if ($existing === null) {
            $this->collaboratorModel->insert($data);
        } else {
            $this->collaboratorModel->update($existing['id'], $data);
        }

        return redirect()->to('/forms/' . $form['id'] . '/edit')->with('message', 'Collaborator access saved.');
    }

    public function deleteCollaborator(int $id, int $collaboratorId)
    {
        $form = $this->findFormOrFail($id, FormAccess::FORM_MANAGE_COLLABORATORS);
        $collaborator = $this->collaboratorModel->find($collaboratorId);

        if ($collaborator === null || (int) $collaborator['form_id'] !== (int) $form['id']) {
            throw new PageNotFoundException('Collaborator not found.');
        }

        $this->collaboratorModel->delete($collaborator['id']);

        return redirect()->to('/forms/' . $form['id'] . '/edit')->with('message', 'Collaborator access removed.');
    }

    private function findFormOrFail(int $id, string $capability = FormAccess::FORM_EDIT): array
    {
        $form = $this->formModel->find($id);

        if ($form === null) {
            throw new PageNotFoundException('Form not found.');
        }

        $this->ensureFormAccess($form, $capability);

        return $form;
    }

    private function availableCollaboratorUsers(int $ownerId): array
    {
        $users = (new UserModel())->orderBy('id', 'ASC')->findAll();

        return array_values(array_filter(array_map(static function ($user) use ($ownerId): ?array {
            if ((int) $user->id === $ownerId) {
                return null;
            }

            return [
                'id'    => (int) $user->id,
                'email' => $user->getEmailIdentity()?->secret ?? $user->username,
            ];
        }, $users)));
    }

    private function collaboratorRows(int $formId): array
    {
        $userModel = new UserModel();

        return array_map(static function (array $row) use ($userModel): array {
            $user = $userModel->findById((int) $row['user_id']);
            $row['email'] = $user?->getEmailIdentity()?->secret ?? $user?->username ?? ('User #' . $row['user_id']);

            return $row;
        }, $this->collaboratorModel->getForForm($formId));
    }
}
