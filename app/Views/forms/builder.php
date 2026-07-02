<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    .builder-container {
        display: flex;
        height: calc(100vh - 150px);
    }
    .field-palette {
        width: 250px;
        background: #f8f9fa;
        border-right: 1px solid #dee2e6;
        overflow-y: auto;
        padding: 15px;
    }
    .field-palette h6 {
        font-weight: 600;
        margin-bottom: 15px;
    }
    .field-item {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
        cursor: grab;
        transition: all 0.2s;
    }
    .field-item:hover {
        border-color: #0d6efd;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .field-item i {
        margin-right: 8px;
        color: #0d6efd;
    }
    .form-canvas {
        flex: 1;
        padding: 30px;
        overflow-y: auto;
        background: #f0f2f5;
    }
    .form-canvas-inner {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        padding: 30px;
        min-height: 400px;
    }
    .form-field {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        cursor: move;
        position: relative;
    }
    .form-field:hover {
        border-color: #0d6efd;
    }
    .form-field.sortable-ghost {
        opacity: 0.4;
        background: #e9ecef;
    }
    .form-field.sortable-drag {
        cursor: grabbing;
    }
    .field-actions {
        position: absolute;
        right: 10px;
        top: 10px;
        display: none;
    }
    .form-field:hover .field-actions {
        display: flex;
    }
    .field-actions .btn {
        padding: 4px 8px;
        font-size: 0.75rem;
    }
    .field-preview {
        pointer-events: none;
        opacity: 0.6;
    }
    .field-properties {
        width: 300px;
        background: white;
        border-left: 1px solid #dee2e6;
        overflow-y: auto;
        padding: 15px;
    }
    .field-properties h6 {
        font-weight: 600;
        margin-bottom: 15px;
    }
    .empty-canvas {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    .empty-canvas i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.3;
    }
    .toolbar {
        background: white;
        border-bottom: 1px solid #dee2e6;
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .form-title-input {
        font-size: 1.5rem;
        font-weight: 600;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 5px 0;
        width: 400px;
    }
    .form-title-input:focus {
        outline: none;
        border-bottom-color: #0d6efd;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="toolbar">
    <div>
        <a href="<?= base_url('/forms') ?>" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
        <input type="text" class="form-title-input" id="formTitle" value="<?= esc($form['title']) ?>" placeholder="Form Title">
    </div>
    <div>
        <a href="<?= base_url('/forms/' . $form['id'] . '/preview') ?>" class="btn btn-outline-primary me-2" target="_blank">
            <i class="fas fa-eye me-1"></i>Preview
        </a>
        <button class="btn btn-success" id="publishBtn">
            <i class="fas fa-paper-plane me-1"></i>Publish
        </button>
    </div>
</div>

<div class="builder-container">
    <!-- Field Palette -->
    <div class="field-palette">
        <h6><i class="fas fa-shapes me-2"></i>Field Types</h6>
        <?php foreach ($fieldTypes as $type => $config): ?>
        <?php if ($type !== 'paragraph' && $type !== 'divider'): ?>
        <div class="field-item" draggable="true" data-field-type="<?= $type ?>">
            <i class="fas <?= $config['icon'] ?>"></i>
            <?= $config['name'] ?>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        
        <h6 class="mt-4"><i class="fas fa-palette me-2"></i>Layout</h6>
        <?php foreach ($fieldTypes as $type => $config): ?>
        <?php if ($type === 'paragraph' || $type === 'divider'): ?>
        <div class="field-item" draggable="true" data-field-type="<?= $type ?>">
            <i class="fas <?= $config['icon'] ?>"></i>
            <?= $config['name'] ?>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Form Canvas -->
    <div class="form-canvas">
        <div class="form-canvas-inner" id="formCanvas">
            <?php if (empty($fields)): ?>
            <div class="empty-canvas">
                <i class="fas fa-mouse-pointer"></i>
                <h5>Drag & Drop Fields Here</h5>
                <p>Select field types from the left panel and drag them here to build your form</p>
            </div>
            <?php else: ?>
            <?php foreach ($fields as $field): ?>
            <div class="form-field" data-field-id="<?= $field['id'] ?>">
                <div class="field-actions">
                    <button class="btn btn-outline-primary btn-sm edit-field" data-field-id="<?= $field['id'] ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm delete-field" data-field-id="<?= $field['id'] ?>">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="field-preview">
                    <label class="form-label fw-bold">
                        <?= esc($field['label']) ?>
                        <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                    </label>
                    <?php if ($field['field_type'] === 'text' || $field['field_type'] === 'email' || $field['field_type'] === 'number'): ?>
                    <input type="<?= $field['field_type'] ?>" class="form-control" placeholder="<?= esc($field['placeholder']) ?>" disabled>
                    <?php elseif ($field['field_type'] === 'textarea'): ?>
                    <textarea class="form-control" rows="3" placeholder="<?= esc($field['placeholder']) ?>" disabled></textarea>
                    <?php elseif ($field['field_type'] === 'select'): ?>
                    <select class="form-select" disabled>
                        <option>Select an option</option>
                    </select>
                    <?php elseif ($field['field_type'] === 'checkbox'): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" disabled>
                        <label class="form-check-label">Checkbox option</label>
                    </div>
                    <?php elseif ($field['field_type'] === 'radio'): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="preview" disabled>
                        <label class="form-check-label">Radio option</label>
                    </div>
                    <?php elseif ($field['field_type'] === 'file'): ?>
                    <input type="file" class="form-control" disabled>
                    <?php elseif ($field['field_type'] === 'date'): ?>
                    <input type="date" class="form-control" disabled>
                    <?php elseif ($field['field_type'] === 'time'): ?>
                    <input type="time" class="form-control" disabled>
                    <?php elseif ($field['field_type'] === 'datetime'): ?>
                    <input type="datetime-local" class="form-control" disabled>
                    <?php elseif ($field['field_type'] === 'password'): ?>
                    <input type="password" class="form-control" placeholder="Password" disabled>
                    <?php elseif ($field['field_type'] === 'paragraph'): ?>
                    <p class="mb-0"><?= esc($field['label']) ?></p>
                    <?php elseif ($field['field_type'] === 'divider'): ?>
                    <hr>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Field Properties Panel -->
    <div class="field-properties" id="fieldProperties">
        <h6><i class="fas fa-cog me-2"></i>Field Properties</h6>
        <div id="propertiesContent">
            <p class="text-muted">Select a field to edit its properties</p>
        </div>
    </div>
</div>

<!-- Field Properties Modal Template -->
<template id="fieldPropertiesTemplate">
    <form id="fieldPropertiesForm">
        <input type="hidden" id="fieldId" name="field_id">
        
        <div class="mb-3">
            <label class="form-label">Label</label>
            <input type="text" class="form-control" id="fieldLabel" name="label">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Field Name</label>
            <input type="text" class="form-control" id="fieldName" name="name" readonly>
            <small class="text-muted">Auto-generated from label</small>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Placeholder</label>
            <input type="text" class="form-control" id="fieldPlaceholder" name="placeholder">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Default Value</label>
            <input type="text" class="form-control" id="fieldDefaultValue" name="default_value">
        </div>
        
        <div class="mb-3 form-check">
            <input class="form-check-input" type="checkbox" id="fieldRequired" name="required">
            <label class="form-check-label" for="fieldRequired">Required Field</label>
        </div>
        
        <div class="mb-3" id="optionsContainer" style="display: none;">
            <label class="form-label">Options (one per line)</label>
            <textarea class="form-control" id="fieldOptions" name="options" rows="5"></textarea>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Width (%)</label>
            <input type="range" class="form-range" id="fieldWidth" name="width" min="25" max="100" step="25">
            <div class="d-flex justify-content-between">
                <small>25%</small>
                <small>50%</small>
                <small>75%</small>
                <small>100%</small>
            </div>
        </div>
        
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</template>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const formId = <?= $form['id'] ?>;
const fieldTypes = <?= json_encode($fieldTypes) ?>;

// Initialize Sortable
const canvas = document.getElementById('formCanvas');
new Sortable(canvas, {
    animation: 150,
    ghostClass: 'sortable-ghost',
    dragClass: 'sortable-drag',
    handle: '.form-field',
    onEnd: function(evt) {
        const fieldIds = [];
        canvas.querySelectorAll('.form-field').forEach(field => {
            fieldIds.push(field.dataset.fieldId);
        });
        
        if (fieldIds.length > 0) {
            fetch('/api/fields/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ field_ids: fieldIds })
            });
        }
    }
});

// Drag and drop from palette
document.querySelectorAll('.field-palette .field-item').forEach(item => {
    item.addEventListener('dragstart', function(e) {
        e.dataTransfer.setData('fieldType', this.dataset.fieldType);
    });
});

canvas.addEventListener('dragover', function(e) {
    e.preventDefault();
});

canvas.addEventListener('drop', function(e) {
    e.preventDefault();
    const fieldType = e.dataTransfer.getData('fieldType');
    
    if (fieldType) {
        addField(fieldType);
    }
});

// Add new field
function addField(fieldType) {
    const fieldConfig = fieldTypes[fieldType];
    const label = prompt('Enter field label:', fieldConfig.name);
    
    if (!label) return;
    
    fetch('/api/forms/' + formId + '/fields', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            field_type: fieldType,
            label: label,
            name: label.toLowerCase().replace(/[^a-z0-9]/g, '_'),
            required: false,
            width: 100
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error adding field: ' + data.message);
        }
    });
}

