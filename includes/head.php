<?php
/**
 * Swap Design - Head Component
 *
 * Renders the complete <head> section with all meta tags,
 * SEO data, Open Graph, Twitter Card, favicon, PWA manifest,
 * stylesheets, Google Fonts, structured data, and analytics.
 *
 * Required: $site (global site config)
 * Uses: $pageTitle, $pageDescription, $pageCanonical, $pageOgImage,
 *        $pageOgType, $pageRobots, $pageTwitterCard, $pageNoIndex,
 *        $pageCss, $pageSchema
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

global $site;

/* Meta globals are set via RenderingEngine::setGlobalMeta() in the
   global scope, so re-declare them here for include-inside-method use. */
global $pageTitle, $pageDescription, $pageCanonical, $pageOgImage, $pageOgType;
global $pageRobots, $pageNoIndex, $pageTwitterCard, $pageCss, $pageSchema;
global $pageOgImageAlt, $pageOgImageWidth, $pageOgImageHeight;

$brandName     = $site->brand->name;
$brandLang     = $site->brand->language ?? 'en';
$titleTemplate = $site->seo->titleTemplate ?? '%s | ' . $brandName;

$seoTitle       = $site->seo->defaultTitle;
if (isset($pageTitle) && $pageTitle !== '') {
    /* Avoid double suffixing when the title already ends with the brand. */
    if ($site->brand->name !== '' && str_ends_with($pageTitle, $site->brand->name)) {
        $seoTitle = $pageTitle;
    } else {
        $seoTitle = sprintf($titleTemplate, $pageTitle);
    }
}
$seoDescription = $pageDescription ?? $site->seo->defaultDescription;
$seoCanonical   = $pageCanonical   ?? currentUrl();
$seoOgImage     = $pageOgImage     ?? $site->seo->defaultOgImage;
$seoOgType      = $pageOgType      ?? $site->seo->defaultOgType;
$robots         = ($pageNoIndex ?? false) ? 'noindex, follow' : ($pageRobots ?? 'index, follow');
$twitterCard    = $pageTwitterCard ?? 'summary_large_image';

/* Enforce canonical/trailing-slash hygiene: the canonical URL should be
   the absolute http(s) URL without a trailing slash (except the root). */
if ($seoCanonical !== '/' && str_ends_with($seoCanonical, '/')) {
    $seoCanonical = rtrim($seoCanonical, '/');
}
if (strpos($seoCanonical, 'http') !== 0) {
    $seoCanonical = rtrim($site->urls->base, '/') . '/' . ltrim($seoCanonical, '/');
}
$seoOgImage = (strpos($seoOgImage, 'http') === 0) ? $seoOgImage : (rtrim($site->urls->base, '/') . '/' . ltrim($seoOgImage, '/'));
$seoOgImageAlt = $pageOgImageAlt ?? ($site->brand->description ?? '');
?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="format-detection" content="telephone=no">
<meta name="theme-color" content="<?php echo esc($site->favicon->themeColor ?? '#0a0a0a'); ?>">

<title><?php echo esc($seoTitle); ?></title>
<meta name="description" content="<?php echo esc($seoDescription); ?>">
<meta name="robots" content="<?php echo esc($robots); ?>">
<meta name="author" content="<?php echo esc($brandName); ?>">
<link rel="canonical" href="<?php echo esc($seoCanonical); ?>">

<?php if ($site->seo->googleSiteVerification): ?>
<meta name="google-site-verification" content="<?php echo esc($site->seo->googleSiteVerification); ?>">
<?php endif; ?>

<!-- Open Graph -->
<meta property="og:type" content="<?php echo esc($seoOgType); ?>">
<meta property="og:url" content="<?php echo esc($seoCanonical); ?>">
<meta property="og:title" content="<?php echo esc($seoTitle); ?>">
<meta property="og:description" content="<?php echo esc($seoDescription); ?>">
<meta property="og:image" content="<?php echo esc($seoOgImage); ?>">
<?php if ($seoOgImageAlt): ?>
<meta property="og:image:alt" content="<?php echo esc($seoOgImageAlt); ?>">
<?php endif; ?>
<?php if ($pageOgImageWidth ?? null): ?>
<meta property="og:image:width" content="<?php echo (int)$pageOgImageWidth; ?>">
<meta property="og:image:height" content="<?php echo (int)$pageOgImageHeight; ?>">
<?php endif; ?>
<meta property="og:site_name" content="<?php echo esc($brandName); ?>">
<meta property="og:locale" content="<?php echo esc($brandLang); ?>_<?php echo strtoupper($brandLang); ?>">
<?php if ($site->seo->ogLocaleAlternate ?? null): ?>
<meta property="og:locale:alternate" content="<?php echo esc($site->seo->ogLocaleAlternate); ?>">
<?php endif; ?>

