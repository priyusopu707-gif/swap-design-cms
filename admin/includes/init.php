<?php
/**
 * Swap Design - Admin Bootstrap
 *
 * Central initialization for all admin pages.
 * Loads configuration, starts session, initializes database,
 * sets security headers, and optionally enforces authentication.
 *
 * Pages that do NOT require auth (login.php):
 *   require __DIR__ . '/includes/init.php';
 *
 * Pages that DO require auth (dashboard, etc.):
 *   require __DIR__ . '/includes/init.php';
 *   Auth::require();
 *
 * @package SwapDesign
 */

define('SWAP_ROOT', true);
define('IS_ADMIN', true);

/* ---- Bootstrap core ---- */
$site = require_once __DIR__ . '/../../includes/config/site.php';
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/config/environment.php';

/* ---- Load logger before error handler so logMessage() is available ---- */
require_once __DIR__ . '/../../includes/functions/logger.php';

require_once __DIR__ . '/../../includes/config/error-handler.php';

require_once __DIR__ . '/../../includes/functions/helpers.php';
require_once __DIR__ . '/../../includes/functions/sanitize.php';
require_once __DIR__ . '/../../includes/functions/security.php';

/* ---- Core services ---- */
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/auth/Auth.php';

/* ---- Sprint 2: Content services ---- */
require_once __DIR__ . '/../../includes/settings/SettingsManager.php';
require_once __DIR__ . '/../../includes/themes/ThemeEngine.php';
require_once __DIR__ . '/../../includes/navigation/NavigationManager.php';
require_once __DIR__ . '/../../includes/blocks/BlockEngine.php';
require_once __DIR__ . '/../../includes/blocks/ComponentLoader.php';
require_once __DIR__ . '/../../includes/media/FileUploader.php';
require_once __DIR__ . '/../../includes/media/ImageOptimizer.php';
require_once __DIR__ . '/../../includes/media/MediaLibrary.php';

/* ---- Sprint 3: Dynamic Content Engine ---- */
require_once __DIR__ . '/../../includes/content/SlugManager.php';
require_once __DIR__ . '/../../includes/content/ContentTypeEngine.php';
require_once __DIR__ . '/../../includes/content/ContentEntryManager.php';
require_once __DIR__ . '/../../includes/content/SectionManager.php';
require_once __DIR__ . '/../../includes/content/LayoutManager.php';
require_once __DIR__ . '/../../includes/content/DynamicRouter.php';
require_once __DIR__ . '/../../includes/content/PageManager.php';
require_once __DIR__ . '/../../includes/content/RenderingEngine.php';
require_once __DIR__ . '/../../includes/content/HomepageManager.php';
require_once __DIR__ . '/../../includes/content/ServiceManager.php';
require_once __DIR__ . '/../../includes/content/PortfolioManager.php';

/* ---- About Module ---- */
require_once __DIR__ . '/../../includes/content/AboutManager.php';

/* ---- Contact & Lead Module ---- */
require_once __DIR__ . '/../../includes/content/ContactManager.php';
require_once __DIR__ . '/../../includes/content/LeadManager.php';
require_once __DIR__ . '/../../includes/content/EmailManager.php';

/* ---- Blog Module ---- */
require_once __DIR__ . '/../../includes/content/BlogManager.php';

/* ---- Advanced Search Module ---- */
require_once __DIR__ . '/../../includes/search/SearchIndexer.php';
require_once __DIR__ . '/../../includes/search/SearchManager.php';
require_once __DIR__ . '/../../includes/search/SearchRenderer.php';

/* ---- SEO Module ---- */
require_once __DIR__ . '/../../includes/seo/SitemapGenerator.php';
require_once __DIR__ . '/../../includes/seo/RobotsManager.php';
require_once __DIR__ . '/../../includes/seo/SEOAuditor.php';

/* ---- WhatsApp Integration ---- */
require_once __DIR__ . '/../../includes/integrations/WhatsAppManager.php';

/* ---- Start session ---- */
Session::start();

/* ---- Set security headers ---- */
setSecureHeaders();
