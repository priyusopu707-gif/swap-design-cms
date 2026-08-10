<?php
/**
 * Swap Design - Services AJAX Handler
 *
 * Handles sub-item CRUD, reorder, and relation linking/unlinking
 * for the services admin editor.
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
require_once __DIR__ . '/../../includes/content/ServiceManager.php';

Session::start();

if (empty($_SESSION['user_id'])) {
    respond(false, 'Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed', 405);
}

/* CSRF protection */
$token = $_POST['token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($token)) {
    respond(false, 'Invalid security token', 403);
}

$action  = $_POST['action'] ?? '';
$manager = new ServiceManager();

try {
    switch ($action) {

        /* ---- Sub-item CRUD ---- */
        case 'save_sub':
            $type      = $_POST['type'] ?? '';         // features, benefits, process_steps, faqs
            $serviceId = (int)($_POST['service_id'] ?? 0);
            $subId     = $_POST['sub_id'] ?? null;
            $subId     = $subId ? (int)$subId : null;
            $data      = $_POST['data'] ?? [];

            if (!$serviceId || !$type) respond(false, 'Missing parameters');

            $id = 0;
            if ($type === 'features')         $id = $manager->saveFeature($serviceId, $data, $subId);
            elseif ($type === 'benefits')      $id = $manager->saveBenefit($serviceId, $data, $subId);
            elseif ($type === 'process_steps') $id = $manager->saveProcessStep($serviceId, $data, $subId);
            elseif ($type === 'faqs')          $id = $manager->saveFaq($serviceId, $data, $subId);
            else respond(false, 'Unknown sub type');

            respond(true, 'Saved', 200, ['id' => $id]);

        case 'delete_sub':
            $type  = $_POST['type'] ?? '';
            $subId = (int)($_POST['sub_id'] ?? 0);
            if (!$subId || !$type) respond(false, 'Missing parameters');

            if ($type === 'features')         $manager->deleteFeature($subId);
            elseif ($type === 'benefits')      $manager->deleteBenefit($subId);
            elseif ($type === 'process_steps') $manager->deleteProcessStep($subId);
            elseif ($type === 'faqs')          $manager->deleteFaq($subId);
            else respond(false, 'Unknown sub type');

            respond(true, 'Deleted');

        case 'reorder_subs':
            $type      = $_POST['type'] ?? '';
            $serviceId = (int)($_POST['service_id'] ?? 0);
            $order     = explode(',', $_POST['order'] ?? '');

            if (!$serviceId || !$type) respond(false, 'Missing parameters');

            $table = 'service_' . $type;
            foreach ($order as $idx => $id) {
                $id = (int)$id;
                if ($id <= 0) continue;
                Database::getInstance()->update($table, ['sort_order' => $idx], 'id = ? AND service_id = ?', [$id, $serviceId]);
            }

            respond(true, 'Order updated');

        /* ---- Service list reorder ---- */
        case 'reorder_services':
            $order = explode(',', $_POST['order'] ?? '');
            $order = array_filter(array_map('intval', $order), function ($id) { return $id > 0; });
            if (empty($order)) respond(false, 'Invalid order');
            $manager->reorder($order);
            respond(true, 'Order updated');

        /* ---- Relations ---- */
        case 'link_relation':
            $type      = $_POST['type'] ?? '';   // portfolio, testimonial, block
            $serviceId = (int)($_POST['service_id'] ?? 0);
            $relationId= (int)($_POST['relation_id'] ?? 0);

            if (!$serviceId || !$relationId || !$type) respond(false, 'Missing parameters');

            if ($type === 'portfolio')   $manager->linkPortfolio($serviceId, $relationId);
            elseif ($type === 'testimonial') $manager->linkTestimonial($serviceId, $relationId);
            elseif ($type === 'block')    $manager->linkBlock($serviceId, $relationId);
            else respond(false, 'Unknown relation type');

            respond(true, 'Linked');

        case 'unlink_relation':
            $type      = $_POST['type'] ?? '';
            $serviceId = (int)($_POST['service_id'] ?? 0);
            $relationId= (int)($_POST['relation_id'] ?? 0);

            if (!$serviceId || !$relationId || !$type) respond(false, 'Missing parameters');

            if ($type === 'portfolio')   $manager->unlinkPortfolio($serviceId, $relationId);
            elseif ($type === 'testimonial') $manager->unlinkTestimonial($serviceId, $relationId);
            elseif ($type === 'block')    $manager->unlinkBlock($serviceId, $relationId);
            else respond(false, 'Unknown relation type');

            respond(true, 'Unlinked');

        default:
            respond(false, 'Unknown action');
    }
} catch (Exception $e) {
    respond(false, $e->getMessage(), 500);
}

function respond(bool $ok, string $message = '', int $httpCode = 200, array $extra = []): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
