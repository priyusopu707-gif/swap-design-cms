<?php
/**
 * Swap Design - Service Editor (Add / Edit)
 *
 * Tabbed editor for a single service:
 *  - General (title, slug, description, image, icon, category, order, status)
 *  - Hero (hero section fields)
 *  - Overview (intro, benefits, why)
 *  - Features (unlimited cards: icon, title, description)
 *  - Benefits (unlimited cards)
 *  - Process (unlimited steps)
 *  - Portfolio (link existing portfolio items)
 *  - Testimonials (link existing testimonials)
 *  - FAQ (unlimited Q&A items)
 *  - CTA (call to action fields)
 *  - Contact (whatsapp, phone, email, form toggles)
 *  - SEO (title, meta desc, keyword, canonical, OG image)
 *  - Blocks (link global blocks)
 *
 * @package SwapDesign
 */

require __DIR__ . '/includes/init.php';
Auth::require();

$manager = new ServiceManager();

$serviceId  = (int)($_GET['id'] ?? 0);
$isNew      = $serviceId === 0;
$pageTitle  = $isNew ? 'Add New Service' : 'Edit Service';
$currentSection = 'services';

$service = $isNew ? [
    'title' => '', 'slug' => '', 'short_description' => '', 'full_description' => '',
    'featured_image' => '', 'icon' => '', 'category' => '', 'sort_order' => 0, 'status' => 'draft',
    'hero_title' => '', 'hero_description' => '', 'hero_image' => '', 'hero_bg_image' => '',
    'hero_cta_primary_text' => '', 'hero_cta_primary_url' => '', 'hero_cta_secondary_text' => '', 'hero_cta_secondary_url' => '',
    'overview_intro' => '', 'overview_benefits' => '', 'overview_why' => '',
    'cta_heading' => '', 'cta_description' => '', 'cta_button_text' => '', 'cta_button_url' => '', 'cta_show_whatsapp' => 0, 'cta_whatsapp_label' => '', 'cta_bg_image' => '',
    'contact_show_whatsapp' => 1, 'contact_show_phone' => 1, 'contact_show_email' => 1, 'contact_show_form' => 1, 'contact_button_text' => '', 'contact_button_url' => '',
    'seo_title' => '', 'meta_description' => '', 'focus_keyword' => '', 'canonical_url' => '', 'og_image' => '',
] : $manager->getById($serviceId);

if (!$isNew && !$service) {
    header('Location: /admin/services.php');
    exit;
}

$serviceId = $isNew ? 0 : (int)$service['id'];

/* Handle main form save */
$message = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
    try {
        $data = [
            'title'                 => $_POST['title'] ?? '',
            'short_description'     => $_POST['short_description'] ?? '',
            'full_description'      => $_POST['full_description'] ?? '',
            'featured_image'        => $_POST['featured_image'] ?? '',
            'icon'                  => $_POST['icon'] ?? '',
            'category'              => $_POST['category'] ?? '',
            'status'                => $_POST['status'] ?? 'draft',
            'hero_title'            => $_POST['hero_title'] ?? '',
            'hero_description'      => $_POST['hero_description'] ?? '',
            'hero_image'            => $_POST['hero_image'] ?? '',
            'hero_bg_image'         => $_POST['hero_bg_image'] ?? '',
            'hero_cta_primary_text' => $_POST['hero_cta_primary_text'] ?? '',
            'hero_cta_primary_url'  => $_POST['hero_cta_primary_url'] ?? '',
            'hero_cta_secondary_text' => $_POST['hero_cta_secondary_text'] ?? '',
            'hero_cta_secondary_url' => $_POST['hero_cta_secondary_url'] ?? '',
            'overview_intro'        => $_POST['overview_intro'] ?? '',
            'overview_benefits'     => $_POST['overview_benefits'] ?? '',
            'overview_why'          => $_POST['overview_why'] ?? '',
            'cta_heading'           => $_POST['cta_heading'] ?? '',
            'cta_description'       => $_POST['cta_description'] ?? '',
            'cta_button_text'       => $_POST['cta_button_text'] ?? '',
            'cta_button_url'        => $_POST['cta_button_url'] ?? '',
            'cta_show_whatsapp'     => !empty($_POST['cta_show_whatsapp']) ? 1 : 0,
            'cta_whatsapp_label'    => $_POST['cta_whatsapp_label'] ?? '',
            'cta_bg_image'          => $_POST['cta_bg_image'] ?? '',
            'contact_show_whatsapp' => !empty($_POST['contact_show_whatsapp']) ? 1 : 0,
            'contact_show_phone'    => !empty($_POST['contact_show_phone']) ? 1 : 0,
            'contact_show_email'    => !empty($_POST['contact_show_email']) ? 1 : 0,
            'contact_show_form'     => !empty($_POST['contact_show_form']) ? 1 : 0,
            'contact_button_text'   => $_POST['contact_button_text'] ?? '',
            'contact_button_url'    => $_POST['contact_button_url'] ?? '',
            'seo_title'             => $_POST['seo_title'] ?? '',
            'meta_description'      => $_POST['meta_description'] ?? '',
            'focus_keyword'         => $_POST['focus_keyword'] ?? '',
            'canonical_url'         => $_POST['canonical_url'] ?? '',
            'og_image'              => $_POST['og_image'] ?? '',
        ];

        if ($isNew) {
            $data['slug'] = $_POST['slug'] ?: $data['title'];
            $serviceId = $manager->create($data);
            $message = 'Service created.';
        } else {
            $data['slug'] = $_POST['slug'] ?? $service['slug'];
            $manager->update($serviceId, $data);
            $message = 'Service updated.';
        }
        $msgType = 'success';

        $service = $manager->getById($serviceId);
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $msgType = 'error';
    }
}

