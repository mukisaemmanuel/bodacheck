<?php
/**
 * Admin Analytics — Government Policy Intelligence
 * -------------------------------------------------
 * Strategic analytics for KCCA and Uganda Police leadership.
 * Shows national road safety context, compliance trends,
 * enforcement effectiveness, and revenue projections.
 * Designed for policy decisions and B2G licensing.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

// ---- System Metrics ----
$total_riders = $pdo->query('SELECT COUNT(*) FROM riders')->fetchColumn();
$total_violations = $pdo->query('SELECT COUNT(*) FROM violations')->fetchColumn();
$total_scans = $pdo->query('SELECT COUNT(*) FROM scan_logs')->fetchColumn();
$avg_score = round($pdo->query('SELECT AVG(safety_score) FROM riders')->fetchColumn() ?? 0);

// ---- Compliance Rates ----
$helmet_pct = $total_riders > 0 ? round(($pdo->query('SELECT COUNT(*) FROM riders WHERE has_helmet = 1')->fetchColumn() / $total_riders) * 100) : 0;
$licence_pct = $total_riders > 0 ? round(($pdo->query('SELECT COUNT(*) FROM riders WHERE has_licence = 1')->fetchColumn() / $total_riders) * 100) : 0;
$psv_pct = $total_riders > 0 ? round(($pdo->query('SELECT COUNT(*) FROM riders WHERE has_psv_permit = 1')->fetchColumn() / $total_riders) * 100) : 0;
$logbook_pct = $total_riders > 0 ? round(($pdo->query('SELECT COUNT(*) FROM riders WHERE has_logbook = 1')->fetchColumn() / $total_riders) * 100) : 0;
$insured_pct = $total_riders > 0 ? round(($pdo->query('SELECT COUNT(*) FROM riders WHERE is_insured = 1')->fetchColumn() / $total_riders) * 100) : 0;

// ---- Violation Breakdown ----
$stmt = $pdo->query(
    'SELECT violation_type, COUNT(*) AS count, SUM(points_deducted) AS total_points
     FROM violations GROUP BY violation_type ORDER BY count DESC'
);
$violation_breakdown = $stmt->fetchAll();

// ---- Scan Types ----
$stmt = $pdo->query('SELECT scan_type, COUNT(*) AS count FROM scan_logs GROUP BY scan_type');
$scan_types = $stmt->fetchAll();

// ---- SACCO Distribution ----
$stmt = $pdo->query(
    'SELECT s.name, s.district, COUNT(r.id) AS rider_count,
            SUM(CASE WHEN r.status = "green" THEN 1 ELSE 0 END) AS green_count,
            SUM(CASE WHEN r.status = "amber" THEN 1 ELSE 0 END) AS amber_count,
            SUM(CASE WHEN r.status = "red" THEN 1 ELSE 0 END) AS red_count,
            AVG(r.safety_score) AS avg_score
     FROM saccos s
     LEFT JOIN riders r ON r.sacco_id = s.id
     GROUP BY s.id
     ORDER BY rider_count DESC'
);
$sacco_stats = $stmt->fetchAll();

// ---- Payment / Revenue ----
$total_revenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'successful'")->fetchColumn();
$paid_riders = $pdo->query("SELECT COUNT(*) FROM riders WHERE payment_status = 'paid'")->fetchColumn();
$payment_rate = $total_riders > 0 ? round(($paid_riders / $total_riders) * 100) : 0;

// ---- 30-day trend ----
$stmt = $pdo->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS count
     FROM violations
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(created_at)
     ORDER BY day"
);
$daily_violations = $stmt->fetchAll();

// ---- Officer Activity ----
$stmt = $pdo->query(
    'SELECT u.name, u.badge_number, COUNT(v.id) AS violation_count
     FROM users u
     LEFT JOIN violations v ON v.officer_id = u.id
     WHERE u.role = "officer" AND u.is_active = 1
     GROUP BY u.id
     ORDER BY violation_count DESC
     LIMIT 10'
);
$officer_activity = $stmt->fetchAll();

// ---- Projected annual revenue ----
// Based on current payment rate and registration fee
$projected_annual = $total_riders * getRegistrationFee() * ($payment_rate / 100);
// If all 6,000 SACCOs adopted with avg 500 riders each
$full_scale_riders = 6000 * 500;
$full_scale_revenue = $full_scale_riders * getRegistrationFee();
?>

<div class="admin-page-title">National Analytics & Policy Intelligence</div>
<div class="admin-page-subtitle">Strategic data for KCCA, Uganda Police, and Ministry of Works & Transport</div>

<!-- ============================================ -->
<!-- UGANDA ROAD SAFETY CONTEXT                    -->
<!-- ============================================ -->
<div class="gov-context-banner">
    <h3>Uganda Road Safety Context — National Baseline</h3>
    <div class="gov-context-stat">
        <span class="gov-context-stat-label">Road deaths in Uganda (2025)</span>
        <span class="gov-context-stat-value red">5,383</span>
    </div>
    <div class="gov-context-stat">
        <span class="gov-context-stat-label">Motorcyclist & passenger fatalities</span>
        <span class="gov-context-stat-value red">47%</span>
    </div>
    <div class="gov-context-stat">
        <span class="gov-context-stat-label">Kampala riders with no driving licence</span>
        <span class="gov-context-stat-value red">94%</span>
    </div>
    <div class="gov-context-stat">
        <span class="gov-context-stat-label">Riders with no PSV permit</span>
        <span class="gov-context-stat-value red">98%</span>
    </div>
    <div class="gov-context-stat">
        <span class="gov-context-stat-label">New motorcycles entering Kampala/month</span>
        <span class="gov-context-stat-value">25,000</span>
    </div>
    <div class="gov-context-stat">
        <span class="gov-context-stat-label">Registered boda SACCOs in Uganda</span>
        <span class="gov-context-stat-value">6,000+</span>
    </div>
    <div class="gov-context-stat">
        <span class="gov-context-stat-label">Riders arrested for no helmet (2024)</span>
        <span class="gov-context-stat-value red">32,308</span>
    </div>
</div>

<!-- ============================================ -->
<!-- COMPLIANCE OVERVIEW                           -->
<!-- ============================================ -->
<div class="gov-section">
    <div class="gov-section-title">Rider Compliance Overview</div>
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
            <div class="gov-stat-number" style="color:<?php echo $logbook_pct >= 70 ? 'var(--green)' : 'var(--red)'; ?>;"><?php echo $logbook_pct; ?>%</div>
            <div class="gov-stat-label">Logbook</div>
            <div class="compliance-bar"><div class="compliance-bar-fill <?php echo $logbook_pct >= 70 ? 'high' : ($logbook_pct >= 40 ? 'medium' : 'low'); ?>" style="width:<?php echo $logbook_pct; ?>%"></div></div>
        </div>
        <div class="gov-stat-card">
            <div class="gov-stat-number" style="color:<?php echo $insured_pct >= 70 ? 'var(--green)' : 'var(--red)'; ?>;"><?php echo $insured_pct; ?>%</div>
            <div class="gov-stat-label">Insurance Coverage</div>
            <div class="compliance-bar"><div class="compliance-bar-fill <?php echo $insured_pct >= 70 ? 'high' : ($insured_pct >= 40 ? 'medium' : 'low'); ?>" style="width:<?php echo $insured_pct; ?>%"></div></div>
        </div>
        <div class="gov-stat-card">
            <div class="gov-stat-number"><?php echo $avg_score; ?>/100</div>
            <div class="gov-stat-label">Average Safety Score</div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- VIOLATION ANALYSIS & ENFORCEMENT              -->
<!-- ============================================ -->
<div class="gov-analytics-grid">
    <div class="gov-analytics-card">
        <div class="gov-card-title">Violation Breakdown by Type</div>
        <?php if (empty($violation_breakdown)): ?>
            <p style="color:#94a3b8; font-size:0.88rem;">No violations recorded yet.</p>
        <?php else: ?>
            <div class="gov-table-wrap">
                <table class="gov-table">
                    <thead><tr><th>Violation Type</th><th>Count</th><th>Total Points</th><th>Points Each</th><th>% of Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($violation_breakdown as $vb): ?>
                            <tr>
                                <td style="font-weight:500;"><?php echo sanitize($vb['violation_type']); ?></td>
                                <td><?php echo $vb['count']; ?></td>
                                <td style="color:var(--red); font-weight:600;">-<?php echo $vb['total_points']; ?></td>
                                <td><?php echo getPointsForViolation($vb['violation_type']); ?></td>
                                <td><?php echo $total_violations > 0 ? round(($vb['count']/$total_violations)*100) : 0; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="gov-analytics-card">
        <div class="gov-card-title">Officer Enforcement Activity</div>
        <?php if (empty($officer_activity)): ?>
            <p style="color:#94a3b8; font-size:0.88rem;">No officer activity recorded.</p>
        <?php else: ?>
            <?php foreach ($officer_activity as $oa): ?>
                <div class="gov-insight">
                    <span class="gov-insight-label">
                        <?php echo sanitize($oa['name']); ?>
                        <span style="font-family:var(--font-mono); font-size:0.75rem; color:#94a3b8; margin-left:4px;"><?php echo sanitize($oa['badge_number']); ?></span>
                    </span>
                    <span class="gov-insight-value"><?php echo $oa['violation_count']; ?> violations</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="gov-analytics-card">
        <div class="gov-card-title">Scan Activity by Type</div>
        <?php if (empty($scan_types)): ?>
            <p style="color:#94a3b8; font-size:0.88rem;">No scans recorded yet.</p>
        <?php else: ?>
            <?php foreach ($scan_types as $st): ?>
                <div class="gov-insight">
                    <span class="gov-insight-label" style="text-transform:capitalize;"><?php echo $st['scan_type']; ?> Scans</span>
                    <span class="gov-insight-value"><?php echo number_format($st['count']); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="gov-analytics-card">
        <div class="gov-card-title">30-Day Violation Trend</div>
        <?php if (empty($daily_violations)): ?>
            <p style="color:#94a3b8; font-size:0.88rem;">No violations in the last 30 days.</p>
        <?php else: ?>
            <?php foreach ($daily_violations as $dv): ?>
                <div class="gov-insight">
                    <span class="gov-insight-label"><?php echo date('d M Y', strtotime($dv['day'])); ?></span>
                    <span class="gov-insight-value" style="color:var(--red);"><?php echo $dv['count']; ?> violations</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================ -->
<!-- SACCO DISTRIBUTION                            -->
<!-- ============================================ -->
<div class="gov-section mt-6">
    <div class="gov-section-title">SACCO Distribution & Performance</div>
    <div class="gov-card">
        <div class="gov-table-wrap">
            <table class="gov-table">
                <thead><tr><th>SACCO Name</th><th>District</th><th>Riders</th><th>Green</th><th>Amber</th><th>Red</th><th>Avg Score</th></tr></thead>
                <tbody>
                    <?php foreach ($sacco_stats as $ss): ?>
                        <tr>
                            <td style="font-weight:500;"><?php echo sanitize($ss['name']); ?></td>
                            <td><?php echo sanitize($ss['district']); ?></td>
                            <td><?php echo $ss['rider_count']; ?></td>
                            <td style="color:var(--green); font-weight:600;"><?php echo $ss['green_count']; ?></td>
                            <td style="color:var(--amber); font-weight:600;"><?php echo $ss['amber_count']; ?></td>
                            <td style="color:var(--red); font-weight:600;"><?php echo $ss['red_count']; ?></td>
                            <td><?php echo round($ss['avg_score'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- REVENUE PROJECTIONS                           -->
<!-- ============================================ -->
<div class="gov-section mt-6">
    <div class="gov-section-title">Revenue & Scale Projections</div>
    <div class="gov-stat-grid">
        <div class="gov-stat-card stat-green">
            <div class="gov-stat-number" style="color:var(--green);"><?php echo formatUGX($total_revenue); ?></div>
            <div class="gov-stat-label">Revenue Collected</div>
        </div>
        <div class="gov-stat-card stat-orange">
            <div class="gov-stat-number" style="color:var(--amber);"><?php echo $payment_rate; ?>%</div>
            <div class="gov-stat-label">Payment Compliance Rate</div>
        </div>
        <div class="gov-stat-card stat-blue">
            <div class="gov-stat-number"><?php echo formatUGX(round($projected_annual)); ?></div>
            <div class="gov-stat-label">Projected Annual Revenue (Current Scale)</div>
        </div>
        <div class="gov-stat-card stat-grey">
            <div class="gov-stat-number"><?php echo formatUGX($full_scale_revenue); ?></div>
            <div class="gov-stat-label">Full Scale (6,000 SACCOs x 500 Riders)</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
