<?php
/**
 * Swap Design - Sections Admin Page
 *
 * Manage reusable page sections. Sections are content blocks
 * of various types that can be assigned to pages within layout zones.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Sections';
$currentSection = 'sections';

$sectionManager = new SectionManager();
$message        = '';
$messageType    = '';

$action    = $_GET['action'] ?? 'list';
$editId    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$typeFilter = $_GET['section_type'] ?? '';
$search    = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $postAction = $_POST['action'] ?? '';

        switch ($postAction) {
            case 'create':
            case 'update':
                $config = $thisBuildConfig();
                $data = [
                    'name'         => sanitizeString($_POST['name'] ?? ''),
                    'section_type' => sanitizeString($_POST['section_type'] ?? 'custom_html'),
                    'config'       => $config,
                    'status'       => sanitizeString($_POST['status'] ?? 'draft'),
                    'category'     => sanitizeString($_POST['category'] ?? ''),
                    'description'  => sanitizeString($_POST['description'] ?? ''),
                ];

                if ($postAction === 'create') {
                    $data['slug'] = sluggify($data['name']);
                    $sectionManager->create($data);
                    $message = 'Section created.';
                } else {
                    $sectionManager->update($editId, $data);
                    $message = 'Section updated.';
                }
                $messageType = 'success';
                break;

            case 'delete':
                $sectionManager->delete($editId);
                $message     = 'Section deleted.';
                $messageType = 'success';
                break;

            case 'duplicate':
                $sectionManager->duplicate($editId);
                $message     = 'Section duplicated.';
                $messageType = 'success';
                break;
        }
    }
}

/** Build config array from POST based on section type */
function thisBuildConfig(): array
{
    $type = $_POST['section_type'] ?? 'custom_html';
    $config = [];

    switch ($type) {
        case 'custom_html':
            $config['html'] = $_POST['config_html'] ?? '';
            break;

        case 'global_block':
            $config['block_id'] = (int)($_POST['config_block_id'] ?? 0);
            break;

        case 'component':
            $config['component_name'] = sanitizeString($_POST['config_component_name'] ?? '');
            break;

        case 'content_entries':
            $config['content_type_slug'] = sanitizeString($_POST['config_content_type'] ?? '');
            $config['limit']             = (int)($_POST['config_limit'] ?? 6);
            $config['display']           = sanitizeString($_POST['config_display'] ?? 'grid');
            $config['status']            = 'published';
            break;

        case 'dynamic_list':
            $config['query_type'] = sanitizeString($_POST['config_query_type'] ?? 'latest_pages');
            $config['limit']      = (int)($_POST['config_limit'] ?? 5);
            $config['template']   = sanitizeString($_POST['config_template'] ?? 'card');
            break;

        case 'shortcode':
            $config['code'] = sanitizeString($_POST['config_code'] ?? '');
            break;
    }

    return $config;
}

/* Load data */
$editSection = null;
if ($action === 'edit' && $editId > 0) {
    $editSection = $sectionManager->getById($editId);
}

$filters = [];
if ($typeFilter) $filters['section_type'] = $typeFilter;
if ($search) $filters['search'] = $search;
$sections = $sectionManager->getAll($filters);

/* Get content types and blocks for dropdowns */
$typeEngine = new ContentTypeEngine();
$contentTypes = $typeEngine->getAll();
$blockEngine  = new BlockEngine();
$blocks = $blockEngine->getBlocks(['status' => 'published']);

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Sections</h1>
    <div class="admin-page-header__actions">
        <a href="/admin/sections.php?action=add" class="admin-btn admin-btn--primary">New Section</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<?php
