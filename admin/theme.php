<?php
/**
 * Swap Design - Theme Settings Admin Page
 *
 * Edit colors, typography, layout values. Changes generate
 * the dynamic theme.css file on save.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Theme Settings';
$currentSection = 'theme';

$themeEngine    = new ThemeEngine();
$settings       = new SettingsManager();
$groups         = $themeEngine->getThemeGroups();
$values         = $themeEngine->getThemeValues();
$message        = '';
$messageType    = '';

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        foreach ($_POST as $key => $value) {
            if (str_starts_with($key, 'theme.')) {
                $settings->set($key, sanitizeString($value));
                $values[$key] = $value;
            }
        }

        if ($themeEngine->generate()) {
            logInfo('Theme settings updated');
            $message     = 'Theme settings saved and CSS regenerated.';
            $messageType = 'success';
        } else {
            $message     = 'Settings saved but CSS generation failed. Check write permissions.';
            $messageType = 'warning';
        }
    }
}

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Theme Settings</h1>
    <p class="admin-page-header__subtitle">Customize colors, typography, and layout for the frontend.</p>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/theme.php">
    <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">

    <?php foreach ($groups as $groupKey => $group): ?>
    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title"><?php echo esc($group['label']); ?></h2>
        </div>
        <div class="admin-card__body">
            <div class="admin-form-grid">
                <?php foreach ($group['fields'] as $fieldKey => $field): ?>
                <div class="admin-form-group">
                    <label class="admin-form-label" for="<?php echo esc($fieldKey); ?>">
                        <?php echo esc($field['label']); ?>
                    </label>
                    <?php if ($field['type'] === 'color'): ?>
                        <div class="admin-color-picker">
                            <input
                                type="color"
                                id="<?php echo esc($fieldKey); ?>"
                                name="<?php echo esc($fieldKey); ?>"
                                value="<?php echo esc($values[$fieldKey] ?? $field['default']); ?>"
                                class="admin-color-input"
                            >
                            <input
                                type="text"
                                value="<?php echo esc($values[$fieldKey] ?? $field['default']); ?>"
                                class="admin-form-input admin-color-hex"
                                data-color-for="<?php echo esc($fieldKey); ?>"
                                pattern="#[0-9a-fA-F]{6}"
                            >
                        </div>
                    <?php else: ?>
                        <input
                            type="text"
                            id="<?php echo esc($fieldKey); ?>"
                            name="<?php echo esc($fieldKey); ?>"
                            value="<?php echo esc($values[$fieldKey] ?? $field['default']); ?>"
                            class="admin-form-input"
                        >
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="admin-form-actions">
        <button type="submit" class="admin-btn admin-btn--primary admin-btn--lg">Save Changes</button>
        <a href="/admin/theme.php" class="admin-btn admin-btn--secondary">Reset</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php';
