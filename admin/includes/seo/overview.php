<?php
/**
 * SEO Dashboard - Overview Tab
 */

$audit = $auditor->runAudit();
$totals = $audit['totals'];

/* Calculate SEO score (0-100) */
$maxIssues = 100;
$totalIssues = array_sum($totals);
$seoScore = max(0, 100 - ($totalIssues * 5));
$seoScore = min(100, $seoScore);

/* Determine health status */
if ($seoScore >= 80) {
    $healthStatus = 'Excellent';
    $healthColor = '#22c55e';
} elseif ($seoScore >= 60) {
    $healthStatus = 'Good';
    $healthColor = '#3b82f6';
} elseif ($seoScore >= 40) {
    $healthStatus = 'Fair';
    $healthColor = '#f59e0b';
} else {
    $healthStatus = 'Needs Work';
    $healthColor = '#ef4444';
}
?>

<div class="seo-overview">
    <!-- SEO Score Card -->
    <div class="seo-score-card">
        <div class="score-circle" style="background: conic-gradient(<?php echo $healthColor; ?> 0deg <?php echo ($seoScore / 100) * 360; ?>deg, #e5e7eb <?php echo ($seoScore / 100) * 360; ?>deg 360deg);">
            <div class="score-inner">
                <span class="score-number"><?php echo (int)$seoScore; ?></span>
                <span class="score-label">SEO Score</span>
            </div>
        </div>
        <div class="score-details">
            <h3 style="color: <?php echo $healthColor; ?>;"><?php echo esc($healthStatus); ?></h3>
            <p>Site health: <strong><?php echo (int)$totalIssues; ?> issues</strong> found</p>
            <a href="?action=audit" class="btn btn-primary btn-sm">Run Full Audit</a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="seo-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📄</div>
            <div class="stat-content">
                <h4><?php echo (int)$stats['published_pages']; ?></h4>
                <p>Published Pages</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-content">
                <h4><?php echo (int)$stats['published_blog']; ?></h4>
                <p>Blog Posts</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🛍️</div>
            <div class="stat-content">
                <h4><?php echo (int)$stats['published_services']; ?></h4>
                <p>Services</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🖼️</div>
            <div class="stat-content">
                <h4><?php echo (int)$stats['published_portfolio']; ?></h4>
                <p>Portfolio Items</p>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
                <h4><?php echo (int)$stats['missing_titles']; ?></h4>
                <p>Missing SEO Titles</p>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
                <h4><?php echo (int)$stats['missing_descriptions']; ?></h4>
                <p>Missing Meta Descriptions</p>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">🖼️</div>
            <div class="stat-content">
                <h4><?php echo (int)$stats['missing_alt']; ?></h4>
                <p>Images Without ALT Text</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔗</div>
            <div class="stat-content">
                <h4><?php echo (int)$stats['active_redirects']; ?></h4>
                <p>Active Redirects</p>
            </div>
        </div>
    </div>

    <!-- Top Issues -->
    <div class="seo-issues-section">
        <h2>Top SEO Issues</h2>

        <?php if ($totals['missing_titles'] > 0): ?>
            <div class="issue-group">
                <h3>Missing SEO Titles (<?php echo (int)$totals['missing_titles']; ?>)</h3>
                <p>Add compelling SEO titles to these pages for better search rankings.</p>
                <a href="?action=meta#missing-titles" class="btn btn-secondary btn-sm">Fix Now</a>
            </div>
        <?php endif; ?>

        <?php if ($totals['missing_descriptions'] > 0): ?>
            <div class="issue-group">
                <h3>Missing Meta Descriptions (<?php echo (int)$totals['missing_descriptions']; ?>)</h3>
                <p>Meta descriptions improve CTR in search results. Add them to all pages.</p>
                <a href="?action=meta#missing-descriptions" class="btn btn-secondary btn-sm">Fix Now</a>
            </div>
        <?php endif; ?>

        <?php if ($totals['missing_alt'] > 0): ?>
            <div class="issue-group">
                <h3>Images Without ALT Text (<?php echo (int)$totals['missing_alt']; ?>)</h3>
                <p>ALT text improves accessibility and image SEO.</p>
                <a href="?action=meta#missing-alt" class="btn btn-secondary btn-sm">Fix Now</a>
            </div>
        <?php endif; ?>

        <?php if ($totals['duplicate_slugs'] > 0): ?>
            <div class="issue-group">
                <h3>Duplicate Slugs (<?php echo (int)$totals['duplicate_slugs']; ?>)</h3>
                <p>Duplicate URLs can confuse search engines. Ensure each page has a unique slug.</p>
                <a href="?action=audit" class="btn btn-secondary btn-sm">View Details</a>
            </div>
        <?php endif; ?>

        <?php if ($totals['orphan_pages'] > 0): ?>
            <div class="issue-group">
                <h3>Orphan Pages (<?php echo (int)$totals['orphan_pages']; ?>)</h3>
                <p>Pages with no internal links won't be crawled by search engines.</p>
                <a href="?action=audit" class="btn btn-secondary btn-sm">View Details</a>
            </div>
        <?php endif; ?>

        <?php if ($totalIssues === 0): ?>
            <div class="success-message">
                ✅ No major SEO issues found! Your site is well-optimized.
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Links -->
    <div class="seo-quick-links">
        <h2>Quick Actions</h2>
        <div class="quick-links-grid">
            <a href="/admin/settings.php?tab=seo" class="quick-link">
                <span class="icon">⚙️</span>
                <span class="label">SEO Settings</span>
            </a>
            <a href="/admin/navigation.php" class="quick-link">
                <span class="icon">🗺️</span>
                <span class="label">Navigation</span>
            </a>
            <a href="/admin/media.php" class="quick-link">
                <span class="icon">🖼️</span>
                <span class="label">Media Library</span>
            </a>
            <a href="/sitemap.xml" class="quick-link" target="_blank">
                <span class="icon">📋</span>
                <span class="label">View Sitemap</span>
            </a>
            <a href="/robots.txt" class="quick-link" target="_blank">
                <span class="icon">🤖</span>
                <span class="label">View Robots.txt</span>
            </a>
            <a href="?action=audit" class="quick-link">
                <span class="icon">🔍</span>
                <span class="label">Full Audit</span>
            </a>
        </div>
    </div>

</div>
