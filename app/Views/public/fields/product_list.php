<?php
    use App\Libraries\ProductList;

    $config = ProductList::sanitizeConfig($field['options'] ?? []);
    $old = old('answers.' . $field['field_key'], []);
    $old = is_array($old) ? $old : [];
    $oldSelected = is_array($old['selected'] ?? null) ? $old['selected'] : [];
    $oldQty = is_array($old['qty'] ?? null) ? $old['qty'] : [];
?>
<div class="mb-3 ak-product-list" data-ak-product-list>
    <span class="form-label d-block">
        <?= esc($field['label']) ?><?php if ($field['is_required']) : ?> <span class="text-danger">*</span><?php endif ?>
    </span>

    <div class="ak-product-items">
        <?php foreach ($config['products'] as $i => $product) : ?>
            <?php
                $stock = (int) $product['stock'];
                $disabled = $stock <= 0;
                $checked = in_array($product['id'], $oldSelected, true) && ! $disabled;
                $qty = max(1, min($stock > 0 ? $stock : 1, (int) ($oldQty[$product['id']] ?? 1)));
            ?>
            <div class="ak-product-item<?= ! empty($product['image']) ? ' has-image' : '' ?><?= $checked ? ' is-selected' : '' ?><?= $disabled ? ' is-disabled' : '' ?>" data-price="<?= esc((string) $product['price'], 'attr') ?>">
                <input class="form-check-input ak-product-check" type="checkbox"
                       id="field-<?= $field['id'] ?>-product-<?= $i ?>"
                       name="answers[<?= esc($field['field_key'], 'attr') ?>][selected][]"
                       value="<?= esc($product['id'], 'attr') ?>"
                       <?= $checked ? 'checked' : '' ?>
                       <?= $disabled ? 'disabled' : '' ?>>
                <?php if (! empty($product['image'])) : ?>
                    <div class="ak-product-media">
                        <img src="<?= site_url('product-image/' . $field['form_id'] . '/' . $product['image']) ?>" alt="">
                    </div>
                <?php endif ?>
                <div class="ak-product-main">
                    <label class="ak-product-name" for="field-<?= $field['id'] ?>-product-<?= $i ?>"><?= esc($product['name']) ?></label>
                    <?php if ($product['description'] !== '') : ?><p class="ak-product-desc"><?= esc($product['description']) ?></p><?php endif ?>
                    <div class="ak-product-controls">
                        <label class="ak-product-qty-label" for="field-<?= $field['id'] ?>-qty-<?= $i ?>">Qty</label>
                        <select class="form-select form-select-sm ak-product-qty"
                                id="field-<?= $field['id'] ?>-qty-<?= $i ?>"
                                name="answers[<?= esc($field['field_key'], 'attr') ?>][qty][<?= esc($product['id'], 'attr') ?>]"
                                <?= $checked ? '' : 'disabled' ?>>
                            <?php for ($n = 1; $n <= max(1, $stock); $n++) : ?>
                                <option value="<?= $n ?>" <?= $qty === $n ? 'selected' : '' ?>><?= $n ?></option>
                            <?php endfor ?>
                        </select>
                        <?php if ($disabled) : ?><span class="ak-product-stock">Out of stock</span><?php endif ?>
                    </div>
                </div>
                <div class="ak-product-price"><?= esc(ProductList::formatMoney((float) $product['price'])) ?></div>
            </div>
        <?php endforeach ?>
    </div>

    <div class="ak-product-total">
        <span>Total</span>
        <strong data-ak-product-total>RM0.00</strong>
    </div>

    <?php if (! empty($field['help_text'])) : ?><div class="form-text"><?= esc($field['help_text']) ?></div><?php endif ?>
    <?php if ($fieldError) : ?><div class="invalid-feedback d-block"><?= esc($fieldError) ?></div><?php endif ?>
</div>
