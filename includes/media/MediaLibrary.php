<?php
/**
 * Swap Design - Media Library
 *
 * Media CRUD operations, browser queries, and upload orchestration.
 * Uses FileUploader + ImageOptimizer for file handling.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class MediaLibrary
{
    private Database $db;
    private FileUploader $uploader;
    private ImageOptimizer $optimizer;
    private string $baseDir;

    public function __construct(?string $baseDir = null)
    {
        $this->db        = Database::getInstance();
        $this->uploader  = new FileUploader();
        $this->optimizer = new ImageOptimizer();
        $this->baseDir   = $baseDir ?? dirname(__DIR__) . '/';
    }

    /**
     * Upload and register a file in the media library.
     *
     * @param array  $file    $_FILES element
     * @param array  $meta    Optional metadata (alt_text, title, caption, folder_id)
     * @param int    $userId  Uploading user ID
     * @return array          Result with media record or error
     */
    public function upload(array $file, array $meta = [], ?int $userId = null): array
    {
        $upload = $this->uploader->upload($file);

        if (!$upload['success']) {
            return $upload;
        }

        /* Insert DB record */
        $mediaId = (int) $this->db->insert('media_library', [
            'filename'      => $upload['filename'],
            'original_name' => $upload['original'],
            'mime_type'     => $upload['mime'],
            'file_size'     => $upload['size'],
            'file_hash'     => $upload['hash'],
            'alt_text'      => $meta['alt_text'] ?? '',
            'title'         => $meta['title'] ?? pathinfo($upload['original'], PATHINFO_FILENAME),
            'caption'       => $meta['caption'] ?? null,
            'folder_id'     => !empty($meta['folder_id']) ? (int) $meta['folder_id'] : null,
            'uploaded_by'   => $userId,
        ]);

        /* Generate thumbnails for images */
        $isImage = str_starts_with($upload['mime'], 'image/');
        $hasWebp = false;
        $hasThumb = false;
        $width  = null;
        $height = null;
        $color  = null;

        if ($isImage) {
            $fullPath = $this->baseDir . ltrim($upload['path'], '/');

            /* Dimensions */
            $dims = $this->optimizer->getImageDimensions($fullPath);
            if ($dims) {
                $width  = $dims['width'];
                $height = $dims['height'];
            }

            /* Dominant color */
            $color = $this->optimizer->getDominantColor($fullPath);

            /* Thumbnails */
            $this->optimizer->generateSizes($upload['path']);
            $hasThumb = true;

            /* WebP (skip SVG) */
            if ($upload['mime'] !== 'image/svg+xml') {
                $webpPath = $this->optimizer->generateWebP($fullPath);
                $hasWebp  = $webpPath !== null;
            }

            $this->db->update('media_library', [
                'width'          => $width,
                'height'         => $height,
                'dominant_color' => $color,
                'has_webp'       => $hasWebp ? 1 : 0,
                'has_thumb'      => $hasThumb ? 1 : 0,
            ], 'id = ?', [$mediaId]);
        }

        /* Return full record */
        $record = $this->getById($mediaId);
        $record['duplicate'] = $upload['duplicate'];

        return ['success' => true, 'media' => $record, 'error' => ''];
    }

    /**
     * Get a media record by ID.
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM media_library WHERE id = ?", [$id]);
    }

    /**
     * Get media records with filtering and pagination.
     */
    public function getMedia(array $filters = []): array
    {
        $sql    = "SELECT * FROM media_library WHERE is_trashed = 0";
        $params = [];

        if (!empty($filters['folder_id'])) {
            $sql .= " AND folder_id = ?";
            $params[] = (int) $filters['folder_id'];
        }

        if (!empty($filters['folder_root'])) {
            $sql .= " AND folder_id IS NULL";
        }

        if (!empty($filters['mime_type'])) {
            if ($filters['mime_type'] === 'image') {
                $sql .= " AND mime_type LIKE 'image/%' AND mime_type != 'image/svg+xml'";
            } elseif ($filters['mime_type'] === 'svg') {
                $sql .= " AND mime_type = 'image/svg+xml'";
            } elseif ($filters['mime_type'] === 'document') {
                $sql .= " AND mime_type NOT LIKE 'image/%' AND mime_type NOT LIKE 'video/%' AND mime_type NOT LIKE 'audio/%'";
            } else {
                $sql .= " AND mime_type LIKE ?";
                $params[] = $filters['mime_type'] . '%';
            }
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (original_name LIKE ? OR title LIKE ? OR alt_text LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        /* Sort */
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = strtoupper($filters['order_dir'] ?? 'DESC');
        $allowedColumns = ['id', 'filename', 'original_name', 'file_size', 'created_at', 'updated_at'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'created_at';
        }
        if (!in_array($orderDir, ['ASC', 'DESC'])) {
            $orderDir = 'DESC';
        }
        $sql .= " ORDER BY {$orderBy} {$orderDir}";

        /* Pagination */
        $limit  = isset($filters['limit']) ? (int) $filters['limit'] : 20;
        $offset = isset($filters['offset']) ? (int) $filters['offset'] : 0;
        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Count media records matching filters.
     */
    public function countMedia(array $filters = []): int
    {
        $sql    = "SELECT COUNT(*) FROM media_library WHERE is_trashed = 0";
        $params = [];

        if (!empty($filters['folder_id'])) {
            $sql .= " AND folder_id = ?";
            $params[] = (int) $filters['folder_id'];
        }

        if (!empty($filters['mime_type'])) {
            if ($filters['mime_type'] === 'image') {
                $sql .= " AND mime_type LIKE 'image/%'";
            } else {
                $sql .= " AND mime_type LIKE ?";
                $params[] = $filters['mime_type'] . '%';
            }
        }

        return (int) $this->db->fetchColumn($sql, $params, 0);
    }

    /**
     * Update media metadata.
     */
    public function updateMeta(int $id, array $data): bool
    {
        $allowed = ['alt_text', 'title', 'caption', 'folder_id'];
        $update = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key];
            }
        }

        if (empty($update)) {
            return false;
        }

        $this->db->update('media_library', $update, 'id = ?', [$id]);
        return true;
    }

    /**
     * Soft-delete (trash) a media item.
     */
    public function trash(int $id): void
    {
        $this->db->update('media_library', [
            'is_trashed' => 1,
            'trashed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    /**
     * Restore a trashed media item.
     */
    public function restore(int $id): void
    {
        $this->db->update('media_library', [
            'is_trashed' => 0,
            'trashed_at' => null,
        ], 'id = ?', [$id]);
    }

    /**
     * Permanently delete a media item and its files.
     */
    public function deletePermanent(int $id): bool
    {
        $media = $this->getById($id);
        if (!$media) {
            return false;
        }

        $hash     = $media['file_hash'];
        $ext      = pathinfo($media['filename'], PATHINFO_EXTENSION);
        $datePart = substr($hash, 0, 4) . '/' . substr($hash, 4, 2);

        /* Only delete originals if no other records use this hash */
        $hashCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM media_library WHERE file_hash = ? AND id != ?",
            [$hash, $id],
            0
        );

        if ($hashCount === 0) {
            /* Delete original */
            $origPath = $this->baseDir . "uploads/originals/{$datePart}/{$hash}.{$ext}";
            if (file_exists($origPath)) {
                unlink($origPath);
            }

            /* Delete WebP */
            $webpPath = $this->baseDir . "uploads/webp/{$datePart}/{$hash}.webp";
            if (file_exists($webpPath)) {
                unlink($webpPath);
            }

            /* Delete generated sizes */
            $genBase = $this->baseDir . "uploads/generated/";
            foreach (['thumb', 'small', 'medium', 'large', 'admin'] as $size) {
                $genPath = $genBase . "{$size}/{$datePart}/{$hash}.{$ext}";
                if (file_exists($genPath)) {
                    unlink($genPath);
                }
            }
        }

        $this->db->delete('media_library', 'id = ?', [$id]);
        return true;
    }

    /* ================================================================
       Folders
       ================================================================ */

    /**
     * Get all media folders.
     */
    public function getFolders(): array
    {
        return $this->db->fetchAll("SELECT * FROM media_folders ORDER BY name ASC");
    }

    /**
     * Create a folder.
     */
    public function createFolder(string $name, ?int $parentId = null): int
    {
        return (int) $this->db->insert('media_folders', [
            'name'      => $name,
            'slug'      => sluggify($name),
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Delete a folder.
     */
    public function deleteFolder(int $id): bool
    {
        $this->db->update('media_library', ['folder_id' => null], 'folder_id = ?', [$id]);
        $this->db->delete('media_folders', 'id = ?', [$id]);
        return true;
    }
}
