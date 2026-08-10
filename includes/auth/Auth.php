<?php
/**
 * Swap Design - Authentication Class
 *
 * Handles user login, logout, remember-me tokens, rate limiting,
 * and session-based authentication state.
 *
 * Features:
 *   - bcrypt password verification (cost 12)
 *   - Split-token remember-me (selector + token_hash)
 *   - Per-email + per-IP login attempt tracking
 *   - Progressive rate limiting (5 attempts / 15 minutes)
 *   - Session fixation prevention (regenerate on login)
 *   - Token rotation on remember-me usage
 *
 * Usage:
 *   Auth::login('admin@swapdesign.com', 'password', false);
 *   $currentUser = Auth::user();
 *   Auth::require(); // redirect to login if not authenticated
 *   Auth::logout();
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class Auth
{
    private const MAX_ATTEMPTS    = 5;
    private const LOCKOUT_MINUTES = 15;
    private const REMEMBER_DAYS   = 30;
    private const BCRYPT_COST     = 12;

    /**
     * Attempt to log in a user.
     *
     * @param string $email    User email
     * @param string $password Plain-text password
     * @param bool   $remember Whether to set a remember-me cookie
     * @return array           ['success' => bool, 'message' => string, 'attempts_remaining' => int]
     */
    public static function login(string $email, string $password, bool $remember = false): array
    {
        $db   = Database::getInstance();
        $ip   = getClientIp();
        $now  = date('Y-m-d H:i:s');

        /* Check rate limit */
        if (self::isLockedOut($email, $ip)) {
            $remaining = self::attemptsRemaining($email, $ip);
            return [
                'success'            => false,
                'message'            => 'Too many login attempts. Please try again in ' . self::LOCKOUT_MINUTES . ' minutes.',
                'attempts_remaining' => 0,
            ];
        }

        /* Find user */
        $user = $db->fetch(
            "SELECT id, email, password_hash, name, role, status FROM users WHERE email = ? LIMIT 1",
            [$email]
        );

        if (!$user || $user['status'] !== 'active') {
            self::recordAttempt($email, $ip, false);
            $remaining = self::attemptsRemaining($email, $ip);
            return [
                'success'            => false,
                'message'            => 'Invalid email or password.',
                'attempts_remaining' => $remaining,
            ];
        }

        /* Verify password */
        if (!password_verify($password, $user['password_hash'])) {
            self::recordAttempt($email, $ip, false);
            $remaining = self::attemptsRemaining($email, $ip);
            return [
                'success'            => false,
                'message'            => 'Invalid email or password.',
                'attempts_remaining' => $remaining,
            ];
        }

        /* Success: rehash if needed */
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
            $db->update('users', ['password_hash' => $newHash], 'id = ?', [$user['id']]);
        }

        /* Record successful attempt */
        self::recordAttempt($email, $ip, true);
        self::clearLockout($email, $ip);

        /* Update last login */
        $db->update('users', ['last_login_at' => $now], 'id = ?', [$user['id']]);

        /* Regenerate session to prevent fixation */
        Session::regenerate();

        /* Set session */
        Session::set('user_id',    $user['id']);
        Session::set('user_email', $user['email']);
        Session::set('user_name',  $user['name']);
        Session::set('user_role',  $user['role']);

        /* Remember me */
        if ($remember) {
            self::setRememberToken($user['id']);
        }

        logInfo('User logged in', ['user_id' => $user['id'], 'email' => $email]);

        return [
            'success'            => true,
            'message'            => 'Login successful.',
            'attempts_remaining' => self::MAX_ATTEMPTS,
        ];
    }

    /**
     * Log out the current user.
     */
    public static function logout(): void
    {
        $userId = Session::get('user_id');

        if ($userId) {
            /* Clear remember token from database */
            self::clearRememberToken($userId);

            /* Clear remember cookie */
            if (isset($_COOKIE['remember_token'])) {
                setcookie(
                    'remember_token',
                    '',
                    [
                        'expires'  => time() - 3600,
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Lax',
                        'secure'   => (APP_ENV === 'production'),
                    ]
                );
            }

            logInfo('User logged out', ['user_id' => $userId]);
        }

        Session::destroy();
    }

    /**
     * Check if a user is currently authenticated.
     *
     * If no active session but a remember cookie is present,
     * attempt to authenticate via remember-me token.
     *
     * @return bool
     */
    public static function check(): bool
    {
        /* Already logged in via session */
        if (Session::has('user_id')) {
            return true;
        }

        /* Try remember-me cookie */
        return self::loginViaRememberToken();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return array|null ['id', 'email', 'name', 'role']
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id'    => Session::get('user_id'),
            'email' => Session::get('user_email'),
            'name'  => Session::get('user_name'),
            'role'  => Session::get('user_role'),
        ];
    }

    /**
     * Require authentication. Redirects to login if not authenticated.
     *
     * @param string $redirectUrl URL to redirect to after login
     */
    public static function require(string $redirectUrl = ''): void
    {
        if (!self::check()) {
            if ($redirectUrl) {
                Session::set('auth_redirect', $redirectUrl);
            }
            redirect('/admin/login.php');
        }
    }

    /**
     * Check if the current user has a specific role.
     *
     * @param string $role Role to check (e.g., 'admin', 'editor')
     * @return bool
     */
    public static function hasRole(string $role): bool
    {
        if (!self::check()) {
            return false;
        }

        $userRole = Session::get('user_role');
        return $userRole === $role;
    }

    /**
     * Require a specific role. Redirects with error if user lacks permission.
     *
     * @param string $role Required role
     */
    public static function requireRole(string $role): void
    {
        if (!self::hasRole($role)) {
            Session::flash('error', 'You do not have permission to access this page.');
            redirect('/admin/index.php');
        }
    }

    /**
     * Check if current user is an admin.
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Get remaining login attempts before lockout.
     *
     * @param string $email
     * @param string $ip
     * @return int
     */
    public static function attemptsRemaining(string $email, string $ip): int
    {
        $db  = Database::getInstance();
        $cutoff = date('Y-m-d H:i:s', time() - (self::LOCKOUT_MINUTES * 60));

        $failedCount = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM login_attempts
             WHERE email = ? AND ip_address = ? AND attempted_at > ? AND success = 0",
            [$email, $ip, $cutoff],
            0
        );

        return max(0, self::MAX_ATTEMPTS - $failedCount);
    }

    /* ================================================================
       Private Helpers
       ================================================================ */

    /**
     * Check if the email+IP is locked out.
     */
    private static function isLockedOut(string $email, string $ip): bool
    {
        $db  = Database::getInstance();
        $cutoff = date('Y-m-d H:i:s', time() - (self::LOCKOUT_MINUTES * 60));

        $count = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM login_attempts
             WHERE email = ? AND ip_address = ? AND attempted_at > ? AND success = 0",
            [$email, $ip, $cutoff],
            0
        );

        return $count >= self::MAX_ATTEMPTS;
    }

    /**
     * Record a login attempt (success or failure).
     */
    private static function recordAttempt(string $email, string $ip, bool $success): void
    {
        Database::getInstance()->insert('login_attempts', [
            'email'       => $email,
            'ip_address'  => $ip,
            'attempted_at'=> date('Y-m-d H:i:s'),
            'success'     => $success ? 1 : 0,
        ]);
    }

    /**
     * Clear the lockout counters for an email+IP pair.
     */
    private static function clearLockout(string $email, string $ip): void
    {
        /* Remove old failed attempts for this email+IP */
        $db  = Database::getInstance();
        $cutoff = date('Y-m-d H:i:s', time() - (self::LOCKOUT_MINUTES * 60));

        $db->query(
            "DELETE FROM login_attempts WHERE email = ? AND ip_address = ? AND attempted_at > ? AND success = 0",
            [$email, $ip, $cutoff]
        );
    }

    /**
     * Set a remember-me cookie and store the split-token in the database.
     */
    private static function setRememberToken(int $userId): void
    {
        $db        = Database::getInstance();
        $selector  = generateToken(32);
        $token     = generateToken(32);
        $tokenHash = hashToken($token);
        $expiresAt = date('Y-m-d H:i:s', time() + (self::REMEMBER_DAYS * 86400));

        $db->insert('remember_tokens', [
            'user_id'    => $userId,
            'selector'   => $selector,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        setcookie(
            'remember_token',
            $selector . ':' . $token,
            [
                'expires'  => time() + (self::REMEMBER_DAYS * 86400),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => (APP_ENV === 'production'),
            ]
        );
    }

    /**
     * Attempt to authenticate via remember-me cookie.
     *
     * Uses split-token pattern: selector (DB lookup) + token (SHA-256 verify).
     * On success, rotates the token to prevent theft/reuse.
     *
     * @return bool
     */
    private static function loginViaRememberToken(): bool
    {
        if (empty($_COOKIE['remember_token'])) {
            return false;
        }

        $parts = explode(':', $_COOKIE['remember_token'], 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$selector, $token] = $parts;
        $db = Database::getInstance();

        /* Look up by selector */
        $row = $db->fetch(
            "SELECT rt.id, rt.user_id, rt.token_hash, rt.expires_at,
                    u.email, u.name, u.role, u.status
             FROM remember_tokens rt
             JOIN users u ON u.id = rt.user_id
             WHERE rt.selector = ? LIMIT 1",
            [$selector]
        );

        if (!$row) {
            return false;
        }

        /* Check expiration */
        if (strtotime($row['expires_at']) < time()) {
            $db->delete('remember_tokens', 'id = ?', [$row['id']]);
            return false;
        }

        /* Check user status */
        if ($row['status'] !== 'active') {
            return false;
        }

        /* Verify token hash */
        if (!verifyToken($row['token_hash'], hashToken($token))) {
            /* Potential token theft: clear all tokens for this user */
            $db->delete('remember_tokens', 'user_id = ?', [$row['user_id']]);
            logWarning('Remember-me token mismatch (possible theft)', ['user_id' => $row['user_id']]);
            return false;
        }

        /* Rotate token: delete old, create new */
        $db->delete('remember_tokens', 'id = ?', [$row['id']]);
        self::setRememberToken($row['user_id']);

        /* Set session */
        Session::regenerate();
        Session::set('user_id',    $row['user_id']);
        Session::set('user_email', $row['email']);
        Session::set('user_name',  $row['name']);
        Session::set('user_role',  $row['role']);

        /* Update last login */
        $db->update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$row['user_id']]);

        logInfo('User authenticated via remember-me', ['user_id' => $row['user_id']]);

        return true;
    }

    /**
     * Clear a user's remember-me tokens.
     */
    private static function clearRememberToken(int $userId): void
    {
        Database::getInstance()->delete('remember_tokens', 'user_id = ?', [$userId]);
    }
}
