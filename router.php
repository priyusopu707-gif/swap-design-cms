<?php
/**
 * Swap Design - Dev Server Router
 *
 * Mimics Apache mod_rewrite for the PHP built-in development server.
 * Usage: php -S 0.0.0.0:8080 -t /path/to/swap-design router.php
 *
 * @package SwapDesign
 */

/* Serve static files directly */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/* Block sensitive directories */
$blocked = ['/includes/', '/database/', '/logs/', '/cache/', '/.env', '/.sql', '/.log', '/.md'];
foreach ($blocked as $prefix) {
    if (strpos($uri, $prefix) === 0 || strpos($uri, $prefix) !== false) {
        http_response_code(403);
        echo "Forbidden";
        return true;
    }
}

/* Serve existing files directly (CSS, JS, images, uploads) */
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && is_file($filePath)) {
    return false;
}

/* Remove trailing slash */
if ($uri !== '/' && substr($uri, -1) === '/') {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . rtrim($uri, '/'));
    return true;
}

/* Route everything through index.php */
$_GET['url'] = trim($uri, '/');
$_SERVER['REQUEST_URI'] = $uri;

/* Load index.php */
require __DIR__ . '/index.php';
return true;
