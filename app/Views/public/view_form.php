<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($form['title']) ?> - AnkeTo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .form-container {
            max-width: 700px;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .form-header {
            padding: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        .form-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .form-header p {
            color: #6c757d;
            margin-bottom: 0;
        }
        .form-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        .required-mark {
            color: #dc3545;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
        .form-check-input {
            width: 1.25em;
            height: 1.25em;
        }
        .btn-submit {
            padding: 12px 30px;
            font-size: 1rem;
            border-radius: 8px;
        }
        .field-paragraph {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .field-divider {
            border: none;
            border-top: 2px solid #dee2e6;
            margin: 30px 0;
        }
        .is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            display: block;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .loading-overlay.show {
            display: flex;
        }
    </style>
    <?= $themeCSS ?>
</head>
<body>
    <div class="form-container form-theme">
        <div class="form-header">
            <h1><?= esc($form['title']) ?></h1>
            <?php if (!empty($form['description'])): ?>
            <p><?= esc($form['description']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-body">
            <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success" role="alert">
                <?= session()->getFlashdata('success') ?>
            </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger" role="alert">
                <?= session()->getFlashdata('error') ?>
            </div>
            <?php endif; ?>

            <form id="publicForm" method="post" action="<?= base_url('/f/' . $form['id'] . '/submit') ?>">
                <?= csrf_field() ?>

                <?php foreach ($form['fields'] as $field): ?>
                <?php if ($field['field_type'] === 'paragraph'): ?>
                <div class="field-paragraph">
                    <?= nl2br(esc($field['label'])) ?>
                </div>
                <?php elseif ($field['field_type'] === 'divider'): ?>
                <hr class="field-divider">
                <?php else: ?>
                <div class="form-group" data-field-id="<?= $field['id'] ?>">
                    <label for="<?= esc($field['name']) ?>" class="form-label">
                        <?= esc($field['label']) ?>
                        <?php if ($field['required']): ?><span class="required-mark">*</span><?php endif; ?>
                    </label>

                    <?php if ($field['field_type'] === 'text'): ?>
                    <input type="text" 
                           class="form-control" 
                           id="<?= esc($field['name']) ?>" 
                           name="<?= esc($field['name']) ?>" 
                           placeholder="<?= esc($field['placeholder'] ?? '') ?>"
                           <?= $field['required'] ? 'required' : '' ?>>
                    
                    <?php elseif ($field['field_type'] === 'email'): ?>
                    <input type="email" 
                           class="form-control" 
                           id="<?= esc($field['name']) ?>" 
                           name="<?= esc($field['name']) ?>" 
                           placeholder="<?= esc($field['placeholder'] ?? '') ?>"
                           <?= $field['required'] ? 'required' : '' ?>>
                    
                    <?php elseif ($field['field_type'] === 'number'): ?>
                    <input type="number" 
                           class="form-control" 
                           id="<?= esc($field['name']) ?>" 
                           name="<?= esc($field['name']) ?>" 
                           placeholder="<?= esc($field['placeholder'] ?? '') ?>"
                           <?= $field['required'] ? 'required' : '' ?>>
                    
                    <?php elseif ($field['field_type'] === 'textarea'): ?>
                    <textarea class="form-control" 
                              id="<?= esc($field['name']) ?>" 
                              name="<?= esc($field['name']) ?>" 
                              rows="4"
                              placeholder="<?= esc($field['placeholder'] ?? '') ?>"
                              <?= $field['required'] ? 'required' : '' ?>><?= esc($field['default_value'] ?? '') ?></textarea>
                    
                    <?php elseif ($field['field_type'] === 'select'): ?>
                    <select class="form-select" 
                            id="<?= esc($field['name']) ?>" 
                            name="<?= esc($field['name']) ?>"
                            <?= $field['required'] ? 'required' : '' ?>>
                        <option value="">Select an option</option>
                        <?php 
                        $options = json_decode($field['options'], true);
                        if ($options): 
                            foreach ($options as $option): 
                        ?>
                        <option value="<?= esc($option) ?>"><?= esc($option) ?></option>
                        <?php 
                            endforeach; 
                        endif; 
                        ?>
                    </select>
                    
                    <?php elseif ($field['field_type'] === 'radio'): ?>
                    <?php 
                    $options = json_decode($field['options'], true);
                    if ($options): 
                        foreach ($options as $index => $option): 
                    ?>
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="radio" 
                               name="<?= esc($field['name']) ?>" 
                               id="<?= esc($field['name']) ?>_<?= $index ?>" 
                               value="<?= esc($option) ?>"
                               <?= $field['required'] && $index === 0 ? 'required' : '' ?>>
                        <label class="form-check-label" for="<?= esc($field['name']) ?>_<?= $index ?>">
                            <?= esc($option) ?>
                        </label>
                    </div>
                    <?php 
                        endforeach; 
                    endif; 
                    ?>
                    
                    <?php elseif ($field['field_type'] === 'checkbox'): ?>
                    <?php 
                    $options = json_decode($field['options'], true);
                    if ($options): 
                        foreach ($options as $index => $option): 
                    ?>
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               name="<?= esc($field['name']) ?>[]" 
                               id="<?= esc($field['name']) ?>_<?= $index ?>" 
                               value="<?= esc($option) ?>"
                               <?= $field['required'] && $index === 0 ? 'required' : '' ?>>
                        <label class="form-check-label" for="<?= esc($field['name']) ?>_<?= $index ?>">
                            <?= esc($option) ?>
                        </label>
                    </div>
                    <?php 
                        endforeach; 
                    endif; 
                    ?>
                    
                    <?php elseif ($field['field_type'] === 'file'): ?>
                    <input type="file" 
                           class="form-control" 
                           id="<?= esc($field['name']) ?>" 
                           name="<?= esc($field['name']) ?>"
                           <?= $field['required'] ? 'required' : '' ?>>
                    
                    <?php elseif ($field['field_type'] === 'date'): ?>
                    <input type="date" 
                           class="form-control" 
                           id="<?= esc($field['name']) ?>" 
                           name="<?= esc($field['name']) ?>"
                           <?= $field['required'] ? 'required' : '' ?>>
                    
                    <?php elseif ($field['field_type'] === 'time'): ?>
                    <input type="time" 
                           class="form-control" 
                           id="<?= esc($field['name']) ?>" 
                           name="<?= esc($field['name']) ?>"
                           <?= $field['required'] ? 'required' : '' ?>>
                    
                    <?php elseif ($field['field_type'] === 'datetime'): ?>
                    <input type="datetime-local" 
                           class="form-control" 
                           id="<?= esc($field['name']) ?>" 
                           name="<?= esc($field['name']) ?>"
                           <?= $field['required'] ? 'required' : '' ?>>
                    
                    <?php elseif ($field['field_type'] === 'password'): ?>
                    <input type="password" 
                           class="form-control" 
                           id="<?= esc($field['name']) ?>" 
                           name="<?= esc($field['name']) ?>" 
                           placeholder="<?= esc($field['placeholder'] ?? '') ?>"
                           <?= $field['required'] ? 'required' : '' ?>>
                    
                    <?php elseif ($field['field_type'] === 'hidden'): ?>
                    <input type="hidden" 
                           id="<?= esc($field['name']) ?>" 
                           name="<?= esc($field['name']) ?>" 
                           value="<?= esc($field['default_value'] ?? '') ?>">
                    
                    <?php endif; ?>
                    
                    <div class="invalid-feedback"></div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-paper-plane me-2"></i>Submit Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center text-white">
            <div class="spinner-border mb-3" role="status"></div>
            <p>Submitting your form...</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#publicForm').on('submit', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').empty();
            
            // Show loading
            $('#loadingOverlay').addClass('show');
            
            // Prepare form data
            const formData = new FormData(this);
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#loadingOverlay').removeClass('show');
                    
                    if (response.success) {
                        // Show success message
                        $('.form-body').html(`
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                                <h2>Thank You!</h2>
                                <p class="text-muted">Your form has been submitted successfully.</p>
                            </div>
                        `);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    $('#loadingOverlay').removeClass('show');
                    
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        for (const field in errors) {
                            const input = $(`[name="${field}"]`);
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(errors[field]);
                        }
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                }
            });
        });
    });
    </script>
</body>
</html>