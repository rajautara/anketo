<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Submissions: <?= esc($form['title']) ?> - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="ak-page-header">
    <div>
        <?php if ($access['canViewForm']) : ?>
            <a href="<?= site_url('forms/' . $form['id'] . '/builder') ?>" class="ak-back-link"><i class="bi bi-arrow-left"></i> Back to builder</a>
        <?php else : ?>
            <a href="<?= site_url('dashboard') ?>" class="ak-back-link"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <?php endif ?>
        <h1 class="h4 mb-0">Submissions</h1>
        <div class="ak-page-title-sub">
            <?= esc($form['title']) ?> &middot; <?= (int) $submissionCount ?> <?= (int) $submissionCount === 1 ? 'response' : 'responses' ?>
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php if ($access['canResetSubmissions'] && (int) $submissionCount > 0) : ?>
            <?php
                $resetConfirm = 'Reset ' . (int) $submissionCount . ' '
                    . ((int) $submissionCount === 1 ? 'submission' : 'submissions')
                    . ' for "' . $form['title'] . '"? This permanently deletes all responses and uploaded submission files for this form.';
            ?>
            <form action="<?= site_url('forms/' . $form['id'] . '/submissions/reset') ?>" method="post" class="d-inline" onsubmit="return confirm(<?= esc(json_encode($resetConfirm), 'attr') ?>);">
                <?= csrf_field() ?>
                <input type="hidden" name="return_to" value="submissions">
                <button type="submit" class="btn btn-outline-warning">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset submissions
                </button>
            </form>
        <?php endif ?>
        <?php if ($access['canExportSubmissions']) : ?>
            <a href="<?= site_url('forms/' . $form['id'] . '/submissions/export') ?>" class="btn btn-outline-primary">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
        <?php endif ?>
    </div>
</div>

<?= $this->include('partials/flash') ?>

<?php if (empty($submissions)) : ?>
    <div class="ak-empty">
        <span class="ak-empty-icon"><i class="bi bi-inbox"></i></span>
        <h2 class="h5">No submissions yet</h2>
        <p class="text-muted mb-0">Responses will appear here once people start filling out your form.</p>
    </div>
<?php else : ?>
    <?php
        $truncate = static function (string $s, int $len = 48): string {
            $s = trim(preg_replace('/\s+/', ' ', $s));
            return mb_strlen($s) > $len ? mb_substr($s, 0, $len - 1) . '…' : $s;
        };
    ?>
    <div class="ak-table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="text-nowrap">Submitted at</th>
                        <?php foreach ($columns as $field) : ?>
                            <th class="text-nowrap"><?= esc($field['label']) ?></th>
                        <?php endforeach ?>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission) : ?>
                        <tr>
                            <td class="fw-semibold text-nowrap"><?= esc($submission['created_at']) ?></td>

                            <?php foreach ($columns as $field) : ?>
                                <?php $answer = $answersById[$submission['id']][$field['field_key']] ?? null; ?>
                                <td style="max-width: 18rem;">
                                    <?php if ($answer === null || ($answer['value'] === null && empty($answer['file_path']))) : ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php elseif (! empty($answer['file_path'])) : ?>
                                        <a href="<?= site_url('forms/' . $form['id'] . '/submissions/' . $submission['id'] . '/files/' . $answer['id']) ?>" class="d-inline-flex align-items-center gap-1 text-truncate" title="<?= esc($answer['value'] ?: 'Download file') ?>">
                                            <i class="bi bi-paperclip"></i> <?= esc($truncate($answer['value'] ?: 'Download', 28)) ?>
                                        </a>
                                    <?php else : ?>
                                        <?php
                                            $raw = $answerFormatter->format($answer, $fieldsByKey[$field['field_key']] ?? null);
                                        ?>
                                        <span class="d-inline-block text-truncate" style="max-width: 18rem;" title="<?= esc($raw) ?>"><?= esc($truncate($raw)) ?></span>
                                    <?php endif ?>
                                </td>
                            <?php endforeach ?>

                            <td class="text-end">
                                <a href="<?= site_url('forms/' . $form['id'] . '/submissions/' . $submission['id']) ?>" class="btn btn-sm btn-outline-secondary text-nowrap">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3"><?= $pager->links() ?></div>
<?php endif ?>

<?= $this->endSection() ?>
