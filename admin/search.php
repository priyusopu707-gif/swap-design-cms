<?php
/**
 * Swap Design - Search Dashboard (Admin)
 *
 * Search analytics: overview stats, popular keywords, zero-result
 * keywords, most-viewed results, recent searches, index rebuild,
 * logging toggle, log clearing, and CSV export.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

$pageTitle      = 'Search Dashboard';
$currentSection = 'search';

$searchManager = new SearchManager();
$stats         = $searchManager->getStats();
$popular       = $searchManager->getPopularSearches(10);
$zeroResults   = $searchManager->getZeroResultKeywords(10);
$mostViewed    = $searchManager->getMostViewedResults(10);
$recent        = $searchManager->getRecentLogs(20);

require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Search Dashboard</h1>
    <p class="admin-page-header__subtitle">Analytics and maintenance for the site search.</p>
</div>

<!-- Stat Cards -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <div class="admin-stat-card__icon admin-stat-card__icon--messages" aria-hidden="true"></div>
        <div class="admin-stat-card__body">
            <span class="admin-stat-card__value"><?php echo (int)$stats['total_searches']; ?></span>
            <span class="admin-stat-card__label">Total Searches</span>
        </div>
    </div>

    <div class="admin-stat-card">
        <div class="admin-stat-card__icon admin-stat-card__icon--pages" aria-hidden="true"></div>
        <div class="admin-stat-card__body">
            <span class="admin-stat-card__value"><?php echo (int)$stats['unique_queries']; ?></span>
            <span class="admin-stat-card__label">Unique Queries</span>
        </div>
    </div>

    <div class="admin-stat-card<?php echo $stats['zero_result_searches'] > 0 ? ' admin-stat-card--accent' : ''; ?>">
        <div class="admin-stat-card__icon admin-stat-card__icon--portfolio" aria-hidden="true"></div>
        <div class="admin-stat-card__body">
            <span class="admin-stat-card__value"><?php echo (int)$stats['zero_result_searches']; ?></span>
            <span class="admin-stat-card__label">Zero-Result Searches</span>
        </div>
    </div>

    <div class="admin-stat-card">
        <div class="admin-stat-card__icon admin-stat-card__icon--published" aria-hidden="true"></div>
        <div class="admin-stat-card__body">
            <span class="admin-stat-card__value"><?php echo (int)$stats['indexed_items']; ?></span>
            <span class="admin-stat-card__label">Indexed Items</span>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="admin-card u-mb-md">
    <div class="admin-card__header">
        <h2 class="admin-card__title">Maintenance</h2>
    </div>
    <div class="admin-card__body">
        <div class="admin-search-actions">
            <button type="button" class="admin-btn admin-btn--primary admin-btn--sm" data-search-action="rebuild_index">
                Rebuild Search Index
            </button>
            <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-search-action="clear_logs">
                Clear Search Logs
            </button>
            <button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-search-action="export_csv">
                Export Analytics (CSV)
            </button>
            <button type="button"
                    class="admin-btn admin-btn--sm<?php echo $stats['logging_enabled'] ? ' admin-btn--danger' : ' admin-btn--primary'; ?>"
                    data-search-action="toggle_logging"
                    data-enabled="<?php echo $stats['logging_enabled'] ? '1' : '0'; ?>">
                <?php echo $stats['logging_enabled'] ? 'Disable' : 'Enable'; ?> Search Logging
            </button>
        </div>
        <p class="admin-search-actions__hint" id="search-action-feedback" role="status" aria-live="polite"></p>
    </div>
</div>

<!-- Analytics Tables -->
<div class="admin-dashboard-grid">
    <div class="admin-card">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Popular Keywords</h2>
        </div>
        <div class="admin-card__body admin-card__body--no-padding">
            <?php if (empty($popular)): ?>
                <div class="admin-empty-state"><p>No search data yet.</p></div>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Searches</th>
                        <th class="admin-table__hide-mobile">Avg Results</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popular as $row): ?>
                    <tr>
                        <td><span class="admin-table__name"><?php echo esc($row['query']); ?></span></td>
                        <td><?php echo (int)$row['count']; ?></td>
                        <td class="admin-table__hide-mobile admin-table__muted">
                            <?php echo $row['count'] > 0 ? number_format((int)$row['total_results'] / (int)$row['count'], 1) : 0; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Zero-Result Keywords</h2>
        </div>
        <div class="admin-card__body admin-card__body--no-padding">
            <?php if (empty($zeroResults)): ?>
                <div class="admin-empty-state"><p>No zero-result searches. Every query matched content.</p></div>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($zeroResults as $row): ?>
                    <tr>
                        <td><span class="admin-table__name"><?php echo esc($row['query']); ?></span></td>
                        <td><?php echo (int)$row['count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Most-Viewed Results</h2>
        </div>
        <div class="admin-card__body admin-card__body--no-padding">
            <?php if (empty($mostViewed)): ?>
                <div class="admin-empty-state"><p>No result clicks yet.</p></div>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Result</th>
                        <th>Type</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mostViewed as $row): ?>
                    <tr>
                        <td>
                            <a class="admin-table__name" href="<?php echo esc($row['url']); ?>" target="_blank" rel="noopener">
                                <?php echo esc($row['title']); ?>
                            </a>
                        </td>
                        <td><?php echo esc(SearchManager::TYPE_LABELS[$row['content_type']] ?? $row['content_type']); ?></td>
                        <td><?php echo (int)$row['clicks']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Recent Searches</h2>
        </div>
        <div class="admin-card__body admin-card__body--no-padding">
            <?php if (empty($recent)): ?>
                <div class="admin-empty-state"><p>No recent searches.</p></div>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Query</th>
                        <th>Results</th>
                        <th class="admin-table__hide-mobile">When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $row): ?>
                    <tr>
                        <td>
                            <span class="admin-table__name"><?php echo esc($row['query']); ?></span>
                            <?php if ((int)$row['is_zero_result'] === 1): ?>
                                <span class="admin-badge admin-badge--warning">no results</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$row['result_count']; ?></td>
                        <td class="admin-table__hide-mobile admin-table__muted">
                            <?php echo esc(date('M j, Y H:i', strtotime($row['created_at']))); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
<script src="/admin/assets/js/search-dashboard.js" defer></script>
