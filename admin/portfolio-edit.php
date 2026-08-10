<?php
require __DIR__ . '/includes/init.php';
Auth::require();

$manager = new PortfolioManager();
$projectId = (int)($_GET['id'] ?? 0);
$isNew = $projectId === 0;
$pageTitle = $isNew ? 'Add New Project' : 'Edit Project';
$currentSection = 'portfolio';

$defaults = ['title'=>'','slug'=>'','description'=>'','full_description'=>'','image_url'=>'','category'=>'','client_name'=>'','industry'=>'','completion_date'=>'','project_url'=>'','is_featured'=>0,'status'=>'draft',
    'hero_title'=>'','hero_description'=>'','hero_image'=>'','hero_bg_image'=>'','hero_cta_text'=>'','hero_cta_url'=>'',
    'overview_summary'=>'','overview_requirements'=>'','overview_problem'=>'','overview_objectives'=>'',
    'solution_strategy'=>'','solution_branding'=>'','solution_process'=>'','solution_tech'=>'',
    'results_summary'=>'','results_achievements'=>'','results_feedback'=>'',
    'project_duration'=>'','project_deliverables'=>'','project_services_used'=>'',
    'cta_heading'=>'','cta_description'=>'','cta_button_text'=>'','cta_button_url'=>'','cta_show_whatsapp'=>0,'cta_whatsapp_label'=>'','cta_bg_image'=>'',
    'seo_title'=>'','meta_description'=>'','focus_keyword'=>'','canonical_url'=>'','og_image'=>''];
$project = $isNew ? $defaults : $manager->getById($projectId);
if (!$isNew && !$project) { header('Location: /admin/portfolio.php'); exit; }
$projectId = $isNew ? 0 : (int)$project['id'];

$message = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    try {
        $data = [];
        foreach ($defaults as $k => $v) {
            if ($k === 'is_featured') $data[$k] = !empty($_POST[$k]) ? 1 : 0;
            elseif ($k === 'cta_show_whatsapp') $data[$k] = !empty($_POST[$k]) ? 1 : 0;
            elseif ($k === 'status' || $k === 'completion_date') $data[$k] = $_POST[$k] ?? $v;
            elseif (in_array($k, ['overview_summary','overview_requirements','overview_problem','overview_objectives','solution_strategy','solution_branding','solution_process','solution_tech','results_summary','results_achievements','results_feedback','project_deliverables','description','full_description'])) $data[$k] = $_POST[$k] ?? '';
            else $data[$k] = $_POST[$k] ?? $v;
        }
        if ($isNew) {
            $data['slug'] = $_POST['slug'] ?: $data['title'];
            $projectId = $manager->create($data);
            $message = 'Project created.';
        } else {
            $data['slug'] = $_POST['slug'] ?? $project['slug'];
            $manager->update($projectId, $data);
            $message = 'Project updated.';
        }
        $msgType = 'success'; $project = $manager->getById($projectId);
    } catch (Exception $e) { $message = 'Error: ' . $e->getMessage(); $msgType = 'error'; }
}

$gallery    = $isNew ? [] : $manager->getGallery($projectId);
$testimonials = $isNew ? [] : $manager->getTestimonialEntries($projectId);
$relServices = $isNew ? [] : $manager->getRelatedServices($projectId);
$faqs       = $isNew ? [] : $manager->getFaqs($projectId);
$blocks     = $isNew ? [] : $manager->getRelatedBlocks($projectId);
$relBlogs   = $isNew ? [] : $manager->getRelatedBlogs($projectId);
$revisions  = $isNew ? [] : $manager->getRevisions($projectId);

$allTestimonials = $manager->getAllTestimonialEntries();
$allServices     = $manager->getAllServices();
$allBlocks       = $manager->getAllGlobalBlocks();
$allBlogPosts    = $manager->getAllBlogPosts();

