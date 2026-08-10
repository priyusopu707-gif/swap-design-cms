<?php
/**
 * Swap Design - Contact Page Editor
 *
 * Admin interface for editing the 6 contact page sections with
 * drag-drop reorder, enable/disable toggles, inline form editing,
 * and auto-save.
 *
 * @package SwapDesign
 */

require __DIR__ . '/includes/init.php';
Auth::require();

$manager = new ContactManager();
$manager->seedDefaults();
$sections       = $manager->getAll();
$currentSection = 'contacts';
$pageTitle      = 'Contact Page Editor';
$sectionDefs    = ContactManager::SECTIONS;

$publishedCount = 0;
$enabledCount   = 0;
foreach ($sections as $s) {
    if ($s['status'] === 'published') $publishedCount++;
    if ($s['is_enabled']) $enabledCount++;
}

function renderContactField(string $type, string $name, $value = '', string $label = '', array $attrs = []): void
{
    $id    = 'cf-' . preg_replace('/[\[\]]+/', '-', rtrim($name, ']'));
    $labelHtml = $label ? "<label for=\"{$id}\" class=\"cf-field__label\">" . esc($label) . "</label>" : '';

    switch ($type) {
        case 'textarea':
            $rows = $attrs['rows'] ?? 4;
            echo "<div class=\"cf-field cf-field--textarea\">{$labelHtml}<textarea name=\"" . esc($name) . "\" id=\"{$id}\" class=\"cf-field__textarea\" rows=\"{$rows}\">" . esc((string)$value) . "</textarea></div>";
            break;
        case 'image':
            echo "<div class=\"cf-field cf-field--image\">{$labelHtml}<input type=\"text\" name=\"" . esc($name) . "\" id=\"{$id}\" class=\"cf-field__input\" value=\"" . esc((string)$value) . "\" placeholder=\"/uploads/image.jpg\"></div>";
            break;
        case 'checkbox':
            $checked = $value ? 'checked' : '';
            echo "<div class=\"cf-field cf-field--checkbox\"><input type=\"hidden\" name=\"" . esc($name) . "\" value=\"0\"><input type=\"checkbox\" name=\"" . esc($name) . "\" id=\"{$id}\" class=\"cf-field__checkbox\" value=\"1\" {$checked}><label for=\"{$id}\" class=\"cf-field__checkbox-label\">" . esc($label) . "</label></div>";
            break;
        case 'toggle':
            $checked = $value ? 'checked' : '';
            echo "<div class=\"cf-field cf-field--toggle\"><label class=\"cf-toggle\"><input type=\"hidden\" name=\"" . esc($name) . "\" value=\"0\"><input type=\"checkbox\" name=\"" . esc($name) . "\" id=\"{$id}\" class=\"cf-toggle__input\" value=\"1\" {$checked}><span class=\"cf-toggle__slider\"></span></label><label for=\"{$id}\" class=\"cf-field__label\">" . esc($label) . "</label></div>";
            break;
        case 'select':
            $options = $attrs['options'] ?? [];
            echo "<div class=\"cf-field cf-field--select\">{$labelHtml}<select name=\"" . esc($name) . "\" id=\"{$id}\" class=\"cf-field__select\">";
            foreach ($options as $optVal => $optLabel) {
                $sel = ((string)$optVal === (string)$value) ? ' selected' : '';
                echo "<option value=\"" . esc($optVal) . "\"{$sel}>" . esc($optLabel) . "</option>";
            }
            echo "</select></div>";
            break;
        default:
            $ph = $attrs['placeholder'] ?? '';
            echo "<div class=\"cf-field\">{$labelHtml}<input type=\"text\" name=\"" . esc($name) . "\" id=\"{$id}\" class=\"cf-field__input\" value=\"" . esc((string)$value) . "\" placeholder=\"" . esc($ph) . "\"></div>";
    }
}

