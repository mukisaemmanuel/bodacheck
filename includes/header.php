<?php
/**
 * Public Header
 * -------------
 * Top navigation bar for public-facing pages.
 * Shows BodaCheck logo and contextual links based on login state.
 */
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';
$base = baseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BodaCheck — Rider Safety ID</title>
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>
<nav class="top-nav">
    <div class="nav-inner">
        <a href="<?php echo $base; ?>/index.php" class="nav-logo">
            <span class="logo-icon">BC</span> BodaCheck
        </a>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <?php if (isRider()): ?>
                    <a href="<?php echo $base; ?>/rider/dashboard.php">Dashboard</a>
                <?php elseif (isOfficer()): ?>
                    <a href="<?php echo $base; ?>/officer/scan.php">Scan</a>
                <?php elseif (isAdmin()): ?>
                    <a href="<?php echo $base; ?>/admin/dashboard.php">Admin</a>
                <?php endif; ?>
                <a href="<?php echo $base; ?>/logout.php" class="btn btn-sm btn-secondary">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base; ?>/login.php">Login</a>
                <a href="<?php echo $base; ?>/register.php" class="btn btn-sm btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="page-content">
