/**
 * AnkeTo - Main Application JavaScript
 */

$(document).ready(function () {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Confirm delete actions
    $('.delete-btn').on('click', function (e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var message = $(this).data('confirm') || 'Are you sure you want to delete this item?';

        if (confirm(message)) {
            form.submit();
        }
    });

    // AJAX form submissions
    $('.ajax-form').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var method = form.attr('method') || 'POST';
        var data = new FormData(form[0]);

        // Show loading
        form.find('.btn[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');

        $.ajax({
            url: url,
            type: method,
            data: data,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    // Show success message
                    if (response.message) {
                        showAlert('success', response.message);
                    }

                    // Redirect if specified
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if (form.data('reload')) {
                        location.reload();
                    } else if (form.data('callback')) {
                        window[form.data('callback')](response);
                    }
                } else {
                    showAlert('danger', response.message || 'An error occurred');
                }
            },
            error: function (xhr) {
                var message = 'An error occurred';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).join('<br>');
                }

                showAlert('danger', message);
            },
            complete: function () {
                // Reset button
                form.find('.btn[type="submit"]').prop('disabled', false).html(form.data('original-text') || 'Submit');
            }
        });
    });

    // Store original button text
    $('.ajax-form').on('click', '.btn[type="submit"]', function () {
        $(this).data('original-text', $(this).html());
    });

    // Show alert function
    function showAlert(type, message) {
        var alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Insert alert at the top of the main content
        $('.main-content').prepend(alertHtml);

        // Auto-hide after 5 seconds
        setTimeout(function () {
            $('.alert').fadeOut('slow');
        }, 5000);
    }

    // Copy to clipboard
    $('.copy-btn').on('click', function () {
        var text = $(this).data('copy');

        navigator.clipboard.writeText(text).then(function () {
            showAlert('success', 'Copied to clipboard!');
        }).catch(function () {
            showAlert('danger', 'Failed to copy to clipboard');
        });
    });

    // Toggle password visibility
    $('.toggle-password').on('click', function () {
        var input = $(this).siblings('input');
        var icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Dynamic form fields
    $('.add-field-btn').on('click', function () {
        var container = $(this).data('container');
        var template = $(this).data('template');
        var index = $(container).children().length;

        var newField = template.replace(/\{index\}/g, index);
        $(container).append(newField);
    });

    // Remove dynamic field
    $(document).on('click', '.remove-field-btn', function () {
        $(this).closest('.dynamic-field').remove();
    });

    // Character counter
    $('.char-counter').on('input', function () {
        var maxLength = $(this).data('max-length');
        var currentLength = $(this).val().length;
        var counter = $(this).siblings('.char-count');

        counter.text(`${currentLength}/${maxLength}`);

        if (currentLength > maxLength) {
            counter.addClass('text-danger');
        } else {
            counter.removeClass('text-danger');
        }
    });

    // Initialize character counters
    $('.char-counter').trigger('input');

    // Select all checkbox
    $('.select-all').on('change', function () {
        var target = $(this).data('target');
        $(target).prop('checked', this.checked);
    });

    // Update select all checkbox
    $('.checkbox-item').on('change', function () {
        var target = $(this).data('target');
        var allChecked = $(target).filter(':checked').length === $(target).length;
        $(target).closest('.table').find('.select-all').prop('checked', allChecked);
    });

    // Bulk actions
    $('.bulk-action-btn').on('click', function () {
        var action = $(this).data('action');
        var target = $(this).data('target');
        var selected = $(target).filter(':checked').map(function () {
            return $(this).val();
        }).get();

        if (selected.length === 0) {
            showAlert('warning', 'Please select at least one item');
            return;
        }

        if (confirm(`Are you sure you want to ${action} ${selected.length} item(s)?`)) {
            // Perform bulk action
            $.ajax({
                url: $(this).data('url'),
                type: 'POST',
                data: {
                    ids: selected,
                    action: action
                },
                success: function (response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        location.reload();
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function () {
                    showAlert('danger', 'An error occurred');
                }
            });
        }
    });

    // Search filter
    $('.search-input').on('keyup', function () {
        var value = $(this).val().toLowerCase();
        var target = $(this).data('target');

        $(target).filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Status toggle
    $('.status-toggle').on('change', function () {
        var url = $(this).data('url');
        var status = this.checked ? 'active' : 'inactive';

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                status: status
            },
            success: function (response) {
                if (response.success) {
                    showAlert('success', 'Status updated successfully');
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function () {
                showAlert('danger', 'An error occurred');
            }
        });
    });

    // Image preview
    $('.image-preview-input').on('change', function () {
        var input = this;
        var preview = $(this).siblings('.image-preview');

        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                preview.attr('src', e.target.result);
                preview.show();
            }

            reader.readAsDataURL(input.files[0]);
        }
    });

    // Date range picker
    if ($.fn.daterangepicker) {
        $('.date-range').daterangepicker({
            opens: 'left',
            locale: {
                format: 'YYYY-MM-DD'
            }
        });
    }

    // Data table
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            pageLength: 25,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search..."
            }
        });
    }
});

// Global functions
window.anketo = {
    /**
     * Show a toast notification
     */
    toast: function (message, type = 'info') {
        var colors = {
            success: '#198754',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#0dcaf0'
        };

        var toast = `
            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                <div class="toast show" role="alert">
                    <div class="toast-header">
                        <span class="badge me-2" style="background-color: ${colors[type]}">${type}</span>
                        <strong class="me-auto">Notification</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            </div>
        `;

        $('body').append(toast);

        setTimeout(function () {
            $('.toast-container').remove();
        }, 3000);
    },

    /**
     * Confirm action
     */
    confirm: function (message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    /**
     * Format date
     */
    formatDate: function (date, format = 'YYYY-MM-DD HH:mm:ss') {
        var d = new Date(date);
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        var hours = String(d.getHours()).padStart(2, '0');
        var minutes = String(d.getMinutes()).padStart(2, '0');
        var seconds = String(d.getSeconds()).padStart(2, '0');

        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day)
            .replace('HH', hours)
            .replace('mm', minutes)
            .replace('ss', seconds);
    },

    /**
     * Debounce function
     */
    debounce: function (func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};