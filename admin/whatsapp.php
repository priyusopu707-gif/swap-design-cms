<?php
/**
 * Swap Design - WhatsApp Settings Admin Page
 *
 * Global WhatsApp configuration, live preview, analytics dashboard,
 * number validation, test button.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'WhatsApp Settings';
$currentSection = 'whatsapp';

$whatsapp = new WhatsAppManager();
$message  = '';
$messageType = '';

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $postAction = $_POST['action'] ?? 'save';

        switch ($postAction) {
            case 'save':
                $whatsapp->saveSettings($_POST);
                $message     = 'WhatsApp settings saved.';
                $messageType = 'success';
                break;

            case 'test':
                $number = $whatsapp->normalizePhoneNumber($_POST['test_number'] ?? '');
                $text   = sanitizeString($_POST['test_message'] ?? 'Hello!');
                header('Location: ' . $whatsapp->buildUrl($number, $text));
                exit;
        }
    }
}

$settings  = $whatsapp->getSettings();
$stats     = $whatsapp->getStats();
$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">WhatsApp Integration</h1>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<!-- Global Settings -->
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Global Settings</h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/whatsapp.php">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="save">

            <!-- Enable -->
            <div class="admin-form-group">
                <label class="admin-toggle">
                    <input type="checkbox" name="enabled" value="1" <?php echo $settings['enabled'] ? 'checked' : ''; ?>>
                    <span class="admin-toggle__slider"></span>
                    <span>Enable WhatsApp Integration</span>
                </label>
            </div>

            <div class="admin-form-grid">
                <!-- Phone Number -->
                <div class="admin-form-group">
                    <label class="admin-form-label">WhatsApp Number <span class="admin-required">*</span></label>
                    <input type="text" name="phone_number" value="<?php echo esc($settings['phone_number']); ?>"
                           class="admin-form-input" placeholder="1234567890 (with country code)">
                    <small style="color:var(--admin-text-muted);font-size:0.75rem">Include country code, no + or spaces.</small>
                </div>

                <!-- Button Position -->
                <div class="admin-form-group">
                    <label class="admin-form-label">Button Position</label>
                    <select name="button_position" class="admin-form-input">
                        <option value="right" <?php echo $settings['button_position'] === 'right' ? 'selected' : ''; ?>>Bottom Right</option>
                        <option value="left" <?php echo $settings['button_position'] === 'left' ? 'selected' : ''; ?>>Bottom Left</option>
                    </select>
                </div>

                <!-- Button Style -->
                <div class="admin-form-group">
                    <label class="admin-form-label">Button Style</label>
                    <select name="button_style" class="admin-form-input">
                        <option value="icon_text" <?php echo $settings['button_style'] === 'icon_text' ? 'selected' : ''; ?>>Icon + Text</option>
                        <option value="icon" <?php echo $settings['button_style'] === 'icon' ? 'selected' : ''; ?>>Icon Only</option>
                    </select>
                </div>

                <!-- Show Online Status -->
                <div class="admin-form-group">
                    <label class="admin-form-label">Online Status</label>
                    <select name="show_online_status" class="admin-form-input">
                        <option value="1" <?php echo $settings['show_online_status'] ? 'selected' : ''; ?>>Show Online/Offline</option>
                        <option value="0" <?php echo !$settings['show_online_status'] ? 'selected' : ''; ?>>Always Show Online</option>
                    </select>
                </div>

                <!-- Business Hours Start -->
                <div class="admin-form-group">
                    <label class="admin-form-label">Business Hours Start</label>
                    <input type="time" name="business_hours_start" value="<?php echo esc($settings['business_hours_start']); ?>" class="admin-form-input">
                </div>

                <!-- Business Hours End -->
                <div class="admin-form-group">
                    <label class="admin-form-label">Business Hours End</label>
                    <input type="time" name="business_hours_end" value="<?php echo esc($settings['business_hours_end']); ?>" class="admin-form-input">
                </div>

                <!-- Device Visibility -->
                <div class="admin-form-group">
                    <label class="admin-form-checkbox">
                        <input type="checkbox" name="show_on_desktop" value="1" <?php echo $settings['show_on_desktop'] ? 'checked' : ''; ?>>
                        <span>Show on Desktop</span>
                    </label>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-checkbox">
                        <input type="checkbox" name="show_on_mobile" value="1" <?php echo $settings['show_on_mobile'] ? 'checked' : ''; ?>>
                        <span>Show on Mobile</span>
                    </label>
                </div>
            </div>

            <!-- Default Message -->
            <div class="admin-form-group u-mt-sm">
                <label class="admin-form-label">Default Message</label>
                <textarea name="default_message" class="admin-form-input admin-form-textarea" rows="3" maxlength="500"><?php echo esc($settings['default_message']); ?></textarea>
                <small style="color:var(--admin-text-muted);font-size:0.75rem">
                    Available placeholders: <code>{page_title}</code> <code>{service_name}</code> <code>{portfolio_title}</code> <code>{site_name}</code>
                </small>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary admin-btn--lg">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- Live Preview -->
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Live Preview</h2>
    </div>
    <div class="admin-card__body">
        <div class="wa-preview">
            <div class="wa-preview__phone">
                <div class="wa-preview__screen">
                    <div class="wa-preview__page">
                        <div class="wa-preview__content">
                            <div class="wa-preview__text">Your website page content...</div>
                        </div>
                        <div class="wa-preview__button" data-position="<?php echo esc($settings['button_position']); ?>" data-style="<?php echo esc($settings['button_style']); ?>" data-online="<?php echo ($settings['show_online_status'] && $whatsapp->isBusinessHours()) ? '1' : '0'; ?>">
                            <div class="wa-preview__btn-inner">
                                <span class="wa-preview__icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                                </span>
                                <span class="wa-preview__label" <?php echo $settings['button_style'] === 'icon' ? 'style="display:none"' : ''; ?>>Chat with us</span>
                                <span class="wa-preview__status" <?php echo !$settings['show_online_status'] ? 'style="display:none"' : ''; ?>>Online</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wa-preview__notch"></div>
            </div>
            <p class="wa-preview__caption">Mobile preview -- actual button will appear on every page</p>
        </div>
    </div>
</div>

<!-- Test Button -->
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Test Message</h2>
    </div>
    <div class="admin-card__body">
        <form method="POST" action="/admin/whatsapp.php" target="_blank">
            <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
            <input type="hidden" name="action" value="test">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label class="admin-form-label">Phone Number</label>
                    <input type="text" name="test_number" value="<?php echo esc($settings['phone_number']); ?>" class="admin-form-input">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Message</label>
                    <input type="text" name="test_message" value="Hello! Testing WhatsApp integration from Swap Design." class="admin-form-input">
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">Open WhatsApp</button>
            </div>
        </form>
    </div>
</div>

<!-- Analytics Dashboard -->
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Analytics</h2>
    </div>
    <div class="admin-card__body">
        <!-- Stat Cards -->
        <div class="admin-stats u-mb-md">
            <div class="admin-stat-card">
                <span class="admin-stat-card__value"><?php echo number_format($stats['total']); ?></span>
                <span class="admin-stat-card__label">Total Clicks</span>
            </div>
        </div>

        <div class="admin-dashboard-grid">
            <!-- Per Device -->
            <?php if (!empty($stats['per_device'])): ?>
            <div class="admin-card">
                <div class="admin-card__header">
                    <h3 class="admin-card__title">Clicks by Device</h3>
                </div>
                <div class="admin-card__body">
                    <table class="admin-table">
                        <thead><tr><th>Device</th><th>Clicks</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats['per_device'] as $row): ?>
                            <tr>
                                <td><?php echo esc(ucfirst($row['device_type'])); ?></td>
                                <td><?php echo number_format($row['cnt']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Per Source -->
            <?php if (!empty($stats['per_source'])): ?>
            <div class="admin-card">
                <div class="admin-card__header">
                    <h3 class="admin-card__title">Clicks by Source</h3>
                </div>
                <div class="admin-card__body">
                    <table class="admin-table">
                        <thead><tr><th>Source</th><th>Clicks</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats['per_source'] as $row): ?>
                            <tr>
                                <td><?php echo esc(str_replace('_', ' ', ucfirst($row['source']))); ?></td>
                                <td><?php echo number_format($row['cnt']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Per Page -->
        <?php if (!empty($stats['per_page'])): ?>
        <div class="admin-card u-mt-sm">
            <div class="admin-card__header">
                <h3 class="admin-card__title">Top Pages</h3>
            </div>
            <div class="admin-card__body">
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead><tr><th>Page</th><th>Clicks</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats['per_page'] as $row): ?>
                            <tr>
                                <td><?php echo esc($row['page_title']); ?></td>
                                <td><?php echo number_format($row['cnt']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Clicks -->
        <?php if (!empty($stats['recent'])): ?>
        <div class="admin-card u-mt-sm">
            <div class="admin-card__header">
                <h3 class="admin-card__title">Recent Clicks</h3>
            </div>
            <div class="admin-card__body">
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead><tr><th>Time</th><th>Source</th><th>Page</th><th>Device</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats['recent'] as $row):
                                $time = strtotime($row['clicked_at']);
                            ?>
                            <tr>
                                <td><?php echo date('M j, H:i', $time); ?></td>
                                <td><?php echo esc(str_replace('_', ' ', ucfirst($row['source']))); ?></td>
                                <td><?php echo esc($row['page_title'] ?? '-'); ?></td>
                                <td><?php echo esc(ucfirst($row['device_type'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ============================================================
   WhatsApp Admin: Live Preview Phone Mockup
   ============================================================ */