/* Tab helpers */
function pfField(string $label, string $name, string $type='text', $value='', string $ph='', array $opts=[]): void {
    $n = esc($name); $l = esc($label); $v = esc((string)$value); $p = esc($ph);
    echo '<div class="svc-form-row"><label class="svc-form-label" for="pf-'.$n.'">'.$l.'</label><div class="svc-form-field">';
    if ($type==='textarea') echo '<textarea id="pf-'.$n.'" name="'.$n.'" class="svc-form-input svc-form-textarea" placeholder="'.$p.'">'.$v.'</textarea>';
    elseif ($type==='toggle') echo '<label class="hp-toggle"><input type="checkbox" id="pf-'.$n.'" name="'.$n.'" value="1"'.($value?' checked':'').'><span class="hp-toggle__slider"></span></label>';
    elseif ($type==='select'&&$opts) { echo '<select id="pf-'.$n.'" name="'.$n.'" class="svc-form-input svc-form-select">'; foreach($opts as $ov=>$ol) echo '<option value="'.esc($ov).'"'.((string)$ov===(string)$value?' selected':'').'>'.esc($ol).'</option>'; echo '</select>'; }
    else echo '<input type="'.$type.'" id="pf-'.$n.'" name="'.$n.'" value="'.$v.'" class="svc-form-input" placeholder="'.$p.'">';
    echo '</div></div>';
}
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<link rel="stylesheet" href="/admin/assets/css/homepage-editor.css?v=1">
<link rel="stylesheet" href="/admin/assets/css/services-admin.css?v=1">

<div class="admin-page">
<div class="admin-page__header"><h1 class="admin-page__title"><?php echo esc($pageTitle); ?></h1><div class="admin-page__actions"><?php if(!$isNew): ?><a href="/portfolio/<?php echo esc($project['slug']); ?>" target="_blank" class="btn btn--outline btn--sm">Preview</a><a href="/admin/portfolio.php" class="btn btn--outline btn--sm">Back</a><?php endif; ?></div></div>
<?php if($message): ?><div class="alert alert--<?php echo $msgType; ?>" role="alert"><?php echo esc($message); ?></div><?php endif; ?>
<form method="post" class="svc-edit-form"><input type="hidden" name="save_project" value="1">
<div class="svc-tabs" role="tablist">
    <button type="button" class="svc-tab svc-tab--active" role="tab" data-tab="general">General</button>
    <button type="button" class="svc-tab" role="tab" data-tab="hero">Hero</button>
    <button type="button" class="svc-tab" role="tab" data-tab="overview">Overview</button>
    <button type="button" class="svc-tab" role="tab" data-tab="challenge">Challenge</button>
    <button type="button" class="svc-tab" role="tab" data-tab="process">Process</button>
    <button type="button" class="svc-tab" role="tab" data-tab="solution">Solution</button>
    <button type="button" class="svc-tab" role="tab" data-tab="results">Results</button>
    <button type="button" class="svc-tab" role="tab" data-tab="gallery">Gallery</button>
    <button type="button" class="svc-tab" role="tab" data-tab="testimonials">Testimonials</button>
    <button type="button" class="svc-tab" role="tab" data-tab="services">Services</button>
    <button type="button" class="svc-tab" role="tab" data-tab="blog">Blog</button>
    <button type="button" class="svc-tab" role="tab" data-tab="faq">FAQ</button>
    <button type="button" class="svc-tab" role="tab" data-tab="cta">CTA</button>
    <button type="button" class="svc-tab" role="tab" data-tab="seo">SEO</button>
    <button type="button" class="svc-tab" role="tab" data-tab="blocks">Blocks</button>
    <button type="button" class="svc-tab" role="tab" data-tab="revisions">Revisions</button>
</div>
<div class="svc-tab-panels">

<!-- General -->
<div class="svc-tab-panel svc-tab-panel--active" id="tab-general" role="tabpanel">
    <div class="svc-tab-panel__grid">
        <?php pfField('Title','title','text',$project['title']); ?>
        <?php pfField('URL Slug','slug','text',$project['slug']); ?>
        <?php pfField('Category','category','text',$project['category']??''); ?>
        <?php pfField('Client Name','client_name','text',$project['client_name']??''); ?>
        <?php pfField('Industry','industry','text',$project['industry']??''); ?>
        <?php pfField('Completion Date','completion_date','date',$project['completion_date']??''); ?>
        <?php pfField('Featured Image','image_url','text',$project['image_url']??''); ?>
        <?php pfField('Project URL','project_url','text',$project['project_url']??''); ?>
        <?php pfField('Featured','is_featured','toggle',$project['is_featured']??0); ?>
        <?php pfField('Status','status','select',$project['status']??'draft','',['draft'=>'Draft','published'=>'Published']); ?>
    </div>
    <?php pfField('Short Description','description','textarea',$project['description']??''); ?>
    <?php pfField('Full Description','full_description','textarea',$project['full_description']??''); ?>
</div>

<!-- Hero -->
<div class="svc-tab-panel" id="tab-hero" role="tabpanel" hidden>
    <div class="svc-tab-panel__grid">
        <?php pfField('Hero Title','hero_title','text',$project['hero_title']??''); ?>
        <?php pfField('Hero Description','hero_description','textarea',$project['hero_description']??''); ?>
        <?php pfField('Hero Image','hero_image','text',$project['hero_image']??''); ?>
        <?php pfField('Background Image','hero_bg_image','text',$project['hero_bg_image']??''); ?>
        <?php pfField('CTA Text','hero_cta_text','text',$project['hero_cta_text']??''); ?>
        <?php pfField('CTA URL','hero_cta_url','text',$project['hero_cta_url']??''); ?>
    </div>
