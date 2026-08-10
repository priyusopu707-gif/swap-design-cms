<?php
/**
 * Swap Design - Helper Functions
 *
 * General-purpose utility functions used throughout the site.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/**
 * Escape output for safe HTML rendering
 */
function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Get the current page URL
 */
function currentUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
}

/**
 * Get base URL of the site (without trailing slash)
 */
function baseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? '');
}

/**
 * Redirect to a given URL and exit
 */
function redirect(string $url, int $statusCode = 302): never
{
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Check if the current request is an AJAX request
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Generate a CSRF token and store in session
 */
function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token
 */
function verifyCsrfToken(string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escape a value for safe use in inline JavaScript strings.
 */
function escJs(string $value): string
{
    return addcslashes($value, "'\\\"\n\r");
}

/**
 * Lazily load and return a SearchIndexer instance, or null when the
 * Advanced Search module is not available.
 *
 * Used by content managers to keep the search index in sync after
 * create/update/delete operations without creating hard dependencies.
 */
function searchIndexer(): ?SearchIndexer
{
    if (!class_exists('SearchIndexer', false)) {
        $file = dirname(__DIR__) . '/search/SearchIndexer.php';
        if (is_file($file)) {
            require_once $file;
        }
    }

    return class_exists('SearchIndexer', false) ? new SearchIndexer() : null;
}

/**
 * Lazily load and return a SitemapGenerator instance, or null when the
 * SEO module is not available. Used by content managers to keep the
 * XML sitemaps in sync after create/update/delete operations without
 * creating hard dependencies.
 */
function sitemapGenerator(): ?SitemapGenerator
{
    if (!class_exists('SitemapGenerator', false)) {
        $file = dirname(__DIR__) . '/seo/SitemapGenerator.php';
        if (is_file($file)) {
            require_once $file;
        }
    }

    return class_exists('SitemapGenerator', false) ? new SitemapGenerator() : null;
}

/**
 * Format a file size in human-readable bytes.
 */
function sizeFormat(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Icon Manager - Returns inline SVG markup for Lucide/Simple Icons
 *
 * @param string $name Icon name (e.g., 'home', 'search', 'arrow-right')
 * @param string $class Additional CSS class
 * @param string $size Size: 'sm', 'md', 'lg', 'xl' or custom size
 * @return string Inline SVG markup
 */
function icon(string $name, string $class = '', string $size = 'md'): string
{
    $sizeClass = match ($size) {
        'sm' => 'icon--sm',
        'md' => '',
        'lg' => 'icon--lg',
        'xl' => 'icon--xl',
        default => '',
    };

    $classAttr = $class || $sizeClass ? ' class="icon ' . trim("$sizeClass $class") . '"' : ' class="icon"';

    // Map icon names to SVG paths (simplified Lucide icons)
    $icons = [
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>',
        'search' => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"></polyline>',
        'menu' => '<line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line>',
        'close' => '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>',
        'check' => '<polyline points="20 6 9 17 4 12"></polyline>',
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
        'clock' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
        'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
        'github' => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>',
        'twitter' => '<path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path>',
        'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle>',
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>',
    ];

    $path = $icons[$name] ?? $icons['home']; // Fallback to home icon

    return <<<HTML
    <svg{$classAttr} width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        {$path}
    </svg>
    HTML;
}
