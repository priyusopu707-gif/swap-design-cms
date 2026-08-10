<?php
/**
 * Swap Design - SEO Dashboard
 *
 * Comprehensive SEO audit, meta management, and site health monitoring.
 * Real-time checks for meta tags, structured data, sitemaps, and redirects.
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$auditor = new SEOAuditor();
$settings = new SettingsManager();

$action = $_GET['action'] ?? 'overview';
$stats = $auditor->getStats();
$pageTitle = 'SEO Dashboard';
?>
<?php require __DIR__ . '/includes/header.php'; ?>
<link rel="stylesheet" href="/admin/assets/css/seo-dashboard.css">

<div class="admin-page">
    <div class="admin-page__header">
        <h1 class="admin-page__title">SEO Dashboard</h1>
        <p>Comprehensive SEO audit and optimization insights</p>
    </div>

    <div class="admin-tabs">
        <button class="admin-tab <?php echo $action === 'overview' ? 'active' : ''; ?>" onclick="location.href='?action=overview'">
            Overview
        </button>
        <button class="admin-tab <?php echo $action === 'audit' ? 'active' : ''; ?>" onclick="location.href='?action=audit'">
            Full Audit
        </button>
        <button class="admin-tab <?php echo $action === 'meta' ? 'active' : ''; ?>" onclick="location.href='?action=meta'">
            Meta Management
        </button>
        <button class="admin-tab <?php echo $action === 'schema' ? 'active' : ''; ?>" onclick="location.href='?action=schema'">
            Structured Data
        </button>
        <button class="admin-tab <?php echo $action === 'sitemap' ? 'active' : ''; ?>" onclick="location.href='?action=sitemap'">
            Sitemap
        </button>
    </div>

    <?php if ($action === 'overview'): ?>
        <?php include __DIR__ . '/includes/seo/overview.php'; ?>
    <?php elseif ($action === 'audit'): ?>
        <?php include __DIR__ . '/includes/seo/audit.php'; ?>
    <?php elseif ($action === 'meta'): ?>
        <?php include __DIR__ . '/includes/seo/meta-management.php'; ?>
    <?php elseif ($action === 'schema'): ?>
        <?php include __DIR__ . '/includes/seo/schema-verification.php'; ?>
    <?php elseif ($action === 'sitemap'): ?>
        <?php include __DIR__ . '/includes/seo/sitemap-check.php'; ?>
    <?php endif; ?>

</div>

<script src="/admin/assets/js/seo-dashboard.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>