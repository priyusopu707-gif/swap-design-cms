<?php
/**
 * Swap Design - Portfolio Manager
 *
 * Full CRUD for portfolio_items table and sub-tables:
 *  - portfolio_gallery
 *  - portfolio_testimonials
 *  - portfolio_related_services
 *  - portfolio_faqs
 *  - portfolio_related_blocks
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class PortfolioManager
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ================================================================
       PORTFOLIO CRUD
       ================================================================ */

    public function getAll(array $filters = []): array
    {
        $sql  = "SELECT * FROM portfolio_items WHERE 1=1";
        $args = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $args[] = $filters['status'];
        }
        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $args[] = $filters['category'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR client_name LIKE ?)";
            $kw = '%' . $filters['search'] . '%';
            $args[] = $kw;
            $args[] = $kw;
            $args[] = $kw;
        }
        if (!empty($filters['featured'])) {
            $sql .= " AND is_featured = 1";
        }

        $sql .= " ORDER BY is_featured DESC, sort_order ASC, created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int)$filters['offset'];
            }
        }

        return $this->db->fetchAll($sql, $args);
    }

    public function count(array $filters = []): int
    {
        $sql  = "SELECT COUNT(*) FROM portfolio_items WHERE 1=1";
        $args = [];
        if (!empty($filters['status'])) { $sql .= " AND status = ?"; $args[] = $filters['status']; }
        if (!empty($filters['category'])) { $sql .= " AND category = ?"; $args[] = $filters['category']; }
        return (int)$this->db->fetchColumn($sql, $args, 0);
    }

    public function getById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM portfolio_items WHERE id = ?", [$id]) ?: null;
    }

    public function getBySlug(string $slug): ?array
    {
        return $this->db->fetch("SELECT * FROM portfolio_items WHERE slug = ? AND status = 'published'", [$slug]) ?: null;
    }

    /**
     * Get a portfolio item with all sub-data for frontend rendering.
     */
    public function getFullBySlug(string $slug): ?array
    {
        $item = $this->getBySlug($slug);
        if (!$item) return null;

        $pid = (int)$item['id'];

        $item['gallery']         = $this->getGallery($pid);
        $item['testimonials']    = $this->getTestimonialEntries($pid);
        $item['related_services']= $this->getRelatedServices($pid);
        $item['faqs']            = $this->getFaqs($pid);
        $item['blocks']          = $this->getRelatedBlocks($pid);
        $item['related_blogs']   = $this->getRelatedBlogs($pid);

        return $item;
    }

    /**
     * Get related projects (same category, excluding current).
     */
    public function getRelatedProjects(int $portfolioId, string $category, int $limit = 3): array
    {
        return $this->db->fetchAll(
            "SELECT id, title, slug, image_url, category
             FROM portfolio_items
             WHERE category = ? AND id != ? AND status = 'published'
             ORDER BY is_featured DESC, sort_order ASC
             LIMIT ?",
            [$category, $portfolioId, $limit]
        );
    }

    public function create(array $data): int
    {
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title'] ?? 'project');
        $portfolioId = (int)$this->db->insert('portfolio_items', $data);

        searchIndexer()?->indexItem('portfolio', $portfolioId);
        sitemapGenerator()?->regenerate();

        return $portfolioId;
    }

    public function update(int $id, array $data): bool
    {
        unset($data['id']);
        $this->db->update('portfolio_items', $data, 'id = ?', [$id]);

        searchIndexer()?->indexItem('portfolio', $id);
        sitemapGenerator()?->regenerate();

        return true;
    }

    public function duplicate(int $id): ?int
    {
        $original = $this->getById($id);
        if (!$original) return null;

        unset($original['id'], $original['created_at'], $original['updated_at']);
        $original['title'] = $original['title'] . ' (Copy)';
        $original['slug']  = $this->uniqueSlug($original['slug'] . '-copy');
        $original['status']= 'draft';

        $newId = $this->create($original);

        foreach ($this->getGallery($id) as $img) {
            unset($img['id']);
            $img['portfolio_id'] = $newId;
            $this->db->insert('portfolio_gallery', $img);
        }
        foreach ($this->getFaqs($id) as $faq) {
            unset($faq['id']);
            $faq['portfolio_id'] = $newId;
            $this->db->insert('portfolio_faqs', $faq);
        }

        return $newId;
    }

    public function delete(int $id): bool
    {
        $this->db->query("DELETE FROM portfolio_items WHERE id = ?", [$id]);

        searchIndexer()?->removeItem('portfolio', $id);
        sitemapGenerator()?->regenerate();

        return true;
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->update('portfolio_items', ['status' => $status], 'id = ?', [$id]);

        searchIndexer()?->indexItem('portfolio', $id);
        sitemapGenerator()?->regenerate();
    }

    public function setFeatured(int $id, bool $featured): void
    {
        $this->db->update('portfolio_items', ['is_featured' => $featured ? 1 : 0], 'id = ?', [$id]);
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $idx => $id) {
            $this->db->update('portfolio_items', ['sort_order' => $idx], 'id = ?', [(int)$id]);
        }
    }

    public function getCategories(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT category FROM portfolio_items WHERE category IS NOT NULL AND category != '' AND status = 'published' ORDER BY category ASC"
        );
        return array_column($rows, 'category');
    }

    /* ================================================================
       GALLERY
       ================================================================ */

    public function getGallery(int $portfolioId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM portfolio_gallery WHERE portfolio_id = ? ORDER BY sort_order ASC",
            [$portfolioId]
        );
    }

    public function saveGalleryImage(int $portfolioId, array $data, ?int $id = null): int
    {
        if ($id) {
            $this->db->update('portfolio_gallery', $data, 'id = ?', [$id]);
            return $id;
        }
        $data['portfolio_id'] = $portfolioId;
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = $this->nextSort('portfolio_gallery', $portfolioId);
        }
        return (int)$this->db->insert('portfolio_gallery', $data);
    }

    public function deleteGalleryImage(int $id): void
    {
        $this->db->query("DELETE FROM portfolio_gallery WHERE id = ?", [$id]);
    }

    public function reorderGalleryImages(int $portfolioId, array $order): void
    {
        foreach ($order as $index => $id) {
            $this->db->update('portfolio_gallery', ['sort_order' => $index], 'id = ? AND portfolio_id = ?', [$id, $portfolioId]);
        }
    }

    /* ================================================================
       TESTIMONIALS
       ================================================================ */

    public function getTestimonialEntries(int $portfolioId): array
    {
        return $this->db->fetchAll(
            "SELECT ce.* FROM content_entries ce
             JOIN portfolio_testimonials pt ON ce.id = pt.testimonial_entry_id
             WHERE pt.portfolio_id = ? AND ce.status = 'published'
             ORDER BY pt.sort_order ASC",
            [$portfolioId]
        );
    }

    public function getAllTestimonialEntries(): array
    {
        return $this->db->fetchAll(
            "SELECT ce.id, ce.title, ce.excerpt, ce.featured_image, ce.fields
             FROM content_entries ce
             JOIN content_types ct ON ce.content_type_id = ct.id
             WHERE ct.slug = 'testimonials' AND ce.status = 'published'
             ORDER BY ce.title ASC"
        );
    }

    public function linkTestimonial(int $portfolioId, int $entryId): void
    {
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM portfolio_testimonials WHERE portfolio_id = ? AND testimonial_entry_id = ?",
            [$portfolioId, $entryId], 0
        );
        if (!$exists) {
            $this->db->insert('portfolio_testimonials', [
                'portfolio_id'          => $portfolioId,
                'testimonial_entry_id'  => $entryId,
                'sort_order'            => $this->nextSort('portfolio_testimonials', $portfolioId),
            ]);
        }
    }

    public function unlinkTestimonial(int $portfolioId, int $entryId): void
    {
        $this->db->query("DELETE FROM portfolio_testimonials WHERE portfolio_id = ? AND testimonial_entry_id = ?", [$portfolioId, $entryId]);
    }

    /* ================================================================
       RELATED SERVICES
       ================================================================ */

    public function getRelatedServices(int $portfolioId): array
    {
        return $this->db->fetchAll(
            "SELECT s.* FROM services s
             JOIN portfolio_related_services prs ON s.id = prs.service_id
             WHERE prs.portfolio_id = ? AND s.status = 'published'
             ORDER BY prs.sort_order ASC",
            [$portfolioId]
        );
    }

    public function getAllServices(): array
    {
        return $this->db->fetchAll(
            "SELECT id, title, slug, category FROM services WHERE status = 'published' ORDER BY title ASC"
        );
    }

    public function linkService(int $portfolioId, int $serviceId): void
    {
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM portfolio_related_services WHERE portfolio_id = ? AND service_id = ?",
            [$portfolioId, $serviceId], 0
        );
        if (!$exists) {
            $this->db->insert('portfolio_related_services', [
                'portfolio_id' => $portfolioId,
                'service_id'   => $serviceId,
                'sort_order'   => $this->nextSort('portfolio_related_services', $portfolioId),
            ]);
        }
    }

    public function unlinkService(int $portfolioId, int $serviceId): void
    {
        $this->db->query("DELETE FROM portfolio_related_services WHERE portfolio_id = ? AND service_id = ?", [$portfolioId, $serviceId]);
    }

    /* ================================================================
       FAQS
       ================================================================ */

    public function getFaqs(int $portfolioId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM portfolio_faqs WHERE portfolio_id = ? ORDER BY sort_order ASC",
            [$portfolioId]
        );
    }

    public function saveFaq(int $portfolioId, array $data, ?int $id = null): int
    {
        if ($id) {
            $this->db->update('portfolio_faqs', $data, 'id = ?', [$id]);
            searchIndexer()?->indexItem('portfolio_faq', $id);
            sitemapGenerator()?->regenerate();
            return $id;
        }
        $data['portfolio_id'] = $portfolioId;
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = $this->nextSort('portfolio_faqs', $portfolioId);
        }
        $faqId = (int)$this->db->insert('portfolio_faqs', $data);

        searchIndexer()?->indexItem('portfolio_faq', $faqId);
        sitemapGenerator()?->regenerate();

        return $faqId;
    }

    public function deleteFaq(int $id): void
    {
        $this->db->query("DELETE FROM portfolio_faqs WHERE id = ?", [$id]);

        searchIndexer()?->removeItem('portfolio_faq', $id);
        sitemapGenerator()?->regenerate();
    }

    /* ================================================================
       RELATED BLOCKS
       ================================================================ */

    public function getRelatedBlocks(int $portfolioId): array
    {
        return $this->db->fetchAll(
            "SELECT gb.* FROM global_blocks gb
             JOIN portfolio_related_blocks prb ON gb.id = prb.global_block_id
             WHERE prb.portfolio_id = ? AND gb.status = 'published'
             ORDER BY prb.sort_order ASC",
            [$portfolioId]
        );
    }

    public function getAllGlobalBlocks(): array
    {
        return $this->db->fetchAll(
            "SELECT id, name, block_type, status FROM global_blocks WHERE status = 'published' ORDER BY name ASC"
        );
    }

    public function linkBlock(int $portfolioId, int $blockId): void
    {
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM portfolio_related_blocks WHERE portfolio_id = ? AND global_block_id = ?",
            [$portfolioId, $blockId], 0
        );
        if (!$exists) {
            $this->db->insert('portfolio_related_blocks', [
                'portfolio_id'      => $portfolioId,
                'global_block_id'   => $blockId,
                'sort_order'        => $this->nextSort('portfolio_related_blocks', $portfolioId),
            ]);
        }
    }

    public function unlinkBlock(int $portfolioId, int $blockId): void
    {
        $this->db->query("DELETE FROM portfolio_related_blocks WHERE portfolio_id = ? AND global_block_id = ?", [$portfolioId, $blockId]);
    }

    /* ================================================================
       RELATED BLOG POSTS
       ================================================================ */

    public function getRelatedBlogs(int $portfolioId): array
    {
        return $this->db->fetchAll(
            "SELECT bp.* FROM blog_posts bp
             JOIN portfolio_related_blog prb ON bp.id = prb.blog_post_id
             WHERE prb.portfolio_id = ? AND bp.status = 'published'
             ORDER BY prb.sort_order ASC",
            [$portfolioId]
        );
    }

    public function getAllBlogPosts(): array
    {
        return $this->db->fetchAll(
            "SELECT id, title, slug, featured_image FROM blog_posts WHERE status = 'published' ORDER BY title ASC"
        );
    }

    public function linkBlog(int $portfolioId, int $blogPostId): void
    {
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM portfolio_related_blog WHERE portfolio_id = ? AND blog_post_id = ?",
            [$portfolioId, $blogPostId], 0
        );
        if (!$exists) {
            $this->db->insert('portfolio_related_blog', [
                'portfolio_id' => $portfolioId,
                'blog_post_id' => $blogPostId,
                'sort_order'   => $this->nextSort('portfolio_related_blog', $portfolioId),
            ]);
        }
    }

    public function unlinkBlog(int $portfolioId, int $blogPostId): void
    {
        $this->db->query("DELETE FROM portfolio_related_blog WHERE portfolio_id = ? AND blog_post_id = ?", [$portfolioId, $blogPostId]);
    }

    /* ================================================================
       REVISIONS
       ================================================================ */

    public function saveRevision(int $portfolioId, string $note = ''): int
    {
        $project = $this->getById($portfolioId);
        if (!$project) return 0;

        unset($project['id'], $project['created_at'], $project['updated_at']);

        return (int)$this->db->insert('portfolio_revisions', [
            'portfolio_id'  => $portfolioId,
            'data_snapshot' => json_encode($project, JSON_UNESCAPED_UNICODE),
            'revision_note' => substr($note, 0, 255),
        ]);
    }

    public function getRevisions(int $portfolioId): array
    {
        return $this->db->fetchAll(
            "SELECT id, revision_note, created_at FROM portfolio_revisions WHERE portfolio_id = ? ORDER BY created_at DESC LIMIT 50",
            [$portfolioId]
        );
    }

    public function getRevision(int $revisionId): ?array
    {
        return $this->db->fetch("SELECT * FROM portfolio_revisions WHERE id = ?", [$revisionId]) ?: null;
    }

    public function restoreRevision(int $portfolioId, int $revisionId): bool
    {
        $revision = $this->getRevision($revisionId);
        if (!$revision || (int)$revision['portfolio_id'] !== $portfolioId) return false;

        $data = json_decode($revision['data_snapshot'], true);
        if (!$data) return false;

        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('portfolio_items', $data, 'id = ?', [$portfolioId]);
        return true;
    }

    /* ================================================================
       HELPERS
       ================================================================ */

    private function nextSort(string $table, int $portfolioId): int
    {
        $max = $this->db->fetchColumn(
            "SELECT COALESCE(MAX(sort_order), -1) FROM {$table} WHERE portfolio_id = ?",
            [$portfolioId], -1
        );
        return (int)$max + 1;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = (new SlugManager())->generate($base, 'portfolio_items', 'slug');
        $count = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM portfolio_items WHERE slug = ?", [$slug], 0
        );
        if ($count > 0) $slug .= '-' . ($count + 1);
        return $slug;
    }
}
