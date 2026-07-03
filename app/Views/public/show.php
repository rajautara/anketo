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
                $pages = [['title' => 'Page 1', 'fields' => []]];
                foreach ($fields as $f) {
                    if ($f['field_type'] === 'file') {
                        $hasFileField = true;
                    }

                    if ($f['field_type'] === 'page_break') {
                        $title = trim((string) ($f['label'] ?? ''));
                        $title = $title !== '' ? $title : 'Page ' . (count($pages) + 1);
                        $currentIndex = count($pages) - 1;

                        if (empty($pages[$currentIndex]['fields'])) {
                            $pages[$currentIndex]['title'] = $title;
                        } else {
                            $pages[] = ['title' => $title, 'fields' => []];
                        }

                        continue;
                    }

                    $pages[count($pages) - 1]['fields'][] = $f;
                }

                $pages = array_values(array_filter($pages, static fn ($page) => ! empty($page['fields'])));
                if ($pages === []) {
                    $pages = [['title' => 'Page 1', 'fields' => []]];
                }

                $hasRenderableFields = ! empty($pages[0]['fields']);
                $isPagedForm = count($pages) > 1;
            ?>

            <form action="<?= site_url('f/' . $form['share_token']) ?>" method="post"<?= $hasFileField ? ' enctype="multipart/form-data"' : '' ?><?= $isPagedForm ? ' data-ak-paged-form' : '' ?>>
                <?= csrf_field() ?>

                <?php foreach ($pages as $pageIndex => $page) : ?>
                    <section class="ak-form-page" data-ak-form-page="<?= $pageIndex ?>">
                        <?php if ($isPagedForm) : ?>
                            <div class="ak-page-step">
                                <span>Step <?= $pageIndex + 1 ?> of <?= count($pages) ?></span>
                                <h2><?= esc($page['title']) ?></h2>
                            </div>
                        <?php endif ?>

                        <?php foreach ($page['fields'] as $field) : ?>
                            <div class="ak-field" data-field-key="<?= esc($field['field_key'], 'attr') ?>" data-field-type="<?= esc($field['field_type'], 'attr') ?>">
                                <?= view('public/fields/' . $field['field_type'], [
                                    'field'       => $field,
                                    'fieldError'  => $errors[$field['field_key']] ?? null,
                                    'bookedSlots' => $bookedSlots ?? [],
                                ]) ?>
                            </div>
                        <?php endforeach ?>
                    </section>
                <?php endforeach ?>

                <?php if (! $hasRenderableFields) : ?>
                    <p class="text-muted mb-0">This form has no fields yet.</p>
                <?php else : ?>
                    <div class="<?= $isPagedForm ? 'ak-page-actions' : '' ?>">
                        <?php if ($isPagedForm) : ?>
                            <button type="button" class="btn btn-outline-secondary btn-lg d-none" data-ak-page-back>Back</button>
                            <button type="button" class="btn btn-primary btn-lg d-none" data-ak-page-next>Next</button>
                        <?php endif ?>
                        <button type="submit" class="btn btn-primary btn-lg<?= $isPagedForm ? '' : ' w-100 mt-2' ?>" data-ak-page-submit>
                            <?= esc($form['submit_button_text'] ?: 'Submit') ?>
                        </button>
                    </div>
                <?php endif ?>
            </form>
        </div>
    </div>

    <script id="ak-form-config" type="application/json"><?= json_encode($formConfig ?? [], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<?php endif ?>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('assets/js/conditions.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/conditions.js') ?>"></script>
<script src="<?= base_url('assets/js/page-breaks.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/page-breaks.js') ?>"></script>
<script src="<?= base_url('assets/js/appointment.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/appointment.js') ?>"></script>
<script src="<?= base_url('assets/js/product-list.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/product-list.js') ?>"></script>
<?= $this->endSection() ?>
