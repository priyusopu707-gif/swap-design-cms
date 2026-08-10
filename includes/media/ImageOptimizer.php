<?php
/**
 * Swap Design - Image Optimizer
 *
 * Generates WebP versions and responsive thumbnail sizes
 * for uploaded images. Works with GD or Imagick.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class ImageOptimizer
{
    private string $baseDir;

    /** Thumbnail size presets */
    private const SIZES = [
        'thumb'   => ['w' => 150,  'h' => 150,  'crop' => true],
        'small'   => ['w' => 300,  'h' => 300,  'crop' => false],
        'medium'  => ['w' => 600,  'h' => 600,  'crop' => false],
        'large'   => ['w' => 1200, 'h' => 1200, 'crop' => false],
        'admin'   => ['w' => 400,  'h' => 300,  'crop' => true],
    ];

    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? (dirname(__DIR__) . '/');
    }

    /**
     * Generate all thumbnail sizes for an uploaded image.
     *
     * @param string $relativePath Relative path from project root (e.g., 'uploads/originals/2026/07/abc.jpg')
     * @return array Generated size records
     */
    public function generateSizes(string $relativePath): array
    {
        $fullPath = $this->baseDir . ltrim($relativePath, '/');
        if (!file_exists($fullPath)) {
            return [];
        }

        $info    = pathinfo($fullPath);
        $results = [];

        foreach (self::SIZES as $sizeName => $size) {
            $generatedPath = $this->generateSize($fullPath, $sizeName, $size['w'], $size['h'], $size['crop']);
            if ($generatedPath) {
                $results[$sizeName] = str_replace($this->baseDir, '', $generatedPath);
            }
        }

        return $results;
    }

    /**
     * Generate WebP version of an image.
     *
     * @param string $fullPath Absolute path to source image
     * @return string|null     Relative WebP path, or null on failure
     */
    public function generateWebP(string $fullPath): ?string
    {
        if (!file_exists($fullPath)) {
            return null;
        }

        $mime = mime_content_type($fullPath);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
            return null;
        }

        /* Determine WebP output path */
        $info      = pathinfo($fullPath);
        $sourceRel = str_replace($this->baseDir, '', $fullPath);
        $webpRel   = preg_replace('#^uploads/originals/#', 'uploads/webp/', $sourceRel);
        $webpRel   = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $webpRel);
        $webpAbs   = $this->baseDir . ltrim($webpRel, '/');

        $webpDir = dirname($webpAbs);
        if (!is_dir($webpDir)) {
            mkdir($webpDir, 0755, true);
        }

        /* Generate WebP */
        $source = $this->createImageResource($fullPath, $mime);
        if (!$source) {
            return null;
        }

        $success = imagewebp($source, $webpAbs, 80);
        imagedestroy($source);

        return $success ? $webpRel : null;
    }

    /**
     * Generate a single resized version.
     *
     * @param string $fullPath Source image path
     * @param string $sizeName Size preset name
     * @param int    $maxW     Max width
     * @param int    $maxH     Max height
     * @param bool   $crop     Whether to hard-crop to exact dimensions
     * @return string|null     Absolute path to generated image
     */
    private function generateSize(string $fullPath, string $sizeName, int $maxW, int $maxH, bool $crop): ?string
    {
        $mime = mime_content_type($fullPath);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            return null;
        }

        $source = $this->createImageResource($fullPath, $mime);
        if (!$source) {
            return null;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        /* Calculate target dimensions */
        if ($crop) {
            $ratio  = max($maxW / $srcW, $maxH / $srcH);
            $cropW  = round($maxW / $ratio);
            $cropH  = round($maxH / $ratio);
            $cropX  = round(($srcW - $cropW) / 2);
            $cropY  = round(($srcH - $cropH) / 2);
            $destW  = $maxW;
            $destH  = $maxH;

            $resized = imagecreatetruecolor($maxW, $maxH);
            $this->preserveTransparency($resized, $mime);
            imagecopyresampled($resized, $source, 0, 0, (int) $cropX, (int) $cropY, $maxW, $maxH, (int) $cropW, (int) $cropH);
            $dest = $resized;
        } else {
            $ratio  = min($maxW / $srcW, $maxH / $srcH, 1);
            $destW  = (int) round($srcW * $ratio);
            $destH  = (int) round($srcH * $ratio);

            $resized = imagecreatetruecolor($destW, $destH);
            $this->preserveTransparency($resized, $mime);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $destW, $destH, $srcW, $srcH);
            $dest = $resized;
        }

        /* Output path */
        $sourceRel  = str_replace($this->baseDir, '', $fullPath);
        $generatedRel = preg_replace('#^uploads/originals/#', "uploads/generated/{$sizeName}/", $sourceRel);
        $generatedAbs = $this->baseDir . ltrim($generatedRel, '/');

        $genDir = dirname($generatedAbs);
        if (!is_dir($genDir)) {
            mkdir($genDir, 0755, true);
        }

        $success = false;
        switch ($mime) {
            case 'image/png':
                imagealphablending($dest, false);
                imagesavealpha($dest, true);
                $success = imagepng($dest, $generatedAbs, 8);
                break;
            case 'image/gif':
                $success = imagegif($dest, $generatedAbs);
                break;
            default:
                $success = imagejpeg($dest, $generatedAbs, 85);
        }

        imagedestroy($source);
        imagedestroy($dest);

        return $success ? $generatedAbs : null;
    }

    /**
     * Create a GD image resource from a file.
     */
    private function createImageResource(string $path, string $mime): \GdImage|false
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/gif'  => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default      => false,
        };
    }

    /**
     * Preserve transparency on new GD images.
     */
    private function preserveTransparency(\GdImage $img, string $mime): void
    {
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $transparent);
        }
    }

    /**
     * Get image dimensions from a file.
     */
    public function getImageDimensions(string $fullPath): ?array
    {
        if (!file_exists($fullPath)) {
            return null;
        }

        $size = getimagesize($fullPath);
        if (!$size) {
            return null;
        }

        return ['width' => $size[0], 'height' => $size[1]];
    }

    /**
     * Get the dominant color of an image (simple average).
     */
    public function getDominantColor(string $fullPath): ?string
    {
        if (!file_exists($fullPath)) {
            return null;
        }

        $mime = mime_content_type($fullPath);
        $source = $this->createImageResource($fullPath, $mime);
        if (!$source) {
            return null;
        }

        /* Scale down to 1x1 for average color */
        $thumb = imagecreatetruecolor(1, 1);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, 1, 1, imagesx($source), imagesy($source));
        $rgb = imagecolorat($thumb, 0, 0);

        imagedestroy($source);
        imagedestroy($thumb);

        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
