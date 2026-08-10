<?php
/**
 * Swap Design - Settings Admin Page
 *
 * Combined Business Profile + Website Settings in tabbed layout.
 * Tab 1: Business Profile (brand info, contact, social)
 * Tab 2: Website Settings (SEO defaults, forms, analytics, features)
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Settings';
$currentSection = 'settings';

$settings = new SettingsManager();
$message  = '';
$messageType = '';

/* Active tab */
$activeTab = $_GET['tab'] ?? 'business';
if (!in_array($activeTab, ['business', 'website'])) {
    $activeTab = 'business';
}

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message     = 'Security check failed.';
        $messageType = 'error';
    } else {
        $activeTab = $_POST['tab'] ?? 'business';
        $saveKeys  = [];

        if ($activeTab === 'business') {
            $saveKeys = [
                'brand.name', 'brand.tagline', 'brand.description',
                'brand.email', 'brand.phone', 'brand.address',
                'brand.business_type', 'brand.founded_year',
                'brand.facebook', 'brand.instagram', 'brand.linkedin',
                'brand.behance', 'brand.dribbble', 'brand.youtube', 'brand.twitter',
            ];
        } else {
            $saveKeys = [
                'seo.default_title', 'seo.default_description', 'seo.title_template',
                'seo.google_verification', 'site.ga_id', 'site.gtm_id',
                'site.fb_pixel_id', 'site.clarity_id',
                'site.feature_blog', 'site.feature_portfolio', 'site.feature_testimonials',
                'site.feature_newsletter', 'forms.contact_email', 'forms.enable_honeypot', 'forms.enable_csrf',
            ];
        }

        foreach ($saveKeys as $key) {
            if (isset($_POST[$key])) {
                $value = sanitizeString($_POST[$key]);
                $settings->set($key, $value);
            }
        }

        /* Boolean checkboxes */
        $booleans = ['site.feature_blog', 'site.feature_portfolio', 'site.feature_testimonials',
                     'site.feature_newsletter', 'forms.enable_honeypot', 'forms.enable_csrf'];
        foreach ($booleans as $key) {
            $settings->set($key, isset($_POST[$key]) ? '1' : '0');
        }

        logInfo('Settings updated', ['tab' => $activeTab]);
        $message     = 'Settings saved successfully.';
        $messageType = 'success';
    }
}

/* Load current values */
$get = fn(string $key, string $default = '') => $settings->get($key, $default);

$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Settings</h1>
</div>

<?php if ($message): ?>
    <div class="admin-flash admin-flash--<?php echo $messageType; ?>" role="alert">
        <?php echo esc($message); ?>
        <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>

<!-- Tabs -->
<nav class="admin-tabs" role="tablist">
    <a href="?tab=business" class="admin-tab<?php echo $activeTab === 'business' ? ' admin-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo $activeTab === 'business' ? 'true' : 'false'; ?>">Business Profile</a>
    <a href="?tab=website" class="admin-tab<?php echo $activeTab === 'website' ? ' admin-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo $activeTab === 'website' ? 'true' : 'false'; ?>">Website Settings</a>
</nav>

