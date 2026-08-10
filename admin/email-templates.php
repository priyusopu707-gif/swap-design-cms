<?php
/**
 * Swap Design - Email Templates Admin Page
 *
 * Manage email notification templates.
 * Edit subject, HTML body, and plain text body.
 * Templates are stored in the email_templates table.
 *
 * @package SwapDesign
 */

require __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Email Templates';
$currentSection = 'email-templates';

$emailManager = new EmailManager();
$message      = '';
$messageType  = '';

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } elseif (!empty($_POST['action']) && $_POST['action'] === 'edit_template' && !empty($_POST['template_key'])) {
        $key   = $_POST['template_key'];
        $data  = [
            'subject'   => sanitizeString($_POST['subject'] ?? ''),
            'body_html' => $_POST['body_html'] ?? '',
            'body_text' => $_POST['body_text'] ?? '',
        ];
        $emailManager->updateTemplate($key, $data);
        logInfo('Email template updated', ['template_key' => $key]);
        $message     = 'Template "' . esc($key) . '" updated successfully.';
        $messageType = 'success';
    }
}

$templates = $emailManager->getAllTemplates();

/* Determine which template is being edited */
$editKey  = $_GET['edit'] ?? '';
$editTmpl = null;
if ($editKey) {
    $editTmpl = $emailManager->getTemplate($editKey);
}

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Email Templates</h1>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<div class="admin-tabs" role="tablist">
    <?php foreach ($templates as $t): ?>
    <a href="?edit=<?php echo esc($t['template_key']); ?>"
       class="admin-tab<?php echo $editKey === $t['template_key'] || (!$editKey && $t === $templates[0]) ? ' admin-tab--active' : ''; ?>"
       role="tab"
       aria-selected="<?php echo $editKey === $t['template_key'] || (!$editKey && $t === $templates[0]) ? 'true' : 'false'; ?>">
        <?php echo esc($t['name']); ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($editTmpl): ?>
<form method="POST" action="/admin/email-templates.php?edit=<?php echo esc($editTmpl['template_key']); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
    <input type="hidden" name="action" value="edit_template">
    <input type="hidden" name="template_key" value="<?php echo esc($editTmpl['template_key']); ?>">

    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title"><?php echo esc($editTmpl['name']); ?></h2>
            <span class="admin-badge admin-badge--info"><?php echo esc($editTmpl['template_key']); ?></span>
        </div>
        <div class="admin-card__body">
            <div class="admin-form-group">
                <label class="admin-form-label">Subject</label>
                <input type="text" name="subject" value="<?php echo esc($editTmpl['subject']); ?>" class="admin-form-input" required>
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">HTML Body</label>
                <textarea name="body_html" rows="15" class="admin-form-textarea admin-form-textarea--mono" style="font-family:monospace;font-size:13px;line-height:1.5"><?php echo esc($editTmpl['body_html']); ?></textarea>
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Plain Text Body (fallback)</label>
                <textarea name="body_text" rows="8" class="admin-form-textarea admin-form-textarea--mono" style="font-family:monospace;font-size:13px;line-height:1.5"><?php echo esc($editTmpl['body_text']); ?></textarea>
            </div>
        </div>
    </div>

    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Available Variables</h2>
        </div>
        <div class="admin-card__body">
            <?php if (!empty($editTmpl['variables_help'])): ?>
            <pre class="admin-form-help-text" style="background:#f5f5f5;padding:12px;border-radius:6px;font-size:13px;line-height:1.6;white-space:pre-wrap"><?php echo esc($editTmpl['variables_help']); ?></pre>
            <?php else: ?>
            <p class="admin-form-hint">No variable documentation defined for this template.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-form-actions">
        <button type="submit" class="admin-btn admin-btn--primary admin-btn--lg">Save Template</button>
        <a href="/admin/email-templates.php" class="admin-btn admin-btn--ghost">Cancel</a>
    </div>
</form>
<?php elseif (!empty($templates)): ?>
    <?php $first = $templates[0]; ?>
    <p>Select a template above to edit, or <a href="?edit=<?php echo esc($first['template_key']); ?>">edit <?php echo esc($first['name']); ?></a>.</p>
<?php else: ?>
<div class="admin-card">
    <div class="admin-card__body">
        <div class="empty-state">
            <p>No email templates found. Templates are created automatically when the database is seeded.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
