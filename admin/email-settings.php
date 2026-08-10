<?php
/**
 * Swap Design - Email Settings Admin Page
 *
 * SMTP configuration management for the EmailManager.
 * Configures host, port, encryption, authentication,
 * from address, and admin notification settings.
 *
 * @package SwapDesign
 */

require __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Email Settings';
$currentSection = 'email-settings';

$emailManager = new EmailManager();
$smtp         = $emailManager->getSmtpConfig();
$message      = '';
$messageType  = '';

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } elseif (!empty($_POST['action']) && $_POST['action'] === 'test') {
        $testEmail = $_POST['test_email'] ?? '';
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $message     = 'Please enter a valid test email address.';
            $messageType = 'error';
        } else {
            $sent = $emailManager->send($testEmail, 'Test Email from Swap Design CMS', '<p>This is a test email to verify your SMTP configuration.</p>', 'This is a test email to verify your SMTP configuration.');
            if ($sent) {
                $message     = 'Test email sent successfully to ' . esc($testEmail);
                $messageType = 'success';
            } else {
                $message     = 'Test email delivery failed. Check your SMTP settings.';
                $messageType = 'error';
            }
        }
    } else {
        $emailManager->saveSmtpConfig($_POST);

        /* Boolean checkboxes */
        $booleans = ['send_admin', 'send_user'];
        foreach ($booleans as $key) {
            $emailManager->saveSmtpConfig([$key => isset($_POST[$key]) ? '1' : '0']);
        }

        logInfo('Email settings updated');
        $message     = 'Email settings saved successfully.';
        $messageType = 'success';

        /* Reload config */
        $smtp = $emailManager->getSmtpConfig();
    }
}

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Email Settings</h1>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/email-settings.php">
    <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">

    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">SMTP Configuration</h2>
        </div>
        <div class="admin-card__body">
            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?php echo esc($smtp['smtp_host'] ?? ''); ?>" class="admin-form-input" placeholder="smtp.example.com">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">SMTP Port</label>
                    <input type="number" name="smtp_port" value="<?php echo esc($smtp['smtp_port'] ?? '587'); ?>" class="admin-form-input" placeholder="587">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Encryption</label>
                    <select name="smtp_encryption" class="admin-form-input">
                        <option value="tls" <?php echo ($smtp['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (port 587)</option>
                        <option value="ssl" <?php echo ($smtp['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (port 465)</option>
                        <option value="" <?php echo empty($smtp['smtp_encryption']) ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Username</label>
                    <input type="text" name="smtp_username" value="<?php echo esc($smtp['smtp_username'] ?? ''); ?>" class="admin-form-input" autocomplete="off">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Password</label>
                    <input type="password" name="smtp_password" value="<?php echo esc($smtp['smtp_password'] ?? ''); ?>" class="admin-form-input" autocomplete="off">
                    <p class="admin-form-hint">Leave unchanged to keep current password. Re-enter to update.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Sender Information</h2>
        </div>
        <div class="admin-card__body">
            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label">From Address</label>
                    <input type="email" name="from_address" value="<?php echo esc($smtp['from_address'] ?? ''); ?>" class="admin-form-input" placeholder="noreply@example.com">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">From Name</label>
                    <input type="text" name="from_name" value="<?php echo esc($smtp['from_name'] ?? ''); ?>" class="admin-form-input" placeholder="Swap Design">
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Notification Settings</h2>
        </div>
        <div class="admin-card__body">
            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label">Admin Notification Email</label>
                    <input type="email" name="admin_email" value="<?php echo esc($smtp['admin_email'] ?? ''); ?>" class="admin-form-input" placeholder="admin@example.com">
                    <p class="admin-form-hint">New lead notifications will be sent to this address.</p>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-checkbox">
                        <input type="checkbox" name="send_admin" value="1" <?php echo ($smtp['send_admin'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <span>Send admin notification on new lead</span>
                    </label>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-checkbox">
                        <input type="checkbox" name="send_user" value="1" <?php echo ($smtp['send_user'] ?? '1') === '1' ? 'checked' : ''; ?>>
                        <span>Send user confirmation on form submission</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-form-actions">
        <button type="submit" class="admin-btn admin-btn--primary admin-btn--lg">Save Settings</button>
    </div>
</form>

<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Test Email</h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/email-settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="test">
            <div class="admin-form-inline">
                <div class="admin-form-group">
                    <label class="admin-form-label">Send Test To</label>
                    <input type="email" name="test_email" class="admin-form-input" placeholder="your@email.com" required>
                </div>
                <button type="submit" class="admin-btn admin-btn--secondary">Send Test</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
