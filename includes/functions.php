<?php
/**
 * Common Functions
 * ----------------
 * Shared logic used across the entire BodaCheck application.
 * Scoring, status calculation, QR token generation, scan logging,
 * violation point mapping, sanitization, and HTML helpers.
 */

/**
 * Calculate rider status from safety score.
 * Green: 70-100, Amber: 40-69, Red: 0-39.
 */
function calculateStatus(int $score): string {
    if ($score >= 70) return 'green';
    if ($score >= 40) return 'amber';
    return 'red';
}

/**
 * Get points to deduct for each violation type.
 * No Helmet=10, No Licence=20, Reckless Driving=25, Overloading=15, Other=10.
 */
function getPointsForViolation(string $type): int {
    $map = [
        'No Helmet'        => 10,
        'No Licence'       => 20,
        'Reckless Driving'  => 25,
        'Overloading'      => 15,
        'Other'            => 10,
    ];
    return $map[$type] ?? 10;
}

/**
 * Generate a unique QR token for a new rider.
 * Format: RDR- followed by 32 random hex characters.
 * Loops until a unique token is found.
 */
function generateQrToken(PDO $pdo): string {
    do {
        $token = 'RDR-' . bin2hex(random_bytes(16));
        $stmt = $pdo->prepare('SELECT id FROM riders WHERE qr_token = ?');
        $stmt->execute([$token]);
    } while ($stmt->fetch());
    return $token;
}

/**
 * Log a QR scan in the scan_logs table.
 * Creates a digital paper trail — every scan is recorded.
 */
function logScan(PDO $pdo, int $rider_id, string $scan_type = 'public', ?int $scanner_user_id = null): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $pdo->prepare(
        'INSERT INTO scan_logs (rider_id, scanner_user_id, scan_type, ip_address) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$rider_id, $scanner_user_id, $scan_type, $ip]);
}

/**
 * Update a rider's safety score after a violation.
 * Deducts points, clamps to zero minimum, recalculates status.
 */
function updateRiderScore(PDO $pdo, int $rider_id, int $points): void {
    $stmt = $pdo->prepare('SELECT safety_score FROM riders WHERE id = ?');
    $stmt->execute([$rider_id]);
    $rider = $stmt->fetch();
    if ($rider) {
        $new_score = max(0, $rider['safety_score'] - $points);
        $new_status = calculateStatus($new_score);
        $stmt = $pdo->prepare('UPDATE riders SET safety_score = ?, status = ? WHERE id = ?');
        $stmt->execute([$new_score, $new_status, $rider_id]);
    }
}

/** Get the list of violation types for dropdown menus */
function getViolationTypes(): array {
    return ['No Helmet', 'No Licence', 'Reckless Driving', 'Overloading', 'Other'];
}

/** Sanitize user input to prevent XSS */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/** Generate an HTML status badge with the correct colour */
function statusBadge(string $status): string {
    $labels = ['green' => 'GREEN', 'amber' => 'AMBER', 'red' => 'RED'];
    $label = $labels[$status] ?? strtoupper($status);
    return '<span class="status-badge status-' . $status . '">' . $label . '</span>';
}

/** Get the annual registration fee in UGX */
function getRegistrationFee(): int {
    return 10000; // UGX 10,000 per year
}

/** Format UGX currency */
function formatUGX(int $amount): string {
    return 'UGX ' . number_format($amount);
}
