<div class="mb-3 ak-paragraph">
    <?php if (! empty($field['label'])) : ?>
        <h3 class="h5 mb-1"><?= esc($field['label']) ?></h3>
    <?php endif ?>
    <?php $body = $field['options']['body'] ?? ''; ?>
    <?php if ($body !== '') : ?>
        <?php // Body is stored pre-sanitised by App\Libraries\HtmlSanitizer on save. ?>
        <div class="ak-paragraph-body text-muted"><?= $body ?></div>
    <?php endif ?>
</div>
