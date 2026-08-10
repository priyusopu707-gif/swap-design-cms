<?php
/**
 * Swap Design - Environment Configuration
 *
 * Centralized environment variable management.
 * All configuration constants are defined here.
 * Sensitive values use getenv() with fallback defaults.
 *
 * Required: loaded after config/site.php and config/database.php
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/* ---- Application Environment ---- */
define('APP_ENV',   getenv('APP_ENV')   ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN));

if (getenv('APP_URL')) {
    define('APP_URL', getenv('APP_URL'));
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('APP_URL', $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
}
define('SITE_URL',  APP_URL);

/* ---- Paths ---- */
define('ROOT_PATH',  dirname(__DIR__, 2));
define('LOG_PATH',   getenv('LOG_PATH') ?: ROOT_PATH . '/logs/');
define('CACHE_PATH', getenv('CACHE_PATH') ?: ROOT_PATH . '/cache/');

/* ---- Timezone ---- */
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/New_York');

/* ---- Environment-specific ini overrides ---- */
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

/* ---- Session security defaults ---- */
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', '1');
}
