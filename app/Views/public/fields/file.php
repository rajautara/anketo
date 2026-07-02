<div class="mb-3">
    <label for="field-<?= $field['id'] ?>" class="form-label">
        <?= esc($field['label']) ?><?php if ($field['is_required']) : ?> <span class="text-danger">*</span><?php endif ?>
    </label>
    <input type="file" class="form-control<?= $fieldError ? ' is-invalid' : '' ?>"
           id="field-<?= $field['id'] ?>"
           name="answers[<?= esc($field['field_key'], 'attr') ?>]"
           <?= $field['is_required'] ? 'required' : '' ?>>
    <div class="form-text">Max file size: 10 MB.<?= ! empty($field['help_text']) ? ' ' . esc($field['help_text']) : '' ?></div>
    <?php if ($fieldError) : ?><div class="invalid-feedback d-block"><?= esc($fieldError) ?></div><?php endif ?>
</div>
