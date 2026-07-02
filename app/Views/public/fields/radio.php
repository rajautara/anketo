<div class="mb-3">
    <span class="form-label d-block">
        <?= esc($field['label']) ?><?php if ($field['is_required']) : ?> <span class="text-danger">*</span><?php endif ?>
    </span>
    <?php $oldValue = old('answers.' . $field['field_key'], '') ?>
    <?php foreach (($field['options'] ?? []) as $i => $option) : ?>
        <div class="form-check">
            <input class="form-check-input<?= $fieldError ? ' is-invalid' : '' ?>" type="radio"
                   id="field-<?= $field['id'] ?>-<?= $i ?>"
                   name="answers[<?= esc($field['field_key'], 'attr') ?>]"
                   value="<?= esc($option['value'], 'attr') ?>"
                   <?= $oldValue === $option['value'] ? 'checked' : '' ?>
                   <?= $field['is_required'] ? 'required' : '' ?>>
            <label class="form-check-label" for="field-<?= $field['id'] ?>-<?= $i ?>"><?= esc($option['label']) ?></label>
        </div>
    <?php endforeach ?>
    <?php if (! empty($field['help_text'])) : ?><div class="form-text"><?= esc($field['help_text']) ?></div><?php endif ?>
    <?php if ($fieldError) : ?><div class="invalid-feedback d-block"><?= esc($fieldError) ?></div><?php endif ?>
</div>
