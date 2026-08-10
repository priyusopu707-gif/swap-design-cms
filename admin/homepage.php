<?php
/**
 * Swap Design - Homepage Editor
 *
 * Full homepage section management page.
 * Drag-drop reorder, enable/disable, inline form editing,
 * device preview, auto-save, draft/publish workflow.
 *
 * @package SwapDesign
 */

require __DIR__ . '/includes/init.php';
Auth::require();

$homepageManager = new HomepageManager();
$homepageManager->seedDefaults();

$sections = $homepageManager->getAll();
$currentSection = 'homepage';
$pageTitle = 'Homepage Editor';

$publishedCount = 0;
$enabledCount = 0;
foreach ($sections as $s) {
    if ($s['status'] === 'published') $publishedCount++;
    if ($s['is_enabled']) $enabledCount++;
}
$totalCount = count($sections);

/** @see HomepageManager::SECTIONS */
$sectionDefs = HomepageManager::SECTIONS;

function renderField(string $type, string $name, $value = '', string $label = '', array $attrs = []): void
{
    $escName  = esc($name);
    $escLabel = $label ? esc($label) : '';
    $escValue = is_string($value) ? esc($value) : '';

    switch ($type) {
        case 'text':
            printf(
                '<input type="text" name="%s" value="%s" class="hp-field hp-field--text"%s>',
                $escName,
                $escValue,
                $attrs['extra'] ?? ''
            );
            break;
        case 'textarea':
            printf(
                '<textarea name="%s" class="hp-field hp-field--textarea"%s>%s</textarea>',
                $escName,
                $attrs['extra'] ?? '',
                $escValue
            );
            break;
        case 'image':
            $preview = $escValue ? '<img src="' . $escValue . '" alt="" class="hp-image-preview">' : '<span class="hp-image-empty">No image</span>';
            printf(
                '<div class="hp-image-field">
                    <div class="hp-image-preview-wrap">%s</div>
                    <div class="hp-image-inputs">
                        <input type="text" name="%s" value="%s" class="hp-field hp-field--text" placeholder="Image URL or media ID">
                        <button type="button" class="btn btn--sm btn--outline hp-media-pick" data-target="%s">Browse</button>
                    </div>
                </div>',
                $preview,
                $escName,
                $escValue,
                $escName
            );
            break;
        case 'icon':
            printf(
                '<input type="text" name="%s" value="%s" class="hp-field hp-field--text" placeholder="icon name"%s><small class="hp-help">%s</small>',
                $escName,
                $escValue,
                $attrs['extra'] ?? '',
                $attrs['help'] ?? ''
            );
            break;
        case 'url':
            printf(
                '<input type="text" name="%s" value="%s" class="hp-field hp-field--text" placeholder="/path or https://..."%s>',
                $escName,
                $escValue,
                $attrs['extra'] ?? ''
            );
            break;
        case 'number':
            printf(
                '<input type="number" name="%s" value="%s" class="hp-field hp-field--number" min="1" max="20"%s>',
                $escName,
                $escValue,
                $attrs['extra'] ?? ''
            );
            break;
        case 'select':
            $opts = $attrs['options'] ?? [];
            printf('<select name="%s" class="hp-field hp-field--select"%s>', $escName, $attrs['extra'] ?? '');
            foreach ($opts as $optVal => $optLabel) {
                $selected = ((string)$optVal === (string)$value) ? ' selected' : '';
                printf('<option value="%s"%s>%s</option>', esc($optVal), $selected, esc($optLabel));
            }
            echo '</select>';
            break;
        case 'toggle':
            $checked = $value === '1' || $value === true ? ' checked' : '';
            printf(
                '<label class="hp-toggle"><input type="checkbox" name="%s" value="1"%s class="hp-field hp-field--toggle"><span class="hp-toggle__slider"></span></label>',
                $escName,
                $checked
            );
            break;
    }
}

function renderFormRow(string $label, string $name, string $type, $value = '', array $extra = []): void
{
    ?>
    <div class="hp-row" data-field="<?php echo esc($name); ?>">
        <label class="hp-row__label"><?php echo esc($label); ?></label>
        <div class="hp-row__field">
            <?php renderField($type, $name, $value, '', $extra); ?>
        </div>
    </div>
    <?php
}

