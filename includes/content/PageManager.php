<?php
/**
 * Swap Design - Page Manager
 *
 * CRUD operations for dynamic pages. Handles page creation,
 * layout assignment, slug management, and homepage management.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class PageManager
{
    private Database $db;
    private SlugManager $slugManager;
    private SectionManager $sectionManager;

    public function __construct()
    {
        $this->db             = Database::getInstance();
        $this->slugManager    = new SlugManager();
        $this->sectionManager = new SectionManager();
    }

    public function getAll(array $filters = []): array
    {
        $sql    = "SELECT p.*, l.name AS layout_name FROM pages p LEFT JOIN layouts l ON l.id = p.layout_id WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.title LIKE ? OR p.slug LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY p.is_homepage DESC, p.updated_at DESC";

        $limit  = isset($filters['limit']) ? (int)$filters['limit'] : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    public function countPages(array $filters = []): int
    {
        $sql    = "SELECT COUNT(*) FROM pages WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR slug LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        return (int)$this->db->fetchColumn($sql, $params, 0);
    }

    public function getById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT p.*, l.name AS layout_name FROM pages p LEFT JOIN layouts l ON l.id = p.layout_id WHERE p.id = ?",
            [$id]
        );
    }

    public function getBySlug(string $slug): ?array
    {
        return $this->db->fetch("SELECT * FROM pages WHERE slug = ?", [$slug]);
    }

    public function getHomepage(): ?array
    {
        return $this->db->fetch("SELECT * FROM pages WHERE is_homepage = 1 AND status = 'published' LIMIT 1");
    }

    public function create(array $data): int
    {
        $title = $data['title'] ?? 'Untitled Page';
        $slug  = $data['slug'] ?? '';

        if (!$slug) {
            $slug = $this->slugManager->generate($title, 'pages', 'slug');
        } else {
            $slug = $this->slugManager->ensureUnique($slug, 'pages', 'slug');
        }

        $pageId = (int)$this->db->insert('pages', [
            'slug'        => $slug,
            'title'       => $title,
            'meta_desc'   => $data['meta_desc'] ?? '',
            'layout_id'   => !empty($data['layout_id']) ? (int)$data['layout_id'] : null,
            'template'    => $data['template'] ?? null,
            'content'     => $data['content'] ?? '',
            'status'      => $data['status'] ?? 'draft',
            'is_homepage' => (int)($data['is_homepage'] ?? 0),
            'show_in_nav' => (int)($data['show_in_nav'] ?? 0),
            'nav_label'   => $data['nav_label'] ?? null,
        ]);

        if ($data['is_homepage'] ?? false) {
            $this->setAsHomepage($pageId);
        }

        searchIndexer()?->indexItem('page', $pageId);
        sitemapGenerator()?->regenerate();

        return $pageId;
    }

    public function update(int $id, array $data): bool
    {
        /* Handle slug changes */
        if (isset($data['title']) && !isset($data['slug'])) {
            $oldPage = $this->getById($id);
            if ($oldPage && !empty($data['title'])) {
                $newSlug = $this->slugManager->handleSlugChange(
                    $data['title'],
                    $oldPage['slug'],
                    'pages', 'slug', $id, 'page', $id
                );
                $data['slug'] = $newSlug;
            }
        }

        $allowed = ['slug', 'title', 'meta_desc', 'layout_id', 'template',
                    'content', 'status', 'is_homepage', 'show_in_nav', 'nav_label'];
        $update  = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key];
            }
        }

        if (empty($update)) return false;

        $this->db->update('pages', $update, 'id = ?', [$id]);

        if (isset($update['is_homepage']) && $update['is_homepage']) {
            $this->setAsHomepage($id);
        }

        searchIndexer()?->indexItem('page', $id);
        sitemapGenerator()?->regenerate();

        return true;
    }

    public function delete(int $id): bool
    {
        $this->db->delete('page_sections', 'page_id = ?', [$id]);
        $this->db->delete('pages', 'id = ?', [$id]);

        searchIndexer()?->removeItem('page', $id);
        sitemapGenerator()?->regenerate();

        return true;
    }

    public function duplicate(int $id): int
    {
        $original = $this->getById($id);
        if (!$original) {
            throw new \RuntimeException('Page not found');
        }

        $newId = $this->create([
            'title'       => $original['title'] . ' (Copy)',
            'meta_desc'   => $original['meta_desc'],
            'layout_id'   => $original['layout_id'],
            'template'    => $original['template'],
            'content'     => $original['content'],
            'status'      => 'draft',
            'show_in_nav' => 0,
        ]);

        /* Duplicate page sections */
        $sections = $this->sectionManager->getPageSections((int)$original['id']);
        foreach ($sections as $ps) {
            $this->sectionManager->assignToPage(
                $newId,
                (int)$ps['id'],
                $ps['zone_key'] ?? 'content',
                (int)($ps['sort_order'] ?? 0)
            );
        }

        return $newId;
    }

    /**
     * Set a page as the homepage (unsets previous homepage).
     */
    public function setAsHomepage(int $id): void
    {
        $this->db->query("UPDATE pages SET is_homepage = 0 WHERE is_homepage = 1");
        $this->db->update('pages', ['is_homepage' => 1], 'id = ?', [$id]);
    }

    /**
     * Assign a layout to a page and return the new zones.
     */
    public function assignLayout(int $pageId, int $layoutId): array
    {
        $this->db->update('pages', ['layout_id' => $layoutId], 'id = ?', [$pageId]);

        $layoutManager = new LayoutManager();
        return $layoutManager->getZoneMap($layoutId);
    }
}
