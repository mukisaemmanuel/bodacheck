<?php
require 'includes/db.php';
require 'includes/session.php';
require 'includes/functions.php';
require 'libs/qrlib.php';

// Mock data
$riders = [
    ['Jane Smith', '0751234567', 'CM987654321', 'UBB 456B', 1],
    ['John Doe', '0771234567', 'CM123456789', 'UBA 123A', 2],
    ['Peter K.', '0700000001', 'CM111111111', 'UBZ 999Z', 3],
    ['Mary S.', '0788888888', 'CM222222222', 'UBC 123C', 1],
    ['David N.', '0755555555', 'CM333333333', 'UBD 456D', 2],
    ['Moses T.', '0777777777', 'CM444444444', 'UBE 789E', 3],
];

$officer_id = 2; // From users table
$password = password_hash('password123', PASSWORD_DEFAULT);

echo "Restoring data...\n";

foreach ($riders as $r) {
    $qr_token = generateQrToken($pdo);
    
    $stmt = $pdo->prepare(
        'INSERT INTO riders (full_name, phone_number, national_id, bike_plate, sacco_id, qr_token, password, momo_phone, payment_status, is_insured, has_helmet, has_licence, has_psv_permit, has_logbook, safety_score, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, "paid", 1, 1, 1, 1, 1, 100, "green")'
    );
    $stmt->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $qr_token, $password, $r[1]]);
    $rider_id = $pdo->lastInsertId();

    // Create payment
    $stmt = $pdo->prepare('INSERT INTO payments (rider_id, amount, momo_phone, payment_type, status) VALUES (?, 10000, ?, "registration", "successful")');
    $stmt->execute([$rider_id, $r[1]]);

    // Generate QR
    $qr_filepath = __DIR__ . '/assets/qr_codes/' . $qr_token . '.png';
    $scan_url = getQrScanUrl($qr_token);
    generateQRCode($scan_url, $qr_filepath);

    // Add some random scan logs
    for ($i = 0; $i < rand(1, 3); $i++) {
        logScan($pdo, $rider_id, 'officer', $officer_id);
    }
}

// Add a violation for Peter K.
$stmt = $pdo->prepare('SELECT id FROM riders WHERE full_name = "Peter K."');
$stmt->execute();
$peter_id = $stmt->fetchColumn();

if ($peter_id) {
    $stmt = $pdo->prepare('INSERT INTO violations (rider_id, officer_id, violation_type, location, points_deducted) VALUES (?, ?, "No Helmet", "Kampala Road", 10)');
    $stmt->execute([$peter_id, $officer_id]);
    updateRiderScore($pdo, $peter_id, 10);
    
    $stmt = $pdo->prepare('INSERT INTO violations (rider_id, officer_id, violation_type, location, points_deducted) VALUES (?, ?, "Reckless Driving", "Jinja Road", 25)');
    $stmt->execute([$peter_id, $officer_id]);
    updateRiderScore($pdo, $peter_id, 25);
}

// Add a violation for Mary S.
$stmt = $pdo->prepare('SELECT id FROM riders WHERE full_name = "Mary S."');
$stmt->execute();
$mary_id = $stmt->fetchColumn();

if ($mary_id) {
    $stmt = $pdo->prepare('INSERT INTO violations (rider_id, officer_id, violation_type, location, points_deducted) VALUES (?, ?, "Overloading", "Acacia Ave", 15)');
    $stmt->execute([$mary_id, $officer_id]);
    updateRiderScore($pdo, $mary_id, 15);
}

echo "Data restored successfully.\n";
