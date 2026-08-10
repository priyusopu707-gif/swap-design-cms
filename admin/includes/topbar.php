<?php
/**
 * Swap Design - Admin Top Navigation Bar
 *
 * Top bar with mobile sidebar toggle, breadcrumb, and user dropdown.
 *
 * Used by: admin/includes/header.php
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

$currentUser    = Auth::user();
$pageTitle      = $pageTitle  ?? 'Dashboard';
$breadcrumbTitle = $breadcrumbTitle ?? $pageTitle;
?>

<header class="admin-topbar" role="banner">
    <div class="admin-topbar__left">
        <!-- Mobile Sidebar Toggle -->
        <button class="admin-topbar__toggle"
                id="sidebar-toggle"
                aria-label="Toggle sidebar"
                aria-expanded="false"
                aria-controls="admin-sidebar">
            <span class="admin-topbar__toggle-bar"></span>
            <span class="admin-topbar__toggle-bar"></span>
            <span class="admin-topbar__toggle-bar"></span>
        </button>

        <!-- Breadcrumb -->
        <nav class="admin-topbar__breadcrumb" aria-label="Admin breadcrumb">
            <a href="/admin/index.php" class="admin-topbar__breadcrumb-link">Dashboard</a>
            <?php if ($currentSection !== 'dashboard'): ?>
                <span class="admin-topbar__breadcrumb-sep" aria-hidden="true">/</span>
                <span class="admin-topbar__breadcrumb-current"><?php echo esc($breadcrumbTitle); ?></span>
            <?php endif; ?>
        </nav>
    </div>

    <div class="admin-topbar__right">
        <!-- User Dropdown -->
        <div class="admin-topbar__user" id="user-dropdown">
            <button class="admin-topbar__user-toggle"
                    aria-expanded="false"
                    aria-haspopup="true"
                    aria-controls="user-menu">
                <span class="admin-topbar__user-avatar">
                    <?php echo esc(strtoupper(substr($currentUser['name'] ?? 'A', 0, 1))); ?>
                </span>
                <span class="admin-topbar__user-name">
                    <?php echo esc($currentUser['name'] ?? 'Admin'); ?>
                </span>
                <svg class="admin-topbar__user-chevron" width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true">
                    <path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <ul class="admin-topbar__user-menu" id="user-menu" role="menu" aria-label="User menu">
                <li role="none">
                    <span class="admin-topbar__user-menu-email" role="menuitem">
                        <?php echo esc($currentUser['email'] ?? ''); ?>
                    </span>
                </li>
                <li role="none"><hr class="admin-topbar__divider"></li>
                <li role="none">
                    <a href="/admin/profile.php" class="admin-topbar__user-menu-link" role="menuitem">Profile</a>
                </li>
                <li role="none">
                    <a href="/admin/logout.php" class="admin-topbar__user-menu-link admin-topbar__user-menu-link--danger" role="menuitem">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</header>
