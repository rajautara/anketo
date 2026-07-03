/* Anketo — live conditional logic on the public form.
   Mirrors App\Libraries\ConditionEvaluator + FormulaEvaluator. This is UX only:
   the server re-evaluates every rule on submit and is authoritative. Keep the
   compare()/formula semantics in sync with the PHP. */
(function () {
    'use strict';

    var cfgEl = document.getElementById('ak-form-config');
    if (!cfgEl) { return; }

    var configs;
    try { configs = JSON.parse(cfgEl.textContent || '[]'); } catch (e) { return; }
    if (!Array.isArray(configs) || configs.length === 0) { return; }

    var form = cfgEl.closest('.ak-form-card') ? cfgEl.closest('.ak-form-card').querySelector('form') : document.querySelector('.ak-form-body form');
    if (!form) { return; }

    var byKey = {};
    configs.forEach(function (c) { byKey[c.key] = c; });

    function wrapperFor(key) {
        return form.querySelector('.ak-field[data-field-key="' + (window.CSS && CSS.escape ? CSS.escape(key) : key) + '"]');
    }

    // ---- read current answers from the DOM ---------------------------------
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

    function readValue(wrap, type) {
        if (!wrap) { return type === 'checkbox' ? [] : ''; }
        if (type === 'checkbox') {
            return Array.prototype.map.call(wrap.querySelectorAll('input[type=checkbox]:checked'), function (i) { return i.value; });
        }
        if (type === 'radio') {
            var r = wrap.querySelector('input[type=radio]:checked');
            return r ? r.value : '';
        }
        if (type === 'appointment') {
            var h = wrap.querySelector('.ak-appt-value');
            return h ? h.value : '';
        }
        if (type === 'product_list') {
            return Array.prototype.map.call(wrap.querySelectorAll('.ak-product-check:checked'), function (i) { return i.value; });
        }
        if (type === 'address') {
            return addressValue(wrap);
        }
        var el = wrap.querySelector('input, select, textarea');
        return el ? el.value : '';
    }

    function collectAnswers() {
        var answers = {};
        configs.forEach(function (c) {
            answers[c.key] = readValue(wrapperFor(c.key), c.type);
        });
        return answers;
    }

    function productListTotal(wrap) {
        if (!wrap) { return null; }

        var total = 0;
        Array.prototype.forEach.call(wrap.querySelectorAll('.ak-product-item'), function (item) {
            var check = item.querySelector('.ak-product-check');
            var qty = item.querySelector('.ak-product-qty');
            if (!check || !qty || !check.checked || check.disabled) { return; }

            var n = parseInt(qty.value || '1', 10) || 1;
            total += (parseFloat(item.getAttribute('data-price') || '0') || 0) * n;
        });

        return Math.round(total * 100) / 100;
    }

    function formulaAnswers(rawAnswers) {
        var out = Object.assign({}, rawAnswers);

        configs.forEach(function (c) {
            var value = rawAnswers[c.key];

            if (c.type === 'product_list') {
                var total = productListTotal(wrapperFor(c.key));
                if (total !== null) {
                    out[c.key] = String(total);
                    if (c.label) { out[c.label] = String(total); }
                }
                return;
            }

            if (Array.isArray(value) || value == null) { return; }

            if (['radio', 'select'].indexOf(c.type) === -1) {
                if (c.label) { out[c.label] = value; }
                return;
            }

            var labels = c.option_labels || {};
            var displayValue = Object.prototype.hasOwnProperty.call(labels, String(value))
                ? labels[String(value)]
                : value;
            out[c.key] = displayValue;
            if (c.label) { out[c.label] = displayValue; }
        });

        return out;
    }

    // ---- compare() — mirror of ConditionEvaluator::compare ------------------
    function isNumeric(v) { return v !== '' && v !== null && !isNaN(v) && isFinite(v); }

    function compare(op, answer, value) {
        var isList = Array.isArray(answer);
        var scalar = isList ? '' : String(answer == null ? '' : answer).trim();
        var isEmpty = isList ? answer.length === 0 : scalar === '';
        value = String(value == null ? '' : value);

        switch (op) {
            case 'empty': return isEmpty;
            case 'not_empty': return !isEmpty;
            case 'equals':
                return isList ? answer.map(String).indexOf(value) !== -1 : (scalar === value);
            case 'not_equals':
                return isList ? answer.map(String).indexOf(value) === -1 : (scalar !== value);
            case 'contains':
                return isList ? answer.map(String).indexOf(value) !== -1 : (value !== '' && scalar.indexOf(value) !== -1);
            case 'not_contains':
                return isList ? answer.map(String).indexOf(value) === -1 : !(value !== '' && scalar.indexOf(value) !== -1);
            case 'gt':
            case 'lt':
                if (isList || !isNumeric(scalar) || !isNumeric(value)) { return false; }
                return op === 'gt' ? (parseFloat(scalar) > parseFloat(value)) : (parseFloat(scalar) < parseFloat(value));
        }
        return false;
    }

    function groupMatches(rule, answers) {
        var when = rule.when || [];
        if (!when.length) { return false; }
        var matchAll = rule.match !== 'any';
        for (var i = 0; i < when.length; i++) {
            var c = when[i];
            var ok = compare(c.operator, answers[c.field], c.value);
            if (matchAll && !ok) { return false; }
            if (!matchAll && ok) { return true; }
        }
        return matchAll;
    }

    function evaluate(rules, answers, baseRequired) {
        var hasShow = false, showMatch = false, hideMatch = false, required = !!baseRequired, disabled = false;
        (rules || []).forEach(function (rule) {
            if (!rule || ['show', 'hide', 'require', 'disable'].indexOf(rule.action) === -1) { return; }
            var m = groupMatches(rule, answers);
            if (rule.action === 'show') { hasShow = true; showMatch = showMatch || m; }
            else if (rule.action === 'hide') { hideMatch = hideMatch || m; }
            else if (rule.action === 'require') { required = required || m; }
            else if (rule.action === 'disable') { disabled = disabled || m; }
        });
        var visible = hasShow ? showMatch : true;
        if (hideMatch) { visible = false; }
        return { visible: visible, required: required, disabled: disabled };
    }

    // ---- formula — mirror of FormulaEvaluator ------------------------------
    function evalFormula(formula, answers) {
        formula = String(formula || '').trim();
        if (formula === '') { return ''; }

        var sub = formula.replace(/\{([^}]*)\}/g, function (_, k) {
            k = k.trim();
            var v = answers[k];
            if (k === '' || Array.isArray(v) || v == null || !isNumeric(String(v).trim())) { return '0'; }
            return '(' + parseFloat(String(v).trim()) + ')';
        });

        var tokens = tokenize(sub);
        if (!tokens) { return ''; }
        var rpn = toRpn(tokens);
        if (!rpn) { return ''; }
        var res = evalRpn(rpn);
        if (res === null || !isFinite(res)) { return ''; }
        return Number.isInteger(res) ? String(res) : String(parseFloat(res.toFixed(10)));
    }

    function tokenize(expr) {
        var tokens = [], i = 0;
        while (i < expr.length) {
            var ch = expr[i];
            if (/\s/.test(ch)) { i++; continue; }
            if (ch === '+' || ch === '-' || ch === '*' || ch === '/') { tokens.push({ t: 'op', v: ch }); i++; continue; }
            if (ch === '(' || ch === ')') { tokens.push({ t: ch === '(' ? 'lp' : 'rp' }); i++; continue; }
            if (/[0-9.]/.test(ch)) {
                var num = '', dots = 0;
                while (i < expr.length && /[0-9.]/.test(expr[i])) {
                    if (expr[i] === '.' && ++dots > 1) { return null; }
                    num += expr[i++];
                }
                if (num === '.' || num === '') { return null; }
                tokens.push({ t: 'num', v: parseFloat(num) });
                continue;
            }
            return null;
        }
        return tokens;
    }

    function toRpn(tokens) {
        var out = [], stack = [], prev = null;
        var prec = { neg: 3, '*': 2, '/': 2, '+': 1, '-': 1 };
        for (var i = 0; i < tokens.length; i++) {
            var tok = tokens[i];
            if (tok.t === 'num') { out.push(tok); prev = 'num'; continue; }
            if (tok.t === 'op') {
                var op = tok.v;
                if ((op === '-' || op === '+') && (prev === null || prev === 'op' || prev === 'lp')) {
                    if (op === '-') { stack.push({ t: 'op', v: 'neg' }); }
                    prev = 'op'; continue;
                }
                while (stack.length) {
                    var top = stack[stack.length - 1];
                    if (top.t === 'op' && prec[top.v] >= prec[op]) { out.push(stack.pop()); } else { break; }
                }
                stack.push({ t: 'op', v: op }); prev = 'op'; continue;
            }
            if (tok.t === 'lp') { stack.push(tok); prev = 'lp'; continue; }
            if (tok.t === 'rp') {
                var found = false;
                while (stack.length) { var s = stack.pop(); if (s.t === 'lp') { found = true; break; } out.push(s); }
                if (!found) { return null; }
                prev = 'rp'; continue;
            }
        }
        while (stack.length) { var e = stack.pop(); if (e.t === 'lp' || e.t === 'rp') { return null; } out.push(e); }
        return out;
    }

    function evalRpn(rpn) {
        var st = [];
        for (var i = 0; i < rpn.length; i++) {
            var tok = rpn[i];
            if (tok.t === 'num') { st.push(tok.v); continue; }
            if (tok.v === 'neg') { if (!st.length) { return null; } st.push(-st.pop()); continue; }
            if (st.length < 2) { return null; }
            var b = st.pop(), a = st.pop();
            switch (tok.v) {
                case '+': st.push(a + b); break;
                case '-': st.push(a - b); break;
                case '*': st.push(a * b); break;
                case '/': if (b === 0) { return null; } st.push(a / b); break;
                default: return null;
            }
        }
        return st.length === 1 ? st[0] : null;
    }

    function evalUpdates(updates, answers, calcAnswers) {
        if (!Array.isArray(updates)) { return null; }
        calcAnswers = calcAnswers || answers;

        for (var i = 0; i < updates.length; i++) {
            var rule = updates[i];
            if (!rule || !groupMatches(rule, answers)) { continue; }

            if (rule.action === 'copy') {
                var copied = answers[rule.source];
                return Array.isArray(copied) || copied == null ? null : String(copied);
            }
            if (rule.action === 'set') {
                return String(rule.value == null ? '' : rule.value);
            }
            if (rule.action === 'calculate') {
                var result = evalFormula(rule.formula || '', calcAnswers);
                return result === '' ? null : result;
            }
        }

        return null;
    }

    // ---- apply state to the DOM --------------------------------------------
    function controls(wrap) { return wrap ? wrap.querySelectorAll('input, select, textarea') : []; }

    function primaryControl(wrap) {
        return wrap ? wrap.querySelector('input, select, textarea') : null;
    }

    function hasUpdates(c) {
        return !!(c.conditions && Array.isArray(c.conditions.updates) && c.conditions.updates.length);
    }

    function hasLegacyCalc(c) {
        return !!(c.conditions && c.conditions.calc && c.conditions.calc.formula);
    }

    function apply() {
        var answers = collectAnswers();
        var calcAnswers = formulaAnswers(answers);

        configs.forEach(function (c) {
            var wrap = wrapperFor(c.key);
            if (!wrap) { return; }
            var rules = (c.conditions && c.conditions.rules) || [];
            var flags = evaluate(rules, answers, c.is_required);

            // Visibility: hide wrapper + disable its controls so they don't submit.
            wrap.hidden = !flags.visible;

            // Required + asterisk (only meaningful when visible).
            var star = wrap.querySelector('.text-danger');
            if (flags.visible && flags.required) {
                if (star) { star.classList.remove('d-none'); }
            } else if (star) {
                star.classList.add('d-none');
            }

            var automated = false;
            if (flags.visible && hasUpdates(c)) {
                var updatedValue = evalUpdates(c.conditions.updates, answers, calcAnswers);
                var updateInput = primaryControl(wrap);
                var updateText = updatedValue == null ? '' : String(updatedValue);
                if (updateInput) { updateInput.value = updateText; }
                answers[c.key] = updateText;
                calcAnswers[c.key] = updateText;
                automated = true;
            } else if (flags.visible && hasLegacyCalc(c)) {
                var calcValue = evalFormula(c.conditions.calc.formula, calcAnswers);
                var calcInput = primaryControl(wrap);
                if (calcInput) { calcInput.value = calcValue; }
                answers[c.key] = calcValue;
                calcAnswers[c.key] = calcValue;
                automated = true;
            }

            Array.prototype.forEach.call(controls(wrap), function (el) {
                if (!flags.visible) {
                    el.disabled = true;
                    return;
                }
                if (c.type === 'product_list') {
                    if (el.classList.contains('ak-product-check')) {
                        var item = el.closest('.ak-product-item');
                        el.disabled = !!(item && item.classList.contains('is-disabled'));
                    } else if (el.classList.contains('ak-product-qty')) {
                        var row = el.closest('.ak-product-item');
                        var check = row ? row.querySelector('.ak-product-check') : null;
                        el.disabled = !(check && check.checked && !check.disabled);
                    }
                    return;
                }
                // visible again
                el.disabled = false;
                el.required = flags.required
                    && !automated
                    && el.type !== 'hidden'
                    && c.type !== 'product_list'
                    && !(c.type === 'address' && el.name.indexOf('[street_address_2]') !== -1);
                if (flags.disabled || automated) {
                    el.readOnly = (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA');
                    if (el.tagName === 'SELECT') { el.disabled = true; }
                } else {
                    el.readOnly = false;
                }
            });
        });
    }

    form.addEventListener('input', apply);
    form.addEventListener('change', apply);
    apply();
})();
