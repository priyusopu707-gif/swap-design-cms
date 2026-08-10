<?php
/**
 * Swap Design - Sticky Header & Navigation Bar
 *
 * Single unified component containing:
 *   Logo (left) | Nav Menu + Services Dropdown (center) | CTA + Mobile Toggle (right)
 *
 * Reads navigation from database via NavigationManager.
 * Falls back to hardcoded $site->navigation config if DB is empty.
 *
 * Requires: $site (global site config), $currentPage (set by router)
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

global $site;
$currentPage = $currentPage ?? 'home';

/* Try DB-driven navigation first, fall back to site config */
$fromDb = false;
try {
    $navManager = new NavigationManager();
    $navItems = $navManager->getMenuTree('primary');
    if (!empty($navItems)) {
        $fromDb = true;
    }
} catch (\Exception $e) {
    $navItems = [];
}

if (!$fromDb) {
    $navItems = $site->navigation['primary'];
}

function renderNavItem(array $item, string $currentPage, bool $fromDb): void
{
    $label   = $item['label'] ?? '';
    $url     = $item['url'] ?? '#';
    $slug    = $item['slug'] ?? '';
    $children = $item['children'] ?? [];
    $isVisible = $fromDb ? (bool)($item['is_visible'] ?? true) : true;
    $openNewTab = $fromDb ? (bool)($item['open_new_tab'] ?? false) : false;

    if (!$isVisible) return;

    $target = $openNewTab ? ' target="_blank" rel="noopener"' : '';

    if (!empty($children)):
        $visibleChildren = array_filter($children, fn($c) => $fromDb ? (bool)($c['is_visible'] ?? true) : true);
        if (empty($visibleChildren)) return;
?>
                        <li class="main-nav__item main-nav__item--has-dropdown">
                            <button class="main-nav__link main-nav__dropdown-toggle"
                                    aria-expanded="false"
                                    aria-haspopup="true"
                                    aria-controls="dropdown-<?php echo esc($slug); ?>"
                                    <?php echo ($currentPage === $slug) ? ' aria-current="page"' : ''; ?>>
                                <?php echo esc($label); ?>
                                <svg class="main-nav__chevron" aria-hidden="true" width="12" height="8" viewBox="0 0 12 8" fill="none">
                                    <path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul id="dropdown-<?php echo esc($slug); ?>"
                                class="main-nav__dropdown"
                                role="menu"
                                aria-label="<?php echo esc($label); ?> submenu">
                                <?php foreach ($visibleChildren as $child): ?>
                                    <?php
                                    $childTarget = ($fromDb && !empty($child['open_new_tab'])) ? ' target="_blank" rel="noopener"' : '';
                                    ?>
                                <li class="main-nav__dropdown-item" role="none">
                                    <a href="<?php echo esc($child['url']); ?>"
                                       class="main-nav__dropdown-link"
                                       role="menuitem"
                                       <?php echo $childTarget; ?>>
                                        <?php echo esc($child['label']); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
<?php else: ?>
                        <li class="main-nav__item">
                            <a href="<?php echo esc($url); ?>"
                               class="main-nav__link<?php echo ($currentPage === $slug) ? ' main-nav__link--active' : ''; ?>"
                               <?php echo ($currentPage === $slug) ? ' aria-current="page"' : ''; ?>
                               <?php echo $target; ?>>
                                <?php echo esc($label); ?>
                            </a>
                        </li>
<?php endif;
}
?>

<header class="main-header" role="banner" id="main-header">
    <div class="main-header__inner container">

        <!-- Logo (Left) -->
        <a href="/" class="main-header__logo" aria-label="<?php echo esc($site->brand->name); ?> - Home">
            <span class="main-header__logo-text"><?php echo esc($site->brand->name); ?></span>
        </a>

        <!-- Navigation Menu (Center) -->
        <nav class="main-nav" role="navigation" aria-label="Main navigation">
            <ul id="main-menu" class="main-nav__list">
                <?php foreach ($navItems as $item): ?>
                    <?php renderNavItem($item, $currentPage, $fromDb); ?>
                <?php endforeach; ?>
            </ul>
        </nav>

        <!-- Right Side: CTA Button + Search + Mobile Toggle -->
        <div class="main-header__actions">
            <?php
            /* Search trigger buttons (desktop expandable + mobile overlay).
               Global component CSS/JS are loaded in head.php / scripts.php. */
            ?>
            <button class="main-header__search-toggle"
                    type="button"
                    aria-label="Open site search"
                    aria-expanded="false"
                    aria-controls="site-search">
                <svg class="main-header__search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>

            <a href="/contact" class="btn btn--cta main-header__cta">
                Let's Talk
            </a>

            <button class="main-header__toggle"
                    aria-expanded="false"
                    aria-controls="main-menu"
                    aria-label="Toggle navigation menu">
                <span class="main-header__toggle-bar"></span>
                <span class="main-header__toggle-bar"></span>
                <span class="main-header__toggle-bar"></span>
            </button>
        </div>

    </div>
</header>

<?php /* Site search component: desktop expandable panel + mobile full-screen overlay */ ?>
<div class="site-search" id="site-search" data-csrf="<?php echo esc(csrfToken()); ?>" hidden>
    <div class="site-search__panel" role="dialog" aria-modal="false" aria-label="Site search">
        <div class="site-search__bar">
            <svg class="site-search__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input type="search"
                   class="site-search__input"
                   id="site-search-input"
                   placeholder="Search services, portfolio, blog..."
                   autocomplete="off"
                   aria-label="Search the site">
            <button class="site-search__clear" type="button" aria-label="Clear search" hidden>&times;</button>
            <button class="site-search__close" type="button" aria-label="Close search">&times;</button>
        </div>

        <div class="site-search__body">
            <div class="site-search__empty">
                <p class="site-search__hint">Type to search. Try &ldquo;branding&rdquo;, &ldquo;portfolio&rdquo;, or &ldquo;UI/UX&rdquo;.</p>
            </div>

            <div class="site-search__popular" hidden>
                <p class="site-search__section-title">Popular Searches</p>
                <ul class="site-search__popular-list"></ul>
            </div>

            <div class="site-search__results" hidden>
                <ul class="site-search__results-list"></ul>
            </div>

            <div class="site-search__footer" hidden>
                <a class="site-search__all" href="/search">View all results</a>
            </div>
        </div>
    </div>
</div>