function renderSimpleConfigForm(string $sectionKey, array $config): void
{
    $defs = HomepageManager::SECTIONS[$sectionKey]['config'] ?? [];

    foreach ($defs as $fieldName => $defaultVal) {
        $curVal = $config[$fieldName] ?? $defaultVal;

        if ($fieldName === 'items' || $fieldName === 'features' || $fieldName === 'steps') {
            continue;
        }

        $label = ucfirst(str_replace('_', ' ', $fieldName));
        $type  = 'text';

        if (preg_match('/description|answer/', $fieldName)) $type = 'textarea';
        elseif (preg_match('/image$/', $fieldName)) $type = 'image';
        elseif (preg_match('/icon$/', $fieldName)) $type = 'icon';
        elseif (preg_match('/url$/i', $fieldName)) $type = 'url';
        elseif (preg_match('/^(display_count)$/', $fieldName)) $type = 'number';
        elseif (preg_match('/^(sort_order)$/i', $fieldName)) {
            $type = 'select';
            $extra = ['options' => ['manual' => 'Manual', 'newest' => 'Newest', 'oldest' => 'Oldest', 'alphabetical' => 'Alphabetical']];
        } elseif (preg_match('/^(layout|display_style|style)$/', $fieldName)) {
            $type = 'select';
            $extra = [];
            if ($fieldName === 'layout') $extra['options'] = ['grid' => 'Grid', 'list' => 'List', 'masonry' => 'Masonry', 'featured_first' => 'Featured First'];
            elseif ($fieldName === 'display_style') $extra['options'] = ['carousel' => 'Carousel', 'grid' => 'Grid', 'list' => 'List', 'masonry' => 'Masonry'];
            elseif ($fieldName === 'style') $extra['options'] = ['accordion' => 'Accordion', 'list' => 'Simple List', 'columns' => 'Columns'];
        } elseif (preg_match('/^(show_)/', $fieldName)) {
            $type = 'toggle';
        }

        $extra = $extra ?? [];
        if ($type === 'icon') $extra['help'] = 'Material or custom icon name';
        if ($type === 'toggle') {
            renderFormRow($label, $fieldName, $type, $curVal, $extra);
            continue;
        }

        $extra['extra'] = $extra['extra'] ?? '';
        renderFormRow($label, $fieldName, $type, $curVal, $extra);
    }
}

function renderRepeaterForm(string $sectionKey, array $config): void
{
    $defs  = HomepageManager::SECTIONS[$sectionKey]['config'] ?? [];
    $items = [];

    if ($sectionKey === 'why_choose') {
        $items = $config['items'] ?? $defs['items'] ?? [];
        $fields = [
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'placeholder' => 'star'],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'placeholder' => 'Feature title'],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'Feature description'],
        ];
    } elseif ($sectionKey === 'experience') {
        $items = $config['items'] ?? $defs['items'] ?? [];
        $fields = [
            ['name' => 'number', 'label' => 'Number', 'type' => 'text', 'placeholder' => '8'],
            ['name' => 'suffix', 'label' => 'Suffix', 'type' => 'text', 'placeholder' => '+'],
            ['name' => 'label', 'label' => 'Label', 'type' => 'text', 'placeholder' => 'Years Experience'],
        ];
    } elseif ($sectionKey === 'about') {
        $items = $config['features'] ?? $defs['features'] ?? [];
        $fields = [
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'placeholder' => 'check'],
            ['name' => 'text', 'label' => 'Text', 'type' => 'text', 'placeholder' => 'Feature text'],
        ];
    } elseif ($sectionKey === 'process') {
        $items = $config['steps'] ?? $defs['steps'] ?? [];
        $fields = [
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon', 'placeholder' => 'search'],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'placeholder' => 'Step title'],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'Step description'],
        ];
    } elseif ($sectionKey === 'faq') {
        $items = $config['items'] ?? $defs['items'] ?? [];
        $fields = [
            ['name' => 'question', 'label' => 'Question', 'type' => 'text', 'placeholder' => 'FAQ question'],
            ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'placeholder' => 'FAQ answer'],
        ];
    } else {
        return;
    }

    $repeaterName = ($sectionKey === 'about') ? 'features' : (($sectionKey === 'faq') ? 'items' : (($sectionKey === 'experience') ? 'items' : (($sectionKey === 'process') ? 'steps' : 'items')));
    ?>
    <div class="hp-repeater" data-repeater="<?php echo esc($repeaterName); ?>" data-section="<?php echo esc($sectionKey); ?>">
        <h4 class="hp-repeater__title">
            <?php echo ucfirst(str_replace('_', ' ', $repeaterName)); ?>
            <button type="button" class="btn btn--sm btn--outline hp-repeater-add">+ Add Item</button>
        </h4>
        <div class="hp-repeater__items">
            <?php foreach ($items as $idx => $item): ?>
                <div class="hp-repeater__item" data-index="<?php echo $idx; ?>">
                    <div class="hp-repeater__header">
                        <span class="hp-repeater__drag" aria-label="Drag to reorder">&#x2630;</span>
                        <span class="hp-repeater__index">#<?php echo $idx + 1; ?></span>
                        <button type="button" class="hp-repeater__remove" aria-label="Remove item">&times;</button>
                    </div>
                    <div class="hp-repeater__body">
                        <?php foreach ($fields as $field): ?>
                            <div class="hp-row hp-row--repeater" data-field="<?php echo esc($field['name']); ?>">
                                <label class="hp-row__label"><?php echo esc($field['label']); ?></label>
                                <div class="hp-row__field">
                                    <?php
                                    $fv = $item[$field['name']] ?? '';
                                    $fe = ['extra' => 'placeholder="' . esc($field['placeholder'] ?? '') . '"'];
                                    renderField($field['type'], $repeaterName . '[' . $idx . '][' . $field['name'] . ']', $fv, '', $fe);
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<link rel="stylesheet" href="/admin/assets/css/homepage-editor.css?v=1">

