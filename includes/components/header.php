<?php
/**
 * Swap Design - Site Header Component
 *
 * Lightweight doctype and <head> wrapper.
 * The sticky header/navigation bar is handled by navigation.php.
 *
 * Requires: $site (global site config), SEO variables
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

global $site;
?>
<!DOCTYPE html>
<html lang="<?php echo esc($site->brand->language); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary Meta Tags -->
    <title><?php echo esc($pageTitle ?? $site->seo->defaultTitle); ?></title>
    <meta name="description" content="<?php echo esc($pageDescription ?? $site->seo->defaultDescription); ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="<?php echo esc($site->brand->name); ?>">
    <link rel="canonical" href="<?php echo esc($pageCanonical ?? $site->urls->base); ?>">

    <?php if ($site->seo->googleSiteVerification): ?>
        <meta name="google-site-verification" content="<?php echo esc($site->seo->googleSiteVerification); ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type" content="<?php echo $pageOgType ?? $site->seo->defaultOgType; ?>">
    <meta property="og:url" content="<?php echo esc($pageCanonical ?? $site->urls->base); ?>">
    <meta property="og:title" content="<?php echo esc($pageTitle ?? $site->seo->defaultTitle); ?>">
    <meta property="og:description" content="<?php echo esc($pageDescription ?? $site->seo->defaultDescription); ?>">
    <meta property="og:image" content="<?php echo esc($pageOgImage ?? $site->seo->defaultOgImage); ?>">
    <meta property="og:site_name" content="<?php echo esc($site->brand->name); ?>">
    <meta property="og:locale" content="<?php echo esc($site->brand->language) . '_US'; ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc($pageTitle ?? $site->seo->defaultTitle); ?>">
    <meta name="twitter:description" content="<?php echo esc($pageDescription ?? $site->seo->defaultDescription); ?>">
    <meta name="twitter:image" content="<?php echo esc($pageOgImage ?? $site->seo->defaultOgImage); ?>">
    <?php if ($site->seo->twitterHandle): ?>
        <meta name="twitter:site" content="<?php echo esc($site->seo->twitterHandle); ?>">
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $site->favicon->favicon32; ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $site->favicon->favicon16; ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $site->favicon->appleIcon; ?>">
    <meta name="theme-color" content="<?php echo $site->favicon->themeColor; ?>">
    <meta name="msapplication-TileColor" content="<?php echo $site->favicon->themeColor; ?>">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo $site->favicon->manifest; ?>">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="/assets/css/components/header-nav.css">
    <?php if (isset($pageCss)): ?>
        <link rel="stylesheet" href="/assets/css/pages/<?php echo esc($pageCss); ?>">
    <?php endif; ?>

    <!-- Google Analytics (placeholder) -->
    <?php if ($site->analytics->googleAnalyticsId): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc($site->analytics->googleAnalyticsId); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo esc($site->analytics->googleAnalyticsId); ?>');
    </script>
    <?php endif; ?>
</head>
<body>

    <!-- Skip to main content (accessibility) -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
