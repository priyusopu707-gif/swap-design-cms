<?php
/**
 * Swap Design - WhatsApp Click Tracker API
 *
 * Records WhatsApp button clicks for analytics.
 * Called via POST from whatsapp.js.
 *
 * @package SwapDesign
 */

define('SWAP_ROOT', true);

require_once __DIR__ . '/../includes/config/site.php';
require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/functions/helpers.php';
require_once __DIR__ . '/../includes/functions/sanitize.php';
require_once __DIR__ . '/../includes/functions/logger.php';
require_once __DIR__ . '/../includes/functions/security.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/settings/SettingsManager.php';
require_once __DIR__ . '/../includes/integrations/WhatsAppManager.php';

/* Only accept POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Rate limiting: max 20 clicks per IP per 5 minutes
if (rateLimitExceeded('api_wa_' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 20, 5)) {
    http_response_code(429);
    exit;
}

$whatsapp = new WhatsAppManager();

try {
    $whatsapp->recordClick([
        'page_id'      => !empty($_POST['page_id']) ? (int)$_POST['page_id'] : null,
        'page_title'   => $_POST['page_title'] ?? null,
        'source'       => $_POST['source'] ?? 'floating_button',
        'source_label' => $_POST['source_label'] ?? null,
        'device_type'  => $_POST['device_type'] ?? 'unknown',
    ]);

    http_response_code(204);
} catch (\Exception $e) {
    logWarning('WhatsApp click tracking failed: ' . $e->getMessage());
    http_response_code(204); /* Still 204 -- don't alert the user */
}
