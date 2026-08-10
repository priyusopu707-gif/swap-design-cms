<?php
/**
 * Swap Design - Contact Form API Endpoint
 *
 * Processes contact form submissions.
 * Expects POST with: name, email, subject, message
 * Returns JSON response.
 *
 * @package SwapDesign
 */

// Define root constant for secure includes
define('SWAP_ROOT', true);

// Load dependencies
$site = require_once __DIR__ . '/../includes/config/site.php';
require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/functions/helpers.php';
require_once __DIR__ . '/../includes/functions/sanitize.php';
require_once __DIR__ . '/../includes/functions/security.php';

// Set response headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Verify AJAX request
if (!isAjax()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Direct access not allowed.']);
    exit;
}

// Rate limiting: max 5 submissions per IP per 15 minutes
if (rateLimitExceeded('api_contact_' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 5, 15)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}

// CSRF verification
if ($site->forms->enableCsrf) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Security check failed. Please refresh the page and try again.']);
        exit;
    }
}

// Honeypot check
if ($site->forms->enableHoneypot && !empty($_POST['website'])) {
    // Silently accept but don't process (bot detected)
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
    exit;
}

// --- Collect and validate input ---
$name    = sanitizeString($_POST['name']    ?? '');
$email   = sanitizeEmail($_POST['email']    ?? '');
$subject = sanitizeString($_POST['subject'] ?? '');
$message = sanitizeString($_POST['message'] ?? '');

$errors = [];

if (empty($name) || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors['contact-name'] = 'Please enter a valid name (2-100 characters).';
}

if (empty($email)) {
    $errors['contact-email'] = 'Please enter a valid email address.';
}

if (empty($message) || mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
    $errors['contact-message'] = 'Please enter a message (10-5000 characters).';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Please fix the errors below.',
        'errors'  => $errors,
    ]);
    exit;
}

// --- Process submission ---
try {
    $db = getDBConnection();

    $stmt = $db->prepare('
        INSERT INTO contact_messages (name, email, subject, message, ip_address, created_at)
        VALUES (:name, :email, :subject, :message, :ip, NOW())
    ');

    $stmt->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':subject' => $subject ?: 'General Inquiry',
        ':message' => $message,
        ':ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    // Send email notification to admin
    $recipient = $site->forms->contactRecipient;
    $emailSubject = $site->forms->contactSubject . ' - ' . ($subject ?: 'General Inquiry');

    $emailBody = "New contact form submission from {$site->brand->name}\n\n";
    $emailBody .= "Name: {$name}\n";
    $emailBody .= "Email: {$email}\n";
    $emailBody .= "Subject: " . ($subject ?: 'General Inquiry') . "\n\n";
    $emailBody .= "Message:\n{$message}\n\n";
    $emailBody .= "--\nSent from {$site->urls->base}";

    $emailHeaders = [
        'From'           => $site->brand->email ?: 'noreply@swapdesign.com',
        'Reply-To'       => $email,
        'Content-Type'   => 'text/plain; charset=UTF-8',
        'X-Mailer'       => 'PHP/' . phpversion(),
    ];

    $headerString = '';
    foreach ($emailHeaders as $key => $value) {
        $headerString .= "{$key}: {$value}\r\n";
    }

    mail($recipient, $emailSubject, $emailBody, $headerString);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully! We will get back to you soon.',
    ]);

} catch (PDOException $e) {
    error_log('Swap Design contact form error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An internal error occurred. Please try again later.',
    ]);
}