$editConfig = $editSection['config'] ?? [];
$editType   = $editSection['section_type'] ?? 'custom_html';
?>
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title"><?php echo $action === 'edit' ? 'Edit Section' : 'New Section'; ?></h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/sections.php<?php echo $action === 'edit' ? "?action=edit&id=$editId" : '?action=add'; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label" for="section_name">Section Name <span class="admin-required">*</span></label>
                    <input type="text" name="name" id="section_name" value="<?php echo esc($editSection['name'] ?? ''); ?>" class="admin-form-input" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label" for="section_type">Section Type</label>
                    <select name="section_type" id="section_type" class="admin-form-input" onchange="toggleConfigFields(this.value)">
                        <?php foreach (SectionManager::TYPES as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo $editType === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label" for="section_category">Category</label>
                    <input type="text" name="category" id="section_category" value="<?php echo esc($editSection['category'] ?? ''); ?>" class="admin-form-input" placeholder="e.g., Homepage, About">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label" for="section_status">Status</label>
                    <select name="status" id="section_status" class="admin-form-input">
                        <option value="draft" <?php echo ($editSection['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($editSection['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                </div>
                <div class="admin-form-group" style="grid-column:1/-1">
                    <label class="admin-form-label" for="section_description">Description</label>
                    <input type="text" name="description" id="section_description" value="<?php echo esc($editSection['description'] ?? ''); ?>" class="admin-form-input">
                </div>
            </div>

            <!-- Type-specific config fields -->
            <div class="section-config-fields u-mt-sm">

                <!-- Custom HTML -->
                <div class="section-config section-config--custom_html" <?php echo $editType !== 'custom_html' ? 'style="display:none"' : ''; ?>>
                    <div class="admin-form-group">
                        <label class="admin-form-label" for="config_html">HTML Content</label>
                        <textarea name="config_html" id="config_html" class="admin-form-input admin-form-textarea" rows="10"><?php echo esc($editConfig['html'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Global Block -->
                <div class="section-config section-config--global_block" <?php echo $editType !== 'global_block' ? 'style="display:none"' : ''; ?>>
                    <div class="admin-form-group">
                        <label class="admin-form-label" for="config_block_id">Select Block</label>
                        <select name="config_block_id" id="config_block_id" class="admin-form-input">
                            <option value="">-- Select a block --</option>
                            <?php foreach ($blocks as $block): ?>
                                <option value="<?php echo $block['id']; ?>" <?php echo ($editConfig['block_id'] ?? 0) == $block['id'] ? 'selected' : ''; ?>>
                                    <?php echo esc($block['name']); ?> (<?php echo esc($block['block_type']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Component -->
                <div class="section-config section-config--component" <?php echo $editType !== 'component' ? 'style="display:none"' : ''; ?>>
                    <div class="admin-form-group">
                        <label class="admin-form-label" for="config_component_name">Component Name</label>
                        <input type="text" name="config_component_name" id="config_component_name" value="<?php echo esc($editConfig['component_name'] ?? ''); ?>" class="admin-form-input" placeholder="e.g., cta, faq, contact-form">
                    </div>
                </div>

                <!-- Content Entries -->
                <div class="section-config section-config--content_entries" <?php echo $editType !== 'content_entries' ? 'style="display:none"' : ''; ?>>
                    <div class="admin-form-grid">
                        <div class="admin-form-group">
                            <label class="admin-form-label" for="config_content_type">Content Type</label>
                            <select name="config_content_type" id="config_content_type" class="admin-form-input">
                                <option value="">-- Select type --</option>
                                <?php foreach ($contentTypes as $ct): ?>
                                    <option value="<?php echo esc($ct['slug']); ?>" <?php echo ($editConfig['content_type_slug'] ?? '') === $ct['slug'] ? 'selected' : ''; ?>>
                                        <?php echo esc($ct['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-form-group">
                            <label class="admin-form-label" for="config_limit">Max Entries</label>
                            <input type="number" name="config_limit" id="config_limit" value="<?php echo esc($editConfig['limit'] ?? 6); ?>" class="admin-form-input" min="1" max="50">
                        </div>
                        <div class="admin-form-group">
                            <label class="admin-form-label" for="config_display">Display Style</label>
                            <select name="config_display" id="config_display" class="admin-form-input">
                                <option value="grid" <?php echo ($editConfig['display'] ?? 'grid') === 'grid' ? 'selected' : ''; ?>>Grid</option>
                                <option value="list" <?php echo ($editConfig['display'] ?? '') === 'list' ? 'selected' : ''; ?>>List</option>
                                <option value="carousel" <?php echo ($editConfig['display'] ?? '') === 'carousel' ? 'selected' : ''; ?>>Carousel</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Dynamic List -->
                <div class="section-config section-config--dynamic_list" <?php echo $editType !== 'dynamic_list' ? 'style="display:none"' : ''; ?>>
                    <div class="admin-form-grid">
                        <div class="admin-form-group">
                            <label class="admin-form-label" for="config_query_type">Query Type</label>
                            <select name="config_query_type" id="config_query_type" class="admin-form-input">
                                <option value="latest_pages" <?php echo ($editConfig['query_type'] ?? '') === 'latest_pages' ? 'selected' : ''; ?>>Latest Pages</option>
                                <option value="featured_pages" <?php echo ($editConfig['query_type'] ?? '') === 'featured_pages' ? 'selected' : ''; ?>>Featured Pages</option>
                                <option value="latest_entries" <?php echo ($editConfig['query_type'] ?? '') === 'latest_entries' ? 'selected' : ''; ?>>Latest Entries</option>
                            </select>
                        </div>
                        <div class="admin-form-group">
                            <label class="admin-form-label" for="config_limit_dynamic">Limit</label>
                            <input type="number" name="config_limit" id="config_limit_dynamic" value="<?php echo esc($editConfig['limit'] ?? 5); ?>" class="admin-form-input" min="1" max="20">
                        </div>
                        <div class="admin-form-group">
                            <label class="admin-form-label" for="config_template">Template</label>
                            <select name="config_template" id="config_template" class="admin-form-input">
                                <option value="card" <?php echo ($editConfig['template'] ?? 'card') === 'card' ? 'selected' : ''; ?>>Card</option>
                                <option value="list" <?php echo ($editConfig['template'] ?? '') === 'list' ? 'selected' : ''; ?>>List</option>
                                <option value="minimal" <?php echo ($editConfig['template'] ?? '') === 'minimal' ? 'selected' : ''; ?>>Minimal</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Shortcode -->
                <div class="section-config section-config--shortcode" <?php echo $editType !== 'shortcode' ? 'style="display:none"' : ''; ?>>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Shortcode Code</label>
                        <input type="text" name="config_code" value="<?php echo esc($editConfig['code'] ?? ''); ?>" class="admin-form-input" placeholder="e.g., contact-form">
                    </div>
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary admin-btn--lg">
                    <?php echo $action === 'edit' ? 'Update Section' : 'Create Section'; ?>
                </button>
                <a href="/admin/sections.php" class="admin-btn admin-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Sections List -->
<div class="admin-card">
    <div class="admin-card__header">
        <h2 class="admin-card__title">All Sections</h2>
        <span class="admin-card__count"><?php echo count($sections); ?> sections</span>
    </div>
    <div class="admin-card__body">
        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;margin-bottom:1rem">
            <form method="GET" action="/admin/sections.php" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;flex:1">
                <input type="text" name="search" value="<?php echo esc($search); ?>" class="admin-form-input" placeholder="Search..." style="max-width:200px" aria-label="Search sections">
                <select name="section_type" class="admin-form-input" style="max-width:160px" aria-label="Filter by type">
                    <option value="">All Types</option>
                    <?php foreach (SectionManager::TYPES as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo $typeFilter === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">Filter</button>
                <?php if ($search || $typeFilter): ?>
                    <a href="/admin/sections.php" class="admin-btn admin-btn--secondary admin-btn--sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($sections)): ?>
            <div class="admin-empty">
                <p>No sections found. <a href="/admin/sections.php?action=add">Create your first section.</a></p>
            </div>
        <?php else: ?>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="admin-table__hide-mobile">Type</th>
                            <th class="admin-table__hide-mobile">Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sections as $section): ?>
                        <tr>
                            <td><strong><?php echo esc($section['name']); ?></strong></td>
                            <td class="admin-table__hide-mobile">
                                <span class="admin-badge admin-badge--info"><?php echo esc(SectionManager::TYPES[$section['section_type']] ?? $section['section_type']); ?></span>
                            </td>
                            <td class="admin-table__hide-mobile"><?php echo esc($section['category'] ?? '-'); ?></td>
                            <td><span class="admin-badge <?php echo $section['status'] === 'published' ? 'admin-badge--success' : 'admin-badge--warning'; ?>"><?php echo ucfirst($section['status']); ?></span></td>
                            <td>
                                <div style="display:flex;gap:0.25rem">
                                    <a href="/admin/sections.php?action=edit&id=<?php echo $section['id']; ?>" class="admin-btn admin-btn--sm admin-btn--secondary">Edit</a>
                                    <form method="POST" action="/admin/sections.php?action=edit&id=<?php echo $section['id']; ?>" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
                                        <input type="hidden" name="action" value="duplicate">
                                        <button type="submit" class="admin-btn admin-btn--sm admin-btn--secondary">Dup</button>
                                    </form>
                                    <form method="POST" action="/admin/sections.php?action=edit&id=<?php echo $section['id']; ?>" style="display:inline" data-confirm="Delete this section?">
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
function toggleConfigFields(type) {
    document.querySelectorAll('.section-config').forEach(function(el) {
        el.style.display = 'none';
    });
    var target = document.querySelector('.section-config--' + type);
    if (target) target.style.display = 'block';
}
</script>

<?php require __DIR__ . '/includes/footer.php';
