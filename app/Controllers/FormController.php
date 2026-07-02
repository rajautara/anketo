<?php

namespace App\Controllers;

use App\Models\FormFieldModel;
use App\Models\FormModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class FormController extends BaseController
{
    protected FormModel $formModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
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
        $form = $this->findFormOrFail($id);

        return view('forms/edit', ['form' => $form]);
    }

    public function update(int $id)
    {
        $form = $this->findFormOrFail($id);

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
        $form = $this->findFormOrFail($id);

        $this->formModel->delete($form['id']);

        return redirect()->to('/dashboard')->with('message', 'Form deleted.');
    }

    public function builder(int $id): string
    {
        $form   = $this->findFormOrFail($id);
        $fields = (new FormFieldModel())->getForForm($form['id']);

        return view('forms/builder', [
            'form'       => $form,
            'fields'     => $fields,
            'fieldTypes' => FormFieldModel::FIELD_TYPES,
            'publicUrl'  => site_url('f/' . $form['share_token']),
        ]);
    }

    public function publish(int $id)
    {
        $form = $this->findFormOrFail($id);

        $this->formModel->publish($form['id']);

        return redirect()->to("/forms/{$id}/builder")->with('message', 'Form published. Share the public link below.');
    }

    public function unpublish(int $id)
    {
        $form = $this->findFormOrFail($id);

        $this->formModel->unpublish($form['id']);

        return redirect()->to("/forms/{$id}/builder")->with('message', 'Form unpublished. The public link is no longer accepting submissions.');
    }

    private function findFormOrFail(int $id): array
    {
        $form = $this->formModel->find($id);

        if ($form === null) {
            throw new PageNotFoundException('Form not found.');
        }

        $this->ensureFormAccess($form);

        return $form;
    }
}
