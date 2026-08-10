<?php
/**
 * Swap Design - Layout Manager
 *
 * Manages page structural templates (layouts) with named
 * zone definitions. Pages are assigned a layout, and sections
 * are placed into layout zones.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class LayoutManager
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Built-in default layouts.
     */
    public const BUILTIN = [
        'default' => [
            'name'          => 'Default Layout',
            'zones'         => [
                ['key' => 'content',     'label' => 'Content Area',     'allowed_sections' => ['custom_html','global_block','component','content_entries','dynamic_list']],
                ['key' => 'sidebar',     'label' => 'Sidebar',          'allowed_sections' => ['custom_html','global_block','component','dynamic_list']],
                ['key' => 'before_main', 'label' => 'Before Main',      'allowed_sections' => ['custom_html','global_block','component']],
                ['key' => 'after_main',  'label' => 'After Main',       'allowed_sections' => ['custom_html','global_block','component']],
            ],
            'template_path' => 'pages/default.php',
        ],
        'fullwidth' => [
            'name'          => 'Full Width',
            'zones'         => [
                ['key' => 'content',     'label' => 'Content Area',     'allowed_sections' => ['custom_html','global_block','component','content_entries','dynamic_list']],
                ['key' => 'hero',        'label' => 'Hero Area',        'allowed_sections' => ['custom_html','global_block','component']],
                ['key' => 'before_main', 'label' => 'Before Content',   'allowed_sections' => ['custom_html','global_block','component']],
                ['key' => 'after_main',  'label' => 'After Content',    'allowed_sections' => ['custom_html','global_block','component']],
            ],
            'template_path' => 'pages/fullwidth.php',
        ],
        'landing' => [
            'name'          => 'Landing Page',
            'zones'         => [
                ['key' => 'hero',        'label' => 'Hero Section',     'allowed_sections' => ['custom_html','global_block','component']],
                ['key' => 'content',     'label' => 'Content Sections', 'allowed_sections' => ['custom_html','global_block','component','content_entries','dynamic_list']],
                ['key' => 'cta',         'label' => 'Call to Action',   'allowed_sections' => ['custom_html','global_block','component']],
                ['key' => 'footer_cta',  'label' => 'Footer CTA',       'allowed_sections' => ['custom_html','global_block','component']],
            ],
            'template_path' => 'pages/landing.php',
        ],
    ];

    /**
     * Seed built-in layouts.
     */
    public function seedBuiltins(): void
    {
        foreach (self::BUILTIN as $slug => $data) {
            if (!$this->existsBySlug($slug)) {
                $this->create([
                    'name'          => $data['name'],
                    'slug'          => $slug,
                    'zones'         => $data['zones'],
                    'template_path' => $data['template_path'],
                ]);
            }
        }

        /* Ensure exactly one default */
        if (!$this->getDefault()) {
            $layouts = $this->getAll(['status' => 'active']);
            if (!empty($layouts)) {
                $this->setDefault((int)$layouts[0]['id']);
            }
        }
    }

    public function existsBySlug(string $slug): bool
    {
        return $this->db->exists('layouts', 'slug = ?', [$slug]);
    }

    public function getAll(array $filters = []): array
    {
        $sql    = "SELECT * FROM layouts WHERE 1=1";
        $params = [];

        if (isset($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY is_default DESC, name ASC";

        $rows = $this->db->fetchAll($sql, $params);
        foreach ($rows as &$row) {
            $row['zones'] = json_decode($row['zones'], true) ?: [];
        }

        return $rows;
    }

    public function getById(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM layouts WHERE id = ?", [$id]);
        if ($row) {
            $row['zones'] = json_decode($row['zones'], true) ?: [];
        }
        return $row ?: null;
    }

    public function getBySlug(string $slug): ?array
    {
        $row = $this->db->fetch("SELECT * FROM layouts WHERE slug = ?", [$slug]);
        if ($row) {
            $row['zones'] = json_decode($row['zones'], true) ?: [];
        }
        return $row ?: null;
    }

    public function getDefault(): ?array
    {
        $row = $this->db->fetch("SELECT * FROM layouts WHERE is_default = 1 AND status = 'active' LIMIT 1");
        if ($row) {
            $row['zones'] = json_decode($row['zones'], true) ?: [];
        }
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $zones = $data['zones'] ?? [];
        if (is_array($zones)) {
            $zones = json_encode($zones, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (int)$this->db->insert('layouts', [
            'name'          => $data['name'] ?? 'Untitled Layout',
            'slug'          => $data['slug'] ?? sluggify($data['name'] ?? 'layout'),
            'description'   => $data['description'] ?? null,
            'zones'         => $zones,
            'template_path' => $data['template_path'] ?? '',
            'status'        => $data['status'] ?? 'active',
            'is_default'    => (int)($data['is_default'] ?? 0),
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'slug', 'description', 'zones', 'template_path', 'status', 'is_default'];
        $update  = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                if ($key === 'zones' && is_array($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $update[$key] = $val;
            }
        }

        if (empty($update)) return false;

        $this->db->update('layouts', $update, 'id = ?', [$id]);
        return true;
    }

    public function delete(int $id): bool
    {
        $this->db->update('layouts', ['is_default' => 0], 'id = ?', [$id]);
        $this->db->delete('layouts', 'id = ?', [$id]);
        return true;
    }

    /**
     * Set a layout as the default (un-sets previous default).
     */
    public function setDefault(int $id): void
    {
        $this->db->query("UPDATE layouts SET is_default = 0 WHERE is_default = 1");
        $this->db->update('layouts', ['is_default' => 1], 'id = ?', [$id]);
    }

    /**
     * Get zones for a layout as key => label pairs.
     */
    public function getZoneMap(int $layoutId): array
    {
        $layout = $this->getById($layoutId);
        if (!$layout || empty($layout['zones'])) return [];

        $map = [];
        foreach ($layout['zones'] as $zone) {
            $map[$zone['key']] = $zone['label'];
        }
        return $map;
    }
}
