<?php
/**
 * Swap Design - Slug Manager
 *
 * Handles slug generation, uniqueness checking, and URL redirect
 * tracking when slugs change.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class SlugManager
{
    private Database $db;

    /** Reserved slugs that cannot be used for pages or entries */
    public const RESERVED = [
        'admin', 'api', 'assets', 'includes', 'uploads', 'cache',
        'login', 'logout', 'dashboard', 'wp-admin', 'wp-content',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate a unique slug from a string.
     */
    public function generate(string $text, string $table = 'pages', string $column = 'slug', ?int $excludeId = null): string
    {
        $slug = sluggify($text);

        if (in_array($slug, self::RESERVED)) {
            $slug .= '-page';
        }

        $slug = $this->ensureUnique($slug, $table, $column, $excludeId);

        return $slug;
    }

    /**
     * Ensure a slug is unique by appending a counter if needed.
     */
    public function ensureUnique(string $slug, string $table = 'pages', string $column = 'slug', ?int $excludeId = null): string
    {
        $base = preg_replace('/-\d+$/', '', $slug);
        $counter = 1;
        $candidate = $slug;

        while ($this->slugExists($candidate, $table, $column, $excludeId)) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * Check if a slug already exists in a table.
     */
    public function slugExists(string $slug, string $table = 'pages', string $column = 'slug', ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $params = [$slug];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        return (int)$this->db->fetchColumn($sql, $params, 0) > 0;
    }

    /**
     * Record a redirect when a slug changes.
     */
    public function recordRedirect(string $oldSlug, string $newSlug, string $redirectType = 'page', ?int $targetId = null, int $statusCode = 301): void
    {
        $this->db->query(
            "INSERT INTO url_redirects (old_slug, new_slug, redirect_type, target_id, status_code)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE new_slug = VALUES(new_slug), status_code = VALUES(status_code)",
            [$oldSlug, $newSlug, $redirectType, $targetId, $statusCode]
        );
    }

    /**
     * Look up a redirect for an old slug.
     */
    public function getRedirect(string $oldSlug, string $redirectType = 'page'): ?array
    {
        $row = $this->db->fetch(
            "SELECT * FROM url_redirects WHERE old_slug = ? AND redirect_type = ?",
            [$oldSlug, $redirectType]
        );

        if ($row) {
            $this->db->query(
                "UPDATE url_redirects SET hit_count = hit_count + 1 WHERE id = ?",
                [(int)$row['id']]
            );
        }

        return $row ?: null;
    }

    /**
     * Process a slug change for any entity: generate new unique slug,
     * record redirect, return the new slug.
     */
    public function handleSlugChange(string $newText, string $oldSlug, string $table = 'pages', string $column = 'slug', ?int $excludeId = null, string $redirectType = 'page', ?int $targetId = null): string
    {
        $newSlug = $this->generate($newText, $table, $column, $excludeId);

        if ($oldSlug && $newSlug !== $oldSlug) {
            $this->recordRedirect($oldSlug, $newSlug, $redirectType, $targetId);
        }

        return $newSlug;
    }

    /**
     * Check if a slug is reserved.
     */
    public function isReserved(string $slug): bool
    {
        return in_array($slug, self::RESERVED);
    }
}
