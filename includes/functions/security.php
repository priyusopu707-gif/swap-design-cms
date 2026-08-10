<?php
/**
 * Swap Design - Security Helper Functions
 *
 * Token generation, secure headers, rate limiting, IP retrieval,
 * and other security utilities.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/**
 * Generate a cryptographically secure random token.
 *
 * @param int $length Number of random bytes (actual string will be 2x length in hex)
 * @return string     Hex-encoded random token
 */
function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Hash a token using SHA-256 (for split-token patterns).
 *
 * @param string $token The raw token
 * @return string       SHA-256 hash
 */
function hashToken(string $token): string
{
    return hash('sha256', $token);
}

/**
 * Timing-safe token comparison.
 *
 * @param string $knownToken The known/stored token
 * @param string $userToken  The user-supplied token
 * @return bool
 */
function verifyToken(string $knownToken, string $userToken): bool
{
    return hash_equals($knownToken, $userToken);
}

/**
 * Get the client's real IP address (respects proxy chains).
 *
 * @return string
 */
function getClientIp(): string
{
    $headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip  = trim($ips[0]);

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get the current user agent (truncated).
 *
 * @param int $maxLength Maximum length
 * @return string
 */
function getUserAgent(int $maxLength = 500): string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    return mb_substr($ua, 0, $maxLength);
}

/**
 * Set security headers on the response.
 */
function setSecureHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    /* Content Security Policy */
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://www.google-analytics.com https://www.googletagmanager.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data: https:",
        "connect-src 'self' https://www.google-analytics.com",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
    ];
    header('Content-Security-Policy: ' . implode('; ', $csp));

    /* Prevent caching of admin pages */
    if (defined('IS_ADMIN') && IS_ADMIN) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }
}

/**
 * Simple rate limiter using file-based storage.
 * Checks if a given key has exceeded a threshold within a time window.
 *
 * @param string $key          Unique key (e.g., email+ip)
 * @param int    $maxAttempts  Maximum allowed attempts
 * @param int    $decayMinutes Time window in minutes
 * @return bool                True if rate limit exceeded
 */
function rateLimitExceeded(string $key, int $maxAttempts = 5, int $decayMinutes = 15): bool
{
    $cacheDir = defined('CACHE_PATH') ? CACHE_PATH : dirname(__DIR__) . '/cache/';

    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    $filename = "{$cacheDir}ratelimit_" . md5($key) . '.json';
    $now      = time();
    $window   = $decayMinutes * 60;

    $attempts = [];

    if (file_exists($filename)) {
        $contents = @file_get_contents($filename);
        if ($contents !== false) {
            $attempts = json_decode($contents, true) ?: [];
        }
    }

    /* Remove expired entries */
    $attempts = array_filter($attempts, fn(int $timestamp) => $now - $timestamp < $window);

    /* Check limit */
    $exceeded = count($attempts) >= $maxAttempts;

    /* Record new attempt if not exceeded */
    if (!$exceeded) {
        $attempts[] = $now;
        @file_put_contents($filename, json_encode($attempts), LOCK_EX);
    }

    return $exceeded;
}

/**
 * Rate limit by IP address for public endpoints.
 *
 * @param string $endpoint     Endpoint identifier
 * @param int    $maxAttempts  Maximum allowed attempts
 * @param int    $decayMinutes Time window in minutes
 * @return bool                True if rate limit exceeded
 */
function rateLimitByIp(string $endpoint, int $maxAttempts = 10, int $decayMinutes = 1): bool
{
    $ip = getClientIp();
    $key = "ip:{$endpoint}:{$ip}";
    return rateLimitExceeded($key, $maxAttempts, $decayMinutes);
}

/**
 * Clear rate limit records for a key.
 *
 * @param string $key
 */
function clearRateLimit(string $key): void
{
    $cacheDir = defined('CACHE_PATH') ? CACHE_PATH : dirname(__DIR__) . '/cache/';
    $filename = "{$cacheDir}ratelimit_" . md5($key) . '.json';

    if (file_exists($filename)) {
        @unlink($filename);
    }
}

/**
 * Validate a URL against allowed schemes.
 *
 * @param string $url
 * @return bool
 */
function isValidUrl(string $url): bool
{
    $scheme = parse_url($url, PHP_URL_SCHEME);
    return $scheme === null || in_array(strtolower($scheme), ['http', 'https'], true);
}

/**
 * Check if a string is a valid slug format.
 *
 * @param string $slug
 * @return bool
 */
function isValidSlug(string $slug): bool
{
    return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
}

/**
 * Generate a slug from a string.
 *
 * @param string $text
 * @return string
 */
function sluggify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s]+/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}
