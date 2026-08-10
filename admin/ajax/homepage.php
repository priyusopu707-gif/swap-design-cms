<?php
/**
 * Swap Design - Homepage Editor AJAX Handler
 *
 * Handles all homepage section AJAX operations:
 *  - save (publish / draft)
 *  - toggle (enable / disable)
 *  - reorder
 *  - publish_all
 *
 * @package SwapDesign
 */

define('SWAP_ROOT', true);
define('IS_ADMIN', true);
define('IS_AJAX', true);

require_once __DIR__ . '/../../includes/config/site.php';
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/config/environment.php';
require_once __DIR__ . '/../../includes/functions/logger.php';
require_once __DIR__ . '/../../includes/config/error-handler.php';
require_once __DIR__ . '/../../includes/functions/helpers.php';
require_once __DIR__ . '/../../includes/functions/sanitize.php';
require_once __DIR__ . '/../../includes/functions/security.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/auth/Auth.php';
require_once __DIR__ . '/../../includes/content/HomepageManager.php';

Session::start();

/* ---- Security checks ---- */
if (empty($_SESSION['user_id'])) {
    respond(false, 'Unauthorized', 401);
}

$trustedReferer = (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], SITE_URL) === 0);
if (!$trustedReferer) {
    respond(false, 'Invalid referer', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed', 405);
}

if (empty($_POST['action'])) {
    respond(false, 'Missing action', 400);
}

/* CSRF protection */
$token = $_POST['token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($token)) {
    respond(false, 'Invalid security token', 403);
}

$action = $_POST['action'];
$manager = new HomepageManager();

switch ($action) {

    case 'save':
        $sectionId  = (int)($_POST['section_id'] ?? 0);
        $sectionKey = $_POST['section_key'] ?? '';
        $configJson = $_POST['config'] ?? '{}';
        $asDraft    = !empty($_POST['as_draft']);

        if ($sectionId <= 0) respond(false, 'Invalid section ID');

        $config = json_decode($configJson, true);
        if (!is_array($config)) respond(false, 'Invalid config JSON');

        // Clean config: remove non-field keys from repeater arrays
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = array_values($value);
            }
        }

        $manager->update($sectionId, $config);
        $manager->setStatus($sectionId, $asDraft ? 'draft' : 'published');

        respond(true, $asDraft ? 'Draft saved' : 'Section published');

    case 'toggle':
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $enabled   = ($_POST['enabled'] ?? '1') === '1';

        if ($sectionId <= 0) respond(false, 'Invalid section ID');

        $manager->toggle($sectionId, $enabled);
        respond(true, $enabled ? 'Section enabled' : 'Section disabled');

    case 'reorder':
        $orderStr = $_POST['order'] ?? '';

        if (empty($orderStr)) respond(false, 'Missing order data');

        $ids = array_map('intval', explode(',', $orderStr));
        $ids = array_filter($ids, function ($id) { return $id > 0; });

        if (empty($ids)) respond(false, 'Invalid order data');

        $manager->reorder($ids);
        respond(true, 'Order updated');

    case 'publish_all':
        $manager->publishAll();
        respond(true, 'All sections published');

    default:
        respond(false, 'Unknown action');
}

/**
 * Send JSON response and exit.
 */
function respond(bool $ok, string $message = '', int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
