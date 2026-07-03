/* Anketo — page break navigation for public multi-step forms. */
(function () {
    'use strict';

    var form = document.querySelector('form[data-ak-paged-form]');
    if (!form) { return; }

    var pages = Array.prototype.slice.call(form.querySelectorAll('[data-ak-form-page]'));
    if (pages.length <= 1) { return; }

    var backBtn = form.querySelector('[data-ak-page-back]');
    var nextBtn = form.querySelector('[data-ak-page-next]');
    var submitBtn = form.querySelector('[data-ak-page-submit]');
    var current = 0;

    form.noValidate = true;

    function setPage(index) {
        current = Math.max(0, Math.min(index, pages.length - 1));

        pages.forEach(function (page, i) {
            page.hidden = i !== current;
        });

        if (backBtn) {
            backBtn.classList.toggle('d-none', current === 0);
        }
        if (nextBtn) {
            nextBtn.classList.toggle('d-none', current === pages.length - 1);
        }
        if (submitBtn) {
            submitBtn.classList.toggle('d-none', current !== pages.length - 1);
        }
    }

    function controlsFor(page) {
        return Array.prototype.slice.call(page.querySelectorAll('input, select, textarea')).filter(function (el) {
            return !el.disabled
                && el.type !== 'hidden'
                && !el.closest('.ak-field[hidden]');
        });
    }

    function validatePage(index, report) {
        var controls = controlsFor(pages[index]);

        for (var i = 0; i < controls.length; i++) {
            if (!controls[i].checkValidity()) {
                if (report) {
                    setPage(index);
                    controls[i].reportValidity();
                }
                return false;
            }
        }

        return true;
    }

    if (backBtn) {
        backBtn.addEventListener('click', function () {
            setPage(current - 1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            if (validatePage(current, true)) {
                setPage(current + 1);
            }
        });
    }

    form.addEventListener('submit', function (evt) {
        for (var i = 0; i < pages.length; i++) {
            if (!validatePage(i, true)) {
                evt.preventDefault();
                return;
            }
        }
    });

    setPage(0);
})();
