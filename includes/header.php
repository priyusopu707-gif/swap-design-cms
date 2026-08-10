<?php
/**
 * Swap Design - Header Component
 *
 * Opens the HTML document: doctype, <html>, <head> (via head.php),
 * <body>, and the accessibility skip-link.
 *
 * The sticky navigation bar is handled separately by
 * includes/components/navigation.php to keep concerns separated.
 *
 * Required: $site (global site config), head.php
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

global $site;
?>
<!DOCTYPE html>
<html lang="<?php echo esc($site->brand->language ?? 'en'); ?>">
<head>
    <?php require __DIR__ . '/head.php'; ?>
</head>
<body>

    <!-- Accessibility: Skip to main content -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
