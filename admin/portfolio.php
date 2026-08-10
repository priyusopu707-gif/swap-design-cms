<?php
require __DIR__ . '/includes/init.php';
Auth::require();

$manager = new PortfolioManager();

$statusFilter   = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$searchQuery    = $_GET['search'] ?? '';

$filters = [];
if ($statusFilter)   $filters['status']   = $statusFilter;
if ($categoryFilter) $filters['category'] = $categoryFilter;
if ($searchQuery)    $filters['search']   = $searchQuery;

$items      = $manager->getAll($filters);
$categories = $manager->getCategories();

$message = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $projectId = (int)($_POST['project_id'] ?? 0);

    try {
        if ($action === 'duplicate' && $projectId) {
            $manager->duplicate($projectId); $message = 'Project duplicated.'; $msgType = 'success';
        } elseif ($action === 'delete' && $projectId) {
            $manager->delete($projectId); $message = 'Project deleted.'; $msgType = 'success';
        } elseif ($action === 'status' && $projectId) {
            $manager->setStatus($projectId, $_POST['status_value'] ?? 'published');
            header('Location: /admin/portfolio.php?' . http_build_query(array_filter(['status' => $statusFilter, 'category' => $categoryFilter, 'search' => $searchQuery])));
            exit;
        } elseif ($action === 'featured' && $projectId) {
            $manager->setFeatured($projectId, ($_POST['featured_value'] ?? '1') === '1');
            header('Location: /admin/portfolio.php?' . http_build_query(array_filter(['status' => $statusFilter, 'category' => $categoryFilter, 'search' => $searchQuery])));
            exit;
        } elseif ($action === 'bulk') {
            $ids = array_map('intval', explode(',', $_POST['ids'] ?? ''));
            $bulk = $_POST['bulk_action'] ?? ''; $c = 0;
            foreach ($ids as $id) {
                if ($bulk === 'publish') { $manager->setStatus($id, 'published'); $c++; }
                elseif ($bulk === 'draft') { $manager->setStatus($id, 'draft'); $c++; }
                elseif ($bulk === 'archive') { $manager->setStatus($id, 'archived'); $c++; }
                elseif ($bulk === 'delete') { $manager->delete($id); $c++; }
            }
            $message = "{$c} project(s) updated."; $msgType = 'success';
        }
    } catch (Exception $e) { $message = 'Error: ' . $e->getMessage(); $msgType = 'error'; }

    if ($action !== 'status' && $action !== 'featured') {
        header('Location: /admin/portfolio.php?' . http_build_query(array_filter(['status' => $statusFilter, 'category' => $categoryFilter, 'search' => $searchQuery])));
        exit;
    }
}

$currentSection = 'portfolio'; $pageTitle = 'Portfolio';
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<link rel="stylesheet" href="/admin/assets/css/services-admin.css?v=1">

<div class="admin-page">
<div class="admin-page__header">
    <h1 class="admin-page__title"><?php echo esc($pageTitle); ?></h1>
    <div class="admin-page__actions"><a href="/admin/portfolio-edit.php" class="btn btn--primary">Add New Project</a></div>
</div>
<?php if ($message): ?><div class="alert alert--<?php echo $msgType; ?>" role="alert"><?php echo esc($message); ?></div><?php endif; ?>
<form method="get" action="/admin/portfolio.php" class="svc-list__filters" role="search">
    <input type="text" name="search" value="<?php echo esc($searchQuery); ?>" placeholder="Search projects..." class="svc-list__search" aria-label="Search">
    <select name="status" class="svc-list__select" aria-label="Status"><option value="">All Statuses</option>
        <option value="published"<?php echo $statusFilter==='published'?' selected':''; ?>>Published</option>
        <option value="draft"<?php echo $statusFilter==='draft'?' selected':''; ?>>Draft</option>
        <option value="archived"<?php echo $statusFilter==='archived'?' selected':''; ?>>Archived</option>
    </select>
    <?php if ($categories): ?><select name="category" class="svc-list__select" aria-label="Category"><option value="">All Categories</option>
        <?php foreach ($categories as $c): ?><option value="<?php echo esc($c); ?>"<?php echo $categoryFilter===$c?' selected':''; ?>><?php echo esc($c); ?></option><?php endforeach; ?>
    </select><?php endif; ?>
    <button type="submit" class="btn btn--sm btn--primary">Filter</button>
    <?php if ($statusFilter||$categoryFilter||$searchQuery): ?><a href="/admin/portfolio.php" class="btn btn--sm btn--outline">Clear</a><?php endif; ?>
