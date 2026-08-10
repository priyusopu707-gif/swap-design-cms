<?php
/**
 * Swap Design - Global Scripts Loader
 *
 * Centralized script loading with defer for non-blocking performance.
 * Load order is significant:
 *   1. helpers.js     (DOM utilities, must load first)
 *   2. header-nav.js   (navigation behavior)
 *   3. animations.js   (scroll-based reveal animations)
 *   4. main.js         (global init: smooth scroll, layout helpers)
 *   5. pageJs          (page-specific scripts, conditionally loaded)
 *
 * Required: $site (global config), $pageJs (optional)
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

global $site;
?>

<?php if ($site->features->darkMode ?? false): ?>
<script>
    (function() {
        var stored = localStorage.getItem('swap-design-theme');
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (stored === 'dark' || (!stored && prefersDark)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    })();
</script>
<?php endif; ?>

<!-- Global Scripts (deferred for Core Web Vitals) -->
<script src="/assets/js/utils/helpers.js" defer></script>
<script src="/assets/js/components/header-nav.js" defer></script>
<script src="/assets/js/components/search.js" defer></script>
<script src="/assets/js/components/animations.js" defer></script>
<script src="/assets/js/main.js" defer></script>

<!-- Swap Design System Animations & Interactions -->
<script src="/assets/js/components/gsap-loader.js" defer></script>
<script src="/assets/js/components/reveal.js" defer></script>
<script src="/assets/js/components/tilt.js" defer></script>
<script src="/assets/js/components/magnetic.js" defer></script>
<script src="/assets/js/components/counter.js" defer></script>
<script src="/assets/js/components/parallax.js" defer></script>
<script src="/assets/js/components/accordion.js" defer></script>
<script src="/assets/js/components/tabs.js" defer></script>
<script src="/assets/js/components/modal.js" defer></script>
<script src="/assets/js/components/drawer.js" defer></script>
<script src="/assets/js/components/toast.js" defer></script>
<script src="/assets/js/components/back-to-top.js" defer></script>
<script src="/assets/js/components/scroll-progress.js" defer></script>

<?php if (isset($pageJs) && is_array($pageJs)): ?>
    <?php foreach ($pageJs as $js): ?>
    <script src="<?php echo esc($js); ?>" defer></script>
    <?php endforeach; ?>
<?php elseif (isset($pageJs)): ?>
    <script src="/assets/js/pages/<?php echo esc($pageJs); ?>" defer></script>
<?php endif; ?>