/* Load sub-data */
$features     = $isNew ? [] : $manager->getFeatures($serviceId);
$benefits      = $isNew ? [] : $manager->getBenefits($serviceId);
$processSteps  = $isNew ? [] : $manager->getProcessSteps($serviceId);
$faqs          = $isNew ? [] : $manager->getFaqs($serviceId);
$linkedPortfolio   = $isNew ? [] : $manager->getPortfolioItems($serviceId);
$linkedTestimonials= $isNew ? [] : $manager->getTestimonialEntries($serviceId);
$linkedBlocks      = $isNew ? [] : $manager->getRelatedBlocks($serviceId);

$allPortfolio    = $manager->getAllPortfolioItems();
$allTestimonials = $manager->getAllTestimonialEntries();
$allBlocks       = $manager->getAllGlobalBlocks();

function renderTabField(string $label, string $name, string $type = 'text', $value = '', $placeholder = '', $options = []): void {
    $escName  = esc($name);
    $escLabel = esc($label);
    $escVal   = esc((string)$value);
    $escPlace = esc($placeholder);
    ?>
    <div class="svc-form-row">
        <label class="svc-form-label" for="svc-f-<?php echo $escName; ?>"><?php echo $escLabel; ?></label>
        <div class="svc-form-field">
            <?php if ($type === 'textarea'): ?>
            <textarea id="svc-f-<?php echo $escName; ?>" name="<?php echo $escName; ?>" class="svc-form-input svc-form-textarea" placeholder="<?php echo $escPlace; ?>"><?php echo $escVal; ?></textarea>
            <?php elseif ($type === 'toggle'): ?>
            <label class="hp-toggle"><input type="checkbox" id="svc-f-<?php echo $escName; ?>" name="<?php echo $escName; ?>" value="1"<?php echo $value ? ' checked' : ''; ?>><span class="hp-toggle__slider"></span></label>
            <?php elseif ($type === 'select' && $options): ?>
            <select id="svc-f-<?php echo $escName; ?>" name="<?php echo $escName; ?>" class="svc-form-input svc-form-select">
                <?php foreach ($options as $ov => $ol): ?>
                <option value="<?php echo esc($ov); ?>"<?php echo (string)$ov === (string)$value ? ' selected' : ''; ?>><?php echo esc($ol); ?></option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
            <input type="<?php echo $type; ?>" id="svc-f-<?php echo $escName; ?>" name="<?php echo $escName; ?>" value="<?php echo $escVal; ?>" class="svc-form-input" placeholder="<?php echo $escPlace; ?>">
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<link rel="stylesheet" href="/admin/assets/css/homepage-editor.css?v=1">
<link rel="stylesheet" href="/admin/assets/css/services-admin.css?v=1">

<div class="admin-page">

    <div class="admin-page__header">
        <h1 class="admin-page__title"><?php echo esc($pageTitle); ?></h1>
        <div class="admin-page__actions">
            <?php if (!$isNew): ?>
            <a href="/services/<?php echo esc($service['slug']); ?>" target="_blank" class="btn btn--outline btn--sm">Preview</a>
            <a href="/admin/services.php" class="btn btn--outline btn--sm">Back to List</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert--<?php echo $msgType; ?>" role="alert"><?php echo esc($message); ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/services-edit.php<?php echo $isNew ? '' : '?id=' . $serviceId; ?>" class="svc-edit-form">
        <input type="hidden" name="save_service" value="1">

        <!-- Tab Navigation -->
        <div class="svc-tabs" role="tablist" aria-label="Service sections">
            <button type="button" class="svc-tab svc-tab--active" role="tab" aria-selected="true" data-tab="general">General</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="hero">Hero</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="overview">Overview</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="features">Features</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="benefits">Benefits</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="process">Process</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="portfolio">Portfolio</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="testimonials">Testimonials</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="faq">FAQ</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="cta">CTA</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="contact">Contact</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="seo">SEO</button>
            <button type="button" class="svc-tab" role="tab" aria-selected="false" data-tab="blocks">Blocks</button>
        </div>

        <div class="svc-tab-panels">

            <!-- General Tab -->
            <div class="svc-tab-panel svc-tab-panel--active" id="tab-general" role="tabpanel">
                <div class="svc-tab-panel__grid">
                    <?php renderTabField('Title', 'title', 'text', $service['title']); ?>
                    <?php renderTabField('URL Slug', 'slug', 'text', $service['slug'], 'service-url-slug'); ?>
                    <?php renderTabField('Category', 'category', 'text', $service['category'] ?? '', 'e.g. Design, Development'); ?>
                    <?php renderTabField('Icon', 'icon', 'text', $service['icon'] ?? '', 'Icon name'); ?>
                    <?php renderTabField('Featured Image', 'featured_image', 'text', $service['featured_image'] ?? '', 'Image URL or media ID'); ?>
                    <?php renderTabField('Status', 'status', 'select', $service['status'] ?? 'draft', '', ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']); ?>
                </div>
                <?php renderTabField('Short Description', 'short_description', 'textarea', $service['short_description'] ?? '', 'Brief summary for cards and SEO'); ?>
                <?php renderTabField('Full Description', 'full_description', 'textarea', $service['full_description'] ?? '', 'Detailed service description'); ?>
            </div>

            <!-- Hero Tab -->
            <div class="svc-tab-panel" id="tab-hero" role="tabpanel" hidden>
                <div class="svc-tab-panel__grid">
                    <?php renderTabField('Hero Title', 'hero_title', 'text', $service['hero_title'] ?? '', 'Overrides service title'); ?>
                    <?php renderTabField('Hero Description', 'hero_description', 'text', $service['hero_description'] ?? ''); ?>
                    <?php renderTabField('Hero Image', 'hero_image', 'text', $service['hero_image'] ?? ''); ?>
                    <?php renderTabField('Background Image', 'hero_bg_image', 'text', $service['hero_bg_image'] ?? ''); ?>
                    <?php renderTabField('Primary CTA Text', 'hero_cta_primary_text', 'text', $service['hero_cta_primary_text'] ?? '', 'e.g. Get Started'); ?>
                    <?php renderTabField('Primary CTA URL', 'hero_cta_primary_url', 'text', $service['hero_cta_primary_url'] ?? '', '/contact or full URL'); ?>
                    <?php renderTabField('Secondary CTA Text', 'hero_cta_secondary_text', 'text', $service['hero_cta_secondary_text'] ?? ''); ?>
                    <?php renderTabField('Secondary CTA URL', 'hero_cta_secondary_url', 'text', $service['hero_cta_secondary_url'] ?? ''); ?>
                </div>
            </div>

            <!-- Overview Tab -->
            <div class="svc-tab-panel" id="tab-overview" role="tabpanel" hidden>
                <?php renderTabField('Introduction', 'overview_intro', 'textarea', $service['overview_intro'] ?? '', 'Introduce the service'); ?>
                <?php renderTabField('Business Benefits', 'overview_benefits', 'textarea', $service['overview_benefits'] ?? '', 'What business benefits does this service provide?'); ?>
                <?php renderTabField('Why This Matters', 'overview_why', 'textarea', $service['overview_why'] ?? '', 'Why should someone choose this service?'); ?>
            </div>

            <!-- Features Tab -->
            <div class="svc-tab-panel" id="tab-features" role="tabpanel" hidden>
                <div class="svc-sub-list" data-type="features" data-service-id="<?php echo $serviceId; ?>">
                    <h3 class="svc-sub-list__title">Feature Cards <button type="button" class="btn btn--sm btn--outline svc-sub-add">+ Add Feature</button></h3>
                    <div class="svc-sub-items" id="svc-features-list">
                        <?php foreach ($features as $f):
                            $fId = (int)$f['id'];
                        ?>
                        <div class="svc-sub-item" data-id="<?php echo $fId; ?>">
                            <input type="text" class="svc-sub-field" data-field="icon" value="<?php echo esc($f['icon'] ?? ''); ?>" placeholder="Icon name">
                            <input type="text" class="svc-sub-field" data-field="title" value="<?php echo esc($f['title'] ?? ''); ?>" placeholder="Title">
                            <input type="text" class="svc-sub-field svc-sub-field--wide" data-field="description" value="<?php echo esc($f['description'] ?? ''); ?>" placeholder="Description">
                            <button type="button" class="svc-sub-remove" title="Remove">&#10005;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Benefits Tab -->
            <div class="svc-tab-panel" id="tab-benefits" role="tabpanel" hidden>
                <div class="svc-sub-list" data-type="benefits" data-service-id="<?php echo $serviceId; ?>">
                    <h3 class="svc-sub-list__title">Benefit Cards <button type="button" class="btn btn--sm btn--outline svc-sub-add">+ Add Benefit</button></h3>
                    <div class="svc-sub-items" id="svc-benefits-list">
                        <?php foreach ($benefits as $b):
                            $bId = (int)$b['id'];
                        ?>
                        <div class="svc-sub-item" data-id="<?php echo $bId; ?>">
                            <input type="text" class="svc-sub-field" data-field="icon" value="<?php echo esc($b['icon'] ?? ''); ?>" placeholder="Icon name">
                            <input type="text" class="svc-sub-field" data-field="title" value="<?php echo esc($b['title'] ?? ''); ?>" placeholder="Title">
                            <input type="text" class="svc-sub-field svc-sub-field--wide" data-field="description" value="<?php echo esc($b['description'] ?? ''); ?>" placeholder="Description">
                            <button type="button" class="svc-sub-remove" title="Remove">&#10005;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Process Tab -->
            <div class="svc-tab-panel" id="tab-process" role="tabpanel" hidden>
                <div class="svc-sub-list" data-type="process_steps" data-service-id="<?php echo $serviceId; ?>">
                    <h3 class="svc-sub-list__title">Process Steps <button type="button" class="btn btn--sm btn--outline svc-sub-add">+ Add Step</button></h3>
                    <div class="svc-sub-items" id="svc-process-list">
                        <?php foreach ($processSteps as $p):
                            $pId = (int)$p['id'];
                        ?>
                        <div class="svc-sub-item" data-id="<?php echo $pId; ?>">
                            <input type="text" class="svc-sub-field" data-field="icon" value="<?php echo esc($p['icon'] ?? ''); ?>" placeholder="Icon name">
                            <input type="text" class="svc-sub-field" data-field="title" value="<?php echo esc($p['title'] ?? ''); ?>" placeholder="Step title">
                            <input type="text" class="svc-sub-field svc-sub-field--wide" data-field="description" value="<?php echo esc($p['description'] ?? ''); ?>" placeholder="Step description">
                            <button type="button" class="svc-sub-remove" title="Remove">&#10005;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Portfolio Tab -->
            <div class="svc-tab-panel" id="tab-portfolio" role="tabpanel" hidden>
                <div class="svc-relation-panel">
                    <h3 class="svc-sub-list__title">Linked Portfolio Items</h3>

                    <!-- Linked items -->
                    <div class="svc-relation-linked" id="svc-pf-linked">
                        <?php foreach ($linkedPortfolio as $lp): ?>
                        <div class="svc-relation-chip" data-relation-id="<?php echo (int)$lp['id']; ?>">
                            <?php echo esc($lp['title'] ?? 'Portfolio #' . $lp['id']); ?>
                            <button type="button" class="svc-relation-remove" data-type="portfolio" data-relation-id="<?php echo (int)$lp['id']; ?>">&times;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Picker -->
                    <?php if ($allPortfolio): ?>
                    <div class="svc-relation-picker">
                        <select class="svc-form-input svc-form-select" id="svc-pf-select">
                            <option value="">-- Select a portfolio item to link --</option>
                            <?php foreach ($allPortfolio as $ap):
                                if (in_array($ap['id'], array_column($linkedPortfolio, 'id'))) continue;
                            ?>
                            <option value="<?php echo (int)$ap['id']; ?>"><?php echo esc($ap['title']); ?> (<?php echo esc($ap['category']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn--sm btn--primary svc-relation-link" data-type="portfolio">Link</button>
                    </div>
                    <?php else: ?>
                    <p class="svc-relation-empty">No portfolio items exist yet. Create some in Portfolio.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Testimonials Tab -->
            <div class="svc-tab-panel" id="tab-testimonials" role="tabpanel" hidden>
                <div class="svc-relation-panel">
                    <h3 class="svc-sub-list__title">Linked Testimonials</h3>
                    <div class="svc-relation-linked" id="svc-testimonial-linked">
                        <?php foreach ($linkedTestimonials as $lt): ?>
                        <div class="svc-relation-chip" data-relation-id="<?php echo (int)$lt['id']; ?>">
                            <?php echo esc($lt['title'] ?? 'Testimonial #' . $lt['id']); ?>
                            <button type="button" class="svc-relation-remove" data-type="testimonial" data-relation-id="<?php echo (int)$lt['id']; ?>">&times;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($allTestimonials): ?>
                    <div class="svc-relation-picker">
                        <select class="svc-form-input svc-form-select" id="svc-testimonial-select">
                            <option value="">-- Select a testimonial to link --</option>
                            <?php foreach ($allTestimonials as $at):
                                if (in_array($at['id'], array_column($linkedTestimonials, 'id'))) continue;
                            ?>
                            <option value="<?php echo (int)$at['id']; ?>"><?php echo esc($at['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn--sm btn--primary svc-relation-link" data-type="testimonial">Link</button>
                    </div>
                    <?php else: ?>
                    <p class="svc-relation-empty">No testimonials exist yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FAQ Tab -->
            <div class="svc-tab-panel" id="tab-faq" role="tabpanel" hidden>
                <div class="svc-sub-list" data-type="faqs" data-service-id="<?php echo $serviceId; ?>">
                    <h3 class="svc-sub-list__title">FAQ Items <button type="button" class="btn btn--sm btn--outline svc-sub-add">+ Add FAQ</button></h3>
                    <div class="svc-sub-items" id="svc-faqs-list">
                        <?php foreach ($faqs as $faq):
                            $faqId = (int)$faq['id'];
                        ?>
                        <div class="svc-sub-item" data-id="<?php echo $faqId; ?>">
                            <input type="text" class="svc-sub-field svc-sub-field--wide" data-field="question" value="<?php echo esc($faq['question'] ?? ''); ?>" placeholder="Question">
                            <textarea class="svc-sub-field svc-sub-field--wide svc-sub-textarea" data-field="answer" placeholder="Answer"><?php echo esc($faq['answer'] ?? ''); ?></textarea>
                            <button type="button" class="svc-sub-remove" title="Remove">&#10005;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- CTA Tab -->
            <div class="svc-tab-panel" id="tab-cta" role="tabpanel" hidden>
                <div class="svc-tab-panel__grid">
                    <?php renderTabField('CTA Heading', 'cta_heading', 'text', $service['cta_heading'] ?? '', 'e.g. Ready to Get Started?'); ?>
                    <?php renderTabField('CTA Description', 'cta_description', 'text', $service['cta_description'] ?? ''); ?>
                    <?php renderTabField('Button Text', 'cta_button_text', 'text', $service['cta_button_text'] ?? ''); ?>
                    <?php renderTabField('Button URL', 'cta_button_url', 'text', $service['cta_button_url'] ?? ''); ?>
                    <?php renderTabField('Show WhatsApp Button', 'cta_show_whatsapp', 'toggle', $service['cta_show_whatsapp'] ?? 0); ?>
                    <?php renderTabField('WhatsApp Label', 'cta_whatsapp_label', 'text', $service['cta_whatsapp_label'] ?? '', 'e.g. Chat on WhatsApp'); ?>
                    <?php renderTabField('Background Image', 'cta_bg_image', 'text', $service['cta_bg_image'] ?? ''); ?>
                </div>
            </div>

            <!-- Contact Tab -->
            <div class="svc-tab-panel" id="tab-contact" role="tabpanel" hidden>
                <div class="svc-tab-panel__grid">
                    <?php renderTabField('Show WhatsApp', 'contact_show_whatsapp', 'toggle', $service['contact_show_whatsapp'] ?? 1); ?>
                    <?php renderTabField('Show Phone', 'contact_show_phone', 'toggle', $service['contact_show_phone'] ?? 1); ?>
                    <?php renderTabField('Show Email', 'contact_show_email', 'toggle', $service['contact_show_email'] ?? 1); ?>
                    <?php renderTabField('Show Contact Form', 'contact_show_form', 'toggle', $service['contact_show_form'] ?? 1); ?>
                    <?php renderTabField('Button Text', 'contact_button_text', 'text', $service['contact_button_text'] ?? '', 'e.g. Get Started'); ?>
                    <?php renderTabField('Button URL', 'contact_button_url', 'text', $service['contact_button_url'] ?? ''); ?>
                </div>
            </div>

            <!-- SEO Tab -->
            <div class="svc-tab-panel" id="tab-seo" role="tabpanel" hidden>
                <div class="svc-tab-panel__grid">
                    <?php renderTabField('SEO Title', 'seo_title', 'text', $service['seo_title'] ?? '', 'Overrides the page title for SEO'); ?>
                    <?php renderTabField('Meta Description', 'meta_description', 'textarea', $service['meta_description'] ?? '', 'Max 160 characters'); ?>
                    <?php renderTabField('Focus Keyword', 'focus_keyword', 'text', $service['focus_keyword'] ?? ''); ?>
                    <?php renderTabField('Canonical URL', 'canonical_url', 'text', $service['canonical_url'] ?? ''); ?>
                    <?php renderTabField('OG Image', 'og_image', 'text', $service['og_image'] ?? 'Social sharing image URL'); ?>
                </div>
            </div>

            <!-- Blocks Tab -->
            <div class="svc-tab-panel" id="tab-blocks" role="tabpanel" hidden>
                <div class="svc-relation-panel">
                    <h3 class="svc-sub-list__title">Linked Global Blocks</h3>
                    <div class="svc-relation-linked" id="svc-blocks-linked">
                        <?php foreach ($linkedBlocks as $lb): ?>
                        <div class="svc-relation-chip" data-relation-id="<?php echo (int)$lb['id']; ?>">
                            <?php echo esc($lb['name'] ?? 'Block #' . $lb['id']); ?> (<?php echo esc($lb['block_type'] ?? ''); ?>)
                            <button type="button" class="svc-relation-remove" data-type="block" data-relation-id="<?php echo (int)$lb['id']; ?>">&times;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($allBlocks): ?>
                    <div class="svc-relation-picker">
                        <select class="svc-form-input svc-form-select" id="svc-blocks-select">
                            <option value="">-- Select a block to link --</option>
                            <?php foreach ($allBlocks as $ab):
                                if (in_array($ab['id'], array_column($linkedBlocks, 'id'))) continue;
                            ?>
                            <option value="<?php echo (int)$ab['id']; ?>"><?php echo esc($ab['name']); ?> (<?php echo esc($ab['block_type'] ?? ''); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn--sm btn--primary svc-relation-link" data-type="block">Link</button>
                    </div>
                    <?php else: ?>
                    <p class="svc-relation-empty">No global blocks exist yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="svc-edit__footer">
            <button type="submit" class="btn btn--primary btn--lg"><?php echo $isNew ? 'Create Service' : 'Save Changes'; ?></button>
            <a href="/admin/services.php" class="btn btn--outline btn--lg">Cancel</a>
        </div>
    </form>

</div>

<script src="/admin/assets/js/services-edit.js?v=1"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>