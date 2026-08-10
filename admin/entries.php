<?php
/**
 * Swap Design - Content Entries Admin Page
 *
 * CRUD for entries belonging to content types. Shows entries
 * filtered by content type.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Entries';
$currentSection = 'entries';

$entryManager   = new ContentEntryManager();
$typeEngine     = new ContentTypeEngine();
$message        = '';
$messageType    = '';

$action    = $_GET['action'] ?? 'list';
$editId    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$typeSlug  = $_GET['type'] ?? '';
$search    = $_GET['search'] ?? '';
$pg        = max(1, (int)($_GET['p'] ?? 1));
$perPage   = 20;
$contentType = $typeSlug ? $typeEngine->getBySlug($typeSlug) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $postAction = $_POST['action'] ?? '';

        switch ($postAction) {
            case 'create':
            case 'update':
                $fields = [];
                $fieldNames = $_POST['field_key'] ?? [];
                $fieldVals  = $_POST['field_val'] ?? [];
                foreach ($fieldNames as $i => $key) {
                    if ($key) {
                        $fields[$key] = $fieldVals[$i] ?? '';
                    }
                }
                $data = [
                    'title'           => sanitizeString($_POST['title'] ?? ''),
                    'fields'          => $fields,
                    'excerpt'         => sanitizeString($_POST['excerpt'] ?? ''),
                    'featured_image'  => sanitizeString($_POST['featured_image'] ?? ''),
                    'status'          => sanitizeString($_POST['status'] ?? 'draft'),
                    'sort_order'      => (int)($_POST['sort_order'] ?? 0),
                ];

                if ($postAction === 'create') {
                    $data['content_type_id'] = (int)($_POST['content_type_id'] ?? 0);
                    $data['slug'] = sluggify($data['title']);
                    $entryManager->create($data);
                    $message = 'Entry created.';
                } else {
                    $entryManager->update($editId, $data);
                    $message = 'Entry updated.';
                }
                $messageType = 'success';
                break;

            case 'delete':
                $entryManager->delete($editId);
                $message     = 'Entry deleted.';
                $messageType = 'success';
                break;
        }
    }
}

/* Load data */
$editEntry = null;
if ($action === 'edit' && $editId > 0) {
    $editEntry = $entryManager->getById($editId);
    if ($editEntry) {
        $contentType = $typeEngine->getById((int)$editEntry['content_type_id']);
        $typeSlug = $contentType['slug'] ?? '';
    }
}

$entries     = [];
$totalEntries = 0;
if ($action === 'list') {
    $filters = ['limit' => $perPage, 'offset' => ($pg - 1) * $perPage];
    if ($typeSlug) $filters['type_slug'] = $typeSlug;
    if ($search) $filters['search'] = $search;
    $entries = $entryManager->getEntries($filters);
    $totalEntries = $entryManager->countEntries($filters);
}

