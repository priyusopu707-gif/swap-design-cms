<?php
/**
 * Swap Design - Admin Sidebar Component
 *
 * Updated for Sprint 3: Content types, entries, sections, layouts.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

$currentSection = $currentSection ?? 'dashboard';
?>

<aside class="admin-sidebar" id="admin-sidebar" role="navigation" aria-label="Admin navigation">
    <div class="admin-sidebar__brand">
        <a href="/" target="_blank" class="admin-sidebar__logo" title="View Site">
            <span class="admin-sidebar__logo-icon">S</span>
            <span class="admin-sidebar__logo-text">Swap Design</span>
        </a>
    </div>

    <nav class="admin-sidebar__nav">
        <ul class="admin-sidebar__menu">
            <?php
            $menuItems = [
                ['id' => 'dashboard',     'label' => 'Dashboard',      'icon' => 'dashboard',    'url' => '/admin/index.php'],
                ['id' => 'homepage',      'label' => 'Homepage',       'icon' => 'homepage',     'url' => '/admin/homepage.php'],
                ['id' => 'about',         'label' => 'About Page',     'icon' => 'about',        'url' => '/admin/about.php'],
                ['id' => 'contacts',      'label' => 'Contact Page',   'icon' => 'contacts',     'url' => '/admin/contacts.php'],
                ['id' => 'leads',         'label' => 'Leads',          'icon' => 'leads',        'url' => '/admin/leads.php'],
                ['id' => 'email-settings','label' => 'Email Settings', 'icon' => 'email',        'url' => '/admin/email-settings.php'],
                ['id' => 'email-templates','label' => 'Email Templates','icon' => 'template',    'url' => '/admin/email-templates.php'],
                ['id' => 'blog',          'label' => 'Blog',           'icon' => 'blog',         'url' => '/admin/blog.php'],
                ['id' => 'search',        'label' => 'Search Dashboard','icon' => 'search',       'url' => '/admin/search.php'],
                ['id' => 'separator-1',   'label' => '-- Content --',  'icon' => '',             'url' => '', 'sep' => true],
                ['id' => 'pages',         'label' => 'Pages',          'icon' => 'pages',        'url' => '/admin/pages.php'],
                ['id' => 'services',      'label' => 'Services',       'icon' => 'services',     'url' => '/admin/services.php'],
                ['id' => 'portfolio',     'label' => 'Portfolio',      'icon' => 'portfolio',    'url' => '/admin/portfolio.php'],
                ['id' => 'content-types', 'label' => 'Content Types',   'icon' => 'types',        'url' => '/admin/content-types.php'],
                ['id' => 'entries',       'label' => 'Entries',        'icon' => 'entries',      'url' => '/admin/entries.php'],
                ['id' => 'sections',      'label' => 'Sections',       'icon' => 'sections',     'url' => '/admin/sections.php'],
                ['id' => 'layouts',       'label' => 'Layouts',        'icon' => 'layout',       'url' => '/admin/layouts.php'],
                ['id' => 'separator-2',   'label' => '-- Media & Design --', 'icon' => '',      'url' => '', 'sep' => true],
                ['id' => 'media',         'label' => 'Media Library',  'icon' => 'media',        'url' => '/admin/media.php'],
                ['id' => 'blocks',        'label' => 'Global Blocks',  'icon' => 'blocks',       'url' => '/admin/blocks.php'],
                ['id' => 'navigation',    'label' => 'Navigation',     'icon' => 'navigation',   'url' => '/admin/navigation.php'],
                ['id' => 'footer',        'label' => 'Footer',         'icon' => 'footer',       'url' => '/admin/footer.php'],
                ['id' => 'separator-3',   'label' => '-- Settings --', 'icon' => '',             'url' => '', 'sep' => true],
                ['id' => 'theme',         'label' => 'Theme Settings', 'icon' => 'theme',        'url' => '/admin/theme.php'],
                ['id' => 'settings',      'label' => 'Site Settings',  'icon' => 'settings',     'url' => '/admin/settings.php'],
                ['id' => 'whatsapp',      'label' => 'WhatsApp',        'icon' => 'whatsapp',     'url' => '/admin/whatsapp.php'],
                ['id' => 'users',         'label' => 'Users',          'icon' => 'users',        'url' => '/admin/users.php'],
            ];

            foreach ($menuItems as $item):
                if (!empty($item['sep'])): ?>
                    <li class="admin-sidebar__separator"><?php echo esc($item['label']); ?></li>
                <?php else:
                    $isActive = ($currentSection === $item['id']);
                ?>
                    <li class="admin-sidebar__item">
                        <a href="<?php echo esc($item['url']); ?>"
                           class="admin-sidebar__link<?php echo $isActive ? ' admin-sidebar__link--active' : ''; ?>"
                           <?php echo $isActive ? ' aria-current="page"' : ''; ?>>
                            <span class="admin-sidebar__icon admin-sidebar__icon--<?php echo esc($item['icon']); ?>" aria-hidden="true"></span>
                            <span class="admin-sidebar__label"><?php echo esc($item['label']); ?></span>
                        </a>
                    </li>
                <?php endif;
            endforeach; ?>

            <li class="admin-sidebar__item admin-sidebar__item--external">
                <a href="/" class="admin-sidebar__link" target="_blank" rel="noopener">
                    <span class="admin-sidebar__icon admin-sidebar__icon--view" aria-hidden="true"></span>
                    <span class="admin-sidebar__label">View Site</span>
                    <svg class="admin-sidebar__external-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                        <path d="M1 11L11 1M11 1H4M11 1V8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </li>
        </ul>
    </nav>

    <div class="admin-sidebar__footer">
        <p class="admin-sidebar__version">CMS v1.0</p>
    </div>
</aside>
