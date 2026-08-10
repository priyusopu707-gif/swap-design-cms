<?php
/**
 * Swap Design - Input Sanitization Functions
 *
 * Security-first input handling for all user-supplied data.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/**
 * Sanitize a string for safe output in HTML context
 */
function sanitizeString(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize an email address
 */
function sanitizeEmail(string $email): string
{
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

/**
 * Sanitize an integer value
 */
function sanitizeInt(mixed $input): int
{
    return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize a URL
 */
function sanitizeUrl(string $url): string
{
    $url = filter_var(trim($url), FILTER_SANITIZE_URL);
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

/**
 * Sanitize input for database insertion (does NOT replace prepared statements)
 */
function sanitizeForDb(string $input): string
{
    return trim(strip_tags($input));
}

/**
 * Validate required fields in an array
 *
 * @param array $data   The input data
 * @param array $fields Required field names
 * @return array        List of missing field names
 */
function validateRequired(array $data, array $fields): array
{
    $missing = [];

    foreach ($fields as $field) {
        if (empty($data[$field]) || trim((string) $data[$field]) === '') {
            $missing[] = $field;
        }
    }

    return $missing;
}

/**
 * Validate email format
 */
function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate string length
 */
function validateLength(string $input, int $min, int $max): bool
{
    $length = mb_strlen(trim($input), 'UTF-8');
    return $length >= $min && $length <= $max;
}
