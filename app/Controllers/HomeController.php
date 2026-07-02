<?php

namespace App\Controllers;

class HomeController extends BaseController
{
    /**
     * Home page
     */
    public function index()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/dashboard');
        }

        return view('home');
    }
}