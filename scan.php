<?php


require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
$base = baseUrl();

$token = $_GET['token'] ?? '';

if (empty($token)) {
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="scan-result"><div class="alert alert-error">Invalid QR code. No token found.</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT r.*, s.name AS sacco_name, s.district AS sacco_district
     FROM riders r
     JOIN saccos s ON r.sacco_id = s.id
     WHERE r.qr_token = ?'
);
$stmt->execute([$token]);
$rider = $stmt->fetch();

if (!$rider) {
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="scan-result">';
    echo '<div class="scan-photo-placeholder" style="border-color:var(--red);">?</div>';
    echo '<div class="scan-name" style="color:var(--red);">Unregistered Rider</div>';
    echo '<div class="scan-plate">This QR code is not registered in BodaCheck</div>';
    echo '<div class="scan-score-section"><div class="scan-score-number score-red">0</div></div>';
    echo '<div class="scan-status-badge">' . statusBadge('red') . '</div>';
    echo '<div class="scan-footer-note">Each QR is cryptographically signed and links to a live database. Fake stickers return "unregistered".</div>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}


$viewer_role = 'public';
$viewer_id = null;

if (isLoggedIn()) {
    if (isRider()) {
        $viewer_role = ($_SESSION['user_id'] == $rider['id']) ? 'rider_own' : 'public';
    } elseif (isOfficer()) {
        $viewer_role = 'officer';
        $viewer_id = $_SESSION['user_id'];
    } elseif (isAdmin()) {
        $viewer_role = 'admin';
        $viewer_id = $_SESSION['user_id'];
    }
}

// Log this scan — creates a digital paper trail
$scan_type = ($viewer_role === 'rider_own') ? 'rider' : $viewer_role;
logScan($pdo, $rider['id'], $scan_type, $viewer_id);

// Determine score colour class
$score_class = 'score-' . $rider['status'];

require_once __DIR__ . '/includes/header.php';
?>


<div class="scan-result">

    <!-- Rider photo or placeholder -->
    <?php if (!empty($rider['photo']) && file_exists($rider['photo'])): ?>
        <img src="<?php echo $base . '/' . htmlspecialchars($rider['photo']); ?>"
             alt="<?php echo sanitize($rider['full_name']); ?>"
             class="scan-photo">
    <?php else: ?>
        <div class="scan-photo-placeholder" style="border-color:var(--<?php echo $rider['status']; ?>);">
            <?php echo strtoupper(substr($rider['full_name'], 0, 1)); ?>
        </div>
    <?php endif; ?>

    <!-- Rider name — large and prominent -->
    <div class="scan-name"><?php echo sanitize($rider['full_name']); ?></div>

    <!-- Bike plate -->
    <div class="scan-plate"><?php echo sanitize($rider['bike_plate']); ?></div>

    <!-- Safety score — the BIGGEST element on the page -->
    <div class="scan-score-section">
        <div class="scan-score-number <?php echo $score_class; ?>">
            <?php echo $rider['safety_score']; ?>
        </div>
        <div style="font-size:0.9rem; color:var(--grey); margin-top:4px;">Safety Score</div>
    </div>

    <!-- Status badge — green, amber, or red -->
    <div class="scan-status-badge">
        <?php echo statusBadge($rider['status']); ?>
    </div>

    <!-- Compliance checklist — shows what the rider has and what they're missing -->
    <div class="scan-checklist">
        <div class="checklist-item <?php echo $rider['has_helmet'] ? 'has' : 'missing'; ?>">
            <span class="check-icon"><?php echo $rider['has_helmet'] ? '&#10003;' : '&#10007;'; ?></span>
            <span>Helmet</span>
        </div>
        <div class="checklist-item <?php echo $rider['has_licence'] ? 'has' : 'missing'; ?>">
            <span class="check-icon"><?php echo $rider['has_licence'] ? '&#10003;' : '&#10007;'; ?></span>
            <span>Driving Licence</span>
        </div>
        <div class="checklist-item <?php echo $rider['has_psv_permit'] ? 'has' : 'missing'; ?>">
            <span class="check-icon"><?php echo $rider['has_psv_permit'] ? '&#10003;' : '&#10007;'; ?></span>
            <span>PSV Permit</span>
        </div>
        <div class="checklist-item <?php echo $rider['has_logbook'] ? 'has' : 'missing'; ?>">
            <span class="check-icon"><?php echo $rider['has_logbook'] ? '&#10003;' : '&#10007;'; ?></span>
            <span>Logbook</span>
        </div>
        <div class="checklist-item <?php echo $rider['is_insured'] ? 'has' : 'missing'; ?>">
            <span class="check-icon"><?php echo $rider['is_insured'] ? '&#10003;' : '&#10007;'; ?></span>
            <span>Insurance</span>
        </div>
    </div>

    <!-- Additional details -->
    <div class="scan-details">
        <div>
            <div class="scan-detail-label">SACCO</div>
            <div class="scan-detail-value"><?php echo sanitize($rider['sacco_name']); ?></div>
        </div>
        <div>
            <div class="scan-detail-label">District</div>
            <div class="scan-detail-value"><?php echo sanitize($rider['sacco_district']); ?></div>
        </div>
        <div>
            <div class="scan-detail-label">Insurance</div>
            <div class="scan-detail-value">
                <?php echo $rider['is_insured']
                    ? '<span style="color:var(--green);">Insured</span>'
                    : '<span style="color:var(--red);">Not Insured</span>'; ?>
            </div>
        </div>
        <div>
            <div class="scan-detail-label">Status</div>
            <div class="scan-detail-value">
                <?php
                    $labels = ['green' => 'Safe to Ride', 'amber' => 'Caution', 'red' => 'Not Safe'];
                    echo $labels[$rider['status']] ?? $rider['status'];
                ?>
            </div>
        </div>
    </div>

    <!-- Insurance premium note — clean score = lower premium -->
    <?php if ($rider['status'] === 'green'): ?>
        <div class="scan-premium-note">
            <strong>Green Badge Rider</strong> — This rider qualifies for lower insurance premiums through our insurance partners. Clean score = cheaper coverage.
        </div>
    <?php elseif ($rider['status'] === 'red'): ?>
        <div class="scan-premium-note">
            <strong>Red Flag</strong> — This rider has a poor safety record. Consider choosing a green-badge rider for your safety.
        </div>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- OFFICER VIEW: Log violation button + history -->
    <!-- ============================================ -->
    <?php if ($viewer_role === 'officer'): ?>
        <div class="mt-5">
            <a href="<?php echo $base; ?>/officer/log_violation.php?rider_id=<?php echo $rider['id']; ?>"
               class="btn btn-danger" style="width:100%;">
                Log Violation
            </a>
        </div>

        <div class="mt-5 text-left">
            <h3 style="font-size:1rem; color:var(--grey-light); margin-bottom:var(--sp-3);">Violation History</h3>
            <?php
                $stmt = $pdo->prepare(
                    'SELECT v.*, u.name AS officer_name
                     FROM violations v
                     JOIN users u ON v.officer_id = u.id
                     WHERE v.rider_id = ?
                     ORDER BY v.created_at DESC'
                );
                $stmt->execute([$rider['id']]);
                $violations = $stmt->fetchAll();
            ?>
            <?php if (empty($violations)): ?>
                <p style="color:var(--grey);">No violations on record.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Type</th><th>Points</th><th>Date</th><th>Officer</th></tr></thead>
                        <tbody>
                            <?php foreach ($violations as $v): ?>
                                <tr>
                                    <td><?php echo sanitize($v['violation_type']); ?></td>
                                    <td style="color:var(--red);">-<?php echo $v['points_deducted']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($v['created_at'])); ?></td>
                                    <td><?php echo sanitize($v['officer_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- ADMIN VIEW: Edit score button                -->
    <!-- ============================================ -->
    <?php if ($viewer_role === 'admin'): ?>
        <div class="mt-5">
            <a href="<?php echo $base; ?>/admin/riders.php?edit=<?php echo $rider['id']; ?>"
               class="btn btn-primary" style="width:100%;">Edit Rider Score</a>
        </div>
    <?php endif; ?>

    <!-- ============================================ -->
    <!-- RIDER OWN VIEW: Dashboard link               -->
    <!-- ============================================ -->
    <?php if ($viewer_role === 'rider_own'): ?>
        <div class="mt-5">
            <a href="<?php echo $base; ?>/rider/dashboard.php"
               class="btn btn-primary" style="width:100%;">Go to My Dashboard</a>
        </div>
    <?php endif; ?>

    <div class="scan-footer-note">
        BodaCheck — Digital Compliance & Safety ID for Uganda's Boda Boda Riders<br>
        No GPS tracking. No real-time location. Just a digital safety record.
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
