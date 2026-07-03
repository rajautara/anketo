(function () {
    'use strict';

    var cfgEl = document.getElementById('ak-form-config');
    if (!cfgEl) { return; }

    var configs;
    try { configs = JSON.parse(cfgEl.textContent || '[]'); } catch (e) { return; }
    if (!Array.isArray(configs)) { return; }

    var form = cfgEl.closest('.ak-form-card') ? cfgEl.closest('.ak-form-card').querySelector('form') : document.querySelector('.ak-form-body form');
    if (!form || !form.querySelector('[data-ak-review-before-submit]')) { return; }

    var SKIP_TYPES = ['paragraph', 'page_break', 'review_before_submit'];

    function esc(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function wrapperFor(key) {
        return form.querySelector('.ak-field[data-field-key="' + (window.CSS && CSS.escape ? CSS.escape(key) : key) + '"]');
    }

    function isHiddenText(cfg) {
        return cfg.type === 'text' && !!(cfg.options && cfg.options.is_hidden);
    }

    function isVisibleAnswerField(wrap, cfg, showHiddenText) {
        if (!wrap || wrap.hidden || SKIP_TYPES.indexOf(cfg.type) !== -1) { return false; }
        if (isHiddenText(cfg) && !showHiddenText) { return false; }
        return true;
    }

    function labelFor(wrap, cfg) {
        if (cfg.label) { return cfg.label; }
        var label = wrap.querySelector('.form-label');
        return label ? label.textContent.replace('*', '').trim() : cfg.key;
    }

    function optionLabel(cfg, value) {
        var labels = cfg.option_labels || {};
        return Object.prototype.hasOwnProperty.call(labels, String(value)) ? labels[String(value)] : value;
    }

    function choiceLabels(wrap, cfg, selector) {
        return Array.prototype.map.call(wrap.querySelectorAll(selector), function (input) {
            return optionLabel(cfg, input.value);
        }).filter(function (value) { return String(value || '').trim() !== ''; });
    }

    function productValues(wrap) {
        return Array.prototype.map.call(wrap.querySelectorAll('.ak-product-check:checked'), function (input) {
            var item = input.closest('.ak-product-item');
            if (!item || input.disabled) { return ''; }

            var name = item.querySelector('.ak-product-name');
            var qty = item.querySelector('.ak-product-qty');
            var qtyText = qty ? String(qty.value || '1') : '1';
            return (name ? name.textContent.trim() : input.value) + ' x ' + qtyText;
        }).filter(function (value) { return value !== ''; });
    }

    function addressPart(wrap, part) {
        var el = wrap ? wrap.querySelector('[name$="[' + part + ']"]') : null;
        return el ? String(el.value || '').trim() : '';
    }

    function addressValue(wrap) {
        var cityLine = [addressPart(wrap, 'city'), addressPart(wrap, 'state_province'), addressPart(wrap, 'postal_zip_code')]
            .filter(function (value) { return value !== ''; })
            .join(', ');
        return [addressPart(wrap, 'street_address'), addressPart(wrap, 'street_address_2'), cityLine, addressPart(wrap, 'country')]
            .filter(function (value) { return value !== ''; })
            .join(', ');
    }

    function valueFor(wrap, cfg) {
        if (cfg.type === 'checkbox') {
            return choiceLabels(wrap, cfg, 'input[type=checkbox]:checked').join(', ');
        }
        if (cfg.type === 'radio') {
            var radio = wrap.querySelector('input[type=radio]:checked');
            return radio ? optionLabel(cfg, radio.value) : '';
        }
        if (cfg.type === 'select') {
            var select = wrap.querySelector('select');
            return select && select.value !== '' ? (select.options[select.selectedIndex] || {}).text || optionLabel(cfg, select.value) : '';
        }
        if (cfg.type === 'file') {
            var file = wrap.querySelector('input[type=file]');
            return file && file.files && file.files.length
                ? Array.prototype.map.call(file.files, function (f) { return f.name; }).join(', ')
                : '';
        }
        if (cfg.type === 'appointment') {
            var appt = wrap.querySelector('.ak-appt-value');
            return appt ? appt.value : '';
        }
        if (cfg.type === 'product_list') {
            return productValues(wrap).join(', ');
        }
        if (cfg.type === 'address') {
            return addressValue(wrap);
        }

        var input = wrap.querySelector('input, textarea, select');
        return input ? input.value : '';
    }

    function rowsHtml(showHiddenText) {
        var rows = [];

        configs.forEach(function (cfg) {
            var wrap = wrapperFor(cfg.key);
            if (!isVisibleAnswerField(wrap, cfg, showHiddenText)) { return; }

            var value = valueFor(wrap, cfg);
            if (String(value || '').trim() === '') { return; }

            rows.push(
                '<div class="ak-review-row">' +
                    '<dt>' + esc(labelFor(wrap, cfg)) + '</dt>' +
                    '<dd>' + esc(value) + '</dd>' +
                '</div>'
            );
        });

        if (rows.length === 0) {
            return '<p class="text-muted mb-0">Your answers will appear here before you submit.</p>';
        }

        return '<dl class="ak-review-list mb-0">' + rows.join('') + '</dl>';
    }

    function refresh() {
        Array.prototype.forEach.call(form.querySelectorAll('[data-ak-review-before-submit]'), function (review) {
            var box = review.querySelector('[data-ak-review-summary]');
            if (!box) { return; }
            box.innerHTML = rowsHtml(review.getAttribute('data-ak-review-show-hidden-text') === '1');
        });
    }

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();
})();
