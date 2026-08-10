<?php
/**
 * Swap Design - About Page Editor
 *
 * Full about page section management.
 * Drag-drop reorder, enable/disable, inline form editing,
 * auto-save, draft/publish, and revision history.
 *
 * @package SwapDesign
 */

require __DIR__ . '/includes/init.php';
Auth::require();

$aboutManager = new AboutManager();
$aboutManager->seedDefaults();

$sections = $aboutManager->getAll();
$relatedPortfolio = $aboutManager->getRelatedPortfolio();
$allPortfolioItems = $aboutManager->getAllPortfolioItems();
$relatedBlocks = $aboutManager->getRelatedBlocks();
$allGlobalBlocks = $aboutManager->getAllGlobalBlocks();
$currentSection = 'about';
$pageTitle = 'About Page Editor';

$publishedCount = 0;
$enabledCount = 0;
foreach ($sections as $s) {
    if ($s['status'] === 'published') $publishedCount++;
    if ($s['is_enabled']) $enabledCount++;
}
$totalCount = count($sections);

$sectionDefs = AboutManager::SECTIONS;

function renderField(string $type, string $name, $value = '', string $label = '', array $attrs = []): void
{
    $escName  = esc($name);
    $escValue = is_string($value) ? esc($value) : '';

    switch ($type) {
        case 'text':
            printf(
                '<input type="text" name="%s" value="%s" class="about-field about-field--text"%s>',
                $escName,
                $escValue,
                $attrs['extra'] ?? ''
            );
            break;
        case 'textarea':
            printf(
                '<textarea name="%s" class="about-field about-field--textarea"%s>%s</textarea>',
                $escName,
                $attrs['extra'] ?? '',
                $escValue
            );
            break;
        case 'image':
            $preview = $escValue ? '<img src="' . $escValue . '" alt="" class="about-image-preview">' : '<span class="about-image-empty">No image</span>';
            printf(
                '<div class="about-image-field">
                    <div class="about-image-preview-wrap">%s</div>
                    <div class="about-image-inputs">
                        <input type="text" name="%s" value="%s" class="about-field about-field--text" placeholder="Image URL or media ID">
                        <button type="button" class="btn btn--sm btn--outline about-media-pick" data-target="%s">Browse</button>
                    </div>
                </div>',
                $preview,
                $escName,
                $escValue,
                $escName
            );
            break;
        case 'number':
            printf(
                '<input type="number" name="%s" value="%s" class="about-field about-field--number"%s>',
                $escName,
                $escValue,
                $attrs['extra'] ?? ''
            );
            break;
        case 'toggle':
            $checked = $value ? ' checked' : '';
            printf(
                '<label class="about-toggle"><input type="checkbox" name="%s" value="1"%s%s><span class="about-toggle__slider"></span></label>',
                $escName,
                $checked,
                $attrs['extra'] ?? ''
            );
            break;
    }
}

