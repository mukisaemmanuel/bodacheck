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
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
