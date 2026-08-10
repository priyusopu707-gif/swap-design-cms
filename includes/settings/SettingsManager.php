<?php
/**
 * Swap Design - Settings Manager
 *
 * CRUD operations for the site_settings key-value table.
 * Supports dot-notation keys (e.g., 'brand.name') and JSON values.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class SettingsManager
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->db->fetch(
            "SELECT setting_value FROM site_settings WHERE setting_key = ?",
            [$key]
        );

        if (!$row) {
            return $default;
        }

        $value = $row['setting_value'];
        $decoded = json_decode($value, true);
        return ($decoded !== null && $value !== null) ? $decoded : $value;
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value): void
    {
        $encoded = is_array($value) || is_object($value)
            ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : (string) $value;

        $exists = $this->db->exists('site_settings', 'setting_key = ?', [$key]);

        if ($exists) {
            $this->db->update('site_settings', ['setting_value' => $encoded], 'setting_key = ?', [$key]);
        } else {
            $this->db->insert('site_settings', [
                'setting_key'   => $key,
                'setting_value' => $encoded,
            ]);
        }
    }

    /**
     * Get all settings matching a key prefix.
     */
    public function getByPrefix(string $prefix): array
    {
        $rows = $this->db->fetchAll(
            "SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE ?",
            [$prefix . '%']
        );

        $result = [];
        foreach ($rows as $row) {
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            $decoded = json_decode($value, true);
            $result[$key] = ($decoded !== null && $value !== null) ? $decoded : $value;
        }

        return $result;
    }

    /**
     * Save multiple settings at once.
     */
    public function saveBatch(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Delete a setting.
     */
    public function delete(string $key): void
    {
        $this->db->delete('site_settings', 'setting_key = ?', [$key]);
    }

    /**
     * Export all settings as an associative array.
     */
    public function exportAll(): array
    {
        $rows = $this->db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
        $result = [];
        foreach ($rows as $row) {
            $value = $row['setting_value'];
            $decoded = json_decode($value, true);
            $result[$row['setting_key']] = ($decoded !== null && $value !== null) ? $decoded : $value;
        }
        return $result;
    }
}
