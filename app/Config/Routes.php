<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Home route
$routes->get('/', 'HomeController::index');

// Authentication routes
$routes->group('auth', function($routes) {
    $routes->get('login', 'AuthController::login');
    $routes->post('login', 'AuthController::attemptLogin');
    $routes->get('logout', 'AuthController::logout');
    $routes->get('register', 'AuthController::register');
    $routes->post('register', 'AuthController::attemptRegister');
    $routes->get('forgot-password', 'AuthController::forgotPassword');
    $routes->post('forgot-password', 'AuthController::sendResetLink');
    $routes->get('reset-password/(:hash)', 'AuthController::resetPassword/$1');
    $routes->post('reset-password', 'AuthController::attemptResetPassword');
});

// Dashboard routes (require authentication)
$routes->group('dashboard', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'DashboardController::index');
});

// Form routes (require authentication)
$routes->group('forms', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'FormController::index');
    $routes->get('create', 'FormController::create');
    $routes->post('/', 'FormController::store');
    $routes->get('(:num)', 'FormController::show/$1');
    $routes->get('(:num)/edit', 'FormController::edit/$1');
    $routes->put('(:num)', 'FormController::update/$1');
    $routes->delete('(:num)', 'FormController::delete/$1');
    $routes->get('(:num)/builder', 'FormController::builder/$1');
    $routes->get('(:num)/preview', 'FormController::preview/$1');
    $routes->post('(:num)/publish', 'FormController::publish/$1');
    $routes->post('(:num)/duplicate', 'FormController::duplicate/$1');
});

// Field routes (require authentication)
$routes->group('fields', ['filter' => 'auth'], function($routes) {
    $routes->post('/', 'FieldController::store');
    $routes->put('(:num)', 'FieldController::update/$1');
    $routes->delete('(:num)', 'FieldController::delete/$1');
    $routes->post('reorder', 'FieldController::reorder');
});

// Submission routes (require authentication)
$routes->group('submissions', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'SubmissionController::index');
    $routes->get('(:num)', 'SubmissionController::show/$1');
    $routes->delete('(:num)', 'SubmissionController::delete/$1');
    $routes->get('form/(:num)', 'SubmissionController::byForm/$1');
    $routes->get('export/(:num)', 'SubmissionController::export/$1');
});

// Admin routes (require admin role)
$routes->group('admin', ['filter' => 'auth:admin'], function($routes) {
    $routes->get('/', 'AdminController::index');
    $routes->get('users', 'AdminController::users');
    $routes->get('users/create', 'AdminController::createUser');
    $routes->post('users', 'AdminController::storeUser');
    $routes->get('users/(:num)/edit', 'AdminController::editUser/$1');
    $routes->put('users/(:num)', 'AdminController::updateUser/$1');
    $routes->delete('users/(:num)', 'AdminController::deleteUser/$1');
    $routes->get('roles', 'AdminController::roles');
    $routes->post('roles', 'AdminController::storeRole');
    $routes->put('roles/(:num)', 'AdminController::updateRole/$1');
    $routes->delete('roles/(:num)', 'AdminController::deleteRole/$1');
    $routes->get('settings', 'AdminController::settings');
    $routes->post('settings', 'AdminController::updateSettings');
});

// Public form routes (no authentication required)
$routes->group('f', function($routes) {
    $routes->get('(:any)', 'PublicFormController::view/$1');
    $routes->post('(:any)/submit', 'PublicFormController::submit/$1');
});

// API routes for AJAX operations (require authentication)
$routes->group('api', ['filter' => 'auth'], function($routes) {
    // Form API
    $routes->post('forms', 'ApiController::createForm');
    $routes->get('forms', 'ApiController::listForms');
    $routes->get('forms/(:num)', 'ApiController::getForm/$1');
    $routes->put('forms/(:num)', 'ApiController::updateForm/$1');
    $routes->delete('forms/(:num)', 'ApiController::deleteForm/$1');
    
    // Field API
    $routes->post('forms/(:num)/fields', 'ApiController::addField/$1');
    $routes->put('fields/(:num)', 'ApiController::updateField/$1');
    $routes->delete('fields/(:num)', 'ApiController::deleteField/$1');
    
    // Submission API
    $routes->get('forms/(:num)/submissions', 'ApiController::getSubmissions/$1');
    $routes->get('submissions/(:num)', 'ApiController::getSubmission/$1');
    $routes->post('forms/(:any)/submit', 'ApiController::submitForm/$1');
    
    // Upload API
    $routes->post('upload', 'ApiController::uploadFile');
});