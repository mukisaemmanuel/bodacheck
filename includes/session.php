<?php
/**
 * Session Helper
 * --------------
 * Starts the session and provides role-checking functions.
 * Every protected page includes this at the top.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Check if anyone is logged in */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/** Check if the logged-in user is a rider */
function isRider(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'rider';
}

/** Check if the logged-in user is a traffic officer */
function isOfficer(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'officer';
}

/** Check if the logged-in user is an admin */
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/** Require login — redirect to login page if not logged in */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl() . '/login.php');
        exit;
    }
}

/** Require a specific role — redirect if wrong role */
function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: ' . baseUrl() . '/login.php');
        exit;
    }
}

/** Base URL of the application — adjust if installed elsewhere */
function baseUrl(): string {
    return '/bodacheck';
}
