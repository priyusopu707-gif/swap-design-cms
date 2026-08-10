<?php
/**
 * Swap Design - Search Indexer
 *
 * Builds and maintains the normalized search_index table over every
 * published content source: pages, services, portfolio items, blog
 * posts, content entries, global blocks, and FAQs.
 *
 * Data is read directly from source tables (no manager dependencies)
 * to avoid circular requires. Hooks in the content managers call
 * indexItem()/removeItem() so the index stays in sync automatically.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class SearchIndexer
{
    private Database $db;

    /** All indexable content types (source content_type values). */
    public const TYPES = [
        'page',
        'service',
        'portfolio',
        'blog_post',
        'content_entry',
        'global_block',
        'service_faq',
        'portfolio_faq',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ================================================================
       Public API
       ================================================================ */

    /**
     * Rebuild the entire search index from all published content.
     *
     * @return array{indexed:int} Number of rows inserted
     */
    public function buildAll(): array
    {
        $this->db->query('TRUNCATE TABLE search_index');

        $indexed = 0;
        foreach (self::TYPES as $type) {
            $indexed += $this->rebuildType($type);
        }

        return ['indexed' => $indexed];
    }

    /**
     * Rebuild one content type (optionally a single item).
     */
    public function rebuildType(string $type, ?int $id = null): int
    {
        if (!in_array($type, self::TYPES, true)) {
            return 0;
        }

        $rows = $this->collectRows($type, $id);

        if ($id !== null) {
            $this->removeItem($type, $id);
        } else {
            $this->db->delete('search_index', 'content_type = ?', [$type]);
        }

        $count = 0;
        foreach ($rows as $row) {
            $this->upsert($type, $row);
            $count++;
        }

        return $count;
    }

    /**
     * Index (upsert) a single content item. Removes the item from the
     * index when it is not currently published.
     */
    public function indexItem(string $type, int $id): void
    {
        if (!in_array($type, self::TYPES, true) || $id <= 0) {
            return;
        }

        $rows = $this->collectRows($type, $id);

        if (empty($rows)) {
            $this->removeItem($type, $id);
            return;
        }

        $this->upsert($type, $rows[0]);
    }

    /**
     * Remove a single content item from the index.
     */
    public function removeItem(string $type, int $id): void
    {
        $this->db->delete('search_index', 'content_type = ? AND content_id = ?', [$type, $id]);
    }

    /**
     * Count indexed rows.
     */
    public function countIndexed(?string $type = null): int
    {
        if ($type !== null) {
            return $this->db->count('search_index', 'content_type = ?', [$type]);
        }
        return $this->db->count('search_index');
    }

    /* ================================================================
       Index write helpers
       ================================================================ */

    /**
     * Insert or update an index row (unique on content_type + content_id).
     */
    private function upsert(string $type, array $row): void
    {
        $exists = $this->db->fetchColumn(
            'SELECT id FROM search_index WHERE content_type = ? AND content_id = ?',
            [$type, (int)$row['content_id']]
        );

        $data = [
            'content_id'   => (int)$row['content_id'],
            'title'        => $this->cleanText($row['title'] ?? '', 255),
            'excerpt'      => $this->cleanText($row['excerpt'] ?? '', 1000),
            'content'      => $this->cleanText($row['content'] ?? '', 60000),
            'keywords'     => $this->cleanText($row['keywords'] ?? '', 500),
            'url'          => $row['url'] ?? '',
            'image'        => $row['image'] ?? '',
            'category'     => $this->cleanText($row['category'] ?? '', 150),
            'tags'         => $this->cleanText($row['tags'] ?? '', 500),
            'is_featured'  => !empty($row['is_featured']) ? 1 : 0,
            'published_at' => $row['published_at'] ?? null,
        ];

        if ($exists) {
            $this->db->update('search_index', $data, 'content_type = ? AND content_id = ?', [$type, (int)$row['content_id']]);
        } else {
            $data['content_type'] = $type;
            $this->db->insert('search_index', $data);
        }
    }

    /* ================================================================
       Collectors (return normalized rows for the index)
       ================================================================ */

    private function collectRows(string $type, ?int $id): array
    {
        switch ($type) {
            case 'page':          return $this->collectPages($id);
            case 'service':       return $this->collectServices($id);
            case 'portfolio':     return $this->collectPortfolio($id);
            case 'blog_post':     return $this->collectBlogPosts($id);
            case 'content_entry': return $this->collectContentEntries($id);
            case 'global_block':  return $this->collectGlobalBlocks($id);
            case 'service_faq':   return $this->collectServiceFaqs($id);
            case 'portfolio_faq': return $this->collectPortfolioFaqs($id);
        }
        return [];
    }

    private function collectPages(?int $id): array
    {
        $sql = "SELECT id, slug, title, meta_desc AS excerpt, content, is_homepage, created_at
                FROM pages WHERE status = 'published'";
        $params = [];
        if ($id !== null) {
            $sql .= ' AND id = ?';
            $params[] = $id;
        }

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];

        foreach ($rows as $r) {
            $result[] = [
                'content_id'   => (int)$r['id'],
                'title'        => $r['title'],
                'excerpt'      => $r['excerpt'],
                'content'      => $r['content'],
                'keywords'     => '',
                'url'          => SITE_URL . '/' . $r['slug'],
                'image'        => '',
                'category'     => '',
                'tags'         => '',
                'is_featured'  => (int)$r['is_homepage'],
                'published_at' => $r['created_at'],
            ];
        }

        return $result;
    }

    private function collectServices(?int $id): array
    {
        $sql = "SELECT id, slug, title, short_description, full_description, category, featured_image,
                       hero_description, overview_intro, overview_benefits, overview_why,
                       cta_heading, cta_description, focus_keyword, created_at
                FROM services WHERE status = 'published'";
        $params = [];
        if ($id !== null) {
            $sql .= ' AND id = ?';
            $params[] = $id;
        }

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];

        foreach ($rows as $r) {
            $body = implode("\n", array_filter([
                $r['full_description'],
                $r['hero_description'],
                $r['overview_intro'],
                $r['overview_benefits'],
                $r['overview_why'],
                $r['cta_heading'],
                $r['cta_description'],
            ]));

            $result[] = [
                'content_id'   => (int)$r['id'],
                'title'        => $r['title'],
                'excerpt'      => $r['short_description'],
                'content'      => $body,
                'keywords'     => $r['focus_keyword'],
                'url'          => SITE_URL . '/services/' . $r['slug'],
                'image'        => $r['featured_image'],
                'category'     => $r['category'],
                'tags'         => '',
                'is_featured'  => 0,
                'published_at' => $r['created_at'],
            ];
        }

        return $result;
    }

    private function collectPortfolio(?int $id): array
    {
        $sql = "SELECT id, slug, title, description, full_description, category, industry, image_url,
                       hero_title, hero_description, overview_summary, overview_problem, overview_objectives,
                       solution_strategy, solution_branding, solution_process, solution_tech,
                       results_summary, results_achievements, focus_keyword, is_featured,
                       completion_date, created_at
                FROM portfolio_items WHERE status = 'published'";
        $params = [];
        if ($id !== null) {
            $sql .= ' AND id = ?';
            $params[] = $id;
        }

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];

        foreach ($rows as $r) {
            $body = implode("\n", array_filter([
                $r['full_description'],
                $r['hero_title'],
                $r['hero_description'],
                $r['overview_summary'],
                $r['overview_problem'],
                $r['overview_objectives'],
                $r['solution_strategy'],
                $r['solution_branding'],
                $r['solution_process'],
                $r['solution_tech'],
                $r['results_summary'],
                $r['results_achievements'],
            ]));

            $result[] = [
                'content_id'   => (int)$r['id'],
                'title'        => $r['title'],
                'excerpt'      => $r['description'],
                'content'      => $body,
                'keywords'     => trim(($r['focus_keyword'] ?? '') . ' ' . ($r['industry'] ?? '')),
                'url'          => SITE_URL . '/portfolio/' . $r['slug'],
                'image'        => $r['image_url'],
                'category'     => $r['category'],
                'tags'         => '',
                'is_featured'  => (int)$r['is_featured'],
                'published_at' => $r['completion_date'] ?: $r['created_at'],
            ];
        }

        return $result;
    }

    private function collectBlogPosts(?int $id): array
    {
        $sql = "SELECT id, slug, title, short_description, content, featured_image,
                       focus_keyword, is_featured, published_at, created_at
                FROM blog_posts WHERE status = 'published'";
        $params = [];
        if ($id !== null) {
            $sql .= ' AND id = ?';
            $params[] = $id;
        }

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];

        /* Bulk taxonomy lookup for full rebuilds */
        $catsByPost = [];
        $tagsByPost = [];
        if ($id === null && !empty($rows)) {
            $catsByPost = $this->bulkPostTaxonomy('blog_categories', 'blog_post_categories', array_column($rows, 'id'));
            $tagsByPost = $this->bulkPostTaxonomy('blog_tags', 'blog_post_tags', array_column($rows, 'id'));
        }

        foreach ($rows as $r) {
            if ($id !== null) {
                $catsByPost[$r['id']] = $this->itemTaxonomy('blog_categories', 'blog_post_categories', (int)$r['id']);
                $tagsByPost[$r['id']] = $this->itemTaxonomy('blog_tags', 'blog_post_tags', (int)$r['id']);
            }

            $result[] = [
                'content_id'   => (int)$r['id'],
                'title'        => $r['title'],
                'excerpt'      => $r['short_description'],
                'content'      => $r['content'],
                'keywords'     => $r['focus_keyword'],
                'url'          => SITE_URL . '/blog/' . $r['slug'],
                'image'        => $r['featured_image'],
                'category'     => implode(', ', $catsByPost[$r['id']] ?? []),
                'tags'         => implode(', ', $tagsByPost[$r['id']] ?? []),
                'is_featured'  => (int)$r['is_featured'],
                'published_at' => $r['published_at'] ?: $r['created_at'],
            ];
        }

        return $result;
    }

    private function collectContentEntries(?int $id): array
    {
        $sql = "SELECT ce.id, ce.title, ce.slug, ce.excerpt, ce.fields, ce.featured_image,
                       ce.created_at, ct.slug AS type_slug, ct.name AS type_name
                FROM content_entries ce
                JOIN content_types ct ON ce.content_type_id = ct.id
                WHERE ce.status = 'published'";
        $params = [];
        if ($id !== null) {
            $sql .= ' AND ce.id = ?';
            $params[] = $id;
        }

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];

        foreach ($rows as $r) {
            $result[] = [
                'content_id'   => (int)$r['id'],
                'title'        => $r['title'],
                'excerpt'      => $r['excerpt'],
                'content'      => $this->flattenJsonText($r['fields'] ?? ''),
                'keywords'     => '',
                'url'          => SITE_URL . '/' . $r['type_slug'] . '/' . $r['slug'],
                'image'        => $r['featured_image'],
                'category'     => $r['type_name'],
                'tags'         => '',
                'is_featured'  => 0,
                'published_at' => $r['created_at'],
            ];
        }

        return $result;
    }

    private function collectGlobalBlocks(?int $id): array
    {
        $sql = "SELECT id, name AS title, slug, description AS excerpt, content, block_type, category, updated_at
                FROM global_blocks WHERE status = 'published'";
        $params = [];
        if ($id !== null) {
            $sql .= ' AND id = ?';
            $params[] = $id;
        }

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];

        foreach ($rows as $r) {
            $result[] = [
                'content_id'   => (int)$r['id'],
                'title'        => $r['title'],
                'excerpt'      => $r['excerpt'],
                'content'      => $r['block_type'] . "\n" . $this->flattenJsonText($r['content'] ?? ''),
                'keywords'     => '',
                'url'          => SITE_URL . '#block-' . $r['slug'],
                'image'        => '',
                'category'     => $r['category'],
                'tags'         => '',
                'is_featured'  => 0,
                'published_at' => $r['updated_at'],
            ];
        }

        return $result;
    }

    private function collectServiceFaqs(?int $id): array
    {
        $sql = "SELECT f.id, f.question, f.answer, s.slug AS service_slug, s.title AS service_title
                FROM service_faqs f
                JOIN services s ON f.service_id = s.id
                WHERE s.status = 'published'";
        $params = [];
        if ($id !== null) {
            $sql .= ' AND f.id = ?';
            $params[] = $id;
        }

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];

        foreach ($rows as $r) {
            $result[] = [
                'content_id'   => (int)$r['id'],
                'title'        => $r['question'],
                'excerpt'      => '',
                'content'      => $r['answer'],
                'keywords'     => '',
                'url'          => SITE_URL . '/services/' . $r['service_slug'] . '#faq',
                'image'        => '',
                'category'     => $r['service_title'],
                'tags'         => '',
                'is_featured'  => 0,
                'published_at' => null,
            ];
        }

        return $result;
    }

    private function collectPortfolioFaqs(?int $id): array
    {
        $sql = "SELECT f.id, f.question, f.answer, p.slug AS project_slug, p.title AS project_title
                FROM portfolio_faqs f
                JOIN portfolio_items p ON f.portfolio_id = p.id
                WHERE p.status = 'published'";
        $params = [];
        if ($id !== null) {
            $sql .= ' AND f.id = ?';
            $params[] = $id;
        }

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];

        foreach ($rows as $r) {
            $result[] = [
                'content_id'   => (int)$r['id'],
                'title'        => $r['question'],
                'excerpt'      => '',
                'content'      => $r['answer'],
                'keywords'     => '',
                'url'          => SITE_URL . '/portfolio/' . $r['project_slug'] . '#faq',
                'image'        => '',
                'category'     => $r['project_title'],
                'tags'         => '',
                'is_featured'  => 0,
                'published_at' => null,
            ];
        }

        return $result;
    }

    /* ================================================================
       Taxonomy + text helpers
       ================================================================ */

    private function bulkPostTaxonomy(string $taxTable, string $linkTable, array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $fk = $taxTable === 'blog_categories' ? 'category_id' : 'tag_id';

        $rows = $this->db->fetchAll(
            "SELECT t.name, l.post_id FROM {$taxTable} t
             JOIN {$linkTable} l ON t.id = l.{$fk}
             WHERE l.post_id IN ({$placeholders})",
            $postIds
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['post_id']][] = $row['name'];
        }
        return $grouped;
    }

    private function itemTaxonomy(string $taxTable, string $linkTable, int $postId): array
    {
        $fk = $taxTable === 'blog_categories' ? 'category_id' : 'tag_id';

        return array_column(
            $this->db->fetchAll(
                "SELECT t.name FROM {$taxTable} t
                 JOIN {$linkTable} l ON t.id = l.{$fk}
                 WHERE l.post_id = ? ORDER BY t.name",
                [$postId]
            ),
            'name'
        );
    }

    /**
     * Flatten a JSON-encoded field set (content_entries.fields or a
     * global block content JSON) into searchable plain text.
     */
    private function flattenJsonText(string $json): string
    {
        if ($json === '' || $json === null) {
            return '';
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return $json;
        }

        $parts = [];
        array_walk_recursive($decoded, function ($value) use (&$parts) {
            if (is_scalar($value) && trim((string)$value) !== '') {
                $parts[] = (string)$value;
            }
        });

        return implode("\n", $parts);
    }

    /**
     * Normalize text for the index: strip HTML, decode entities,
     * collapse whitespace, and enforce a length cap.
     */
    private function cleanText(?string $text, int $maxLength = 60000): string
    {
        if ($text === null) {
            return '';
        }

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }

        return trim($text);
    }
}
