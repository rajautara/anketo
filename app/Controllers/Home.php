<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/dashboard');
        }

        return redirect()->to('/login');
    }
}
