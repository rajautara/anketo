<?php

namespace App\Controllers;

use App\Models\FormModel;
use App\Models\SubmissionModel;
use App\Models\UserModel;

class DashboardController extends BaseController
{
    protected $formModel;
    protected $submissionModel;
    protected $userModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->submissionModel = new SubmissionModel();
        $this->userModel = new UserModel();
    }

    /**
     * Dashboard home
     */
    public function index()
    {
        $userId = $this->getCurrentUserId();
        $isAdmin = $this->isAdmin();

        // Get forms
        if ($isAdmin) {
            $forms = $this->formModel->orderBy('created_at', 'DESC')->findAll();
        } else {
            $forms = $this->formModel->getFormsByUser($userId);
        }

        // Get statistics
        $stats = [
            'total_forms' => count($forms),
            'published_forms' => count(array_filter($forms, fn($f) => $f['status'] === 'published')),
            'draft_forms' => count(array_filter($forms, fn($f) => $f['status'] === 'draft')),
        ];

        // Get recent submissions
        $recentSubmissions = [];
        foreach (array_slice($forms, 0, 5) as $form) {
            $formSubmissions = $this->submissionModel->getSubmissionsByForm($form['id'], 5);
            foreach ($formSubmissions as $submission) {
                $submission['form_title'] = $form['title'];
                $recentSubmissions[] = $submission;
            }
        }

        // Sort by submission date
        usort($recentSubmissions, fn($a, $b) => strtotime($b['submitted_at']) - strtotime($a['submitted_at']));
        $recentSubmissions = array_slice($recentSubmissions, 0, 10);

        // Get new submissions count
        $newSubmissionsCount = $this->submissionModel->getNewSubmissionsCount();

        return view('dashboard/index', [
            'forms' => $forms,
            'stats' => $stats,
            'recent_submissions' => $recentSubmissions,
            'new_submissions_count' => $newSubmissionsCount,
        ]);
    }
}