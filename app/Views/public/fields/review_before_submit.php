<?php $showHiddenText = (bool) ($field['options']['show_hidden_text'] ?? false); ?>
<div class="mb-3 ak-review-before-submit" data-ak-review-before-submit data-ak-review-show-hidden-text="<?= $showHiddenText ? '1' : '0' ?>">
    <?php if (! empty($field['label'])) : ?>
        <h3 class="h5 mb-2"><?= esc($field['label']) ?></h3>
    <?php endif ?>
    <div class="ak-review-summary" data-ak-review-summary>
        <p class="text-muted mb-0">Your answers will appear here before you submit.</p>
    </div>
</div>
