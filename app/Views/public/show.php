<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($form['title']) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>

<?php if ($submitted) : ?>

    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
            <h1 class="h4 mt-3 mb-2"><?= esc($form['title']) ?></h1>
            <p class="mb-0"><?= esc($form['success_message'] ?: 'Thank you! Your response has been recorded.') ?></p>
        </div>
    </div>

<?php else : ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-2"><?= esc($form['title']) ?></h1>
            <?php if (! empty($form['description'])) : ?>
                <p class="text-muted"><?= nl2br(esc($form['description'])) ?></p>
            <?php endif ?>

            <?= $this->include('partials/flash') ?>

            <?php
                $hasFileField = false;
                foreach ($fields as $f) {
                    if ($f['field_type'] === 'file') {
                        $hasFileField = true;
                        break;
                    }
                }
            ?>

            <form action="<?= site_url('f/' . $form['share_token']) ?>" method="post"<?= $hasFileField ? ' enctype="multipart/form-data"' : '' ?>>
                <?= csrf_field() ?>

                <?php foreach ($fields as $field) : ?>
                    <?= view('public/fields/' . $field['field_type'], [
                        'field'      => $field,
                        'fieldError' => $errors[$field['field_key']] ?? null,
                    ]) ?>
                <?php endforeach ?>

                <?php if (empty($fields)) : ?>
                    <p class="text-muted">This form has no fields yet.</p>
                <?php else : ?>
                    <button type="submit" class="btn btn-primary w-100">
                        <?= esc($form['submit_button_text'] ?: 'Submit') ?>
                    </button>
                <?php endif ?>
            </form>
        </div>
    </div>

<?php endif ?>

<?= $this->endSection() ?>
