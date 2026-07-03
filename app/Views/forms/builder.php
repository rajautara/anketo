<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Builder: <?= esc($form['title']) ?> - Anketo<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
<link rel="stylesheet" href="<?= base_url('assets/css/builder.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/builder.css') ?>">
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
    <div class="col-12 col-lg-9 ak-builder-col">
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

</div>

<!-- Field properties modal (form is populated per field by the builder JS) -->
<div class="modal fade" id="field-modal" tabindex="-1" aria-labelledby="field-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="field-modal-title">Edit field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="properties-form"></form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-field">Save</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script src="<?= base_url('assets/js/builder-conditions.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/builder-conditions.js') ?>"></script>
<script>
(function () {
    'use strict';

    var FORM_ID = <?= (int) $form['id'] ?>;
    var API_BASE = '<?= site_url('forms/' . $form['id']) ?>';
    var PRODUCT_IMAGE_BASE = '<?= site_url('product-image/' . $form['id']) ?>';
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
        page_break:  { icon: 'bi-layout-split',     label: 'Page Break' },
        appointment: { icon: 'bi-calendar-check',  label: 'Appointment' },
        product_list: { icon: 'bi-bag',            label: 'Product List' }
    };
    var OPTION_TYPES = ['checkbox', 'radio', 'select'];
    var WEEKDAYS = [{ v: 1, t: 'Mon' }, { v: 2, t: 'Tue' }, { v: 3, t: 'Wed' }, { v: 4, t: 'Thu' }, { v: 5, t: 'Fri' }, { v: 6, t: 'Sat' }, { v: 7, t: 'Sun' }];
    var PARA_TOOLBAR = [['bold', 'italic', 'underline', 'strike'], [{ header: [2, 3, false] }], [{ list: 'ordered' }, { list: 'bullet' }], [{ align: [] }], ['link', 'image'], ['clean']];

    // Live registry of all fields on the canvas: id -> field object.
    var FIELDS = {};
    function registerField(field) { FIELDS[field.id] = field; }
    function unregisterField(id) { delete FIELDS[id]; }
    // Fields eligible as condition sources / calc tokens: exclude self, paragraphs, and calc targets.
    function otherFieldsFor(current) {
        return Object.keys(FIELDS).map(function (id) { return FIELDS[id]; }).filter(function (f) {
            return String(f.id) !== String(current.id)
                && f.field_type !== 'paragraph'
                && f.field_type !== 'page_break'
                && !(f.conditions && f.conditions.calc && f.conditions.calc.formula);
        }).map(function (f) {
            return { key: f.field_key, label: f.label, type: f.field_type, options: Array.isArray(f.options) ? f.options : [] };
        });
    }

    var canvas = document.getElementById('field-canvas');
    var emptyHint = document.getElementById('empty-canvas-hint');
    var propertiesForm = document.getElementById('properties-form');
    var fieldModalEl = document.getElementById('field-modal');
    var fieldModalDialog = fieldModalEl.querySelector('.modal-dialog');
    var fieldModalTitle = document.getElementById('field-modal-title');
    // focus:false disables Bootstrap's FocusTrap so the image size bar's percent
    // input (appended to document.body, outside the modal) stays typeable.
    var fieldModal = new bootstrap.Modal(fieldModalEl, { focus: false });
    var currentField = null;
    var currentLi = null;
    var paraQuill = null;
    var paraSizeBar = null;
    var paraSizeInput = null;
    var paraSizeImg = null;
    var paraOverlay = null;
    var paraDrag = null;
    var paraDragJustEnded = false;
    var paraRafId = null;

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

        var edit = document.createElement('button');
        edit.type = 'button';
        edit.className = 'btn btn-sm btn-outline-secondary';
        edit.innerHTML = '<i class="bi bi-pencil"></i>';
        edit.addEventListener('click', function () {
            // Resolve from the registry at click time: saves replace the object
            // in FIELDS, and the closure's `field` would go stale.
            openFieldModal(FIELDS[field.id] || field, li);
        });

        var del = document.createElement('button');
        del.type = 'button';
        del.className = 'btn btn-sm btn-outline-danger';
        del.innerHTML = '<i class="bi bi-trash"></i>';
        del.addEventListener('click', function () {
            deleteField(field.id, li);
        });

        top.appendChild(handle);
        top.appendChild(icon);
        top.appendChild(label);
        top.appendChild(edit);
        top.appendChild(del);
        li.appendChild(top);

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
        }).catch(function (err) { alert(err.message); });
    }

    // Emptying the form kills per-open listeners and keeps ids (#field-required,
    // #conditions-container, #ak-para-editor, ...) singletons across opens.
    function onModalHidden() {
        hideImageOverlay();
        paraQuill = null;
        propertiesForm.innerHTML = '';
        currentField = null;
        currentLi = null;
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

    function openFieldModal(field, li) {
        currentField = field;
        currentLi = li;
        paraQuill = null;
        hideImageOverlay();

        var meta = FIELD_META[field.field_type] || { icon: '', label: field.field_type };
        fieldModalTitle.textContent = meta.label + ' — ' + field.label;

        var type = field.field_type;
        var isOptionType = OPTION_TYPES.indexOf(type) !== -1;
        var isParagraph = type === 'paragraph';
        var isPageBreak = type === 'page_break';
        var isAppointment = type === 'appointment';
        var isProductList = type === 'product_list';
        var options = Array.isArray(field.options) ? field.options : [];
        var apptCfg = (isAppointment && field.options && !Array.isArray(field.options)) ? field.options : {};
        var productCfg = (isProductList && field.options && !Array.isArray(field.options)) ? field.options : { products: [] };

        var html = '';
        html += '<div class="mb-2"><label class="form-label small">Label</label>' +
            '<input type="text" class="form-control form-control-sm" name="label" value="' + escapeHtml(field.label) + '"></div>';

        if (isParagraph) {
            html += '<div class="mb-2"><label class="form-label small">Content</label>' +
                '<div id="ak-para-editor-wrap">' +
                '<textarea class="form-control form-control-sm" name="body" rows="4">' + escapeHtml((field.options && field.options.body) || '') + '</textarea>' +
                '</div></div>';
        } else if (isPageBreak) {
            html += '<p class="text-muted small mb-0">Starts a new page in the public form. It does not collect or store an answer.</p>';
        } else {
            html += '<div class="mb-2"><label class="form-label small">Field key</label>' +
                '<input type="text" class="form-control form-control-sm" name="field_key" value="' + escapeHtml(field.field_key) + '"></div>';

            if (!isOptionType && !isAppointment && !isProductList) {
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

            if (isProductList) {
                html += productConfigHtml(productCfg);
            }

            html += '<div class="form-check mb-3">' +
                '<input type="checkbox" class="form-check-input" id="field-required" name="is_required"' + (field.is_required ? ' checked' : '') + '>' +
                '<label class="form-check-label small" for="field-required">Required</label></div>';
        }

        if (!isPageBreak) {
            html += '<div id="conditions-container"></div>';
        }

        propertiesForm.innerHTML = html;

        if (isOptionType) {
            propertiesForm.querySelector('#add-option').addEventListener('click', function () {
                propertiesForm.querySelector('#options-list').insertAdjacentHTML('beforeend', optionRowHtml({ label: '' }));
            });
        }

        if (isProductList) {
            var addProduct = propertiesForm.querySelector('#add-product');
            if (addProduct) {
                addProduct.addEventListener('click', function () {
                    propertiesForm.querySelector('#products-list').insertAdjacentHTML('beforeend', productRowHtml(defaultProduct()));
                });
            }
        }

        var weekdaysBox = propertiesForm.querySelector('#appt-weekdays');
        if (weekdaysBox) {
            weekdaysBox.addEventListener('change', function (evt) {
                var cb = evt.target.closest('.appt-weekday');
                if (cb) { cb.closest('label').classList.toggle('active', cb.checked); }
            });
        }

        if (!isPageBreak && window.AkBuilderConditions) {
            window.AkBuilderConditions.render(propertiesForm.querySelector('#conditions-container'), field, otherFieldsFor(field));
        }

        // The paragraph Quill editor is initialised on shown.bs.modal instead of
        // here: it (and the image-overlay math) needs a laid-out, stationary DOM,
        // and the dialog carries a transform during the fade transition.
        fieldModalDialog.classList.toggle('modal-xl', isParagraph || isProductList);
        fieldModalDialog.classList.toggle('modal-lg', !(isParagraph || isProductList));
        fieldModal.show();
    }

    function initParagraphEditor() {
        if (!currentField || currentField.field_type !== 'paragraph' || !window.Quill || paraQuill) { return; }
        var wrap = propertiesForm.querySelector('#ak-para-editor-wrap');
        if (!wrap) { return; }
        var ta = wrap.querySelector('textarea[name="body"]');
        var edDiv = document.createElement('div');
        edDiv.id = 'ak-para-editor';
        wrap.insertBefore(edDiv, ta);
        ta.classList.add('d-none');
        paraQuill = new Quill(edDiv, {
            theme: 'snow',
            modules: { toolbar: { container: PARA_TOOLBAR, handlers: { image: paragraphImageHandler } } }
        });
        if (ta.value.trim() !== '') { paraQuill.clipboard.dangerouslyPasteHTML(ta.value); }
        setupParagraphImageResize();
    }

    function onModalShown() {
        initParagraphEditor();
        // Manual autofocus — the modal is created with focus:false.
        var first = propertiesForm.querySelector('[name="label"]');
        if (first) { first.focus(); }
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

    function defaultProduct() {
        return {
            id: 'product_' + Date.now().toString(36) + '_' + Math.floor(Math.random() * 10000).toString(36),
            name: 'Product Name',
            description: '',
            price: 0,
            stock: 10,
            image: ''
        };
    }

    function productConfigHtml(cfg) {
        var products = Array.isArray(cfg.products) && cfg.products.length ? cfg.products : [defaultProduct()];
        return '<div class="mb-3"><label class="form-label small d-block">Products</label>' +
            '<div id="products-list" class="ak-products-list">' + products.map(productRowHtml).join('') + '</div>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="add-product"><i class="bi bi-plus"></i> Add product</button></div>';
    }

    function productImageUrl(name) {
        return name ? PRODUCT_IMAGE_BASE + '/' + encodeURIComponent(name) : '';
    }

    function productRowHtml(product) {
        product = product || defaultProduct();
        var image = product.image || '';
        var imageUrl = productImageUrl(image);
        return '<div class="ak-product-row" data-product-id="' + escapeHtml(product.id || defaultProduct().id) + '">' +
            '<div class="ak-product-image-box">' +
                '<div class="ak-product-preview' + (imageUrl ? '' : ' is-empty') + '">' +
                    (imageUrl ? '<img src="' + escapeHtml(imageUrl) + '" alt="">' : '<i class="bi bi-image"></i>') +
                '</div>' +
                '<input type="hidden" class="product-image" value="' + escapeHtml(image) + '">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary upload-product-image"><i class="bi bi-upload"></i> Upload</button>' +
            '</div>' +
            '<div class="ak-product-fields">' +
                '<div class="row g-2">' +
                    '<div class="col-md-7"><label class="form-label small">Name</label><input type="text" class="form-control form-control-sm product-name" value="' + escapeHtml(product.name || '') + '"></div>' +
                    '<div class="col-6 col-md-2"><label class="form-label small">Price</label><input type="number" class="form-control form-control-sm product-price" min="0" step="0.01" value="' + escapeHtml(product.price == null ? 0 : product.price) + '"></div>' +
                    '<div class="col-6 col-md-3"><label class="form-label small">Stock</label><input type="number" class="form-control form-control-sm product-stock" min="0" step="1" value="' + escapeHtml(product.stock == null ? 0 : product.stock) + '"></div>' +
                    '<div class="col-12"><label class="form-label small">Description</label><textarea class="form-control form-control-sm product-description" rows="2">' + escapeHtml(product.description || '') + '</textarea></div>' +
                '</div>' +
            '</div>' +
            '<div class="ak-product-actions">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary move-product-up" title="Move up"><i class="bi bi-arrow-up"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary move-product-down" title="Move down"><i class="bi bi-arrow-down"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger remove-product" title="Remove"><i class="bi bi-trash"></i></button>' +
            '</div>' +
            '</div>';
    }

    function uploadProductImage(row) {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png,image/jpeg,image/gif,image/webp';
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { return; }

            var data = new FormData();
            data.append('image', file);

            fetch(API_BASE + '/product-image', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            }).then(function (res) {
                if (!res.ok) {
                    return res.json().catch(function () { return {}; }).then(function (b) { throw new Error(b.error || ('Upload failed (' + res.status + ')')); });
                }
                return res.json();
            }).then(function (body) {
                row.querySelector('.product-image').value = body.name || '';
                var preview = row.querySelector('.ak-product-preview');
                preview.classList.remove('is-empty');
                preview.innerHTML = '<img src="' + escapeHtml(body.url || productImageUrl(body.name || '')) + '" alt="">';
            }).catch(function (err) { alert(err.message); });
        });
        input.click();
    }

    // Quill toolbar image button: upload the picked file, insert the returned URL
    // (avoids base64-embedding, which would bloat the stored HTML).
    function paragraphImageHandler() {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png,image/jpeg,image/gif,image/webp';
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file || !paraQuill) { return; }

            var data = new FormData();
            data.append('image', file);

            fetch(API_BASE + '/paragraph-image', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            }).then(function (res) {
                if (!res.ok) {
                    return res.json().catch(function () { return {}; }).then(function (b) { throw new Error(b.error || ('Upload failed (' + res.status + ')')); });
                }
                return res.json();
            }).then(function (result) {
                var range = paraQuill.getSelection(true);
                paraQuill.insertEmbed(range ? range.index : paraQuill.getLength(), 'image', result.url, 'user');
                paraQuill.setSelection((range ? range.index : paraQuill.getLength()) + 1, 0);
            }).catch(function (err) { alert(err.message); });
        });
        input.click();
    }

    // Click an image in the paragraph editor to select it: a fixed-position
    // overlay with corner drag-handles plus a floating size bar (preset widths,
    // an exact percent input, and a reset control). Widths are stored as integer
    // percentages so they scale correctly from the narrow builder to the wide
    // public form; applied via Quill's `width` image format so getSemanticHTML
    // persists them (and CSS max-width:100% still prevents any overflow).
    function setupParagraphImageResize() {
        function onEditorPress(e) {
            if (paraDrag || paraDragJustEnded) { return; } // stray mouseup/click from a handle drag
            var img = imgFromNode(e.target);
            if (img) { selectParagraphImage(img); }
            else { hideImageOverlay(); }
        }
        paraQuill.root.addEventListener('click', onEditorPress);
        paraQuill.root.addEventListener('mouseup', onEditorPress);
        paraQuill.on('text-change', onParaTextChange);
    }

    // Editor edits can delete the selected image (Backspace, undo, `clean`)
    // or change its width format — tear down or refresh accordingly.
    function onParaTextChange() {
        if (!paraSizeImg || paraDrag) { return; }
        if (!paraQuill || !paraQuill.root.contains(paraSizeImg)) { hideImageOverlay(); return; }
        refreshSizeInput();
        requestReposition();
    }

    // Resolve the <img> from a clicked node whether it is the image itself,
    // an ancestor, or a wrapper containing it.
    function imgFromNode(node) {
        if (!node || node.nodeType !== 1) { return null; }
        if (node.tagName === 'IMG') { return node; }
        var up = node.closest ? node.closest('img') : null;
        if (up) { return up; }
        return node.querySelector ? node.querySelector('img') : null;
    }

    function selectParagraphImage(img) {
        if (img === paraSizeImg) { requestReposition(); return; }
        hideImageOverlay();
        paraSizeImg = img;

        // Put Quill's selection on the embed so Backspace/Delete removes it.
        var blot = (window.Quill && Quill.find) ? Quill.find(img) : null;
        if (blot && typeof paraQuill.getIndex === 'function') {
            paraQuill.setSelection(paraQuill.getIndex(blot), 1, 'user');
        }
        // The rect height jumps once a still-loading image gets its real size.
        if (!img.complete) { img.addEventListener('load', requestReposition, { once: true }); }

        buildImageOverlay();
        buildImageSizeBar(img);
        positionImageOverlay();
    }

    function buildImageOverlay() {
        var overlay = document.createElement('div');
        overlay.className = 'ak-img-overlay';
        ['nw', 'ne', 'sw', 'se'].forEach(function (corner) {
            var h = document.createElement('div');
            h.className = 'ak-img-handle';
            h.setAttribute('data-corner', corner);
            h.addEventListener('pointerdown', startHandleDrag);
            h.addEventListener('pointermove', onHandleMove);
            h.addEventListener('pointerup', endHandleDrag);
            h.addEventListener('pointercancel', function () { cancelDrag(); });
            overlay.appendChild(h);
        });
        document.body.appendChild(overlay);
        paraOverlay = overlay;
    }

    function buildImageSizeBar(img) {
        var bar = document.createElement('div');
        bar.className = 'ak-img-size-bar';

        var label = document.createElement('span');
        label.className = 'ak-img-size-label';
        label.textContent = 'Width';
        bar.appendChild(label);

        ['25%', '50%', '75%', '100%'].forEach(function (w) {
            var b = document.createElement('button');
            b.type = 'button';
            b.textContent = w;
            b.addEventListener('mousedown', function (ev) {
                ev.preventDefault(); // keep the editor's image selection
                commitImageWidthPercent(img, parseInt(w, 10));
            });
            bar.appendChild(b);
        });

        var input = document.createElement('input');
        input.type = 'number';
        input.className = 'ak-img-size-input';
        input.min = '1';
        input.max = '100';
        input.step = '1';
        input.addEventListener('change', function () { commitSizeInput(img, input); });
        input.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); commitSizeInput(img, input); }
        });
        bar.appendChild(input);

        var suffix = document.createElement('span');
        suffix.className = 'ak-img-size-suffix';
        suffix.textContent = '%';
        bar.appendChild(suffix);

        var reset = document.createElement('button');
        reset.type = 'button';
        reset.title = 'Original size';
        reset.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>';
        reset.addEventListener('mousedown', function (ev) {
            ev.preventDefault();
            commitImageWidthPercent(img, null);
        });
        bar.appendChild(reset);

        document.body.appendChild(bar);
        paraSizeBar = bar;
        paraSizeInput = input;
        refreshSizeInput();
    }

    function commitSizeInput(img, input) {
        var n = parseInt(input.value, 10);
        if (isNaN(n)) { refreshSizeInput(); return; }
        commitImageWidthPercent(img, Math.min(100, Math.max(1, n)));
    }

    // Show the stored percent in the input; fall back to the rendered percent
    // (as a placeholder) when no explicit width is set.
    function refreshSizeInput() {
        if (!paraSizeInput || !paraSizeImg) { return; }
        var pct = imgWidthPercent(paraSizeImg);
        if (pct !== null) {
            paraSizeInput.value = pct;
            paraSizeInput.placeholder = '';
        } else {
            paraSizeInput.value = '';
            paraSizeInput.placeholder = String(renderedPercent(paraSizeImg));
        }
    }

    function imgWidthPercent(img) {
        var m = /^(\d{1,3})%$/.exec(img.getAttribute('width') || '');
        return m ? parseInt(m[1], 10) : null;
    }

    function renderedPercent(img) {
        var parent = img.parentElement;
        var pw = parent ? parent.getBoundingClientRect().width : 0;
        if (!pw) { return 100; }
        return Math.min(100, Math.max(1, Math.round(img.getBoundingClientRect().width / pw * 100)));
    }

    // Single source of truth for overlay + bar placement (viewport coords,
    // clamped so both stay visible even when the image is taller than the
    // viewport — the anchor rect's bottom edge can be far below the fold).
    function positionImageOverlay() {
        if (!paraQuill || !paraSizeImg || !paraQuill.root.contains(paraSizeImg)) {
            hideImageOverlay();
            return;
        }
        var r = paraSizeImg.getBoundingClientRect();
        if (paraOverlay) {
            paraOverlay.style.top = r.top + 'px';
            paraOverlay.style.left = r.left + 'px';
            paraOverlay.style.width = r.width + 'px';
            paraOverlay.style.height = r.height + 'px';
        }
        if (paraSizeBar) {
            var top = Math.max(8, Math.min(r.bottom + 6, window.innerHeight - paraSizeBar.offsetHeight - 8));
            var left = Math.min(Math.max(8, r.left), window.innerWidth - paraSizeBar.offsetWidth - 8);
            paraSizeBar.style.top = top + 'px';
            paraSizeBar.style.left = left + 'px';
        }
    }

    function requestReposition() {
        if (!paraSizeImg || paraRafId !== null) { return; }
        paraRafId = requestAnimationFrame(function () {
            paraRafId = null;
            positionImageOverlay();
        });
    }

    function hideImageOverlay() {
        if (paraRafId !== null) { cancelAnimationFrame(paraRafId); paraRafId = null; }
        paraDrag = null;
        // If focus is inside the size bar, hand it back to the editor before the
        // bar is removed — otherwise focus drops to <body>, outside the modal,
        // and Bootstrap's Escape-to-close listener never hears the next keydown.
        if (paraSizeBar && paraSizeBar.contains(document.activeElement) && paraQuill) {
            paraQuill.root.focus();
        }
        if (paraSizeBar && paraSizeBar.parentNode) { paraSizeBar.parentNode.removeChild(paraSizeBar); }
        if (paraOverlay && paraOverlay.parentNode) { paraOverlay.parentNode.removeChild(paraOverlay); }
        paraSizeBar = null;
        paraSizeInput = null;
        paraOverlay = null;
        paraSizeImg = null;
    }

    function startHandleDrag(e) {
        if (!paraSizeImg) { return; }
        e.preventDefault(); // no native image drag / text selection
        var container = paraSizeImg.parentElement ? paraSizeImg.parentElement.getBoundingClientRect().width : 0;
        if (!container) { return; }
        paraDrag = {
            corner: e.target.getAttribute('data-corner'),
            handle: e.target,
            pointerId: e.pointerId,
            startX: e.clientX,
            startWidth: paraSizeImg.getBoundingClientRect().width,
            containerWidth: container,
            originalAttr: paraSizeImg.getAttribute('width'),
            lastPx: null
        };
        e.target.setPointerCapture(e.pointerId);
        // Bound undo granularity around the burst of mid-drag mutations.
        if (paraQuill && paraQuill.history) { paraQuill.history.cutoff(); }
    }

    function onHandleMove(e) {
        if (!paraDrag || !paraSizeImg) { return; }
        var dx = e.clientX - paraDrag.startX;
        var w = (paraDrag.corner === 'nw' || paraDrag.corner === 'sw')
            ? paraDrag.startWidth - dx
            : paraDrag.startWidth + dx;
        var minPx = Math.max(32, paraDrag.containerWidth * 0.05);
        w = Math.round(Math.min(paraDrag.containerWidth, Math.max(minPx, w)));
        paraDrag.lastPx = w;
        // Live feedback with a real px attribute (true reflow); the canonical
        // percent is committed through Quill on pointerup.
        paraSizeImg.setAttribute('width', w);
        if (paraSizeInput) {
            paraSizeInput.value = Math.min(100, Math.max(1, Math.round(w / paraDrag.containerWidth * 100)));
        }
        requestReposition();
    }

    function endHandleDrag(e) {
        if (!paraDrag) { return; }
        var drag = paraDrag;
        paraDrag = null;
        flagDragJustEnded();
        if (drag.handle.hasPointerCapture && drag.handle.hasPointerCapture(e.pointerId)) {
            drag.handle.releasePointerCapture(e.pointerId);
        }
        if (drag.lastPx === null || !paraSizeImg) { requestReposition(); return; }
        commitImageWidthPercent(paraSizeImg, Math.min(100, Math.max(1, Math.round(drag.lastPx / drag.containerWidth * 100))));
    }

    // Compatibility mouseup/click events trail a pointer drag; briefly flag the
    // end of a drag so they cannot dismiss or re-select through onEditorPress.
    function flagDragJustEnded() {
        paraDragJustEnded = true;
        setTimeout(function () { paraDragJustEnded = false; }, 0);
    }

    function cancelDrag() {
        if (!paraDrag) { return; }
        var drag = paraDrag;
        paraDrag = null;
        flagDragJustEnded();
        if (drag.handle.hasPointerCapture && drag.handle.hasPointerCapture(drag.pointerId)) {
            drag.handle.releasePointerCapture(drag.pointerId);
        }
        if (paraSizeImg) {
            if (drag.originalAttr === null) { paraSizeImg.removeAttribute('width'); }
            else { paraSizeImg.setAttribute('width', drag.originalAttr); }
        }
        refreshSizeInput();
        requestReposition();
    }

    // All size changes funnel through here so the Quill Delta records them.
    // pct is an integer 1-100, or null to reset to natural size.
    function commitImageWidthPercent(img, pct) {
        applyImageWidth(img, pct === null ? false : pct + '%');
        if (paraQuill && paraQuill.history) { paraQuill.history.cutoff(); }
        refreshSizeInput();
        requestReposition();
    }

    function applyImageWidth(img, width) {
        if (!paraQuill) { return; }
        var blot = (window.Quill && Quill.find) ? Quill.find(img) : null;
        if (blot && typeof paraQuill.getIndex === 'function') {
            paraQuill.formatText(paraQuill.getIndex(blot), 1, 'width', width, 'user');
        } else if (width === false) {
            img.removeAttribute('width');
        } else {
            img.setAttribute('width', width);
        }
    }

    // getSemanticHTML round-trips image width attributes today, but that is a
    // Quill internal detail — re-assert the live editor's widths onto the
    // serialized markup so a Quill change can't silently drop sizes.
    function mergeImgWidths(html) {
        var liveImgs = paraQuill.root.querySelectorAll('img');
        if (liveImgs.length === 0) { return html; }
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var outImgs = doc.body.querySelectorAll('img');
        if (outImgs.length !== liveImgs.length) { return html; }
        for (var i = 0; i < outImgs.length; i++) {
            var w = liveImgs[i].getAttribute('width');
            if (w === null) { outImgs[i].removeAttribute('width'); }
            else { outImgs[i].setAttribute('width', w); }
        }
        return doc.body.innerHTML;
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
                payload.body = paraQuill.getLength() > 1 ? mergeImgWidths(paraQuill.getSemanticHTML()) : '';
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

        if (field.field_type === 'product_list') {
            var productRows = Array.prototype.slice.call(propertiesForm.querySelectorAll('.ak-product-row'));
            payload.options = {
                products: productRows.map(function (row) {
                    return {
                        id: row.getAttribute('data-product-id') || defaultProduct().id,
                        name: (row.querySelector('.product-name') || {}).value || '',
                        description: (row.querySelector('.product-description') || {}).value || '',
                        price: parseFloat((row.querySelector('.product-price') || {}).value || '0') || 0,
                        stock: parseInt((row.querySelector('.product-stock') || {}).value || '0', 10) || 0,
                        image: (row.querySelector('.product-image') || {}).value || ''
                    };
                })
            };
            if (payload.options.products.length === 0) {
                alert('Add at least one product.');
                return;
            }
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
            fieldModal.hide();
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
                openFieldModal(field, row);
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

    // Field modal wiring (bound once; the form body is rebuilt per open).
    fieldModalEl.addEventListener('shown.bs.modal', onModalShown);
    fieldModalEl.addEventListener('hidden.bs.modal', onModalHidden);
    document.getElementById('save-field').addEventListener('click', function () {
        if (currentField && currentLi) { saveField(currentField, currentLi); }
    });
    // No submit button exists, but a form with a single text input still
    // implicitly submits on Enter — which would reload the page.
    propertiesForm.addEventListener('submit', function (e) { e.preventDefault(); });
    propertiesForm.addEventListener('click', function (evt) {
        if (evt.target.closest('.remove-option')) {
            evt.target.closest('.option-row').remove();
        }
        var productRow = evt.target.closest('.ak-product-row');
        if (evt.target.closest('.remove-product') && productRow) {
            productRow.remove();
        }
        if (evt.target.closest('.move-product-up') && productRow && productRow.previousElementSibling) {
            productRow.parentNode.insertBefore(productRow, productRow.previousElementSibling);
        }
        if (evt.target.closest('.move-product-down') && productRow && productRow.nextElementSibling) {
            productRow.parentNode.insertBefore(productRow.nextElementSibling, productRow);
        }
        if (evt.target.closest('.upload-product-image') && productRow) {
            uploadProductImage(productRow);
        }
    });

    // Image overlay/bar: dismiss on outside pointerdown, reposition (never
    // hide) on scroll/resize (capture also catches .modal-body scrolls).
    document.addEventListener('pointerdown', function (e) {
        if (!paraSizeBar && !paraOverlay) { return; }
        if (paraSizeBar && paraSizeBar.contains(e.target)) { return; }
        if (paraOverlay && paraOverlay.contains(e.target)) { return; }
        if (paraQuill && paraQuill.root.contains(e.target) && imgFromNode(e.target)) { return; }
        hideImageOverlay();
    }, true);
    window.addEventListener('scroll', function () { requestReposition(); }, true);
    window.addEventListener('resize', function () { requestReposition(); });
    // Capture phase so an Escape that dismisses the image overlay (or cancels a
    // drag) is consumed before Bootstrap's bubble listener closes the modal.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        if (paraDrag) { cancelDrag(); }
        else if (paraSizeImg) { hideImageOverlay(); }
        else { return; }
        e.stopPropagation();
        e.preventDefault();
    }, true);

    renderInitial();
})();
</script>
<?= $this->endSection() ?>
