<?php
/**
 * Swap Design - Search AJAX Handler (Public)
 *
 * Endpoints:
 *   GET  ?action=suggest&q=term   Live suggestion dropdown results
 *   GET  ?action=popular          Popular searches (for empty dropdown)
 *   POST action=record_click      Log a result click (CSRF protected)
 *
 * All reads are rate limited per IP; the write action is CSRF protected.
 *
 * @package SwapDesign
 */

define('SWAP_ROOT', true);

require_once __DIR__ . '/../includes/config/site.php';
require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/config/environment.php';
require_once __DIR__ . '/../includes/functions/logger.php';
require_once __DIR__ . '/../includes/config/error-handler.php';
require_once __DIR__ . '/../includes/functions/helpers.php';
require_once __DIR__ . '/../includes/functions/sanitize.php';
require_once __DIR__ . '/../includes/functions/security.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/settings/SettingsManager.php';
require_once __DIR__ . '/../includes/search/SearchManager.php';
require_once __DIR__ . '/../includes/search/SearchIndexer.php';

Session::start();

function respond(bool $ok, string $message = '', int $httpCode = 200, array $extra = []): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'ok'      => $ok,
        'message' => $message,
    ], $extra));
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    /* ---- Live suggestions ---- */
    case 'suggest':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            respond(false, 'Method not allowed', 405);
        }

        $searchManager = new SearchManager();

        if ($searchManager->isRateLimited()) {
            respond(false, 'Too many requests', 429);
        }

        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) > 100) {
            $q = mb_substr($q, 0, 100);
        }

        $limit = min(10, max(1, (int)($_GET['limit'] ?? 8)));

        if ($q !== '' && mb_strlen($q) < $searchManager->minQueryLength()) {
            respond(true, '', 200, ['items' => []]);
        }

        $items = $searchManager->getSuggestions($q, $limit);

        respond(true, '', 200, ['items' => $items]);

    /* ---- Popular searches ---- */
    case 'popular':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            respond(false, 'Method not allowed', 405);
        }

        $searchManager = new SearchManager();
        $limit = min(10, max(1, (int)($_GET['limit'] ?? 6)));

        respond(true, '', 200, ['items' => $searchManager->getPopularSearches($limit)]);

    /* ---- Record a result click ---- */
    case 'record_click':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(false, 'Method not allowed', 405);
        }

        $token = $_POST['token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verifyCsrfToken($token)) {
            respond(false, 'Invalid security token', 403);
        }

        $searchManager = new SearchManager();

        if ($searchManager->isRateLimited()) {
            respond(false, 'Too many requests', 429);
        }

        $result = [
            'content_type' => (string)($_POST['content_type'] ?? ''),
            'content_id'   => (int)($_POST['content_id'] ?? 0),
            'title'        => (string)($_POST['title'] ?? ''),
            'url'          => (string)($_POST['url'] ?? ''),
        ];

        if ($result['content_type'] === '' || $result['content_id'] <= 0) {
            respond(false, 'Invalid result', 400);
        }

        $searchLogId = !empty($_POST['search_log_id']) ? (int)$_POST['search_log_id'] : null;
        $query       = (string)($_POST['query'] ?? '');
        $position    = max(0, (int)($_POST['position'] ?? 0));

        $searchManager->logClick($searchLogId, $query, $result, $position);

        respond(true, 'Recorded');

    default:
        respond(false, 'Unknown action', 400);
}
