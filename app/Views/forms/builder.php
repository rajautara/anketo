<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Builder: <?= esc($form['title']) ?> - Anketo<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
<link rel="stylesheet" href="<?= base_url('assets/css/builder.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('main') ?>

<?= $this->include('partials/flash') ?>

<div class="ak-page-header">
    <div>
        <a href="<?= site_url('dashboard') ?>" class="ak-back-link"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h4 mb-0"><?= esc($form['title']) ?></h1>
            <?php if ($form['status'] === 'published') : ?>
                <span class="ak-pill ak-pill-success">Published</span>
            <?php else : ?>
                <span class="ak-pill ak-pill-warning">Draft</span>
            <?php endif ?>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="<?= site_url('forms/' . $form['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear me-1"></i> Settings</a>
        <a href="<?= site_url('forms/' . $form['id'] . '/submissions') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-inbox me-1"></i> Submissions</a>
        <?php if ($form['status'] === 'published') : ?>
            <form action="<?= site_url('forms/' . $form['id'] . '/unpublish') ?>" method="post" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-warning btn-sm">Unpublish</button>
            </form>
        <?php else : ?>
            <form action="<?= site_url('forms/' . $form['id'] . '/publish') ?>" method="post" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-globe me-1"></i> Publish</button>
            </form>
        <?php endif ?>
    </div>
</div>

<?php if ($form['status'] === 'published') : ?>
    <div class="ak-share-banner mb-3">
        <div class="ak-share-url">
            <i class="bi bi-link-45deg fs-5 flex-shrink-0"></i>
            <a href="<?= $publicUrl ?>" target="_blank" id="public-url-link" class="text-truncate"><?= $publicUrl ?></a>
        </div>
        <button type="button" class="btn btn-sm btn-primary flex-shrink-0" id="copy-public-url"><i class="bi bi-clipboard me-1"></i> Copy link</button>
    </div>
<?php endif ?>

<div class="row g-3">
    <!-- Palette -->
    <div class="col-12 col-lg-3 ak-builder-col">
        <div class="card">
            <div class="card-header">Add a field</div>
            <div class="card-body">
                <div id="field-palette">
                    <?php foreach ($fieldTypes as $type) : ?>
                        <div class="field-palette-item" data-field-type="<?= esc($type) ?>">
                            <i class="bi <?= esc(field_type_icon($type)) ?>"></i>
                            <span><?= esc(field_type_label($type)) ?></span>
                        </div>
                    <?php endforeach ?>
                </div>
                <p class="text-muted small mt-3 mb-0"><i class="bi bi-hand-index-thumb me-1"></i> Drag a field onto the form.</p>
            </div>
        </div>
    </div>

    <!-- Canvas -->
    <div class="col-12 col-lg-6 ak-builder-col">
        <div class="card">
            <div class="card-header">Form fields</div>
            <div class="card-body">
                <ul id="field-canvas" class="list-unstyled d-flex flex-column gap-2 mb-0"></ul>
                <p id="empty-canvas-hint" class="ak-canvas-hint text-center py-5 mb-0">
                    <i class="bi bi-arrow-down-square d-block fs-3 mb-2 opacity-50"></i>
                    Drop a field here to get started.
                </p>
            </div>
        </div>
    </div>

    <!-- Properties -->
    <div class="col-12 col-lg-3 ak-builder-col">
        <div class="card">
            <div class="card-header">Properties</div>
            <div class="card-body">
                <p id="properties-panel-empty">Select a field to edit its properties.</p>
                <form id="properties-form" class="d-none"></form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script src="<?= base_url('assets/js/builder-conditions.js') ?>"></script>
