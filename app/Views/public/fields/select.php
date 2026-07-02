<div class="mb-3">
    <label for="field-<?= $field['id'] ?>" class="form-label">
        <?= esc($field['label']) ?><?php if ($field['is_required']) : ?> <span class="text-danger">*</span><?php endif ?>
    </label>
    <select class="form-select<?= $fieldError ? ' is-invalid' : '' ?>"
            id="field-<?= $field['id'] ?>"
            name="answers[<?= esc($field['field_key'], 'attr') ?>]"
            <?= $field['is_required'] ? 'required' : '' ?>>
        <option value="" disabled <?= old('answers.' . $field['field_key'], '') === '' ? 'selected' : '' ?>>Choose...</option>
        <?php foreach (($field['options'] ?? []) as $option) : ?>
            <option value="<?= esc($option['value'], 'attr') ?>" <?= old('answers.' . $field['field_key'], '') === $option['value'] ? 'selected' : '' ?>>
                <?= esc($option['label']) ?>
            </option>
        <?php endforeach ?>
    </select>
    <?php if (! empty($field['help_text'])) : ?><div class="form-text"><?= esc($field['help_text']) ?></div><?php endif ?>
    <?php if ($fieldError) : ?><div class="invalid-feedback d-block"><?= esc($fieldError) ?></div><?php endif ?>
</div>