<!-- Twitter Card -->
<meta name="twitter:card" content="<?php echo esc($twitterCard); ?>">
<meta name="twitter:title" content="<?php echo esc($seoTitle); ?>">
<meta name="twitter:description" content="<?php echo esc($seoDescription); ?>">
<meta name="twitter:image" content="<?php echo esc($seoOgImage); ?>">
<meta name="twitter:url" content="<?php echo esc($seoCanonical); ?>">
<?php if ($site->seo->twitterHandle): ?>
<meta name="twitter:site" content="<?php echo esc($site->seo->twitterHandle); ?>">
<meta name="twitter:creator" content="<?php echo esc($site->seo->twitterHandle); ?>">
<?php endif; ?>

<!-- Favicon & PWA -->
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $site->favicon->favicon32; ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $site->favicon->favicon16; ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $site->favicon->appleIcon; ?>">
<meta name="theme-color" content="<?php echo $site->favicon->themeColor; ?>">
<meta name="msapplication-TileColor" content="<?php echo $site->favicon->themeColor; ?>">
<link rel="manifest" href="<?php echo $site->favicon->manifest; ?>">

<!-- Preconnect to Google Fonts for performance -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- Design Tokens & Base (load FIRST so variables override everything) -->
<link rel="stylesheet" href="/assets/css/components/design-system/design-tokens.css">
<link rel="stylesheet" href="/assets/css/components/design-system/base.css">

<!-- Global Stylesheets (order matters) -->
<link rel="stylesheet" href="/assets/css/main.css">
<link rel="stylesheet" href="/assets/css/theme-generated.css">
<link rel="stylesheet" href="/assets/css/responsive.css">
<link rel="stylesheet" href="/assets/css/components/header-nav.css">
<link rel="stylesheet" href="/assets/css/components/search.css">
<link rel="stylesheet" href="/assets/css/components/footer.css">
<link rel="stylesheet" href="/assets/css/components/layout.css">
<link rel="stylesheet" href="/assets/css/components/breadcrumb.css">
<link rel="stylesheet" href="/assets/css/components/cta.css">

<!-- Swap Design System Components (core blocking) -->
<link rel="stylesheet" href="/assets/css/components/design-system/glass.css">
<link rel="stylesheet" href="/assets/css/components/design-system/buttons.css">
<link rel="stylesheet" href="/assets/css/components/design-system/forms.css">
<link rel="stylesheet" href="/assets/css/components/design-system/cards.css">
<link rel="stylesheet" href="/assets/css/components/design-system/section.css">
<link rel="stylesheet" href="/assets/css/components/design-system/components.css">
<link rel="stylesheet" href="/assets/css/components/design-system/icons.css">
<link rel="stylesheet" href="/assets/css/components/design-system/faq.css">
<link rel="stylesheet" href="/assets/css/components/design-system/empty-state.css">
<link rel="stylesheet" href="/assets/css/components/design-system/3d.css">
<link rel="stylesheet" href="/assets/css/components/design-system/animations.css">

<!-- Secondary design-system components (non-render-blocking: only load when needed) -->
<link rel="stylesheet" href="/assets/css/components/design-system/badges.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="/assets/css/components/design-system/tabs.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="/assets/css/components/design-system/modal.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="/assets/css/components/design-system/drawer.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="/assets/css/components/design-system/toast.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="/assets/css/components/design-system/pagination.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="/assets/css/components/design-system/whatsapp.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="/assets/css/components/design-system/back-to-top.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="/assets/css/components/design-system/progress.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="/assets/css/components/breadcrumb.css" media="print" onload="this.media='all'">

<?php if (isset($pageCss) && is_array($pageCss)): ?>
    <?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?php echo esc($css); ?>">
    <?php endforeach; ?>
<?php elseif (isset($pageCss)): ?>
    <link rel="stylesheet" href="/assets/css/pages/<?php echo esc($pageCss); ?>">
<?php endif; ?>

<!-- Google Fonts (Montserrat + Plus Jakarta Sans + JetBrains Mono) -->
<link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap"
    rel="stylesheet"
    media="print"
    onload="this.media='all'"
>
<noscript>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
</noscript>

<!-- Structured Data (JSON-LD) -->
<?php
$localBusinessSchema = getLocalBusinessSchema();
$existingSchema = ($pageSchema ?? '') . $localBusinessSchema;
echo $pageSchema ?? '';
echo $localBusinessSchema;
echo getBaseSchemas($existingSchema, $seoTitle, $seoCanonical);
?>

<!-- Google Analytics / Tag Manager -->
<?php if ($site->analytics->googleAnalyticsId): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc($site->analytics->googleAnalyticsId); ?>"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?php echo esc($site->analytics->googleAnalyticsId); ?>');
</script>
<?php endif; ?>
