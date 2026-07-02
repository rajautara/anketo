<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Submissions: <?= esc($form['title']) ?> - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="text-decoration-none small d-block mb-1"><i class="bi bi-arrow-left"></i> Back to builder</a>
        <h1 class="h4 mb-0">Submissions: <?= esc($form['title']) ?></h1>
    </div>
    <a href="<?= site_url('forms/' . $form['id'] . '/submissions/export') ?>" class="btn btn-outline-primary">
        <i class="bi bi-download"></i> Export CSV
    </a>
</div>

<?= $this->include('partials/flash') ?>

<?php if (empty($submissions)) : ?>
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            No submissions yet.
        </div>
    </div>
<?php else : ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle bg-white">
            <thead>
                <tr>
                    <th>Submitted At</th>
                    <th>IP Address</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission) : ?>
                    <tr>
                        <td><?= esc($submission['created_at']) ?></td>
                        <td><?= esc($submission['ip_address']) ?></td>
                        <td class="text-end">
                            <a href="<?= site_url('forms/' . $form['id'] . '/submissions/' . $submission['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <?= $pager->links() ?>
<?php endif ?>

<?= $this->endSection() ?>
