<?php
/**
 * Swap Design - Dev Database Migration Fix
 *
 * Adds missing columns to portfolio_items that failed during
 * schema import due to MariaDB compat issues with ALTER TABLE.
 */

define('SWAP_ROOT', true);

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'swap';
$pass = getenv('DB_PASS') ?: 'swap_dev_pass';
$name = getenv('DB_NAME') ?: 'swap_design';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    /* Check which columns are missing from portfolio_items */
    $existing = $pdo->query("DESCRIBE portfolio_items")->fetchAll(PDO::FETCH_COLUMN, 0);
    $existing = array_flip($existing);

    $needed = [
        'client_name'       => "VARCHAR(200) NULL AFTER category",
        'industry'          => "VARCHAR(100) NULL AFTER client_name",
        'completion_date'   => "DATE NULL AFTER industry",
        'project_url'       => "VARCHAR(500) NULL AFTER completion_date",
        'full_description'  => "LONGTEXT NULL AFTER description",
        'gallery_images'    => "JSON NULL AFTER image_url",
        'hero_title'        => "VARCHAR(200) NULL AFTER project_url",
        'hero_description'  => "TEXT NULL AFTER hero_title",
        'hero_image'        => "VARCHAR(500) NULL AFTER hero_description",
        'hero_bg_image'     => "VARCHAR(500) NULL AFTER hero_image",
        'hero_cta_text'     => "VARCHAR(100) NULL AFTER hero_bg_image",
        'hero_cta_url'      => "VARCHAR(300) NULL AFTER hero_cta_text",
        'overview_summary'  => "TEXT NULL AFTER hero_cta_url",
        'overview_requirements' => "TEXT NULL AFTER overview_summary",
        'overview_problem'  => "TEXT NULL AFTER overview_requirements",
        'overview_objectives' => "TEXT NULL AFTER overview_problem",
        'solution_strategy' => "TEXT NULL AFTER overview_objectives",
        'solution_branding' => "TEXT NULL AFTER solution_strategy",
        'solution_process'  => "TEXT NULL AFTER solution_branding",
        'solution_tech'     => "TEXT NULL AFTER solution_process",
        'results_summary'   => "TEXT NULL AFTER solution_tech",
        'results_achievements' => "TEXT NULL AFTER results_summary",
        'results_feedback'  => "TEXT NULL AFTER results_achievements",
        'project_duration'  => "VARCHAR(100) NULL AFTER results_feedback",
        'project_deliverables' => "TEXT NULL AFTER project_duration",
        'project_services_used' => "VARCHAR(500) NULL AFTER project_deliverables",
        'cta_heading'       => "VARCHAR(200) NULL AFTER project_services_used",
        'cta_description'   => "TEXT NULL AFTER cta_heading",
        'cta_button_text'   => "VARCHAR(100) NULL AFTER cta_description",
        'cta_button_url'    => "VARCHAR(300) NULL AFTER cta_button_text",
        'cta_show_whatsapp' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER cta_button_url",
        'cta_whatsapp_label' => "VARCHAR(100) NULL AFTER cta_show_whatsapp",
        'cta_bg_image'      => "VARCHAR(500) NULL AFTER cta_whatsapp_label",
        'seo_title'         => "VARCHAR(200) NULL AFTER cta_bg_image",
        'meta_description'  => "VARCHAR(320) NULL AFTER seo_title",
        'focus_keyword'     => "VARCHAR(200) NULL AFTER meta_description",
        'canonical_url'     => "VARCHAR(500) NULL AFTER focus_keyword",
        'og_image'          => "VARCHAR(500) NULL AFTER canonical_url",
        'updated_at'        => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    ];

    $added = 0;
    foreach ($needed as $col => $def) {
        if (!isset($existing[$col])) {
            $sql = "ALTER TABLE portfolio_items ADD COLUMN $col $def";
            $pdo->exec($sql);
            echo "  Added column: $col\n";
            $added++;
        }
    }

    /* Also check the pages ALTER TABLE */
    $pagesCols = $pdo->query("DESCRIBE pages")->fetchAll(PDO::FETCH_COLUMN, 0);
    $pagesCols = array_flip($pagesCols);
    $pagesNeeded = [
        'layout_id'  => "INT UNSIGNED NULL AFTER meta_desc",
        'template'   => "VARCHAR(100) NULL AFTER layout_id",
        'is_homepage' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER template",
        'show_in_nav' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_homepage",
        'nav_label'  => "VARCHAR(100) NULL AFTER show_in_nav",
    ];
    foreach ($pagesNeeded as $col => $def) {
        if (!isset($pagesCols[$col])) {
            $sql = "ALTER TABLE pages ADD COLUMN $col $def";
            $pdo->exec($sql);
            echo "  Added column: pages.$col\n";
            $added++;
        }
    }

    if ($added === 0) {
        echo "All columns already exist.\n";
    } else {
        echo "Migration complete: $added columns added.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
