<?php
/**
 * Swap Design - Lead Management Dashboard
 *
 * Lead list with search, filters, status management, notes,
 * CSV export, and detail view.
 *
 * @package SwapDesign
 */

require __DIR__ . '/includes/init.php';
Auth::require();

$leadManager   = new LeadManager();
$emailManager  = new EmailManager();
$currentSection = 'leads';
$pageTitle      = 'Lead Management';

$filterStatus   = $_GET['status'] ?? '';
$filterService  = $_GET['service_id'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to'] ?? '';
$searchQuery    = $_GET['q'] ?? '';
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 20;

$filters = array_filter([
    'status'     => $filterStatus,
    'service_id' => $filterService,
    'date_from'  => $filterDateFrom,
    'date_to'    => $filterDateTo,
]);

/* Detail view */
$leadId = $_GET['id'] ?? null;
if ($leadId) {
    $lead = $leadManager->getById((int)$leadId);
    if (!$lead) {
        header('Location: /admin/leads.php');
        exit;
    }
    $notes       = $leadManager->getNotes($lead['id']);
    $emailLog    = $emailManager->getLogForLead($lead['id']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postAction = $_POST['action'] ?? '';
        if ($postAction === 'update_status' && !empty($_POST['status'])) {
            $leadManager->updateStatus($lead['id'], $_POST['status']);
            $lead = $leadManager->getById($lead['id']);
        } elseif ($postAction === 'add_note' && !empty($_POST['note'])) {
            $leadManager->addNote($lead['id'], $_SESSION['user_id'], sanitizeString($_POST['note']));
            header('Location: /admin/leads.php?id=' . $lead['id']);
            exit;
        } elseif ($postAction === 'send_email') {
            if ($_POST['email_type'] === 'admin') {
                $emailManager->sendAdminNotification($lead);
            } else {
                $emailManager->sendUserConfirmation($lead);
            }
            if ($_POST['email_type'] === 'admin' || $_POST['email_type'] === 'user') {
                $leadManager->markEmailed($lead['id']);
            }
            header('Location: /admin/leads.php?id=' . $lead['id']);
            exit;
        }
    }
} else {
    /* List view */
    if ($searchQuery) {
        $leads     = $leadManager->search($searchQuery, $filters, $page, $perPage);
        $totalLeads = $leadManager->searchCount($searchQuery, $filters);
    } else {
        $leads     = $leadManager->getAll($filters, $page, $perPage);
        $totalLeads = $leadManager->count($filters);
    }
    $totalPages  = max(1, ceil($totalLeads / $perPage));
    $statusCounts = $leadManager->statusCounts();

    /* CSV export */
    if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
        $exportLeads = $leadManager->exportCsv($filters);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=leads-' . date('Y-m-d') . '.csv');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['ID', 'Date', 'Name', 'Email', 'Phone', 'Company', 'Service', 'Budget', 'Timeline', 'Subject', 'Message', 'Source', 'Status', 'IP']);
        foreach ($exportLeads as $l) {
            $svcName = '';
            if ($l['service_id']) {
                $db = Database::getInstance();
                $svc = $db->fetch("SELECT title FROM services WHERE id = ?", [(int)$l['service_id']]);
                $svcName = $svc ? $svc['title'] : '';
            }
            fputcsv($fp, [
                $l['id'], $l['created_at'], $l['full_name'], $l['email'], $l['phone'],
                $l['company'], $svcName, $l['budget'], $l['timeline'], $l['subject'],
                $l['message'], $l['source_page'], $l['status'], $l['ip_address'],
            ]);
        }
        fclose($fp);
        exit;
    }
}

