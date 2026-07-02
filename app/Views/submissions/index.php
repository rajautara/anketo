<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Submissions: <?= esc($form['title']) ?> - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="ak-page-header">
    <div>
        <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="ak-back-link"><i class="bi bi-arrow-left"></i> Back to builder</a>
        <h1 class="h4 mb-0">Submissions</h1>
        <div class="ak-page-title-sub"><?= esc($form['title']) ?></div>
    </div>
    <a href="<?= site_url('forms/' . $form['id'] . '/submissions/export') ?>" class="btn btn-outline-primary">
        <i class="bi bi-download me-1"></i> Export CSV
    </a>
</div>

<?= $this->include('partials/flash') ?>

<?php if (empty($submissions)) : ?>
    <div class="ak-empty">
        <span class="ak-empty-icon"><i class="bi bi-inbox"></i></span>
        <h2 class="h5">No submissions yet</h2>
        <p class="text-muted mb-0">Responses will appear here once people start filling out your form.</p>
    </div>
<?php else : ?>
    <div class="ak-table-card">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Submitted at</th>
                    <th>IP address</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission) : ?>
                    <tr>
                        <td class="fw-semibold"><?= esc($submission['created_at']) ?></td>
                        <td class="text-muted"><?= esc($submission['ip_address']) ?></td>
                        <td class="text-end">
                            <a href="<?= site_url('forms/' . $form['id'] . '/submissions/' . $submission['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3"><?= $pager->links() ?></div>
<?php endif ?>

<?= $this->endSection() ?>
