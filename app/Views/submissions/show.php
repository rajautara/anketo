<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Submission - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="row justify-content-center">
    <div class="col-lg-9 col-xl-8">
        <a href="<?= site_url('forms/' . $form['id'] . '/submissions') ?>" class="ak-back-link"><i class="bi bi-arrow-left"></i> Back to submissions</a>
        <h1 class="h4 mb-1">Submission for <?= esc($form['title']) ?></h1>
        <p class="text-muted mb-4">
            <i class="bi bi-clock me-1"></i><?= esc($submission['created_at']) ?>
            <span class="mx-2">·</span>
            <i class="bi bi-globe2 me-1"></i><?= esc($submission['ip_address']) ?>
        </p>

        <div class="card">
            <div class="card-body p-4">
                <?php if (empty($answers)) : ?>
                    <p class="text-muted mb-0">No answers recorded.</p>
                <?php else : ?>
                    <?php foreach ($answers as $i => $answer) : ?>
                        <div class="row py-3<?= $i > 0 ? ' border-top' : '' ?>">
                            <dt class="col-sm-4 text-muted fw-semibold"><?= esc($answer['field_label']) ?></dt>
                            <dd class="col-sm-8 mb-0">
                                <?php if (! empty($answer['file_path'])) : ?>
                                    <a href="<?= site_url('forms/' . $form['id'] . '/submissions/' . $submission['id'] . '/files/' . $answer['id']) ?>" class="d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-paperclip"></i> <?= esc($answer['value'] ?: 'Download file') ?>
                                    </a>
                                <?php else : ?>
                                    <?php $displayValue = $answerFormatter->format($answer, $fieldsByKey[$answer['field_key']] ?? null); ?>
                                    <?= $displayValue !== '' ? nl2br(esc($displayValue)) : '<span class="text-muted">&mdash;</span>' ?>
                                <?php endif ?>
                            </dd>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
