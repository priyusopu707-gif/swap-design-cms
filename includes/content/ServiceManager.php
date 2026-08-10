<?php
/**
 * Swap Design - Service Manager
 *
 * Full CRUD for services table and all sub-tables:
 *  - service_features
 *  - service_benefits
 *  - service_process_steps
 *  - service_faqs
 *  - service_portfolio
 *  - service_testimonials
 *  - service_related_blocks
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class ServiceManager
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* ================================================================
       SERVICE CRUD
       ================================================================ */

    public function getAll(array $filters = []): array
    {
        $sql  = "SELECT * FROM services WHERE 1=1";
        $args = [];

        if (!empty($filters['status'])) {
            $sql   .= " AND status = ?";
            $args[] = $filters['status'];
        }
        if (!empty($filters['category'])) {
            $sql   .= " AND category = ?";
            $args[] = $filters['category'];
        }
        if (!empty($filters['search'])) {
            $sql   .= " AND (title LIKE ? OR short_description LIKE ?)";
            $kw     = '%' . $filters['search'] . '%';
            $args[] = $kw;
            $args[] = $kw;
        }

        $sql .= " ORDER BY sort_order ASC, title ASC";
        return $this->db->fetchAll($sql, $args);
    }

    public function getById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM services WHERE id = ?", [$id]) ?: null;
    }

    public function getBySlug(string $slug): ?array
    {
        return $this->db->fetch("SELECT * FROM services WHERE slug = ? AND status = 'published'", [$slug]) ?: null;
    }

    /**
     * Get all services with their sub-data (for frontend rendering).
     */
    public function getFullBySlug(string $slug): ?array
    {
        $service = $this->getBySlug($slug);
        if (!$service) return null;

        $sid = (int)$service['id'];

        $service['features']     = $this->getFeatures($sid);
        $service['benefits']     = $this->getBenefits($sid);
        $service['process_steps']= $this->getProcessSteps($sid);
        $service['faqs']         = $this->getFaqs($sid);
        $service['portfolio']    = $this->getPortfolioItems($sid);
        $service['testimonials'] = $this->getTestimonialEntries($sid);
        $service['blocks']       = $this->getRelatedBlocks($sid);

        return $service;
    }

    public function create(array $data): int
    {
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title'] ?? 'service');
        $serviceId = (int)$this->db->insert('services', $data);

        searchIndexer()?->indexItem('service', $serviceId);
        sitemapGenerator()?->regenerate();

        return $serviceId;
    }

    public function update(int $id, array $data): bool
    {
        unset($data['id']);
        $this->db->update('services', $data, 'id = ?', [$id]);

        searchIndexer()?->indexItem('service', $id);
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

        /* Duplicate sub-data */
        foreach (['features', 'benefits', 'process_steps', 'faqs'] as $sub) {
            $items = $this->getSubItems($sub, $id);
            foreach ($items as $item) {
                unset($item['id']);
                $item['service_id'] = $newId;
                $this->db->insert("service_{$sub}", $item);
            }
        }

        return $newId;
    }

    public function delete(int $id): bool
    {
        $this->db->query("DELETE FROM services WHERE id = ?", [$id]);

        searchIndexer()?->removeItem('service', $id);
        sitemapGenerator()?->regenerate();

        return true;
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->update('services', ['status' => $status], 'id = ?', [$id]);

        searchIndexer()?->indexItem('service', $id);
        sitemapGenerator()?->regenerate();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $idx => $id) {
            $this->db->update('services', ['sort_order' => $idx], 'id = ?', [(int)$id]);
        }
    }

    /**
     * Get distinct categories from services table.
     */
    public function getCategories(): array
    {
        $rows = $this->db->fetchAll("SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        return array_column($rows, 'category');
    }

    /* ================================================================
       FEATURES
       ================================================================ */

    public function getFeatures(int $serviceId): array
    {
        return $this->getSubItems('features', $serviceId);
    }

    public function saveFeature(int $serviceId, array $data, ?int $id = null): int
    {
        return $this->saveSubItem('service_features', $serviceId, $data, $id);
    }

    public function deleteFeature(int $id): void
    {
        $this->db->query("DELETE FROM service_features WHERE id = ?", [$id]);
    }

    /* ================================================================
       BENEFITS
       ================================================================ */

    public function getBenefits(int $serviceId): array
    {
        return $this->getSubItems('benefits', $serviceId);
    }

    public function saveBenefit(int $serviceId, array $data, ?int $id = null): int
    {
        return $this->saveSubItem('service_benefits', $serviceId, $data, $id);
    }

    public function deleteBenefit(int $id): void
    {
        $this->db->query("DELETE FROM service_benefits WHERE id = ?", [$id]);
    }

    /* ================================================================
       PROCESS STEPS
       ================================================================ */

    public function getProcessSteps(int $serviceId): array
    {
        return $this->getSubItems('process_steps', $serviceId);
    }

    public function saveProcessStep(int $serviceId, array $data, ?int $id = null): int
    {
        return $this->saveSubItem('service_process_steps', $serviceId, $data, $id);
    }

    public function deleteProcessStep(int $id): void
    {
        $this->db->query("DELETE FROM service_process_steps WHERE id = ?", [$id]);
    }

    /* ================================================================
       FAQS
       ================================================================ */

    public function getFaqs(int $serviceId): array
    {
        return $this->getSubItems('faqs', $serviceId);
    }

    public function saveFaq(int $serviceId, array $data, ?int $id = null): int
    {
        $faqId = $this->saveSubItem('service_faqs', $serviceId, $data, $id);

        searchIndexer()?->indexItem('service_faq', $faqId);
        sitemapGenerator()?->regenerate();

        return $faqId;
    }

    public function deleteFaq(int $id): void
    {
        $this->db->query("DELETE FROM service_faqs WHERE id = ?", [$id]);

        searchIndexer()?->removeItem('service_faq', $id);
        sitemapGenerator()?->regenerate();
    }

    /* ================================================================
       PORTFOLIO RELATIONSHIPS
       ================================================================ */

    public function getPortfolioItems(int $serviceId): array
    {
        return $this->db->fetchAll(
            "SELECT pi.* FROM portfolio_items pi
             JOIN service_portfolio sp ON pi.id = sp.portfolio_item_id
             WHERE sp.service_id = ? AND pi.status = 'published'
             ORDER BY sp.sort_order ASC",
            [$serviceId]
        );
    }

    /**
     * Get ALL published portfolio items (for the picker).
     */
    public function getAllPortfolioItems(): array
    {
        return $this->db->fetchAll(
            "SELECT id, title, category, image_url FROM portfolio_items WHERE status = 'published' ORDER BY title ASC"
        );
    }

    public function linkPortfolio(int $serviceId, int $portfolioId): void
    {
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM service_portfolio WHERE service_id = ? AND portfolio_item_id = ?",
            [$serviceId, $portfolioId],
            0
        );
        if (!$exists) {
            $this->db->insert('service_portfolio', [
                'service_id'        => $serviceId,
                'portfolio_item_id' => $portfolioId,
                'sort_order'        => $this->nextSortOrder('service_portfolio', $serviceId),
            ]);
        }
    }

    public function unlinkPortfolio(int $serviceId, int $portfolioId): void
    {
        $this->db->query("DELETE FROM service_portfolio WHERE service_id = ? AND portfolio_item_id = ?", [$serviceId, $portfolioId]);
    }

    /* ================================================================
       TESTIMONIAL RELATIONSHIPS
       ================================================================ */

    public function getTestimonialEntries(int $serviceId): array
    {
        return $this->db->fetchAll(
            "SELECT ce.* FROM content_entries ce
             JOIN service_testimonials st ON ce.id = st.testimonial_entry_id
             WHERE st.service_id = ? AND ce.status = 'published'
             ORDER BY st.sort_order ASC",
            [$serviceId]
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

    public function linkTestimonial(int $serviceId, int $entryId): void
    {
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM service_testimonials WHERE service_id = ? AND testimonial_entry_id = ?",
            [$serviceId, $entryId],
            0
        );
        if (!$exists) {
            $this->db->insert('service_testimonials', [
                'service_id'           => $serviceId,
                'testimonial_entry_id' => $entryId,
                'sort_order'           => $this->nextSortOrder('service_testimonials', $serviceId),
            ]);
        }
    }

    public function unlinkTestimonial(int $serviceId, int $entryId): void
    {
        $this->db->query("DELETE FROM service_testimonials WHERE service_id = ? AND testimonial_entry_id = ?", [$serviceId, $entryId]);
    }

    /* ================================================================
       GLOBAL BLOCK RELATIONSHIPS
       ================================================================ */

    public function getRelatedBlocks(int $serviceId): array
    {
        return $this->db->fetchAll(
            "SELECT gb.* FROM global_blocks gb
             JOIN service_related_blocks srb ON gb.id = srb.global_block_id
             WHERE srb.service_id = ? AND gb.status = 'published'
             ORDER BY srb.sort_order ASC",
            [$serviceId]
        );
    }

    public function getAllGlobalBlocks(): array
    {
        return $this->db->fetchAll(
            "SELECT id, name, block_type, status FROM global_blocks WHERE status = 'published' ORDER BY name ASC"
        );
    }

    public function linkBlock(int $serviceId, int $blockId): void
    {
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM service_related_blocks WHERE service_id = ? AND global_block_id = ?",
            [$serviceId, $blockId],
            0
        );
        if (!$exists) {
            $this->db->insert('service_related_blocks', [
                'service_id'      => $serviceId,
                'global_block_id' => $blockId,
                'sort_order'      => $this->nextSortOrder('service_related_blocks', $serviceId),
            ]);
        }
    }

    public function unlinkBlock(int $serviceId, int $blockId): void
    {
        $this->db->query("DELETE FROM service_related_blocks WHERE service_id = ? AND global_block_id = ?", [$serviceId, $blockId]);
    }

    /* ================================================================
       HELPER: Sub-Item Generic CRUD
       ================================================================ */

    private function getSubItems(string $tableKey, int $serviceId): array
    {
        $table = 'service_' . $tableKey;
        return $this->db->fetchAll(
            "SELECT * FROM {$table} WHERE service_id = ? ORDER BY sort_order ASC",
            [$serviceId]
        );
    }

    private function saveSubItem(string $table, int $serviceId, array $data, ?int $id): int
    {
        if ($id) {
            $this->db->update($table, $data, 'id = ?', [$id]);
            return $id;
        }

        $data['service_id'] = $serviceId;
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = $this->nextSortOrder($table, $serviceId);
        }
        return (int)$this->db->insert($table, $data);
    }

    private function nextSortOrder(string $table, int $serviceId): int
    {
        $max = $this->db->fetchColumn(
            "SELECT COALESCE(MAX(sort_order), -1) FROM {$table} WHERE service_id = ?",
            [$serviceId],
            -1
        );
        return (int)$max + 1;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = (new SlugManager())->generate($base, 'services', 'slug');
        $count = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM services WHERE slug = ?",
            [$slug],
            0
        );
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }
        return $slug;
    }
}