<script>
(function () {
    'use strict';

    var FORM_ID = <?= (int) $form['id'] ?>;
    var API_BASE = '<?= site_url('forms/' . $form['id']) ?>';
    var CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
    var INITIAL_FIELDS = <?= json_encode($fields) ?>;

    var FIELD_META = {
        text:     { icon: 'bi-input-cursor-text', label: 'Text' },
        email:    { icon: 'bi-envelope',           label: 'Email' },
        number:   { icon: 'bi-123',                label: 'Number' },
        textarea: { icon: 'bi-text-paragraph',     label: 'Long Answer' },
        checkbox: { icon: 'bi-check2-square',      label: 'Checkboxes' },
        radio:    { icon: 'bi-ui-radios',          label: 'Multiple Choice' },
        select:   { icon: 'bi-menu-button-wide',   label: 'Dropdown' },
        date:     { icon: 'bi-calendar-date',      label: 'Date' },
        file:     { icon: 'bi-paperclip',          label: 'File Upload' },
        paragraph:   { icon: 'bi-text-left',       label: 'Paragraph' },
        appointment: { icon: 'bi-calendar-check',  label: 'Appointment' }
    };
    var OPTION_TYPES = ['checkbox', 'radio', 'select'];
    var WEEKDAYS = [{ v: 1, t: 'Mon' }, { v: 2, t: 'Tue' }, { v: 3, t: 'Wed' }, { v: 4, t: 'Thu' }, { v: 5, t: 'Fri' }, { v: 6, t: 'Sat' }, { v: 7, t: 'Sun' }];
    var PARA_TOOLBAR = [['bold', 'italic', 'underline', 'strike'], [{ header: [2, 3, false] }], [{ list: 'ordered' }, { list: 'bullet' }], [{ align: [] }], ['link'], ['clean']];

    // Live registry of all fields on the canvas: id -> field object.
    var FIELDS = {};
    function registerField(field) { FIELDS[field.id] = field; }
    function unregisterField(id) { delete FIELDS[id]; }
    // Fields eligible as condition sources / calc tokens: exclude self, paragraphs, and calc targets.
    function otherFieldsFor(current) {
        return Object.keys(FIELDS).map(function (id) { return FIELDS[id]; }).filter(function (f) {
            return String(f.id) !== String(current.id)
                && f.field_type !== 'paragraph'
                && !(f.conditions && f.conditions.calc && f.conditions.calc.formula);
        }).map(function (f) {
            return { key: f.field_key, label: f.label, type: f.field_type, options: Array.isArray(f.options) ? f.options : [] };
        });
    }

    var canvas = document.getElementById('field-canvas');
    var emptyHint = document.getElementById('empty-canvas-hint');
    var propertiesEmpty = document.getElementById('properties-panel-empty');
    var propertiesForm = document.getElementById('properties-form');
    var selectedId = null;
    var paraQuill = null;

    function apiFetch(url, options) {
        options = options || {};
        options.headers = Object.assign({
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF_TOKEN
        }, options.headers || {});
        return fetch(url, options).then(function (res) {
            if (!res.ok) {
                return res.json().catch(function () { return {}; }).then(function (body) {
                    throw new Error(body.error || ('Request failed (' + res.status + ')'));
                });
            }
            return res.json();
        });
    }

    function updateEmptyHint() {
        emptyHint.classList.toggle('d-none', canvas.children.length > 0);
    }

    function buildRow(field) {
        var meta = FIELD_META[field.field_type] || { icon: 'bi-input-cursor-text', label: field.field_type };

        var li = document.createElement('li');
        li.className = 'field-row border rounded p-2';
        li.dataset.id = field.id;
        li.dataset.type = field.field_type;

        var top = document.createElement('div');
        top.className = 'd-flex align-items-center gap-2';

        var handle = document.createElement('span');
        handle.className = 'drag-handle text-muted';
        handle.innerHTML = '<i class="bi bi-grip-vertical"></i>';

        var icon = document.createElement('i');
        icon.className = 'bi ' + meta.icon;

        var label = document.createElement('span');
        label.className = 'field-row-label flex-grow-1 text-truncate';
        label.textContent = field.label + (field.is_required ? ' *' : '');

        var del = document.createElement('button');
        del.type = 'button';
        del.className = 'btn btn-sm btn-outline-danger';
        del.innerHTML = '<i class="bi bi-trash"></i>';
        del.addEventListener('click', function (evt) {
            evt.stopPropagation();
            deleteField(field.id, li);
        });

        top.appendChild(handle);
        top.appendChild(icon);
        top.appendChild(label);
        top.appendChild(del);
        li.appendChild(top);

        li.addEventListener('click', function () { selectField(li, field); });

        return li;
    }

    function renderInitial() {
        INITIAL_FIELDS.forEach(function (field) {
            registerField(field);
            canvas.appendChild(buildRow(field));
        });
        updateEmptyHint();
    }

    function currentOrder() {
        return Array.prototype.slice.call(canvas.children).map(function (li) { return parseInt(li.dataset.id, 10); });
    }

    function syncOrder() {
        var order = currentOrder();
        if (order.length === 0) { return; }
        apiFetch(API_BASE + '/fields/reorder', {
            method: 'POST',
            body: JSON.stringify({ order: order })
        }).catch(function (err) { alert(err.message); });
    }

    function deleteField(fieldId, li) {
        if (!confirm('Remove this field?')) { return; }
        apiFetch(API_BASE + '/fields/' + fieldId, { method: 'DELETE' }).then(function () {
            li.remove();
            unregisterField(fieldId);
            updateEmptyHint();
            if (selectedId === fieldId) { clearProperties(); }
        }).catch(function (err) { alert(err.message); });
    }

    function clearProperties() {
        selectedId = null;
        paraQuill = null;
        propertiesForm.classList.add('d-none');
        propertiesForm.innerHTML = '';
        propertiesEmpty.classList.remove('d-none');
        Array.prototype.forEach.call(canvas.querySelectorAll('.field-row.selected'), function (el) {
            el.classList.remove('selected');
        });
    }

    function optionRowHtml(opt) {
        return '<div class="input-group input-group-sm mb-1 option-row">' +
            '<input type="text" class="form-control option-label" value="' + escapeHtml(opt.label) + '" placeholder="Option label">' +
            '<button type="button" class="btn btn-outline-danger remove-option"><i class="bi bi-x"></i></button>' +
            '</div>';
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function selectField(li, field) {
        selectedId = field.id;
        propertiesEmpty.classList.add('d-none');
        propertiesForm.classList.remove('d-none');

        Array.prototype.forEach.call(canvas.querySelectorAll('.field-row.selected'), function (el) {
            el.classList.remove('selected');
        });
        li.classList.add('selected');

        paraQuill = null;
        var type = field.field_type;
        var isOptionType = OPTION_TYPES.indexOf(type) !== -1;
        var isParagraph = type === 'paragraph';
        var isAppointment = type === 'appointment';
        var options = Array.isArray(field.options) ? field.options : [];
        var apptCfg = (isAppointment && field.options && !Array.isArray(field.options)) ? field.options : {};

        var html = '';
        html += '<div class="mb-2"><label class="form-label small">Label</label>' +
            '<input type="text" class="form-control form-control-sm" name="label" value="' + escapeHtml(field.label) + '"></div>';

        if (isParagraph) {
            html += '<div class="mb-2"><label class="form-label small">Content</label>' +
                '<div id="ak-para-editor-wrap">' +
                '<textarea class="form-control form-control-sm" name="body" rows="4">' + escapeHtml((field.options && field.options.body) || '') + '</textarea>' +
                '</div></div>';
        } else {
            html += '<div class="mb-2"><label class="form-label small">Field key</label>' +
                '<input type="text" class="form-control form-control-sm" name="field_key" value="' + escapeHtml(field.field_key) + '"></div>';

            if (!isOptionType && !isAppointment) {
                html += '<div class="mb-2"><label class="form-label small">Placeholder</label>' +
                    '<input type="text" class="form-control form-control-sm" name="placeholder" value="' + escapeHtml(field.placeholder) + '"></div>';
            }

            html += '<div class="mb-2"><label class="form-label small">Help text</label>' +
                '<input type="text" class="form-control form-control-sm" name="help_text" value="' + escapeHtml(field.help_text) + '"></div>';

            if (isOptionType) {
                html += '<div class="mb-2"><label class="form-label small d-block">Options</label>' +
                    '<div id="options-list">' + options.map(optionRowHtml).join('') + '</div>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="add-option"><i class="bi bi-plus"></i> Add option</button></div>';
            }

            if (isAppointment) {
                html += appointmentConfigHtml(apptCfg);
            }

            html += '<div class="form-check mb-3">' +
                '<input type="checkbox" class="form-check-input" id="field-required" name="is_required"' + (field.is_required ? ' checked' : '') + '>' +
                '<label class="form-check-label small" for="field-required">Required</label></div>';
        }

        html += '<div id="conditions-container"></div>';
        html += '<button type="button" class="btn btn-primary btn-sm w-100 mt-2" id="save-field">Save</button>';

        propertiesForm.innerHTML = html;

        if (isOptionType) {
            propertiesForm.querySelector('#add-option').addEventListener('click', function () {
                propertiesForm.querySelector('#options-list').insertAdjacentHTML('beforeend', optionRowHtml({ label: '' }));
            });
            propertiesForm.addEventListener('click', function (evt) {
                if (evt.target.closest('.remove-option')) {
                    evt.target.closest('.option-row').remove();
                }
            });
        }

        if (isParagraph && window.Quill) {
            var wrap = propertiesForm.querySelector('#ak-para-editor-wrap');
            var ta = wrap.querySelector('textarea[name="body"]');
            var edDiv = document.createElement('div');
            edDiv.id = 'ak-para-editor';
            wrap.insertBefore(edDiv, ta);
            ta.classList.add('d-none');
            paraQuill = new Quill(edDiv, { theme: 'snow', modules: { toolbar: PARA_TOOLBAR } });
            if (ta.value.trim() !== '') { paraQuill.clipboard.dangerouslyPasteHTML(ta.value); }
        }

        var weekdaysBox = propertiesForm.querySelector('#appt-weekdays');
        if (weekdaysBox) {
            weekdaysBox.addEventListener('change', function (evt) {
                var cb = evt.target.closest('.appt-weekday');
                if (cb) { cb.closest('label').classList.toggle('active', cb.checked); }
            });
        }

        if (window.AkBuilderConditions) {
            window.AkBuilderConditions.render(propertiesForm.querySelector('#conditions-container'), field, otherFieldsFor(field));
        }

        propertiesForm.querySelector('#save-field').addEventListener('click', function () {
            saveField(field, li);
        });
    }

    function appointmentConfigHtml(cfg) {
        var days = Array.isArray(cfg.weekdays) ? cfg.weekdays.map(Number) : [1, 2, 3, 4, 5];
        var boxes = WEEKDAYS.map(function (d) {
            return '<label class="btn btn-sm btn-outline-secondary' + (days.indexOf(d.v) !== -1 ? ' active' : '') + '">' +
                '<input type="checkbox" class="d-none appt-weekday" value="' + d.v + '"' + (days.indexOf(d.v) !== -1 ? ' checked' : '') + '> ' + d.t + '</label>';
        }).join('');
        return '<div class="mb-2"><label class="form-label small d-block">Available days</label>' +
            '<div class="d-flex flex-wrap gap-1" id="appt-weekdays">' + boxes + '</div></div>' +
            '<div class="row g-2 mb-2">' +
            '<div class="col-6"><label class="form-label small">Start</label><input type="time" class="form-control form-control-sm" name="appt_start" value="' + escapeHtml(cfg.start_time || '09:00') + '"></div>' +
            '<div class="col-6"><label class="form-label small">End</label><input type="time" class="form-control form-control-sm" name="appt_end" value="' + escapeHtml(cfg.end_time || '17:00') + '"></div>' +
            '<div class="col-6"><label class="form-label small">Slot (min)</label><input type="number" class="form-control form-control-sm" name="appt_slot" min="5" max="480" value="' + (parseInt(cfg.slot_minutes, 10) || 30) + '"></div>' +
            '<div class="col-6"><label class="form-label small">Book within (days)</label><input type="number" class="form-control form-control-sm" name="appt_maxdays" min="1" max="365" value="' + (parseInt(cfg.date_max_days, 10) || 60) + '"></div>' +
            '</div>';
    }

    function saveField(field, li) {
        var fieldId = field.id;
        var payload = { label: propertiesForm.querySelector('[name="label"]').value.trim() };

        var keyInput = propertiesForm.querySelector('[name="field_key"]');
        if (keyInput) { payload.field_key = keyInput.value.trim(); }

        var helpInput = propertiesForm.querySelector('[name="help_text"]');
        if (helpInput) { payload.help_text = helpInput.value.trim(); }

        var requiredInput = propertiesForm.querySelector('[name="is_required"]');
        if (requiredInput) { payload.is_required = requiredInput.checked; }

        var placeholderInput = propertiesForm.querySelector('[name="placeholder"]');
        if (placeholderInput) { payload.placeholder = placeholderInput.value.trim(); }

        var bodyInput = propertiesForm.querySelector('[name="body"]');
        if (bodyInput) {
            // Quill's semantic HTML (clean ul/ol + text-align styles) when the
            // editor is active, else the raw textarea (Quill-unavailable fallback).
            if (paraQuill) {
                payload.body = paraQuill.getLength() > 1 ? paraQuill.getSemanticHTML() : '';
            } else {
                payload.body = bodyInput.value;
            }
        }

        var optionsList = propertiesForm.querySelector('#options-list');
        if (optionsList) {
            var labels = Array.prototype.slice.call(optionsList.querySelectorAll('.option-label'));
            payload.options = labels.map(function (input, idx) {
                return { value: 'option_' + (idx + 1), label: input.value.trim() };
            }).filter(function (opt) { return opt.label !== ''; });
        }

        if (field.field_type === 'appointment') {
            var weekdays = Array.prototype.map.call(propertiesForm.querySelectorAll('.appt-weekday:checked'), function (cb) { return parseInt(cb.value, 10); });
            payload.options = {
                weekdays: weekdays,
                start_time: (propertiesForm.querySelector('[name="appt_start"]') || {}).value || '09:00',
                end_time: (propertiesForm.querySelector('[name="appt_end"]') || {}).value || '17:00',
                slot_minutes: parseInt((propertiesForm.querySelector('[name="appt_slot"]') || {}).value, 10) || 30,
                date_max_days: parseInt((propertiesForm.querySelector('[name="appt_maxdays"]') || {}).value, 10) || 60
            };
        }

        var condContainer = propertiesForm.querySelector('#conditions-container');
        if (window.AkBuilderConditions && condContainer) {
            payload.conditions = window.AkBuilderConditions.collect(condContainer);
        }

        if (payload.label === '') { alert('Label is required.'); return; }

        apiFetch(API_BASE + '/fields/' + fieldId, {
            method: 'PUT',
            body: JSON.stringify(payload)
        }).then(function (updated) {
            registerField(updated);
            li.querySelector('.field-row-label').textContent = updated.label + (updated.is_required ? ' *' : '');
            li.dataset.type = updated.field_type;
        }).catch(function (err) { alert(err.message); });
    }

    // Palette -> clone-only source
    Sortable.create(document.getElementById('field-palette'), {
        group: { name: 'builder', pull: 'clone', put: false },
        sort: false,
        animation: 150
    });

    // Canvas -> sortable target
    Sortable.create(canvas, {
        group: { name: 'builder', pull: false, put: true },
        animation: 150,
        handle: '.drag-handle',
        onAdd: function (evt) {
            var fieldType = evt.item.dataset.fieldType;
            var index = evt.newIndex;
            evt.item.remove();

            apiFetch(API_BASE + '/fields', {
                method: 'POST',
                body: JSON.stringify({ field_type: fieldType })
            }).then(function (field) {
                registerField(field);
                var row = buildRow(field);
                var ref = canvas.children[index] || null;
                canvas.insertBefore(row, ref);
                updateEmptyHint();
                syncOrder();
                selectField(row, field);
            }).catch(function (err) { alert(err.message); });
        },
        onUpdate: function () { syncOrder(); }
    });

    var copyBtn = document.getElementById('copy-public-url');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(document.getElementById('public-url-link').href).then(function () {
                copyBtn.textContent = 'Copied!';
                setTimeout(function () { copyBtn.textContent = 'Copy link'; }, 1500);
            });
        });
    }

    renderInitial();
})();
</script>
<?= $this->endSection() ?>
