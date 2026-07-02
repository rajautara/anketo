<?php

if (! function_exists('field_type_label')) {
    function field_type_label(string $type): string
    {
        $labels = [
            'text'     => 'Text',
            'email'    => 'Email',
            'number'   => 'Number',
            'textarea' => 'Long Answer',
            'checkbox' => 'Checkboxes',
            'radio'    => 'Multiple Choice',
            'select'   => 'Dropdown',
            'date'     => 'Date',
            'file'     => 'File Upload',
        ];

        return $labels[$type] ?? ucfirst($type);
    }
}

if (! function_exists('field_type_icon')) {
    function field_type_icon(string $type): string
    {
        $icons = [
            'text'     => 'bi-input-cursor-text',
            'email'    => 'bi-envelope',
            'number'   => 'bi-123',
            'textarea' => 'bi-text-paragraph',
            'checkbox' => 'bi-check2-square',
            'radio'    => 'bi-ui-radios',
            'select'   => 'bi-menu-button-wide',
            'date'     => 'bi-calendar-date',
            'file'     => 'bi-paperclip',
        ];

        return $icons[$type] ?? 'bi-input-cursor-text';
    }
}
