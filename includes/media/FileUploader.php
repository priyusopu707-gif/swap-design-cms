<?php
/**
 * Swap Design - File Uploader
 *
 * Secure file upload handling with validation, type checking,
 * and hash-based deduplication.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class FileUploader
{
    /** Allowed MIME types */
    private const ALLOWED_TYPES = [
        'image/jpeg'      => ['jpg', 'jpeg'],
        'image/png'       => ['png'],
        'image/gif'       => ['gif'],
        'image/webp'      => ['webp'],
        'image/svg+xml'   => ['svg'],
        'application/pdf' => ['pdf'],
        'text/plain'      => ['txt'],
        'text/csv'        => ['csv'],
    ];

    /** Maximum file size (20MB) */
    private const MAX_SIZE = 20971520;

    private string $uploadDir;

    public function __construct(?string $uploadDir = null)
    {
        $this->uploadDir = $uploadDir ?? (dirname(__DIR__) . '/uploads/');
    }

    /**
     * Upload a file from $_FILES array.
     *
     * @param array  $file      $_FILES['field_name']
     * @param string $subDir    Optional subdirectory (e.g., 'images')
     * @return array            ['success' => bool, 'path' => string, 'filename' => string, 'original' => string, 'mime' => string, 'size' => int, 'hash' => string, 'error' => string]
     */
    public function upload(array $file, string $subDir = ''): array
    {
        /* Validate upload */
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $this->error('No file uploaded or upload failed.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->error($this->uploadErrorMessage($file['error']));
        }

        if ($file['size'] > self::MAX_SIZE) {
            return $this->error('File exceeds maximum size of 20MB.');
        }

        if ($file['size'] === 0) {
            return $this->error('File is empty.');
        }

        /* Detect MIME type with finfo */
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::ALLOWED_TYPES[$mimeType])) {
            return $this->error("File type '{$mimeType}' is not allowed.");
        }

        /* Double-check with magic bytes for images */
        if (strpos($mimeType, 'image/') === 0 && $mimeType !== 'image/svg+xml') {
            if (!$this->validateImageMagicBytes($file['tmp_name'], $mimeType)) {
                return $this->error('File content does not match its declared type.');
            }
        }

        /* Verify extension matches MIME */
        $originalName = $this->sanitizeFilename($file['name']);
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_TYPES[$mimeType], true)) {
            /* Trust MIME over extension; use correct extension */
            $extension  = self::ALLOWED_TYPES[$mimeType][0];
            $originalName = pathinfo($originalName, PATHINFO_FILENAME) . '.' . $extension;
        }

        /* Generate content-hash filename */
        $fileHash       = hash_file('sha256', $file['tmp_name']);
        $storedFilename = $fileHash . '.' . $extension;

        /* Date-based directory sharding */
        $dateDir = date('Y/m');
        $targetDir = rtrim($this->uploadDir, '/') . '/originals/' . $dateDir;
        if ($subDir) {
            $targetDir .= '/' . trim($subDir, '/');
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $storedFilename;

        /* Check for duplicate */
        if (file_exists($targetPath)) {
            return [
                'success'  => true,
                'path'     => 'uploads/originals/' . $dateDir . '/' . $storedFilename,
                'filename' => $storedFilename,
                'original' => $originalName,
                'mime'     => $mimeType,
                'size'     => $file['size'],
                'hash'     => $fileHash,
                'duplicate'=> true,
                'error'    => '',
            ];
        }

        /* SVG sanitization */
        if ($mimeType === 'image/svg+xml') {
            if (!$this->sanitizeSvg($file['tmp_name'])) {
                return $this->error('SVG file contains potentially unsafe content.');
            }
        }

        /* Move file */
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $this->error('Failed to save uploaded file.');
        }

        chmod($targetPath, 0644);

        return [
            'success'  => true,
            'path'     => 'uploads/originals/' . $dateDir . '/' . $storedFilename,
            'filename' => $storedFilename,
            'original' => $originalName,
            'mime'     => $mimeType,
            'size'     => $file['size'],
            'hash'     => $fileHash,
            'duplicate'=> false,
            'error'    => '',
        ];
    }

    /**
     * Delete a file from disk.
     */
    public function delete(string $relativePath): bool
    {
        $fullPath = dirname(__DIR__) . '/' . ltrim($relativePath, '/');

        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Sanitize SVG to remove scripts and event handlers.
     */
    private function sanitizeSvg(string $filePath): bool
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $dangerous = [
            '/<script[^>]*>.*?<\/script>/si',
            '/on\w+\s*=\s*"[^"]*"/i',
            '/on\w+\s*=\s*\'[^\']*\'/i',
            '/<foreignObject[^>]*>.*?<\/foreignObject>/si',
        ];

        $cleaned = preg_replace($dangerous, '', $content);

        if ($cleaned !== $content) {
            return @file_put_contents($filePath, $cleaned) !== false;
        }

        return true;
    }

    /**
     * Validate image magic bytes match MIME type.
     */
    private function validateImageMagicBytes(string $filePath, string $mimeType): bool
    {
        $handle = @fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $bytes = fread($handle, 12);
        fclose($handle);

        if ($bytes === false || strlen($bytes) < 4) {
            return false;
        }

        return match ($mimeType) {
            'image/jpeg' => substr($bytes, 0, 2) === "\xFF\xD8",
            'image/png'  => substr($bytes, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A",
            'image/gif'  => substr($bytes, 0, 3) === 'GIF',
            'image/webp' => substr($bytes, 0, 4) === 'RIFF' && substr($bytes, 8, 4) === 'WEBP',
            default      => true,
        };
    }

    /**
     * Sanitize a filename.
     */
    public function sanitizeFilename(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        return trim($name, '-.');
    }

    /**
     * Get human-readable upload error message.
     */
    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload stopped by extension.',
            default               => 'Unknown upload error.',
        };
    }

    /**
     * Build error response.
     */
    private function error(string $message): array
    {
        return [
            'success'  => false,
            'path'     => '',
            'filename' => '',
            'original' => '',
            'mime'     => '',
            'size'     => 0,
            'hash'     => '',
            'duplicate'=> false,
            'error'    => $message,
        ];
    }
}