<div class="admin-page">

    <div class="admin-page__header">
        <h1 class="admin-page__title"><?php echo esc($pageTitle); ?></h1>
        <div class="admin-page__actions">
            <span class="hp-status-summary">
                <span class="hp-stat"><?php echo $enabledCount; ?>/<?php echo $totalCount; ?> enabled</span>
                <span class="hp-stat hp-stat--<?php echo $publishedCount === $totalCount ? 'ok' : 'warn'; ?>"><?php echo $publishedCount; ?>/<?php echo $totalCount; ?> published</span>
            </span>
            <div class="hp-preview-toggles">
                <button type="button" class="hp-preview-btn hp-preview-btn--active" data-preview="desktop" aria-label="Desktop preview" title="Desktop">&#x1F5A5;</button>
                <button type="button" class="hp-preview-btn" data-preview="tablet" aria-label="Tablet preview" title="Tablet">&#x1F4BB;</button>
                <button type="button" class="hp-preview-btn" data-preview="mobile" aria-label="Mobile preview" title="Mobile">&#x1F4F1;</button>
            </div>
            <button type="button" class="btn btn--primary btn--sm" id="hp-publish-all">Publish All</button>
            <a href="/" target="_blank" class="btn btn--outline btn--sm" id="hp-live-preview">Live Preview</a>
        </div>
    </div>

    <noscript>
        <div class="alert alert--warning">JavaScript is required for the homepage editor drag-drop and auto-save features.</div>
    </noscript>

    <div class="hp-editor" id="hp-editor">
        <?php
        $counter = 0;
        foreach ($sections as $section):
            $key        = $section['section_key'];
            $id         = (int)$section['id'];
            $label      = $section['label'];
            $enabled    = (bool)$section['is_enabled'];
            $status     = $section['status'];
            $config     = $section['config'] ?? [];
            $hasRepeater = in_array($key, ['about', 'experience', 'why_choose', 'process', 'faq'], true);
        ?>
            <div class="hp-section-card"
                 data-section-id="<?php echo $id; ?>"
                 data-section-key="<?php echo esc($key); ?>"
                 data-sort="<?php echo $counter; ?>"
                 draggable="true"
                 id="hp-section-<?php echo $id; ?>">

                <div class="hp-section-card__header">
                    <button type="button" class="hp-section-card__drag" aria-label="Drag to reorder">&#x2630;</button>
                    <span class="hp-section-card__icon hp-section-card__icon--<?php echo esc($key); ?>"></span>
                    <span class="hp-section-card__label"><?php echo esc($label); ?></span>

                    <label class="hp-section-card__toggle-switch">
                        <input type="checkbox"
                               class="hp-section-card__toggle-input"
                               data-action="toggle"
                               data-section-id="<?php echo $id; ?>"
                               <?php echo $enabled ? 'checked' : ''; ?>>
                        <span class="hp-section-card__toggle-slider"></span>
                    </label>

                    <span class="hp-section-card__status hp-status--<?php echo $status; ?>"
                          data-status-for="<?php echo $id; ?>">
                        <?php echo ucfirst($status); ?>
                    </span>

                    <button type="button"
                            class="hp-section-card__expand"
                            data-section-id="<?php echo $id; ?>"
                            aria-expanded="false"
                            aria-label="Expand section editor">
                        <svg width="12" height="12" viewBox="0 0 12 12"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                    </button>
                </div>

                <div class="hp-section-card__body" id="hp-body-<?php echo $id; ?>" hidden>
                    <form class="hp-section-form"
                          data-section-id="<?php echo $id; ?>"
                          data-section-key="<?php echo esc($key); ?>"
                          data-autosave="true">

                        <?php renderSimpleConfigForm($key, $config); ?>
                        <?php if ($hasRepeater) renderRepeaterForm($key, $config); ?>

                        <div class="hp-form-actions">
                            <button type="submit" class="btn btn--primary btn--sm">Save</button>
                            <button type="button" class="btn btn--sm btn--outline hp-save-draft" data-section-id="<?php echo $id; ?>">Save as Draft</button>
                            <span class="hp-save-status" data-status-for="<?php echo $id; ?>"></span>
                        </div>
                    </form>
                </div>
            </div>
        <?php
            $counter++;
        endforeach;
        ?>
    </div>

    <div class="hp-editor-empty" id="hp-editor-empty" style="display:none;">
        <p>No homepage sections found. Seed defaults to get started.</p>
        <button type="button" class="btn btn--primary" id="hp-seed-defaults">Seed Default Sections</button>
    </div>

</div>

<script src="/admin/assets/js/homepage-editor.js?v=1"></script>
<script>
window.HP_CSRF_TOKEN = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
window.HP_AJAX_URL  = '/admin/ajax/homepage.php';
</script>

<div class="hp-toast" id="hp-toast" role="status" aria-live="polite" hidden>
    <span class="hp-toast__message"></span>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>