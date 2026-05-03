<?php
/**
 * Admin Dashboard — Government Professional
 * ------------------------------------------
 * Key performance indicators for the BodaCheck system.
 * Designed for KCCA and Uganda Police oversight.
 * Shows rider registration, compliance, enforcement, and revenue metrics.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

// ---- Core Metrics ----
$total_riders = $pdo->query('SELECT COUNT(*) FROM riders')->fetchColumn();
$total_violations = $pdo->query('SELECT COUNT(*) FROM violations')->fetchColumn();
$total_scans = $pdo->query('SELECT COUNT(*) FROM scan_logs')->fetchColumn();
$violations_today = $pdo->query("SELECT COUNT(*) FROM violations WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$violations_this_month = $pdo->query("SELECT COUNT(*) FROM violations WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$scans_today = $pdo->query("SELECT COUNT(*) FROM scan_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$new_riders_today = $pdo->query("SELECT COUNT(*) FROM riders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$new_riders_this_month = $pdo->query("SELECT COUNT(*) FROM riders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

// ---- Status Breakdown ----
$stmt = $pdo->query("SELECT status, COUNT(*) AS total FROM riders GROUP BY status");
$status_counts = ['green' => 0, 'amber' => 0, 'red' => 0];
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['total'];
}

// ---- Compliance Rates ----
$riders_with_helmet = $pdo->query('SELECT COUNT(*) FROM riders WHERE has_helmet = 1')->fetchColumn();
$riders_with_licence = $pdo->query('SELECT COUNT(*) FROM riders WHERE has_licence = 1')->fetchColumn();
$riders_with_psv = $pdo->query('SELECT COUNT(*) FROM riders WHERE has_psv_permit = 1')->fetchColumn();
$riders_insured = $pdo->query('SELECT COUNT(*) FROM riders WHERE is_insured = 1')->fetchColumn();
$helmet_pct = $total_riders > 0 ? round(($riders_with_helmet / $total_riders) * 100) : 0;
$licence_pct = $total_riders > 0 ? round(($riders_with_licence / $total_riders) * 100) : 0;
$psv_pct = $total_riders > 0 ? round(($riders_with_psv / $total_riders) * 100) : 0;
$insured_pct = $total_riders > 0 ? round(($riders_insured / $total_riders) * 100) : 0;

// ---- Payment / Revenue ----
$paid_riders = $pdo->query("SELECT COUNT(*) FROM riders WHERE payment_status = 'paid'")->fetchColumn();
$payment_rate = $total_riders > 0 ? round(($paid_riders / $total_riders) * 100) : 0;
$total_revenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'successful'")->fetchColumn();
$pending_revenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'pending'")->fetchColumn();

// ---- Average Score ----
$avg_score = round($pdo->query('SELECT AVG(safety_score) FROM riders')->fetchColumn() ?? 0);

// ---- Active Officers ----
$active_officers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'officer' AND is_active = 1")->fetchColumn();

// ---- Recent Violations ----
$stmt = $pdo->query(
    'SELECT v.*, r.full_name AS rider_name, r.bike_plate, u.name AS officer_name, u.badge_number
     FROM violations v
     JOIN riders r ON v.rider_id = r.id
     JOIN users u ON v.officer_id = u.id
     ORDER BY v.created_at DESC
     LIMIT 8'
);
$recent = $stmt->fetchAll();

// ---- Recent Scans ----
$stmt = $pdo->query(
    'SELECT sl.*, r.full_name AS rider_name
     FROM scan_logs sl
     JOIN riders r ON sl.rider_id = r.id
     ORDER BY sl.created_at DESC
     LIMIT 5'
);
$recent_scans = $stmt->fetchAll();

// Fetch logged-in admin details
$stmt_admin = $pdo->prepare('SELECT name, photo FROM users WHERE id = ?');
$stmt_admin->execute([$_SESSION['user_id']]);
$logged_in_admin = $stmt_admin->fetch();
?>

<div class="dashboard-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:var(--sp-5);">
    <div>
        <div class="admin-page-title" style="margin-bottom:0;">National Dashboard</div>
        <div class="admin-page-subtitle" style="margin-bottom:0;">BodaCheck Digital Compliance & Safety ID System — Republic of Uganda</div>
    </div>
    <div style="display:flex; align-items:center; gap:var(--sp-2);">
        <div class="profile-photo" style="width: 45px; height: 45px; border-radius: 50%; background-color: var(--primary); display: flex; align-items: center; justify-content: center; overflow: hidden;">
            <?php if (!empty($logged_in_admin['photo']) && file_exists(__DIR__ . '/../' . $logged_in_admin['photo'])): ?>
                <img src="<?php echo baseUrl() . '/' . $logged_in_admin['photo']; ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
                <span style="color:#fff; font-weight:bold; font-size:1.2rem;"><?php echo strtoupper(substr($logged_in_admin['name'], 0, 1)); ?></span>
            <?php endif; ?>
        </div>
        <span style="font-weight:600; color:var(--text); font-size: 0.95rem;"><?php echo sanitize($logged_in_admin['name']); ?></span>
    </div>
</div>

<!-- ============================================ -->
<!-- KEY PERFORMANCE INDICATORS                    -->
<!-- ============================================ -->
<div class="gov-kpi-row">
    <div class="gov-kpi-card">
        <div class="gov-kpi-icon orange">R</div>
        <div class="gov-kpi-content">
            <div class="gov-kpi-value"><?php echo number_format($total_riders); ?></div>
            <div class="gov-kpi-label">Registered Riders</div>
            <div class="gov-kpi-sub">+<?php echo $new_riders_today; ?> today &middot; +<?php echo $new_riders_this_month; ?> this month</div>
        </div>
    </div>
    <div class="gov-kpi-card">
        <div class="gov-kpi-icon red">V</div>
        <div class="gov-kpi-content">
            <div class="gov-kpi-value"><?php echo number_format($total_violations); ?></div>
            <div class="gov-kpi-label">Total Violations</div>
            <div class="gov-kpi-sub"><?php echo $violations_today; ?> today &middot; <?php echo $violations_this_month; ?> this month</div>
        </div>
    </div>
    <div class="gov-kpi-card">
        <div class="gov-kpi-icon blue">S</div>
        <div class="gov-kpi-content">
            <div class="gov-kpi-value"><?php echo number_format($total_scans); ?></div>
            <div class="gov-kpi-label">QR Scans Logged</div>
            <div class="gov-kpi-sub"><?php echo $scans_today; ?> scans today</div>
        </div>
    </div>
    <div class="gov-kpi-card">
        <div class="gov-kpi-icon green">P</div>
        <div class="gov-kpi-content">
            <div class="gov-kpi-value"><?php echo formatUGX($total_revenue); ?></div>
            <div class="gov-kpi-label">Revenue Collected</div>
            <div class="gov-kpi-sub"><?php echo formatUGX($pending_revenue); ?> pending &middot; <?php echo $payment_rate; ?>% paid</div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- STATUS & COMPLIANCE OVERVIEW                  -->
<!-- ============================================ -->
<div class="gov-stat-grid">
    <div class="gov-stat-card stat-green">
        <div class="gov-stat-number" style="color:var(--green);"><?php echo $status_counts['green']; ?></div>
        <div class="gov-stat-label">Green — Safe to Ride</div>
        <div class="compliance-bar"><div class="compliance-bar-fill high" style="width:<?php echo $total_riders > 0 ? round(($status_counts['green']/$total_riders)*100) : 0; ?>%"></div></div>
    </div>
    <div class="gov-stat-card stat-orange">
        <div class="gov-stat-number" style="color:var(--amber);"><?php echo $status_counts['amber']; ?></div>
        <div class="gov-stat-label">Amber — Caution</div>
        <div class="compliance-bar"><div class="compliance-bar-fill medium" style="width:<?php echo $total_riders > 0 ? round(($status_counts['amber']/$total_riders)*100) : 0; ?>%"></div></div>
    </div>
    <div class="gov-stat-card stat-red">
        <div class="gov-stat-number" style="color:var(--red);"><?php echo $status_counts['red']; ?></div>
        <div class="gov-stat-label">Red — Not Safe</div>
        <div class="compliance-bar"><div class="compliance-bar-fill low" style="width:<?php echo $total_riders > 0 ? round(($status_counts['red']/$total_riders)*100) : 0; ?>%"></div></div>
    </div>
    <div class="gov-stat-card stat-blue">
        <div class="gov-stat-number"><?php echo $avg_score; ?></div>
        <div class="gov-stat-label">Average Safety Score</div>
    </div>
</div>

<!-- ============================================ -->
<!-- COMPLIANCE RATES                             -->
<!-- ============================================ -->
<div class="gov-section">
    <div class="gov-section-title">Rider Compliance Rates</div>
    <div class="gov-stat-grid">
        <div class="gov-stat-card">
            <div class="gov-stat-number" style="color:<?php echo $helmet_pct >= 70 ? 'var(--green)' : 'var(--red)'; ?>;"><?php echo $helmet_pct; ?>%</div>
            <div class="gov-stat-label">Helmet Compliance</div>
            <div class="compliance-bar"><div class="compliance-bar-fill <?php echo $helmet_pct >= 70 ? 'high' : ($helmet_pct >= 40 ? 'medium' : 'low'); ?>" style="width:<?php echo $helmet_pct; ?>%"></div></div>
        </div>
        <div class="gov-stat-card">
            <div class="gov-stat-number" style="color:<?php echo $licence_pct >= 70 ? 'var(--green)' : 'var(--red)'; ?>;"><?php echo $licence_pct; ?>%</div>
            <div class="gov-stat-label">Driving Licence</div>
            <div class="compliance-bar"><div class="compliance-bar-fill <?php echo $licence_pct >= 70 ? 'high' : ($licence_pct >= 40 ? 'medium' : 'low'); ?>" style="width:<?php echo $licence_pct; ?>%"></div></div>
        </div>
        <div class="gov-stat-card">
            <div class="gov-stat-number" style="color:<?php echo $psv_pct >= 70 ? 'var(--green)' : 'var(--red)'; ?>;"><?php echo $psv_pct; ?>%</div>
            <div class="gov-stat-label">PSV Permit</div>
            <div class="compliance-bar"><div class="compliance-bar-fill <?php echo $psv_pct >= 70 ? 'high' : ($psv_pct >= 40 ? 'medium' : 'low'); ?>" style="width:<?php echo $psv_pct; ?>%"></div></div>
        </div>
        <div class="gov-stat-card">
            <div class="gov-stat-number" style="color:<?php echo $insured_pct >= 70 ? 'var(--green)' : 'var(--red)'; ?>;"><?php echo $insured_pct; ?>%</div>
            <div class="gov-stat-label">Insurance Coverage</div>
            <div class="compliance-bar"><div class="compliance-bar-fill <?php echo $insured_pct >= 70 ? 'high' : ($insured_pct >= 40 ? 'medium' : 'low'); ?>" style="width:<?php echo $insured_pct; ?>%"></div></div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ENFORCEMENT & RECENT VIOLATIONS               -->
<!-- ============================================ -->
<div class="gov-analytics-grid">
    <div class="gov-analytics-card">
        <div class="gov-card-header">
            <div>
                <div class="gov-card-title">Recent Violations</div>
                <div class="gov-card-subtitle">Latest enforcement actions logged by officers</div>
            </div>
            <a href="<?php echo $base; ?>/admin/violations.php" class="gov-btn gov-btn-sm">View All</a>
        </div>
        <?php if (empty($recent)): ?>
            <p style="color:#94a3b8; font-size:0.88rem;">No violations recorded yet.</p>
        <?php else: ?>
            <div class="gov-table-wrap">
                <table class="gov-table">
                    <thead><tr><th>Date</th><th>Rider</th><th>Plate</th><th>Type</th><th>Pts</th><th>Officer</th><th>Badge</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent as $v): ?>
                            <tr>
                                <td><?php echo date('d M H:i', strtotime($v['created_at'])); ?></td>
                                <td><?php echo sanitize($v['rider_name']); ?></td>
                                <td style="font-family:var(--font-mono); font-size:0.8rem;"><?php echo sanitize($v['bike_plate']); ?></td>
                                <td><?php echo sanitize($v['violation_type']); ?></td>
                                <td style="color:var(--red); font-weight:600;">-<?php echo $v['points_deducted']; ?></td>
                                <td><?php echo sanitize($v['officer_name']); ?></td>
                                <td style="font-family:var(--font-mono); font-size:0.8rem;"><?php echo sanitize($v['badge_number']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="gov-analytics-card">
        <div class="gov-card-header">
            <div>
                <div class="gov-card-title">Enforcement Summary</div>
                <div class="gov-card-subtitle">Active officers and scan activity</div>
            </div>
        </div>
        <div class="gov-insight">
            <span class="gov-insight-label">Active Traffic Officers</span>
            <span class="gov-insight-value"><?php echo $active_officers; ?></span>
        </div>
        <div class="gov-insight">
            <span class="gov-insight-label">Total QR Scans</span>
            <span class="gov-insight-value"><?php echo number_format($total_scans); ?></span>
        </div>
        <div class="gov-insight">
            <span class="gov-insight-label">Scans Today</span>
            <span class="gov-insight-value"><?php echo $scans_today; ?></span>
        </div>
        <div class="gov-insight">
            <span class="gov-insight-label">Violations This Month</span>
            <span class="gov-insight-value" style="color:var(--red);"><?php echo $violations_this_month; ?></span>
        </div>
        <div class="gov-insight">
            <span class="gov-insight-label">Payment Compliance Rate</span>
            <span class="gov-insight-value"><?php echo $payment_rate; ?>%</span>
        </div>
        <div class="gov-insight">
            <span class="gov-insight-label">Revenue Collected</span>
            <span class="gov-insight-value" style="color:var(--green);"><?php echo formatUGX($total_revenue); ?></span>
        </div>
        <div class="gov-insight">
            <span class="gov-insight-label">Revenue Pending</span>
            <span class="gov-insight-value" style="color:var(--amber);"><?php echo formatUGX($pending_revenue); ?></span>
        </div>

        <div style="margin-top:var(--sp-4); padding-top:var(--sp-3); border-top:1px solid #f1f5f9;">
            <div style="font-size:0.78rem; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:var(--sp-2);">Recent Scans</div>
            <?php foreach ($recent_scans as $s): ?>
                <div style="font-size:0.8rem; color:#475569; padding:3px 0;">
                    <span style="color:#64748b;"><?php echo date('H:i', strtotime($s['created_at'])); ?></span>
                    &middot; <?php echo sanitize($s['rider_name']); ?>
                    &middot; <span style="text-transform:capitalize;"><?php echo $s['scan_type']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