</div>

<!-- Overview -->
<div class="svc-tab-panel" id="tab-overview" role="tabpanel" hidden>
    <?php pfField('Project Summary','overview_summary','textarea',$project['overview_summary']??''); ?>
</div>

<!-- Challenge -->
<div class="svc-tab-panel" id="tab-challenge" role="tabpanel" hidden>
    <?php pfField('Problem Statement','overview_problem','textarea',$project['overview_problem']??''); ?>
    <?php pfField('Client Requirements','overview_requirements','textarea',$project['overview_requirements']??''); ?>
    <?php pfField('Objectives','overview_objectives','textarea',$project['overview_objectives']??''); ?>
</div>

<!-- Process -->
<div class="svc-tab-panel" id="tab-process" role="tabpanel" hidden>
    <?php pfField('Design Strategy','solution_strategy','textarea',$project['solution_strategy']??''); ?>
    <?php pfField('Branding Approach','solution_branding','textarea',$project['solution_branding']??''); ?>
    <?php pfField('Development Process','solution_process','textarea',$project['solution_process']??''); ?>
    <?php pfField('Technologies Used','solution_tech','textarea',$project['solution_tech']??''); ?>
</div>

<!-- Solution -->
<div class="svc-tab-panel" id="tab-solution" role="tabpanel" hidden>
    <p class="svc-form-help">Design strategy, branding, and development process details are in the <strong>Process</strong> tab.</p>
</div>

<!-- Results -->
<div class="svc-tab-panel" id="tab-results" role="tabpanel" hidden>
    <?php pfField('Outcome Summary','results_summary','textarea',$project['results_summary']??''); ?>
    <?php pfField('Key Achievements','results_achievements','textarea',$project['results_achievements']??''); ?>
    <?php pfField('Client Feedback','results_feedback','textarea',$project['results_feedback']??''); ?>
    <?php pfField('Duration','project_duration','text',$project['project_duration']??''); ?>
    <?php pfField('Deliverables','project_deliverables','textarea',$project['project_deliverables']??''); ?>
    <?php pfField('Services Used','project_services_used','text',$project['project_services_used']??''); ?>
</div>

<!-- Gallery -->
<div class="svc-tab-panel" id="tab-gallery" role="tabpanel" hidden>
    <div class="svc-sub-list" data-type="gallery" data-project-id="<?php echo $projectId; ?>">
        <h3 class="svc-sub-list__title">Gallery Images <button type="button" class="btn btn--sm btn--outline svc-sub-add">+ Add Image</button></h3>
        <div class="svc-sub-items" id="pf-gallery-list">
            <?php foreach ($gallery as $g): ?>
            <div class="svc-sub-item" data-id="<?php echo (int)$g['id']; ?>">
                <input type="text" class="svc-sub-field svc-sub-field--wide" data-field="image_url" value="<?php echo esc($g['image_url']??''); ?>" placeholder="Image URL">
                <input type="text" class="svc-sub-field" data-field="caption" value="<?php echo esc($g['caption']??''); ?>" placeholder="Caption">
                <select class="svc-sub-field svc-form-select" data-field="image_type" style="width:auto">
                    <?php foreach(['general'=>'General','before'=>'Before','after'=>'After','screenshot'=>'Screenshot','mockup'=>'Mockup','web_screenshot'=>'Web Screenshot','mobile_screenshot'=>'Mobile Screenshot'] as $tv=>$tl): ?>
                    <option value="<?php echo $tv; ?>"<?php echo ($g['image_type']??'general')===$tv?' selected':''; ?>><?php echo $tl; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="svc-sub-remove" title="Remove">&#10005;</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Testimonials -->