<form method="POST" action="/admin/settings.php?tab=<?php echo esc($activeTab); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo esc($csrfToken); ?>">
    <input type="hidden" name="tab" value="<?php echo esc($activeTab); ?>">

    <?php if ($activeTab === 'business'): ?>
    <!-- ====== Business Profile ====== -->
    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Brand Identity</h2>
        </div>
        <div class="admin-card__body">
            <div class="admin-form-grid">
                <?php
                $brandFields = [
                    'brand.name'           => 'Company Name',
                    'brand.tagline'        => 'Tagline',
                    'brand.description'    => 'Description',
                    'brand.business_type'  => 'Business Type',
                    'brand.founded_year'   => 'Year Founded',
                ];
                foreach ($brandFields as $key => $label):
                ?>
                <div class="admin-form-group">
                    <label class="admin-form-label" for="<?php echo str_replace('.', '_', esc($key)); ?>"><?php echo esc($label); ?></label>
                    <input type="text" name="<?php echo esc($key); ?>" id="<?php echo str_replace('.', '_', esc($key)); ?>" value="<?php echo esc($get($key)); ?>" class="admin-form-input">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Contact Information</h2>
        </div>
        <div class="admin-card__body">
            <div class="admin-form-grid">
                <?php foreach (['brand.email' => 'Email', 'brand.phone' => 'Phone', 'brand.address' => 'Address'] as $key => $label): ?>
                <div class="admin-form-group">
                    <label class="admin-form-label" for="<?php echo str_replace('.', '_', esc($key)); ?>"><?php echo esc($label); ?></label>
                    <input type="text" name="<?php echo esc($key); ?>" id="<?php echo str_replace('.', '_', esc($key)); ?>" value="<?php echo esc($get($key)); ?>" class="admin-form-input">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Social Media Links</h2>
        </div>
        <div class="admin-card__body">
            <div class="admin-form-grid">
                <?php
                $socialFields = ['brand.facebook' => 'Facebook', 'brand.instagram' => 'Instagram',
                    'brand.linkedin' => 'LinkedIn', 'brand.behance' => 'Behance',
                    'brand.dribbble' => 'Dribbble', 'brand.youtube' => 'YouTube',
                    'brand.twitter' => 'Twitter'];
                foreach ($socialFields as $key => $label):
                ?>
                <div class="admin-form-group">
                    <label class="admin-form-label" for="<?php echo str_replace('.', '_', esc($key)); ?>"><?php echo esc($label); ?> URL</label>
                    <input type="url" name="<?php echo esc($key); ?>" id="<?php echo str_replace('.', '_', esc($key)); ?>" value="<?php echo esc($get($key)); ?>" class="admin-form-input" placeholder="https://">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ====== Website Settings ====== -->
    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">SEO Defaults</h2>
        </div>
        <div class="admin-card__body">
            <?php
            $seoFields = [
                'seo.default_title'       => 'Default Meta Title',
                'seo.default_description' => 'Default Meta Description',
                'seo.title_template'      => 'Title Template (%s = page title)',
                'seo.google_verification' => 'Google Verification Code',
            ];
            foreach ($seoFields as $key => $label): ?>
            <div class="admin-form-group">
                <label class="admin-form-label" for="<?php echo str_replace('.', '_', esc($key)); ?>"><?php echo esc($label); ?></label>
                <input type="text" name="<?php echo esc($key); ?>" id="<?php echo str_replace('.', '_', esc($key)); ?>" value="<?php echo esc($get($key)); ?>" class="admin-form-input">
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Analytics & Tracking</h2>
        </div>
        <div class="admin-card__body">
            <?php
            $analyticsFields = [
                'site.ga_id'       => 'Google Analytics ID (G-XXXXXXX)',
                'site.gtm_id'      => 'Google Tag Manager ID (GTM-XXXX)',
                'site.fb_pixel_id' => 'Facebook Pixel ID',
                'site.clarity_id'  => 'Microsoft Clarity ID',
            ];
            foreach ($analyticsFields as $key => $label): ?>
            <div class="admin-form-group">
                <label class="admin-form-label" for="<?php echo str_replace('.', '_', esc($key)); ?>"><?php echo esc($label); ?></label>
                <input type="text" name="<?php echo esc($key); ?>" id="<?php echo str_replace('.', '_', esc($key)); ?>" value="<?php echo esc($get($key)); ?>" class="admin-form-input">
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Contact Form Settings</h2>
        </div>
        <div class="admin-card__body">
            <div class="admin-form-group">
                <label class="admin-form-label" for="forms_contact_email">Recipient Email</label>
                <input type="email" name="forms.contact_email" id="forms_contact_email" value="<?php echo esc($get('forms.contact_email')); ?>" class="admin-form-input">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-checkbox">
                    <input type="checkbox" name="forms.enable_honeypot" value="1" <?php echo $get('forms.enable_honeypot', '1') === '1' ? 'checked' : ''; ?>>
                    <span>Enable Honeypot Spam Protection</span>
                </label>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-checkbox">
                    <input type="checkbox" name="forms.enable_csrf" value="1" <?php echo $get('forms.enable_csrf', '1') === '1' ? 'checked' : ''; ?>>
                    <span>Enable CSRF Protection</span>
                </label>
            </div>
        </div>
    </div>

    <div class="admin-card u-mb-md">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Feature Toggles</h2>
        </div>
        <div class="admin-card__body">
            <?php foreach (['site.feature_blog' => 'Blog', 'site.feature_portfolio' => 'Portfolio',
                'site.feature_testimonials' => 'Testimonials', 'site.feature_newsletter' => 'Newsletter'] as $key => $label): ?>
            <div class="admin-form-group">
                <label class="admin-form-checkbox">
                    <input type="checkbox" name="<?php echo esc($key); ?>" value="1" <?php echo $get($key, '0') === '1' ? 'checked' : ''; ?>>
                    <span>Enable <?php echo esc($label); ?></span>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="admin-form-actions">
        <button type="submit" class="admin-btn admin-btn--primary admin-btn--lg">Save Settings</button>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php';