$db = Database::getInstance();
$servicesList = $db->fetchAll("SELECT id, title FROM services WHERE status = 'published' ORDER BY title ASC");
?><!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/leads.css">
</head>
<body class="admin-body">
    <a href="#admin-content" class="admin-skip-link">Skip to main content</a>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <?php require __DIR__ . '/includes/topbar.php'; ?>
    <main class="admin-main">
        <div class="admin-content" id="admin-content">
            <?php if ($leadId): ?>
                <!-- Lead Detail View -->
                <div class="admin-page-header">
                    <a href="/admin/leads.php" class="btn btn--ghost">&larr; Back to Leads</a>
                    <h1>Lead #<?php echo (int)$lead['id']; ?> - <?php echo esc($lead['full_name']); ?></h1>
                </div>

                <div class="lead-detail">
                    <div class="lead-detail__main">
                        <div class="lead-detail__card">
                            <h3 class="lead-detail__card-title">Lead Information</h3>
                            <table class="lead-detail__table">
                                <tr><th>Date:</th><td><?php echo esc($lead['created_at']); ?></td></tr>
                                <tr><th>Name:</th><td><?php echo esc($lead['full_name']); ?></td></tr>
                                <tr><th>Email:</th><td><a href="mailto:<?php echo esc($lead['email']); ?>"><?php echo esc($lead['email']); ?></a></td></tr>
                                <tr><th>Phone:</th><td><a href="tel:<?php echo esc($lead['phone']); ?>"><?php echo esc($lead['phone']); ?></a></td></tr>
                                <tr><th>Company:</th><td><?php echo esc($lead['company']); ?></td></tr>
                                <tr><th>Service:</th><td><?php echo esc($svcName ?? 'N/A'); ?></td></tr>
                                <tr><th>Budget:</th><td><?php echo esc($lead['budget']); ?></td></tr>
                                <tr><th>Timeline:</th><td><?php echo esc($lead['timeline']); ?></td></tr>
                                <tr><th>Subject:</th><td><?php echo esc($lead['subject']); ?></td></tr>
                                <tr><th>Source:</th><td><?php echo esc($lead['source_page']); ?></td></tr>
                                <tr><th>Referrer:</th><td><?php echo esc($lead['referrer_url']); ?></td></tr>
                                <tr><th>IP Address:</th><td><?php echo esc($lead['ip_address']); ?></td></tr>
                                <tr><th>Device:</th><td><?php echo esc($lead['device_type']); ?></td></tr>
                                <tr><th>Consent:</th><td><?php echo $lead['consent_given'] ? 'Yes' : 'No'; ?></td></tr>
                            </table>
                        </div>

                        <div class="lead-detail__card">
                            <h3 class="lead-detail__card-title">Message</h3>
                            <div class="lead-detail__message"><?php echo nl2br(esc($lead['message'])); ?></div>
                        </div>

                        <?php if (!empty($lead['uploaded_files'])): ?>
                        <div class="lead-detail__card">
                            <h3 class="lead-detail__card-title">Uploaded Files</h3>
                            <ul class="lead-detail__files">
                                <?php foreach ($lead['uploaded_files'] as $file): ?>
                                <li><a href="/uploads/leads/<?php echo esc($file['stored_name']); ?>" target="_blank"><?php echo esc($file['original_name']); ?> (<?php echo round($file['size'] / 1024, 1); ?> KB)</a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- Notes -->
                        <div class="lead-detail__card">
                            <h3 class="lead-detail__card-title">Notes (<?php echo count($notes); ?>)</h3>
                            <?php foreach ($notes as $note): ?>
                            <div class="lead-note">
                                <div class="lead-note__meta">
                                    <span class="lead-note__author"><?php echo esc($note['username'] ?? 'System'); ?></span>
                                    <span class="lead-note__date"><?php echo esc($note['created_at']); ?></span>
                                </div>
                                <div class="lead-note__text"><?php echo nl2br(esc($note['note'])); ?></div>
                            </div>
                            <?php endforeach; ?>

                            <form method="POST" class="lead-detail__note-form">
                                <input type="hidden" name="action" value="add_note">
                                <textarea name="note" rows="3" class="lead-detail__note-textarea" placeholder="Add a note..." required></textarea>
                                <button type="submit" class="btn btn--primary">Add Note</button>
                            </form>
                        </div>

                        <!-- Email Log -->
                        <div class="lead-detail__card">
                            <h3 class="lead-detail__card-title">Email History</h3>
                            <?php if ($emailLog): ?>
                            <table class="lead-detail__table">
                                <thead><tr><th>Date</th><th>To</th><th>Subject</th><th>Template</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ($emailLog as $el): ?>
                                <tr>
                                    <td><?php echo esc($el['created_at']); ?></td>
                                    <td><?php echo esc($el['recipient']); ?></td>
                                    <td><?php echo esc($el['subject']); ?></td>
                                    <td><?php echo esc($el['template_key']); ?></td>
                                    <td><span class="lead-status lead-status--<?php echo $el['status']; ?>"><?php echo esc($el['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <p class="lead-detail__empty">No emails sent yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="lead-detail__sidebar">
                        <!-- Status -->
                        <div class="lead-detail__card">
                            <h3 class="lead-detail__card-title">Status</h3>
                            <span class="lead-status lead-status--<?php echo esc($lead['status']); ?>"><?php echo esc(LeadManager::STATUS_LABELS[$lead['status']] ?? $lead['status']); ?></span>

                            <form method="POST" class="lead-detail__status-form">
                                <input type="hidden" name="action" value="update_status">
                                <select name="status" class="lead-detail__status-select">
                                    <?php foreach (LeadManager::STATUSES as $st): ?>
                                    <option value="<?php echo esc($st); ?>" <?php echo $lead['status'] === $st ? 'selected' : ''; ?>><?php echo esc(LeadManager::STATUS_LABELS[$st]); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn--primary">Update Status</button>
                            </form>
                        </div>

                        <!-- Quick Actions -->
                        <div class="lead-detail__card">
                            <h3 class="lead-detail__card-title">Quick Actions</h3>
                            <form method="POST" class="lead-detail__actions">
                                <input type="hidden" name="action" value="send_email">
                                <input type="hidden" name="email_type" value="admin">
                                <button type="submit" class="btn btn--secondary btn--block">Send Admin Notification</button>
                            </form>
                            <form method="POST" class="lead-detail__actions">
                                <input type="hidden" name="action" value="send_email">
                                <input type="hidden" name="email_type" value="user">
                                <button type="submit" class="btn btn--secondary btn--block">Send User Confirmation</button>
                            </form>
                            <?php if ($lead['phone']): ?>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $lead['phone']); ?>?text=Hi%20<?php echo urlencode($lead['full_name']); ?>%2C%20regarding%20your%20enquiry%E2%80%A6" target="_blank" rel="noopener" class="btn btn--whatsapp btn--block">Chat on WhatsApp</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Lead List -->
                <div class="admin-page-header">
                    <h1><?php echo esc($pageTitle); ?></h1>
                    <div class="admin-page-header__actions">
                        <a href="/admin/leads.php?export=csv<?php echo $filterStatus ? '&status=' . esc($filterStatus) : ''; ?>" class="btn btn--secondary">Export CSV</a>
                    </div>
                </div>

                <!-- Status Tabs -->
                <div class="lead-tabs">
                    <a href="/admin/leads.php" class="lead-tab <?php echo empty($filterStatus) && empty($searchQuery) ? 'lead-tab--active' : ''; ?>">All <span class="lead-tab__count"><?php echo (int)array_sum($statusCounts); ?></span></a>
                    <?php foreach (LeadManager::STATUSES as $st): ?>
                    <?php if ($st === 'archived' && $statusCounts[$st] === 0) continue; ?>
                    <a href="/admin/leads.php?status=<?php echo esc($st); ?>" class="lead-tab <?php echo $filterStatus === $st ? 'lead-tab--active' : ''; ?>">
                        <?php echo esc(LeadManager::STATUS_LABELS[$st]); ?>
                        <span class="lead-tab__count"><?php echo (int)$statusCounts[$st]; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Search & Filters -->
                <form class="lead-filters" method="GET" action="/admin/leads.php">
                    <input type="hidden" name="status" value="<?php echo esc($filterStatus); ?>">
                    <input type="text" name="q" class="lead-filters__search" placeholder="Search leads..." value="<?php echo esc($searchQuery); ?>">
                    <select name="service_id" class="lead-filters__select">
                        <option value="">All Services</option>
                        <?php foreach ($servicesList as $svc): ?>
                        <option value="<?php echo (int)$svc['id']; ?>" <?php echo $filterService === (string)$svc['id'] ? 'selected' : ''; ?>><?php echo esc($svc['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="date_from" class="lead-filters__date" value="<?php echo esc($filterDateFrom); ?>" placeholder="From">
                    <input type="date" name="date_to" class="lead-filters__date" value="<?php echo esc($filterDateTo); ?>" placeholder="To">
                    <button type="submit" class="btn btn--primary">Filter</button>
                    <?php if ($searchQuery || $filterStatus || $filterService || $filterDateFrom || $filterDateTo): ?>
                    <a href="/admin/leads.php" class="btn btn--ghost">Clear</a>
                    <?php endif; ?>
                </form>

                <!-- Leads Table -->
                <?php if (empty($leads)): ?>
                <div class="lead-empty">
                    <p>No leads found.</p>
                </div>
                <?php else: ?>
                <div class="lead-table-wrap">
                    <table class="lead-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Service</th>
                                <th>Budget</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $l): ?>
                            <?php
                                $svcLabel = '';
                                if ($l['service_id']) {
                                    $found = array_filter($servicesList, function($s) use ($l) { return (int)$s['id'] === (int)$l['service_id']; });
                                    $svcLabel = $found ? reset($found)['title'] : '';
                                }
                            ?>
                            <tr class="lead-row" onclick="window.location='/admin/leads.php?id=<?php echo (int)$l['id']; ?>'" style="cursor:pointer">
                                <td>#<?php echo (int)$l['id']; ?></td>
                                <td><?php echo esc(substr($l['created_at'], 0, 10)); ?></td>
                                <td><strong><?php echo esc($l['full_name']); ?></strong></td>
                                <td><?php echo esc($l['email']); ?></td>
                                <td><?php echo esc($l['phone']); ?></td>
                                <td><?php echo esc($svcLabel); ?></td>
                                <td><?php echo esc($l['budget']); ?></td>
                                <td><span class="lead-status lead-status--<?php echo esc($l['status']); ?>"><?php echo esc(LeadManager::STATUS_LABELS[$l['status']] ?? $l['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="lead-pagination">
                    <?php
                    $queryStr = '';
                    if ($filterStatus) $queryStr .= '&status=' . urlencode($filterStatus);
                    if ($searchQuery) $queryStr .= '&q=' . urlencode($searchQuery);
                    if ($filterService) $queryStr .= '&service_id=' . urlencode($filterService);
                    if ($filterDateFrom) $queryStr .= '&date_from=' . urlencode($filterDateFrom);
                    if ($filterDateTo) $queryStr .= '&date_to=' . urlencode($filterDateTo);
                    ?>
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1 . $queryStr; ?>" class="lead-pagination__link">&laquo; Prev</a>
                    <?php endif; ?>
                    <span class="lead-pagination__info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1 . $queryStr; ?>" class="lead-pagination__link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
