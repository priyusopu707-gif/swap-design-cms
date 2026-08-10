<?php
/**
 * Swap Design - Theme Engine
 *
 * Generates dynamic CSS from theme settings stored in the database.
 * Reads color, typography, spacing, and layout values from site_settings
 * and outputs a valid CSS file with :root custom properties.
 *
 * Usage:
 *   $theme = new ThemeEngine();
 *   $theme->generate();  // writes to /assets/css/theme-generated.css
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class ThemeEngine
{
    private SettingsManager $settings;
    private string $outputPath;

    /**
     * Default theme values (fallback if no DB settings exist).
     */
    private array $defaults = [
        'theme.color_primary'         => '#0a0a0a',
        'theme.color_primary_light'   => '#2a2a2a',
        'theme.color_accent'          => '#ff4d2e',
        'theme.color_accent_hover'    => '#e6391a',
        'theme.color_bg'              => '#ffffff',
        'theme.color_bg_alt'          => '#f6f6f6',
        'theme.color_bg_dark'         => '#0a0a0a',
        'theme.color_text'            => '#1a1a1a',
        'theme.color_text_light'      => '#666666',
        'theme.color_text_on_dark'    => '#f0f0f0',
        'theme.color_border'          => '#e0e0e0',
        'theme.color_border_light'    => '#f0f0f0',
        'theme.font_primary'          => "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
        'theme.font_heading'          => "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
        'theme.font_size_base'        => '1rem',
        'theme.line_height'           => '1.6',
        'theme.line_heading'          => '1.15',
        'theme.spacing_unit'          => '4',
        'theme.radius_default'        => '8px',
        'theme.container_max'         => '1200px',
        'theme.container_narrow'      => '800px',
        'theme.transition_base'       => '300ms ease',
        'theme.transition_fast'       => '150ms ease',
        'theme.shadow_default'        => '0 4px 12px rgba(0, 0, 0, 0.08)',
    ];

    public function __construct(?string $outputPath = null)
    {
        $this->settings   = new SettingsManager();
        $this->outputPath = $outputPath ?? (ROOT_PATH . '/assets/css/theme-generated.css');
    }

    /**
     * Generate the theme CSS file from current settings.
     *
     * @return bool True on success
     */
    public function generate(): bool
    {
        $values = $this->getThemeValues();
        $css    = $this->buildCSS($values);

        $dir = dirname($this->outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return @file_put_contents($this->outputPath, $css, LOCK_EX) !== false;
    }

    /**
     * Get the generated CSS as a string (for inline delivery).
     */
    public function getCSS(): string
    {
        $values = $this->getThemeValues();
        return $this->buildCSS($values);
    }

    /**
     * Get all theme values, merging DB settings over defaults.
     */
    public function getThemeValues(): array
    {
        $dbValues = $this->settings->getByPrefix('theme.');
        return array_merge($this->defaults, $dbValues);
    }

    /**
     * Get all theme settings grouped for the admin form.
     */
    public function getThemeGroups(): array
    {
        return [
            'colors' => [
                'label'  => 'Colors',
                'fields' => [
                    'theme.color_primary'        => ['label' => 'Primary Color',       'type' => 'color', 'default' => '#0a0a0a'],
                    'theme.color_primary_light'  => ['label' => 'Primary Light',       'type' => 'color', 'default' => '#2a2a2a'],
                    'theme.color_accent'         => ['label' => 'Accent Color',        'type' => 'color', 'default' => '#ff4d2e'],
                    'theme.color_accent_hover'   => ['label' => 'Accent Hover',        'type' => 'color', 'default' => '#e6391a'],
                    'theme.color_bg'             => ['label' => 'Background',          'type' => 'color', 'default' => '#ffffff'],
                    'theme.color_bg_alt'         => ['label' => 'Alt Background',      'type' => 'color', 'default' => '#f6f6f6'],
                    'theme.color_bg_dark'        => ['label' => 'Dark Background',     'type' => 'color', 'default' => '#0a0a0a'],
                    'theme.color_text'           => ['label' => 'Text Color',          'type' => 'color', 'default' => '#1a1a1a'],
                    'theme.color_text_light'     => ['label' => 'Text Light',          'type' => 'color', 'default' => '#666666'],
                    'theme.color_text_on_dark'   => ['label' => 'Text on Dark',        'type' => 'color', 'default' => '#f0f0f0'],
                    'theme.color_border'         => ['label' => 'Border Color',        'type' => 'color', 'default' => '#e0e0e0'],
                    'theme.color_border_light'   => ['label' => 'Border Light',        'type' => 'color', 'default' => '#f0f0f0'],
                ],
            ],
            'typography' => [
                'label'  => 'Typography',
                'fields' => [
                    'theme.font_primary'         => ['label' => 'Primary Font',        'type' => 'text',  'default' => 'Inter, sans-serif'],
                    'theme.font_heading'         => ['label' => 'Heading Font',        'type' => 'text',  'default' => 'Inter, sans-serif'],
                    'theme.font_size_base'       => ['label' => 'Base Font Size',      'type' => 'text',  'default' => '1rem'],
                    'theme.line_height'          => ['label' => 'Line Height',         'type' => 'text',  'default' => '1.6'],
                    'theme.line_heading'         => ['label' => 'Heading Line Height', 'type' => 'text',  'default' => '1.15'],
                ],
            ],
            'layout' => [
                'label'  => 'Layout',
                'fields' => [
                    'theme.container_max'        => ['label' => 'Container Max Width', 'type' => 'text',  'default' => '1200px'],
                    'theme.container_narrow'     => ['label' => 'Narrow Container',    'type' => 'text',  'default' => '800px'],
                    'theme.radius_default'       => ['label' => 'Border Radius',       'type' => 'text',  'default' => '8px'],
                    'theme.transition_base'      => ['label' => 'Base Transition',     'type' => 'text',  'default' => '300ms ease'],
                    'theme.transition_fast'      => ['label' => 'Fast Transition',     'type' => 'text',  'default' => '150ms ease'],
                    'theme.shadow_default'       => ['label' => 'Default Shadow',      'type' => 'text',  'default' => '0 4px 12px rgba(0,0,0,0.08)'],
                ],
            ],
        ];
    }

    /**
     * Build CSS string from theme values.
     */
    private function buildCSS(array $values): string
    {
        $css  = "/* Swap Design - Generated Theme CSS */\n";
        $css .= "/* Auto-generated: " . date('Y-m-d H:i:s') . " */\n";
        $css .= ":root {\n";

        $map = [
            'theme.color_primary'        => '--color-primary',
            'theme.color_primary_light'  => '--color-primary-light',
            'theme.color_accent'         => '--color-accent',
            'theme.color_accent_hover'   => '--color-accent-hover',
            'theme.color_bg'             => '--color-bg',
            'theme.color_bg_alt'         => '--color-bg-alt',
            'theme.color_bg_dark'        => '--color-bg-dark',
            'theme.color_text'           => '--color-text',
            'theme.color_text_light'     => '--color-text-light',
            'theme.color_text_on_dark'   => '--color-text-on-dark',
            'theme.color_border'         => '--color-border',
            'theme.color_border_light'   => '--color-border-light',
            'theme.font_primary'         => '--font-primary',
            'theme.font_heading'         => '--font-heading',
            'theme.font_size_base'       => '--fs-base',
            'theme.line_height'          => '--line-height',
            'theme.line_heading'         => '--line-heading',
            'theme.container_max'        => '--container-max',
            'theme.container_narrow'     => '--container-narrow',
            'theme.radius_default'       => '--radius-md',
            'theme.transition_base'      => '--transition-base',
            'theme.transition_fast'      => '--transition-fast',
            'theme.shadow_default'       => '--shadow-md',
        ];

        foreach ($map as $key => $prop) {
            $value = $values[$key] ?? $this->defaults[$key] ?? '';
            $css .= "    {$prop}: {$value};\n";
        }

        $css .= "}\n";
        return $css;
    }
}