// Edit field
document.querySelectorAll('.edit-field').forEach(btn => {
    btn.addEventListener('click', function() {
        const fieldId = this.dataset.fieldId;
        showFieldProperties(fieldId);
    });
});

// Delete field
document.querySelectorAll('.delete-field').forEach(btn => {
    btn.addEventListener('click', function() {
        const fieldId = this.dataset.fieldId;
        
        if (confirm('Are you sure you want to delete this field?')) {
            fetch('/api/fields/' + fieldId, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting field: ' + data.message);
                }
            });
        }
    });
});

// Show field properties
function showFieldProperties(fieldId) {
    const template = document.getElementById('fieldPropertiesTemplate');
    const content = document.getElementById('propertiesContent');
    content.innerHTML = template.innerHTML;
    
    // Load field data
    fetch('/api/fields/' + fieldId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const field = data.data;
            document.getElementById('fieldId').value = field.id;
            document.getElementById('fieldLabel').value = field.label;
            document.getElementById('fieldName').value = field.name;
            document.getElementById('fieldPlaceholder').value = field.placeholder || '';
            document.getElementById('fieldDefaultValue').value = field.default_value || '';
            document.getElementById('fieldRequired').checked = field.required;
            document.getElementById('fieldWidth').value = field.width;
            
            // Show options for select, radio, checkbox
            if (['select', 'radio', 'checkbox'].includes(field.field_type)) {
                document.getElementById('optionsContainer').style.display = 'block';
                const options = field.options ? JSON.parse(field.options) : [];
                document.getElementById('fieldOptions').value = options.join('\n');
            }
        }
    });
}