<div class="svc-tab-panel" id="tab-testimonials" role="tabpanel" hidden>
    <div class="svc-relation-panel"><h3 class="svc-sub-list__title">Linked Testimonials</h3>
        <div class="svc-relation-linked" id="pf-testimonial-linked">
            <?php foreach($testimonials as $t): ?><div class="svc-relation-chip" data-relation-id="<?php echo (int)$t['id']; ?>"><?php echo esc($t['title']??''); ?><button type="button" class="svc-relation-remove" data-type="testimonial" data-relation-id="<?php echo (int)$t['id']; ?>">&times;</button></div><?php endforeach; ?>
        </div>
        <?php if($allTestimonials): ?><div class="svc-relation-picker"><select class="svc-form-input svc-form-select" id="pf-testimonial-select"><option value="">-- Select --</option>
            <?php foreach($allTestimonials as $at): if(in_array($at['id'],array_column($testimonials,'id'))) continue; ?><option value="<?php echo (int)$at['id']; ?>"><?php echo esc($at['title']); ?></option><?php endforeach; ?>
        </select><button type="button" class="btn btn--sm btn--primary svc-relation-link" data-type="testimonial">Link</button></div><?php else: ?><p class="svc-relation-empty">No testimonials yet.</p><?php endif; ?>
    </div>
</div>

<!-- Related Services -->
<div class="svc-tab-panel" id="tab-services" role="tabpanel" hidden>
    <div class="svc-relation-panel"><h3 class="svc-sub-list__title">Related Services</h3>
        <div class="svc-relation-linked" id="pf-service-linked">
            <?php foreach($relServices as $s): ?><div class="svc-relation-chip" data-relation-id="<?php echo (int)$s['id']; ?>"><?php echo esc($s['title']??''); ?><button type="button" class="svc-relation-remove" data-type="service" data-relation-id="<?php echo (int)$s['id']; ?>">&times;</button></div><?php endforeach; ?>
        </div>
        <?php if($allServices): ?><div class="svc-relation-picker"><select class="svc-form-input svc-form-select" id="pf-service-select"><option value="">-- Select --</option>
            <?php foreach($allServices as $as): if(in_array($as['id'],array_column($relServices,'id'))) continue; ?><option value="<?php echo (int)$as['id']; ?>"><?php echo esc($as['title']); ?></option><?php endforeach; ?>
        </select><button type="button" class="btn btn--sm btn--primary svc-relation-link" data-type="service">Link</button></div><?php else: ?><p class="svc-relation-empty">No services yet.</p><?php endif; ?>
    </div>
</div>

<!-- Related Blog -->
<div class="svc-tab-panel" id="tab-blog" role="tabpanel" hidden>
    <div class="svc-relation-panel"><h3 class="svc-sub-list__title">Related Blog Posts</h3>
        <div class="svc-relation-linked" id="pf-blog-linked">
            <?php foreach($relBlogs as $b): ?><div class="svc-relation-chip" data-relation-id="<?php echo (int)$b['id']; ?>"><?php echo esc($b['title']??''); ?><button type="button" class="svc-relation-remove" data-type="blog" data-relation-id="<?php echo (int)$b['id']; ?>">&times;</button></div><?php endforeach; ?>
        </div>
        <?php if($allBlogPosts): ?><div class="svc-relation-picker"><select class="svc-form-input svc-form-select" id="pf-blog-select"><option value="">-- Select --</option>
            <?php foreach($allBlogPosts as $ab): if(in_array($ab['id'],array_column($relBlogs,'id'))) continue; ?><option value="<?php echo (int)$ab['id']; ?>"><?php echo esc($ab['title']); ?></option><?php endforeach; ?>
        </select><button type="button" class="btn btn--sm btn--primary svc-relation-link" data-type="blog">Link</button></div><?php else: ?><p class="svc-relation-empty">No blog posts yet.</p><?php endif; ?>
    </div>
</div>

<!-- FAQ -->
<div class="svc-tab-panel" id="tab-faq" role="tabpanel" hidden>
    <div class="svc-sub-list" data-type="faqs" data-project-id="<?php echo $projectId; ?>">
        <h3 class="svc-sub-list__title">FAQ Items <button type="button" class="btn btn--sm btn--outline svc-sub-add">+ Add FAQ</button></h3>
        <div class="svc-sub-items" id="pf-faqs-list">
            <?php foreach($faqs as $f): ?><div class="svc-sub-item" data-id="<?php echo (int)$f['id']; ?>">
                <input type="text" class="svc-sub-field svc-sub-field--wide" data-field="question" value="<?php echo esc($f['question']??''); ?>" placeholder="Question">
                <textarea class="svc-sub-field svc-sub-field--wide svc-sub-textarea" data-field="answer" placeholder="Answer"><?php echo esc($f['answer']??''); ?></textarea>
                <button type="button" class="svc-sub-remove" title="Remove">&#10005;</button>
            </div><?php endforeach; ?>
        </div>
    </div>
</div>

