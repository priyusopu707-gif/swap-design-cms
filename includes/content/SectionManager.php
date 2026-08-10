<?php
/**
 * Swap Design - Section Manager
 *
 * CRUD for reusable page sections. Sections are content blocks
 * that can be placed on pages within layout zones. Each section
 * has a type (custom_html, global_block, component, content_entries,
 * dynamic_list, shortcode) and type-specific config.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class SectionManager
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Available section types */
    public const TYPES = [
        'custom_html'    => 'Custom HTML',
        'global_block'   => 'Global Block',
        'component'      => 'Component',
        'content_entries'=> 'Content Entries',
        'dynamic_list'   => 'Dynamic List',
        'shortcode'      => 'Shortcode',
    ];

    public function getAll(array $filters = []): array
    {
        $sql    = "SELECT * FROM sections WHERE 1=1";
        $params = [];

        if (!empty($filters['section_type'])) {
            $sql .= " AND section_type = ?";
            $params[] = $filters['section_type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR slug LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY category ASC, name ASC";

        $limit  = isset($filters['limit']) ? (int)$filters['limit'] : 100;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        $rows = $this->db->fetchAll($sql, $params);
        foreach ($rows as &$row) {
            $row['config'] = json_decode($row['config'], true) ?: [];
        }

        return $rows;
    }

    public function getById(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM sections WHERE id = ?", [$id]);
        if ($row) {
            $row['config'] = json_decode($row['config'], true) ?: [];
        }
        return $row ?: null;
    }

    public function getBySlug(string $slug): ?array
    {
        $row = $this->db->fetch("SELECT * FROM sections WHERE slug = ?", [$slug]);
        if ($row) {
            $row['config'] = json_decode($row['config'], true) ?: [];
        }
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $config = $data['config'] ?? [];
        if (is_array($config)) {
            $config = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (int)$this->db->insert('sections', [
            'name'         => $data['name'] ?? 'Untitled Section',
            'slug'         => $data['slug'] ?? sluggify($data['name'] ?? 'section'),
            'section_type' => $data['section_type'] ?? 'custom_html',
            'config'       => $config,
            'status'       => $data['status'] ?? 'draft',
            'category'     => $data['category'] ?? null,
            'description'  => $data['description'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'slug', 'section_type', 'config', 'status', 'category', 'description'];
        $update  = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                if ($key === 'config' && is_array($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $update[$key] = $val;
            }
        }

        if (empty($update)) return false;

        $this->db->update('sections', $update, 'id = ?', [$id]);
        return true;
    }

    public function delete(int $id): bool
    {
        $this->db->delete('page_sections', 'section_id = ?', [$id]);
        $this->db->delete('sections', 'id = ?', [$id]);
        return true;
    }

    public function duplicate(int $id): int
    {
        $original = $this->getById($id);
        if (!$original) {
            throw new \RuntimeException('Section not found');
        }

        return $this->create([
            'name'         => $original['name'] . ' (Copy)',
            'slug'         => $original['slug'] . '-copy-' . time(),
            'section_type' => $original['section_type'],
            'config'       => $original['config'],
            'status'       => 'draft',
            'category'     => $original['category'],
            'description'  => $original['description'],
        ]);
    }

    /**
     * Get sections assigned to a page, ordered by zone and sort_order.
     * Returns sections with their page_section metadata.
     */
    public function getPageSections(int $pageId): array
    {
        $now = date('Y-m-d H:i:s');

        return $this->db->fetchAll(
            "SELECT s.*, ps.id AS ps_id, ps.zone_key, ps.sort_order, ps.is_enabled,
                    ps.custom_config, ps.schedule_start, ps.schedule_end
             FROM page_sections ps
             JOIN sections s ON s.id = ps.section_id
             WHERE ps.page_id = ?
               AND ps.is_enabled = 1
               AND (ps.schedule_start IS NULL OR ps.schedule_start <= ?)
               AND (ps.schedule_end IS NULL OR ps.schedule_end >= ?)
             ORDER BY ps.zone_key ASC, ps.sort_order ASC",
            [$pageId, $now, $now]
        );
    }

    /**
     * Assign a section to a page.
     */
    public function assignToPage(int $pageId, int $sectionId, string $zoneKey = 'content', int $sortOrder = -1): int
    {
        if ($sortOrder < 0) {
            $max = (int)$this->db->fetchColumn(
                "SELECT COALESCE(MAX(sort_order), -1) FROM page_sections WHERE page_id = ? AND zone_key = ?",
                [$pageId, $zoneKey],
                -1
            );
            $sortOrder = $max + 1;
        }

        return (int)$this->db->insert('page_sections', [
            'page_id'    => $pageId,
            'section_id' => $sectionId,
            'zone_key'   => $zoneKey,
            'sort_order' => $sortOrder,
            'is_enabled' => 1,
        ]);
    }

    /**
     * Remove a section assignment from a page.
     */
    public function removeFromPage(int $pageSectionId): void
    {
        $this->db->delete('page_sections', 'id = ?', [$pageSectionId]);
    }

    /**
     * Reorder sections on a page within a zone.
     */
    public function reorderPageSections(int $pageId, string $zoneKey, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $psId) {
            $this->db->update(
                'page_sections',
                ['sort_order' => $index, 'zone_key' => $zoneKey],
                'id = ?',
                [(int)$psId]
            );
        }
    }

    /**
     * Toggle a page section enabled/disabled.
     */
    public function togglePageSection(int $pageSectionId, bool $enabled): void
    {
        $this->db->update('page_sections', ['is_enabled' => $enabled ? 1 : 0], 'id = ?', [$pageSectionId]);
    }

    /**
     * Update a page section's custom config override.
     */
    public function updatePageSectionConfig(int $pageSectionId, array $config): void
    {
        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->db->update('page_sections', ['custom_config' => $json], 'id = ?', [$pageSectionId]);
    }
}
