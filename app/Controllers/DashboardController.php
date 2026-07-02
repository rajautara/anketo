<?php

namespace App\Controllers;

use App\Models\FormModel;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $formModel = new FormModel();

        $forms = $formModel->getForUser($this->currentUserId(), $this->isAdmin());

        return view('dashboard/index', [
            'forms'   => $forms,
            'isAdmin' => $this->isAdmin(),
        ]);
    }
}
