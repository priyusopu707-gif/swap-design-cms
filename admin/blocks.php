<?php
/**
 * Swap Design - Global Block Library Admin Page
 *
 * Manage reusable content blocks: create, edit, duplicate, delete.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Global Blocks';
$currentSection = 'blocks';

$blockEngine    = new BlockEngine();
$message        = '';
$messageType    = '';

$action   = $_GET['action'] ?? 'list';
$editId   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$typeFilter = $_GET['type'] ?? '';
$search    = $_GET['search'] ?? '';
$page      = max(1, (int)($_GET['p'] ?? 1));
$perPage   = 20;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $postAction = $_POST['action'] ?? '';

        switch ($postAction) {
            case 'create':
                $blockEngine->create([
                    'name'             => sanitizeString($_POST['name'] ?? ''),
                    'slug'             => sluggify($_POST['name'] ?? 'block'),
                    'block_type'       => sanitizeString($_POST['type'] ?? 'custom_html'),
                    'content'          => $_POST['content'] ?? '',
                    'status'           => sanitizeString($_POST['status'] ?? 'draft'),
                    'category'         => sanitizeString($_POST['category'] ?? ''),
                    'device_visibility' => sanitizeString($_POST['device_visibility'] ?? 'all'),
                ]);
                $message     = 'Block created.';
                $messageType = 'success';
                break;

            case 'update':
                $blockEngine->update($editId, [
                    'name'             => sanitizeString($_POST['name'] ?? ''),
                    'block_type'       => sanitizeString($_POST['type'] ?? 'custom_html'),
                    'content'          => $_POST['content'] ?? '',
                    'status'           => sanitizeString($_POST['status'] ?? 'draft'),
                    'category'         => sanitizeString($_POST['category'] ?? ''),
                    'device_visibility' => sanitizeString($_POST['device_visibility'] ?? 'all'),
                ]);
                $message     = 'Block updated.';
                $messageType = 'success';
                break;

            case 'delete':
                $blockEngine->delete($editId);
                $message     = 'Block deleted.';
                $messageType = 'success';
                break;

            case 'duplicate':
                $blockEngine->duplicate($editId);
                $message     = 'Block duplicated.';
                $messageType = 'success';
                break;
        }
    }
}

/* Load edit data */
$editBlock = null;
if ($action === 'edit' && $editId > 0) {
    $editBlock = $blockEngine->getBlock($editId);
    if ($editBlock) {
        $editContent = $editBlock['content'];
        if (is_array($editContent)) {
            $editContent = json_encode($editContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
    }
}

/* List blocks */
$filters = ['limit' => $perPage, 'offset' => ($page - 1) * $perPage];
if ($typeFilter) $filters['block_type'] = $typeFilter;
if ($search) $filters['search'] = $search;
$blocks    = $blockEngine->getBlocks($filters);
$totalBlocks = count($blockEngine->getBlocks());
$typeCounts  = $blockEngine->getTypeCounts();

$blockTypes = [
    'custom_html'  => 'Custom HTML',
    'cta'          => 'Call to Action',
    'hero'         => 'Hero Banner',
    'faq'          => 'FAQ',
    'stats'        => 'Statistics',
    'testimonials' => 'Testimonials',
    'banner'       => 'Banner',
    'gallery'      => 'Gallery',
    'pricing'      => 'Pricing',
    'team'         => 'Team',
    'timeline'     => 'Timeline',
    'features'     => 'Features',
    'form'         => 'Form',
    'map'          => 'Map',
    'video'        => 'Video',
];

$csrfToken  = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Global Block Library</h1>
    <div class="admin-page-header__actions">
        <a href="/admin/blocks.php?action=add" class="admin-btn admin-btn--primary">New Block</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Form -->
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title"><?php echo $action === 'edit' ? 'Edit Block' : 'New Block'; ?></h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/blocks.php<?php echo $action === 'edit' ? "?action=edit&id=$editId" : '?action=add'; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label" for="block_name">Block Name <span class="admin-required">*</span></label>
                    <input type="text" name="name" id="block_name" value="<?php echo esc($editBlock['name'] ?? ''); ?>" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label" for="block_type">Type</label>
                    <select name="type" id="block_type" class="admin-form-input">
                        <?php foreach ($blockTypes as $value => $label): ?>
                            <option value="<?php echo esc($value); ?>" <?php echo ($editBlock['block_type'] ?? 'custom_html') === $value ? 'selected' : ''; ?>>
                                <?php echo esc($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label" for="block_status">Status</label>
                    <select name="status" id="block_status" class="admin-form-input">
                        <option value="draft" <?php echo ($editBlock['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($editBlock['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo ($editBlock['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label" for="block_category">Category</label>
                    <input type="text" name="category" id="block_category" value="<?php echo esc($editBlock['category'] ?? ''); ?>" class="admin-form-input" placeholder="e.g., Homepage, About">
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label" for="block_device_visibility">Device Visibility</label>
                    <select name="device_visibility" id="block_device_visibility" class="admin-form-input">
                        <option value="all" <?php echo ($editBlock['device_visibility'] ?? 'all') === 'all' ? 'selected' : ''; ?>>All Devices</option>
                        <option value="desktop" <?php echo ($editBlock['device_visibility'] ?? '') === 'desktop' ? 'selected' : ''; ?>>Desktop Only</option>
                        <option value="tablet" <?php echo ($editBlock['device_visibility'] ?? '') === 'tablet' ? 'selected' : ''; ?>>Tablet Only</option>
                        <option value="mobile" <?php echo ($editBlock['device_visibility'] ?? '') === 'mobile' ? 'selected' : ''; ?>>Mobile Only</option>
                    </select>
                </div>
            </div>

            <div class="admin-form-group u-mt-sm">
                <label class="admin-form-label" for="block_content">Content (HTML)</label>
                <textarea name="content" id="block_content" class="admin-form-input admin-form-textarea" rows="12"><?php echo esc($editContent ?? ''); ?></textarea>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">
                    <?php echo $action === 'edit' ? 'Update Block' : 'Create Block'; ?>
                </button>
                <a href="/admin/blocks.php" class="admin-btn admin-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Blocks List -->
<div class="admin-card">
    <div class="admin-card__header">
        <h2 class="admin-card__title">All Blocks</h2>
        <span class="admin-card__count"><?php echo count($blocks); ?> blocks</span>
    </div>
    <div class="admin-card__body">
        <!-- Filters -->
        <div class="admin-media-toolbar">
            <form method="GET" action="/admin/blocks.php" class="admin-media-toolbar">
                <input type="text" name="search" value="<?php echo esc($search); ?>" class="admin-form-input" placeholder="Search blocks..." aria-label="Search blocks">
                <select name="type" class="admin-form-input" style="max-width:160px" aria-label="Filter by type">
                    <option value="">All Types</option>
                    <?php foreach ($blockTypes as $value => $label): ?>
                        <option value="<?php echo esc($value); ?>" <?php echo $typeFilter === $value ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">Filter</button>
                <?php if ($search || $typeFilter): ?>
                    <a href="/admin/blocks.php" class="admin-btn admin-btn--secondary admin-btn--sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($blocks)): ?>
            <div class="admin-empty">
                <p>No blocks found. <a href="/admin/blocks.php?action=add">Create your first block.</a></p>
            </div>
        <?php else: ?>
            <div class="admin-block-grid">
                <?php foreach ($blocks as $block): ?>
                <div class="admin-block-card">
                    <span class="admin-block-card__type"><?php echo esc($blockTypes[$block['block_type']] ?? $block['block_type']); ?></span>
                    <h3 class="admin-block-card__title"><?php echo esc($block['name']); ?></h3>
                    <p class="admin-block-card__slug"><?php echo esc($block['slug']); ?></p>
                    <div class="admin-block-card__meta">
                        <span class="admin-badge admin-badge--sm <?php echo $block['status'] === 'published' ? 'admin-badge--success' : ($block['status'] === 'archived' ? 'admin-badge--neutral' : 'admin-badge--warning'); ?>">
                            <?php echo ucfirst(esc($block['status'])); ?>
                        </span>
                        <?php if ($block['category']): ?>
                        <span class="admin-badge admin-badge--sm admin-badge--info"><?php echo esc($block['category']); ?></span>
                        <?php endif; ?>
                        <?php if (($block['usage_count'] ?? 0) > 0): ?>
                        <span class="admin-badge admin-badge--sm admin-badge--neutral">Used <?php echo (int)$block['usage_count']; ?>x</span>
                        <?php endif; ?>
                    </div>
                    <div class="admin-block-card__actions">
                        <a href="/admin/blocks.php?action=edit&id=<?php echo $block['id']; ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Edit</a>
                        <form method="POST" action="/admin/blocks.php?action=edit&id=<?php echo $block['id']; ?>" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                            <input type="hidden" name="action" value="duplicate">
                            <button type="submit" class="admin-btn admin-btn--sm admin-btn--secondary">Duplicate</button>
                        </form>
                        <form method="POST" action="/admin/blocks.php?action=edit&id=<?php echo $block['id']; ?>" style="display:inline" data-confirm="Delete this block?">
                            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php
            /* Pagination */
            $totalPages = ceil($totalBlocks / $perPage);
            if ($totalPages > 1):
                $queryParams = ['action' => 'list'];
                if ($search) $queryParams['search'] = $search;
                if ($typeFilter) $queryParams['type'] = $typeFilter;
            ?>
            <nav class="admin-pagination" aria-label="Block pagination">
                <?php if ($page > 1): ?>
                    <a href="/admin/blocks.php?<?php echo http_build_query(array_merge($queryParams, ['p' => $page - 1])); ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Previous</a>
                <?php endif; ?>
                <span class="admin-pagination__info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="/admin/blocks.php?<?php echo http_build_query(array_merge($queryParams, ['p' => $page + 1])); ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Next</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';
