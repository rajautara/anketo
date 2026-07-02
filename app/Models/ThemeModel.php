<?php

namespace App\Models;

use CodeIgniter\Model;

class ThemeModel extends Model
{
    protected $table = 'form_themes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'css_config',
        'preview_image',
        'is_default',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[100]',
        'css_config' => 'permit_empty',
        'preview_image' => 'permit_empty|max_length[255]',
        'is_default' => 'permit_empty|boolean',
    ];
    protected $skipValidation = false;

    /**
     * Get default theme
     */
    public function getDefaultTheme()
    {
        return $this->where('is_default', true)->first();
    }

    /**
     * Set theme as default
     */
    public function setAsDefault($themeId)
    {
        // Remove default from all themes
        $this->set(['is_default' => false])->update();

        // Set new default
        return $this->update($themeId, ['is_default' => true]);
    }

    /**
     * Get theme CSS
     */
    public function getThemeCSS($themeId)
    {
        $theme = $this->find($themeId);
        if (!$theme) {
            $theme = $this->getDefaultTheme();
        }

        $config = json_decode($theme['css_config'], true);
        
        $css = "
        :root {
            --primary-color: {$config['primary_color']};
            --background-color: {$config['background_color']};
            --font-family: {$config['font_family']};
            --border-radius: {$config['border_radius']};
            --input-padding: {$config['input_padding']};
        }
        
        .form-theme {
            font-family: var(--font-family);
            background-color: var(--background-color);
        }
        
        .form-theme .form-control,
        .form-theme .form-select {
            border-radius: var(--border-radius);
            padding: var(--input-padding);
        }
        
        .form-theme .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: var(--border-radius);
        }
        
        .form-theme .btn-primary:hover {
            background-color: {$this->darkenColor($config['primary_color'])};
            border-color: {$this->darkenColor($config['primary_color'])};
        }
        ";

        return $css;
    }

    /**
     * Helper function to darken color
     */
    private function darkenColor($hex)
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, $r - 30);
        $g = max(0, $g - 30);
        $b = max(0, $b - 30);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Get predefined themes
     */
    public static function getPredefinedThemes()
    {
        return [
            [
                'name' => 'Default Blue',
                'css_config' => [
                    'primary_color' => '#0d6efd',
                    'background_color' => '#ffffff',
                    'font_family' => 'Arial, sans-serif',
                    'border_radius' => '4px',
                    'input_padding' => '10px',
                ],
            ],
            [
                'name' => 'Modern Green',
                'css_config' => [
                    'primary_color' => '#198754',
                    'background_color' => '#f8f9fa',
                    'font_family' => 'Segoe UI, sans-serif',
                    'border_radius' => '8px',
                    'input_padding' => '12px',
                ],
            ],
            [
                'name' => 'Elegant Purple',
                'css_config' => [
                    'primary_color' => '#6f42c1',
                    'background_color' => '#ffffff',
                    'font_family' => 'Georgia, serif',
                    'border_radius' => '6px',
                    'input_padding' => '11px',
                ],
            ],
            [
                'name' => 'Dark Theme',
                'css_config' => [
                    'primary_color' => '#212529',
                    'background_color' => '#343a40',
                    'font_family' => 'Courier New, monospace',
                    'border_radius' => '4px',
                    'input_padding' => '10px',
                ],
            ],
        ];
    }
}