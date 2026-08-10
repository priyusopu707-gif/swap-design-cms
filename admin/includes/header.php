<?php
/**
 * Swap Design - Admin Header Component
 *
 * Opens the admin HTML document: doctype, <head>, <body>,
 * then renders the sidebar and topbar.
 *
 * Also handles flash messages from Session.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

global $site;

$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="<?php echo esc($site->brand->language ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc(($pageTitle ?? 'Dashboard') . ' | Admin — ' . $site->brand->name); ?></title>

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Admin Stylesheets -->
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <link rel="stylesheet" href="/admin/assets/css/admin-premium.css">
    <?php if (!empty($adminPageCss)): ?>
    <link rel="stylesheet" href="<?php echo esc($adminPageCss); ?>">
    <?php endif; ?>

    <!-- Google Fonts (Montserrat + Plus Jakarta Sans — design system typography) -->
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet"
        media="print"
        onload="this.media='all'"
    >
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    </noscript>

    <?php /* CSRF token for JS to read */ ?>
    <meta name="csrf-token" content="<?php echo esc(csrfToken()); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon/favicon-16.png">
</head>
<body class="admin-body">

    <a href="#admin-content" class="admin-skip-link">Skip to main content</a>

    <div class="admin-layout">
        <?php /* Sidebar */ ?>
        <?php require __DIR__ . '/sidebar.php'; ?>

        <div class="admin-main">
            <?php /* Topbar */ ?>
            <?php require __DIR__ . '/topbar.php'; ?>

            <main class="admin-content" id="admin-content">

                <?php /* Flash Messages */ ?>
                <?php if (Session::hasFlash('success')): ?>
                <div class="admin-flash admin-flash--success" role="alert">
                    <?php echo esc(Session::getFlash('success')); ?>
                    <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
                </div>
                <?php endif; ?>

                <?php if (Session::hasFlash('error')): ?>
                <div class="admin-flash admin-flash--error" role="alert">
                    <?php echo esc(Session::getFlash('error')); ?>
                    <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
                </div>
                <?php endif; ?>

                <?php if (Session::hasFlash('warning')): ?>
                <div class="admin-flash admin-flash--warning" role="alert">
                    <?php echo esc(Session::getFlash('warning')); ?>
                    <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
                </div>
                <?php endif; ?>

                <?php if (Session::hasFlash('info')): ?>
                <div class="admin-flash admin-flash--info" role="alert">
                    <?php echo esc(Session::getFlash('info')); ?>
                    <button class="admin-flash__close" aria-label="Dismiss">&times;</button>
                </div>
                <?php endif; ?>
