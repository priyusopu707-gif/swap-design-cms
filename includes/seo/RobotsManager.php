<?php
/**
 * Swap Design - Robots Manager
 *
 * Generates robots.txt from SettingsManager-backed configuration and
 * writes it to the project root so it is served as a static file
 * (Hostinger-safe, no routing changes required).
 *
 * Stored settings (dot-notation):
 *   seo.robots.enabled          '1'/'0' - serve generated robots.txt
 *   seo.robots.user_agents      textarea, one rule group per line
 *   seo.robots.allow            textarea, one Allow path per line
 *   seo.robots.disallow         textarea, one Disallow path per line
 *   seo.robots.custom           textarea, raw directives appended
 *   seo.robots.sitemaps         textarea, one sitemap URL per line
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class RobotsManager
{
    private SettingsManager $settings;
    private Database $db;

    public function __construct()
    {
        $this->settings = new SettingsManager();
        $this->db       = Database::getInstance();
    }

    /* ================================================================
       Public API
       ================================================================ */

    /**
     * Build robots.txt content from settings.
     *
     * @return string Full robots.txt body (without trailing newline)
     */
    public function build(): string
    {
        $lines = [];

        $custom = trim((string)$this->settings->get('seo.robots.custom', ''));
        $userAgents = $this->lines('seo.robots.user_agents', ["User-agent: *"]);

        foreach ($userAgents as $ua) {
            if (trim($ua) === '') {
                continue;
            }
            $lines[] = trim($ua);

            foreach ($this->lines('seo.robots.allow', []) as $allow) {
                if (trim($allow) !== '') {
                    $lines[] = 'Allow: ' . trim($allow);
                }
            }

            foreach ($this->lines('seo.robots.disallow', ['/admin/', '/includes/', '/database/', '/api/', '/logs/', '/cache/']) as $disallow) {
                if (trim($disallow) !== '') {
                    $lines[] = 'Disallow: ' . trim($disallow);
                }
            }

            if ($custom !== '') {
                $lines[] = '';
                $lines[] = $custom;
            }

            $lines[] = '';
        }

        /* Sitemap references */
        $sitemaps = $this->lines('seo.robots.sitemaps', $this->defaultSitemaps());
        foreach ($sitemaps as $sitemap) {
            if (trim($sitemap) !== '') {
                $lines[] = 'Sitemap: ' . trim($sitemap);
            }
        }

        /* Trim trailing blank lines */
        while (end($lines) === '') {
            array_pop($lines);
        }

        return implode("\n", $lines);
    }

    /**
     * Regenerate robots.txt on disk.
     *
     * @return bool True when the file was written successfully
     */
    public function regenerate(): bool
    {
        if (!$this->isEnabled()) {
            return $this->removeFile();
        }

        $content = $this->build();
        return @file_put_contents(ROOT_PATH . '/robots.txt', $content) !== false;
    }

    /**
     * Whether robots.txt generation is enabled in settings.
     */
    public function isEnabled(): bool
    {
        return (string)$this->settings->get('seo.robots.enabled', '1') === '1';
    }

    /* ================================================================
       Helpers
       ================================================================ */

    /**
     * Read a multiline setting as an array of trimmed lines.
     */
    private function lines(string $key, array $default = []): array
    {
        $value = (string)$this->settings->get($key, '');
        if ($value === '') {
            return $default;
        }

        return preg_split('/\r\n|\r|\n/', $value) ?: [];
    }

    /**
     * Default sitemap URLs derived from the current site base URL.
     */
    private function defaultSitemaps(): array
    {
        return [SITE_URL . '/sitemap.xml'];
    }

    /**
     * Remove the robots.txt file from disk.
     */
    private function removeFile(): bool
    {
        $path = ROOT_PATH . '/robots.txt';
        if (is_file($path)) {
            return @unlink($path);
        }
        return true;
    }
}
