<?php
/**
 * Swap Design - Content Entry Manager
 *
 * CRUD for entries belonging to any content type.
 * Entries store field values as a JSON object.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class ContentEntryManager
{
    private Database $db;
    private ContentTypeEngine $typeEngine;

    public function __construct()
    {
        $this->db         = Database::getInstance();
        $this->typeEngine = new ContentTypeEngine();
    }

    public function getEntries(array $filters = []): array
    {
        $sql    = "SELECT ce.*, ct.name AS type_name, ct.slug AS type_slug FROM content_entries ce JOIN content_types ct ON ct.id = ce.content_type_id WHERE 1=1";
        $params = [];

        if (!empty($filters['content_type_id'])) {
            $sql .= " AND ce.content_type_id = ?";
            $params[] = (int)$filters['content_type_id'];
        }

        if (!empty($filters['type_slug'])) {
            $sql .= " AND ct.slug = ?";
            $params[] = $filters['type_slug'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND ce.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (ce.title LIKE ? OR ce.slug LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY ce.sort_order ASC, ce.updated_at DESC";

        $limit  = isset($filters['limit']) ? (int)$filters['limit'] : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        $rows = $this->db->fetchAll($sql, $params);
        foreach ($rows as &$row) {
            $row['fields'] = json_decode($row['fields'], true) ?: [];
        }

        return $rows;
    }

    public function countEntries(array $filters = []): int
    {
        $sql    = "SELECT COUNT(*) FROM content_entries ce JOIN content_types ct ON ct.id = ce.content_type_id WHERE 1=1";
        $params = [];

        if (!empty($filters['content_type_id'])) {
            $sql .= " AND ce.content_type_id = ?";
            $params[] = (int)$filters['content_type_id'];
        }

        if (!empty($filters['type_slug'])) {
            $sql .= " AND ct.slug = ?";
            $params[] = $filters['type_slug'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND ce.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (ce.title LIKE ? OR ce.slug LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        return (int)$this->db->fetchColumn($sql, $params, 0);
    }

    public function getById(int $id): ?array
    {
        $row = $this->db->fetch(
            "SELECT ce.*, ct.name AS type_name, ct.slug AS type_slug FROM content_entries ce JOIN content_types ct ON ct.id = ce.content_type_id WHERE ce.id = ?",
            [$id]
        );
        if ($row) {
            $row['fields'] = json_decode($row['fields'], true) ?: [];
        }
        return $row ?: null;
    }

    public function getBySlug(string $slug, string $typeSlug = ''): ?array
    {
        $sql    = "SELECT ce.*, ct.name AS type_name, ct.slug AS type_slug FROM content_entries ce JOIN content_types ct ON ct.id = ce.content_type_id WHERE ce.slug = ?";
        $params = [$slug];

        if ($typeSlug) {
            $sql .= " AND ct.slug = ?";
            $params[] = $typeSlug;
        }

        $row = $this->db->fetch($sql, $params);
        if ($row) {
            $row['fields'] = json_decode($row['fields'], true) ?: [];
        }
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $fields = $data['fields'] ?? [];
        if (is_array($fields)) {
            $fields = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $entryId = (int)$this->db->insert('content_entries', [
            'content_type_id' => (int)($data['content_type_id'] ?? 0),
            'title'           => $data['title'] ?? 'Untitled',
            'slug'            => $data['slug'] ?? sluggify($data['title'] ?? 'entry'),
            'fields'          => $fields,
            'excerpt'         => $data['excerpt'] ?? null,
            'featured_image'  => $data['featured_image'] ?? null,
            'status'          => $data['status'] ?? 'draft',
            'sort_order'      => (int)($data['sort_order'] ?? 0),
            'created_by'      => $data['created_by'] ?? null,
        ]);

        searchIndexer()?->indexItem('content_entry', $entryId);
        sitemapGenerator()?->regenerate();

        return $entryId;
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'slug', 'fields', 'excerpt', 'featured_image', 'status', 'sort_order'];
        $update  = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                if ($key === 'fields' && is_array($val)) {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $update[$key] = $val;
            }
        }

        if (empty($update)) return false;

        $this->db->update('content_entries', $update, 'id = ?', [$id]);

        searchIndexer()?->indexItem('content_entry', $id);
        sitemapGenerator()?->regenerate();

        return true;
    }

    public function delete(int $id): bool
    {
        $this->db->delete('content_entries', 'id = ?', [$id]);

        searchIndexer()?->removeItem('content_entry', $id);
        sitemapGenerator()?->regenerate();

        return true;
    }

    /**
     * Reorder entries by ID array.
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $entryId) {
            $this->db->update('content_entries', ['sort_order' => $index], 'id = ?', [(int)$entryId]);
        }
    }

    /**
     * Get a field value from an entry.
     */
    public function getField(array $entry, string $fieldName, mixed $default = null): mixed
    {
        return $entry['fields'][$fieldName] ?? $default;
    }
}
