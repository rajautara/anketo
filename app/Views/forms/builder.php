<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Builder: <?= esc($form['title']) ?> - Anketo<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<style>
    .field-palette-item { cursor: grab; }
    .field-palette-item:active { cursor: grabbing; }
    #field-canvas { min-height: 200px; }
    #field-canvas .field-row { cursor: default; background: #fff; }
    #field-canvas .field-row.selected { border-color: #0d6efd !important; box-shadow: 0 0 0 .15rem rgba(13,110,253,.25); }
    #field-canvas .drag-handle { cursor: grab; }
    .sortable-ghost { opacity: .4; }
    #properties-panel-empty { color: #6c757d; }
</style>
<?= $this->endSection() ?>

<?= $this->section('main') ?>

<?= $this->include('partials/flash') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <a href="<?= site_url('dashboard') ?>" class="text-decoration-none small d-block mb-1"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <h1 class="h4 mb-0"><?= esc($form['title']) ?></h1>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="<?= site_url('forms/' . $form['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm">Form settings</a>
        <a href="<?= site_url('forms/' . $form['id'] . '/submissions') ?>" class="btn btn-outline-secondary btn-sm">Submissions</a>
        <?php if ($form['status'] === 'published') : ?>
            <form action="<?= site_url('forms/' . $form['id'] . '/unpublish') ?>" method="post" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-warning btn-sm">Unpublish</button>
            </form>
        <?php else : ?>
            <form action="<?= site_url('forms/' . $form['id'] . '/publish') ?>" method="post" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success btn-sm">Publish</button>
            </form>
        <?php endif ?>
    </div>
</div>

<?php if ($form['status'] === 'published') : ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center gap-2">
        <div class="text-truncate">
            <i class="bi bi-link-45deg"></i>
            Public link: <a href="<?= $publicUrl ?>" target="_blank" id="public-url-link"><?= $publicUrl ?></a>
        </div>
        <button type="button" class="btn btn-sm btn-success flex-shrink-0" id="copy-public-url">Copy link</button>
    </div>
<?php endif ?>

<div class="row g-3">
    <!-- Palette -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Add a field</div>
            <div class="card-body">
                <div id="field-palette" class="d-flex flex-column gap-2">
                    <?php foreach ($fieldTypes as $type) : ?>
                        <div class="field-palette-item border rounded p-2 d-flex align-items-center gap-2" data-field-type="<?= esc($type) ?>">
                            <i class="bi <?= esc(field_type_icon($type)) ?>"></i>
                            <span><?= esc(field_type_label($type)) ?></span>
                        </div>
                    <?php endforeach ?>
                </div>
                <p class="text-muted small mt-3 mb-0">Drag a field type onto the form to add it.</p>
            </div>
        </div>
    </div>

    <!-- Canvas -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Form fields</div>
            <div class="card-body">
                <ul id="field-canvas" class="list-unstyled d-flex flex-column gap-2 mb-0"></ul>
                <p id="empty-canvas-hint" class="text-muted text-center py-4 mb-0">Drop a field here to get started.</p>
            </div>
        </div>
    </div>

    <!-- Properties -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Properties</div>
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
        file:     { icon: 'bi-paperclip',          label: 'File Upload' }
    };
    var OPTION_TYPES = ['checkbox', 'radio', 'select'];

    var canvas = document.getElementById('field-canvas');
    var emptyHint = document.getElementById('empty-canvas-hint');
    var propertiesEmpty = document.getElementById('properties-panel-empty');
    var propertiesForm = document.getElementById('properties-form');
    var selectedId = null;

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
            updateEmptyHint();
            if (selectedId === fieldId) { clearProperties(); }
        }).catch(function (err) { alert(err.message); });
    }

    function clearProperties() {
        selectedId = null;
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

        var isOptionType = OPTION_TYPES.indexOf(field.field_type) !== -1;
        var options = Array.isArray(field.options) ? field.options : [];

        var html = '';
        html += '<div class="mb-2"><label class="form-label small">Label</label>' +
            '<input type="text" class="form-control form-control-sm" name="label" value="' + escapeHtml(field.label) + '"></div>';
        html += '<div class="mb-2"><label class="form-label small">Field key</label>' +
            '<input type="text" class="form-control form-control-sm" name="field_key" value="' + escapeHtml(field.field_key) + '"></div>';

        if (field.field_type !== 'checkbox' && field.field_type !== 'radio') {
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

        html += '<div class="form-check mb-3">' +
            '<input type="checkbox" class="form-check-input" id="field-required" name="is_required"' + (field.is_required ? ' checked' : '') + '>' +
            '<label class="form-check-label small" for="field-required">Required</label></div>';

        html += '<button type="button" class="btn btn-primary btn-sm w-100" id="save-field">Save</button>';

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

        propertiesForm.querySelector('#save-field').addEventListener('click', function () {
            saveField(field.id, li);
        });
    }

    function saveField(fieldId, li) {
        var payload = {
            label: propertiesForm.querySelector('[name="label"]').value.trim(),
            field_key: propertiesForm.querySelector('[name="field_key"]').value.trim(),
            help_text: propertiesForm.querySelector('[name="help_text"]').value.trim(),
            is_required: propertiesForm.querySelector('[name="is_required"]').checked
        };

        var placeholderInput = propertiesForm.querySelector('[name="placeholder"]');
        if (placeholderInput) { payload.placeholder = placeholderInput.value.trim(); }

        var optionsList = propertiesForm.querySelector('#options-list');
        if (optionsList) {
            var labels = Array.prototype.slice.call(optionsList.querySelectorAll('.option-label'));
            payload.options = labels.map(function (input, idx) {
                return { value: 'option_' + (idx + 1), label: input.value.trim() };
            }).filter(function (opt) { return opt.label !== ''; });
        }

        if (payload.label === '') { alert('Label is required.'); return; }

        apiFetch(API_BASE + '/fields/' + fieldId, {
            method: 'PUT',
            body: JSON.stringify(payload)
        }).then(function (field) {
            li.querySelector('.field-row-label').textContent = field.label + (field.is_required ? ' *' : '');
            li.dataset.type = field.field_type;
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
