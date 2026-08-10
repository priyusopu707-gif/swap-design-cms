<?php
/**
 * Swap Design - Admin Head Partial
 *
 * Renders the shared <head> content (meta, title, stylesheets,
 * CSRF meta) for admin pages that build their own document shell.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

global $site;
?>
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
