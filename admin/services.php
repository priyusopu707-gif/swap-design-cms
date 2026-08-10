<?php
/**
 * Swap Design - Services Admin List
 *
 * Service list with drag-drop reorder, search, category/status
 * filters, bulk actions, and duplicate/delete operations.
 *
 * @package SwapDesign
 */

require __DIR__ . '/includes/init.php';
Auth::require();

$manager = new ServiceManager();

$statusFilter  = $_GET['status'] ?? '';
$categoryFilter= $_GET['category'] ?? '';
$searchQuery   = $_GET['search'] ?? '';

$filters = [];
if ($statusFilter)  $filters['status']   = $statusFilter;
if ($categoryFilter)$filters['category'] = $categoryFilter;
if ($searchQuery)   $filters['search']   = $searchQuery;

$services   = $manager->getAll($filters);
$categories = $manager->getCategories();

/* Handle actions */
$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $serviceId = (int)($_POST['service_id'] ?? 0);

    try {
        if ($action === 'duplicate' && $serviceId) {
            $newId = $manager->duplicate($serviceId);
            $message = 'Service duplicated.';
            $msgType = 'success';
        } elseif ($action === 'delete' && $serviceId) {
            $manager->delete($serviceId);
            $message = 'Service deleted.';
            $msgType = 'success';
        } elseif ($action === 'status' && $serviceId) {
            $newStatus = $_POST['status_value'] ?? 'published';
            $manager->setStatus($serviceId, $newStatus);
            $message = 'Status updated.';
            $msgType = 'success';

            header('Location: /admin/services.php?' . http_build_query(['status' => $statusFilter, 'category' => $categoryFilter, 'search' => $searchQuery]));
            exit;
        } elseif ($action === 'bulk') {
            $ids    = array_map('intval', explode(',', $_POST['ids'] ?? ''));
            $bulkAction = $_POST['bulk_action'] ?? '';
            $count = 0;
            foreach ($ids as $id) {
                if ($bulkAction === 'publish') { $manager->setStatus($id, 'published'); $count++; }
                elseif ($bulkAction === 'draft') { $manager->setStatus($id, 'draft'); $count++; }
                elseif ($bulkAction === 'archive') { $manager->setStatus($id, 'archived'); $count++; }
                elseif ($bulkAction === 'delete') { $manager->delete($id); $count++; }
            }
            $message = "{$count} service(s) updated.";
            $msgType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $msgType = 'error';
    }

    /* Refresh after POST */
    if ($action !== 'status') {
        header('Location: /admin/services.php?' . http_build_query(['status' => $statusFilter, 'category' => $categoryFilter, 'search' => $searchQuery]));
        exit;
    }
}

$currentSection = 'services';
$pageTitle = 'Services';
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<link rel="stylesheet" href="/admin/assets/css/services-admin.css?v=1">

