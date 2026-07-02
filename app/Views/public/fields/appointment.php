<?php
    $config = is_array($field['options'] ?? null) ? $field['options'] : [];
    $booked = $bookedSlots[$field['field_key']] ?? [];
    $maxDays = (int) ($config['date_max_days'] ?? 60);
    // Native date input bounds (client also enforces weekday/slot availability).
    $minDate = date('Y-m-d');
    $maxDate = date('Y-m-d', strtotime('+' . max(1, $maxDays) . ' days'));
?>
<div class="mb-3 ak-appointment" data-ak-appointment
     data-config="<?= esc(json_encode($config), 'attr') ?>"
     data-booked="<?= esc(json_encode(array_values($booked)), 'attr') ?>">
    <label for="field-<?= $field['id'] ?>-date" class="form-label">
        <?= esc($field['label']) ?><?php if ($field['is_required']) : ?> <span class="text-danger">*</span><?php endif ?>
    </label>

    <input type="date" class="form-control ak-appt-date<?= $fieldError ? ' is-invalid' : '' ?>"
           id="field-<?= $field['id'] ?>-date"
           min="<?= esc($minDate, 'attr') ?>" max="<?= esc($maxDate, 'attr') ?>">

    <div class="ak-appt-slots d-flex flex-wrap gap-2 mt-2"></div>
    <p class="ak-appt-empty text-muted small mt-2 mb-0 d-none">No available times on that date.</p>

    <?php $old = old('answers.' . $field['field_key'], ''); ?>
    <input type="hidden" name="answers[<?= esc($field['field_key'], 'attr') ?>]"
           class="ak-appt-value" value="<?= esc($old, 'attr') ?>">

    <?php if (! empty($field['help_text'])) : ?><div class="form-text"><?= esc($field['help_text']) ?></div><?php endif ?>
    <?php if ($fieldError) : ?><div class="invalid-feedback d-block"><?= esc($fieldError) ?></div><?php endif ?>
</div>