$types = $typeEngine->getAll();

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">
        <?php echo $contentType ? esc($contentType['name']) . ' Entries' : 'All Entries'; ?>
    </h1>
    <div class="admin-page-header__actions">
        <?php if ($typeSlug): ?>
        <a href="/admin/entries.php?action=add&type=<?php echo esc($typeSlug); ?>" class="admin-btn admin-btn--primary">New Entry</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<?php $schema = $contentType['fields_schema'] ?? []; ?>
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title"><?php echo $action === 'edit' ? 'Edit Entry' : 'New Entry'; ?></h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/entries.php<?php echo $action === 'edit' ? "?action=edit&id=$editId&type=$typeSlug" : "?action=add&type=$typeSlug"; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
            <?php if ($action === 'add'): ?>
            <input type="hidden" name="content_type_id" value="<?php echo esc($contentType['id'] ?? 0); ?>">
            <?php endif; ?>

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label">Title <span class="admin-required">*</span></label>
                    <input type="text" name="title" value="<?php echo esc($editEntry['title'] ?? ''); ?>" class="admin-form-input" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Status</label>
                    <select name="status" class="admin-form-input">
                        <option value="draft" <?php echo ($editEntry['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($editEntry['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo ($editEntry['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Sort Order</label>
                    <input type="number" name="sort_order" value="<?php echo esc($editEntry['sort_order'] ?? 0); ?>" class="admin-form-input" min="0">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Featured Image URL</label>
                    <input type="text" name="featured_image" value="<?php echo esc($editEntry['featured_image'] ?? ''); ?>" class="admin-form-input">
                </div>
                <div class="admin-form-group" style="grid-column:1/-1">
                    <label class="admin-form-label">Excerpt</label>
                    <textarea name="excerpt" class="admin-form-input" rows="2" style="width:100%"><?php echo esc($editEntry['excerpt'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Dynamic fields from schema -->
            <?php if (!empty($schema)): ?>
            <h3 class="u-mt-sm">Fields</h3>
            <div class="admin-form-grid">
                <?php foreach ($schema as $field):
                    $fieldKey  = $field['name'] ?? '';
                    $fieldVal  = $editEntry['fields'][$fieldKey] ?? '';
                ?>
                <div class="admin-form-group">
                    <label class="admin-form-label">
                        <?php echo esc($field['label'] ?? $fieldKey); ?>
                        <?php if ($field['required'] ?? false): ?><span class="admin-required">*</span><?php endif; ?>
                    </label>
                    <input type="hidden" name="field_key[]" value="<?php echo esc($fieldKey); ?>">
                    <?php if (($field['type'] ?? 'text') === 'textarea' || ($field['type'] ?? '') === 'richtext'): ?>
                        <textarea name="field_val[]" class="admin-form-input" rows="3" style="width:100%"><?php echo esc($fieldVal); ?></textarea>
                    <?php else: ?>
                        <input type="<?php echo esc($field['type'] ?? 'text'); ?>"
                               name="field_val[]"
                               value="<?php echo esc($fieldVal); ?>"
                               class="admin-form-input"
                               <?php echo ($field['required'] ?? false) ? 'required' : ''; ?>>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary admin-btn--lg">
                    <?php echo $action === 'edit' ? 'Update Entry' : 'Create Entry'; ?>
                </button>
                <a href="/admin/entries.php?type=<?php echo esc($typeSlug); ?>" class="admin-btn admin-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<!-- Type selector -->
<div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1.25rem">
    <a href="/admin/entries.php" class="admin-btn admin-btn--sm <?php echo !$typeSlug ? 'admin-btn--primary' : 'admin-btn--secondary'; ?>">All</a>
    <?php foreach ($types as $type): if ($type['has_entries']): ?>
        <a href="/admin/entries.php?type=<?php echo esc($type['slug']); ?>" class="admin-btn admin-btn--sm <?php echo $typeSlug === $type['slug'] ? 'admin-btn--primary' : 'admin-btn--secondary'; ?>">
            <?php echo esc($type['icon']); ?> <?php echo esc($type['name']); ?>
        </a>
    <?php endif; endforeach; ?>
</div>

<!-- Entries List -->
<div class="admin-card">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Entries</h2>
        <span class="admin-card__count"><?php echo count($entries); ?> entries</span>
    </div>
    <div class="admin-card__body">
        <?php if (empty($entries)): ?>
            <div class="admin-empty">
                <p>No entries found. <?php if ($typeSlug): ?><a href="/admin/entries.php?action=add&type=<?php echo esc($typeSlug); ?>">Create one.</a><?php endif; ?></p>
            </div>
        <?php else: ?>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th class="admin-table__hide-mobile">Type</th>
                            <th class="admin-table__hide-mobile">Slug</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><strong><?php echo esc($entry['title']); ?></strong></td>
                            <td class="admin-table__hide-mobile"><?php echo esc($entry['type_name']); ?></td>
                            <td class="admin-table__hide-mobile"><code><?php echo esc($entry['type_slug']); ?>/<?php echo esc($entry['slug']); ?></code></td>
                            <td><span class="admin-badge <?php echo $entry['status'] === 'published' ? 'admin-badge--success' : ($entry['status'] === 'archived' ? 'admin-badge--neutral' : 'admin-badge--warning'); ?>"><?php echo ucfirst($entry['status']); ?></span></td>
                            <td>
                                <div style="display:flex;gap:0.25rem">
                                    <a href="/admin/entries.php?action=edit&id=<?php echo $entry['id']; ?>&type=<?php echo esc($entry['type_slug']); ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Edit</a>
                                    <form method="POST" action="/admin/entries.php?action=edit&id=<?php echo $entry['id']; ?>&type=<?php echo esc($entry['type_slug']); ?>" style="display:inline" data-confirm="Delete this entry?">
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
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php';
