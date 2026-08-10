<?php
/**
 * Swap Design - Navigation Manager
 *
 * CRUD operations for editable navigation menus.
 * Supports parent/child hierarchy, ordering, visibility toggles.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class NavigationManager
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all menu items for a location, ordered by sort_order.
     *
     * @param string $location 'primary', 'footer', etc. Empty string for all.
     * @return array
     */
    public function getMenu(string $location = 'primary'): array
    {
        if ($location === '') {
            return $this->db->fetchAll(
                "SELECT * FROM navigation_menu ORDER BY location ASC, sort_order ASC"
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM navigation_menu WHERE location = ? ORDER BY sort_order ASC",
            [$location]
        );
    }

    /**
     * Get a menu tree (nested parent/child structure).
     *
     * @param string $location
     * @return array
     */
    public function getMenuTree(string $location = 'primary'): array
    {
        $items = $this->getMenu($location);
        return $this->buildTree($items);
    }

    /**
     * Get a single menu item.
     */
    public function getItem(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM navigation_menu WHERE id = ?", [$id]);
    }

    /**
     * Create a new menu item.
     *
     * @return int Insert ID
     */
    public function create(array $data): int
    {
        $maxOrder = (int) $this->db->fetchColumn(
            "SELECT COALESCE(MAX(sort_order), -1) FROM navigation_menu WHERE location = ? AND parent_id <=> ?",
            [$data['location'] ?? 'primary', $data['parent_id'] ?? null],
            -1
        );

        $insert = [
            'parent_id'    => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'label'        => $data['label'] ?? 'New Item',
            'url'          => $data['url'] ?? '#',
            'slug'         => $data['slug'] ?? sluggify($data['label'] ?? 'item'),
            'location'     => $data['location'] ?? 'primary',
            'sort_order'   => $maxOrder + 1,
            'is_visible'   => isset($data['is_visible']) ? (int) $data['is_visible'] : 1,
            'open_new_tab' => isset($data['open_new_tab']) ? (int) $data['open_new_tab'] : 0,
        ];

        return (int) $this->db->insert('navigation_menu', $insert);
    }

    /**
     * Update a menu item.
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['parent_id', 'label', 'url', 'slug', 'location', 'sort_order', 'is_visible', 'open_new_tab'];
        $update = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                if ($key === 'parent_id') {
                    $val = $val ? (int) $val : null;
                }
                $update[$key] = $val;
            }
        }

        if (empty($update)) {
            return false;
        }

        $this->db->update('navigation_menu', $update, 'id = ?', [$id]);
        return true;
    }

    /**
     * Delete a menu item and its children.
     */
    public function delete(int $id): bool
    {
        $children = $this->db->fetchAll(
            "SELECT id FROM navigation_menu WHERE parent_id = ?",
            [$id]
        );

        foreach ($children as $child) {
            $this->delete((int) $child['id']);
        }

        $this->db->delete('navigation_menu', 'id = ?', [$id]);
        return true;
    }

    /**
     * Reorder menu items from a list of IDs.
     *
     * @param array $orderedIds Ordered array of item IDs
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            $this->db->update(
                'navigation_menu',
                ['sort_order' => $index],
                'id = ?',
                [(int) $id]
            );
        }
    }

    /**
     * Build a nested tree from flat results.
     */
    private function buildTree(array $items, ?int $parentId = null): array
    {
        $branch = [];

        foreach ($items as $item) {
            if ((int) ($item['parent_id'] ?? 0) === (int) $parentId) {
                $children = $this->buildTree($items, (int) $item['id']);
                $item['children'] = $children;
                $branch[] = $item;
            }
        }

        return $branch;
    }

    /**
     * Get available parent items for a dropdown.
     */
    public function getParentOptions(string $location = 'primary', ?int $excludeId = null): array
    {
        $sql  = "SELECT id, label, parent_id, location FROM navigation_menu WHERE parent_id IS NULL";
        $params = [];

        if ($location !== '') {
            $sql .= " AND location = ?";
            $params[] = $location;
        }

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $sql .= " ORDER BY location ASC, sort_order ASC";
        return $this->db->fetchAll($sql, $params);
    }
}
