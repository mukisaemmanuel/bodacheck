<?php
/**
 * Admin Reports Page — Government Data Export
 * --------------------------------------------
 * Export data as CSV for reporting to KCCA, Uganda Police,
 * and Ministry of Works & Transport. Government-standard exports.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

// Handle CSV download
if (isset($_GET['download'])) {
    $download_type = $_GET['download'];
    header('Content-Type: text/csv; charset=utf-8');

    if ($download_type === 'riders') {
        header('Content-Disposition: attachment; filename=BodaCheck_Rider_Registry_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Full Name', 'Phone', 'National ID', 'Bike Plate', 'SACCO', 'District', 'QR Token', 'Safety Score', 'Status', 'Insured', 'Helmet', 'Licence', 'PSV Permit', 'Logbook', 'Payment Status', 'MoMo Phone', 'Registered Date']);

        $stmt = $pdo->query(
            'SELECT r.*, s.name AS sacco_name, s.district AS sacco_district
             FROM riders r JOIN saccos s ON r.sacco_id = s.id ORDER BY r.created_at DESC'
        );
        foreach ($stmt->fetchAll() as $r) {
            fputcsv($output, [
                $r['id'], $r['full_name'], $r['phone_number'], $r['national_id'],
                $r['bike_plate'], $r['sacco_name'], $r['sacco_district'], $r['qr_token'],
                $r['safety_score'], strtoupper($r['status']),
                $r['is_insured'] ? 'Yes' : 'No', $r['has_helmet'] ? 'Yes' : 'No',
                $r['has_licence'] ? 'Yes' : 'No', $r['has_psv_permit'] ? 'Yes' : 'No',
                $r['has_logbook'] ? 'Yes' : 'No', ucfirst($r['payment_status']),
                $r['momo_phone'], $r['created_at']
            ]);
        }
        fclose($output);
        exit;

    } elseif ($download_type === 'violations') {
        header('Content-Disposition: attachment; filename=BodaCheck_Enforcement_Log_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Rider Name', 'Bike Plate', 'Rider Score', 'Rider Status', 'Officer Name', 'Badge Number', 'Violation Type', 'Points Deducted', 'Location', 'Notes', 'Date']);

        $stmt = $pdo->query(
            'SELECT v.*, r.full_name AS rider_name, r.bike_plate, r.safety_score AS rider_score, r.status AS rider_status, u.name AS officer_name, u.badge_number
             FROM violations v JOIN riders r ON v.rider_id = r.id JOIN users u ON v.officer_id = u.id ORDER BY v.created_at DESC'
        );
        foreach ($stmt->fetchAll() as $v) {
            fputcsv($output, [
                $v['id'], $v['rider_name'], $v['bike_plate'], $v['rider_score'],
                strtoupper($v['rider_status']), $v['officer_name'], $v['badge_number'],
                $v['violation_type'], $v['points_deducted'], $v['location'],
                $v['notes'] ?? '', $v['created_at']
            ]);
        }
        fclose($output);
        exit;

    } elseif ($download_type === 'scans') {
        header('Content-Disposition: attachment; filename=BodaCheck_Scan_Log_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Rider Name', 'Bike Plate', 'Scan Type', 'IP Address', 'Date']);

        $stmt = $pdo->query(
            'SELECT sl.*, r.full_name AS rider_name, r.bike_plate
             FROM scan_logs sl JOIN riders r ON sl.rider_id = r.id ORDER BY sl.created_at DESC'
        );
        foreach ($stmt->fetchAll() as $s) {
            fputcsv($output, [$s['id'], $s['rider_name'], $s['bike_plate'], $s['scan_type'], $s['ip_address'], $s['created_at']]);
        }
        fclose($output);
        exit;

    } elseif ($download_type === 'payments') {
        header('Content-Disposition: attachment; filename=BodaCheck_Payment_Records_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Rider Name', 'Amount (UGX)', 'MoMo Phone', 'MoMo Reference', 'Payment Type', 'Status', 'Date']);

        $stmt = $pdo->query(
            'SELECT p.*, r.full_name AS rider_name
             FROM payments p JOIN riders r ON p.rider_id = r.id ORDER BY p.created_at DESC'
        );
        foreach ($stmt->fetchAll() as $p) {
            fputcsv($output, [
                $p['id'], $p['rider_name'], $p['amount'], $p['momo_phone'],
                $p['momo_reference'], $p['payment_type'], ucfirst($p['status']), $p['created_at']
            ]);
        }
        fclose($output);
        exit;
    }
}

// Summary stats for the reports page
$total_riders = $pdo->query('SELECT COUNT(*) FROM riders')->fetchColumn();
$total_violations = $pdo->query('SELECT COUNT(*) FROM violations')->fetchColumn();
$total_scans = $pdo->query('SELECT COUNT(*) FROM scan_logs')->fetchColumn();
$total_payments = $pdo->query('SELECT COUNT(*) FROM payments')->fetchColumn();
?>

<div class="admin-page-title">Reports & Data Export</div>
<div class="admin-page-subtitle">Export system data for KCCA, Uganda Police, and Ministry of Works & Transport reporting</div>

<!-- Export options -->
<div class="gov-kpi-row">
    <div class="gov-kpi-card">
        <div class="gov-kpi-icon orange">R</div>
        <div class="gov-kpi-content">
            <div class="gov-kpi-value"><?php echo number_format($total_riders); ?></div>
            <div class="gov-kpi-label">Rider Registry</div>
            <div class="gov-kpi-sub">Full rider profiles with compliance data</div>
            <div style="margin-top:var(--sp-3);">
                <a href="<?php echo $base; ?>/admin/reports.php?download=riders" class="gov-btn gov-btn-primary gov-btn-sm">Download CSV</a>
            </div>
        </div>
    </div>
    <div class="gov-kpi-card">
        <div class="gov-kpi-icon red">V</div>
        <div class="gov-kpi-content">
            <div class="gov-kpi-value"><?php echo number_format($total_violations); ?></div>
            <div class="gov-kpi-label">Enforcement Log</div>
            <div class="gov-kpi-sub">All violations with officer and location data</div>
            <div style="margin-top:var(--sp-3);">
                <a href="<?php echo $base; ?>/admin/reports.php?download=violations" class="gov-btn gov-btn-primary gov-btn-sm">Download CSV</a>
            </div>
        </div>
    </div>
    <div class="gov-kpi-card">
        <div class="gov-kpi-icon blue">S</div>
        <div class="gov-kpi-content">
            <div class="gov-kpi-value"><?php echo number_format($total_scans); ?></div>
            <div class="gov-kpi-label">Scan Activity Log</div>
            <div class="gov-kpi-sub">All QR scans with type and timestamp</div>
            <div style="margin-top:var(--sp-3);">
                <a href="<?php echo $base; ?>/admin/reports.php?download=scans" class="gov-btn gov-btn-sm">Download CSV</a>
            </div>
        </div>
    </div>
    <div class="gov-kpi-card">
        <div class="gov-kpi-icon green">P</div>
        <div class="gov-kpi-content">
            <div class="gov-kpi-value"><?php echo number_format($total_payments); ?></div>
            <div class="gov-kpi-label">Payment Records</div>
            <div class="gov-kpi-sub">MTN MoMo transactions and status</div>
            <div style="margin-top:var(--sp-3);">
                <a href="<?php echo $base; ?>/admin/reports.php?download=payments" class="gov-btn gov-btn-sm">Download CSV</a>
            </div>
        </div>
    </div>
</div>

<!-- Data policy notice -->
<div class="gov-card" style="border-left:3px solid #1e3a5f;">
    <div class="gov-card-title">Data Policy & Privacy Notice</div>
    <p style="font-size:0.85rem; color:#475569; line-height:1.6;">
        BodaCheck collects minimal data: phone number, national ID, bike registration, and crash log.
        No GPS tracking. No real-time location. This data is stored in accordance with the Uganda Data Protection
        and Privacy Act 2019. Data exported from this portal is classified as government records and must be
        handled according to government data handling policies. The data stored here is nothing the government
        does not already require on paper — BodaCheck is digitising what exists.
    </p>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
