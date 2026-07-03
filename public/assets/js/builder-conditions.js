/* Anketo - builder-side conditional logic + value update editor.
   Exposes AkBuilderConditions.render(container, field, otherFields) and
   .collect(container) -> { rules, calc, updates }. */
(function () {
    'use strict';

    var STATE_ACTIONS = [
        { v: 'show', t: 'Show this field' },
        { v: 'hide', t: 'Hide this field' },
        { v: 'require', t: 'Make this field required' },
        { v: 'disable', t: 'Make this field read-only' }
    ];
    var UPDATE_ACTIONS = [
        { v: 'copy', t: 'Copy another field' },
        { v: 'set', t: 'Set a fixed value' },
        { v: 'calculate', t: 'Calculate a value' }
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
    var UPDATE_TARGET_TYPES = ['text', 'email', 'number', 'textarea', 'date'];
    var CALC_TARGET_TYPES = ['text', 'number'];
    var NO_VALUE_OPS = ['empty', 'not_empty'];

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function optionsHtml(list, selected, placeholder) {
        var html = placeholder ? '<option value="">' + esc(placeholder) + '</option>' : '';
        list.forEach(function (o) {
            html += '<option value="' + esc(o.v) + '"' + (String(o.v) === String(selected) ? ' selected' : '') + '>' + esc(o.t) + '</option>';
        });
        return html;
    }

    function fieldOptions(fields) {
        return fields.map(function (f) { return { v: f.key, t: f.label }; });
    }

    function fieldByKey(fields, key) {
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].key === key) { return fields[i]; }
        }
        return null;
    }

    function usableSourceFields(fields) {
        return fields.filter(function (f) {
            return ['paragraph', 'page_break', 'file'].indexOf(f.type) === -1;
        });
    }

    function updateActionsFor(field) {
        return UPDATE_ACTIONS.filter(function (a) {
            return a.v !== 'calculate' || CALC_TARGET_TYPES.indexOf(field.field_type) !== -1;
        });
    }

    function valueControl(fields, fieldKey, value, className, placeholder) {
        var f = fieldByKey(fields, fieldKey);
        className = className || 'akc-value';
        placeholder = placeholder || 'value';

        if (f && OPTION_TYPES.indexOf(f.type) !== -1 && Array.isArray(f.options)) {
            var opts = f.options.map(function (o) { return { v: o.value, t: o.label }; });
            return '<select class="form-select form-select-sm ' + className + '">' + optionsHtml(opts, value, 'Choose...') + '</select>';
        }

        return '<input type="text" class="form-control form-control-sm ' + className + '" value="' + esc(value || '') + '" placeholder="' + esc(placeholder) + '">';
    }

    function conditionRowHtml(fields, cond) {
        cond = cond || {};
        var noValue = NO_VALUE_OPS.indexOf(cond.operator || 'equals') !== -1;
        return '<div class="akc-when input-group input-group-sm mb-1">' +
            '<select class="form-select akc-field" style="max-width:38%">' + optionsHtml(fieldOptions(fields), cond.field, 'Field...') + '</select>' +
            '<select class="form-select akc-op" style="max-width:34%">' + optionsHtml(OPERATORS, cond.operator || 'equals') + '</select>' +
            '<span class="akc-value-wrap' + (noValue ? ' d-none' : '') + '">' + valueControl(fields, cond.field, cond.value, 'akc-value', 'value') + '</span>' +
            '<button type="button" class="btn btn-outline-danger akc-remove-when" title="Remove">&times;</button>' +
            '</div>';
    }

    function stateRuleHtml(fields, rule) {
        rule = rule || {};
        var when = (rule.when && rule.when.length) ? rule.when : [{}];
        return '<div class="akc-rule border rounded p-2 mb-2">' +
            '<div class="d-flex gap-1 mb-2">' +
            '<select class="form-select form-select-sm akc-action">' + optionsHtml(STATE_ACTIONS, rule.action || 'show') + '</select>' +
            '<button type="button" class="btn btn-sm btn-outline-danger akc-remove-rule" title="Remove condition"><i class="bi bi-trash"></i></button>' +
            '</div>' +
            '<div class="small text-muted mb-1">when <select class="form-select form-select-sm d-inline-block w-auto akc-match">' +
            optionsHtml([{ v: 'all', t: 'all' }, { v: 'any', t: 'any' }], rule.match || 'all') + '</select> of these match:</div>' +
            '<div class="akc-when-list">' + when.map(function (c) { return conditionRowHtml(fields, c); }).join('') + '</div>' +
            '<button type="button" class="btn btn-sm btn-link p-0 akc-add-when">+ add rule</button>' +
            '</div>';
    }

    function updateWhenHtml(fields, cond) {
        cond = cond || {};
        var noValue = NO_VALUE_OPS.indexOf(cond.operator || 'equals') !== -1;
        return '<div class="akcu-condition">' +
            '<div class="akcu-row">' +
            '<label>IF</label>' +
            '<select class="form-select form-select-sm akcu-field">' + optionsHtml(fieldOptions(fields), cond.field, 'Select field') + '</select>' +
            '</div>' +
            '<div class="akcu-row">' +
            '<label>STATE</label>' +
            '<select class="form-select form-select-sm akcu-op">' + optionsHtml(OPERATORS, cond.operator || 'equals', 'Select field state') + '</select>' +
            '</div>' +
            '<div class="akcu-row akcu-value-row' + (noValue ? ' d-none' : '') + '">' +
            '<label>VALUE</label>' +
            valueControl(fields, cond.field, cond.value, 'akcu-value', 'Please type a value here') +
            '</div>' +
            '</div>';
    }

    function updateActionConfigHtml(field, sourceFields, rule) {
        rule = rule || {};
        var action = rule.action || 'copy';

        if (action === 'set') {
            return '<div class="akcu-row">' +
                '<label>TO</label>' +
                '<input type="text" class="form-control form-control-sm akcu-set-value" value="' + esc(rule.value || '') + '" placeholder="Value to place in this field">' +
                '</div>';
        }

        if (action === 'calculate' && CALC_TARGET_TYPES.indexOf(field.field_type) !== -1) {
            return '<div class="akcu-row">' +
                '<label>FORMULA</label>' +
                '<input type="text" class="form-control form-control-sm akcu-formula" value="' + esc(rule.formula || '') + '" placeholder="{field_key} * 2 + {other}">' +
                '</div>';
        }

        return '<div class="akcu-row">' +
            '<label>FROM</label>' +
            '<select class="form-select form-select-sm akcu-source">' + optionsHtml(fieldOptions(sourceFields), rule.source, 'Select field') + '</select>' +
            '</div>';
    }

    function updateRuleHtml(field, fields, sourceFields, rule) {
        rule = rule || {};
        var when = (rule.when && rule.when.length) ? rule.when[0] : {};
        var actions = updateActionsFor(field);
        var action = rule.action || actions[0].v;

        return '<div class="akcu-rule" data-update-rule>' +
            '<div class="akcu-rule-head">' +
            '<span>Rule</span>' +
            '<button type="button" class="btn btn-sm btn-outline-danger akcu-remove-rule" title="Remove rule"><i class="bi bi-trash"></i></button>' +
            '</div>' +
            updateWhenHtml(fields, when) +
            '<div class="akcu-action">' +
            '<div class="akcu-row">' +
            '<label>DO</label>' +
            '<select class="form-select form-select-sm akcu-action-select">' + optionsHtml(actions, action, 'Select action') + '</select>' +
            '</div>' +
            '<div class="akcu-action-config">' + updateActionConfigHtml(field, sourceFields, Object.assign({}, rule, { action: action })) + '</div>' +
            '</div>' +
            '</div>';
    }

    function renderUpdateEditor(container, field, fields, conditions) {
        if (UPDATE_TARGET_TYPES.indexOf(field.field_type) === -1) {
            return '';
        }

        var sourceFields = usableSourceFields(fields);
        var updates = Array.isArray(conditions.updates) ? conditions.updates : [];
        var html = '<div class="akcu-panel" data-update-editor>' +
            '<div class="akcu-title">' +
            '<span class="akcu-icon"><i class="bi bi-calculator"></i></span>' +
            '<div><h6>UPDATE/CALCULATE FIELD</h6><p>Copy a field\'s value or perform complex calculations</p></div>' +
            '</div>';

        if (fields.length === 0) {
            html += '<p class="text-muted small mb-0">Add other fields first to update or calculate this field.</p>';
        } else {
            html += '<div class="akcu-rules">' + updates.map(function (r) {
                return updateRuleHtml(field, fields, sourceFields, r);
            }).join('') + '</div>' +
                '<button type="button" class="btn btn-sm btn-primary akcu-add-rule"><i class="bi bi-plus"></i></button>';
        }

        html += '</div>';
        return html;
    }

    function render(container, field, otherFields) {
        var conditions = field.conditions || {};
        var rules = Array.isArray(conditions.rules) ? conditions.rules : [];
        var calcFormula = (conditions.calc && conditions.calc.formula) || '';
        var html = renderUpdateEditor(container, field, otherFields, conditions);

        html += '<hr class="my-3"><div class="form-label small">Conditional logic</div>';
        if (otherFields.length === 0) {
            html += '<p class="text-muted small mb-2">Add other fields first to use conditions.</p>';
        } else {
            html += '<div class="akc-rules">' + rules.map(function (r) { return stateRuleHtml(otherFields, r); }).join('') + '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary akc-add-rule mb-2"><i class="bi bi-plus"></i> Add condition</button>';
        }

        if (CALC_TARGET_TYPES.indexOf(field.field_type) !== -1) {
            html += '<div class="mt-2"><label class="form-label small">Always calculate <span class="text-muted">(legacy)</span></label>' +
                '<input type="text" class="form-control form-control-sm akc-calc" value="' + esc(calcFormula) + '" placeholder="{field_key} * 2 + {other}">' +
                '<div class="form-text">Use this for an unconditional formula. Update rules above take precedence.</div></div>';
        }

        container.innerHTML = html;
        wire(container, field, otherFields);
    }

    function wire(container, field, fields) {
        var sourceFields = usableSourceFields(fields);

        container.addEventListener('click', function (evt) {
            var addUpdate = evt.target.closest('.akcu-add-rule');
            if (addUpdate) {
                container.querySelector('.akcu-rules').insertAdjacentHTML('beforeend', updateRuleHtml(field, fields, sourceFields, {}));
                return;
            }

            var rmUpdate = evt.target.closest('.akcu-remove-rule');
            if (rmUpdate) {
                rmUpdate.closest('.akcu-rule').remove();
                return;
            }

            var addRule = evt.target.closest('.akc-add-rule');
            if (addRule) {
                container.querySelector('.akc-rules').insertAdjacentHTML('beforeend', stateRuleHtml(fields, {}));
                return;
            }

            var addWhen = evt.target.closest('.akc-add-when');
            if (addWhen) {
                addWhen.previousElementSibling.insertAdjacentHTML('beforeend', conditionRowHtml(fields, {}));
                return;
            }

            var rmWhen = evt.target.closest('.akc-remove-when');
            if (rmWhen) {
                var list = rmWhen.closest('.akc-when-list');
                rmWhen.closest('.akc-when').remove();
                if (list && list.children.length === 0) {
                    list.insertAdjacentHTML('beforeend', conditionRowHtml(fields, {}));
                }
                return;
            }

            var rmRule = evt.target.closest('.akc-remove-rule');
            if (rmRule) {
                rmRule.closest('.akc-rule').remove();
            }
        });

        container.addEventListener('change', function (evt) {
            var stateField = evt.target.closest('.akc-field');
            if (stateField) {
                replaceValueControl(stateField.closest('.akc-when'), fields, stateField.value, 'akc-value', 'value');
                syncNoValueState(stateField.closest('.akc-when'), 'akc-op', 'akc-value-wrap');
                return;
            }

            var stateOp = evt.target.closest('.akc-op');
            if (stateOp) {
                syncNoValueState(stateOp.closest('.akc-when'), 'akc-op', 'akc-value-wrap');
                return;
            }

            var updateField = evt.target.closest('.akcu-field');
            if (updateField) {
                replaceValueControl(updateField.closest('.akcu-condition'), fields, updateField.value, 'akcu-value', 'Please type a value here');
                syncNoValueState(updateField.closest('.akcu-condition'), 'akcu-op', 'akcu-value-row');
                return;
            }

            var updateOp = evt.target.closest('.akcu-op');
            if (updateOp) {
                syncNoValueState(updateOp.closest('.akcu-condition'), 'akcu-op', 'akcu-value-row');
                return;
            }

            var updateAction = evt.target.closest('.akcu-action-select');
            if (updateAction) {
                var rule = updateAction.closest('.akcu-rule');
                rule.querySelector('.akcu-action-config').innerHTML = updateActionConfigHtml(field, sourceFields, { action: updateAction.value });
            }
        });
    }

    function replaceValueControl(scope, fields, fieldKey, className, placeholder) {
        var old = scope.querySelector('.' + className);
        if (!old) { return; }
        var tmp = document.createElement('div');
        tmp.innerHTML = valueControl(fields, fieldKey, '', className, placeholder);
        old.replaceWith(tmp.firstChild);
    }

    function syncNoValueState(scope, opClass, valueContainerClass) {
        if (!scope) { return; }
        var op = (scope.querySelector('.' + opClass) || {}).value || '';
        var wrap = scope.querySelector('.' + valueContainerClass);
        if (!wrap) { return; }

        var noValue = NO_VALUE_OPS.indexOf(op) !== -1;
        wrap.classList.toggle('d-none', noValue);
        var value = wrap.querySelector('input, select, textarea');
        if (value && noValue) { value.value = ''; }
    }

    function collectWhen(row, fieldClass, opClass, valueClass) {
        var field = (row.querySelector('.' + fieldClass) || {}).value || '';
        var op = (row.querySelector('.' + opClass) || {}).value || '';
        var valEl = row.querySelector('.' + valueClass);
        var value = valEl ? valEl.value : '';

        if (field === '') { return null; }
        if (NO_VALUE_OPS.indexOf(op) === -1 && value === '') { return null; }

        return { field: field, operator: op, value: value };
    }

    function collect(container) {
        var out = {};
        var rules = [];
        Array.prototype.forEach.call(container.querySelectorAll('.akc-rule'), function (ruleEl) {
            var when = [];
            Array.prototype.forEach.call(ruleEl.querySelectorAll('.akc-when'), function (w) {
                var cond = collectWhen(w, 'akc-field', 'akc-op', 'akc-value');
                if (cond) { when.push(cond); }
            });
            if (when.length) {
                rules.push({
                    action: ruleEl.querySelector('.akc-action').value,
                    match: ruleEl.querySelector('.akc-match').value,
                    when: when
                });
            }
        });
        if (rules.length) { out.rules = rules; }

        var updates = [];
        Array.prototype.forEach.call(container.querySelectorAll('.akcu-rule'), function (ruleEl) {
            var cond = collectWhen(ruleEl, 'akcu-field', 'akcu-op', 'akcu-value');
            if (!cond) { return; }

            var action = ruleEl.querySelector('.akcu-action-select').value;
            var update = { match: 'all', when: [cond], action: action };

            if (action === 'copy') {
                var source = (ruleEl.querySelector('.akcu-source') || {}).value || '';
                if (source === '') { return; }
                update.source = source;
            } else if (action === 'set') {
                update.value = (ruleEl.querySelector('.akcu-set-value') || {}).value || '';
            } else if (action === 'calculate') {
                var formula = ((ruleEl.querySelector('.akcu-formula') || {}).value || '').trim();
                if (formula === '') { return; }
                update.formula = formula;
            }

            updates.push(update);
        });
        if (updates.length) { out.updates = updates; }

        var calc = container.querySelector('.akc-calc');
        if (calc && calc.value.trim() !== '') { out.calc = { formula: calc.value.trim() }; }

        return out;
    }

    window.AkBuilderConditions = { render: render, collect: collect };
})();
