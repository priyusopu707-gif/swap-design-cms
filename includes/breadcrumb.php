<?php
/**
 * Swap Design - Breadcrumb Component
 *
 * Renders a dynamic breadcrumb navigation trail.
 * Expects $breadcrumbs array:
 *   [['name' => 'Home', 'url' => '/'], ['name' => 'About', 'url' => '/about']]
 *
 * The last crumb is rendered as plain text (current page).
 * Also outputs JSON-LD BreadcrumbList structured data via getBreadcrumbSchema().
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/* Support both the explicit $breadcrumbs variable and the
   $pageBreadcrumb global set by RenderingEngine. */
global $pageBreadcrumb;
if ((empty($breadcrumbs) || !is_array($breadcrumbs)) && !empty($pageBreadcrumb) && is_array($pageBreadcrumb)) {
    $breadcrumbs = $pageBreadcrumb;
}

if (empty($breadcrumbs) || !is_array($breadcrumbs)):
    return;
endif;

/* Normalize label/name keys so both RenderingEngine and legacy callers work. */
$normalized = [];
foreach ($breadcrumbs as $crumb) {
    $normalized[] = [
        'name' => $crumb['name'] ?? $crumb['label'] ?? 'Home',
        'url'  => $crumb['url'] ?? null,
    ];
}
$breadcrumbs = $normalized;

$total = count($breadcrumbs);
?>
<nav class="breadcrumbs" aria-label="<?php echo esc('Breadcrumb'); ?>">
    <div class="container">
        <ol class="breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <?php $position = $i + 1; ?>
                <li class="breadcrumbs__item"
                    itemprop="itemListElement"
                    itemscope
                    itemtype="https://schema.org/ListItem">
                    <?php if ($i < $total - 1 && !empty($crumb['url'])): ?>
                        <a href="<?php echo esc($crumb['url']); ?>"
                           class="breadcrumbs__link"
                           itemprop="item">
                            <span itemprop="name"><?php echo esc($crumb['name']); ?></span>
                        </a>
                        <span class="breadcrumbs__separator" aria-hidden="true">/</span>
                    <?php else: ?>
                        <span class="breadcrumbs__current"
                              aria-current="page"
                              itemprop="name">
                            <?php echo esc($crumb['name']); ?>
                        </span>
                    <?php endif; ?>
                    <meta itemprop="position" content="<?php echo $position; ?>">
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>

<?php if (function_exists('getBreadcrumbSchema')): ?>
    <?php echo getBreadcrumbSchema($breadcrumbs); ?>
<?php endif; ?>
