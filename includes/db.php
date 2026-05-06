<?php
/**
 * Database Connection
 * -------------------
 * PDO connection to the BodaCheck MySQL database.
 * All queries use prepared statements — no raw SQL injection possible.
 * Adjust credentials for your XAMPP setup.
 */
$DB_SOCKET = '/opt/lampp/var/mysql/mysql.sock';
$DB_NAME = 'bodacheck';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:unix_socket=$DB_SOCKET;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,// if some thing goes wrong it throws an error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,// fetches data in associative array format
            PDO::ATTR_EMULATE_PREPARES => false,// use real prepared statements instead of emulating them
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
