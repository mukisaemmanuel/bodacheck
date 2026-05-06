<?php
/**
 * Admin Header — Government Professional Layout
 * -----------------------------------------------
 * Sidebar with Republic of Uganda government branding.
 * Only accessible by users with the 'admin' role.
 * Includes section labels and icon-based navigation.
 */
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';
requireRole('admin');
$base = baseUrl();
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Get admin user info for the topbar
$stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$admin_user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BodaCheck — Government Admin Portal</title>
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <!-- Government sidebar -->
    <aside class="admin-sidebar">
        <!-- Government crest / branding -->
        <div class="sidebar-crest">
            <div class="sidebar-crest-emblem">UG</div>
            <div class="sidebar-crest-title">BodaCheck</div>
            <div class="sidebar-crest-subtitle">Republic of Uganda</div>
        </div>

        <nav class="sidebar-nav">
            <!-- Overview section -->
            <div class="sidebar-section-label">Overview</div>
            <a href="<?php echo $base; ?>/admin/dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon">&#9632;</span> Dashboard
            </a>
            <a href="<?php echo $base; ?>/admin/analytics.php" class="sidebar-link <?php echo $current_page === 'analytics' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon">&#9670;</span> Analytics
            </a>

            <!-- Registry section -->
            <div class="sidebar-section-label">Registry</div>
            <a href="<?php echo $base; ?>/admin/riders.php" class="sidebar-link <?php echo $current_page === 'riders' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon">&#9679;</span> Riders
            </a>
            <a href="<?php echo $base; ?>/admin/violations.php" class="sidebar-link <?php echo $current_page === 'violations' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon">&#9650;</span> Violations
            </a>
            <a href="<?php echo $base; ?>/admin/officers.php" class="sidebar-link <?php echo $current_page === 'officers' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon">&#9733;</span> Officers
            </a>

            <!-- Operations section -->
            <div class="sidebar-section-label">Operations</div>
            <a href="<?php echo $base; ?>/admin/reports.php" class="sidebar-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon">&#9998;</span> Reports & Export
            </a>

            <!-- Logout -->
            <a href="<?php echo $base; ?>/logout.php" class="sidebar-link sidebar-logout">
                <span class="sidebar-link-icon">&#10006;</span> Logout
            </a>
        </nav>
    </aside>

    <!-- Main content area -->
    <div class="admin-main">
        <!-- Government top bar -->
        <div class="admin-topbar">
            <div class="admin-topbar-title">
                BodaCheck Admin Portal
            </div>
            <div class="admin-topbar-meta">
                <span class="admin-topbar-badge">&#9733; Administrator</span>
                <span><?php echo sanitize($admin_user['name'] ?? 'Admin'); ?></span>
                <span style="color:#94a3b8;"><?php echo date('d M Y'); ?></span>
            </div>
        </div>

        <!-- Page content -->
        <div class="admin-content">
        </script>

        <!-- Page content -->
        <div class="admin-content">
