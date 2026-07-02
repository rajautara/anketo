<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Submission - Anketo<?= $this->endSection() ?>

<?= $this->section('main') ?>

<a href="<?= site_url('forms/' . $form['id'] . '/submissions') ?>" class="text-decoration-none small d-block mb-1"><i class="bi bi-arrow-left"></i> Back to submissions</a>
<h1 class="h4 mb-1">Submission for <?= esc($form['title']) ?></h1>
<p class="text-muted mb-4">Submitted <?= esc($submission['created_at']) ?> from <?= esc($submission['ip_address']) ?></p>

<div class="card">
    <div class="card-body">
        <?php if (empty($answers)) : ?>
            <p class="text-muted mb-0">No answers recorded.</p>
        <?php else : ?>
            <dl class="row mb-0">
                <?php foreach ($answers as $answer) : ?>
                    <dt class="col-sm-4"><?= esc($answer['field_label']) ?></dt>
                    <dd class="col-sm-8">
                        <?php if (! empty($answer['file_path'])) : ?>
                            <a href="<?= site_url('forms/' . $form['id'] . '/submissions/' . $submission['id'] . '/files/' . $answer['id']) ?>">
                                <i class="bi bi-paperclip"></i> <?= esc($answer['value'] ?: 'Download file') ?>
                            </a>
                        <?php elseif ($answer['value'] !== null && str_starts_with(trim($answer['value']), '[')) : ?>
                            <?php $decoded = json_decode($answer['value'], true); ?>
                            <?= is_array($decoded) ? esc(implode(', ', $decoded)) : esc($answer['value']) ?>
                        <?php else : ?>
                            <?= $answer['value'] !== null && $answer['value'] !== '' ? nl2br(esc($answer['value'])) : '<span class="text-muted">&mdash;</span>' ?>
                        <?php endif ?>
                    </dd>
                <?php endforeach ?>
            </dl>
        <?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>
