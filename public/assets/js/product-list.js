(function () {
    'use strict';

    function money(value) {
        return 'RM' + (Math.round(value * 100) / 100).toFixed(2);
    }

    function refresh(root) {
        var total = 0;
        Array.prototype.forEach.call(root.querySelectorAll('.ak-product-item'), function (item) {
            var check = item.querySelector('.ak-product-check');
            var qty = item.querySelector('.ak-product-qty');
            if (!check || !qty) { return; }

            var selected = check.checked && !check.disabled;
            qty.disabled = !selected;
            item.classList.toggle('is-selected', selected);

            if (!selected) { return; }
            var n = parseInt(qty.value || '1', 10) || 1;
            if (qty.tagName !== 'SELECT') {
                var max = parseInt(qty.max || '1', 10) || 1;
                n = Math.max(1, Math.min(max, n));
                qty.value = n;
            }
            total += (parseFloat(item.getAttribute('data-price') || '0') || 0) * n;
        });

        var out = root.querySelector('[data-ak-product-total]');
        if (out) { out.textContent = money(total); }
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-ak-product-list]'), function (root) {
        root.addEventListener('change', function (evt) {
            if (evt.target.matches('.ak-product-check, .ak-product-qty')) {
                refresh(root);
            }
        });
        root.addEventListener('input', function (evt) {
            if (evt.target.matches('.ak-product-qty')) {
                refresh(root);
            }
        });
        refresh(root);
    });
})();
