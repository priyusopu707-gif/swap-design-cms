<?php
/**
 * Swap Design - Logging System
 *
 * Simple file-based logger with daily rotation.
 * Compatible with Hostinger shared hosting (no external deps).
 *
 * Log levels: DEBUG, INFO, WARNING, ERROR
 *
 * Usage:
 *   logMessage('INFO', 'User logged in', ['email' => $email]);
 *   logMessage('ERROR', 'Database query failed', ['error' => $e->getMessage()]);
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/* ---- Log Level Constants ---- */
defined('LOG_DEBUG')   or define('LOG_DEBUG',   'DEBUG');
defined('LOG_INFO')    or define('LOG_INFO',    'INFO');
defined('LOG_WARNING') or define('LOG_WARNING', 'WARNING');
defined('LOG_ERROR')   or define('LOG_ERROR',   'ERROR');

/**
 * Write a message to the log file.
 *
 * @param string $level   Log level (LOG_DEBUG, LOG_INFO, LOG_WARNING, LOG_ERROR)
 * @param string $message The log message
 * @param array  $context Additional context data (will be JSON-encoded)
 */
function logMessage(string $level, string $message, array $context = []): void
{
    /* Only log DEBUG in development */
    if ($level === LOG_DEBUG && (!defined('APP_DEBUG') || !APP_DEBUG)) {
        return;
    }

    $logDir = defined('LOG_PATH') ? LOG_PATH : (dirname(__DIR__) . '/logs/');

    /* Auto-create log directory */
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $date     = date('Y-m-d');
    $time     = date('Y-m-d H:i:s');
    $logFile  = $logDir . "app-{$date}.log";

    $contextStr = '';
    if (!empty($context)) {
        /* Remove sensitive fields from context */
        $safe = $context;
        $sensitive = ['password', 'password_hash', 'token', 'secret', 'api_key'];
        foreach ($sensitive as $key) {
            if (isset($safe[$key])) {
                $safe[$key] = '[REDACTED]';
            }
        }
        $contextStr = ' ' . json_encode($safe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $line = sprintf("[%s] %s: %s%s\n", $time, strtoupper($level), $message, $contextStr);

    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Log a debug message.
 */
function logDebug(string $message, array $context = []): void
{
    logMessage(LOG_DEBUG, $message, $context);
}

/**
 * Log an info message.
 */
function logInfo(string $message, array $context = []): void
{
    logMessage(LOG_INFO, $message, $context);
}

/**
 * Log a warning.
 */
function logWarning(string $message, array $context = []): void
{
    logMessage(LOG_WARNING, $message, $context);
}

/**
 * Log an error.
 */
function logError(string $message, array $context = []): void
{
    logMessage(LOG_ERROR, $message, $context);
}
