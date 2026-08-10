<?php
/**
 * Swap Design - Main Entry Point
 *
 * All requests are routed through this file by .htaccess.
 * Uses DynamicRouter for DB-driven page resolution and
 * RenderingEngine for layout/section/component assembly.
 *
 * Hostinger Premium Shared Hosting | PHP 8+ | MySQL
 *
 * @package SwapDesign
 */

define('SWAP_ROOT', true);

/* ---- Bootstrap ---- */
$site = require_once __DIR__ . '/includes/config/site.php';
require_once __DIR__ . '/includes/config/database.php';
require_once __DIR__ . '/includes/functions/helpers.php';
require_once __DIR__ . '/includes/functions/sanitize.php';
require_once __DIR__ . '/includes/functions/seo.php';

/* ---- Core Infrastructure ---- */
require_once __DIR__ . '/includes/config/environment.php';
require_once __DIR__ . '/includes/functions/logger.php';
require_once __DIR__ . '/includes/config/error-handler.php';
require_once __DIR__ . '/includes/functions/security.php';

/* ---- Sprint 1: Session, Database, Auth ---- */
require_once __DIR__ . '/includes/Session.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/auth/Auth.php';

/* ---- Sprint 2: Core Services ---- */
require_once __DIR__ . '/includes/settings/SettingsManager.php';
require_once __DIR__ . '/includes/navigation/NavigationManager.php';
require_once __DIR__ . '/includes/blocks/BlockEngine.php';
require_once __DIR__ . '/includes/blocks/ComponentLoader.php';

/* ---- Sprint 3: Dynamic Content Engine ---- */
require_once __DIR__ . '/includes/content/SlugManager.php';
require_once __DIR__ . '/includes/content/DynamicRouter.php';
require_once __DIR__ . '/includes/content/ContentTypeEngine.php';
require_once __DIR__ . '/includes/content/ContentEntryManager.php';
require_once __DIR__ . '/includes/content/SectionManager.php';
require_once __DIR__ . '/includes/content/LayoutManager.php';
require_once __DIR__ . '/includes/content/RenderingEngine.php';
require_once __DIR__ . '/includes/content/HomepageManager.php';
require_once __DIR__ . '/includes/content/HomepageRenderer.php';
require_once __DIR__ . '/includes/content/ServiceManager.php';
require_once __DIR__ . '/includes/content/ServiceRenderer.php';
require_once __DIR__ . '/includes/content/PortfolioManager.php';
require_once __DIR__ . '/includes/content/PortfolioRenderer.php';
require_once __DIR__ . '/includes/content/AboutManager.php';
require_once __DIR__ . '/includes/content/AboutRenderer.php';
require_once __DIR__ . '/includes/content/ContactManager.php';
require_once __DIR__ . '/includes/content/ContactRenderer.php';
require_once __DIR__ . '/includes/content/EmailManager.php';
require_once __DIR__ . '/includes/content/LeadManager.php';
require_once __DIR__ . '/includes/content/BlogManager.php';
require_once __DIR__ . '/includes/content/BlogRenderer.php';

/* ---- Advanced Search ---- */
require_once __DIR__ . '/includes/search/SearchIndexer.php';
require_once __DIR__ . '/includes/search/SearchManager.php';
require_once __DIR__ . '/includes/search/SearchRenderer.php';

/* ---- SEO ---- */
require_once __DIR__ . '/includes/seo/SitemapGenerator.php';
require_once __DIR__ . '/includes/seo/RobotsManager.php';
require_once __DIR__ . '/includes/seo/SEOAuditor.php';

/* ---- WhatsApp Integration ---- */
require_once __DIR__ . '/includes/integrations/WhatsAppManager.php';

/* ---- Performance: Cache Layer ---- */
require_once __DIR__ . '/includes/cache/CacheManager.php';
require_once __DIR__ . '/includes/cache/PageCache.php';
require_once __DIR__ . '/includes/cache/CacheInvalidator.php';
require_once __DIR__ . '/includes/functions/cache.php';

/* ---- Try to serve from page cache ---- */
if (PageCache::serve()) {
    /* Page was served from cache and script exited */
}

/* ---- Security Headers ---- */
setSecureHeaders();

/* ---- Resolve URL ---- */
$url = $_GET['url'] ?? trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

$router   = new DynamicRouter();
$context  = $router->resolve($url);

/* Handle redirects */
if ($context['status'] === 301) {
    header('Location: ' . $context['target_url'], true, 301);
    exit;
}

/* Send appropriate HTTP status code */
if ($context['status'] !== 200) {
    http_response_code((int)$context['status']);
}

/* Render the page */
$engine = new RenderingEngine();
$html = $engine->render($context);

/* Store in page cache */
$pageType = $context['type'] ?? 'page';
PageCache::store($html, $pageType);

/* Output */
echo $html;
