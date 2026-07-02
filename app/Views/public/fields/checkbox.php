<div class="mb-3">
    <span class="form-label d-block">
        <?= esc($field['label']) ?><?php if ($field['is_required']) : ?> <span class="text-danger">*</span><?php endif ?>
    </span>
    <?php $oldValues = old('answers.' . $field['field_key'], []); ?>
    <?php $oldValues = is_array($oldValues) ? $oldValues : []; ?>
    <?php foreach (($field['options'] ?? []) as $i => $option) : ?>
        <div class="form-check">
            <input class="form-check-input<?= $fieldError ? ' is-invalid' : '' ?>" type="checkbox"
                   id="field-<?= $field['id'] ?>-<?= $i ?>"
                   name="answers[<?= esc($field['field_key'], 'attr') ?>][]"
                   value="<?= esc($option['value'], 'attr') ?>"
                   <?= in_array($option['value'], $oldValues, true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="field-<?= $field['id'] ?>-<?= $i ?>"><?= esc($option['label']) ?></label>
        </div>
    <?php endforeach ?>
    <?php if (! empty($field['help_text'])) : ?><div class="form-text"><?= esc($field['help_text']) ?></div><?php endif ?>
    <?php if ($fieldError) : ?><div class="invalid-feedback d-block"><?= esc($fieldError) ?></div><?php endif ?>
</div>
