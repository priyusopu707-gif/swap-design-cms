<?php
/**
 * Swap Design - Page Manager Admin Page
 *
 * CRUD for dynamic pages, layout assignment, section management,
 * and homepage control.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Pages';
$currentSection = 'pages';

$pageManager    = new PageManager();
$layoutManager  = new LayoutManager();
$sectionManager = new SectionManager();
$slugManager    = new SlugManager();
$message        = '';
$messageType    = '';

$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$pg     = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $postAction = $_POST['action'] ?? '';

        switch ($postAction) {
            case 'create':
                $pageManager->create([
                    'title'       => sanitizeString($_POST['title'] ?? ''),
                    'meta_desc'   => sanitizeString($_POST['meta_desc'] ?? ''),
                    'layout_id'   => !empty($_POST['layout_id']) ? (int)$_POST['layout_id'] : null,
                    'content'     => $_POST['content'] ?? '',
                    'status'      => sanitizeString($_POST['status'] ?? 'draft'),
                    'is_homepage' => isset($_POST['is_homepage']),
                    'show_in_nav' => isset($_POST['show_in_nav']),
                    'nav_label'   => sanitizeString($_POST['nav_label'] ?? ''),
                ]);
                $message     = 'Page created.';
                $messageType = 'success';
                break;

            case 'update':
                $pageManager->update($editId, [
                    'title'       => sanitizeString($_POST['title'] ?? ''),
                    'meta_desc'   => sanitizeString($_POST['meta_desc'] ?? ''),
                    'layout_id'   => !empty($_POST['layout_id']) ? (int)$_POST['layout_id'] : null,
                    'content'     => $_POST['content'] ?? '',
                    'status'      => sanitizeString($_POST['status'] ?? 'draft'),
                    'is_homepage' => isset($_POST['is_homepage']),
                    'show_in_nav' => isset($_POST['show_in_nav']),
                    'nav_label'   => sanitizeString($_POST['nav_label'] ?? ''),
                ]);
                $message     = 'Page updated.';
                $messageType = 'success';
                break;

            case 'delete':
                $pageManager->delete($editId);
                $message     = 'Page deleted.';
                $messageType = 'success';
                break;

            case 'duplicate':
                $pageManager->duplicate($editId);
                $message     = 'Page duplicated.';
                $messageType = 'success';
                break;

            case 'assign_section':
                $sectionManager->assignToPage(
                    (int)$_POST['page_id'],
                    (int)$_POST['section_id'],
                    sanitizeString($_POST['zone_key'] ?? 'content')
                );
                $message     = 'Section assigned to page.';
                $messageType = 'success';
                break;

            case 'remove_section':
                $sectionManager->removeFromPage((int)$_POST['ps_id']);
                $message     = 'Section removed.';
                $messageType = 'success';
                break;

            case 'reorder_sections':
                $order = json_decode($_POST['order'] ?? '[]', true);
                if (is_array($order)) {
                    $sectionManager->reorderPageSections(
                        (int)$_POST['page_id'],
                        sanitizeString($_POST['zone_key'] ?? 'content'),
                        $order
                    );
                }
                $message     = 'Section order saved.';
                $messageType = 'success';
                break;

            case 'toggle_section':
                $sectionManager->togglePageSection(
                    (int)$_POST['ps_id'],
                    ($_POST['enabled'] ?? '1') === '1'
                );
                $message     = 'Section toggled.';
                $messageType = 'success';
                break;

            case 'save_whatsapp':
                $whatsappManager = new WhatsAppManager();
                $whatsappManager->savePageOverride($editId, [
                    'is_enabled'        => isset($_POST['wa_enabled']),
                    'custom_number'     => $_POST['wa_custom_number'] ?? '',
                    'custom_message'    => $_POST['wa_custom_message'] ?? '',
                    'position_override' => $_POST['wa_position_override'] ?? 'global',
                ]);
                $message     = 'WhatsApp page settings saved.';
                $messageType = 'success';
                break;
        }
    }
}

/* Load data */
$editPage = null;
$pageSections = [];
$availableSections = [];
$zones = [];
$waOverride = null;

if ($action === 'edit' && $editId > 0) {
    $editPage = $pageManager->getById($editId);
    if ($editPage) {
        $pageSections = $sectionManager->getPageSections($editId);
        $zones = $editPage['layout_id'] ? $layoutManager->getZoneMap((int)$editPage['layout_id']) : ['content' => 'Content Area'];
        $availableSections = $sectionManager->getAll(['status' => 'published']);
        $waOverride = (new WhatsAppManager())->getPageOverride($editId);
    }
}