// Save field properties
document.addEventListener('submit', function(e) {
    if (e.target.id === 'fieldPropertiesForm') {
        e.preventDefault();
        
        const fieldId = document.getElementById('fieldId').value;
        const optionsText = document.getElementById('fieldOptions').value;
        const options = optionsText ? optionsText.split('\n').filter(o => o.trim()) : [];
        
        const data = {
            label: document.getElementById('fieldLabel').value,
            placeholder: document.getElementById('fieldPlaceholder').value,
            default_value: document.getElementById('fieldDefaultValue').value,
            required: document.getElementById('fieldRequired').checked,
            width: document.getElementById('fieldWidth').value,
        };
        
        if (options.length > 0) {
            data.options = options;
        }
        
        fetch('/api/fields/' + fieldId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error saving field: ' + data.message);
            }
        });
    }
});

// Publish form
document.getElementById('publishBtn').addEventListener('click', function() {
    fetch('/forms/' + formId + '/publish', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Form published successfully!');
        } else {
            alert('Error publishing form: ' + data.message);
        }
    });
});

// Update form title
document.getElementById('formTitle').addEventListener('blur', function() {
    const title = this.value;
    
    fetch('/api/forms/' + formId, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ title: title })
    });
});
</script>
<?= $this->endSection() ?>