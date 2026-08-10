<?php
/**
 * Swap Design - Search AJAX Handler (Admin)
 *
 * Endpoints for the admin Search Dashboard:
 *  - get_stats         Overview stats + popular/zero-result/most-viewed
 *  - get_recent        Recent search logs
 *  - clear_logs        Wipe search logs + click analytics
 *  - toggle_logging    Enable/disable search analytics logging
 *  - rebuild_index     Rebuild the search index from content
 *  - export_csv        Download analytics as CSV
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
require_once __DIR__ . '/../../includes/settings/SettingsManager.php';
require_once __DIR__ . '/../../includes/search/SearchManager.php';
require_once __DIR__ . '/../../includes/search/SearchIndexer.php';

Session::start();

if (empty($_SESSION['user_id'])) {
    respond(false, 'Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed', 405);
}

if (empty($_POST['action'])) {
    respond(false, 'Missing action', 400);
}

/* CSRF + referer protection for state-changing admin operations */
$token = $_POST['token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($token)) {
    respond(false, 'Invalid security token', 403);
}

$referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referer === '' || strpos($referer, SITE_URL) !== 0) {
    respond(false, 'Invalid referer', 403);
}

$action       = $_POST['action'];
$searchManager = new SearchManager();

switch ($action) {

    case 'get_stats':
        $stats = $searchManager->getStats();

        $stats['popular']        = $searchManager->getPopularSearches(10);
        $stats['zero_results']   = $searchManager->getZeroResultKeywords(10);
        $stats['most_viewed']    = $searchManager->getMostViewedResults(10);
        $stats['recent']         = $searchManager->getRecentLogs(15);

        respond(true, '', 200, ['stats' => $stats]);

    case 'get_recent':
        $limit = max(1, min(100, (int)($_POST['limit'] ?? 20)));
        respond(true, '', 200, ['logs' => $searchManager->getRecentLogs($limit)]);

    case 'clear_logs':
        $deleted = $searchManager->clearLogs();
        respond(true, $deleted . ' log entries cleared', 200, ['deleted' => $deleted]);

    case 'toggle_logging':
        $enabled = !empty($_POST['enabled']) ? 1 : 0;
        $searchManager->setSetting('logging_enabled', $enabled);
        respond(true, $enabled ? 'Search logging enabled' : 'Search logging disabled', 200, ['enabled' => (bool)$enabled]);

    case 'rebuild_index':
        $indexer = new SearchIndexer();
        $result  = $indexer->buildAll();
        respond(true, 'Index rebuilt: ' . $result['indexed'] . ' items indexed', 200, ['indexed' => (int)$result['indexed']]);

    case 'export_csv':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="search-analytics-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        $stats = $searchManager->getStats();
        fputcsv($out, ['Swap Design Search Analytics', date('Y-m-d H:i:s')]);
        fputcsv($out, []);
        fputcsv($out, ['Metric', 'Value']);
        fputcsv($out, ['Total searches', $stats['total_searches']]);
        fputcsv($out, ['Unique queries', $stats['unique_queries']]);
        fputcsv($out, ['Zero-result searches', $stats['zero_result_searches']]);
        fputcsv($out, ['Result clicks', $stats['total_clicks']]);
        fputcsv($out, ['Indexed items', $stats['indexed_items']]);
        fputcsv($out, ['Logging enabled', $stats['logging_enabled'] ? 'Yes' : 'No']);
        fputcsv($out, []);

        fputcsv($out, ['Popular Keywords', 'Count', 'Total Results']);
        foreach ($searchManager->getPopularSearches(50) as $row) {
            fputcsv($out, [$row['query'], $row['count'], $row['total_results']]);
        }
        fputcsv($out, []);

        fputcsv($out, ['Zero-Result Keywords', 'Count']);
        foreach ($searchManager->getZeroResultKeywords(50) as $row) {
            fputcsv($out, [$row['query'], $row['count']]);
        }
        fputcsv($out, []);

        fputcsv($out, ['Most-Viewed Results', 'Type', 'Clicks', 'URL']);
        foreach ($searchManager->getMostViewedResults(50) as $row) {
            fputcsv($out, [$row['title'], $row['content_type'], $row['clicks'], $row['url']]);
        }

        fclose($out);
        exit;

    default:
        respond(false, 'Unknown action', 400);
}

function respond(bool $ok, string $message = '', int $httpCode = 200, array $extra = []): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