</form>
<form method="post" action="/admin/portfolio.php" id="pf-list-form">
    <input type="hidden" name="action" value="bulk"><input type="hidden" name="ids" id="pf-bulk-ids" value="">
    <div class="svc-list__toolbar" id="pf-toolbar" style="display:none">
        <span class="svc-list__selected-count"><span id="pf-selected-count">0</span> selected</span>
        <select name="bulk_action" class="svc-list__select"><option value="">Bulk Actions</option><option value="publish">Set Published</option><option value="draft">Set Draft</option><option value="archive">Set Archived</option><option value="delete">Delete</option></select>
        <button type="submit" class="btn btn--sm btn--primary">Apply</button>
    </div>
    <div class="svc-table__wrap"><table class="svc-table" id="pf-table"><thead><tr>
        <th class="svc-table__check"><input type="checkbox" id="pf-select-all" aria-label="Select all"></th>
        <th class="svc-table__drag"></th><th>Project</th><th>Category</th><th>Featured</th><th>Status</th><th>Actions</th>
    </tr></thead><tbody id="pf-table-body">
    <?php if (empty($items)): ?><tr><td colspan="7" class="svc-table__empty">No projects found. <a href="/admin/portfolio-edit.php">Add your first project</a>.</td></tr>
    <?php else: foreach ($items as $p): $pid=(int)$p['id']; ?>
    <tr class="svc-table__row" data-project-id="<?php echo $pid; ?>" draggable="true">
        <td class="svc-table__check"><input type="checkbox" name="pf_check[]" value="<?php echo $pid; ?>" class="svc-checkbox" aria-label="Select"></td>
        <td class="svc-table__drag"><span class="svc-drag-handle" aria-label="Drag">&#x2630;</span></td>
        <td><div class="svc-table__service"><?php if($p['image_url']): ?><img src="<?php echo esc($p['image_url']); ?>" alt="" class="svc-table__thumb" width="40" height="40" loading="lazy"><?php endif; ?><div><a href="/admin/portfolio-edit.php?id=<?php echo $pid; ?>" class="svc-table__title"><?php echo esc($p['title']); ?></a><span class="svc-table__slug">/portfolio/<?php echo esc($p['slug']); ?></span></div></div></td>
        <td><span class="svc-table__cat"><?php echo esc($p['category']??'—'); ?></span></td>
        <td>
            <form method="post" action="/admin/portfolio.php" class="svc-status-form">
                <input type="hidden" name="action" value="featured"><input type="hidden" name="project_id" value="<?php echo $pid; ?>">
                <input type="hidden" name="featured_value" value="<?php echo $p['is_featured']?'0':'1'; ?>">
                <button type="submit" class="svc-status-badge svc-status--<?php echo $p['is_featured']?'published':'draft'; ?>"><?php echo $p['is_featured']?'&#9733; Featured':'&#9734; Normal'; ?></button>
            </form>
        </td>
        <td>
            <form method="post" action="/admin/portfolio.php" class="svc-status-form">
                <input type="hidden" name="action" value="status"><input type="hidden" name="project_id" value="<?php echo $pid; ?>">
                <?php $nextStatus = $p['status'] === 'draft' ? 'published' : ($p['status'] === 'published' ? 'archived' : 'draft'); ?>
                <input type="hidden" name="status_value" value="<?php echo $nextStatus; ?>">
                <button type="submit" class="svc-status-badge svc-status--<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></button>
            </form>
        </td>
        <td class="svc-table__actions">
            <a href="/portfolio/<?php echo esc($p['slug']); ?>" target="_blank" class="svc-action-btn" title="View">&#x1F441;</a>
            <a href="/admin/portfolio-edit.php?id=<?php echo $pid; ?>" class="svc-action-btn" title="Edit">&#x270E;</a>
            <form method="post" action="/admin/portfolio.php" style="display:inline"><input type="hidden" name="action" value="duplicate"><input type="hidden" name="project_id" value="<?php echo $pid; ?>"><button type="submit" class="svc-action-btn" title="Duplicate" onclick="return confirm('Duplicate?')">&#x1F4CB;</button></form>
            <form method="post" action="/admin/portfolio.php" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="project_id" value="<?php echo $pid; ?>"><button type="submit" class="svc-action-btn svc-action-btn--danger" title="Delete" onclick="return confirm('Delete permanently?')">&#x1F5D1;</button></form>
        </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody></table></div>
</form>
</div>

<script src="/admin/assets/js/portfolio-admin.js?v=1"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>