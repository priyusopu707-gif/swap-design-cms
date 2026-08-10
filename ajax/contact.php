<?php
/**
 * Swap Design - Contact Form AJAX Handler (Public)
 *
 * Handles form submissions with CSRF, honeypot, rate limiting,
 * file upload, lead creation, and email notifications.
 */

define('SWAP_ROOT', true);

require_once __DIR__ . '/../includes/config/site.php';
require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/config/environment.php';
require_once __DIR__ . '/../includes/functions/logger.php';
require_once __DIR__ . '/../includes/config/error-handler.php';
require_once __DIR__ . '/../includes/functions/helpers.php';
require_once __DIR__ . '/../includes/functions/sanitize.php';
require_once __DIR__ . '/../includes/functions/security.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/content/LeadManager.php';
require_once __DIR__ . '/../includes/content/EmailManager.php';
require_once __DIR__ . '/../includes/content/ContactManager.php';
require_once __DIR__ . '/../includes/settings/SettingsManager.php';

Session::start();

/* Only accept POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed', 405);
}

$action = $_POST['action'] ?? '';

if ($action === 'track_whatsapp') {
    $db = Database::getInstance();
    $db->insert('whatsapp_clicks', [
        'page_url'        => substr($_POST['page_url'] ?? '', 0, 500),
        'button_location' => substr($_POST['button_location'] ?? '', 0, 50),
    ]);
    respond(true, 'Tracked');
}

if ($action !== 'submit') {
    respond(false, 'Invalid action', 400);
}

/* ---- Spam Protection ---- */

/* 1. Honeypot: hidden field "website" must be empty */
if (!empty($_POST['website'])) {
    /* Bot detected -- silently succeed to not tip off */
    respond(true, 'Thank you! Your message has been sent.');
}

/* 2. CSRF token */
if (empty($_POST['csrf_token'])) {
    respond(false, 'Invalid request - missing token', 403);
}

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    respond(false, 'Invalid request - token mismatch', 403);
}

/* 3. Rate limiting: max 3 submissions per IP per 15 minutes */
$rateKey = 'contact_rate_' . getClientIp();
$submissions = $_SESSION[$rateKey] ?? [];
$submissions = array_filter($submissions, function ($t) {
    return $t > (time() - 900); /* 15 min window */
});

if (count($submissions) >= 3) {
    respond(false, 'Too many submissions. Please try again later.', 429);
}

$submissions[] = time();
$_SESSION[$rateKey] = $submissions;

/* ---- Server-side validation ---- */

$errors = [];

$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$message  = trim($_POST['message'] ?? '');

if (empty($fullName)) $errors[] = 'Full name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
if (empty($message)) $errors[] = 'Message is required.';

/* Consent */
$consent = !empty($_POST['consent']);

/* Custom field requirements from DB config */
$contactManager = new ContactManager();
$formSection = $contactManager->getByKey('contact_form');
$formConfig = $formSection['config'] ?? [];

$subjectReq = !empty($formConfig['subject_required']);
$consentReq = !empty($formConfig['consent_required']);

if ($subjectReq && empty(trim($_POST['subject'] ?? ''))) {
    $errors[] = 'Subject is required.';
}
if ($consentReq && !$consent) {
    $errors[] = 'You must agree to the privacy policy.';
}

if (!empty($errors)) {
    respond(false, implode(' ', $errors), 400);
}

/* ---- File Upload ---- */
$uploadedFiles = [];
$fileEnabled = !empty($formConfig['file_upload_enabled']);

if ($fileEnabled && !empty($_FILES['files']['name'][0])) {
    $maxSizeMb = (int)($formConfig['file_max_size'] ?? 10);
    $allowedRaw = $formConfig['file_allowed_types'] ?? 'pdf,doc,docx,jpg,png,zip';
    $allowedExts = array_map('trim', explode(',', $allowedRaw));

    $uploadedFiles = LeadManager::handleUpload('files', $maxSizeMb * 1024 * 1024, $allowedExts);
}

/* ---- Store Lead ---- */
$leadManager = new LeadManager();

$sourcePage  = $_POST['source_page'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
$referrer    = $_POST['referrer_url'] ?? ($_SERVER['HTTP_REFERER'] ?? '');

$leadData = [
    'full_name'      => $fullName,
    'email'          => $email,
    'phone'          => trim($_POST['phone'] ?? ''),
    'company'        => trim($_POST['company'] ?? ''),
    'service_id'     => !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null,
    'budget'         => trim($_POST['budget'] ?? ''),
    'timeline'       => trim($_POST['timeline'] ?? ''),
    'subject'        => trim($_POST['subject'] ?? ''),
    'message'        => $message,
    'uploaded_files' => $uploadedFiles ?: null,
    'source_page'    => $sourcePage,
    'referrer_url'   => $referrer,
    'ip_address'     => getClientIp(),
    'user_agent'     => getUserAgent(500),
    'device_type'    => LeadManager::detectDeviceType(getUserAgent()),
    'consent_given'  => $consent,
];

$leadId = $leadManager->create($leadData);
$lead   = $leadManager->getById($leadId);

/* ---- Send Emails ---- */
$emailManager = new EmailManager();
$emailManager->sendAdminNotification($lead);
$emailManager->sendUserConfirmation($lead);
$leadManager->markEmailed($leadId);

$successMessage = $formConfig['success_message'] ?? 'Thank you! Your message has been sent successfully.';
respond(true, $successMessage);

/* ================================================================
   Helper
   ================================================================ */

function respond(bool $ok, string $message = '', int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
