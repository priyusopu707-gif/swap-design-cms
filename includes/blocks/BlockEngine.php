<?php
/**
 * Swap Design - Block Engine
 *
 * CRUD operations for global reusable content blocks.
 * Supports rendering blocks with device visibility, scheduling,
 * and usage tracking.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class BlockEngine
{
    private Database $db;

    /** Available block types and their default content schemas */
    public const TYPES = [
        'cta'          => 'Call To Action',
        'contact_form' => 'Contact Form',
        'faq'          => 'FAQ',
        'testimonials' => 'Testimonials',
        'trust_badges' => 'Trust Badges',
        'stats'        => 'Statistics',
        'newsletter'   => 'Newsletter',
        'process'      => 'Process Timeline',
        'why_choose'   => 'Why Choose Me',
        'client_logos' => 'Client Logos',
        'awards'       => 'Awards',
        'social_proof' => 'Social Proof',
        'banner'       => 'Custom Banner',
        'announcement' => 'Announcement Bar',
        'custom_html'  => 'Custom HTML',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all blocks with optional filters.
     */
    public function getBlocks(array $filters = []): array
    {
        $sql  = "SELECT * FROM global_blocks WHERE 1=1";
        $params = [];

        if (!empty($filters['block_type'])) {
            $sql .= " AND block_type = ?";
            $params[] = $filters['block_type'];
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

        $sql .= " ORDER BY updated_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get a single block by ID or slug.
     */
    public function getBlock(int|string $identifier): ?array
    {
        if (is_numeric($identifier)) {
            $row = $this->db->fetch("SELECT * FROM global_blocks WHERE id = ?", [(int) $identifier]);
        } else {
            $row = $this->db->fetch("SELECT * FROM global_blocks WHERE slug = ?", [$identifier]);
        }

        if ($row) {
            $row['content'] = json_decode($row['content'], true) ?: [];
        }

        return $row ?: null;
    }

    /**
     * Create a new block.
     */
    public function create(array $data): int
    {
        $content = $data['content'] ?? [];
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $insert = [
            'name'              => $data['name'] ?? 'Untitled Block',
            'slug'              => $data['slug'] ?? sluggify($data['name'] ?? 'block'),
            'block_type'        => $data['block_type'] ?? 'custom_html',
            'description'       => $data['description'] ?? null,
            'content'           => $content,
            'status'            => $data['status'] ?? 'draft',
            'category'          => $data['category'] ?? null,
            'full_width'        => isset($data['full_width']) ? (int) $data['full_width'] : 0,
            'background_color'  => $data['background_color'] ?? null,
            'custom_css'        => $data['custom_css'] ?? null,
            'device_visibility' => $data['device_visibility'] ?? 'all',
            'schedule_start'    => $data['schedule_start'] ?? null,
            'schedule_end'      => $data['schedule_end'] ?? null,
        ];

        $blockId = (int) $this->db->insert('global_blocks', $insert);

        searchIndexer()?->indexItem('global_block', $blockId);

        return $blockId;
    }

    /**
     * Update a block.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = [
            'name', 'slug', 'block_type', 'description', 'content',
            'status', 'category', 'full_width', 'background_color',
            'custom_css', 'device_visibility', 'schedule_start', 'schedule_end',
        ];

        $update = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                if ($key === 'content' && is_array($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $update[$key] = $val;
            }
        }

        if (empty($update)) {
            return false;
        }

        $this->db->update('global_blocks', $update, 'id = ?', [$id]);

        searchIndexer()?->indexItem('global_block', $id);

        return true;
    }

    /**
     * Delete a block and its usage records.
     */
    public function delete(int $id): bool
    {
        $this->db->delete('global_block_usage', 'block_id = ?', [$id]);
        $this->db->delete('global_blocks', 'id = ?', [$id]);

        searchIndexer()?->removeItem('global_block', $id);

        return true;
    }

    /**
     * Duplicate a block.
     */
    public function duplicate(int $id): int
    {
        $original = $this->getBlock($id);
        if (!$original) {
            throw new \RuntimeException('Block not found');
        }

        $original['name'] = $original['name'] . ' (Copy)';
        $original['slug'] = $original['slug'] . '-copy-' . time();
        $original['status'] = 'draft';

        return $this->create($original);
    }

    /**
     * Track usage of a block on a page.
     */
    public function trackUsage(int $blockId, string $pageSlug, string $sectionKey = 'content'): void
    {
        $exists = $this->db->exists(
            'global_block_usage',
            'block_id = ? AND page_slug = ? AND section_key = ?',
            [$blockId, $pageSlug, $sectionKey]
        );

        if (!$exists) {
            $this->db->insert('global_block_usage', [
                'block_id'    => $blockId,
                'page_slug'   => $pageSlug,
                'section_key' => $sectionKey,
            ]);

            $this->db->query(
                "UPDATE global_blocks SET usage_count = usage_count + 1 WHERE id = ?",
                [$blockId]
            );
        }
    }

    /**
     * Get usage records for a block.
     */
    public function getUsage(int $blockId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM global_block_usage WHERE block_id = ? ORDER BY page_slug ASC",
            [$blockId]
        );
    }

    /**
     * Get active blocks for a page (considering schedule and visibility).
     */
    public function getBlocksForPage(string $pageSlug): array
    {
        $now = date('Y-m-d H:i:s');

        $blocks = $this->db->fetchAll(
            "SELECT gb.*, gbu.section_key, gbu.sort_order
             FROM global_blocks gb
             JOIN global_block_usage gbu ON gbu.block_id = gb.id
             WHERE gbu.page_slug = ?
               AND gb.status = 'published'
               AND (gb.schedule_start IS NULL OR gb.schedule_start <= ?)
               AND (gb.schedule_end IS NULL OR gb.schedule_end >= ?)
             ORDER BY gbu.sort_order ASC",
            [$pageSlug, $now, $now]
        );

        foreach ($blocks as &$block) {
            $block['content'] = json_decode($block['content'], true) ?: [];
        }

        return $blocks;
    }

    /**
     * Get counts grouped by block type.
     */
    public function getTypeCounts(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT block_type, COUNT(*) AS cnt FROM global_blocks GROUP BY block_type"
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['block_type']] = (int) $row['cnt'];
        }

        return $counts;
    }
}
