<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="ak-page-header">
    <div>
        <h1><?= $isAdmin ? 'All Forms' : 'My Forms' ?></h1>
        <div class="ak-page-title-sub">
            <?= count($forms) ?> <?= count($forms) === 1 ? 'form' : 'forms' ?><?= $isAdmin ? ' across all users' : '' ?>
        </div>
    </div>
    <a href="<?= site_url('forms/new') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Form
    </a>
</div>

<?= $this->include('partials/flash') ?>

<?php if (empty($forms)) : ?>
    <div class="ak-empty">
        <span class="ak-empty-icon"><i class="bi bi-ui-checks-grid"></i></span>
        <h2 class="h5">No forms yet</h2>
        <p class="text-muted mb-4">Create your first form and start collecting responses.</p>
        <a href="<?= site_url('forms/new') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Create your first form
        </a>
    </div>
<?php else : ?>
    <div class="row g-3 g-lg-4">
        <?php foreach ($forms as $form) : ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="h5 mb-0 text-decoration-none text-truncate" title="<?= esc($form['title']) ?>">
                                <?= esc($form['title']) ?>
                            </a>
                            <?php if ($form['status'] === 'published') : ?>
                                <span class="ak-pill ak-pill-success flex-shrink-0">Published</span>
                            <?php elseif ($form['status'] === 'archived') : ?>
                                <span class="ak-pill ak-pill-muted flex-shrink-0">Archived</span>
                            <?php else : ?>
                                <span class="ak-pill ak-pill-warning flex-shrink-0">Draft</span>
                            <?php endif ?>
                        </div>

                        <div class="d-flex flex-wrap gap-3 text-muted small mb-3">
                            <span class="d-inline-flex align-items-center gap-1">
                                <i class="bi bi-inbox"></i> <?= (int) $form['submission_count'] ?> <?= (int) $form['submission_count'] === 1 ? 'response' : 'responses' ?>
                            </span>
                            <span class="d-inline-flex align-items-center gap-1">
                                <i class="bi bi-calendar3"></i> <?= esc(date('M j, Y', strtotime($form['created_at']))) ?>
                            </span>
                        </div>

                        <div class="mt-auto d-flex gap-2 pt-3 border-top">
                            <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-pencil-square me-1"></i> Builder
                            </a>
                            <a href="<?= site_url('forms/' . $form['id'] . '/submissions') ?>" class="btn btn-sm btn-outline-secondary" title="Submissions">
                                <i class="bi bi-inbox"></i>
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= site_url('forms/' . $form['id'] . '/edit') ?>"><i class="bi bi-gear me-2"></i>Settings</a></li>
                                    <li><a class="dropdown-item" href="<?= site_url('forms/' . $form['id'] . '/submissions') ?>"><i class="bi bi-inbox me-2"></i>Submissions</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="<?= site_url('forms/' . $form['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Delete this form and all its submissions?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
<?php endif ?>

<?= $this->endSection() ?>
