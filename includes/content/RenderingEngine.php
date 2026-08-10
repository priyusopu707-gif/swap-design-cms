<?php
/**
 * Swap Design - Rendering Engine
 *
 * The core rendering pipeline: takes a resolved page context,
 * loads the assigned layout, fetches active sections per zone,
 * renders each section's content, and outputs final HTML.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class RenderingEngine
{
    private SectionManager $sectionManager;
    private BlockEngine $blockEngine;
    private ComponentLoader $componentLoader;
    private ContentEntryManager $entryManager;
    private WhatsAppManager $whatsapp;
    private ?HomepageRenderer $homepageRenderer = null;
    private ?array $homepageAssets = null;

    public function __construct()
    {
        $this->sectionManager  = new SectionManager();
        $this->blockEngine     = new BlockEngine();
        $this->componentLoader = new ComponentLoader();
        $this->entryManager    = new ContentEntryManager();
        $this->whatsapp        = new WhatsAppManager();
    }

    /**
     * Render a full page from a resolved context.
     *
     * @param array $context Resolved page context from DynamicRouter::resolve()
     * @return string        Complete HTML output
     */
    public function render(array $context): string
    {
        if ($context['status'] === 301) {
            header('Location: ' . $context['target_url'], true, 301);
            exit;
        }

        if ($context['status'] === 404) {
            $html = $this->render404();
        } elseif ($context['type'] === 'empty') {
            $html = $this->renderEmpty();
        } elseif ($context['type'] === 'page') {
            $html = $this->renderPage($context);
        } elseif ($context['type'] === 'service') {
            $html = $this->renderServicePage($context);
        } elseif ($context['type'] === 'portfolio_archive') {
            $html = $this->renderPortfolioArchive($context);
        } elseif ($context['type'] === 'services_archive') {
            $html = $this->renderServicesArchive($context);
        } elseif ($context['type'] === 'portfolio_single') {
            $html = $this->renderPortfolioSingle($context);
        } elseif ($context['type'] === 'archive' || $context['type'] === 'entry') {
            $html = $this->renderContentType($context);
        } elseif ($context['type'] === 'about') {
            $html = $this->renderAboutPage($context);
        } elseif ($context['type'] === 'contact') {
            $html = $this->renderContactPage($context);
        } elseif ($context['type'] === 'blog_listing') {
            $html = $this->renderBlogListing($context);
        } elseif ($context['type'] === 'blog_post') {
            $html = $this->renderBlogPost($context);
        } elseif ($context['type'] === 'blog_category') {
            $html = $this->renderBlogCategory($context);
        } elseif ($context['type'] === 'blog_tag') {
            $html = $this->renderBlogTag($context);
        } elseif ($context['type'] === 'search') {
            $html = $this->renderSearch($context);
        } else {
            $html = $this->render404();
        }

        /* Inject WhatsApp floating button before </body> */
        $pageId = $context['page']['id'] ?? null;
        $whatsappHtml = $this->whatsapp->renderFloatingButton($pageId);

        if ($whatsappHtml) {
            $html = str_replace('</body>', $whatsappHtml . '</body>', $html);
        }

        return $html;
    }

    /**
     * Render a page with its layout and sections.
     */
    private function renderPage(array $context): string
    {
        $page   = $context['page'];
        $layout = $context['layout'];
        $isHome = $context['is_home'] ?? false;

        /* Set global SEO variables for the layout system */
        $this->setGlobalMeta($context['meta']);

        /* Homepage: use HomepageRenderer */
        if ($isHome) {
            return $this->renderHomepage($page);
        }

        /* If no layout, render page content directly */
        if (!$layout) {
            global $pageSchema, $pageTitle, $pageCanonical, $pageBreadcrumb;
            $pageSchema = getWebPageSchema($pageTitle, $pageCanonical);
            $pageBreadcrumb = [
                ['label' => 'Home', 'url' => '/'],
                ['label' => $page['title']],
            ];
            return $this->renderLegacyPage($page);
        }

        /* Load page sections grouped by zone */
        $pageSections = $this->sectionManager->getPageSections((int)$page['id']);
        $zoneContents = $this->groupSectionsByZone($pageSections);

        /* Set WebPage schema for the layout-based page */
        global $pageSchema, $pageTitle, $pageCanonical;
        $pageSchema = getWebPageSchema($pageTitle, $pageCanonical);

        /* Render each section, producing HTML per zone */
        $zoneHtml = [];
        foreach ($zoneContents as $zoneKey => $sections) {
            $zoneHtml[$zoneKey] = $this->renderZoneSections($zoneKey, $sections);
        }

        /* Build the layout: header -> zones -> footer */
        return $this->assembleLayout($layout, $zoneHtml, $context);
    }

    /**
     * Group page sections by zone key, applying zone-level overrides.
     */
    private function groupSectionsByZone(array $pageSections): array
    {
        $zones = [];
        foreach ($pageSections as $ps) {
            $zoneKey = $ps['zone_key'] ?? 'content';

            /* Decode section config */
            $ps['config'] = json_decode($ps['config'] ?? '{}', true) ?: [];
            if (!empty($ps['custom_config'])) {
                $custom = json_decode($ps['custom_config'], true) ?: [];
                $ps['config'] = array_merge($ps['config'], $custom);
            }

            $zones[$zoneKey][] = $ps;
        }
        return $zones;
    }

    /**
     * Render all sections for a single zone.
     */
    private function renderZoneSections(string $zoneKey, array $sections): string
    {
        $html = '';

        foreach ($sections as $section) {
            $html .= $this->renderSection($section);
        }

        return $html;
    }

    /**
     * Render a single section based on its type.
     */
    private function renderSection(array $section): string
    {
        $type   = $section['section_type'] ?? 'custom_html';
        $config = $section['config'] ?? [];

        switch ($type) {
            case 'custom_html':
                return $config['html'] ?? '';

            case 'global_block':
                $blockId = $config['block_id'] ?? 0;
                if ($blockId > 0) {
                    $block = $this->blockEngine->getBlock((int)$blockId);
                    if ($block && $block['status'] === 'published') {
                        return $this->componentLoader->renderBlock($block, $config);
                    }
                }
                return '';

            case 'component':
                $componentName = $config['component_name'] ?? '';
                if ($componentName) {
                    return $this->componentLoader->render($componentName, $config);
                }
                return '';

            case 'content_entries':
                return $this->renderContentEntriesSection($config);

            case 'dynamic_list':
                return $this->renderDynamicListSection($config);

            case 'shortcode':
                return $this->renderShortcode($config);

            default:
                return '';
        }
    }

    /**
     * Render a section that lists content entries.
     */
    private function renderContentEntriesSection(array $config): string
    {
        $typeSlug    = $config['content_type_slug'] ?? '';
        $limit       = (int)($config['limit'] ?? 6);
        $displayType = $config['display'] ?? 'grid';
        $status      = $config['status'] ?? 'published';

        if (!$typeSlug) return '';

        $entries = $this->entryManager->getEntries([
            'type_slug' => $typeSlug,
            'status'    => $status,
            'limit'     => $limit,
        ]);

        if (empty($entries)) return '';

        ob_start();
        ?>
        <section class="content-entries content-entries--<?php echo esc($typeSlug); ?> content-entries--<?php echo esc($displayType); ?>">
            <div class="content-entries__grid">
                <?php foreach ($entries as $entry): ?>
                <article class="content-entry-card">
                    <?php if (!empty($entry['featured_image'])): ?>
                    <div class="content-entry-card__image">
                        <img src="<?php echo esc($entry['featured_image']); ?>"
                             alt="<?php echo esc($entry['title']); ?>"
                             loading="lazy"
                             width="400" height="300">
                    </div>
                    <?php endif; ?>
                    <div class="content-entry-card__body">
                        <h3 class="content-entry-card__title">
                            <a href="/<?php echo esc($typeSlug); ?>/<?php echo esc($entry['slug']); ?>">
                                <?php echo esc($entry['title']); ?>
                            </a>
                        </h3>
                        <?php if (!empty($entry['excerpt'])): ?>
                        <p class="content-entry-card__excerpt"><?php echo esc($entry['excerpt']); ?></p>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a dynamic list (custom query-based listing).
     */
    private function renderDynamicListSection(array $config): string
    {
        $queryType = $config['query_type'] ?? 'latest_pages';
        $limit     = (int)($config['limit'] ?? 5);
        $template  = $config['template'] ?? 'card';

        $items = [];
        switch ($queryType) {
            case 'latest_pages':
                $items = $this->db()->fetchAll(
                    "SELECT * FROM pages WHERE status = 'published' ORDER BY created_at DESC LIMIT ?",
                    [$limit]
                );
                break;
            case 'featured_pages':
                $items = $this->db()->fetchAll(
                    "SELECT * FROM pages WHERE status = 'published' ORDER BY updated_at DESC LIMIT ?",
                    [$limit]
                );
                break;
            case 'latest_entries':
                $items = $this->entryManager->getEntries(['status' => 'published', 'limit' => $limit]);
                break;
        }

        if (empty($items)) return '';

        ob_start();
        echo '<div class="dynamic-list dynamic-list--' . esc($template) . '">';
        foreach ($items as $item) {
            echo '<div class="dynamic-list__item">';
            echo '<a href="/' . esc($item['slug']) . '">' . esc($item['title']) . '</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Render a shortcode placeholder.
     */
    private function renderShortcode(array $config): string
    {
        $code = $config['code'] ?? '';
        return "<!-- shortcode: {$code} -->";
    }

    /**
     * Render homepage using HomepageRenderer.
     */
    private function renderHomepage(array $page): string
    {
        if ($this->homepageRenderer === null) {
            $this->homepageRenderer = new HomepageRenderer();
        }

        $assets = $this->homepageRenderer->getPageAssets();
        $this->homepageAssets = $assets;

        global $pageCss, $pageJs, $pageSchema;
        $pageCss    = $assets['css'];
        $pageJs     = $assets['js'];
        $pageSchema = $this->homepageRenderer->getSchema();

        $homepageHtml = $this->homepageRenderer->render();

        $root = ROOT_PATH;
        ob_start();
        require $root . '/includes/header.php';
        require $root . '/includes/components/navigation.php';
        echo '<main class="main-content" id="main-content">';
        echo $homepageHtml;
        echo '</main>';
        require $root . '/includes/components/footer.php';
        echo '<div class="scroll-progress" aria-hidden="true"></div>';
        require $root . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Render a service detail page using ServiceRenderer.
     */
    private function renderServicePage(array $context): string
    {
        $service = $context['service'] ?? null;
        if (!$service) return $this->render404();

        $serviceRenderer = new ServiceRenderer();
        $serviceHtml = $serviceRenderer->renderFromData($service);

        if (!$serviceHtml) return $this->render404();

        /* Set SEO globals */
        $this->setGlobalMeta($context['meta']);
        global $pageCss, $pageJs, $pageSchema;
        $assets = $serviceRenderer->getPageAssets();
        $pageCss    = $assets['css'];
        $pageJs     = $assets['js'];
        $pageSchema = $serviceRenderer->getSchema($service);

        /* Breadcrumb */
        global $pageBreadcrumb;
        $pageBreadcrumb = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Services', 'url' => '/services'],
            ['label' => $service['title']],
        ];

        $root = ROOT_PATH;
        ob_start();
        require $root . '/includes/head.php';
        require $root . '/includes/header.php';
        require $root . '/includes/components/navigation.php';
        require $root . '/includes/breadcrumb.php';
        echo $serviceHtml;
        require $root . '/includes/components/footer.php';
        require $root . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Get assets registered by the homepage renderer (used by index.php).
     */
    public function getHomepageAssets(): ?array
    {
        return $this->homepageAssets;
    }

    /**
     * Render the portfolio listing page.
     */
    private function renderPortfolioArchive(array $context): string
    {
        $this->setGlobalMeta($context['meta']);

        $category = $_GET['category'] ?? '';
        $search   = $_GET['search'] ?? '';
        $page     = max(1, (int)($_GET['page'] ?? 1));

        $portfolioRenderer = new PortfolioRenderer();
        $assets = $portfolioRenderer->getPageAssets();

        global $pageCss, $pageJs, $pageSchema, $pageTitle, $pageCanonical;
        $pageCss = $assets['css'];
        $pageJs  = $assets['js'];
        $pageSchema = getCollectionPageSchema($pageTitle, $pageCanonical);

        $portfolioHtml = $portfolioRenderer->renderListing($category, $search, $page);

        global $pageBreadcrumb;
        $pageBreadcrumb = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Portfolio'],
        ];

        ob_start();
        $root = ROOT_PATH;
        require $root . '/includes/head.php';
        require $root . '/includes/header.php';
        require $root . '/includes/components/navigation.php';
        require $root . '/includes/breadcrumb.php';
        echo $portfolioHtml;
        require $root . '/includes/components/footer.php';
        require $root . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Render the services listing page.
     */
    private function renderServicesArchive(array $context): string
    {
        $this->setGlobalMeta($context['meta']);

        $serviceRenderer = new ServiceRenderer();
        $assets = $serviceRenderer->getPageAssets();

        global $pageCss, $pageJs, $pageSchema, $pageTitle, $pageCanonical;
        $pageCss = $assets['css'];
        $pageJs  = $assets['js'];
        $pageSchema = getCollectionPageSchema($pageTitle, $pageCanonical);

        $servicesHtml = $serviceRenderer->renderListing();

        /* Breadcrumb */
        global $pageBreadcrumb;
        $pageBreadcrumb = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Services'],
        ];

        ob_start();
        $root = ROOT_PATH;
        require $root . '/includes/head.php';
        require $root . '/includes/header.php';
        require $root . '/includes/components/navigation.php';
        require $root . '/includes/breadcrumb.php';
        echo $servicesHtml;
        require $root . '/includes/components/footer.php';
        require $root . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Render a single portfolio project page.
     */
    private function renderPortfolioSingle(array $context): string
    {
        $portfolio = $context['portfolio'] ?? null;
        if (!$portfolio) return $this->render404();

        $this->setGlobalMeta($context['meta']);

        $portfolioRenderer = new PortfolioRenderer();
        $assets = $portfolioRenderer->getPageAssets();

        global $pageCss, $pageJs, $pageSchema;
        $pageCss    = $assets['css'];
        $pageJs     = $assets['js'];
        $pageSchema = $portfolioRenderer->getSchema($portfolio);

        $portfolioHtml = $portfolioRenderer->renderSingle($portfolio['slug']);

        if (!$portfolioHtml) return $this->render404();

        /* Breadcrumb */
        global $pageBreadcrumb;
        $pageBreadcrumb = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Portfolio', 'url' => '/portfolio'],
            ['label' => $portfolio['title']],
        ];

        ob_start();
        $root = ROOT_PATH;
        require $root . '/includes/head.php';
        require $root . '/includes/header.php';
        require $root . '/includes/components/navigation.php';
        require $root . '/includes/breadcrumb.php';
        echo $portfolioHtml;
        require $root . '/includes/components/footer.php';
        require $root . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Legacy rendering: page content directly (no layout/sections).
     */
    private function renderLegacyPage(array $page): string
    {
        $content = $page['content'] ?? '';

        ob_start();
        ?>
        <main class="main-content" id="main-content">
            <?php
            global $pageBreadcrumb;
            if (!empty($pageBreadcrumb)) {
                require ROOT_PATH . '/includes/breadcrumb.php';
            }
            ?>
            <div class="container">
                <div class="page-content">
                    <?php echo $content; ?>
                </div>
            </div>
        </main>
        <?php
        return ob_get_clean();
    }

    /**
     * Assemble layout with zone HTML.
     */
    private function assembleLayout(array $layout, array $zoneHtml, array $context): string
    {
        $zones = $layout['zones'] ?? [];
        $page  = $context['page'];

        ob_start();

        /* Header */
        require ROOT_PATH . '/includes/head.php';
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . '/includes/components/navigation.php';

        /* Breadcrumb trail (non-homepage) */
        if (!($context['is_home'] ?? false)) {
            global $pageBreadcrumb;
            $pageBreadcrumb = [
                ['label' => 'Home', 'url' => '/'],
                ['label' => $page['title']],
            ];
            require ROOT_PATH . '/includes/breadcrumb.php';
        }

        /* Page title banner (non-homepage) */
        if (!($context['is_home'] ?? false) && $page['title']) {
            ?>
            <header class="page-header">
                <div class="container">
                    <h1 class="page-title"><?php echo esc($page['title']); ?></h1>
                </div>
            </header>
            <?php
        }

        /* Before main zone */
        echo $zoneHtml['before_main'] ?? '';

        echo '<main class="main-content" id="main-content">';

        /* Hero zone */
        echo $zoneHtml['hero'] ?? '';

        /* Content + Sidebar (two-column layout) */
        $hasSidebar = !empty($zoneHtml['sidebar']);
        ?>
        <div class="container">
            <div class="layout-row <?php echo $hasSidebar ? 'layout-row--with-sidebar' : ''; ?>">
                <div class="layout-content">
                    <?php echo $zoneHtml['content'] ?? ''; ?>
                </div>
                <?php if ($hasSidebar): ?>
                <aside class="layout-sidebar" role="complementary">
                    <?php echo $zoneHtml['sidebar']; ?>
                </aside>
                <?php endif; ?>
            </div>
        </div>

        <!-- CTA zone -->
        <?php echo $zoneHtml['cta'] ?? ''; ?>

        <?php
        echo '</main>';

        /* After main zone */
        echo $zoneHtml['after_main'] ?? '';

        /* Footer CTA zone */
        echo $zoneHtml['footer_cta'] ?? '';

        /* Footer */
        require ROOT_PATH . '/includes/components/footer.php';
        require ROOT_PATH . '/includes/scripts.php';

        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Render a content type archive or single entry page.
     */
    private function renderContentType(array $context): string
    {
        $this->setGlobalMeta($context['meta']);

        global $pageSchema, $pageTitle, $pageCanonical;
        if (($context['type'] ?? '') === 'archive') {
            $pageSchema = getCollectionPageSchema($pageTitle, $pageCanonical);
        } else {
            $pageSchema = getWebPageSchema($pageTitle, $pageCanonical);
        }

        ob_start();
        require ROOT_PATH . '/includes/head.php';
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . '/includes/components/navigation.php';
        ?>
        <main class="main-content" id="main-content">
            <div class="container">
                <?php if ($context['type'] === 'archive'): ?>
                    <header class="page-header">
                        <h1 class="page-title"><?php echo esc($context['meta']['title']); ?></h1>
                    </header>
                    <div class="content-entries content-entries--grid">
                        <?php
                        $entries = $this->entryManager->getEntries([
                            'type_slug' => $context['content_type']['slug'],
                            'status'    => 'published',
                            'limit'     => 12,
                        ]);
                        foreach ($entries as $entry):
                        ?>
                        <article class="content-entry-card">
                            <?php if ($entry['featured_image']): ?>
                            <div class="content-entry-card__image">
                                <img src="<?php echo esc($entry['featured_image']); ?>" alt="<?php echo esc($entry['title']); ?>" loading="lazy">
                            </div>
                            <?php endif; ?>
                            <div class="content-entry-card__body">
                                <h2 class="content-entry-card__title">
                                    <a href="/<?php echo esc($context['content_type']['slug']); ?>/<?php echo esc($entry['slug']); ?>">
                                        <?php echo esc($entry['title']); ?>
                                    </a>
                                </h2>
                                <?php if ($entry['excerpt']): ?>
                                <p><?php echo esc($entry['excerpt']); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <article class="content-entry-single">
                        <header class="page-header">
                            <h1 class="page-title"><?php echo esc($context['entry']['title']); ?></h1>
                        </header>
                        <div class="content-entry-single__body">
                            <?php
                            $fields = $context['entry']['fields'] ?? [];
                            foreach ($fields as $key => $value):
                                if ($key === 'description' || $key === 'bio' || $key === 'quote'):
                            ?>
                            <div class="content-entry-field">
                                <p><?php echo nl2br(esc($value)); ?></p>
                            </div>
                            <?php elseif ($key === 'photo' || $key === 'avatar'): ?>
                            <div class="content-entry-field">
                                <img src="<?php echo esc($value); ?>" alt="<?php echo esc($context['entry']['title']); ?>" style="max-width:200px">
                            </div>
                            <?php else: ?>
                            <div class="content-entry-field">
                                <p><strong><?php echo esc(ucfirst($key)); ?>:</strong> <?php echo esc($value); ?></p>
                            </div>
                            <?php endif;
                            endforeach; ?>
                        </div>
                    </article>
                <?php endif; ?>
            </div>
        </main>
        <?php
        require ROOT_PATH . '/includes/components/footer.php';
        require ROOT_PATH . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Render the contact page using ContactRenderer.
     */
    private function renderContactPage(array $context): string
    {
        $this->setGlobalMeta($context['meta']);

        $contactRenderer = new ContactRenderer();
        $contactHtml = $contactRenderer->render();
        $assets = $contactRenderer->getPageAssets();

        global $pageCss, $pageJs, $pageSchema;
        $pageCss    = $assets['css'];
        $pageJs     = $assets['js'];
        $pageSchema = $contactRenderer->getSchema();

        global $pageBreadcrumb;
        $pageBreadcrumb = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Contact'],
        ];

        ob_start();
        require ROOT_PATH . '/includes/head.php';
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . '/includes/components/navigation.php';
        require ROOT_PATH . '/includes/breadcrumb.php';
        echo $contactHtml;
        require ROOT_PATH . '/includes/components/footer.php';
        require ROOT_PATH . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Render blog listing.
     */
    private function renderBlogListing(array $context): string
    {
        $this->setGlobalMeta($context['meta']);

        $blogRenderer = new BlogRenderer();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $blogHtml = $blogRenderer->renderListing($page);
        $assets = $blogRenderer->getPageAssets();

        global $pageCss, $pageJs, $pageSchema, $pageTitle, $pageCanonical;
        $pageCss = $assets['css'];
        $pageJs  = $assets['js'];
        $pageSchema = getCollectionPageSchema($pageTitle, $pageCanonical);

        global $pageBreadcrumb;
        $pageBreadcrumb = [['label' => 'Home', 'url' => '/'], ['label' => 'Blog']];

        ob_start();
        require ROOT_PATH . '/includes/head.php';
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . '/includes/components/navigation.php';
        require ROOT_PATH . '/includes/breadcrumb.php';
        echo $blogHtml;
        require ROOT_PATH . '/includes/components/footer.php';
        require ROOT_PATH . '/includes/scripts.php';
        echo '</body></html>';
        return ob_get_clean();
    }

    /**
     * Render single blog post.
     */
    private function renderBlogPost(array $context): string
    {
        $this->setGlobalMeta($context['meta']);
        $post = $context['post'];

        $blogRenderer = new BlogRenderer();
        $blogHtml  = $blogRenderer->renderSingle($post);
        $assets    = $blogRenderer->getPageAssets();

        global $pageCss, $pageJs, $pageSchema;
        $pageCss    = $assets['css'];
        $pageJs     = array_merge($assets['js'], []);
        $pageSchema = $blogRenderer->getSchema($post);

        global $pageBreadcrumb;
        $pageBreadcrumb = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Blog', 'url' => '/blog'],
            ['label' => $post['title']],
        ];

        /* Reading progress bar */
        $progressBar = '<div class="blog-progress-bar" style="width:0%"></div>';

        ob_start();
        require ROOT_PATH . '/includes/head.php';
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . '/includes/components/navigation.php';
        require ROOT_PATH . '/includes/breadcrumb.php';
        echo $progressBar;
        echo $blogHtml;
        require ROOT_PATH . '/includes/components/footer.php';
        require ROOT_PATH . '/includes/scripts.php';
        echo '</body></html>';
        return ob_get_clean();
    }

    /**
     * Render blog category archive.
     */
    private function renderBlogCategory(array $context): string
    {
        $this->setGlobalMeta($context['meta']);

        $blogRenderer = new BlogRenderer();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $blogHtml = $blogRenderer->renderCategoryArchive($context['category']['slug'], $page);
        $assets = $blogRenderer->getPageAssets();

        global $pageCss, $pageJs, $pageSchema, $pageTitle, $pageCanonical;
        $pageCss = $assets['css'];
        $pageJs  = $assets['js'];
        $pageSchema = getCollectionPageSchema($pageTitle, $pageCanonical);

        global $pageBreadcrumb;
        $pageBreadcrumb = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Blog', 'url' => '/blog'],
            ['label' => $context['category']['name']],
        ];

        ob_start();
        require ROOT_PATH . '/includes/head.php';
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . '/includes/components/navigation.php';
        require ROOT_PATH . '/includes/breadcrumb.php';
        echo $blogHtml;
        require ROOT_PATH . '/includes/components/footer.php';
        require ROOT_PATH . '/includes/scripts.php';
        echo '</body></html>';
        return ob_get_clean();
    }

    /**
     * Render blog tag archive.
     */
    private function renderBlogTag(array $context): string
    {
        $this->setGlobalMeta($context['meta']);

        $blogRenderer = new BlogRenderer();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $blogHtml = $blogRenderer->renderTagArchive($context['tag']['slug'], $page);
        $assets = $blogRenderer->getPageAssets();

        global $pageCss, $pageJs, $pageSchema, $pageTitle, $pageCanonical;
        $pageCss = $assets['css'];
        $pageJs  = $assets['js'];
        $pageSchema = getCollectionPageSchema($pageTitle, $pageCanonical);

        global $pageBreadcrumb;
        $pageBreadcrumb = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Blog', 'url' => '/blog'],
            ['label' => $context['tag']['name']],
        ];

        ob_start();
        require ROOT_PATH . '/includes/head.php';
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . '/includes/components/navigation.php';
        require ROOT_PATH . '/includes/breadcrumb.php';
        echo $blogHtml;
        require ROOT_PATH . '/includes/components/footer.php';
        require ROOT_PATH . '/includes/scripts.php';
        echo '</body></html>';
        return ob_get_clean();
    }

    /**
     * Render the about page using AboutRenderer.
     */
    private function renderAboutPage(array $context): string
    {
        $this->setGlobalMeta($context['meta']);

        $aboutRenderer = new AboutRenderer();
        $aboutHtml = $aboutRenderer->render();
        $assets = $aboutRenderer->getPageAssets();

        global $pageCss, $pageJs, $pageSchema;
        $pageCss    = $assets['css'];
        $pageJs     = $assets['js'];
        $pageSchema = $aboutRenderer->getSchema();

        global $pageBreadcrumb;
        $pageBreadcrumb = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'About'],
        ];

        $root = ROOT_PATH;
        ob_start();
        require ROOT_PATH . '/includes/head.php';
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . '/includes/components/navigation.php';
        require ROOT_PATH . '/includes/breadcrumb.php';
        echo $aboutHtml;
        require ROOT_PATH . '/includes/components/footer.php';
        require ROOT_PATH . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Render the /search results page.
     */
    private function renderSearch(array $context): string
    {
        $meta = $context['meta'];
        $meta['noindex'] = true;
        $this->setGlobalMeta($meta);

        $searchRenderer = new SearchRenderer();
        $assets = $searchRenderer->getPageAssets();

        global $pageCss, $pageJs, $pageSchema, $pageTitle, $pageCanonical;
        $pageCss    = $assets['css'];
        $pageJs     = $assets['js'];
        $pageSchema = getWebPageSchema($pageTitle, $pageCanonical);

        global $pageBreadcrumb;
        $pageBreadcrumb = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Search'],
        ];

        $searchHtml = $searchRenderer->render();

        ob_start();
        $root = ROOT_PATH;
        require $root . '/includes/head.php';
        require $root . '/includes/header.php';
        require $root . '/includes/components/navigation.php';
        require $root . '/includes/breadcrumb.php';
        echo $searchHtml;
        require $root . '/includes/components/footer.php';
        require $root . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Set global meta variables for the layout system.
     */
    private function setGlobalMeta(array $meta): void
    {
        global $pageTitle, $pageDescription, $pageCanonical, $pageOgImage, $pageOgType, $pageNoIndex;
        $pageTitle       = $meta['title'] ?? 'Swap Design';
        $pageDescription = $meta['description'] ?? '';
        $pageCanonical   = $meta['canonical'] ?? '';
        $pageOgImage     = $meta['og_image'] ?? '';
        $pageOgType      = $meta['og_type'] ?? 'website';
        $pageNoIndex     = $meta['noindex'] ?? false;
    }

    /**
     * Render 404 page.
     */
    private function render404(): string
    {
        header('HTTP/1.1 404 Not Found');
        $this->setGlobalMeta(['title' => 'Page Not Found', 'noindex' => true]);

        global $pageSchema, $pageTitle, $pageCanonical;
        $pageSchema = getWebPageSchema($pageTitle, $pageCanonical, ['@type' => 'ErrorPage']);

        ob_start();
        require ROOT_PATH . '/includes/head.php';
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . '/includes/components/navigation.php';
        ?>
        <main class="main-content" id="main-content">
            <div class="container">
                <div class="error-page">
                    <h1>Page Not Found</h1>
                    <p>The page you are looking for does not exist or has been moved.</p>
                    <a href="/" class="btn btn--primary">Return Home</a>
                </div>
            </div>
        </main>
        <?php
        require ROOT_PATH . '/includes/components/footer.php';
        require ROOT_PATH . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    /**
     * Render empty state (no homepage set).
     */
    private function renderEmpty(): string
    {
        $this->setGlobalMeta(['title' => 'Welcome', 'noindex' => true]);

        global $pageSchema, $pageTitle, $pageCanonical;
        $pageSchema = getWebPageSchema($pageTitle, $pageCanonical);
        ob_start();
        require ROOT_PATH . '/includes/head.php';
        require ROOT_PATH . '/includes/header.php';
        require ROOT_PATH . '/includes/components/navigation.php';
        ?>
        <main class="main-content" id="main-content">
            <div class="container">
                <div class="empty-state">
                    <h1>Welcome to Swap Design</h1>
                    <p>Your website is being set up. Please check back soon.</p>
                </div>
            </div>
        </main>
        <?php
        require ROOT_PATH . '/includes/components/footer.php';
        require ROOT_PATH . '/includes/scripts.php';
        echo '</body></html>';

        return ob_get_clean();
    }

    private function db(): Database
    {
        return Database::getInstance();
    }
}
