/* Anketo — builder-side conditional-logic editor.
   Exposes AkBuilderConditions.render(container, field, otherFields) and
   .collect(container) → { rules, calc }. Kept out of the builder's inline IIFE
   to keep that file manageable. otherFields = [{key,label,type,options}] with
   the current field, paragraph fields, and calc-target fields already removed. */
(function () {
    'use strict';

    var ACTIONS = [
        { v: 'show', t: 'Show this field' },
        { v: 'hide', t: 'Hide this field' },
        { v: 'require', t: 'Make this field required' },
        { v: 'disable', t: 'Make this field read-only' }
    ];
    var OPERATORS = [
        { v: 'equals', t: 'is equal to' },
        { v: 'not_equals', t: 'is not equal to' },
        { v: 'contains', t: 'contains' },
        { v: 'not_contains', t: 'does not contain' },
        { v: 'empty', t: 'is empty' },
        { v: 'not_empty', t: 'is filled' },
        { v: 'gt', t: 'greater than' },
        { v: 'lt', t: 'less than' }
    ];
    var OPTION_TYPES = ['checkbox', 'radio', 'select'];
    var NO_VALUE_OPS = ['empty', 'not_empty'];

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function optionsHtml(list, selected, placeholder) {
        var html = placeholder ? '<option value="">' + esc(placeholder) + '</option>' : '';
        list.forEach(function (o) {
            html += '<option value="' + esc(o.v) + '"' + (o.v === selected ? ' selected' : '') + '>' + esc(o.t) + '</option>';
        });
        return html;
    }

    function fieldByKey(otherFields, key) {
        for (var i = 0; i < otherFields.length; i++) { if (otherFields[i].key === key) { return otherFields[i]; } }
        return null;
    }

    // Value control: dropdown of option values when the chosen field is a
    // choice field, else a text input. (radio/select/checkbox compare on value.)
    function valueControl(otherFields, fieldKey, value) {
        var f = fieldByKey(otherFields, fieldKey);
        if (f && OPTION_TYPES.indexOf(f.type) !== -1 && Array.isArray(f.options)) {
            var opts = f.options.map(function (o) { return { v: o.value, t: o.label }; });
            return '<select class="form-select form-select-sm akc-value">' + optionsHtml(opts, value, 'Choose…') + '</select>';
        }
        return '<input type="text" class="form-control form-control-sm akc-value" value="' + esc(value || '') + '" placeholder="value">';
    }

    function whenRowHtml(otherFields, cond) {
        cond = cond || {};
        var fieldOpts = otherFields.map(function (f) { return { v: f.key, t: f.label }; });
        return '<div class="akc-when input-group input-group-sm mb-1">' +
            '<select class="form-select akc-field" style="max-width:38%">' + optionsHtml(fieldOpts, cond.field, 'Field…') + '</select>' +
            '<select class="form-select akc-op" style="max-width:34%">' + optionsHtml(OPERATORS, cond.operator || 'equals') + '</select>' +
            valueControl(otherFields, cond.field, cond.value) +
            '<button type="button" class="btn btn-outline-danger akc-remove-when" title="Remove">&times;</button>' +
            '</div>';
    }

    function ruleHtml(otherFields, rule) {
        rule = rule || {};
        var when = (rule.when && rule.when.length) ? rule.when : [{}];
        return '<div class="akc-rule border rounded p-2 mb-2">' +
            '<div class="d-flex gap-1 mb-2">' +
            '<select class="form-select form-select-sm akc-action">' + optionsHtml(ACTIONS, rule.action || 'show') + '</select>' +
            '<button type="button" class="btn btn-sm btn-outline-danger akc-remove-rule" title="Remove condition"><i class="bi bi-trash"></i></button>' +
            '</div>' +
            '<div class="small text-muted mb-1">when <select class="form-select form-select-sm d-inline-block w-auto akc-match">' +
            optionsHtml([{ v: 'all', t: 'all' }, { v: 'any', t: 'any' }], rule.match || 'all') + '</select> of these match:</div>' +
            '<div class="akc-when-list">' + when.map(function (c) { return whenRowHtml(otherFields, c); }).join('') + '</div>' +
            '<button type="button" class="btn btn-sm btn-link p-0 akc-add-when">+ add rule</button>' +
            '</div>';
    }

    function render(container, field, otherFields) {
        var conditions = field.conditions || {};
        var rules = conditions.rules || [];
        var isCalcTarget = field.field_type === 'number' || field.field_type === 'text';

        var html = '<hr class="my-3"><div class="form-label small">Conditional logic</div>';

        if (otherFields.length === 0) {
            html += '<p class="text-muted small mb-2">Add other fields first to use conditions.</p>';
        } else {
            html += '<div class="akc-rules">' + rules.map(function (r) { return ruleHtml(otherFields, r); }).join('') + '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary akc-add-rule mb-2"><i class="bi bi-plus"></i> Add condition</button>';
        }

        if (isCalcTarget) {
            var formula = (conditions.calc && conditions.calc.formula) || '';
            html += '<div class="mt-2"><label class="form-label small">Calculation formula <span class="text-muted">(optional)</span></label>' +
                '<input type="text" class="form-control form-control-sm akc-calc" value="' + esc(formula) + '" placeholder="{field_key} * 2 + {other}">' +
                '<div class="form-text">Use {field_key} tokens and + - * / ( ). The value is computed and read-only.</div></div>';
        }

        container.innerHTML = html;
        wire(container, otherFields);
    }

    function wire(container, otherFields) {
        container.addEventListener('click', function (evt) {
            var addRule = evt.target.closest('.akc-add-rule');
            if (addRule) {
                container.querySelector('.akc-rules').insertAdjacentHTML('beforeend', ruleHtml(otherFields, {}));
                return;
            }
            var addWhen = evt.target.closest('.akc-add-when');
            if (addWhen) {
                addWhen.previousElementSibling.insertAdjacentHTML('beforeend', whenRowHtml(otherFields, {}));
                return;
            }
            var rmWhen = evt.target.closest('.akc-remove-when');
            if (rmWhen) {
                var list = rmWhen.closest('.akc-when-list');
                rmWhen.closest('.akc-when').remove();
                if (list && list.children.length === 0) { list.insertAdjacentHTML('beforeend', whenRowHtml(otherFields, {})); }
                return;
            }
            var rmRule = evt.target.closest('.akc-remove-rule');
            if (rmRule) { rmRule.closest('.akc-rule').remove(); return; }
        });

        // Swap the value control when the chosen field changes.
        container.addEventListener('change', function (evt) {
            var fieldSel = evt.target.closest('.akc-field');
            if (!fieldSel) { return; }
            var row = fieldSel.closest('.akc-when');
            var val = row.querySelector('.akc-value');
            var tmp = document.createElement('div');
            tmp.innerHTML = valueControl(otherFields, fieldSel.value, '');
            val.replaceWith(tmp.firstChild);
        });
    }

    function collect(container) {
        var out = {};
        var rules = [];
        Array.prototype.forEach.call(container.querySelectorAll('.akc-rule'), function (ruleEl) {
            var action = ruleEl.querySelector('.akc-action').value;
            var match = ruleEl.querySelector('.akc-match').value;
            var when = [];
            Array.prototype.forEach.call(ruleEl.querySelectorAll('.akc-when'), function (w) {
                var field = w.querySelector('.akc-field').value;
                var op = w.querySelector('.akc-op').value;
                var valEl = w.querySelector('.akc-value');
                var value = valEl ? valEl.value : '';
                if (field === '') { return; }
                if (NO_VALUE_OPS.indexOf(op) === -1 && value === '') { return; }
                when.push({ field: field, operator: op, value: value });
            });
            if (when.length) { rules.push({ action: action, match: match, when: when }); }
        });
        if (rules.length) { out.rules = rules; }

        var calc = container.querySelector('.akc-calc');
        if (calc && calc.value.trim() !== '') { out.calc = { formula: calc.value.trim() }; }

        return out;
    }

    window.AkBuilderConditions = { render: render, collect: collect };
})();
