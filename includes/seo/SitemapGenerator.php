<?php
/**
 * Swap Design - Sitemap Generator
 *
 * Builds XML sitemaps from published content and writes them to the
 * project root so they are served as static files (Hostinger-safe,
 * no routing changes required). Generates:
 *   - sitemap.xml            (home + top-level pages + archives)
 *   - sitemap-blog.xml       (blog posts, categories, tags)
 *   - sitemap-services.xml   (services)
 *   - sitemap-portfolio.xml  (portfolio projects)
 *   - sitemap-images.xml     (media library images + featured images)
 *
 * Data is read directly from source tables (no manager dependencies)
 * to avoid circular requires. Managers call sitemapGenerator()?->regenerate()
 * after content changes so sitemaps stay in sync automatically, mirroring
 * the SearchIndexer hook pattern.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class SitemapGenerator
{
    private Database $db;

    /** Sitemap index: file name => collection method. */
    private const SITEMAPS = [
        'sitemap.xml'           => 'collectRoot',
        'post-sitemap.xml'      => 'collectPosts',
        'page-sitemap.xml'      => 'collectPages',
        'local-sitemap.xml'     => 'collectLocal',
        'sitemap-services.xml'  => 'collectServices',
        'sitemap-portfolio.xml' => 'collectPortfolio',
        'sitemap-images.xml'    => 'collectImages',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ================================================================
       Public API
       ================================================================ */

    /**
     * Regenerate every sitemap file.
     *
     * @return array{files:int,urls:int} Number of files written and total URLs
     */
    public function regenerate(): array
    {
        $files = 0;
        $urls  = 0;

        foreach (self::SITEMAPS as $file => $method) {
            $rows = $this->{$method}();
            if ($this->writeFile($file, $this->renderUrlset($rows))) {
                $files++;
            }
            $urls += count($rows);
        }

        return ['files' => $files, 'urls' => $urls];
    }

    /**
     * Regenerate a single sitemap file by name.
     */
    public function regenerateFile(string $file): bool
    {
        if (!isset(self::SITEMAPS[$file])) {
            return false;
        }

        $method = self::SITEMAPS[$file];
        $rows   = $this->{$method}();

        return $this->writeFile($file, $this->renderUrlset($rows));
    }

    /**
     * Check whether every configured sitemap file exists on disk.
     */
    public function isCurrent(): bool
    {
        foreach (self::SITEMAPS as $file => $method) {
            if (!is_file(ROOT_PATH . '/' . $file)) {
                return false;
            }
        }
        return true;
    }

    /* ================================================================
       Collectors
       ================================================================ */

    /**
     * Root sitemap: homepage and fixed module routes.
     */
    private function collectRoot(): array
    {
        $rows = [];

        $rows[] = [
            'loc'        => SITE_URL . '/',
            'lastmod'    => null,
            'changefreq' => 'weekly',
            'priority'   => '1.0',
        ];

        $static = [
            'about-us'     => ['weekly', '0.8'],
            'contact-us'   => ['weekly', '0.8'],
            'services'     => ['weekly', '0.9'],
            'our-work'     => ['weekly', '0.9'],
            'blog'         => ['daily',   '0.8'],
            'privacy-policy' => ['monthly', '0.3'],
        ];
        foreach ($static as $slug => $meta) {
            $rows[] = [
                'loc'        => SITE_URL . '/' . $slug . '/',
                'lastmod'    => null,
                'changefreq' => $meta[0],
                'priority'   => $meta[1],
            ];
        }

        return $rows;
    }

    /**
     * Post sitemap: blog posts with /website/ and /blog/ URL prefixes.
     */
    private function collectPosts(): array
    {
        $rows = [];

        /* Website posts */
        $websitePosts = [
            'stop-ignoring-your-wordpress-updates',
            'dedicated-wordpress-developer-in-2025',
            'website-maintenance-cost-month-in-india',
            'how-to-create-a-beautiful-and-functional-blog-website',
            'swap-design-affordable-website-solutions',
            'crafting-digital-experiences-website-design',
            'invest-in-a-dedicated-wordpress-developer',
            'crafting-user-friendly-website-tips-for-website-designers',
            'reasons-why-your-business-needs-a-website',
            '5reasons-why-web-design-and-functionality',
            'website-design-and-development-aurangabad',
        ];

        foreach ($websitePosts as $slug) {
            $post = $this->db->fetch(
                "SELECT updated_at FROM blog_posts WHERE slug = ? AND status = 'published' LIMIT 1",
                [$slug]
            );
            if ($post) {
                $rows[] = [
                    'loc'        => SITE_URL . '/website/' . $slug . '/',
                    'lastmod'    => $post['updated_at'] ?: null,
                    'changefreq' => 'monthly',
                    'priority'   => '0.7',
                ];
            }
        }

        /* Blog posts */
        $blogPosts = [
            'search-engine-optimization-demystified',
            'exploring-the-future-of-design-and-development-trends-and-insights',
            'essential-tips-for-effective-website-design-and-development',
            'business-card-design-trends-2023',
            'every-business-owner-able-answer-correctly',
            'informative-websites-and-seo',
            'website-design-in-aurangabad',
        ];

        foreach ($blogPosts as $slug) {
            $post = $this->db->fetch(
                "SELECT updated_at FROM blog_posts WHERE slug = ? AND status = 'published' LIMIT 1",
                [$slug]
            );
            if ($post) {
                $rows[] = [
                    'loc'        => SITE_URL . '/blog/' . $slug . '/',
                    'lastmod'    => $post['updated_at'] ?: null,
                    'changefreq' => 'monthly',
                    'priority'   => '0.7',
                ];
            }
        }

        return $rows;
    }

    /**
     * Page sitemap: all static pages and service pages.
     */
    private function collectPages(): array
    {
        $rows = [];

        /* Static pages */
        $staticPages = [
            'privacy-policy',
            'about-us',
            'contact-us',
            'blog',
        ];

        foreach ($staticPages as $slug) {
            $page = $this->db->fetch(
                "SELECT updated_at FROM pages WHERE slug = ? AND status = 'published' LIMIT 1",
                [$slug]
            );
            $rows[] = [
                'loc'        => SITE_URL . '/' . $slug . '/',
                'lastmod'    => $page['updated_at'] ?? null,
                'changefreq' => 'monthly',
                'priority'   => $slug === 'blog' ? '0.8' : '0.7',
            ];
        }

        /* Service pages */
        $services = $this->db->fetchAll(
            "SELECT slug, updated_at FROM services WHERE status = 'published' ORDER BY sort_order"
        );
        foreach ($services as $service) {
            $rows[] = [
                'loc'        => SITE_URL . '/services/' . $service['slug'] . '/',
                'lastmod'    => $service['updated_at'] ?: null,
                'changefreq' => 'monthly',
                'priority'   => '0.8',
            ];
        }

        /* Portfolio work pages */
        $portfolioPages = [
            'logo-branding-design-work',
            'graphic-design-work',
            'website-design-development-work',
        ];

        foreach ($portfolioPages as $slug) {
            $rows[] = [
                'loc'        => SITE_URL . '/' . $slug . '/',
                'lastmod'    => null,
                'changefreq' => 'monthly',
                'priority'   => '0.7',
            ];
        }

        /* Portfolio project pages */
        $portfolio = $this->db->fetchAll(
            "SELECT slug, updated_at FROM portfolio_items WHERE status = 'published' ORDER BY created_at DESC"
        );
        foreach ($portfolio as $item) {
            $rows[] = [
                'loc'        => SITE_URL . '/' . $item['slug'] . '/',
                'lastmod'    => $item['updated_at'] ?: null,
                'changefreq' => 'monthly',
                'priority'   => '0.6',
            ];
        }

        return $rows;
    }

    /**
     * Local sitemap: local business pages.
     */
    private function collectLocal(): array
    {
        $rows = [];

        /* Local business listing page */
        $rows[] = [
            'loc'        => SITE_URL . '/',
            'lastmod'    => null,
            'changefreq' => 'weekly',
            'priority'   => '1.0',
        ];

        /* Add location-specific URLs if needed */
        $rows[] = [
            'loc'        => SITE_URL . '/website-design-in-aurangabad/',
            'lastmod'    => null,
            'changefreq' => 'monthly',
            'priority'   => '0.7',
        ];

        return $rows;
    }

    /**
     * Services sitemap.
     */
    private function collectServices(): array
    {
        $services = $this->db->fetchAll(
            "SELECT slug, updated_at FROM services WHERE status = 'published' ORDER BY sort_order ASC"
        );

        $rows = [];
        foreach ($services as $service) {
            $rows[] = [
                'loc'        => SITE_URL . '/services/' . $service['slug'],
                'lastmod'    => $service['updated_at'] ?: null,
                'changefreq' => 'monthly',
                'priority'   => '0.7',
            ];
        }

        return $rows;
    }

    /**
     * Portfolio sitemap.
     */
    private function collectPortfolio(): array
    {
        $items = $this->db->fetchAll(
            "SELECT slug, updated_at, completion_date FROM portfolio_items
             WHERE status = 'published' ORDER BY completion_date DESC, id DESC"
        );

        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'loc'        => SITE_URL . '/portfolio/' . $item['slug'],
                'lastmod'    => $item['updated_at'] ?: null,
                'changefreq' => 'monthly',
                'priority'   => '0.6',
            ];
        }

        return $rows;
    }

    /**
     * Images sitemap: media library images plus featured images across
     * content types, using the <image:image> extension.
     */
    private function collectImages(): array
    {
        $rows = [];
        $seen = [];

        $media = $this->db->fetchAll(
            "SELECT filename, alt_text, title, original_name, created_at, width, height
             FROM media_library
             WHERE is_trashed = 0 AND mime_type LIKE 'image/%' AND mime_type != 'image/svg+xml'
             ORDER BY created_at DESC"
        );
        foreach ($media as $item) {
            $loc = $this->resolveImageUrl($item['filename']);
            if (!$loc || isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $rows[] = [
                'loc'     => $loc,
                'lastmod' => $item['created_at'] ?: null,
                'title'   => $item['title'] ?: ($item['original_name'] ?: ''),
                'caption' => $item['alt_text'] ?: '',
                'width'   => $item['width'] ?: null,
                'height'  => $item['height'] ?: null,
            ];
        }

        /* Featured images across content types */
        $featuredSources = [
            "SELECT featured_image AS img, title, updated_at FROM services WHERE status = 'published' AND featured_image <> ''",
            "SELECT image_url AS img, title, updated_at FROM portfolio_items WHERE status = 'published' AND image_url <> ''",
            "SELECT featured_image AS img, title, updated_at FROM blog_posts WHERE status = 'published' AND featured_image <> ''",
        ];
        foreach ($featuredSources as $sql) {
            $rowsList = $this->db->fetchAll($sql);
            foreach ($rowsList as $row) {
                $loc = $this->resolveImageUrl($row['img']);
                if (!$loc || isset($seen[$loc])) {
                    continue;
                }
                $seen[$loc] = true;
                $rows[] = [
                    'loc'     => $loc,
                    'lastmod' => $row['updated_at'] ?: null,
                    'title'   => $row['title'] ?: '',
                    'caption' => '',
                    'width'   => null,
                    'height'  => null,
                ];
            }
        }

        return $rows;
    }

    /* ================================================================
       Rendering + file output
       ================================================================ */

    /**
     * Render a <urlset> document from URL rows.
     */
    private function renderUrlset(array $rows): string
    {
        $out = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $out .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"";
        $hasImages = $this->hasImageRows($rows);
        if ($hasImages) {
            $out .= " xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\"";
        }
        $out .= ">\n";

        foreach ($rows as $row) {
            $out .= "  <url>\n";
            $out .= "    <loc>" . $this->xml($row['loc']) . "</loc>\n";
            if (!empty($row['lastmod'])) {
                $out .= "    <lastmod>" . $this->xml($this->isoDate($row['lastmod'])) . "</lastmod>\n";
            }
            if (!empty($row['changefreq'])) {
                $out .= "    <changefreq>" . $this->xml($row['changefreq']) . "</changefreq>\n";
            }
            if (!empty($row['priority'])) {
                $out .= "    <priority>" . $this->xml($row['priority']) . "</priority>\n";
            }
            if (!empty($row['title']) || !empty($row['caption']) || $hasImages) {
                $out .= "    <image:image>\n";
                if (!empty($row['loc'])) {
                    $out .= "      <image:loc>" . $this->xml($row['loc']) . "</image:loc>\n";
                }
                if (!empty($row['title'])) {
                    $out .= "      <image:title>" . $this->xml($row['title']) . "</image:title>\n";
                }
                if (!empty($row['caption'])) {
                    $out .= "      <image:caption>" . $this->xml($row['caption']) . "</image:caption>\n";
                }
                $out .= "    </image:image>\n";
            }
            $out .= "  </url>\n";
        }

        $out .= "</urlset>\n";
        return $out;
    }

    private function hasImageRows(array $rows): bool
    {
        foreach ($rows as $row) {
            if (!empty($row['title']) || !empty($row['caption'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Write an XML document to the project root.
     */
    private function writeFile(string $file, string $content): bool
    {
        $path = ROOT_PATH . '/' . $file;
        return @file_put_contents($path, $content) !== false;
    }

    /* ================================================================
       Helpers
       ================================================================ */

    /**
     * Resolve a stored image reference (URL, /uploads/... path, or
     * absolute server path) into an absolute http(s) URL.
     */
    private function resolveImageUrl(string $ref): ?string
    {
        $ref = trim($ref);
        if ($ref === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $ref)) {
            return $ref;
        }

        if (strpos($ref, ROOT_PATH) === 0) {
            $ref = str_replace(ROOT_PATH, '', $ref);
        }

        $ref = '/' . ltrim($ref, '/');
        return SITE_URL . $ref;
    }

    /**
     * Normalize a date-time value to ISO 8601 (W3C datetime).
     */
    private function isoDate(string $value): string
    {
        $ts = strtotime($value);
        return $ts ? date('c', $ts) : $value;
    }

    /**
     * Escape text for safe XML output.
     */
    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
