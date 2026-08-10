<?php
/**
 * Meta Management Tab - View all content meta tags
 */

$contentTypes = ['page', 'blog_post', 'service', 'portfolio'];
$selectedType = $_GET['type'] ?? 'page';
$page = (int)($_GET['page'] ?? 1);
$perPage = 50;

$auditor = new SEOAuditor();
$issues = $auditor->runAudit();

$db = Database::getInstance();
$offset = ($page - 1) * $perPage;

/* Get content for selected type */
$typeConfig = SEOAuditor::CONTENT_SOURCES[$selectedType] ?? null;
if (!$typeConfig) $selectedType = 'page';

$table = $typeConfig['table'];
$titleCol = $typeConfig['title_col'];
$seoTitleCol = $typeConfig['seo_title_col'];
$metaCol = $typeConfig['meta_col'];

$items = $db->fetchAll(
    "SELECT id, {$titleCol} as title, {$seoTitleCol} as seo_title, {$metaCol} as meta_desc, status
     FROM {$table} WHERE status = 'published'
     LIMIT ? OFFSET ?",
    [$perPage, $offset]
);

$total = $db->count($table, "status = 'published'");
$totalPages = ceil($total / $perPage);
?>

<div class="meta-management">
    <div class="meta-header">
        <h2>Meta Management</h2>
        <div class="meta-filters">
            <select id="contentTypeFilter" onchange="window.location.href='?action=meta&type=' + this.value">
                <option value="page" <?php echo $selectedType === 'page' ? 'selected' : ''; ?>>Pages</option>
                <option value="blog_post" <?php echo $selectedType === 'blog_post' ? 'selected' : ''; ?>>Blog Posts</option>
                <option value="service" <?php echo $selectedType === 'service' ? 'selected' : ''; ?>>Services</option>
                <option value="portfolio" <?php echo $selectedType === 'portfolio' ? 'selected' : ''; ?>>Portfolio</option>
            </select>
        </div>
    </div>

    <!-- Meta Tags Table -->
    <table class="admin-table meta-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>SEO Title</th>
                <th>Meta Description</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo esc(substr($item['title'], 0, 40)); ?></strong>
                        <?php if (strlen($item['title']) > 40) echo '...'; ?>
                    </td>
                    <td>
                        <?php if ($item['seo_title']): ?>
                            <span class="tag-success"><?php echo esc(substr($item['seo_title'], 0, 35)); ?></span>
                        <?php else: ?>
                            <span class="tag-warning">Missing</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($item['meta_desc']): ?>
                            <span class="tag-success"><?php echo esc(substr($item['meta_desc'], 0, 35)); ?></span>
                            <small><?php echo strlen($item['meta_desc']); ?> chars</small>
                        <?php else: ?>
                            <span class="tag-warning">Missing</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                    <td>
                        <a href="/admin/<?php echo $selectedType === 'blog_post' ? 'blog' : ($selectedType === 'portfolio' ? 'portfolio' : ($selectedType === 'service' ? 'services' : 'pages')); ?>.php?edit=<?php echo (int)$item['id']; ?>" class="btn-link">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="admin-pagination">
            <?php if ($page > 1): ?>
                <a href="?action=meta&type=<?php echo $selectedType; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary">← Previous</a>
            <?php endif; ?>
            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?action=meta&type=<?php echo $selectedType; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Missing Meta Issues -->
    <div class="meta-issues">
        <h3>SEO Issues Summary</h3>
        <div class="issue-stats">
            <div class="issue-stat">
                <h4><?php echo $issues['totals']['missing_titles']; ?></h4>
                <p>Missing SEO Titles</p>
            </div>
            <div class="issue-stat">
                <h4><?php echo $issues['totals']['missing_descriptions']; ?></h4>
                <p>Missing Meta Descriptions</p>
            </div>
            <div class="issue-stat">
                <h4><?php echo $issues['totals']['missing_alt']; ?></h4>
                <p>Images Without ALT Text</p>
            </div>
        </div>
    </div>
</div>