function renderRepeater(string $label, string $name, array $items, array $fields): void
{
    ?>
    <fieldset class="cf-repeater" data-repeater="<?php echo esc($name); ?>">
        <legend class="cf-repeater__label"><?php echo esc($label); ?></legend>
        <div class="cf-repeater__list" id="repeater-<?php echo esc($name); ?>">
            <?php foreach ($items as $idx => $item): ?>
            <div class="cf-repeater__item cf-repeater__item--<?php echo esc($name); ?>" draggable="true">
                <div class="cf-repeater__header">
                    <span class="cf-repeater__handle" aria-label="Drag to reorder">&#9776;</span>
                    <span class="cf-repeater__title"><?php echo esc($item[$fields[0]['name']] ?? ''); ?></span>
                    <button type="button" class="cf-repeater__remove" aria-label="Remove item">&times;</button>
                </div>
                <div class="cf-repeater__body">
                    <?php foreach ($fields as $f): ?>
                        <?php renderContactField($f['type'], "{$name}[{$idx}][{$f['name']}]", $item[$f['name']] ?? '', $f['label'] ?? '', $f['attrs'] ?? []); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="cf-repeater__add" data-repeater="<?php echo esc($name); ?>" data-fields="<?php echo esc(json_encode($fields, JSON_UNESCAPED_UNICODE)); ?>">+ Add Item</button>
    </fieldset>
    <?php
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/includes/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/contacts-editor.css">
</head>
<body class="admin-body">
    <a href="#admin-content" class="admin-skip-link">Skip to main content</a>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <?php require __DIR__ . '/includes/topbar.php'; ?>
    <main class="admin-main">
        <div class="admin-content" id="admin-content">
            <div class="admin-page-header">
                <h1><?php echo esc($pageTitle); ?></h1>
                <div class="admin-page-header__actions">
                    <a href="/contact" target="_blank" class="btn btn--secondary" rel="noopener">View Page</a>
                    <button type="button" class="btn btn--primary" id="cf-publish-all">Publish All</button>
                </div>
            </div>

            <div class="cf-status-bar">
                <span class="cf-status-bar__item" id="cf-published-count"><?php echo (int)$publishedCount; ?> published</span>
                <span class="cf-status-bar__item" id="cf-enabled-count"><?php echo (int)$enabledCount; ?> enabled</span>
            </div>

            <div class="cf-editor" id="cf-editor">
                <?php foreach ($sections as $section): ?>
                <?php
                    $sId    = (int)$section['id'];
                    $key    = $section['section_key'];
                    $label  = esc($section['section_label']);
                    $config = $section['config'] ?? [];
                    $enabled = (int)$section['is_enabled'];
                    $status  = $section['status'];
                    $def     = $sectionDefs[$key]['config'] ?? [];
                ?>
                <div class="cf-section-card" data-section-id="<?php echo $sId; ?>" data-section-key="<?php echo esc($key); ?>" draggable="true">
                    <div class="cf-section-card__header">
                        <span class="cf-section-card__handle" aria-label="Drag to reorder">&#9776;</span>
                        <span class="cf-section-card__label"><?php echo $label; ?></span>
                        <span class="cf-section-card__status <?php echo $status === 'published' ? 'cf-section-card__status--published' : 'cf-section-card__status--draft'; ?>"><?php echo esc($status); ?></span>
                        <label class="cf-section-card__toggle">
                            <input type="checkbox" class="cf-section-card__toggle-input" <?php echo $enabled ? 'checked' : ''; ?>>
                            <span class="cf-section-card__toggle-slider"></span>
                        </label>
                        <button type="button" class="cf-section-card__expand" aria-expanded="false" aria-label="Expand section">
                            &#9660;
                        </button>
                    </div>
                    <div class="cf-section-card__body" hidden>
                        <form class="cf-section-form" data-autosave="true" data-section-id="<?php echo $sId; ?>">
                            <?php if ($key === 'hero'): ?>
                                <?php renderContactField('text', 'title', $config['title'], 'Title', ['placeholder' => 'Hero title']); ?>
                                <?php renderContactField('textarea', 'description', $config['description'], 'Description'); ?>
                                <?php renderContactField('image', 'bg_image', $config['bg_image'], 'Background Image'); ?>
                            <?php elseif ($key === 'contact_info'): ?>
                                <?php renderContactField('text', 'heading', $config['heading'], 'Section Heading'); ?>
                                <?php renderContactField('text', 'phone', $config['phone'], 'Phone Number'); ?>
                                <?php renderContactField('text', 'phone_label', $config['phone_label'], 'Phone Label'); ?>
                                <?php renderContactField('text', 'whatsapp', $config['whatsapp'], 'WhatsApp Number'); ?>
                                <?php renderContactField('text', 'whatsapp_label', $config['whatsapp_label'], 'WhatsApp Label'); ?>
                                <?php renderContactField('text', 'email', $config['email'], 'Email Address'); ?>
                                <?php renderContactField('text', 'email_label', $config['email_label'], 'Email Label'); ?>
                                <?php renderContactField('text', 'office_hours', $config['office_hours'], 'Office Hours'); ?>
                                <?php renderContactField('text', 'service_area', $config['service_area'], 'Service Area'); ?>
                                <?php renderContactField('text', 'address_line1', $config['address_line1'], 'Address Line 1'); ?>
                                <?php renderContactField('text', 'address_line2', $config['address_line2'], 'Address Line 2'); ?>
                                <?php renderContactField('toggle', 'show_map', $config['show_map'], 'Show Google Map'); ?>
                                <?php renderContactField('textarea', 'google_maps_embed', $config['google_maps_embed'], 'Google Maps Embed URL', ['rows' => 3]); ?>
                            <?php elseif ($key === 'contact_form'): ?>
                                <fieldset class="cf-fieldset">
                                    <legend>Form Headings</legend>
                                    <?php renderContactField('text', 'heading', $config['heading'], 'Section Heading'); ?>
                                    <?php renderContactField('textarea', 'subheading', $config['subheading'], 'Subheading', ['rows' => 2]); ?>
                                </fieldset>
                                <fieldset class="cf-fieldset">
                                    <legend>Field Labels &amp; Settings</legend>
                                    <div class="cf-grid--2col">
                                        <?php renderContactField('text', 'name_label', $config['name_label'], 'Name Label'); ?>
                                        <?php renderContactField('text', 'name_placeholder', $config['name_placeholder'], 'Name Placeholder'); ?>
                                        <?php renderContactField('toggle', 'name_required', $config['name_required'], 'Name Required'); ?>
                                    </div>
                                    <div class="cf-grid--2col">
                                        <?php renderContactField('text', 'email_label', $config['email_label'], 'Email Label'); ?>
                                        <?php renderContactField('text', 'email_placeholder', $config['email_placeholder'], 'Email Placeholder'); ?>
                                        <?php renderContactField('toggle', 'email_required', $config['email_required'], 'Email Required'); ?>
                                    </div>
                                    <div class="cf-grid--2col">
                                        <?php renderContactField('text', 'phone_label', $config['phone_label'], 'Phone Label'); ?>
                                        <?php renderContactField('text', 'phone_placeholder', $config['phone_placeholder'], 'Phone Placeholder'); ?>
                                        <?php renderContactField('toggle', 'phone_required', $config['phone_required'], 'Phone Required'); ?>
                                    </div>
                                    <div class="cf-grid--2col">
                                        <?php renderContactField('text', 'company_label', $config['company_label'], 'Company Label'); ?>
                                        <?php renderContactField('text', 'company_placeholder', $config['company_placeholder'], 'Company Placeholder'); ?>
                                        <?php renderContactField('toggle', 'company_required', $config['company_required'], 'Company Required'); ?>
                                    </div>
                                    <?php renderContactField('text', 'subject_label', $config['subject_label'], 'Subject Label'); ?>
                                    <?php renderContactField('text', 'subject_placeholder', $config['subject_placeholder'], 'Subject Placeholder'); ?>
                                    <?php renderContactField('toggle', 'subject_required', $config['subject_required'], 'Subject Required'); ?>
                                    <?php renderContactField('text', 'service_label', $config['service_label'], 'Service Label'); ?>
                                    <?php renderContactField('text', 'budget_label', $config['budget_label'], 'Budget Label'); ?>
                                    <?php renderContactField('text', 'timeline_label', $config['timeline_label'], 'Timeline Label'); ?>
                                    <?php renderContactField('text', 'message_label', $config['message_label'], 'Message Label'); ?>
                                    <?php renderContactField('text', 'message_placeholder', $config['message_placeholder'], 'Message Placeholder'); ?>
                                    <?php renderContactField('toggle', 'message_required', $config['message_required'], 'Message Required'); ?>
                                    <fieldset class="cf-fieldset">
                                        <legend>Budget Options (one per line)</legend>
                                        <textarea name="budget_options" class="cf-field__textarea" rows="4"><?php echo esc(implode("\n", $config['budget_options'] ?? [])); ?></textarea>
                                    </fieldset>
                                    <fieldset class="cf-fieldset">
                                        <legend>Timeline Options (one per line)</legend>
                                        <textarea name="timeline_options" class="cf-field__textarea" rows="4"><?php echo esc(implode("\n", $config['timeline_options'] ?? [])); ?></textarea>
                                    </fieldset>
                                </fieldset>
                                <fieldset class="cf-fieldset">
                                    <legend>File Upload</legend>
                                    <?php renderContactField('text', 'file_upload_label', $config['file_upload_label'], 'Upload Label'); ?>
                                    <?php renderContactField('toggle', 'file_upload_enabled', $config['file_upload_enabled'], 'Enable File Upload'); ?>
                                    <?php renderContactField('text', 'file_max_size', $config['file_max_size'], 'Max File Size (MB)'); ?>
                                    <?php renderContactField('text', 'file_allowed_types', $config['file_allowed_types'], 'Allowed File Types (comma separated)'); ?>
                                </fieldset>
                                <fieldset class="cf-fieldset">
                                    <legend>Consent &amp; Submit</legend>
                                    <?php renderContactField('text', 'consent_label', $config['consent_label'], 'Consent Checkbox Label'); ?>
                                    <?php renderContactField('toggle', 'consent_required', $config['consent_required'], 'Consent Required'); ?>
                                    <?php renderContactField('text', 'submit_label', $config['submit_label'], 'Submit Button Text'); ?>
                                    <?php renderContactField('textarea', 'success_message', $config['success_message'], 'Success Message', ['rows' => 2]); ?>
                                </fieldset>
                                <fieldset class="cf-fieldset">
                                    <legend>reCAPTCHA</legend>
                                    <?php renderContactField('toggle', 'recaptcha_enabled', $config['recaptcha_enabled'], 'Enable reCAPTCHA'); ?>
                                    <?php renderContactField('text', 'recaptcha_site_key', $config['recaptcha_site_key'], 'reCAPTCHA Site Key'); ?>
                                </fieldset>
                            <?php elseif ($key === 'whatsapp_cta'): ?>
                                <?php renderContactField('text', 'heading', $config['heading'], 'Section Heading'); ?>
                                <?php renderContactField('textarea', 'description', $config['description'], 'Description'); ?>
                                <?php renderContactField('text', 'button_text', $config['button_text'], 'Button Text'); ?>
                                <?php renderContactField('textarea', 'message_prefix', $config['message_prefix'], 'WhatsApp Pre-filled Message', ['rows' => 2]); ?>
                            <?php elseif ($key === 'faq'): ?>
                                <?php renderContactField('text', 'heading', $config['heading'], 'Section Heading'); ?>
                                <?php
                                    renderRepeater('FAQ Items', 'items', $config['items'] ?? [], [
                                        ['name' => 'question', 'label' => 'Question', 'type' => 'textarea', 'attrs' => ['rows' => 2]],
                                        ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'attrs' => ['rows' => 3]],
                                    ]);
                                ?>
                            <?php elseif ($key === 'final_cta'): ?>
                                <?php renderContactField('text', 'heading', $config['heading'], 'Section Heading'); ?>
                                <?php renderContactField('textarea', 'description', $config['description'], 'Description'); ?>
                                <?php renderContactField('text', 'button_text', $config['button_text'], 'Button Text'); ?>
                                <?php renderContactField('text', 'button_url', $config['button_url'], 'Button URL'); ?>
                                <?php renderContactField('text', 'bg_color', $config['bg_color'], 'Background Color (hex)'); ?>
                            <?php endif; ?>

                            <div class="cf-section-form__actions">
                                <button type="button" class="btn btn--primary cf-save-btn">Save</button>
                                <button type="button" class="btn btn--secondary cf-draft-btn">Save as Draft</button>
                                <span class="cf-section-form__saved" hidden>Saved</span>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cf-toast" id="cf-toast" hidden>
            <span class="cf-toast__message" id="cf-toast-message"></span>
        </div>
    </main>

    <script>
        window.CF_AJAX_URL = '/admin/ajax/contacts.php';
        window.CF_CSRF_TOKEN = '<?php echo csrfToken(); ?>';
    </script>
    <script src="/admin/assets/js/contacts-editor.js"></script>
</body>
</html>
