<?php
/**
 * Rider Dashboard — rider/dashboard.php
 * --------------------------------------
 * Shows the rider their safety score, status badge, QR code,
 * download QR button, violation history, compliance checklist,
 * insurance status, and Mobile Money payment status.
 * Riders can only see their own data.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../libs/qrlib.php';

requireRole('rider');
$base = baseUrl();
$rider_id = $_SESSION['user_id'];

// Fetch the rider's full profile
$stmt = $pdo->prepare(
    'SELECT r.*, s.name AS sacco_name
     FROM riders r
     JOIN saccos s ON r.sacco_id = s.id
     WHERE r.id = ?'
);
$stmt->execute([$rider_id]);
$rider = $stmt->fetch();

if (!$rider) {
    session_destroy();
    header('Location: ' . $base . '/login.php');
    exit;
}

// Generate QR code image if it doesn't exist
$qr_filename = 'assets/qr_codes/' . $rider['qr_token'] . '.png';
$qr_filepath = __DIR__ . '/../' . $qr_filename;
if (!file_exists($qr_filepath)) {
    $scan_url = getQrScanUrl($rider['qr_token']);
    generateQRCode($scan_url, $qr_filepath);
}

// Fetch violation history
$stmt = $pdo->prepare(
    'SELECT v.*, u.name AS officer_name
     FROM violations v
     JOIN users u ON v.officer_id = u.id
     WHERE v.rider_id = ?
     ORDER BY v.created_at DESC'
);
$stmt->execute([$rider_id]);
$violations = $stmt->fetchAll();

// Fetch payment history
$stmt = $pdo->prepare(
    'SELECT * FROM payments WHERE rider_id = ? ORDER BY created_at DESC'
);
$stmt->execute([$rider_id]);
$payments = $stmt->fetchAll();

$score_class = 'score-' . $rider['status'];

// Handle MoMo payment initiation
$payment_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initiate_payment'])) {
    $fee = getRegistrationFee();
    $momo = $rider['momo_phone'] ?: $rider['phone_number'];

    // In a real implementation, this would call a Payment Gateway API (like Flutterwave/Yo! Uganda) for MTN/Airtel
    // For now, we simulate the payment initiation
    $stmt = $pdo->prepare(
        'INSERT INTO payments (rider_id, amount, momo_phone, payment_type, status)
         VALUES (?, ?, ?, "registration", "pending")'
    );
    $stmt->execute([$rider_id, $fee, $momo]);

    // Update rider payment status to pending
    $stmt = $pdo->prepare('UPDATE riders SET payment_status = "pending" WHERE id = ?');
    $stmt->execute([$rider_id]);

    $payment_msg = 'Mobile Money payment initiated! You will receive a prompt on ' . sanitize($momo) . '. Enter your PIN to complete payment of ' . formatUGX($fee) . '.';

    // Refresh rider data
    $stmt = $pdo->prepare('SELECT * FROM riders WHERE id = ?');
    $stmt->execute([$rider_id]);
    $rider = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header" style="display:flex; align-items:center; gap:var(--sp-3); margin-bottom:var(--sp-5);">
    <div class="profile-photo" style="width: 50px; height: 50px; border-radius: 50%; background-color: var(--grey-light); display: flex; align-items: center; justify-content: center; overflow: hidden;">
        <?php if (!empty($rider['photo']) && file_exists(__DIR__ . '/../' . $rider['photo'])): ?>
            <img src="<?php echo $base . '/' . $rider['photo']; ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
        <?php else: ?>
            <span style="color:#fff; font-size:1.2rem; font-weight:bold;"><?php echo strtoupper(substr($rider['full_name'], 0, 1)); ?></span>
        <?php endif; ?>
    </div>
    <h1 style="margin:0;"><?php echo sanitize($rider['full_name']); ?>'s Dashboard</h1>
</div>

<div class="dashboard-grid">
    <!-- Score card -->
    <div class="card dash-score-card">
        <div style="font-size:0.85rem; color:var(--grey); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:var(--sp-3);">Safety Score</div>
        <div class="dash-score-big <?php echo $score_class; ?>">
            <?php echo $rider['safety_score']; ?>
        </div>
        <div class="mt-4"><?php echo statusBadge($rider['status']); ?></div>
        <div class="mt-4" style="font-size:0.9rem; color:var(--grey-light);">
            Insurance:
            <?php echo $rider['is_insured']
                ? '<span style="color:var(--green);">Insured</span>'
                : '<span style="color:var(--red);">Not Insured</span>'; ?>
        </div>
        <?php if ($rider['status'] === 'green'): ?>
            <div style="margin-top:var(--sp-3); font-size:0.8rem; color:var(--green);">
                Green badge = lower insurance premiums
            </div>
        <?php endif; ?>
    </div>

    <!-- QR Code card -->
    <div class="card dash-qr-card">
        <div style="font-size:0.85rem; color:var(--grey); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:var(--sp-3);">My QR Code</div>
        <img src="<?php echo $base . '/' . $qr_filename; ?>" alt="My QR Code" class="dash-qr-img">
        <div>
            <a href="<?php echo $base . '/' . $qr_filename; ?>"
               download="BodaCheck-QR-<?php echo $rider['qr_token']; ?>.png"
               class="btn btn-primary btn-sm">Download QR Code</a>
        </div>
        <div class="mt-4" style="font-size:0.8rem; color:var(--grey-dark);">
            Print this QR code and stick it on your helmet and bike.
        </div>
    </div>

    <!-- Payment status card -->
    <div class="card dash-payment-card">
        <div style="font-size:0.85rem; color:var(--grey); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:var(--sp-3);">Payment Status</div>
        <div style="font-size:1.5rem; font-weight:700;" class="payment-status-<?php echo $rider['payment_status']; ?>">
            <?php echo ucfirst($rider['payment_status']); ?>
        </div>
        <?php if ($rider['payment_expires_at']): ?>
            <div style="font-size:0.85rem; color:var(--grey-light); margin-top:var(--sp-2);">
                Expires: <?php echo date('M j, Y', strtotime($rider['payment_expires_at'])); ?>
            </div>
        <?php endif; ?>

        <?php if ($rider['payment_status'] !== 'paid'): ?>
            <div class="mt-4">
                <?php if ($payment_msg): ?>
                    <div class="alert alert-info" style="font-size:0.85rem;"><?php echo $payment_msg; ?></div>
                <?php else: ?>
                    <form method="POST" action="">
                        <button type="submit" name="initiate_payment" class="btn btn-primary btn-sm" style="width:100%;">
                            Pay <?php echo formatUGX(getRegistrationFee()); ?> via Mobile Money
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div style="font-size:0.8rem; color:var(--grey-dark); margin-top:var(--sp-3);">
            MoMo: <?php echo sanitize($rider['momo_phone'] ?: $rider['phone_number']); ?>
        </div>
    </div>

    <!-- Compliance checklist card -->
    <div class="card">
        <div style="font-size:0.85rem; color:var(--grey); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:var(--sp-3);">Compliance Checklist</div>
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
        <div class="mt-4">
            <a href="<?php echo $base; ?>/rider/edit_profile.php" class="btn btn-secondary btn-sm" style="width:100%;">Edit Profile</a>
        </div>
    </div>

    <!-- Violations card -->
    <div class="card dash-violations-card">
        <div class="flex-between mb-4">
            <div class="card-header" style="margin-bottom:0;">Violation History</div>
        </div>
        <?php if (empty($violations)): ?>
            <p style="color:var(--grey);">No violations on record. Keep riding safe!</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Type</th><th>Points</th><th>Location</th><th>Officer</th></tr></thead>
                    <tbody>
                        <?php foreach ($violations as $v): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($v['created_at'])); ?></td>
                                <td><?php echo sanitize($v['violation_type']); ?></td>
                                <td style="color:var(--red);">-<?php echo $v['points_deducted']; ?></td>
                                <td><?php echo sanitize($v['location']); ?></td>
                                <td><?php echo sanitize($v['officer_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
