<?php
/**
 * Swap Design - Contact Page AJAX Handler
 *
 * Handles save, toggle, reorder, and publish_all for contact sections.
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
require_once __DIR__ . '/../../includes/content/ContactManager.php';

Session::start();

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

$action  = $_POST['action'];
$manager = new ContactManager();

switch ($action) {
    case 'save':
        $id     = (int)($_POST['section_id'] ?? 0);
        $key    = $_POST['section_key'] ?? '';
        $draft  = !empty($_POST['draft']);

        if ($id < 1) {
            respond(false, 'Missing section_id');
        }

        $defFields = ContactManager::SECTIONS[$key]['config'] ?? [];
        $config    = [];

        foreach ($defFields as $fieldName => $default) {
            if (is_bool($default)) {
                $config[$fieldName] = !empty($_POST[$fieldName]);
            } elseif (is_array($default)) {
                /* Handle repeater arrays */
                $rawItems = $_POST[$fieldName] ?? [];
                if ($fieldName === 'budget_options' || $fieldName === 'timeline_options') {
                    $lines = !empty($_POST[$fieldName]) ? explode("\n", $_POST[$fieldName]) : [];
                    $config[$fieldName] = array_values(array_filter(array_map('trim', $lines)));
                } elseif ($fieldName === 'items' && is_array($rawItems)) {
                    $items = [];
                    foreach ($rawItems as $raw) {
                        if (is_array($raw)) {
                            $items[] = [
                                'question' => sanitizeString($raw['question'] ?? ''),
                                'answer'   => sanitizeString($raw['answer'] ?? ''),
                            ];
                        }
                    }
                    $config[$fieldName] = $items;
                } else {
                    $config[$fieldName] = $default;
                }
            } else {
                $config[$fieldName] = sanitizeString($_POST[$fieldName] ?? (string)$default);
            }
        }

        $manager->update($id, $config);

        if ($draft) {
            $manager->setStatus($id, 'draft');
        } elseif (!empty($_POST['publish'])) {
            $manager->setStatus($id, 'published');
        }

        respond(true, 'Saved');

    case 'toggle':
        $id      = (int)($_POST['section_id'] ?? 0);
        $enabled = !empty($_POST['enabled']);
        if ($id < 1) respond(false, 'Missing section_id');
        $manager->toggle($id, $enabled);
        respond(true, $enabled ? 'Enabled' : 'Disabled');

    case 'reorder':
        $ids = $_POST['ids'] ?? '';
        if (empty($ids)) respond(false, 'Missing ids');
        $orderedIds = array_map('intval', explode(',', $ids));
        $manager->reorder($orderedIds);
        respond(true, 'Order updated');

    case 'publish_all':
        $manager->publishAll();
        respond(true, 'All sections published');

    default:
        respond(false, 'Unknown action');
}

function respond(bool $ok, string $message = '', int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
