<?php
/**
 * Swap Design - Content Types Admin Page
 *
 * Manage custom content type definitions with field schemas.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Content Types';
$currentSection = 'content-types';

$typeEngine     = new ContentTypeEngine();
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
                $fieldsSchema = [];
                $fieldNames   = $_POST['field_name'] ?? [];
                $fieldLabels  = $_POST['field_label'] ?? [];
                $fieldTypes   = $_POST['field_type'] ?? [];
                foreach ($fieldNames as $i => $name) {
                    if ($name) {
                        $fieldsSchema[] = [
                            'name'     => sluggify($name),
                            'label'    => $fieldLabels[$i] ?? $name,
                            'type'     => $fieldTypes[$i] ?? 'text',
                            'required' => isset($_POST['field_required'][$i]),
                        ];
                    }
                }
                $typeEngine->create([
                    'name'          => sanitizeString($_POST['name'] ?? ''),
                    'slug'          => sluggify($_POST['name'] ?? 'type'),
                    'description'   => sanitizeString($_POST['description'] ?? ''),
                    'fields_schema' => $fieldsSchema,
                    'icon'          => sanitizeString($_POST['icon'] ?? 'file'),
                    'has_entries'   => isset($_POST['has_entries']),
                    'list_template' => sanitizeString($_POST['list_template'] ?? ''),
                    'single_template' => sanitizeString($_POST['single_template'] ?? ''),
                    'status'        => 'active',
                ]);
                $message     = 'Content type created.';
                $messageType = 'success';
                break;

            case 'update':
                $fieldsSchema = [];
                $fieldNames   = $_POST['field_name'] ?? [];
                $fieldLabels  = $_POST['field_label'] ?? [];
                $fieldTypes   = $_POST['field_type'] ?? [];
                foreach ($fieldNames as $i => $name) {
                    if ($name) {
                        $fieldsSchema[] = [
                            'name'     => sluggify($name),
                            'label'    => $fieldLabels[$i] ?? $name,
                            'type'     => $fieldTypes[$i] ?? 'text',
                            'required' => isset($_POST['field_required'][$i]),
                        ];
                    }
                }
                $typeEngine->update($editId, [
                    'name'            => sanitizeString($_POST['name'] ?? ''),
                    'slug'            => sluggify($_POST['name'] ?? 'type'),
                    'description'     => sanitizeString($_POST['description'] ?? ''),
                    'fields_schema'   => $fieldsSchema,
                    'icon'            => sanitizeString($_POST['icon'] ?? 'file'),
                    'has_entries'     => isset($_POST['has_entries']),
                    'list_template'   => sanitizeString($_POST['list_template'] ?? ''),
                    'single_template' => sanitizeString($_POST['single_template'] ?? ''),
                ]);
                $message     = 'Content type updated.';
                $messageType = 'success';
                break;

            case 'delete':
                $typeEngine->delete($editId);
                $message     = 'Content type deleted.';
                $messageType = 'success';
                break;

            case 'seed':
                $typeEngine->seedSystemTypes();
                $message     = 'System content types seeded.';
                $messageType = 'success';
                break;
        }
    }
}

$editType = null;
if ($action === 'edit' && $editId > 0) {
    $editType = $typeEngine->getById($editId);
}

$types = $typeEngine->getAll();

$fieldTypes = ['text' => 'Text', 'textarea' => 'Textarea', 'number' => 'Number',
    'email' => 'Email', 'url' => 'URL', 'image' => 'Image', 'select' => 'Select',
    'repeater' => 'Repeater', 'richtext' => 'Rich Text'];

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Content Types</h1>
    <div class="admin-page-header__actions">
        <form method="POST" action="/admin/content-types.php" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="seed">
            <button type="submit" class="admin-btn admin-btn--secondary admin-btn--sm">Seed System Types</button>
        </form>
        <a href="/admin/content-types.php?action=add" class="admin-btn admin-btn--primary">New Type</a>
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
        <h2 class="admin-card__title"><?php echo $action === 'edit' ? 'Edit Content Type' : 'New Content Type'; ?></h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/content-types.php<?php echo $action === 'edit' ? "?action=edit&id=$editId" : '?action=add'; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label">Name <span class="admin-required">*</span></label>
                    <input type="text" name="name" value="<?php echo esc($editType['name'] ?? ''); ?>" class="admin-form-input" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Icon (emoji or icon name)</label>
                    <input type="text" name="icon" value="<?php echo esc($editType['icon'] ?? 'file'); ?>" class="admin-form-input">
                </div>
                <div class="admin-form-group" style="grid-column:1/-1">
                    <label class="admin-form-label">Description</label>
                    <textarea name="description" class="admin-form-input admin-form-textarea" rows="2"><?php echo esc($editType['description'] ?? ''); ?></textarea>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">List Template Path</label>
                    <input type="text" name="list_template" value="<?php echo esc($editType['list_template'] ?? ''); ?>" class="admin-form-input" placeholder="pages/services.php">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Single Template Path</label>
                    <input type="text" name="single_template" value="<?php echo esc($editType['single_template'] ?? ''); ?>" class="admin-form-input" placeholder="pages/service-single.php">
                </div>
            </div>

            <div class="admin-form-checkboxes u-mt-sm">
                <label class="admin-form-checkbox">
                    <input type="checkbox" name="has_entries" value="1" <?php echo ($editType['has_entries'] ?? 1) ? 'checked' : ''; ?>>
                    <span>Has Entries (archives + single pages)</span>
                </label>
            </div>

            <!-- Field Schema Builder -->
            <div class="u-mt-sm">
                <h3>Field Schema</h3>
                <div id="fields-container">
                    <?php
                    $schema = $editType['fields_schema'] ?? [];
                    if (empty($schema)) $schema = [['name' => '', 'label' => '', 'type' => 'text', 'required' => false]];
                    foreach ($schema as $i => $field):
                    ?>
                    <div class="admin-field-row" style="display:flex;gap:0.5rem;align-items:center;margin-bottom:0.5rem">
                        <input type="text" name="field_name[]" value="<?php echo esc($field['name'] ?? ''); ?>" class="admin-form-input" placeholder="Field key" style="flex:1">
                        <input type="text" name="field_label[]" value="<?php echo esc($field['label'] ?? ''); ?>" class="admin-form-input" placeholder="Label" style="flex:1">
                        <select name="field_type[]" class="admin-form-input" style="width:120px">
                            <?php foreach ($fieldTypes as $val => $lbl): ?>
                                <option value="<?php echo $val; ?>" <?php echo ($field['type'] ?? 'text') === $val ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="admin-form-checkbox" style="white-space:nowrap">
                            <input type="checkbox" name="field_required[<?php echo $i; ?>]" value="1" <?php echo ($field['required'] ?? false) ? 'checked' : ''; ?>>
                            Required
                        </label>
                        <button type="button" class="admin-btn admin-btn--sm admin-btn--danger" onclick="this.closest('.admin-field-row').remove()">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm u-mt-sm" onclick="addFieldRow()">+ Add Field</button>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary admin-btn--lg">
                    <?php echo $action === 'edit' ? 'Update Type' : 'Create Type'; ?>
                </button>
                <a href="/admin/content-types.php" class="admin-btn admin-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Content Types List -->
<div class="admin-card">
    <div class="admin-card__header">
        <h2 class="admin-card__title">All Content Types</h2>
    </div>
    <div class="admin-card__body">
        <?php if (empty($types)): ?>
            <div class="admin-empty">
                <p>No content types defined. <a href="/admin/content-types.php?action=add">Create one</a> or seed system types.</p>
            </div>
        <?php else: ?>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th class="admin-table__hide-mobile">Fields</th>
                            <th class="admin-table__hide-mobile">Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $type): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc($type['name']); ?></strong>
                                <span style="margin-left:0.375rem;color:var(--admin-text-muted)"><?php echo esc($type['icon']); ?></span>
                            </td>
                            <td><code><?php echo esc($type['slug']); ?></code></td>
                            <td class="admin-table__hide-mobile"><?php echo count($type['fields_schema'] ?? []); ?> fields</td>
                            <td class="admin-table__hide-mobile">
                                <?php if ($type['is_system']): ?>
                                    <span class="admin-badge admin-badge--info">System</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge--neutral">Custom</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.25rem">
                                    <a href="/admin/content-types.php?action=edit&id=<?php echo $type['id']; ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Edit</a>
                                    <a href="/admin/entries.php?type=<?php echo esc($type['slug']); ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Entries</a>
                                    <?php if (!$type['is_system']): ?>
                                    <form method="POST" action="/admin/content-types.php?action=edit&id=<?php echo $type['id']; ?>" style="display:inline" data-confirm="Delete this type and all entries?">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="admin-btn admin-btn--sm admin-btn--danger">Del</button>
                                    </form>
                                    <?php endif; ?>
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
function addFieldRow() {
    var container = document.getElementById('fields-container');
    var idx = container.children.length;
    var row = document.createElement('div');
    row.className = 'admin-field-row';
    row.style.cssText = 'display:flex;gap:0.5rem;align-items:center;margin-bottom:0.5rem';
    row.innerHTML = '<input type="text" name="field_name[]" class="admin-form-input" placeholder="Field key" style="flex:1">' +
        '<input type="text" name="field_label[]" class="admin-form-input" placeholder="Label" style="flex:1">' +
        '<select name="field_type[]" class="admin-form-input" style="width:120px">' +
        <?php foreach ($fieldTypes as $val => $lbl): ?>
        '<option value="<?php echo $val; ?>"><?php echo $lbl; ?></option>' +
        <?php endforeach; ?>
        '</select>' +
        '<label class="admin-form-checkbox" style="white-space:nowrap"><input type="checkbox" name="field_required[' + idx + ']" value="1"> Required</label>' +
        '<button type="button" class="admin-btn admin-btn--sm admin-btn--danger" onclick="this.closest(\'.admin-field-row\').remove()">&times;</button>';
    container.appendChild(row);
}
</script>

<?php require __DIR__ . '/includes/footer.php';
