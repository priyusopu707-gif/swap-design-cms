<?php
/**
 * Swap Design - Blog Manager
 *
 * Full CRUD for blog posts, categories, tags, taxonomy relationships,
 * revision history, and related content.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class BlogManager
{
    private Database $db;

    public const STATUSES = ['draft', 'published', 'scheduled', 'archived'];

    public const STATUS_LABELS = [
        'draft'     => 'Draft',
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'archived'  => 'Archived',
    ];

    public const STATUS_COLORS = [
        'draft'     => '#f59e0b',
        'published' => '#22c55e',
        'scheduled' => '#3b82f6',
        'archived'  => '#6b7280',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Slugs whose canonical URL uses the /website/ prefix. */
    private const WEBSITE_PREFIX_SLUGS = [
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

    /**
     * Get the canonical URL path for a blog post (uses /website/ or /blog/ prefix).
     *
     * @param array|string $post Post array (with 'slug') or a slug string
     * @return string Relative URL path, e.g. '/website/my-post/' or '/blog/my-post/'
     */
    public static function getPostUrl($post): string
    {
        $slug = is_array($post) ? ($post['slug'] ?? '') : (string)$post;
        $prefix = in_array($slug, self::WEBSITE_PREFIX_SLUGS, true) ? 'website' : 'blog';
        return '/' . $prefix . '/' . $slug . '/';
    }

    /* ================================================================
       Posts - Create
       ================================================================ */

    public function createPost(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        $postId = (int)$this->db->insert('blog_posts', [
            'title'            => substr($data['title'] ?? '', 0, 255),
            'slug'             => $this->uniqueSlug($data['slug'] ?? '', $data['title'] ?? ''),
            'short_description'=> $data['short_description'] ?? '',
            'content'          => $data['content'] ?? '',
            'featured_image'   => $data['featured_image'] ?? '',
            'gallery'          => !empty($data['gallery']) ? json_encode($data['gallery'], JSON_UNESCAPED_UNICODE) : null,
            'author_id'        => !empty($data['author_id']) ? (int)$data['author_id'] : null,
            'published_at'     => $data['published_at'] ?? null,
            'status'           => in_array($data['status'] ?? 'draft', self::STATUSES, true) ? $data['status'] : 'draft',
            'reading_time'     => self::calculateReadingTime($data['content'] ?? ''),
            'is_featured'      => !empty($data['is_featured']) ? 1 : 0,
            'is_sticky'        => !empty($data['is_sticky']) ? 1 : 0,
            'seo_title'        => $data['seo_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'focus_keyword'    => $data['focus_keyword'] ?? '',
            'canonical_url'    => $data['canonical_url'] ?? '',
            'og_image'         => $data['og_image'] ?? '',
            'twitter_card'     => $data['twitter_card'] ?? 'summary_large_image',
        ]);

        searchIndexer()?->indexItem('blog_post', $postId);
        sitemapGenerator()?->regenerate();

        if (class_exists('CacheInvalidator')) {
            CacheInvalidator::invalidateBlog($postId);
        }

        return $postId;
    }

    public function updatePost(int $id, array $data): void
    {
        $fields = [
            'title'            => substr($data['title'] ?? '', 0, 255),
            'short_description'=> $data['short_description'] ?? '',
            'content'          => $data['content'] ?? '',
            'featured_image'   => $data['featured_image'] ?? '',
            'gallery'          => !empty($data['gallery']) ? json_encode($data['gallery'], JSON_UNESCAPED_UNICODE) : null,
            'author_id'        => !empty($data['author_id']) ? (int)$data['author_id'] : null,
            'published_at'     => $data['published_at'] ?? null,
            'reading_time'     => self::calculateReadingTime($data['content'] ?? ''),
            'is_featured'      => !empty($data['is_featured']) ? 1 : 0,
            'is_sticky'        => !empty($data['is_sticky']) ? 1 : 0,
            'seo_title'        => $data['seo_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'focus_keyword'    => $data['focus_keyword'] ?? '',
            'canonical_url'    => $data['canonical_url'] ?? '',
            'og_image'         => $data['og_image'] ?? '',
            'twitter_card'     => $data['twitter_card'] ?? 'summary_large_image',
        ];

        if (!empty($data['slug'])) {
            $fields['slug'] = $this->uniqueSlug($data['slug'], $data['title'] ?? '', $id);
        }

        if (isset($data['status']) && in_array($data['status'], self::STATUSES, true)) {
            $fields['status'] = $data['status'];
        }

        $this->db->update('blog_posts', $fields, 'id = ?', [$id]);

        searchIndexer()?->indexItem('blog_post', $id);
        sitemapGenerator()?->regenerate();

        if (class_exists('CacheInvalidator')) {
            CacheInvalidator::invalidateBlog($id);
        }
    }

    /* ================================================================
       Posts - Read
       ================================================================ */

    public function getPostById(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM blog_posts WHERE id = ?", [$id]);
        if ($row) {
            $row = $this->hydratePost($row);
        }
        return $row ?: null;
    }

    public function getPostBySlug(string $slug): ?array
    {
        $row = $this->db->fetch("SELECT * FROM blog_posts WHERE slug = ?", [$slug]);
        if ($row) {
            $row = $this->hydratePost($row);
        }
        return $row ?: null;
    }

    public function getAllPosts(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $sql = "SELECT * FROM blog_posts" . ($where ? " WHERE $where" : '') . " ORDER BY is_sticky DESC, published_at DESC";
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT $perPage OFFSET $offset";

        $rows = $this->db->fetchAll($sql, $params);
        return array_map([$this, 'hydratePost'], $rows);
    }

    public function countPosts(array $filters = []): int
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $sql = "SELECT COUNT(*) FROM blog_posts" . ($where ? " WHERE $where" : '');
        return (int)$this->db->fetchColumn($sql, $params);
    }

    public function getPublishedPosts(int $page = 1, int $perPage = 12): array
    {
        return $this->getAllPosts(['status' => 'published'], $page, $perPage);
    }

    public function countPublished(): int
    {
        return $this->countPosts(['status' => 'published']);
    }

    public function getPostsByCategory(string $categorySlug, int $page = 1, int $perPage = 12): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.* FROM blog_posts p
                JOIN blog_post_categories bpc ON p.id = bpc.post_id
                JOIN blog_categories c ON bpc.category_id = c.id
                WHERE c.slug = ? AND p.status = 'published'
                ORDER BY p.is_sticky DESC, p.published_at DESC
                LIMIT $perPage OFFSET $offset";
        $rows = $this->db->fetchAll($sql, [$categorySlug]);
        return array_map([$this, 'hydratePost'], $rows);
    }

    public function countPostsByCategory(string $categorySlug): int
    {
        $sql = "SELECT COUNT(*) FROM blog_posts p
                JOIN blog_post_categories bpc ON p.id = bpc.post_id
                JOIN blog_categories c ON bpc.category_id = c.id
                WHERE c.slug = ? AND p.status = 'published'";
        return (int)$this->db->fetchColumn($sql, [$categorySlug]);
    }

    public function getPostsByTag(string $tagSlug, int $page = 1, int $perPage = 12): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.* FROM blog_posts p
                JOIN blog_post_tags bpt ON p.id = bpt.post_id
                JOIN blog_tags t ON bpt.tag_id = t.id
                WHERE t.slug = ? AND p.status = 'published'
                ORDER BY p.is_sticky DESC, p.published_at DESC
                LIMIT $perPage OFFSET $offset";
        $rows = $this->db->fetchAll($sql, [$tagSlug]);
        return array_map([$this, 'hydratePost'], $rows);
    }

    public function countPostsByTag(string $tagSlug): int
    {
        $sql = "SELECT COUNT(*) FROM blog_posts p
                JOIN blog_post_tags bpt ON p.id = bpt.post_id
                JOIN blog_tags t ON bpt.tag_id = t.id
                WHERE t.slug = ? AND p.status = 'published'";
        return (int)$this->db->fetchColumn($sql, [$tagSlug]);
    }

    public function searchPosts(string $query, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $like = "%$query%";
        $sql = "SELECT * FROM blog_posts WHERE (title LIKE ? OR short_description LIKE ? OR content LIKE ?) AND status = 'published'
                ORDER BY published_at DESC LIMIT $perPage OFFSET $offset";
        $rows = $this->db->fetchAll($sql, [$like, $like, $like]);
        return array_map([$this, 'hydratePost'], $rows);
    }

    public function searchPostsCount(string $query): int
    {
        $like = "%$query%";
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM blog_posts WHERE (title LIKE ? OR short_description LIKE ? OR content LIKE ?) AND status = 'published'",
            [$like, $like, $like]
        );
    }

    public function getRelatedPosts(int $postId, int $limit = 3): array
    {
        /* Try same-category posts first */
        $catSql = "SELECT bpc.category_id FROM blog_post_categories bpc WHERE bpc.post_id = ?";
        $catIds = $this->db->fetchAll($catSql, [$postId]);
        $catIdList = array_column($catIds, 'category_id');

        if ($catIdList) {
            $placeholders = implode(',', array_fill(0, count($catIdList), '?'));
            $params = array_merge([$postId], $catIdList);
            $sql = "SELECT DISTINCT p.* FROM blog_posts p
                    JOIN blog_post_categories bpc ON p.id = bpc.post_id
                    WHERE p.id != ? AND bpc.category_id IN ($placeholders) AND p.status = 'published'
                    ORDER BY p.published_at DESC LIMIT $limit";
            $rows = $this->db->fetchAll($sql, $params);
            if ($rows) {
                return array_map([$this, 'hydratePost'], $rows);
            }
        }

        /* Fallback: recent posts */
        $rows = $this->db->fetchAll(
            "SELECT * FROM blog_posts WHERE id != ? AND status = 'published' ORDER BY published_at DESC LIMIT $limit",
            [$postId]
        );
        return array_map([$this, 'hydratePost'], $rows);
    }

    public function getPreviousPost(int $postId): ?array
    {
        $current = $this->getPostById($postId);
        if (!$current || !$current['published_at']) return null;
        $row = $this->db->fetch(
            "SELECT * FROM blog_posts WHERE status = 'published' AND published_at < ? AND id != ? ORDER BY published_at DESC LIMIT 1",
            [$current['published_at'], $postId]
        );
        return $row ? $this->hydratePost($row) : null;
    }

    public function getNextPost(int $postId): ?array
    {
        $current = $this->getPostById($postId);
        if (!$current || !$current['published_at']) return null;
        $row = $this->db->fetch(
            "SELECT * FROM blog_posts WHERE status = 'published' AND published_at > ? AND id != ? ORDER BY published_at ASC LIMIT 1",
            [$current['published_at'], $postId]
        );
        return $row ? $this->hydratePost($row) : null;
    }

    /* ================================================================
       Posts - Update helpers
       ================================================================ */

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) return;
        $data = ['status' => $status];
        if ($status === 'published' && empty($this->getPostById($id)['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        $this->db->update('blog_posts', $data, 'id = ?', [$id]);

        searchIndexer()?->indexItem('blog_post', $id);
        sitemapGenerator()?->regenerate();

        if (class_exists('CacheInvalidator')) {
            CacheInvalidator::invalidateBlog($id);
        }
    }

    public function deletePost(int $id): void
    {
        $this->db->query("DELETE FROM blog_posts WHERE id = ?", [$id]);

        searchIndexer()?->removeItem('blog_post', $id);
        sitemapGenerator()?->regenerate();

        if (class_exists('CacheInvalidator')) {
            CacheInvalidator::invalidateBlog($id);
        }
    }

    public function duplicatePost(int $id): int
    {
        $original = $this->getPostById($id);
        if (!$original) return 0;

        $newData = $original;
        $newData['title'] = $original['title'] . ' (Copy)';
        $newData['slug'] = '';
        $newData['status'] = 'draft';
        $newData['is_featured'] = 0;
        $newData['is_sticky'] = 0;
        $newData['view_count'] = 0;
        unset($newData['id'], $newData['created_at'], $newData['updated_at']);

        $newId = $this->createPost($newData);

        /* Duplicate taxonomy */
        $cats = $this->db->fetchAll("SELECT category_id FROM blog_post_categories WHERE post_id = ?", [$id]);
        foreach ($cats as $c) {
            $this->db->insert('blog_post_categories', ['post_id' => $newId, 'category_id' => $c['category_id']]);
        }
        $tags = $this->db->fetchAll("SELECT tag_id FROM blog_post_tags WHERE post_id = ?", [$id]);
        foreach ($tags as $t) {
            $this->db->insert('blog_post_tags', ['post_id' => $newId, 'tag_id' => $t['tag_id']]);
        }

        return $newId;
    }

    public function incrementViews(int $id): void
    {
        $this->db->query("UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?", [$id]);
    }

    /* ================================================================
       Revisions
       ================================================================ */

    public function saveRevision(int $postId, int $userId = null, string $note = ''): int
    {
        $post = $this->getPostById($postId);
        if (!$post) return 0;
        return (int)$this->db->insert('blog_revisions', [
            'post_id'           => $postId,
            'title'             => $post['title'],
            'content'           => $post['content'],
            'short_description' => $post['short_description'],
            'revision_note'     => $note ?: null,
            'saved_by'          => $userId ?: null,
        ]);
    }

    public function getRevisions(int $postId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM blog_revisions WHERE post_id = ? ORDER BY created_at DESC",
            [$postId]
        );
    }

    public function restoreRevision(int $revisionId): bool
    {
        $rev = $this->db->fetch("SELECT * FROM blog_revisions WHERE id = ?", [$revisionId]);
        if (!$rev) return false;
        $this->db->update('blog_posts', [
            'title'             => $rev['title'],
            'content'           => $rev['content'],
            'short_description' => $rev['short_description'],
        ], 'id = ?', [$rev['post_id']]);
        return true;
    }

    /* ================================================================
       Categories
       ================================================================ */

    public function getAllCategories(): array
    {
        return $this->db->fetchAll("SELECT c.*, (SELECT COUNT(*) FROM blog_post_categories bpc WHERE bpc.category_id = c.id) AS post_count FROM blog_categories c ORDER BY c.sort_order ASC, c.name ASC");
    }

    public function getCategoryBySlug(string $slug): ?array
    {
        return $this->db->fetch("SELECT * FROM blog_categories WHERE slug = ?", [$slug]);
    }

    public function getCategoryById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM blog_categories WHERE id = ?", [$id]);
    }

    public function createCategory(array $data): int
    {
        $slug = $this->uniqueCategorySlug($data['name'] ?? '');
        return (int)$this->db->insert('blog_categories', [
            'name'        => substr($data['name'] ?? '', 0, 150),
            'slug'        => $slug,
            'description' => $data['description'] ?? '',
            'parent_id'   => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            'sort_order'  => (int)($data['sort_order'] ?? 0),
        ]);
    }

    public function updateCategory(int $id, array $data): void
    {
        $fields = [
            'name'        => substr($data['name'] ?? '', 0, 150),
            'description' => $data['description'] ?? '',
            'parent_id'   => !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            'sort_order'  => (int)($data['sort_order'] ?? 0),
        ];
        if (!empty($data['slug'])) {
            $fields['slug'] = $this->uniqueCategorySlug($data['name'], $id);
        }
        $this->db->update('blog_categories', $fields, 'id = ?', [$id]);
    }

    public function deleteCategory(int $id): void
    {
        $this->db->query("DELETE FROM blog_categories WHERE id = ?", [$id]);
    }

    /* ================================================================
       Tags
       ================================================================ */

    public function getAllTags(): array
    {
        return $this->db->fetchAll("SELECT t.*, (SELECT COUNT(*) FROM blog_post_tags bpt WHERE bpt.tag_id = t.id) AS post_count FROM blog_tags t ORDER BY t.name ASC");
    }

    public function getTagBySlug(string $slug): ?array
    {
        return $this->db->fetch("SELECT * FROM blog_tags WHERE slug = ?", [$slug]);
    }

    public function getTagById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM blog_tags WHERE id = ?", [$id]);
    }

    public function createTag(array $data): int
    {
        $slug = $this->uniqueTagSlug($data['name'] ?? '');
        return (int)$this->db->insert('blog_tags', [
            'name' => substr($data['name'] ?? '', 0, 100),
            'slug' => $slug,
        ]);
    }

    public function updateTag(int $id, array $data): void
    {
        $this->db->update('blog_tags', [
            'name' => substr($data['name'] ?? '', 0, 100),
        ], 'id = ?', [$id]);
    }

    public function deleteTag(int $id): void
    {
        $this->db->query("DELETE FROM blog_tags WHERE id = ?", [$id]);
    }

    /* ================================================================
       Taxonomy sync (post categories/tags)
       ================================================================ */

    public function getPostCategories(int $postId): array
    {
        return $this->db->fetchAll(
            "SELECT c.* FROM blog_categories c JOIN blog_post_categories bpc ON c.id = bpc.category_id WHERE bpc.post_id = ? ORDER BY c.name",
            [$postId]
        );
    }

    public function getPostTags(int $postId): array
    {
        return $this->db->fetchAll(
            "SELECT t.* FROM blog_tags t JOIN blog_post_tags bpt ON t.id = bpt.tag_id WHERE bpt.post_id = ? ORDER BY t.name",
            [$postId]
        );
    }

    public function syncPostCategories(int $postId, array $categoryIds): void
    {
        $this->db->query("DELETE FROM blog_post_categories WHERE post_id = ?", [$postId]);
        foreach ($categoryIds as $catId) {
            $this->db->insert('blog_post_categories', ['post_id' => $postId, 'category_id' => (int)$catId]);
        }

        searchIndexer()?->indexItem('blog_post', $postId);
        sitemapGenerator()?->regenerate();
    }

    public function syncPostTags(int $postId, array $tagNames): void
    {
        $this->db->query("DELETE FROM blog_post_tags WHERE post_id = ?", [$postId]);
        foreach ($tagNames as $name) {
            $name = trim($name);
            if (empty($name)) continue;
            $tag = $this->db->fetch("SELECT id FROM blog_tags WHERE name = ?", [$name]);
            if (!$tag) {
                $tagId = $this->createTag(['name' => $name]);
            } else {
                $tagId = $tag['id'];
            }
            $this->db->insert('blog_post_tags', ['post_id' => $postId, 'tag_id' => (int)$tagId]);
        }

        searchIndexer()?->indexItem('blog_post', $postId);
        sitemapGenerator()?->regenerate();
    }

    /* ================================================================
       Related content
       ================================================================ */

    public function syncRelationships(int $postId, string $type, array $relatedIds): void
    {
        $this->db->query("DELETE FROM blog_relationships WHERE post_id = ? AND related_type = ?", [$postId, $type]);
        $order = 0;
        foreach ($relatedIds as $rid) {
            $this->db->insert('blog_relationships', [
                'post_id'      => $postId,
                'related_type' => $type,
                'related_id'   => (int)$rid,
                'sort_order'   => $order++,
            ]);
        }
    }

    public function getRelationships(int $postId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM blog_relationships WHERE post_id = ? ORDER BY related_type, sort_order",
            [$postId]
        );
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['related_type']][] = (int)$row['related_id'];
        }
        return $grouped;
    }

    /* ================================================================
       Helpers
       ================================================================ */

    public static function calculateReadingTime(string $content): int
    {
        $text  = strip_tags($content);
        $words = str_word_count($text, 0);
        return max(1, (int)ceil($words / 200));
    }

    private function uniqueSlug(string $slug, string $title, ?int $excludeId = null): string
    {
        if (empty($slug)) {
            $slug = self::slugify($title);
        } else {
            $slug = self::slugify($slug);
        }

        $baseSlug = $slug;
        $counter = 1;
        while (true) {
            $sql = "SELECT id FROM blog_posts WHERE slug = ?";
            $params = [$slug];
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $exists = $this->db->fetchColumn($sql, $params);
            if (!$exists) break;
            $slug = $baseSlug . '-' . ($counter++);
        }
        return $slug;
    }

    private function uniqueCategorySlug(string $name, ?int $excludeId = null): string
    {
        $slug = self::slugify($name);
        $baseSlug = $slug;
        $counter = 1;
        while (true) {
            $sql = "SELECT id FROM blog_categories WHERE slug = ?";
            $params = [$slug];
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $exists = $this->db->fetchColumn($sql, $params);
            if (!$exists) break;
            $slug = $baseSlug . '-' . ($counter++);
        }
        return $slug;
    }

    private function uniqueTagSlug(string $name): string
    {
        $slug = self::slugify($name);
        $baseSlug = $slug;
        $counter = 1;
        while ($this->db->fetchColumn("SELECT id FROM blog_tags WHERE slug = ?", [$slug])) {
            $slug = $baseSlug . '-' . ($counter++);
        }
        return $slug;
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s]+/', '-', $text);
        return trim($text, '-');
    }

    private function hydratePost(array $row): array
    {
        $row['gallery'] = $row['gallery'] ? json_decode($row['gallery'], true) : [];
        return $row;
    }

    private function buildFilterClause(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['author_id'])) {
            $conditions[] = 'author_id = ?';
            $params[] = (int)$filters['author_id'];
        }
        if (!empty($filters['category_id'])) {
            $conditions[] = 'id IN (SELECT post_id FROM blog_post_categories WHERE category_id = ?)';
            $params[] = (int)$filters['category_id'];
        }
        if (isset($filters['is_featured'])) {
            $conditions[] = 'is_featured = ?';
            $params[] = $filters['is_featured'] ? 1 : 0;
        }
        if (isset($filters['is_sticky'])) {
            $conditions[] = 'is_sticky = ?';
            $params[] = $filters['is_sticky'] ? 1 : 0;
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $conditions[] = '(title LIKE ? OR short_description LIKE ? OR content LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return [implode(' AND ', $conditions), $params];
    }
}
