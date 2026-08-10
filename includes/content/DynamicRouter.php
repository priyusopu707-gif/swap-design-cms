<?php
/**
 * Swap Design - Dynamic Router
 *
 * Replaces the hardcoded route table in loader.php with
 * database-driven page resolution. Resolves URLs to pages,
 * handles 301 redirects for slug changes, and supports
 * custom route patterns for content types.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class DynamicRouter
{
    private Database $db;
    private SlugManager $slugManager;

    public function __construct()
    {
        $this->db         = Database::getInstance();
        $this->slugManager = new SlugManager();
    }

    /**
     * Resolve a URL to a page context array.
     *
     * @param string $url The requested URL path (from $_GET['url'] or REQUEST_URI)
     * @return array      Context array with 'type', 'status', and entity data
     */
    public function resolve(string $url): array
    {
        $url = trim($url, '/');
        $url = $url === '' ? '/' : $url;

        /* Step 1: Homepage */
        if ($url === '/') {
            return $this->resolveHomepage();
        }

        /* Step 1b: About page (dedicated module) */
        if ($url === 'about' || $url === 'about-us') {
            return $this->resolveAboutPage();
        }

        /* Step 1c: Contact page (dedicated module) */
        if ($url === 'contact' || $url === 'contact-us') {
            return $this->resolveContactPage();
        }

        /* Step 1d: Blog listing (dedicated module) */
        if ($url === 'blog') {
            return $this->resolveBlogListing();
        }

        /* Step 1e: Website posts (new URL structure: /website/{slug}/) */
        if (preg_match('#^website/([^/]+)/?$#', $url, $m)) {
            return $this->resolveWebsitePost($m[1]);
        }

        /* Step 1f: Blog category archive */
        if (preg_match('#^blog/category/([^/]+)$#', $url, $m)) {
            return $this->resolveBlogCategory($m[1]);
        }

        /* Step 1g: Blog tag archive */
        if (preg_match('#^blog/tag/([^/]+)$#', $url, $m)) {
            return $this->resolveBlogTag($m[1]);
        }

        /* Step 1h: Blog single post (new URL structure: /blog/{slug}/) */
        if (preg_match('#^blog/([^/]+)/?$#', $url, $m) && $m[1] !== 'category' && $m[1] !== 'tag') {
            return $this->resolveBlogPost($m[1]);
        }

        /* Step 1i: Search page (dedicated module) */
        if ($url === 'search') {
            return $this->resolveSearch();
        }

        /* Step 1j: Privacy policy */
        if ($url === 'privacy-policy') {
            return $this->resolvePageBySlug('privacy-policy');
        }

        /* Step 1k: Our work */
        if ($url === 'our-work') {
            return $this->resolvePortfolioArchive();
        }

        /* Step 2: Content type archive routes (e.g., /services, /team) */
        $slug  = $url;
        $segments = explode('/', $slug);

        /* Step 2a: Check if first segment is a content type archive */
        if (count($segments) === 1) {
            $contentType = $this->db->fetch(
                "SELECT * FROM content_types WHERE slug = ? AND status = 'active'",
                [$segments[0]]
            );
            if ($contentType && $contentType['has_entries']) {
                return $this->resolveContentTypeArchive($contentType, $segments[0]);
            }

            /* Check if segment is 'portfolio' listing */
            if ($segments[0] === 'portfolio') {
                return $this->resolvePortfolioArchive();
            }

            /* Check if segment is 'services' listing */
            if ($segments[0] === 'services') {
                return $this->resolveServicesArchive();
            }
        }

        /* Step 2b: Check if two segments form a content type + entry slug */
        if (count($segments) === 2) {
            $contentType = $this->db->fetch(
                "SELECT * FROM content_types WHERE slug = ? AND status = 'active'",
                [$segments[0]]
            );
            if ($contentType && $contentType['has_entries']) {
                $entry = $this->db->fetch(
                    "SELECT * FROM content_entries WHERE content_type_id = ? AND slug = ? AND status = 'published'",
                    [(int)$contentType['id'], $segments[1]]
                );
                if ($entry) {
                    return $this->resolveContentEntry($contentType, $entry);
                }
            }
        }

        /* Step 2c: Check if first segment is 'services' — dedicated services table */
        if (count($segments) === 2 && $segments[0] === 'services') {
            $service = $this->db->fetch(
                "SELECT * FROM services WHERE slug = ? AND status = 'published'",
                [$segments[1]]
            );
            if ($service) {
                return $this->resolveService($service);
            }
        }

        /* Step 2d: Check if first segment is 'portfolio' — dedicated portfolio table */
        if (count($segments) === 2 && $segments[0] === 'portfolio') {
            $portfolio = $this->db->fetch(
                "SELECT * FROM portfolio_items WHERE slug = ? AND status = 'published'",
                [$segments[1]]
            );
            if ($portfolio) {
                return $this->resolvePortfolioSingle($portfolio);
            }
        }

        /* Step 3: Page by slug */
        $page = $this->db->fetch(
            "SELECT * FROM pages WHERE slug = ? AND status = 'published'",
            [$slug]
        );

        if ($page) {
            return $this->resolvePage($page);
        }

        /* Step 4: Check for redirect */
        $redirect = $this->slugManager->getRedirect($slug);
        if ($redirect) {
            return [
                'type'       => 'redirect',
                'status'     => 301,
                'target_url' => '/' . ltrim($redirect['new_slug'], '/'),
            ];
        }

        /* Step 5: 404 */
        return [
            'type'   => '404',
            'status' => 404,
        ];
    }

    /**
     * Resolve the homepage.
     */
    private function resolveHomepage(): array
    {
        $page = $this->db->fetch(
            "SELECT * FROM pages WHERE is_homepage = 1 AND status = 'published' LIMIT 1"
        );

        if ($page) {
            return $this->resolvePage($page, true);
        }

        /* Ensure homepage page exists: create default homepage if missing */
        $this->db->insert('pages', [
            'title' => 'Homepage',
            'slug' => 'home',
            'meta_desc' => 'Swap Design - Creative Design Solutions',
            'content' => '',
            'status' => 'published',
            'is_homepage' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $page = $this->db->fetch(
            "SELECT * FROM pages WHERE is_homepage = 1 AND status = 'published' LIMIT 1"
        );

        if ($page) {
            return $this->resolvePage($page, true);
        }

        /* Fallback: first published page */
        $page = $this->db->fetch(
            "SELECT * FROM pages WHERE status = 'published' ORDER BY created_at ASC LIMIT 1"
        );

        if ($page) {
            $result = $this->resolvePage($page, true);
            $result['is_fallback_home'] = true;
            return $result;
        }

        return ['type' => 'empty', 'status' => 200];
    }

    /**
     * Resolve a page record to context.
     */
    private function resolvePage(array $page, bool $isHome = false): array
    {
        $layout = null;
        if (!empty($page['layout_id'])) {
            $layout = $this->db->fetch("SELECT * FROM layouts WHERE id = ?", [(int)$page['layout_id']]);
        }

        if (!$layout) {
            $layout = $this->db->fetch("SELECT * FROM layouts WHERE is_default = 1 AND status = 'active' LIMIT 1");
        }

        return [
            'type'       => 'page',
            'status'     => 200,
            'page'       => $page,
            'layout'     => $layout,
            'is_home'    => $isHome,
            'meta'       => [
                'title'       => $page['title'],
                'description' => $page['meta_desc'] ?? '',
            ],
        ];
    }

    /**
     * Resolve a content type archive.
     */
    private function resolveContentTypeArchive(array $contentType, string $slug): array
    {
        return [
            'type'         => 'archive',
            'status'       => 200,
            'content_type' => $contentType,
            'meta'         => [
                'title'       => $contentType['name'],
                'description' => $contentType['description'] ?? '',
            ],
            'template'     => $contentType['list_template'] ?: null,
        ];
    }

    /**
     * Resolve a single content entry.
     */
    private function resolveContentEntry(array $contentType, array $entry): array
    {
        return [
            'type'         => 'entry',
            'status'       => 200,
            'content_type' => $contentType,
            'entry'        => $entry,
            'meta'         => [
                'title'       => $entry['title'],
                'description' => $entry['excerpt'] ?? '',
            ],
            'template'     => $contentType['single_template'] ?: null,
        ];
    }

    /**
     * Resolve the about page.
     */
    private function resolveAboutPage(): array
    {
        return [
            'type'   => 'about',
            'status' => 200,
            'meta'   => [
                'title'       => 'About | Swap Design',
                'description' => 'Learn more about my experience, skills, and creative journey.',
            ],
        ];
    }

    /**
     * Resolve the /contact page as a dedicated module.
     */
    private function resolveContactPage(): array
    {
        return [
            'type'   => 'contact',
            'status' => 200,
            'meta'   => [
                'title'       => 'Contact | Swap Design',
                'description' => 'Get in touch. I am available for freelance projects worldwide.',
            ],
        ];
    }

    /**
     * Resolve /blog as blog listing.
     */
    private function resolveBlogListing(): array
    {
        return [
            'type'   => 'blog_listing',
            'status' => 200,
            'meta'   => [
                'title'       => 'Blog | Swap Design',
                'description' => 'Read articles, tutorials, and insights about design and development.',
            ],
        ];
    }

    /**
     * Resolve /blog/category/{slug} as category archive.
     */
    private function resolveBlogCategory(string $slug): array
    {
        $blogManager = new BlogManager();
        $category = $blogManager->getCategoryBySlug($slug);

        if (!$category) {
            return ['type' => '404', 'status' => 404, 'meta' => []];
        }

        return [
            'type'       => 'blog_category',
            'status'     => 200,
            'category'   => $category,
            'meta'       => [
                'title'       => $category['name'] . ' | Blog | Swap Design',
                'description' => $category['description'] ?: 'Browse posts in ' . $category['name'],
            ],
        ];
    }

    /**
     * Resolve /blog/tag/{slug} as tag archive.
     */
    private function resolveBlogTag(string $slug): array
    {
        $blogManager = new BlogManager();
        $tag = $blogManager->getTagBySlug($slug);

        if (!$tag) {
            return ['type' => '404', 'status' => 404, 'meta' => []];
        }

        return [
            'type'   => 'blog_tag',
            'status' => 200,
            'tag'    => $tag,
            'meta'   => [
                'title'       => $tag['name'] . ' | Blog | Swap Design',
                'description' => 'Browse posts tagged with ' . $tag['name'],
            ],
        ];
    }

    /**
     * Resolve /blog/{slug} as single post.
     */
    private function resolveBlogPost(string $slug): array
    {
        $blogManager = new BlogManager();
        $post = $blogManager->getPostBySlug($slug);

        if (!$post || $post['status'] !== 'published') {
            return ['type' => '404', 'status' => 404, 'meta' => []];
        }

        return [
            'type'   => 'blog_post',
            'status' => 200,
            'post'   => $post,
            'meta'   => [
                'title'       => ($post['seo_title'] ?: $post['title']) . ' | Blog | Swap Design',
                'description' => $post['meta_description'] ?: $post['short_description'] ?: '',
                'og_image'    => $post['og_image'] ?: $post['featured_image'] ?? '',
                'canonical'   => SITE_URL . '/blog/' . $post['slug'] . '/',
            ],
        ];
    }

    /**
     * Resolve /website/{slug} as website post (website category).
     */
    private function resolveWebsitePost(string $slug): array
    {
        $blogManager = new BlogManager();
        $post = $blogManager->getPostBySlug($slug);

        if (!$post || $post['status'] !== 'published') {
            return ['type' => '404', 'status' => 404, 'meta' => []];
        }

        return [
            'type'   => 'blog_post',
            'status' => 200,
            'post'   => $post,
            'meta'   => [
                'title'       => ($post['seo_title'] ?: $post['title']) . ' | Swap Design',
                'description' => $post['meta_description'] ?: $post['short_description'] ?: '',
                'og_image'    => $post['og_image'] ?: $post['featured_image'] ?? '',
                'canonical'   => SITE_URL . '/website/' . $post['slug'] . '/',
            ],
        ];
    }

    /**
     * Resolve a page by slug.
     */
    private function resolvePageBySlug(string $slug): array
    {
        $page = $this->db->fetch(
            "SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1",
            [$slug]
        );

        if (!$page) {
            return ['type' => '404', 'status' => 404, 'meta' => []];
        }

        return [
            'type'  => 'page',
            'status' => 200,
            'page'  => $page,
            'layout' => $this->getPageLayout($page),
            'is_home' => false,
            'meta'   => [
                'title'       => $page['seo_title'] ?: $page['title'],
                'description' => $page['meta_desc'] ?? '',
            ],
        ];
    }

    /**
     * Resolve /search as the site search page.
     */
    private function resolveSearch(): array
    {
        $q = trim((string)($_GET['q'] ?? ''));

        $metaTitle = 'Search Results | Swap Design';
        if ($q !== '') {
            $metaTitle = 'Search Results for "' . mb_substr($q, 0, 60) . '" | Swap Design';
        }

        $canonical = SITE_URL . '/search';
        $queryParams = $_GET;
        unset($queryParams['url']);
        if (!empty($queryParams)) {
            $canonical .= '?' . http_build_query($queryParams);
        }

        return [
            'type'   => 'search',
            'status' => 200,
            'meta'   => [
                'title'       => $metaTitle,
                'description' => 'Search our services, portfolio, blog posts, and more.',
                'canonical'   => $canonical,
                'noindex'     => true,
            ],
        ];
    }

    /**
     * Resolve a service detail page from the services table.
     */
    private function resolveService(array $service): array
    {
        return [
            'type'    => 'service',
            'status'  => 200,
            'service' => $service,
            'meta'    => [
                'title'       => $service['seo_title'] ?: $service['title'],
                'description' => $service['meta_description'] ?: $service['short_description'] ?? '',
                'og_image'    => $service['og_image'] ?? $service['featured_image'] ?? '',
            ],
        ];
    }

    /**
     * Resolve the portfolio listing page.
     */
    private function resolvePortfolioArchive(): array
    {
        return [
            'type'   => 'portfolio_archive',
            'status' => 200,
            'meta'   => [
                'title'       => 'Portfolio | Swap Design',
                'description' => 'Explore our portfolio of design projects, case studies, and creative work.',
            ],
        ];
    }

    /**
     * Resolve the services listing page.
     */
    private function resolveServicesArchive(): array
    {
        return [
            'type'   => 'services_archive',
            'status' => 200,
            'meta'   => [
                'title'       => 'Services | Swap Design',
                'description' => 'Explore our design and development services.',
            ],
        ];
    }

    /**
     * Resolve a single portfolio project page.
     */
    private function resolvePortfolioSingle(array $portfolio): array
    {
        return [
            'type'      => 'portfolio_single',
            'status'    => 200,
            'portfolio' => $portfolio,
            'meta'      => [
                'title'       => $portfolio['seo_title'] ?: $portfolio['title'],
                'description' => $portfolio['meta_description'] ?: $portfolio['description'] ?? '',
                'og_image'    => $portfolio['og_image'] ?? $portfolio['image_url'] ?? '',
            ],
        ];
    }
}
