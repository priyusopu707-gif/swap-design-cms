<?php
/**
 * Swap Design - WhatsApp Manager
 *
 * Centralized WhatsApp integration: floating button, CTA buttons,
 * page-level overrides, analytics tracking, business hours.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class WhatsAppManager
{
    private Database $db;
    private SettingsManager $settings;

    /** Default global settings */
    private const DEFAULTS = [
        'whatsapp.enabled'              => '0',
        'whatsapp.phone_number'         => '',
        'whatsapp.default_message'      => 'Hello! I would like to know more about your services.',
        'whatsapp.button_position'      => 'right',
        'whatsapp.button_style'         => 'icon_text',
        'whatsapp.business_hours_start' => '09:00',
        'whatsapp.business_hours_end'   => '18:00',
        'whatsapp.show_online_status'   => '1',
        'whatsapp.show_on_mobile'       => '1',
        'whatsapp.show_on_desktop'      => '1',
    ];

    public function __construct()
    {
        $this->db       = Database::getInstance();
        $this->settings = new SettingsManager();
    }

    /* ================================================================
       Global Settings
       ================================================================ */

    /**
     * Get all WhatsApp settings as an associative array.
     */
    public function getSettings(): array
    {
        $data = [];
        foreach (self::DEFAULTS as $key => $default) {
            $data[str_replace('whatsapp.', '', $key)] = $this->settings->get($key, $default);
        }

        /* Normalize booleans */
        $data['enabled']           = (int)$data['enabled'];
        $data['show_online_status'] = (int)$data['show_online_status'];
        $data['show_on_mobile']     = (int)$data['show_on_mobile'];
        $data['show_on_desktop']    = (int)$data['show_on_desktop'];

        return $data;
    }

    /**
     * Save WhatsApp settings from a POST array.
     */
    public function saveSettings(array $data): void
    {
        $keys = array_keys(self::DEFAULTS);
        foreach ($keys as $key) {
            $shortKey = str_replace('whatsapp.', '', $key);
            $value = $data[$shortKey] ?? null;

            if ($value !== null) {
                /* Validate phone number */
                if ($shortKey === 'phone_number') {
                    $value = $this->normalizePhoneNumber($value);
                }
                $this->settings->set($key, sanitizeString($value));
            }
        }
    }

    /**
     * Check if WhatsApp is globally enabled.
     */
    public function isEnabled(): bool
    {
        return (int)$this->settings->get('whatsapp.enabled', '0') === 1;
    }

    /**
     * Check if business is currently within business hours.
     */
    public function isBusinessHours(): bool
    {
        $start = $this->settings->get('whatsapp.business_hours_start', '09:00');
        $end   = $this->settings->get('whatsapp.business_hours_end', '18:00');
        $now   = (int)date('Hi');

        $startInt = (int)str_replace(':', '', $start);
        $endInt   = (int)str_replace(':', '', $end);

        return $now >= $startInt && $now <= $endInt;
    }

    /* ================================================================
       Page Overrides
       ================================================================ */

    /**
     * Get WhatsApp override for a specific page.
     */
    public function getPageOverride(int $pageId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM whatsapp_page_overrides WHERE page_id = ?",
            [$pageId]
        );
    }

    /**
     * Save or update a page override.
     */
    public function savePageOverride(int $pageId, array $data): void
    {
        $existing = $this->getPageOverride($pageId);

        $row = [
            'page_id'           => $pageId,
            'is_enabled'        => isset($data['is_enabled']) ? 1 : 0,
            'custom_number'     => !empty($data['custom_number']) ? $this->normalizePhoneNumber($data['custom_number']) : null,
            'custom_message'    => !empty($data['custom_message']) ? sanitizeString($data['custom_message']) : null,
            'position_override' => sanitizeString($data['position_override'] ?? 'global'),
        ];

        if ($existing) {
            unset($row['page_id']);
            $this->db->update('whatsapp_page_overrides', $row, 'page_id = ?', [$pageId]);
        } else {
            $this->db->insert('whatsapp_page_overrides', $row);
        }
    }

    /**
     * Get resolved settings for a page (global + override merged).
     */
    public function getResolvedSettings(?int $pageId = null): array
    {
        $global = $this->getSettings();

        if ($pageId === null) {
            return $global;
        }

        $override = $this->getPageOverride($pageId);

        if (!$override || !$override['is_enabled']) {
            $global['enabled'] = $override ? (int)$override['is_enabled'] : $global['enabled'];
            return $global;
        }

        if ($override['custom_number']) {
            $global['phone_number'] = $override['custom_number'];
        }
        if ($override['custom_message']) {
            $global['default_message'] = $override['custom_message'];
        }
        if ($override['position_override'] !== 'global') {
            $global['button_position'] = $override['position_override'];
        }

        $global['enabled'] = (int)$override['is_enabled'];

        return $global;
    }

    /* ================================================================
       WhatsApp URL Builder
       ================================================================ */

    /**
     * Build a WhatsApp deep link URL.
     */
    public function buildUrl(string $phoneNumber, string $message = '', array $placeholders = []): string
    {
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
        $message     = $this->replacePlaceholders($message, $placeholders);

        return 'https://wa.me/' . $phoneNumber . '?text=' . urlencode($message);
    }

    /**
     * Replace {placeholders} in a message string.
     */
    public function replacePlaceholders(string $message, array $data = []): string
    {
        $replacements = [
            '{page_title}'      => $data['page_title'] ?? '',
            '{service_name}'    => $data['service_name'] ?? '',
            '{portfolio_title}' => $data['portfolio_title'] ?? '',
            '{site_name}'       => $data['site_name'] ?? 'Swap Design',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /* ================================================================
       Floating Button Output
       ================================================================ */

    /**
     * Determine if the floating button should be shown.
     */
    public function shouldShowButton(array $settings): bool
    {
        if (!$settings['enabled']) return false;
        if (empty($settings['phone_number'])) return false;

        /* Device visibility */
        $isMobile  = $this->isMobile();
        if ($isMobile && !$settings['show_on_mobile']) return false;
        if (!$isMobile && !$settings['show_on_desktop']) return false;

        return true;
    }

    /**
     * Render the floating WhatsApp button HTML + inline CSS + JS.
     */
    public function renderFloatingButton(?int $pageId = null): string
    {
        $settings = $this->getResolvedSettings($pageId);

        if (!$this->shouldShowButton($settings)) {
            return '';
        }

        $position   = $settings['button_position'];
        $style      = $settings['button_style'];
        $isOnline   = $settings['show_online_status'] ? $this->isBusinessHours() : true;
        $statusText = $isOnline ? 'Online' : 'Offline';
        $statusDot  = $isOnline ? '#25d366' : '#999';
        $showLabel  = ($style === 'icon_text');
        $message    = $settings['default_message'];
        $number     = $settings['phone_number'];
        $url        = $this->buildUrl($number, $message);

        $pageData = null;
        if ($pageId) {
            $pageData = $this->db->fetch("SELECT id, title FROM pages WHERE id = ?", [$pageId]);
        }

        $pageIdAttr   = $pageId ?: 0;
        $pageTitle    = escJs($pageData['title'] ?? '');
        $dataMessage  = esc($message);

        $labelHtml = '';
        if ($showLabel) {
            $labelHtml = '<span class="wa-btn__label">Chat with us</span>';
        }

        $statusHtml = '';
        if ($settings['show_online_status']) {
            $statusHtml = '<span class="wa-btn__status ' . ($isOnline ? 'wa-btn__status--online' : 'wa-btn__status--offline') . '">' . $statusText . '</span>';
        }

        $buttonStyle  = ($style === 'icon') ? ' wa-btn--icon-only' : '';
        $posClass     = ' wa-btn--' . $position;
        $onlineClass  = $isOnline ? '' : ' wa-btn--offline';

        return <<<HTML
<link rel="stylesheet" href="/assets/css/whatsapp.css">
<div class="wa-btn{$buttonStyle}{$posClass}{$onlineClass}"
     id="wa-floating-btn"
     data-enabled="1"
     data-page-id="{$pageIdAttr}"
     data-page-title="{$pageTitle}"
     data-message="{$dataMessage}"
     aria-label="Chat on WhatsApp"
     role="button"
     tabindex="0">
    <a href="{$url}" target="_blank" rel="noopener noreferrer" class="wa-btn__link" data-wa-click="floating_button">
        <span class="wa-btn__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="#ffffff">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
            </svg>
        </span>
        {$labelHtml}
        {$statusHtml}
    </a>
</div>
<script src="/assets/js/whatsapp.js" defer></script>
HTML;
    }

    /**
     * Render a CTA button for use in sections/components (not floating).
     */
    public function renderCtaButton(array $options = []): string
    {
        $settings   = $this->getResolvedSettings($options['page_id'] ?? null);
        $number     = $options['phone_number'] ?? $settings['phone_number'];
        $message    = $options['message'] ?? $settings['default_message'];
        $label      = $options['label'] ?? 'Chat on WhatsApp';
        $cssClass   = $options['class'] ?? 'btn btn--whatsapp';
        $placeholders = $options['placeholders'] ?? [];
        $source     = $options['source'] ?? 'cta';
        $sourceLabel = $options['source_label'] ?? '';

        if (!$settings['enabled'] || empty($number)) return '';

        $message = $this->replacePlaceholders($message, $placeholders);
        $url     = $this->buildUrl($number, $message);

        $dataSource  = esc($source);
        $dataLabel   = esc($sourceLabel);
        $pageId      = (int)($options['page_id'] ?? 0);

        return '<a href="' . esc($url) . '" target="_blank" rel="noopener noreferrer" class="' . esc($cssClass) . '" data-wa-click="' . $dataSource . '" data-wa-label="' . $dataLabel . '" data-page-id="' . $pageId . '">' .
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" style="margin-right:0.5rem;vertical-align:-4px"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>' .
            esc($label) . '</a>';
    }

    /* ================================================================
       Analytics
       ================================================================ */

    /**
     * Record a WhatsApp click event.
     */
    public function recordClick(array $data): void
    {
        $this->db->insert('whatsapp_clicks', [
            'page_id'      => !empty($data['page_id']) ? (int)$data['page_id'] : null,
            'page_title'   => $data['page_title'] ?? null,
            'source'       => $data['source'] ?? 'floating_button',
            'source_label' => $data['source_label'] ?? null,
            'device_type'  => $data['device_type'] ?? 'unknown',
            'visitor_ip'   => $this->getClientIp(),
        ]);
    }

    /**
     * Get click statistics.
     */
    public function getStats(): array
    {
        $total = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM whatsapp_clicks", [], 0);

        $perPage = $this->db->fetchAll(
            "SELECT page_title, COUNT(*) AS cnt FROM whatsapp_clicks WHERE page_title IS NOT NULL GROUP BY page_title ORDER BY cnt DESC LIMIT 10"
        );

        $perSource = $this->db->fetchAll(
            "SELECT source, COUNT(*) AS cnt FROM whatsapp_clicks WHERE source IS NOT NULL GROUP BY source ORDER BY cnt DESC"
        );

        $perDevice = $this->db->fetchAll(
            "SELECT device_type, COUNT(*) AS cnt FROM whatsapp_clicks GROUP BY device_type ORDER BY cnt DESC"
        );

        $recent = $this->db->fetchAll(
            "SELECT * FROM whatsapp_clicks ORDER BY clicked_at DESC LIMIT 20"
        );

        return [
            'total'      => $total,
            'per_page'   => $perPage,
            'per_source' => $perSource,
            'per_device' => $perDevice,
            'recent'     => $recent,
        ];
    }

    /* ================================================================
       Helpers
       ================================================================ */

    /**
     * Normalize a phone number to digits only, stripping + and spaces.
     */
    public function normalizePhoneNumber(string $number): string
    {
        $number = preg_replace('/[^0-9+]/', '', trim($number));

        /* Ensure starts with country code */
        if (str_starts_with($number, '+')) {
            $number = substr($number, 1);
        }

        return $number;
    }

    /**
     * Validate a WhatsApp phone number format.
     */
    public function validatePhoneNumber(string $number): array
    {
        $cleaned = $this->normalizePhoneNumber($number);

        if (empty($cleaned)) {
            return ['valid' => false, 'error' => 'Phone number is required.'];
        }

        if (!preg_match('/^\d{7,15}$/', $cleaned)) {
            return ['valid' => false, 'error' => 'Phone number must be 7-15 digits.'];
        }

        return ['valid' => true, 'normalized' => $cleaned];
    }

    /**
     * Get client IP address.
     */
    private function getClientIp(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CF_CONNECTING_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    /**
     * Check if the current request is from a mobile device.
     */
    private function isMobile(): bool
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return (bool)preg_match('/(android|iphone|ipad|ipod|blackberry|webos|mobile)/i', $ua);
    }
}
