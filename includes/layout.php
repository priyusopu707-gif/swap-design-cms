<?php
/**
 * Swap Design - Master Frontend Layout
 *
 * Orchestrates the full page-rendering pipeline.
 * Every page routes through here:
 *
 *   Request URL  -->  loader.php (resolvePage)  -->  layout.php (renderLayout)
 *       |                       |                            |
 *   Find Page        Load Assigned Sections        Render Components
 *                                                    Output HTML
 *
 * Components rendered, in order:
 *   1. header.php        doctype, <html>, <head>, <body>, skip-link
 *   2. navigation.php    sticky header bar with logo, menu, CTA
 *   3. <main>            main content landmark
 *   4. breadcrumb.php    navigation trail (if crumbs present)
 *   5. page-header       <h1> heading banner (inner pages only)
 *   6. page template     pages/{template}.php
 *   7. </main>
 *   8. cta.php           call-to-action section (conditional)
 *   9. footer.php        site footer
 *  10. scripts.php       deferred JavaScript loader
 *  11. </body></html>
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

/**
 * Render the complete page layout.
 *
 * @param array $context Page context returned by resolvePage()
 */
function renderLayout(array $context): void
{
    global $site;

    /* ---- Set SEO global variables consumed by head.php ---- */
    global $pageTitle, $pageDescription, $pageCanonical;
    global $pageOgImage, $pageOgType, $pageRobots, $pageTwitterCard;
    global $pageNoIndex, $pageCss, $pageJs, $pageSchema;

    $meta = $context['meta'] ?? [];

    $pageTitle       = $meta['title']       ?? null;
    $pageDescription = $meta['description'] ?? null;
    $pageCanonical   = $meta['canonical']   ?? null;
    $pageOgImage     = $meta['ogImage']     ?? null;
    $pageOgType      = $meta['ogType']      ?? 'website';
    $pageTwitterCard = $meta['twitterCard'] ?? null;
    $pageRobots      = ($context['statusCode'] === 200) ? 'index, follow' : 'noindex, nofollow';
    $pageNoIndex     = ($context['statusCode'] !== 200);
    $pageCss         = $context['pageCss']  ?? null;
    $pageJs          = $context['pageJs']   ?? null;
    $pageSchema      = $context['schema']   ?? null;

    /* ---- Component variables ---- */
    $breadcrumbs = $context['crumbs'] ?? [];
    $showCta     = $context['showCta'] ?? true;
    $pageHeading = $meta['heading']   ?? null;

    /* ---- Start rendering ---- */
    require __DIR__ . '/header.php';
    require __DIR__ . '/components/navigation.php';
    ?>

    <main id="main-content" class="main-content" tabindex="-1">

        <?php /* Breadcrumb Trail (only inner pages with a trail) */ ?>
        <?php if (count($breadcrumbs) > 1): ?>
            <?php require __DIR__ . '/breadcrumb.php'; ?>
        <?php endif; ?>

        <?php /* Page Heading Banner (inner pages only, home handles its own hero) */ ?>
        <?php if ($pageHeading && ($context['template'] ?? '') !== 'home'): ?>
        <div class="page-header">
            <div class="container">
                <h1 class="page-header__title"><?php echo esc($pageHeading); ?></h1>
            </div>
        </div>
        <?php endif; ?>

        <?php
        /* ---- Page Content ---- */
        $templateName = $context['template'] ?? '404';
        $templateFile = __DIR__ . '/../pages/' . $templateName . '.php';

        if (file_exists($templateFile)):
            require $templateFile;
        else: ?>
        <div class="container u-text-center" style="padding: var(--space-4xl) 0;">
            <p>Page template &quot;<?php echo esc($templateName); ?>&quot; not found.</p>
            <p><a href="/" class="btn btn--primary btn--lg">Back to Home</a></p>
        </div>
        <?php endif; ?>

    </main>

    <?php /* Call To Action (skipped on 404, contact, and pages that opt out) */ ?>
    <?php if ($showCta && !in_array($context['template'] ?? '', ['404', 'contact'])): ?>
        <?php require __DIR__ . '/components/cta.php'; ?>
    <?php endif; ?>

    <?php
    /* ---- Footer ---- */
    require __DIR__ . '/components/footer.php';

    /* ---- Global Scripts (deferred, non-blocking) ---- */
    require __DIR__ . '/scripts.php';
    ?>

    <?php /* Global scroll progress bar (visible on all pages) */ ?>
    <div class="scroll-progress" aria-hidden="true"></div>

    </body>
    </html>
    <?php
}
