<?php
/**
 * Swap Design - Database Configuration
 *
 * Reads credentials from environment variables (`.env` on local dev,
 * hoster-provided env vars in production). Fall back to defaults only
 * when no environment variable is set, to keep the repository commit-safe.
 *
 * IMPORTANT: Never commit real credentials in this file.
 * Always use environment variables or .env (which is gitignored).
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/*
 * Local .env loader.
 *
 * Runs before environment.php (index.php loads database.php first) and is
 * executed by every entry point that needs DB credentials (index.php,
 * api/*, admin/*). Reads the gitignored `.env` once and exports variables
 * via putenv(). In production the same keys come from hoster env vars and
 * this loader is a harmless no-op when `.env` is absent.
 */
(function () {
    if (getenv('SWAP_DOTENV_LOADED') === '1') {
        return;
    }
    $root = dirname(__DIR__, 2);
    $file = $root . '/.env';
    if (is_file($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                    continue;
                }
                [$key, $value] = array_map('trim', explode('=', $line, 2));
                $value = trim($value, '"\'');
                if ($key !== '') {
                    putenv($key . '=' . $value);
                    $_ENV[$key] = $value;
                }
            }
        }
    }

    putenv('SWAP_DOTENV_LOADED=1');
})();

// ---- Database credentials from environment (preferred) or fallback ----
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'swap_design');
define('DB_USER', getenv('DB_USER') ?: 'swap_design_user');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
