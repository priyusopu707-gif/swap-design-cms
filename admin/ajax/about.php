<?php
/**
 * Swap Design - About Editor AJAX Handler
 *
 * Handles all about section AJAX operations:
 *  - save (publish / draft)
 *  - toggle (enable / disable)
 *  - reorder
 *  - publish_all
 *  - save_revision
 *  - get_revisions
 *  - restore_revision
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
require_once __DIR__ . '/../../includes/content/AboutManager.php';

Session::start();

/* Security checks */
if (empty($_SESSION['user_id'])) {
    respond(false, 'Unauthorized', 401);
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
$manager = new AboutManager();

switch ($action) {

    case 'save':
        $sectionId  = (int)($_POST['section_id'] ?? 0);
        $sectionKey = $_POST['section_key'] ?? '';
        $configJson = $_POST['config'] ?? '{}';
        $asDraft    = !empty($_POST['as_draft']);

        if ($sectionId <= 0) respond(false, 'Invalid section ID');

        $config = json_decode($configJson, true);
        if (!is_array($config)) respond(false, 'Invalid config JSON');

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = array_values($value);
            }
        }

        $manager->saveRevision($sectionId, $asDraft ? 'Draft saved' : 'Published');
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

    case 'save_revision':
        $sectionId = (int)($_POST['section_id'] ?? 0);
        $note      = sanitizeString($_POST['note'] ?? '');

        if ($sectionId <= 0) respond(false, 'Invalid section ID');

        $revId = $manager->saveRevision($sectionId, $note);
        respond(true, 'Revision saved', 200, ['revision_id' => $revId]);

    case 'get_revisions':
        $sectionId = (int)($_POST['section_id'] ?? 0);

        if ($sectionId <= 0) respond(false, 'Invalid section ID');

        $revisions = $manager->getRevisions($sectionId);
        respond(true, '', 200, ['revisions' => $revisions]);

    case 'restore_revision':
        $revisionId = (int)($_POST['revision_id'] ?? 0);

        if ($revisionId <= 0) respond(false, 'Invalid revision ID');

        $ok = $manager->restoreRevision($revisionId);
        respond($ok, $ok ? 'Revision restored' : 'Failed to restore');

    case 'link_portfolio':
        $portfolioItemId = (int)($_POST['relation_id'] ?? 0);
        if ($portfolioItemId <= 0) respond(false, 'Invalid portfolio item ID');
        $manager->linkPortfolio($portfolioItemId);
        respond(true, 'Portfolio item linked');

    case 'unlink_portfolio':
        $portfolioItemId = (int)($_POST['relation_id'] ?? 0);
        if ($portfolioItemId <= 0) respond(false, 'Invalid portfolio item ID');
        $manager->unlinkPortfolio($portfolioItemId);
        respond(true, 'Portfolio item unlinked');

    case 'link_block':
        $blockId = (int)($_POST['relation_id'] ?? 0);
        if ($blockId <= 0) respond(false, 'Invalid block ID');
        $manager->linkBlock($blockId);
        respond(true, 'Block linked');

    case 'unlink_block':
        $blockId = (int)($_POST['relation_id'] ?? 0);
        if ($blockId <= 0) respond(false, 'Invalid block ID');
        $manager->unlinkBlock($blockId);
        respond(true, 'Block unlinked');

    default:
        respond(false, 'Unknown action');
}

function respond(bool $ok, string $message = '', int $httpCode = 200, array $extra = []): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
