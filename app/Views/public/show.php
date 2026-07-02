<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($form['title']) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>

<?php if ($submitted) : ?>

    <div class="ak-form-card">
        <div class="ak-form-accent"></div>
        <div class="ak-success">
            <span class="ak-success-badge"><i class="bi bi-check-lg"></i></span>
            <h1 class="h4 mb-2"><?= esc($form['title']) ?></h1>
            <p class="text-muted mb-0"><?= esc($form['success_message'] ?: 'Thank you! Your response has been recorded.') ?></p>
        </div>
    </div>

<?php else : ?>

    <div class="ak-form-card">
        <div class="ak-form-accent"></div>
        <div class="ak-form-head">
            <h1 class="ak-form-title"><?= esc($form['title']) ?></h1>
            <?php if (! empty($form['description'])) : ?>
                <p class="ak-form-desc mb-0"><?= nl2br(esc($form['description'])) ?></p>
            <?php endif ?>
        </div>
        <div class="ak-form-body">
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
                    <p class="text-muted mb-0">This form has no fields yet.</p>
                <?php else : ?>
                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">
                        <?= esc($form['submit_button_text'] ?: 'Submit') ?>
                    </button>
                <?php endif ?>
            </form>
        </div>
    </div>

<?php endif ?>

<?= $this->endSection() ?>
