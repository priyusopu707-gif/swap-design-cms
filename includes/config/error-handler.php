<?php
/**
 * Swap Design - Error & Exception Handler
 *
 * Registers custom error, exception, and shutdown handlers.
 * In production, errors are logged and a generic message is displayed.
 * In development, detailed error information is shown.
 *
 * Must be loaded AFTER environment.php (needs APP_DEBUG, LOG_PATH).
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/**
 * Custom exception handler.
 */
function swapExceptionHandler(Throwable $e): void
{
    $logMessage = sprintf(
        "[%s] %s in %s:%d\nStack trace:\n%s\n",
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    error_log($logMessage);

    if (function_exists('logMessage')) {
        logMessage('ERROR', $e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'class' => get_class($e),
        ]);
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (APP_DEBUG) {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width,initial-scale=1.0">';
        echo '<title>Application Error</title>';
        echo '<style>body{font-family:monospace;padding:2rem;max-width:960px;margin:0 auto;background:#1a1a1a;color:#e0e0e0;line-height:1.6}h1{color:#ff4d2e}pre{background:#2a2a2a;padding:1rem;border-radius:6px;overflow-x:auto;white-space:pre-wrap}</style></head><body>';
        echo '<h1>' . get_class($e) . '</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . ' : ' . $e->getLine() . '</p>';
        echo '<h2>Stack Trace</h2>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '</body></html>';
    } else {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Server Error</title>';
        echo '<style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f6f6f6;color:#333;text-align:center}h1{font-size:2rem;margin-bottom:.5rem}p{color:#666}a{color:#ff4d2e}</style></head><body>';
        echo '<div><h1>500 -- Server Error</h1><p>Something went wrong. Please try again later.</p><p><a href="/">Go to Homepage</a></p></div>';
        echo '</body></html>';
    }

    exit(1);
}

/**
 * Custom error handler.
 */
function swapErrorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
{
    if (!(error_reporting() & $errno)) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

/**
 * Shutdown handler for fatal errors.
 */
function swapShutdownHandler(): void
{
    $error = error_get_last();
    if ($error === null) {
        return;
    }

    $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if (in_array($error['type'], $fatalErrors, true)) {
        $message = sprintf(
            "Fatal error: %s in %s:%d\n",
            $error['message'],
            $error['file'],
            $error['line']
        );
        error_log($message);

        if (function_exists('logMessage')) {
            logMessage('ERROR', 'Fatal error: ' . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (!APP_DEBUG) {
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Server Error</title></head>';
            echo '<body style="font-family:system-ui;text-align:center;padding:4rem;"><h1>500 -- Server Error</h1><p>Something went wrong.</p></body></html>';
        }
    }
}

/* ---- Register handlers ---- */
set_exception_handler('swapExceptionHandler');
set_error_handler('swapErrorHandler');
register_shutdown_function('swapShutdownHandler');
