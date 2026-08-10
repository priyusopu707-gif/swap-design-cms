<?php
/**
 * Swap Design - Content Type Engine
 *
 * Manages custom content type definitions. Each type has a JSON
 * field schema that defines what fields its entries contain.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class ContentTypeEngine
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Built-in system content types.
     */
    public const SYSTEM_TYPES = [
        'service' => [
            'name'          => 'Service',
            'fields_schema' => [
                ['name' => 'icon',        'label' => 'Icon',         'type' => 'text',   'required' => false],
                ['name' => 'description', 'label' => 'Description',  'type' => 'textarea','required' => true],
                ['name' => 'price',       'label' => 'Starting Price','type' => 'text',  'required' => false],
                ['name' => 'features',    'label' => 'Features',     'type' => 'repeater','required' => false,
                 'fields' => [['name' => 'text', 'label' => 'Feature', 'type' => 'text']]],
            ],
        ],
        'team' => [
            'name'          => 'Team Member',
            'fields_schema' => [
                ['name' => 'position',  'label' => 'Position',     'type' => 'text',   'required' => true],
                ['name' => 'bio',       'label' => 'Bio',          'type' => 'textarea','required' => false],
                ['name' => 'photo',     'label' => 'Photo URL',    'type' => 'image',  'required' => false],
                ['name' => 'email',     'label' => 'Email',        'type' => 'email',  'required' => false],
                ['name' => 'linkedin',  'label' => 'LinkedIn URL', 'type' => 'url',    'required' => false],
            ],
        ],
        'testimonial' => [
            'name'          => 'Testimonial',
            'fields_schema' => [
                ['name' => 'quote',      'label' => 'Quote',        'type' => 'textarea','required' => true],
                ['name' => 'author',     'label' => 'Author Name',  'type' => 'text',   'required' => true],
                ['name' => 'role',       'label' => 'Role/Company', 'type' => 'text',   'required' => false],
                ['name' => 'rating',     'label' => 'Rating (1-5)', 'type' => 'number', 'required' => false],
                ['name' => 'avatar',     'label' => 'Avatar URL',   'type' => 'image',  'required' => false],
            ],
        ],
    ];

    /**
     * Seed system content types if they don't exist.
     */
    public function seedSystemTypes(): void
    {
        foreach (self::SYSTEM_TYPES as $slug => $data) {
            if (!$this->existsBySlug($slug)) {
                $this->create([
                    'name'          => $data['name'],
                    'slug'          => $slug,
                    'fields_schema' => $data['fields_schema'],
                    'is_system'     => 1,
                    'status'        => 'active',
                ]);
            }
        }
    }

    public function existsBySlug(string $slug): bool
    {
        return $this->db->exists('content_types', 'slug = ?', [$slug]);
    }

    public function getAll(array $filters = []): array
    {
        $sql    = "SELECT * FROM content_types WHERE 1=1";
        $params = [];

        if (isset($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['is_system'])) {
            $sql .= " AND is_system = ?";
            $params[] = (int)$filters['is_system'];
        }

        $sql .= " ORDER BY is_system DESC, name ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getById(int $id): ?array
    {
        $row = $this->db->fetch("SELECT * FROM content_types WHERE id = ?", [$id]);
        if ($row) {
            $row['fields_schema'] = json_decode($row['fields_schema'], true) ?: [];
        }
        return $row ?: null;
    }

    public function getBySlug(string $slug): ?array
    {
        $row = $this->db->fetch("SELECT * FROM content_types WHERE slug = ?", [$slug]);
        if ($row) {
            $row['fields_schema'] = json_decode($row['fields_schema'], true) ?: [];
        }
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $schema = $data['fields_schema'] ?? [];
        if (is_array($schema)) {
            $schema = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (int)$this->db->insert('content_types', [
            'name'            => $data['name'] ?? 'Untitled',
            'slug'            => $data['slug'] ?? sluggify($data['name'] ?? 'type'),
            'description'     => $data['description'] ?? null,
            'fields_schema'   => $schema,
            'icon'            => $data['icon'] ?? 'file',
            'is_system'       => (int)($data['is_system'] ?? 0),
            'has_entries'     => (int)($data['has_entries'] ?? 1),
            'list_template'   => $data['list_template'] ?? null,
            'single_template' => $data['single_template'] ?? null,
            'status'          => $data['status'] ?? 'active',
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'slug', 'description', 'fields_schema', 'icon',
                    'has_entries', 'list_template', 'single_template', 'status'];
        $update  = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                if ($key === 'fields_schema' && is_array($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $update[$key] = $val;
            }
        }

        if (empty($update)) return false;

        $this->db->update('content_types', $update, 'id = ?', [$id]);
        return true;
    }

    public function delete(int $id): bool
    {
        $type = $this->getById($id);
        if (!$type || $type['is_system']) return false;

        $this->db->delete('content_entries', 'content_type_id = ?', [$id]);
        $this->db->delete('content_types', 'id = ?', [$id]);
        return true;
    }

    /**
     * Get field schema as a flat, resolved array.
     */
    public function getResolvedFields(int $typeId): array
    {
        $type = $this->getById($typeId);
        return $type['fields_schema'] ?? [];
    }
}
