<?php
    use App\Libraries\AddressField;

    $old = old('answers.' . $field['field_key'], []);
    $old = AddressField::sanitize(is_array($old) ? $old : []);
    $requiredParts = array_flip(AddressField::REQUIRED_PARTS);
    $isRequiredPart = static fn (string $part): bool => (bool) $field['is_required'] && isset($requiredParts[$part]);
    $controlClass = 'form-control' . ($fieldError ? ' is-invalid' : '');
    $selectClass = 'form-select' . ($fieldError ? ' is-invalid' : '');
    $name = static fn (string $part): string => 'answers[' . esc($field['field_key'], 'attr') . '][' . $part . ']';
?>
<div class="mb-3 ak-address-field" data-ak-address>
    <span class="form-label d-block">
        <?= esc($field['label']) ?><?php if ($field['is_required']) : ?> <span class="text-danger">*</span><?php endif ?>
    </span>

    <div class="ak-address-grid">
        <div class="ak-address-full">
            <input type="text" class="<?= $controlClass ?>"
                   id="field-<?= $field['id'] ?>-street-address"
                   name="<?= $name('street_address') ?>"
                   value="<?= esc($old['street_address'], 'attr') ?>"
                   <?= $isRequiredPart('street_address') ? 'required' : '' ?>>
            <label for="field-<?= $field['id'] ?>-street-address" class="form-text">Street Address</label>
        </div>

        <div class="ak-address-full">
            <input type="text" class="<?= $controlClass ?>"
                   id="field-<?= $field['id'] ?>-street-address-2"
                   name="<?= $name('street_address_2') ?>"
                   value="<?= esc($old['street_address_2'], 'attr') ?>">
            <label for="field-<?= $field['id'] ?>-street-address-2" class="form-text">Street Address Line 2</label>
        </div>

        <div>
            <input type="text" class="<?= $controlClass ?>"
                   id="field-<?= $field['id'] ?>-city"
                   name="<?= $name('city') ?>"
                   value="<?= esc($old['city'], 'attr') ?>"
                   <?= $isRequiredPart('city') ? 'required' : '' ?>>
            <label for="field-<?= $field['id'] ?>-city" class="form-text">City</label>
        </div>

        <div>
            <input type="text" class="<?= $controlClass ?>"
                   id="field-<?= $field['id'] ?>-state-province"
                   name="<?= $name('state_province') ?>"
                   value="<?= esc($old['state_province'], 'attr') ?>"
                   <?= $isRequiredPart('state_province') ? 'required' : '' ?>>
            <label for="field-<?= $field['id'] ?>-state-province" class="form-text">State / Province</label>
        </div>

        <div>
            <input type="text" class="<?= $controlClass ?>"
                   id="field-<?= $field['id'] ?>-postal-zip-code"
                   name="<?= $name('postal_zip_code') ?>"
                   value="<?= esc($old['postal_zip_code'], 'attr') ?>"
                   <?= $isRequiredPart('postal_zip_code') ? 'required' : '' ?>>
            <label for="field-<?= $field['id'] ?>-postal-zip-code" class="form-text">Postal / Zip Code</label>
        </div>

        <div>
            <select class="<?= $selectClass ?>"
                    id="field-<?= $field['id'] ?>-country"
                    name="<?= $name('country') ?>"
                    <?= $isRequiredPart('country') ? 'required' : '' ?>>
                <option value=""<?= $old['country'] === '' ? ' selected' : '' ?>>Please Select</option>
                <?php foreach (AddressField::COUNTRIES as $country) : ?>
                    <option value="<?= esc($country, 'attr') ?>"<?= $old['country'] === $country ? ' selected' : '' ?>>
                        <?= esc($country) ?>
                    </option>
                <?php endforeach ?>
            </select>
            <label for="field-<?= $field['id'] ?>-country" class="form-text">Country</label>
        </div>
    </div>

    <?php if (! empty($field['help_text'])) : ?><div class="form-text mt-2"><?= esc($field['help_text']) ?></div><?php endif ?>
    <?php if ($fieldError) : ?><div class="invalid-feedback d-block"><?= esc($fieldError) ?></div><?php endif ?>
</div>
