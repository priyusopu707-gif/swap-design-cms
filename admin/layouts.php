<?php
/**
 * Swap Design - Layouts Admin Page
 *
 * Manage page structural templates (layouts). Each layout
 * defines named zones where sections can be placed.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Layouts';
$currentSection = 'layouts';

$layoutManager  = new LayoutManager();
$message        = '';
$messageType    = '';

$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $postAction = $_POST['action'] ?? '';

        switch ($postAction) {
            case 'create':
            case 'update':
                $zones = [];
                $zoneKeys  = $_POST['zone_key'] ?? [];
                $zoneLabels = $_POST['zone_label'] ?? [];
                foreach ($zoneKeys as $i => $key) {
                    if ($key) {
                        $zones[] = [
                            'key'              => $key,
                            'label'            => $zoneLabels[$i] ?? $key,
                            'allowed_sections' => ['custom_html','global_block','component','content_entries','dynamic_list','shortcode'],
                        ];
                    }
                }
                $data = [
                    'name'          => sanitizeString($_POST['name'] ?? ''),
                    'slug'          => sluggify($_POST['name'] ?? 'layout'),
                    'description'   => sanitizeString($_POST['description'] ?? ''),
                    'zones'         => $zones,
                    'template_path' => sanitizeString($_POST['template_path'] ?? ''),
                    'status'        => sanitizeString($_POST['status'] ?? 'active'),
                ];

                if ($postAction === 'create') {
                    $layoutManager->create($data);
                    $message = 'Layout created.';
                } else {
                    $layoutManager->update($editId, $data);
                    $message = 'Layout updated.';
                }
                $messageType = 'success';
                break;

            case 'delete':
                $layoutManager->delete($editId);
                $message     = 'Layout deleted.';
                $messageType = 'success';
                break;

            case 'set_default':
                $layoutManager->setDefault($editId);
                $message     = 'Default layout set.';
                $messageType = 'success';
                break;

            case 'seed':
                $layoutManager->seedBuiltins();
                $message     = 'Built-in layouts seeded.';
                $messageType = 'success';
                break;
        }
    }
}

$editLayout = null;
if ($action === 'edit' && $editId > 0) {
    $editLayout = $layoutManager->getById($editId);
}

$layouts = $layoutManager->getAll();

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Layouts</h1>
    <div class="admin-page-header__actions">
        <form method="POST" action="/admin/layouts.php" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="seed">
            <button type="submit" class="admin-btn admin-btn--secondary admin-btn--sm">Seed Built-ins</button>
        </form>
        <a href="/admin/layouts.php?action=add" class="admin-btn admin-btn--primary">New Layout</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title"><?php echo $action === 'edit' ? 'Edit Layout' : 'New Layout'; ?></h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/layouts.php<?php echo $action === 'edit' ? "?action=edit&id=$editId" : '?action=add'; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label">Layout Name <span class="admin-required">*</span></label>
                    <input type="text" name="name" value="<?php echo esc($editLayout['name'] ?? ''); ?>" class="admin-form-input" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Template Path</label>
                    <input type="text" name="template_path" value="<?php echo esc($editLayout['template_path'] ?? ''); ?>" class="admin-form-input" placeholder="pages/default.php">
                </div>
                <div class="admin-form-group" style="grid-column:1/-1">
                    <label class="admin-form-label">Description</label>
                    <textarea name="description" class="admin-form-input" rows="2" style="width:100%"><?php echo esc($editLayout['description'] ?? ''); ?></textarea>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Status</label>
                    <select name="status" class="admin-form-input">
                        <option value="active" <?php echo ($editLayout['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($editLayout['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Zone Builder -->
            <div class="u-mt-sm">
                <h3>Layout Zones</h3>
                <p style="font-size:0.8125rem;color:var(--admin-text-muted)">Define named zones where sections can be placed on pages using this layout.</p>
                <div id="zones-container">
                    <?php
                    $editZones = $editLayout['zones'] ?? [['key' => 'content', 'label' => 'Content Area']];
                    foreach ($editZones as $zone):
                    ?>
                    <div class="admin-field-row" style="display:flex;gap:0.5rem;align-items:center;margin-bottom:0.5rem">
                        <input type="text" name="zone_key[]" value="<?php echo esc($zone['key']); ?>" class="admin-form-input" placeholder="Zone key (e.g., content, hero)" style="flex:1">
                        <input type="text" name="zone_label[]" value="<?php echo esc($zone['label']); ?>" class="admin-form-input" placeholder="Display label" style="flex:1.5">
                        <button type="button" class="admin-btn admin-btn--sm admin-btn--danger" onclick="this.closest('.admin-field-row').remove()">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm u-mt-sm" onclick="addZoneRow()">+ Add Zone</button>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary admin-btn--lg">
                    <?php echo $action === 'edit' ? 'Update Layout' : 'Create Layout'; ?>
                </button>
                <a href="/admin/layouts.php" class="admin-btn admin-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Layouts List -->
<div class="admin-card">
    <div class="admin-card__header">
        <h2 class="admin-card__title">All Layouts</h2>
    </div>
    <div class="admin-card__body">
        <?php if (empty($layouts)): ?>
            <div class="admin-empty">
                <p>No layouts defined. <a href="/admin/layouts.php?action=add">Create one</a> or seed built-in layouts.</p>
            </div>
        <?php else: ?>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="admin-table__hide-mobile">Slug</th>
                            <th class="admin-table__hide-mobile">Zones</th>
                            <th>Default</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($layouts as $layout): ?>
                        <tr>
                            <td><strong><?php echo esc($layout['name']); ?></strong></td>
                            <td class="admin-table__hide-mobile"><code><?php echo esc($layout['slug']); ?></code></td>
                            <td class="admin-table__hide-mobile">
                                <?php foreach (($layout['zones'] ?? []) as $zone): ?>
                                    <span class="admin-badge admin-badge--sm admin-badge--info"><?php echo esc($zone['label'] ?? $zone['key']); ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php if ($layout['is_default']): ?>
                                    <span class="admin-badge admin-badge--success">Default</span>
                                <?php else: ?>
                                    <form method="POST" action="/admin/layouts.php?action=edit&id=<?php echo $layout['id']; ?>" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                                        <input type="hidden" name="action" value="set_default">
                                        <button type="submit" class="admin-btn admin-btn--sm admin-btn--secondary">Set Default</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.25rem">
                                    <a href="/admin/layouts.php?action=edit&id=<?php echo $layout['id']; ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Edit</a>
                                    <form method="POST" action="/admin/layouts.php?action=edit&id=<?php echo $layout['id']; ?>" style="display:inline" data-confirm="Delete this layout?">
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
        <?php endif; ?>
    </div>
</div>

<script>
function addZoneRow() {
    var container = document.getElementById('zones-container');
    var row = document.createElement('div');
    row.className = 'admin-field-row';
    row.style.cssText = 'display:flex;gap:0.5rem;align-items:center;margin-bottom:0.5rem';
    row.innerHTML = '<input type="text" name="zone_key[]" class="admin-form-input" placeholder="Zone key (e.g., content, hero)" style="flex:1">' +
        '<input type="text" name="zone_label[]" class="admin-form-input" placeholder="Display label" style="flex:1.5">' +
        '<button type="button" class="admin-btn admin-btn--sm admin-btn--danger" onclick="this.closest(\'.admin-field-row\').remove()">&times;</button>';
    container.appendChild(row);
}
</script>

<?php require __DIR__ . '/includes/footer.php';