$pages = [];
$totalPages = 0;
if ($action === 'list') {
    $filters = [];
    if ($search) $filters['search'] = $search;
    if ($status) $filters['status'] = $status;
    $filters['limit']  = $perPage;
    $filters['offset'] = ($pg - 1) * $perPage;
    $pages = $pageManager->getAll($filters);
    $totalPages = $pageManager->countPages($filters);
}

$layouts = $layoutManager->getAll(['status' => 'active']);

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Page Manager</h1>
    <div class="admin-page-header__actions">
        <a href="/admin/pages.php?action=add" class="admin-btn admin-btn--primary">New Page</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Page Form -->
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title"><?php echo $action === 'edit' ? 'Edit Page' : 'New Page'; ?></h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/pages.php<?php echo $action === 'edit' ? "?action=edit&id=$editId" : '?action=add'; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label" for="page_title">Page Title <span class="admin-required">*</span></label>
                    <input type="text" name="title" id="page_title" value="<?php echo esc($editPage['title'] ?? ''); ?>" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label" for="page_slug">Slug</label>
                    <input type="text" id="page_slug" class="admin-form-input" value="<?php echo esc($editPage['slug'] ?? ''); ?>" disabled style="background:var(--admin-background);color:var(--admin-text-muted)">
                    <small style="color:var(--admin-text-muted);font-size:0.75rem">Auto-generated from title</small>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label" for="page_meta_desc">Meta Description</label>
                    <input type="text" name="meta_desc" id="page_meta_desc" value="<?php echo esc($editPage['meta_desc'] ?? ''); ?>" class="admin-form-input" maxlength="320">
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label" for="page_layout_id">Layout</label>
                    <select name="layout_id" id="page_layout_id" class="admin-form-input">
                        <option value="">None (Raw Content)</option>
                        <?php foreach ($layouts as $layout): ?>
                            <option value="<?php echo $layout['id']; ?>" <?php echo ($editPage['layout_id'] ?? 0) == $layout['id'] ? 'selected' : ''; ?>>
                                <?php echo esc($layout['name']); ?> <?php echo $layout['is_default'] ? '(Default)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label" for="page_status">Status</label>
                    <select name="status" id="page_status" class="admin-form-input">
                        <option value="draft" <?php echo ($editPage['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($editPage['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label" for="page_nav_label">Navigation Label (if shown)</label>
                    <input type="text" name="nav_label" id="page_nav_label" value="<?php echo esc($editPage['nav_label'] ?? ''); ?>" class="admin-form-input">
                </div>
            </div>

            <div class="admin-form-checkboxes">
                <label class="admin-form-checkbox">
                    <input type="checkbox" name="is_homepage" value="1" <?php echo ($editPage['is_homepage'] ?? 0) ? 'checked' : ''; ?>>
                    <span>Set as Homepage</span>
                </label>
                <label class="admin-form-checkbox">
                    <input type="checkbox" name="show_in_nav" value="1" <?php echo ($editPage['show_in_nav'] ?? 0) ? 'checked' : ''; ?>>
                    <span>Show in Navigation</span>
                </label>
            </div>

            <div class="admin-form-group u-mt-sm">
                <label class="admin-form-label" for="page_content">Raw Content (used if no layout assigned)</label>
                <textarea name="content" id="page_content" class="admin-form-input admin-form-textarea" rows="8"><?php echo esc($editPage['content'] ?? ''); ?></textarea>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary admin-btn--lg">
                    <?php echo $action === 'edit' ? 'Update Page' : 'Create Page'; ?>
                </button>
                <a href="/admin/pages.php" class="admin-btn admin-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php if ($action === 'edit' && $editPage): ?>
<!-- WhatsApp Page Override -->
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title">WhatsApp Button Override</h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/pages.php?action=edit&id=<?php echo $editId; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="save_whatsapp">

            <label class="admin-toggle u-mb-sm">
                <input type="checkbox" name="wa_enabled" value="1" <?php echo ($waOverride ? $waOverride['is_enabled'] : 1) ? 'checked' : ''; ?>>
                <span class="admin-toggle__slider"></span>
                <span>Show WhatsApp button on this page</span>
            </label>

            <div class="admin-form-grid u-mt-sm">
                <div class="admin-form-group">
                    <label class="admin-form-label" for="wa_custom_number">Custom Number (optional)</label>
                    <input type="text" name="wa_custom_number" id="wa_custom_number" value="<?php echo esc($waOverride['custom_number'] ?? ''); ?>" class="admin-form-input" placeholder="Use global number if empty">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label" for="wa_position_override">Position Override</label>
                    <select name="wa_position_override" id="wa_position_override" class="admin-form-input">
                        <option value="global" <?php echo ($waOverride['position_override'] ?? 'global') === 'global' ? 'selected' : ''; ?>>Use Global Position</option>
                        <option value="left" <?php echo ($waOverride['position_override'] ?? '') === 'left' ? 'selected' : ''; ?>>Left</option>
                        <option value="right" <?php echo ($waOverride['position_override'] ?? '') === 'right' ? 'selected' : ''; ?>>Right</option>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:1/-1">
                    <label class="admin-form-label" for="wa_custom_message">Custom Message (optional)</label>
                    <textarea name="wa_custom_message" id="wa_custom_message" class="admin-form-input" rows="2" style="width:100%" maxlength="500" placeholder="Use global message if empty"><?php echo esc($waOverride['custom_message'] ?? ''); ?></textarea>
                    <small style="color:var(--admin-text-muted);font-size:0.75rem">
                        Placeholders: <code>{page_title}</code> <code>{service_name}</code> <code>{portfolio_title}</code>
                    </small>
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">Save WhatsApp Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- Section Manager for this page -->
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Page Sections</h2>
        <span class="admin-card__count"><?php echo count($pageSections); ?> sections</span>
    </div>
    <div class="admin-card__body">
        <?php if (empty($zones)): ?>
            <p class="admin-text-muted">Assign a layout to this page first to manage sections.</p>
        <?php else: ?>
            <?php foreach ($zones as $zoneKey => $zoneLabel): ?>
            <div class="u-mb-sm">
                <h3 class="admin-section-zone-title">Zone: <?php echo esc($zoneLabel); ?> <code><?php echo esc($zoneKey); ?></code></h3>
                <?php
                $zoneSections = array_filter($pageSections, fn($s) => ($s['zone_key'] ?? 'content') === $zoneKey);
                usort($zoneSections, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
                ?>
                <?php if (empty($zoneSections)): ?>
                    <p class="admin-text-muted" style="font-size:0.8125rem">No sections in this zone.</p>
                <?php else: ?>
                <div class="admin-page-sections" data-zone="<?php echo esc($zoneKey); ?>">
                    <?php foreach ($zoneSections as $ps): ?>
                    <div class="admin-page-section-item" data-ps-id="<?php echo $ps['ps_id']; ?>">
                        <span class="admin-nav-handle">&#x2630;</span>
                        <span class="admin-page-section-name">
                            <?php echo esc($ps['name'] ?? 'Untitled'); ?>
                            <small>(<?php echo esc(SectionManager::TYPES[$ps['section_type']] ?? $ps['section_type']); ?>)</small>
                        </span>
                        <span class="admin-badge admin-badge--sm <?php echo $ps['is_enabled'] ? 'admin-badge--success' : 'admin-badge--neutral'; ?>">
                            <?php echo $ps['is_enabled'] ? 'Active' : 'Disabled'; ?>
                        </span>
                        <div class="admin-page-section-actions">
                            <form method="POST" action="/admin/pages.php?action=edit&id=<?php echo $editId; ?>" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                                <input type="hidden" name="action" value="toggle_section">
                                <input type="hidden" name="ps_id" value="<?php echo $ps['ps_id']; ?>">
                                <input type="hidden" name="enabled" value="<?php echo $ps['is_enabled'] ? 0 : 1; ?>">
                                <button type="submit" class="admin-btn admin-btn--sm admin-btn--secondary">
                                    <?php echo $ps['is_enabled'] ? 'Disable' : 'Enable'; ?>
                                </button>
                            </form>
                            <form method="POST" action="/admin/pages.php?action=edit&id=<?php echo $editId; ?>" style="display:inline" data-confirm="Remove this section from the page?">
                                <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                                <input type="hidden" name="action" value="remove_section">
                                <input type="hidden" name="ps_id" value="<?php echo $ps['ps_id']; ?>">
                                <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Remove</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Assign new section -->
                <form method="POST" action="/admin/pages.php?action=edit&id=<?php echo $editId; ?>" style="display:flex;gap:0.5rem;margin-top:0.5rem">
                    <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                    <input type="hidden" name="action" value="assign_section">
                    <input type="hidden" name="page_id" value="<?php echo $editId; ?>">
                    <input type="hidden" name="zone_key" value="<?php echo esc($zoneKey); ?>">
                    <select name="section_id" class="admin-form-input" style="flex:1;max-width:300px">
                        <option value="">-- Assign Section --</option>
                        <?php foreach ($availableSections as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo esc($s['name']); ?> (<?php echo esc(SectionManager::TYPES[$s['section_type']] ?? ''); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">Add</button>
                </form>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<!-- Pages List -->
<div class="admin-card">
    <div class="admin-card__header">
        <h2 class="admin-card__title">All Pages</h2>
        <span class="admin-card__count"><?php echo count($pages); ?> pages</span>
    </div>
    <div class="admin-card__body">
        <!-- Filters -->
        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;margin-bottom:1rem">
            <form method="GET" action="/admin/pages.php" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;flex:1">
                <input type="text" name="search" value="<?php echo esc($search); ?>" class="admin-form-input" placeholder="Search pages..." style="max-width:220px" aria-label="Search pages">
                <select name="status" class="admin-form-input" style="max-width:140px" aria-label="Filter by status">
                    <option value="">All Status</option>
                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                </select>
                <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">Filter</button>
                <?php if ($search || $status): ?>
                    <a href="/admin/pages.php" class="admin-btn admin-btn--secondary admin-btn--sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($pages)): ?>
            <div class="admin-empty">
                <p>No pages found. <a href="/admin/pages.php?action=add">Create your first page.</a></p>
            </div>
        <?php else: ?>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Slug</th>
                            <th class="admin-table__hide-mobile">Layout</th>
                            <th>Status</th>
                            <th class="admin-table__hide-mobile">Updated</th>
                            <th style="width:180px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc($page['title']); ?></strong>
                                <?php if ($page['is_homepage']): ?>
                                    <span class="admin-badge admin-badge--sm admin-badge--info" style="margin-left:0.375rem">Home</span>
                                <?php endif; ?>
                                <?php if ($page['show_in_nav']): ?>
                                    <span class="admin-badge admin-badge--sm admin-badge--neutral" style="margin-left:0.375rem">Nav</span>
                                <?php endif; ?>
                            </td>
                            <td><code>/<?php echo esc($page['slug']); ?></code></td>
                            <td class="admin-table__hide-mobile"><?php echo esc($page['layout_name'] ?? 'None'); ?></td>
                            <td><span class="admin-badge <?php echo $page['status'] === 'published' ? 'admin-badge--success' : 'admin-badge--warning'; ?>"><?php echo ucfirst($page['status']); ?></span></td>
                            <td class="admin-table__hide-mobile"><?php echo date('M j, Y', strtotime($page['updated_at'])); ?></td>
                            <td>
                                <div style="display:flex;gap:0.25rem">
                                    <a href="/admin/pages.php?action=edit&id=<?php echo $page['id']; ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Edit</a>
                                    <?php if ($page['status'] === 'published'): ?>
                                    <a href="/<?php echo esc($page['slug']); ?>" target="_blank" rel="noopener" class="admin-btn admin-btn--sm admin-btn--secondary">View</a>
                                    <?php endif; ?>
                                    <form method="POST" action="/admin/pages.php?action=edit&id=<?php echo $page['id']; ?>" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                                        <input type="hidden" name="action" value="duplicate">
                                        <button type="submit" class="admin-btn admin-btn--sm admin-btn--secondary">Dup</button>
                                    </form>
                                    <form method="POST" action="/admin/pages.php?action=edit&id=<?php echo $page['id']; ?>" style="display:inline" data-confirm="Delete this page?">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Del</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $totalPgs = ceil($totalPages / $perPage);
            if ($totalPgs > 1):
                $q = ['search' => $search, 'status' => $status];
            ?>
            <nav class="admin-pagination">
                <?php if ($pg > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($q, ['p' => $pg - 1])); ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Previous</a>
                <?php endif; ?>
                <span class="admin-pagination__info">Page <?php echo $pg; ?> of <?php echo $totalPgs; ?></span>
                <?php if ($pg < $totalPgs): ?>
                    <a href="?<?php echo http_build_query(array_merge($q, ['p' => $pg + 1])); ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Next</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php';
