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
    <div class="ak-table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Form</th>
                        <th>Status</th>
                        <th class="text-center">Responses</th>
                        <th class="d-none d-md-table-cell">Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form) : ?>
                        <?php
                            $submissionCount = (int) $form['submission_count'];
                            $resetConfirm = 'Reset ' . $submissionCount . ' '
                                . ($submissionCount === 1 ? 'submission' : 'submissions')
                                . ' for "' . $form['title'] . '"? This permanently deletes all responses and uploaded submission files for this form.';
                        ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="fw-semibold text-decoration-none d-inline-block text-truncate" style="max-width: 22rem;" title="<?= esc($form['title']) ?>">
                                    <?= esc($form['title']) ?>
                                </a>
                                <div class="text-muted small d-md-none"><?= esc(date('M j, Y', strtotime($form['created_at']))) ?></div>
                            </td>
                            <td>
                                <?php if ($form['status'] === 'published') : ?>
                                    <span class="ak-pill ak-pill-success">Published</span>
                                <?php elseif ($form['status'] === 'archived') : ?>
                                    <span class="ak-pill ak-pill-muted">Archived</span>
                                <?php else : ?>
                                    <span class="ak-pill ak-pill-warning">Draft</span>
                                <?php endif ?>
                            </td>
                            <td class="text-center"><?= (int) $form['submission_count'] ?></td>
                            <td class="text-muted d-none d-md-table-cell"><?= esc(date('M j, Y', strtotime($form['created_at']))) ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                    <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="btn btn-sm btn-outline-primary" title="Builder">
                                        <i class="bi bi-pencil-square"></i><span class="d-none d-lg-inline ms-1">Builder</span>
                                    </a>
                                    <a href="<?= site_url('forms/' . $form['id'] . '/submissions') ?>" class="btn btn-sm btn-outline-secondary" title="Submissions">
                                        <i class="bi bi-inbox"></i>
                                    </a>
                                    <a href="<?= site_url('forms/' . $form['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary" title="Settings">
                                        <i class="bi bi-gear"></i>
                                    </a>
                                    <form action="<?= site_url('forms/' . $form['id'] . '/submissions/reset') ?>" method="post" class="d-inline" onsubmit="return confirm(<?= esc(json_encode($resetConfirm), 'attr') ?>);">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="return_to" value="dashboard">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Reset submissions" <?= $submissionCount > 0 ? '' : 'disabled' ?>>
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                    <form action="<?= site_url('forms/' . $form['id'] . '/delete') ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this form and all its submissions?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif ?>

<?= $this->endSection() ?>