<!-- CTA -->
<div class="svc-tab-panel" id="tab-cta" role="tabpanel" hidden>
    <div class="svc-tab-panel__grid">
        <?php pfField('CTA Heading','cta_heading','text',$project['cta_heading']??''); ?>
        <?php pfField('CTA Description','cta_description','textarea',$project['cta_description']??''); ?>
        <?php pfField('Button Text','cta_button_text','text',$project['cta_button_text']??''); ?>
        <?php pfField('Button URL','cta_button_url','text',$project['cta_button_url']??''); ?>
        <?php pfField('Show WhatsApp','cta_show_whatsapp','toggle',$project['cta_show_whatsapp']??0); ?>
        <?php pfField('WhatsApp Label','cta_whatsapp_label','text',$project['cta_whatsapp_label']??''); ?>
        <?php pfField('BG Image','cta_bg_image','text',$project['cta_bg_image']??''); ?>
    </div>
</div>

<!-- SEO -->
<div class="svc-tab-panel" id="tab-seo" role="tabpanel" hidden>
    <div class="svc-tab-panel__grid">
        <?php pfField('SEO Title','seo_title','text',$project['seo_title']??''); ?>
        <?php pfField('Meta Description','meta_description','textarea',$project['meta_description']??''); ?>
        <?php pfField('Focus Keyword','focus_keyword','text',$project['focus_keyword']??''); ?>
        <?php pfField('Canonical URL','canonical_url','text',$project['canonical_url']??''); ?>
        <?php pfField('OG Image','og_image','text',$project['og_image']??''); ?>
    </div>
</div>

<!-- Blocks -->
<div class="svc-tab-panel" id="tab-blocks" role="tabpanel" hidden>
    <div class="svc-relation-panel"><h3 class="svc-sub-list__title">Linked Blocks</h3>
        <div class="svc-relation-linked" id="pf-blocks-linked">
            <?php foreach($blocks as $b): ?><div class="svc-relation-chip" data-relation-id="<?php echo (int)$b['id']; ?>"><?php echo esc($b['name']??''); ?><button type="button" class="svc-relation-remove" data-type="block" data-relation-id="<?php echo (int)$b['id']; ?>">&times;</button></div><?php endforeach; ?>
        </div>
        <?php if($allBlocks): ?><div class="svc-relation-picker"><select class="svc-form-input svc-form-select" id="pf-blocks-select"><option value="">-- Select --</option>
            <?php foreach($allBlocks as $ab): if(in_array($ab['id'],array_column($blocks,'id'))) continue; ?><option value="<?php echo (int)$ab['id']; ?>"><?php echo esc($ab['name']); ?></option><?php endforeach; ?>
        </select><button type="button" class="btn btn--sm btn--primary svc-relation-link" data-type="block">Link</button></div><?php else: ?><p class="svc-relation-empty">No global blocks yet.</p><?php endif; ?>
    </div>
</div>

<!-- Revisions -->
<div class="svc-tab-panel" id="tab-revisions" role="tabpanel" hidden>
    <div class="svc-revision-panel">
        <h3 class="svc-sub-list__title">Revision History</h3>
        <?php if ($isNew): ?>
        <p class="svc-relation-empty">Save the project first to start tracking revisions.</p>
        <?php elseif (empty($revisions)): ?>
        <p class="svc-relation-empty">No revisions yet. Revisions are created automatically when you save.</p>
        <button type="button" class="btn btn--sm btn--outline" id="pf-save-revision-btn" data-project-id="<?php echo $projectId; ?>">Save Revision Now</button>
        <?php else: ?>
        <button type="button" class="btn btn--sm btn--outline" id="pf-save-revision-btn" data-project-id="<?php echo $projectId; ?>">Save Revision Now</button>
        <div class="svc-revision-list" id="pf-revision-list">
            <?php foreach ($revisions as $r): ?>
            <div class="svc-revision-item" data-revision-id="<?php echo (int)$r['id']; ?>">
                <div class="svc-revision-item__info">
                    <span class="svc-revision-item__date"><?php echo esc(date('M j, Y g:i a', strtotime($r['created_at']))); ?></span>
                    <?php if ($r['revision_note']): ?><span class="svc-revision-item__note">— <?php echo esc($r['revision_note']); ?></span><?php endif; ?>
                </div>
                <button type="button" class="btn btn--sm btn--outline svc-revision-restore" data-revision-id="<?php echo (int)$r['id']; ?>">Restore</button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>
<div class="svc-edit__footer">
    <button type="submit" class="btn btn--primary btn--lg"><?php echo $isNew?'Create Project':'Save Changes'; ?></button>
    <a href="/admin/portfolio.php" class="btn btn--outline btn--lg">Cancel</a>
</div>
</form>
</div>

<script src="/admin/assets/js/portfolio-edit.js?v=1"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>