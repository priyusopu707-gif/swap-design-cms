<?php
/**
 * Swap Design - SEO Auditor
 *
 * Runs on-demand SEO health checks against published content and
 * reports findings for the admin SEO dashboard. All queries read
 * directly from source tables (no manager dependencies) so the
 * auditor works even when other modules are disabled.
 *
 * Checks:
 *   - Missing meta titles / descriptions
 *   - Missing image ALT text (media library)
 *   - Duplicate slugs across content types
 *   - Broken internal links
 *   - Orphan pages (published pages with no internal inbound links)
 *   - Redirect status / top redirected URLs
 *   - Canonical URL validation
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class SEOAuditor
{
    private Database $db;

    /** Content sources with their SEO columns. */
    private const CONTENT_SOURCES = [
        'page'     => ['table' => 'pages',          'title_col' => 'title',   'seo_title_col' => 'seo_title',       'meta_col' => 'meta_desc'],
        'service'  => ['table' => 'services',       'title_col' => 'title',   'seo_title_col' => 'seo_title',       'meta_col' => 'meta_description'],
        'portfolio'=> ['table' => 'portfolio_items','title_col' => 'title',   'seo_title_col' => 'seo_title',       'meta_col' => 'meta_description'],
        'blog_post'=> ['table' => 'blog_posts',     'title_col' => 'title',   'seo_title_col' => 'seo_title',       'meta_col' => 'meta_description'],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ================================================================
       Overview stats
       ================================================================ */

    /**
     * Aggregate counts for the dashboard stat cards.
     */
    public function getStats(): array
    {
        return [
            'published_pages'      => $this->db->count('pages', "status = 'published'"),
            'published_services'   => $this->db->count('services', "status = 'published'"),
            'published_portfolio'  => $this->db->count('portfolio_items', "status = 'published'"),
            'published_blog'       => $this->db->count('blog_posts', "status = 'published'"),
            'media_images'         => $this->db->count('media_library', "is_trashed = 0 AND mime_type LIKE 'image/%'"),
            'active_redirects'     => $this->db->count('url_redirects'),
            'missing_titles'       => count($this->missingMeta('seo_title_col')),
            'missing_descriptions' => count($this->missingMeta('meta_col')),
            'missing_alt'          => count($this->missingAltText()),
            'duplicate_slugs'      => count($this->duplicateSlugs()),
        ];
    }

    /* ================================================================
       Full audit
       ================================================================ */

    /**
     * Run every check and return a categorized findings list.
     *
     * @return array{groups:array, totals:array}
     */
    public function runAudit(): array
    {
        $checks = [
            'missing_titles'       => $this->missingMeta('seo_title_col'),
            'missing_descriptions' => $this->missingMeta('meta_col'),
            'missing_alt'          => $this->missingAltText(),
            'duplicate_slugs'      => $this->duplicateSlugs(),
            'broken_links'         => $this->brokenInternalLinks(),
            'orphan_pages'         => $this->orphanPages(),
            'redirect_status'      => $this->redirectStatus(),
            'canonical_issues'     => $this->canonicalIssues(),
        ];

        $totals = [];
        foreach ($checks as $key => $rows) {
            $totals[$key] = count($rows);
        }

        return ['groups' => $checks, 'totals' => $totals];
    }

    /* ================================================================
       Individual checks
       ================================================================ */

    /**
     * Content records missing a usable SEO title or meta description.
     */
    private function missingMeta(string $column): array
    {
        $findings = [];

        foreach (self::CONTENT_SOURCES as $type => $cfg) {
            $table     = $cfg['table'];
            $titleCol  = $cfg['title_col'];
            $seoCol    = $cfg['seo_title_col'];
            $metaCol   = $cfg['meta_col'];

            /* Guard against tables that never gained an SEO column
               (e.g. pages has meta_desc but no seo_title). */
            if ($column === 'seo_title_col' && !$this->columnExists($table, $seoCol)) {
                continue;
            }
            if ($column === 'meta_col' && !$this->columnExists($table, $metaCol)) {
                continue;
            }

            $rows = $this->db->fetchAll(
                "SELECT id, {$titleCol} AS title, {$seoCol} AS seo_title, {$metaCol} AS meta_desc
                 FROM {$table} WHERE status = 'published'"
            );

            foreach ($rows as $row) {
                $label = $column === 'seo_title_col' ? 'title' : 'description';
                $isMissing = $column === 'seo_title_col'
                    ? trim((string)($row['seo_title'] ?? '')) === ''
                    : trim((string)($row['meta_desc'] ?? '')) === '';

                if ($isMissing) {
                    $findings[] = [
                        'type'    => $type,
                        'id'      => (int)$row['id'],
                        'title'   => $row['title'],
                        'label'   => "Missing meta {$label}",
                        'url'     => $this->entityUrl($type, $row),
                    ];
                }
            }
        }

        return $findings;
    }

    /**
     * Media images with no ALT text.
     */
    private function missingAltText(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT id, filename, original_name, title
             FROM media_library
             WHERE is_trashed = 0 AND mime_type LIKE 'image/%' AND mime_type != 'image/svg+xml'
             ORDER BY id DESC"
        );

        $findings = [];
        foreach ($rows as $row) {
            if (trim((string)($row['title'] ?? '')) !== '' || trim((string)($row['original_name'] ?? '')) !== '') {
                continue;
            }
            $findings[] = [
                'type'    => 'media',
                'id'      => (int)$row['id'],
                'title'   => $row['filename'],
                'label'   => 'Missing ALT text',
                'url'     => '/admin/media.php',
            ];
        }

        return $findings;
    }

    /**
     * Slugs that collide across content types or repeat within a type.
     */
    private function duplicateSlugs(): array
    {
        $sources = [
            'page'      => "SELECT id, slug, title FROM pages WHERE status = 'published'",
            'service'   => "SELECT id, slug, title FROM services WHERE status = 'published'",
            'portfolio' => "SELECT id, slug, title FROM portfolio_items WHERE status = 'published'",
            'blog_post' => "SELECT id, slug, title FROM blog_posts WHERE status = 'published'",
            'entry'     => "SELECT id, slug, title FROM content_entries WHERE status = 'published'",
        ];

        $map = [];
        foreach ($sources as $type => $sql) {
            foreach ($this->db->fetchAll($sql) as $row) {
                $slug = mb_strtolower(trim($row['slug'] ?? ''));
                if ($slug === '') {
                    continue;
                }
                $map[$slug][] = [
                    'type'  => $type,
                    'id'    => (int)$row['id'],
                    'title' => $row['title'],
                ];
            }
        }

        $findings = [];
        foreach ($map as $slug => $items) {
            if (count($items) < 2) {
                continue;
            }
            $findings[] = [
                'type'  => 'slug',
                'slug'  => $slug,
                'count' => count($items),
                'items' => $items,
            ];
        }

        return $findings;
    }

    /**
     * Internal links whose target does not resolve to a published route.
     */
    private function brokenInternalLinks(): array
    {
        /* Build the set of valid internal paths. */
        $validPaths = [
            '/', '/about', '/contact', '/blog', '/services', '/portfolio', '/search',
        ];

        foreach ($this->db->fetchAll("SELECT slug FROM pages WHERE status = 'published'") as $row) {
            $validPaths[] = '/' . $row['slug'];
        }
        foreach ($this->db->fetchAll("SELECT slug FROM services WHERE status = 'published'") as $row) {
            $validPaths[] = '/services/' . $row['slug'];
        }
        foreach ($this->db->fetchAll("SELECT slug FROM portfolio_items WHERE status = 'published'") as $row) {
            $validPaths[] = '/portfolio/' . $row['slug'];
        }
        foreach ($this->db->fetchAll("SELECT slug FROM blog_posts WHERE status = 'published'") as $row) {
            $validPaths[] = '/blog/' . $row['slug'];
        }

        $valid = array_fill_keys($validPaths, true);

        /* Content bodies to scan. */
        $bodies = [];
        foreach ($this->db->fetchAll("SELECT id, title, content FROM pages WHERE status = 'published'") as $row) {
            $bodies[] = ['source' => 'page', 'title' => $row['title'], 'content' => (string)$row['content']];
        }
        foreach ($this->db->fetchAll("SELECT id, title, content FROM blog_posts WHERE status = 'published'") as $row) {
            $bodies[] = ['source' => 'blog_post', 'title' => $row['title'], 'content' => (string)$row['content']];
        }

        $findings = [];
        $base = parse_url(SITE_URL, PHP_URL_HOST) ?: '';

        foreach ($bodies as $body) {
            if ($body['content'] === '') {
                continue;
            }
            if (!preg_match_all('/href=["\']([^"\']+)["\']/', $body['content'], $m)) {
                continue;
            }

            foreach ($m[1] as $href) {
                $href = trim($href);
                if ($href === '' || strpos($href, '#') === 0 || strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0 || strpos($href, 'javascript:') === 0) {
                    continue;
                }

                $path = $href;
                if (preg_match('#^https?://#i', $href)) {
                    $host = parse_url($href, PHP_URL_HOST) ?? '';
                    if ($host !== '' && $host !== $base) {
                        continue; /* external link */
                    }
                    $path = parse_url($href, PHP_URL_PATH) ?? '/';
                }

                /* Strip anchor + query */
                $path = strtok($path, '#');
                if ($path === false) {
                    continue;
                }
                $path = strtok($path, '?') ?: $path;
                $path = '/' . trim($path, '/');

                /* Allow fragment-only and empty paths already handled. */
                if ($path === '') {
                    $path = '/';
                }

                if (isset($valid[$path])) {
                    continue;
                }

                $findings[] = [
                    'source' => $body['source'],
                    'title'  => $body['title'],
                    'href'   => $href,
                ];
            }
        }

        return $findings;
    }

    /**
     * Published pages that no internal content links point to.
     */
    private function orphanPages(): array
    {
        $pages = $this->db->fetchAll(
            "SELECT id, slug, title, content FROM pages WHERE status = 'published'"
        );

        $linked = [];
        foreach ($pages as $page) {
            $pagePath = '/' . trim($page['slug'], '/');
            $linked[$pagePath] = false;
        }

        foreach ($pages as $page) {
            $content = (string)$page['content'];
            if ($content === '' || !preg_match_all('/href=["\']([^"\']+)["\']/', $content, $m)) {
                continue;
            }
            foreach ($m[1] as $href) {
                if (preg_match('#^https?://#i', $href)) {
                    $href = parse_url($href, PHP_URL_PATH) ?? '/';
                }
                $path = '/' . trim(strtok($href, '#') ?: '/', '/');
                if (isset($linked[$path])) {
                    $linked[$path] = true;
                }
            }
        }

        $findings = [];
        foreach ($pages as $page) {
            $pagePath = '/' . trim($page['slug'], '/');
            if ($linked[$pagePath]) {
                continue;
            }
            $findings[] = [
                'type'  => 'page',
                'id'    => (int)$page['id'],
                'title' => $page['title'],
                'label' => 'No internal inbound links',
                'url'   => $pagePath,
            ];
        }

        return $findings;
    }

    /**
     * Redirect table summary (top by hit count).
     */
    private function redirectStatus(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT old_slug, new_slug, redirect_type, status_code, hit_count, created_at
             FROM url_redirects ORDER BY hit_count DESC, created_at DESC LIMIT 100"
        );

        $findings = [];
        foreach ($rows as $row) {
            $findings[] = [
                'type'        => 'redirect',
                'old_slug'    => $row['old_slug'],
                'new_slug'    => $row['new_slug'],
                'redirect_type' => $row['redirect_type'],
                'status_code' => (int)$row['status_code'],
                'hit_count'   => (int)$row['hit_count'],
                'created_at'  => $row['created_at'],
            ];
        }

        return $findings;
    }

    /**
     * Explicit canonical URLs that do not match the entity's own URL.
     */
    private function canonicalIssues(): array
    {
        $findings = [];

        $sources = [
            'service'   => "SELECT id, title, slug, canonical_url FROM services WHERE status = 'published' AND canonical_url IS NOT NULL AND canonical_url <> ''",
            'portfolio' => "SELECT id, title, slug, canonical_url FROM portfolio_items WHERE status = 'published' AND canonical_url IS NOT NULL AND canonical_url <> ''",
            'blog_post' => "SELECT id, title, slug, canonical_url FROM blog_posts WHERE status = 'published' AND canonical_url IS NOT NULL AND canonical_url <> ''",
        ];

        $prefix = [
            'service'   => '/services/',
            'portfolio' => '/portfolio/',
            'blog_post' => '/blog/',
        ];

        foreach ($sources as $type => $sql) {
            foreach ($this->db->fetchAll($sql) as $row) {
                $expected = SITE_URL . $prefix[$type] . $row['slug'];
                $canonical = trim((string)$row['canonical_url']);

                if ($canonical !== $expected && rtrim($canonical, '/') !== rtrim($expected, '/')) {
                    $findings[] = [
                        'type'    => $type,
                        'id'      => (int)$row['id'],
                        'title'   => $row['title'],
                        'label'   => 'Canonical mismatch',
                        'current' => $canonical,
                        'expected'=> $expected,
                    ];
                }
            }
        }

        return $findings;
    }

    /* ================================================================
       Helpers
       ================================================================ */

    /**
     * Whether a column exists in the current database schema.
     */
    private function columnExists(string $table, string $column): bool
    {
        $row = $this->db->fetch(
            "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return $row ? (int)$row['c'] > 0 : false;
    }

    /**
     * Build the public URL for a content entity.
     */
    private function entityUrl(string $type, array $row): string
    {
        return match ($type) {
            'page'      => SITE_URL . '/' . trim($row['slug'] ?? '', '/'),
            'service'   => SITE_URL . '/services/' . ($row['slug'] ?? ''),
            'portfolio' => SITE_URL . '/portfolio/' . ($row['slug'] ?? ''),
            'blog_post' => SITE_URL . '/blog/' . ($row['slug'] ?? ''),
            default     => SITE_URL . '/',
        };
    }
}
