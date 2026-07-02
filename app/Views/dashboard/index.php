<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= $isAdmin ? 'All Forms' : 'My Forms' ?></h1>
    <a href="<?= site_url('forms/new') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Form
    </a>
</div>

<?= $this->include('partials/flash') ?>

<?php if (empty($forms)) : ?>
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-ui-checks-grid fs-1 d-block mb-3"></i>
            <p class="mb-3">No forms yet.</p>
            <a href="<?= site_url('forms/new') ?>" class="btn btn-primary">Create your first form</a>
        </div>
    </div>
<?php else : ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle bg-white">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Submissions</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form) : ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="fw-semibold text-decoration-none">
                                <?= esc($form['title']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($form['status'] === 'published') : ?>
                                <span class="badge text-bg-success">Published</span>
                            <?php elseif ($form['status'] === 'archived') : ?>
                                <span class="badge text-bg-secondary">Archived</span>
                            <?php else : ?>
                                <span class="badge text-bg-warning">Draft</span>
                            <?php endif ?>
                        </td>
                        <td><?= (int) $form['submission_count'] ?></td>
                        <td><?= esc($form['created_at']) ?></td>
                        <td class="text-end">
                            <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="btn btn-sm btn-outline-primary">Builder</a>
                            <a href="<?= site_url('forms/' . $form['id'] . '/submissions') ?>" class="btn btn-sm btn-outline-secondary">Submissions</a>
                            <a href="<?= site_url('forms/' . $form['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form action="<?= site_url('forms/' . $form['id'] . '/delete') ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this form and all its submissions?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

<?= $this->endSection() ?>