<div class="admin-page">

    <div class="admin-page__header">
        <h1 class="admin-page__title"><?php echo esc($pageTitle); ?></h1>
        <div class="admin-page__actions">
            <a href="/admin/services-edit.php" class="btn btn--primary">Add New Service</a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert--<?php echo $msgType; ?>" role="alert"><?php echo esc($message); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="get" action="/admin/services.php" class="svc-list__filters" role="search">
        <input type="text" name="search" value="<?php echo esc($searchQuery); ?>" placeholder="Search services..." class="svc-list__search" aria-label="Search services">
        <select name="status" class="svc-list__select" aria-label="Filter by status">
            <option value="">All Statuses</option>
            <option value="published"<?php echo $statusFilter === 'published' ? ' selected' : ''; ?>>Published</option>
            <option value="draft"<?php echo $statusFilter === 'draft' ? ' selected' : ''; ?>>Draft</option>
            <option value="archived"<?php echo $statusFilter === 'archived' ? ' selected' : ''; ?>>Archived</option>
        </select>
        <?php if ($categories): ?>
        <select name="category" class="svc-list__select" aria-label="Filter by category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo esc($cat); ?>"<?php echo $categoryFilter === $cat ? ' selected' : ''; ?>><?php echo esc($cat); ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <button type="submit" class="btn btn--sm btn--primary">Filter</button>
        <?php if ($statusFilter || $categoryFilter || $searchQuery): ?>
        <a href="/admin/services.php" class="btn btn--sm btn--outline">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Services Table -->
    <form method="post" action="/admin/services.php" id="svc-list-form">
        <input type="hidden" name="action" value="bulk">
        <input type="hidden" name="ids" id="svc-bulk-ids" value="">

        <div class="svc-list__toolbar" id="svc-toolbar" style="display:none;">
            <span class="svc-list__selected-count"><span id="svc-selected-count">0</span> selected</span>
            <select name="bulk_action" class="svc-list__select">
                <option value="">Bulk Actions</option>
                <option value="publish">Set Published</option>
                <option value="draft">Set Draft</option>
                <option value="archive">Set Archived</option>
                <option value="delete">Delete</option>
            </select>
            <button type="submit" class="btn btn--sm btn--primary">Apply</button>
        </div>

        <div class="svc-table__wrap">
            <table class="svc-table" id="svc-table">
                <thead>
                    <tr>
                        <th class="svc-table__check"><input type="checkbox" id="svc-select-all" aria-label="Select all"></th>
                        <th class="svc-table__drag"></th>
                        <th>Service</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="svc-table-body">
                    <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="6" class="svc-table__empty">No services found. <a href="/admin/services-edit.php">Create your first service</a>.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($services as $svc):
                        $svcId     = (int)$svc['id'];
                        $title     = esc($svc['title']);
                        $slug      = esc($svc['slug']);
                        $category  = esc($svc['category'] ?? '—');
                        $status    = $svc['status'];
                        $image     = esc($svc['featured_image'] ?? '');
                    ?>
                    <tr class="svc-table__row" data-service-id="<?php echo $svcId; ?>" draggable="true">
                        <td class="svc-table__check">
                            <input type="checkbox" name="svc_check[]" value="<?php echo $svcId; ?>" class="svc-checkbox" aria-label="Select <?php echo $title; ?>">
                        </td>
                        <td class="svc-table__drag"><span class="svc-drag-handle" aria-label="Drag to reorder">&#x2630;</span></td>
                        <td>
                            <div class="svc-table__service">
                                <?php if ($image): ?>
                                <img src="<?php echo $image; ?>" alt="" class="svc-table__thumb" width="40" height="40" loading="lazy">
                                <?php endif; ?>
                                <div>
                                    <a href="/admin/services-edit.php?id=<?php echo $svcId; ?>" class="svc-table__title"><?php echo $title; ?></a>
                                    <span class="svc-table__slug">/services/<?php echo $slug; ?></span>
                                </div>
                            </div>
                        </td>
                        <td><span class="svc-table__cat"><?php echo $category; ?></span></td>
                        <td>
                            <form method="post" action="/admin/services.php" class="svc-status-form">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="service_id" value="<?php echo $svcId; ?>">
                                <input type="hidden" name="status_value" value="<?php echo $status === 'published' ? 'draft' : ($status === 'draft' ? 'archived' : 'published'); ?>">
                                <button type="submit" class="svc-status-badge svc-status--<?php echo $status; ?>">
                                    <?php echo ucfirst($status); ?>
                                </button>
                            </form>
                        </td>
                        <td class="svc-table__actions">
                            <a href="/services/<?php echo $slug; ?>" target="_blank" class="svc-action-btn" title="View">&#x1F441;</a>
                            <a href="/admin/services-edit.php?id=<?php echo $svcId; ?>" class="svc-action-btn" title="Edit">&#x270E;</a>
                            <form method="post" action="/admin/services.php" style="display:inline;">
                                <input type="hidden" name="action" value="duplicate">
                                <input type="hidden" name="service_id" value="<?php echo $svcId; ?>">
                                <button type="submit" class="svc-action-btn" title="Duplicate" onclick="return confirm('Duplicate this service?')">&#x1F4CB;</button>
                            </form>
                            <form method="post" action="/admin/services.php" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="service_id" value="<?php echo $svcId; ?>">
                                <button type="submit" class="svc-action-btn svc-action-btn--danger" title="Delete" onclick="return confirm('Delete this service permanently? This will also remove all features, benefits, process steps, FAQs, and relationships.')">&#x1F5D1;</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>

</div>

<script src="/admin/assets/js/services-admin.js?v=1"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>