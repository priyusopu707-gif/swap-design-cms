<?php
/**
 * Swap Design - Admin Dashboard
 *
 * Overview page with stat cards, recent activity,
 * and quick-action shortcuts.
 *
 * @package SwapDesign
 */

require_once __DIR__ . '/includes/init.php';
Auth::require();

/* ---- Page setup ---- */
$pageTitle      = 'Dashboard';
$currentSection = 'dashboard';

/* ---- Fetch stats ---- */
$db = Database::getInstance();

$stats = [
    'totalPages'     => $db->count('pages'),
    'publishedPages' => $db->count('pages', "status = 'published'"),
    'portfolioItems' => $db->count('portfolio_items'),
    'unreadMessages' => $db->count('contact_messages', 'is_read = 0'),
];

$totalMessages = $db->count('contact_messages');

$recentMessages = $totalMessages > 0
    ? $db->fetchAll("SELECT id, name, email, subject, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5")
    : [];

$recentPages = $db->fetchAll("SELECT id, title, status, updated_at FROM pages ORDER BY updated_at DESC LIMIT 3");

/* ---- Render ---- */
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1 class="admin-page-header__title">Dashboard</h1>
    <p class="admin-page-header__subtitle">Welcome back, <?php echo esc(Auth::user()['name'] ?? 'Admin'); ?>.</p>
</div>

<!-- Stat Cards -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <div class="admin-stat-card__icon admin-stat-card__icon--pages" aria-hidden="true"></div>
        <div class="admin-stat-card__body">
            <span class="admin-stat-card__value"><?php echo (int) $stats['totalPages']; ?></span>
            <span class="admin-stat-card__label">Total Pages</span>
        </div>
    </div>

    <div class="admin-stat-card">
        <div class="admin-stat-card__icon admin-stat-card__icon--published" aria-hidden="true"></div>
        <div class="admin-stat-card__body">
            <span class="admin-stat-card__value"><?php echo (int) $stats['publishedPages']; ?></span>
            <span class="admin-stat-card__label">Published</span>
        </div>
    </div>

    <div class="admin-stat-card">
        <div class="admin-stat-card__icon admin-stat-card__icon--portfolio" aria-hidden="true"></div>
        <div class="admin-stat-card__body">
            <span class="admin-stat-card__value"><?php echo (int) $stats['portfolioItems']; ?></span>
            <span class="admin-stat-card__label">Portfolio Items</span>
        </div>
    </div>

    <div class="admin-stat-card<?php echo $stats['unreadMessages'] > 0 ? ' admin-stat-card--accent' : ''; ?>">
        <div class="admin-stat-card__icon admin-stat-card__icon--messages" aria-hidden="true"></div>
        <div class="admin-stat-card__body">
            <span class="admin-stat-card__value">
                <?php echo (int) $stats['unreadMessages']; ?>
            </span>
            <span class="admin-stat-card__label">Unread Messages</span>
        </div>
        <?php if ($stats['unreadMessages'] > 0): ?>
            <span class="admin-stat-card__badge">New</span>
        <?php endif; ?>
    </div>
</div>

<!-- Two-Column Grid -->
<div class="admin-dashboard-grid">
    <!-- Recent Messages -->
    <div class="admin-card">
        <div class="admin-card__header">
            <h2 class="admin-card__title">Recent Messages</h2>
            <?php if ($totalMessages > 0): ?>
                <span class="admin-card__count"><?php echo (int) $totalMessages; ?> total</span>
            <?php endif; ?>
        </div>
        <div class="admin-card__body admin-card__body--no-padding">
            <?php if (empty($recentMessages)): ?>
                <div class="admin-empty-state">
                    <p>No messages received yet.</p>
                </div>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Subject</th>
                        <th class="admin-table__hide-mobile">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentMessages as $msg): ?>
                    <tr>
                        <td>
                            <span class="admin-table__name"><?php echo esc($msg['name']); ?></span>
                            <span class="admin-table__email"><?php echo esc($msg['email']); ?></span>
                        </td>
                        <td><?php echo esc($msg['subject']); ?></td>
                        <td class="admin-table__hide-mobile admin-table__muted">
                            <?php echo esc(date('M j, Y', strtotime($msg['created_at']))); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions + Recent Pages -->
    <div>
        <!-- Quick Actions -->
        <div class="admin-card u-mb-md">
            <div class="admin-card__header">
                <h2 class="admin-card__title">Quick Actions</h2>
            </div>
            <div class="admin-card__body">
                <div class="admin-quick-actions">
                    <a href="/admin/pages.php?action=create" class="admin-quick-action">
                        <span class="admin-quick-action__icon admin-quick-action__icon--add" aria-hidden="true">+</span>
                        <span>Create Page</span>
                    </a>
                    <a href="/admin/media.php" class="admin-quick-action">
                        <span class="admin-quick-action__icon admin-quick-action__icon--upload" aria-hidden="true">&uarr;</span>
                        <span>Upload Media</span>
                    </a>
                    <a href="/" target="_blank" class="admin-quick-action">
                        <span class="admin-quick-action__icon admin-quick-action__icon--view" aria-hidden="true">&rarr;</span>
                        <span>View Site</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Pages -->
        <div class="admin-card">
            <div class="admin-card__header">
                <h2 class="admin-card__title">Recent Pages</h2>
            </div>
            <div class="admin-card__body admin-card__body--no-padding">
                <?php if (empty($recentPages)): ?>
                    <div class="admin-empty-state">
                        <p>No pages created yet.</p>
                        <a href="/admin/pages.php?action=create" class="admin-btn admin-btn--primary admin-btn--sm u-mt-md">Create First Page</a>
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPages as $page): ?>
                            <tr>
                                <td><?php echo esc($page['title']); ?></td>
                                <td>
                                    <span class="admin-badge admin-badge--<?php echo $page['status'] === 'published' ? 'success' : 'default'; ?>">
                                        <?php echo esc(ucfirst($page['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';
