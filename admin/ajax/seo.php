<?php
/**
 * SEO Dashboard AJAX Handler
 */

define('SWAP_ROOT', true);
define('IS_ADMIN', true);
define('IS_AJAX', true);

require_once __DIR__ . '/../includes/init.php';

if (empty($_SESSION['user_id'])) {
    respond(false, 'Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed', 405);
}

$token = $_POST['token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($token)) {
    respond(false, 'Invalid security token', 403);
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'regenerate_sitemap':
        if (class_exists('SitemapGenerator')) {
            $generator = new SitemapGenerator();
            $generator->regenerate();
            respond(true, 'Sitemap regenerated');
        } else {
            respond(false, 'Sitemap generator not available');
        }
        break;

    case 'run_audit':
        if (class_exists('SEOAuditor')) {
            $auditor = new SEOAuditor();
            $audit = $auditor->runAudit();
            respond(true, '', 200, ['audit' => $audit]);
        } else {
            respond(false, 'SEO auditor not available');
        }
        break;

    default:
        respond(false, 'Unknown action', 400);
}
