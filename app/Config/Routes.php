<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

service('auth')->routes($routes);

// Public — anonymous, no login required
$routes->get('f/(:segment)', 'PublicFormController::show/$1');
$routes->post('f/(:segment)', 'PublicFormController::submit/$1');

// Authenticated
$routes->group('', ['filter' => 'session'], static function ($routes) {
    $routes->get('dashboard', 'DashboardController::index');

    // Own account: profile (email) + password
    $routes->get('account', 'AccountController::edit');
    $routes->post('account/profile', 'AccountController::updateProfile');
    $routes->post('account/password', 'AccountController::updatePassword');

    $routes->resource('forms', [
        'controller'  => 'FormController',
        'except'      => 'show',
        'websafe'     => 1,
        'placeholder' => '(:num)',
    ]);
    $routes->get('forms/(:num)/builder', 'FormController::builder/$1');
    $routes->post('forms/(:num)/publish', 'FormController::publish/$1');
    $routes->post('forms/(:num)/unpublish', 'FormController::unpublish/$1');

    $routes->post('forms/(:num)/fields', 'FormFieldController::store/$1');
    $routes->post('forms/(:num)/fields/reorder', 'FormFieldController::reorder/$1');
    $routes->put('forms/(:num)/fields/(:num)', 'FormFieldController::update/$1/$2');
    $routes->delete('forms/(:num)/fields/(:num)', 'FormFieldController::delete/$1/$2');

    $routes->get('forms/(:num)/submissions', 'SubmissionController::index/$1');
    $routes->get('forms/(:num)/submissions/export', 'SubmissionController::export/$1');
    $routes->get('forms/(:num)/submissions/(:num)', 'SubmissionController::show/$1/$2');
    $routes->get('forms/(:num)/submissions/(:num)/files/(:num)', 'SubmissionController::downloadFile/$1/$2/$3');
});

// Admin only
$routes->group('admin', ['filter' => ['session', 'group:admin']], static function ($routes) {
    $routes->get('users', 'Admin\UserController::index');
    $routes->get('users/new', 'Admin\UserController::new');
    $routes->post('users', 'Admin\UserController::create');
    $routes->post('users/(:num)/group', 'Admin\UserController::updateGroup/$1');
    $routes->post('users/(:num)/delete', 'Admin\UserController::delete/$1');
});
