<?php
/**
 * Swap Design - Session Management Class
 *
 * OOP wrapper around PHP's native session functions with secure defaults.
 * Supports flash messages (one-request persistence).
 *
 * Usage:
 *   Session::start();
 *   Session::set('user_id', 42);
 *   $name = Session::get('user_name', 'Guest');
 *   Session::flash('success', 'Login successful');
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class Session
{
    private static bool $started = false;
    private static array $flashBag = [];

    /**
     * Start the session with secure configuration.
     */
    public static function start(array $options = []): void
    {
        if (self::$started) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            $defaults = [
                'cookie_httponly'  => true,
                'cookie_samesite'  => 'Lax',
                'use_strict_mode'  => true,
                'use_only_cookies' => true,
                'cookie_secure'    => (APP_ENV === 'production'),
            ];

            $options = array_merge($defaults, $options);

            if (!session_start($options)) {
                throw new \RuntimeException('Failed to start session');
            }
        }

        self::$started = true;
        self::ageFlashData();
    }

    /**
     * Get a value from the session.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a value in the session.
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a key exists in the session.
     */
    public static function has(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a key from the session.
     */
    public static function remove(string $key): void
    {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Set a flash message (survives exactly one request).
     */
    public static function flash(string $key, mixed $value): void
    {
        self::ensureStarted();

        if (!isset($_SESSION['_flash_new'])) {
            $_SESSION['_flash_new'] = [];
        }

        $_SESSION['_flash_new'][$key] = $value;
    }

    /**
     * Get a flash message (and consume it).
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();

        if (isset($_SESSION['_flash_old'][$key])) {
            $value = $_SESSION['_flash_old'][$key];
            unset($_SESSION['_flash_old'][$key]);
            return $value;
        }

        return $default;
    }

    /**
     * Check if a flash message exists.
     */
    public static function hasFlash(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION['_flash_old'][$key]);
    }

    /**
     * Regenerate the session ID (prevents session fixation).
     */
    public static function regenerate(): void
    {
        self::ensureStarted();
        session_regenerate_id(true);
    }

    /**
     * Destroy the session completely.
     */
    public static function destroy(): void
    {
        self::ensureStarted();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
        self::$started = false;
    }

    /**
     * Move new flash data to old (consumed on next getFlash).
     */
    private static function ageFlashData(): void
    {
        if (isset($_SESSION['_flash_new'])) {
            $_SESSION['_flash_old'] = $_SESSION['_flash_new'];
            unset($_SESSION['_flash_new']);
        }
    }

    /**
     * Ensure session has been started.
     */
    private static function ensureStarted(): void
    {
        if (!self::$started) {
            throw new \RuntimeException(
                'Session not started. Call Session::start() first.'
            );
        }
    }
}