function renderRepeaterRow(string $prefix, array $item, int $index, array $fields): void
{
    ?>
    <div class="about-repeater__row" data-index="<?php echo $index; ?>">
        <div class="about-repeater__row-header">
            <span class="about-repeater__row-handle" title="Drag to reorder">::</span>
            <span class="about-repeater__row-label"><?php echo esc($item[$fields[0]['name']] ?? 'Item ' . ($index + 1)); ?></span>
            <button type="button" class="about-repeater__row-remove" title="Remove">&times;</button>
        </div>
        <div class="about-repeater__row-body">
            <?php foreach ($fields as $field):
                $fname = $prefix . '[' . $index . '][' . $field['name'] . ']';
                $fval  = $item[$field['name']] ?? ($field['default'] ?? '');
                ?>
                <div class="about-form__field">
                    <?php if (!empty($field['label'])): ?>
                        <label class="about-form__label"><?php echo esc($field['label']); ?></label>
                    <?php endif; ?>
                    <?php renderField($field['type'], $fname, $fval, '', $field['attrs'] ?? []); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div class="admin-page-header__left">
        <h1 class="admin-page-header__title">About Page Editor</h1>
        <div class="admin-page-header__stats">
            <span class="admin-stat"><?php echo $totalCount; ?> sections</span>
            <span class="admin-stat admin-stat--success"><?php echo $enabledCount; ?> enabled</span>
            <span class="admin-stat admin-stat--info"><?php echo $publishedCount; ?> published</span>
        </div>
    </div>
    <div class="admin-page-header__right">
        <button type="button" class="btn btn--outline" id="about-publish-all">Publish All</button>
        <a href="/about" target="_blank" class="btn btn--outline">Preview Page</a>
    </div>
</div>

<div class="admin-content admin-content--about" id="about-editor">
    <div class="admin-content__body">

        <div class="admin-toolbar">
            <span class="admin-toolbar__hint">Drag sections to reorder. Click a section to edit. Toggle to enable/disable.</span>
        </div>

        <div class="about-sections" id="about-section-list" data-ajax="/admin/ajax/about.php">
            <?php foreach ($sections as $section):
                $skey   = $section['section_key'];
                $def    = $sectionDefs[$skey] ?? null;
                $config = $section['config'] ?? [];
                $sid    = (int)$section['id'];
                $enabled= (bool)$section['is_enabled'];
                $status = $section['status'];
                $label  = esc($section['section_label']);
            ?>
            <div class="about-section-card<?php echo $enabled ? '' : ' about-section-card--disabled'; ?><?php echo $status === 'draft' ? ' about-section-card--draft' : ''; ?>"
                 data-section-id="<?php echo $sid; ?>"
                 data-section-key="<?php echo esc($skey); ?>"
                 data-status="<?php echo $status; ?>"
                 data-sort="<?php echo (int)$section['sort_order']; ?>">
                <div class="about-section-card__header">
                    <span class="about-section-card__handle" title="Drag to reorder">::</span>
                    <span class="about-section-card__title"><?php echo $label; ?></span>
                    <div class="about-section-card__actions">
                        <label class="about-section-card__toggle" title="<?php echo $enabled ? 'Enabled' : 'Disabled'; ?>">
                            <input type="checkbox" class="about-section-enable" <?php echo $enabled ? 'checked' : ''; ?>>
                            <span class="about-section-card__toggle-slider"></span>
                        </label>
                        <span class="about-section-card__status-badge about-section-card__status-badge--<?php echo $status; ?>"><?php echo $status; ?></span>
                        <button type="button" class="about-section-card__expand" title="Edit section">&#9660;</button>
                    </div>
                </div>
                <div class="about-section-card__body" hidden>
                    <form class="about-section-form" data-section-id="<?php echo $sid; ?>" data-section-key="<?php echo esc($skey); ?>" autocomplete="off">
                        <div class="about-form__grid">
                            <?php
                            $flatFields = [];
                            $repeaterFields = [];

                            /* Hero */
                            if ($skey === 'hero'):
                                renderField('text', 'title', $config['title'] ?? '', 'Title');
                                renderField('text', 'subtitle', $config['subtitle'] ?? '', 'Subtitle');
                                renderField('textarea', 'intro', $config['intro'] ?? '', 'Introduction', ['extra' => ' rows="2"']);
                                renderField('image', 'hero_image', $config['hero_image'] ?? '', 'Hero Image');
                                renderField('text', 'cta_text', $config['cta_text'] ?? '', 'CTA Button Text');
                                renderField('text', 'cta_url', $config['cta_url'] ?? '', 'CTA Button URL');

                            /* Personal Introduction */
                            elseif ($skey === 'personal_intro'):
                                renderField('text', 'name', $config['name'] ?? '', 'Name');
                                renderField('text', 'professional_title', $config['professional_title'] ?? '', 'Professional Title');
                                renderField('text', 'experience', $config['experience'] ?? '', 'Experience');
                                renderField('textarea', 'short_bio', $config['short_bio'] ?? '', 'Short Bio', ['extra' => ' rows="2"']);
                                renderField('textarea', 'long_bio', $config['long_bio'] ?? '', 'Long Bio', ['extra' => ' rows="4"']);
                                renderField('image', 'signature_image', $config['signature_image'] ?? '', 'Signature Image (Optional)');

                            /* My Story */
                            elseif ($skey === 'my_story'):
                                renderField('text', 'title', $config['title'] ?? '', 'Title');
                                renderField('textarea', 'description', $config['description'] ?? '', 'Description', ['extra' => ' rows="2"']);
                                ?>
                                <div class="about-form__field about-form__field--full">
                                    <label class="about-form__label">Timeline</label>
                                    <div class="about-repeater" data-name="timeline" data-fields='[{"name":"year","label":"Year","type":"text"},{"name":"title","label":"Title","type":"text"},{"name":"description","label":"Description","type":"textarea"}]'>
                                        <?php foreach (($config['timeline'] ?? []) as $idx => $item):
                                            renderRepeaterRow('timeline', $item, $idx, [
                                                ['name' => 'year', 'label' => 'Year', 'type' => 'text'],
                                                ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                                                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'attrs' => ['extra' => ' rows="2"']],
                                            ]);
                                        endforeach; ?>
                                    </div>
                                    <button type="button" class="btn btn--sm btn--outline about-repeater-add">+ Add Timeline Item</button>
                                </div>
                                <?php

                            /* Experience */
                            elseif ($skey === 'experience'):
                                renderField('text', 'title', $config['title'] ?? '', 'Title');
                                renderField('textarea', 'description', $config['description'] ?? '', 'Description', ['extra' => ' rows="2"']);
                                renderField('number', 'years', $config['years'] ?? '', 'Years of Experience');
                                renderField('number', 'projects', $config['projects'] ?? '', 'Projects Completed');
                                renderField('number', 'industries', $config['industries'] ?? '', 'Industries Served');
                                renderField('toggle', 'show_counters', $config['show_counters'] ?? '1', 'Show Counter Animation');

                            /* Core Services */
                            elseif ($skey === 'core_services'):
                                renderField('text', 'title', $config['title'] ?? '', 'Title');
                                renderField('textarea', 'description', $config['description'] ?? '', 'Description', ['extra' => ' rows="2"']);
                                renderField('number', 'display_count', $config['display_count'] ?? '6', 'Number of Services');
                                ?>
                                <div class="about-form__field">
                                    <label class="about-form__label">Layout</label>
                                    <select name="layout" class="about-field about-field--select">
                                        <option value="grid" <?php echo ($config['layout'] ?? '') === 'grid' ? 'selected' : ''; ?>>Grid</option>
                                        <option value="list" <?php echo ($config['layout'] ?? '') === 'list' ? 'selected' : ''; ?>>List</option>
                                    </select>
                                </div>
                                <?php

                            /* Working Process */
                            elseif ($skey === 'working_process'):
                                renderField('text', 'title', $config['title'] ?? '', 'Title');
                                renderField('textarea', 'description', $config['description'] ?? '', 'Description', ['extra' => ' rows="2"']);
                                ?>
                                <div class="about-form__field about-form__field--full">
                                    <label class="about-form__label">Process Steps</label>
                                    <div class="about-repeater" data-name="steps" data-fields='[{"name":"icon","label":"Icon","type":"text"},{"name":"title","label":"Title","type":"text"},{"name":"description","label":"Description","type":"textarea"}]'>
                                        <?php foreach (($config['steps'] ?? []) as $idx => $item):
                                            renderRepeaterRow('steps', $item, $idx, [
                                                ['name' => 'icon', 'label' => 'Icon', 'type' => 'text'],
                                                ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                                                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'attrs' => ['extra' => ' rows="2"']],
                                            ]);
                                        endforeach; ?>
                                    </div>
                                    <button type="button" class="btn btn--sm btn--outline about-repeater-add">+ Add Step</button>
                                </div>
                                <?php

                            /* Why Work With Me */
                            elseif ($skey === 'why_work_with_me'):
                                renderField('text', 'title', $config['title'] ?? '', 'Title');
                                renderField('textarea', 'description', $config['description'] ?? '', 'Description', ['extra' => ' rows="2"']);
                                ?>
                                <div class="about-form__field about-form__field--full">
                                    <label class="about-form__label">Cards</label>
                                    <div class="about-repeater" data-name="cards" data-fields='[{"name":"icon","label":"Icon","type":"text"},{"name":"title","label":"Title","type":"text"},{"name":"description","label":"Description","type":"textarea"}]'>
                                        <?php foreach (($config['cards'] ?? []) as $idx => $item):
                                            renderRepeaterRow('cards', $item, $idx, [
                                                ['name' => 'icon', 'label' => 'Icon', 'type' => 'text'],
                                                ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                                                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'attrs' => ['extra' => ' rows="2"']],
                                            ]);
                                        endforeach; ?>
                                    </div>
                                    <button type="button" class="btn btn--sm btn--outline about-repeater-add">+ Add Card</button>
                                </div>
                                <?php

                            /* Skills */
                            elseif ($skey === 'skills'):
                                renderField('text', 'title', $config['title'] ?? '', 'Title');
                                renderField('textarea', 'description', $config['description'] ?? '', 'Description', ['extra' => ' rows="2"']);
                                ?>
                                <div class="about-form__field">
                                    <label class="about-form__label">Display Style</label>
                                    <select name="display_style" class="about-field about-field--select">
                                        <option value="bars" <?php echo ($config['display_style'] ?? '') === 'bars' ? 'selected' : ''; ?>>Progress Bars</option>
                                        <option value="tags" <?php echo ($config['display_style'] ?? '') === 'tags' ? 'selected' : ''; ?>>Tags</option>
                                    </select>
                                </div>
                                <div class="about-form__field about-form__field--full">
                                    <label class="about-form__label">Skills</label>
                                    <div class="about-repeater" data-name="skills" data-fields='[{"name":"name","label":"Skill Name","type":"text"},{"name":"category","label":"Category","type":"text"},{"name":"percentage","label":"Percentage (0-100)","type":"number"}]'>
                                        <?php foreach (($config['skills'] ?? []) as $idx => $item):
                                            renderRepeaterRow('skills', $item, $idx, [
                                                ['name' => 'name', 'label' => 'Skill Name', 'type' => 'text'],
                                                ['name' => 'category', 'label' => 'Category', 'type' => 'text'],
                                                ['name' => 'percentage', 'label' => 'Percentage', 'type' => 'number'],
                                            ]);
                                        endforeach; ?>
                                    </div>
                                    <button type="button" class="btn btn--sm btn--outline about-repeater-add">+ Add Skill</button>
                                </div>
                                <?php

                            /* Tools */
                            elseif ($skey === 'tools'):
                                renderField('text', 'title', $config['title'] ?? '', 'Title');
                                renderField('textarea', 'description', $config['description'] ?? '', 'Description', ['extra' => ' rows="2"']);
                                ?>
                                <div class="about-form__field about-form__field--full">
                                    <label class="about-form__label">Tools</label>
                                    <div class="about-repeater" data-name="tools" data-fields='[{"name":"name","label":"Tool Name","type":"text"},{"name":"category","label":"Category","type":"text"},{"name":"logo_url","label":"Logo URL","type":"text"}]'>
                                        <?php foreach (($config['tools'] ?? []) as $idx => $item):
                                            renderRepeaterRow('tools', $item, $idx, [
                                                ['name' => 'name', 'label' => 'Tool Name', 'type' => 'text'],
                                                ['name' => 'category', 'label' => 'Category', 'type' => 'text'],
                                                ['name' => 'logo_url', 'label' => 'Logo URL', 'type' => 'text'],
                                            ]);
                                        endforeach; ?>
                                    </div>
                                    <button type="button" class="btn btn--sm btn--outline about-repeater-add">+ Add Tool</button>
                                </div>
                                <?php

                            /* Testimonials */
                            elseif ($skey === 'testimonials'):
                                renderField('text', 'title', $config['title'] ?? '', 'Title');
                                renderField('textarea', 'description', $config['description'] ?? '', 'Description', ['extra' => ' rows="2"']);
                                renderField('number', 'display_count', $config['display_count'] ?? '6', 'Number of Testimonials');
                                ?>
                                <div class="about-form__field">
                                    <label class="about-form__label">Display Style</label>
                                    <select name="display_style" class="about-field about-field--select">
                                        <option value="carousel" <?php echo ($config['display_style'] ?? '') === 'carousel' ? 'selected' : ''; ?>>Carousel</option>
                                        <option value="grid" <?php echo ($config['display_style'] ?? '') === 'grid' ? 'selected' : ''; ?>>Grid</option>
                                        <option value="list" <?php echo ($config['display_style'] ?? '') === 'list' ? 'selected' : ''; ?>>List</option>
                                    </select>
                                </div>
                                <?php

                            /* FAQ */
                            elseif ($skey === 'faq'):
                                renderField('text', 'title', $config['title'] ?? '', 'Title');
                                renderField('textarea', 'description', $config['description'] ?? '', 'Description', ['extra' => ' rows="2"']);
                                ?>
                                <div class="about-form__field about-form__field--full">
                                    <label class="about-form__label">FAQ Items</label>
                                    <div class="about-repeater" data-name="items" data-fields='[{"name":"question","label":"Question","type":"text"},{"name":"answer","label":"Answer","type":"textarea"}]'>
                                        <?php foreach (($config['items'] ?? []) as $idx => $item):
                                            renderRepeaterRow('items', $item, $idx, [
                                                ['name' => 'question', 'label' => 'Question', 'type' => 'text'],
                                                ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'attrs' => ['extra' => ' rows="2"']],
                                            ]);
                                        endforeach; ?>
                                    </div>
                                    <button type="button" class="btn btn--sm btn--outline about-repeater-add">+ Add FAQ</button>
                                </div>
                                <?php

                            /* Final CTA */
                            elseif ($skey === 'final_cta'):
                                renderField('text', 'heading', $config['heading'] ?? '', 'Heading');
                                renderField('textarea', 'description', $config['description'] ?? '', 'Description', ['extra' => ' rows="2"']);
                                renderField('text', 'primary_text', $config['primary_text'] ?? '', 'Primary Button Text');
                                renderField('text', 'primary_url', $config['primary_url'] ?? '', 'Primary Button URL');
                                renderField('text', 'whatsapp_text', $config['whatsapp_text'] ?? '', 'WhatsApp Button Text');
                                renderField('toggle', 'show_whatsapp', $config['show_whatsapp'] ?? '1', 'Show WhatsApp Button');
                                renderField('image', 'background_image', $config['background_image'] ?? '', 'Background Image');
                            endif;
                            ?>
                        </div>
                        <div class="about-form__actions">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <button type="button" class="btn btn--primary about-save-btn" data-action="save">Save & Publish</button>
                            <button type="button" class="btn btn--outline about-save-btn" data-action="save_draft">Save Draft</button>
                            <button type="button" class="btn btn--sm btn--outline about-revision-btn" data-action="revisions">Revisions</button>
                            <span class="about-save-status" aria-live="polite"></span>
                        </div>
                    </form>

                    <div class="about-revisions-panel" hidden>
                        <h4>Revision History</h4>
                        <div class="about-revisions__list"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="about-relations">
        <h2 class="about-relations__title">Related Content</h2>

        <!-- Portfolio Items -->
        <div class="svc-relation-panel">
            <h3 class="svc-sub-list__title">Linked Portfolio Items</h3>
            <div class="svc-relation-linked" id="about-portfolio-linked">
                <?php foreach($relatedPortfolio as $p): ?>
                <div class="svc-relation-chip" data-relation-id="<?php echo (int)$p['id']; ?>">
                    <?php echo esc($p['title']??''); ?>
                    <button type="button" class="svc-relation-remove" data-type="portfolio" data-relation-id="<?php echo (int)$p['id']; ?>">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if($allPortfolioItems): ?>
            <div class="svc-relation-picker">
                <select class="svc-form-input svc-form-select" id="about-portfolio-select">
                    <option value="">-- Select Portfolio Item --</option>
                    <?php foreach($allPortfolioItems as $ap):
                        if(in_array($ap['id'], array_column($relatedPortfolio, 'id'))) continue; ?>
                    <option value="<?php echo (int)$ap['id']; ?>"><?php echo esc($ap['title']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn--sm btn--primary svc-relation-link" data-type="portfolio">Link</button>
            </div>
            <?php else: ?>
            <p class="svc-relation-empty">No published portfolio items yet.</p>
            <?php endif; ?>
        </div>

        <!-- Global Blocks -->
        <div class="svc-relation-panel">
            <h3 class="svc-sub-list__title">Linked Global Blocks</h3>
            <div class="svc-relation-linked" id="about-blocks-linked">
                <?php foreach($relatedBlocks as $b): ?>
                <div class="svc-relation-chip" data-relation-id="<?php echo (int)$b['id']; ?>">
                    <?php echo esc($b['name']??''); ?>
                    <button type="button" class="svc-relation-remove" data-type="block" data-relation-id="<?php echo (int)$b['id']; ?>">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if($allGlobalBlocks): ?>
            <div class="svc-relation-picker">
                <select class="svc-form-input svc-form-select" id="about-blocks-select">
                    <option value="">-- Select Block --</option>
                    <?php foreach($allGlobalBlocks as $ab):
                        if(in_array($ab['id'], array_column($relatedBlocks, 'id'))) continue; ?>
                    <option value="<?php echo (int)$ab['id']; ?>"><?php echo esc($ab['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn--sm btn--primary svc-relation-link" data-type="block">Link</button>
            </div>
            <?php else: ?>
            <p class="svc-relation-empty">No published global blocks yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/admin/assets/css/about-editor.css">
<script src="/admin/assets/js/about-editor.js" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
