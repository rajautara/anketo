<div class="mb-3">
    <label for="field-<?= $field['id'] ?>" class="form-label">
        <?= esc($field['label']) ?><?php if ($field['is_required']) : ?> <span class="text-danger">*</span><?php endif ?>
    </label>
    <input type="number" class="form-control<?= $fieldError ? ' is-invalid' : '' ?>"
           id="field-<?= $field['id'] ?>"
           name="answers[<?= esc($field['field_key'], 'attr') ?>]"
           placeholder="<?= esc($field['placeholder'] ?? '') ?>"
           value="<?= esc(old('answers.' . $field['field_key'], '')) ?>"
           <?= $field['is_required'] ? 'required' : '' ?>>
    <?php if (! empty($field['help_text'])) : ?><div class="form-text"><?= esc($field['help_text']) ?></div><?php endif ?>
    <?php if ($fieldError) : ?><div class="invalid-feedback d-block"><?= esc($fieldError) ?></div><?php endif ?>
</div>
