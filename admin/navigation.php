<?php
/**
 * Swap Design - Navigation Manager Admin Page
 *
 * CRUD interfaces for frontend navigation menus.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Navigation';
$currentSection = 'navigation';

$navManager     = new NavigationManager();
$message        = '';
$messageType    = '';

/* Action routing */
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $postAction = $_POST['action'] ?? $action;

        switch ($postAction) {
            case 'create':
                $navManager->create([
                    'label'        => sanitizeString($_POST['label'] ?? ''),
                    'url'          => sanitizeString($_POST['url'] ?? '#'),
                    'slug'         => sluggify($_POST['label'] ?? 'menu-item'),
                    'parent_id'    => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                    'sort_order'   => (int)($_POST['sort_order'] ?? 0),
                    'location'     => sanitizeString($_POST['location'] ?? 'primary'),
                    'is_visible'   => isset($_POST['is_visible']) ? 1 : 0,
                    'open_new_tab' => isset($_POST['open_new_tab']) ? 1 : 0,
                ]);
                $message     = 'Menu item created.';
                $messageType = 'success';
                break;

            case 'update':
                $navManager->update($id, [
                    'label'        => sanitizeString($_POST['label'] ?? ''),
                    'url'          => sanitizeString($_POST['url'] ?? '#'),
                    'parent_id'    => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                    'sort_order'   => (int)($_POST['sort_order'] ?? 0),
                    'location'     => sanitizeString($_POST['location'] ?? 'primary'),
                    'is_visible'   => isset($_POST['is_visible']) ? 1 : 0,
                    'open_new_tab' => isset($_POST['open_new_tab']) ? 1 : 0,
                ]);
                $message     = 'Menu item updated.';
                $messageType = 'success';
                break;

            case 'delete':
                $navManager->delete($id);
                $message     = 'Menu item deleted.';
                $messageType = 'success';
                break;

            case 'reorder':
                $order = json_decode($_POST['order'] ?? '[]', true);
                if (is_array($order)) {
                    $navManager->reorder($order);
                }
                $message     = 'Menu order saved.';
                $messageType = 'success';
                break;
        }
    }
}

/* Load data for form context */
$editItem = null;
if ($action === 'edit' && $id > 0) {
    $editItem = $navManager->getItem($id);
}

$menuTree  = $navManager->getMenuTree('');
$parentOpts = $navManager->getParentOptions('', $id);

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Navigation Manager</h1>
    <div class="admin-page-header__actions">
        <a href="/admin/navigation.php?action=add" class="admin-btn admin-btn--primary">Add Menu Item</a>
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
        <h2 class="admin-card__title"><?php echo $action === 'edit' ? 'Edit Menu Item' : 'Add Menu Item'; ?></h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/navigation.php<?php echo $action === 'edit' ? "?action=edit&id=$id" : '?action=add'; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label">Label <span class="admin-required">*</span></label>
                    <input type="text" name="label" value="<?php echo esc($editItem['label'] ?? ''); ?>" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">URL <span class="admin-required">*</span></label>
                    <input type="text" name="url" value="<?php echo esc($editItem['url'] ?? ''); ?>" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Location</label>
                    <select name="location" class="admin-form-input">
                        <option value="primary" <?php echo ($editItem['location'] ?? 'primary') === 'primary' ? 'selected' : ''; ?>>Primary Menu</option>
                        <option value="footer" <?php echo ($editItem['location'] ?? '') === 'footer' ? 'selected' : ''; ?>>Footer Menu</option>
                        <option value="social" <?php echo ($editItem['location'] ?? '') === 'social' ? 'selected' : ''; ?>>Social Links</option>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Parent Item</label>
                    <select name="parent_id" class="admin-form-input">
                        <option value="">None (Top Level)</option>
                        <?php foreach ($parentOpts as $opt): ?>
                            <option value="<?php echo $opt['id']; ?>" <?php echo ($editItem['parent_id'] ?? null) == $opt['id'] ? 'selected' : ''; ?>>
                                <?php echo esc($opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Sort Order</label>
                    <input type="number" name="sort_order" value="<?php echo esc($editItem['sort_order'] ?? 0); ?>" class="admin-form-input" min="0">
                </div>
            </div>

            <div class="admin-form-checkboxes">
                <label class="admin-form-checkbox">
                    <input type="checkbox" name="is_visible" value="1" <?php echo ($editItem['is_visible'] ?? 1) ? 'checked' : ''; ?>>
                    <span>Visible</span>
                </label>
                <label class="admin-form-checkbox">
                    <input type="checkbox" name="open_new_tab" value="1" <?php echo ($editItem['open_new_tab'] ?? 0) ? 'checked' : ''; ?>>
                    <span>Open in New Tab</span>
                </label>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">
                    <?php echo $action === 'edit' ? 'Update Item' : 'Add Item'; ?>
                </button>
                <a href="/admin/navigation.php" class="admin-btn admin-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Menu Items List -->
<div class="admin-card">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Current Menu Structure</h2>
        <span class="admin-card__count"><?php echo count($menuTree); ?> items</span>
    </div>
    <div class="admin-card__body">
        <?php if (empty($menuTree)): ?>
            <div class="admin-empty">
                <p>No menu items yet. <a href="/admin/navigation.php?action=add">Add your first item.</a></p>
            </div>
        <?php else: ?>
            <div class="admin-nav-manager" data-reorderable="true">
                <?php
                function renderNavTree(array $items, int $level = 0, string $csrf = ''): void
                {
                    foreach ($items as $item):
                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                        $indentStyle = $level > 0 ? 'style="padding-left:' . ($level * 24 + 12) . 'px"' : '';
                    ?>
                    <div class="admin-nav-item" data-id="<?php echo $item['id']; ?>" <?php echo $indentStyle; ?>>
                        <span class="admin-nav-handle" aria-label="Drag to reorder">&#x2630;</span>
                        <span class="admin-nav-label">
                            <?php echo $indent; ?>
                            <a href="<?php echo esc($item['url']); ?>" target="_blank" rel="noopener" title="Open in new tab">
                                <?php echo esc($item['label']); ?>
                                <?php if ($item['open_new_tab']): ?><small>(new tab)</small><?php endif; ?>
                            </a>
                        </span>
                        <span class="admin-nav-badges">
                            <span class="admin-badge admin-badge--sm <?php echo $item['is_visible'] ? 'admin-badge--success' : 'admin-badge--neutral'; ?>">
                                <?php echo $item['is_visible'] ? 'Visible' : 'Hidden'; ?>
                            </span>
                            <span class="admin-badge admin-badge--sm admin-badge--info"><?php echo esc($item['location']); ?></span>
                        </span>
                        <div class="admin-nav-actions">
                            <a href="/admin/navigation.php?action=edit&id=<?php echo $item['id']; ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Edit</a>
                            <form method="POST" action="/admin/navigation.php?action=delete&id=<?php echo $item['id']; ?>" style="display:inline" data-confirm="Delete this menu item?">
                                <input type="hidden" name="csrf_token" value="<?php echo esc($csrf); ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php
                    if (!empty($item['children'])) {
                        renderNavTree($item['children'], $level + 1, $csrf);
                    }
                    endforeach;
                }
                renderNavTree($menuTree, 0, $csrfToken);
                ?>
            </div>

            <form method="POST" action="/admin/navigation.php?action=reorder" id="nav-reorder-form" style="display:none">
                <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                <input type="hidden" name="action" value="reorder">
                <input type="hidden" name="order" id="nav-order-input">
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';