.wa-preview {
    text-align: center;
}

.wa-preview__phone {
    width: 280px;
    height: 480px;
    background: #1a1a2e;
    border-radius: 32px;
    padding: 12px;
    margin: 0 auto 1rem;
    position: relative;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}

.wa-preview__notch {
    width: 120px;
    height: 6px;
    background: #333;
    border-radius: 3px;
    margin: 0 auto;
}

.wa-preview__screen {
    height: 100%;
    background: #ffffff;
    border-radius: 24px;
    overflow: hidden;
    position: relative;
}

.wa-preview__page {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.wa-preview__content {
    flex: 1;
    padding: 20px 16px;
    background: #f5f5f5;
}

.wa-preview__text {
    color: #999;
    font-size: 0.75rem;
    font-family: monospace;
}

.wa-preview__button {
    position: absolute;
    bottom: 16px;
    z-index: 10;
}

.wa-preview__button[data-position="right"] { right: 16px; }
.wa-preview__button[data-position="left"]  { left: 16px; }

.wa-preview__btn-inner {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #25d366;
    color: #fff;
    border-radius: 50px;
    padding: 8px 14px;
    font-size: 0.75rem;
    font-weight: 500;
    box-shadow: 0 2px 10px rgba(37,211,102,0.35);
}

.wa-preview__button[data-style="icon"] .wa-preview__btn-inner {
    padding: 8px;
    border-radius: 50%;
}

.wa-preview__button[data-online="0"] .wa-preview__btn-inner {
    background: #999;
    box-shadow: 0 2px 10px rgba(0,0,0,0.12);
}

.wa-preview__icon {
    display: flex;
    align-items: center;
}

.wa-preview__status {
    font-size: 0.5625rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: rgba(255,255,255,0.2);
    padding: 1px 6px;
    border-radius: 8px;
}

.wa-preview__caption {
    font-size: 0.8125rem;
    color: var(--admin-text-muted);
}
</style>

<?php require __DIR__ . '/includes/footer.php';
