<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<?php
$renderForms = static function (array $rows, bool $shared): void {
?>
    <div class="ak-table-card mb-4">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Form</th>
                        <th>Status</th>
                        <?php if ($shared) : ?><th>Access</th><?php endif ?>
                        <th class="text-center">Responses</th>
                        <th class="d-none d-md-table-cell">Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $form) : ?>
                        <?php
                            $submissionCount = (int) $form['submission_count'];
                            $formAccess = $shared ? (string) ($form['form_access'] ?? 'none') : 'edit';
                            $submissionAccess = $shared ? (string) ($form['submission_access'] ?? 'none') : 'export';
                            $canViewForm = ! $shared || in_array($formAccess, ['view', 'edit'], true);
                            $canEditForm = ! $shared || $formAccess === 'edit';
                            $canViewSubmissions = ! $shared || in_array($submissionAccess, ['view', 'export'], true);
                            $primaryUrl = $canViewForm
                                ? site_url('forms/' . $form['id'] . '/builder')
                                : site_url('forms/' . $form['id'] . '/submissions');
                            $resetConfirm = 'Reset ' . $submissionCount . ' '
                                . ($submissionCount === 1 ? 'submission' : 'submissions')
                                . ' for "' . $form['title'] . '"? This permanently deletes all responses and uploaded submission files for this form.';
                        ?>
                        <tr>
                            <td>
                                <a href="<?= $primaryUrl ?>" class="fw-semibold text-decoration-none d-inline-block text-truncate" style="max-width: 22rem;" title="<?= esc($form['title']) ?>">
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
                            <?php if ($shared) : ?>
                                <td>
                                    <span class="badge text-bg-light border">Form: <?= esc($formAccess) ?></span>
                                    <span class="badge text-bg-light border">Results: <?= esc($submissionAccess) ?></span>
                                </td>
                            <?php endif ?>
                            <td class="text-center"><?= (int) $form['submission_count'] ?></td>
                            <td class="text-muted d-none d-md-table-cell"><?= esc(date('M j, Y', strtotime($form['created_at']))) ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2 align-items-center justify-content-end">
                                    <?php if ($canViewForm) : ?>
                                        <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="btn btn-sm btn-outline-primary" title="<?= $canEditForm ? 'Builder' : 'View form' ?>">
                                            <i class="bi <?= $canEditForm ? 'bi-pencil-square' : 'bi-eye' ?>"></i><span class="d-none d-lg-inline ms-1"><?= $canEditForm ? 'Builder' : 'View' ?></span>
                                        </a>
                                    <?php endif ?>
                                    <?php if ($canViewSubmissions) : ?>
                                        <a href="<?= site_url('forms/' . $form['id'] . '/submissions') ?>" class="btn btn-sm btn-outline-secondary" title="Submissions">
                                            <i class="bi bi-inbox"></i>
                                        </a>
                                    <?php endif ?>
                                    <?php if (! $shared) : ?>
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
                                    <?php elseif ($canEditForm) : ?>
                                        <a href="<?= site_url('forms/' . $form['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary" title="Settings">
                                            <i class="bi bi-gear"></i>
                                        </a>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
};
?>

<div class="ak-page-header">
    <div>
        <h1>My Forms</h1>
        <div class="ak-page-title-sub">
            <?= count($forms) ?> <?= count($forms) === 1 ? 'form' : 'forms' ?>
            <?php if (! empty($sharedForms)) : ?>
                &middot; <?= count($sharedForms) ?> shared
            <?php endif ?>
        </div>
    </div>
    <a href="<?= site_url('forms/new') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Form
    </a>
</div>

<?= $this->include('partials/flash') ?>

<?php if (empty($forms)) : ?>
    <div class="ak-empty mb-4">
        <span class="ak-empty-icon"><i class="bi bi-ui-checks-grid"></i></span>
        <h2 class="h5">No forms yet</h2>
        <p class="text-muted mb-4">Create your first form and start collecting responses.</p>
        <a href="<?= site_url('forms/new') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Create your first form
        </a>
    </div>
<?php else : ?>
    <?php $renderForms($forms, false); ?>
<?php endif ?>

<?php if (! empty($sharedForms)) : ?>
    <div class="ak-page-header mt-4">
        <div>
            <h2 class="h4 mb-0">Shared with me</h2>
            <div class="ak-page-title-sub">
                <?= count($sharedForms) ?> <?= count($sharedForms) === 1 ? 'form' : 'forms' ?>
            </div>
        </div>
    </div>
    <?php $renderForms($sharedForms, true); ?>
<?php endif ?>

<?= $this->endSection() ?>
