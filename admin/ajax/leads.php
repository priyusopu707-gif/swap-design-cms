<?php
/**
 * Swap Design - Lead AJAX Handler
 *
 * AJAX endpoints for lead management operations:
 *  - update_status
 *  - add_note
 *  - send_email
 *  - delete
 *  - get
 *
 * @package SwapDesign
 */

define('SWAP_ROOT', true);
define('IS_ADMIN', true);
define('IS_AJAX', true);

require_once __DIR__ . '/../../includes/config/site.php';
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/config/environment.php';
require_once __DIR__ . '/../../includes/functions/logger.php';
require_once __DIR__ . '/../../includes/config/error-handler.php';
require_once __DIR__ . '/../../includes/functions/helpers.php';
require_once __DIR__ . '/../../includes/functions/sanitize.php';
require_once __DIR__ . '/../../includes/functions/security.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/auth/Auth.php';
require_once __DIR__ . '/../../includes/settings/SettingsManager.php';
require_once __DIR__ . '/../../includes/content/LeadManager.php';
require_once __DIR__ . '/../../includes/content/EmailManager.php';

Session::start();

if (empty($_SESSION['user_id'])) {
    respond(false, 'Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed', 405);
}

if (empty($_POST['action'])) {
    respond(false, 'Missing action', 400);
}

/* CSRF protection */
$token = $_POST['token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($token)) {
    respond(false, 'Invalid security token', 403);
}

$action      = $_POST['action'];
$leadManager = new LeadManager();
$emailManager = new EmailManager();

switch ($action) {

    case 'update_status':
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if ($leadId <= 0) respond(false, 'Invalid lead ID');
        if (!in_array($status, LeadManager::STATUSES, true)) respond(false, 'Invalid status');

        $leadManager->updateStatus($leadId, $status);
        respond(true, 'Status updated');

    case 'add_note':
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $note   = sanitizeString($_POST['note'] ?? '');

        if ($leadId <= 0) respond(false, 'Invalid lead ID');
        if (empty($note)) respond(false, 'Note cannot be empty');

        $leadManager->addNote($leadId, $_SESSION['user_id'], $note);
        respond(true, 'Note added');

    case 'send_email':
        $leadId    = (int)($_POST['lead_id'] ?? 0);
        $emailType = $_POST['email_type'] ?? '';

        if ($leadId <= 0) respond(false, 'Invalid lead ID');
        if (!in_array($emailType, ['admin', 'user'], true)) respond(false, 'Invalid email type');

        $lead = $leadManager->getById($leadId);
        if (!$lead) respond(false, 'Lead not found');

        $sent = false;
        if ($emailType === 'admin') {
            $sent = $emailManager->sendAdminNotification($lead);
        } else {
            $sent = $emailManager->sendUserConfirmation($lead);
        }

        if ($sent) {
            $leadManager->markEmailed($leadId);
            respond(true, 'Email sent');
        } else {
            respond(false, 'Email delivery failed');
        }

    case 'delete':
        $leadId = (int)($_POST['lead_id'] ?? 0);

        if ($leadId <= 0) respond(false, 'Invalid lead ID');

        $leadManager->delete($leadId);
        respond(true, 'Lead deleted');

    case 'get':
        $leadId = (int)($_POST['lead_id'] ?? 0);

        if ($leadId <= 0) respond(false, 'Invalid lead ID');

        $lead = $leadManager->getById($leadId);
        if (!$lead) respond(false, 'Lead not found');

        $notes    = $leadManager->getNotes($leadId);
        $emailLog = $emailManager->getLogForLead($leadId);

        respond(true, '', 200, [
            'lead'      => $lead,
            'notes'     => $notes,
            'email_log' => $emailLog,
        ]);

    default:
        respond(false, 'Unknown action');
}

function respond(bool $ok, string $message = '', int $httpCode = 200, array $extra = []): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
