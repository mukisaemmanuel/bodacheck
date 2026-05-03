<?php
/**
 * Officer Scan Page — officer/scan.php
 * --------------------------------------
 * Traffic officers search for riders by QR token, phone number,
 * or bike plate. The matching rider appears with their full profile
 * and a button to log a violation. Every scan is logged digitally.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('officer');
$base = baseUrl();

$search = '';
$rider = null;
$violations = [];

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['search'])) {
    $search = sanitize($_GET['search']);

    // Search by QR token, phone number, or bike plate
    $stmt = $pdo->prepare(
        'SELECT r.*, s.name AS sacco_name
         FROM riders r
         JOIN saccos s ON r.sacco_id = s.id
         WHERE r.qr_token = ? OR r.phone_number = ? OR r.bike_plate = ?
         LIMIT 1'
    );
    $stmt->execute([$search, $search, $search]);
    $rider = $stmt->fetch();

    if ($rider) {
        // Fetch violation history
        $stmt = $pdo->prepare(
            'SELECT v.*, u.name AS officer_name
             FROM violations v
             JOIN users u ON v.officer_id = u.id
             WHERE v.rider_id = ?
             ORDER BY v.created_at DESC'
        );
        $stmt->execute([$rider['id']]);
        $violations = $stmt->fetchAll();
    }
}

// Fetch logged-in officer details
$stmt_officer = $pdo->prepare('SELECT name, photo FROM users WHERE id = ?');
$stmt_officer->execute([$_SESSION['user_id']]);
$logged_in_officer = $stmt_officer->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:var(--sp-5);">
    <h1 style="margin:0;">Scan Rider</h1>
    <div style="display:flex; align-items:center; gap:var(--sp-2);">
        <div class="profile-photo" style="width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary); display: flex; align-items: center; justify-content: center; overflow: hidden;">
            <?php if (!empty($logged_in_officer['photo']) && file_exists(__DIR__ . '/../' . $logged_in_officer['photo'])): ?>
                <img src="<?php echo $base . '/' . $logged_in_officer['photo']; ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
                <span style="color:#fff; font-weight:bold; font-size:1.1rem;"><?php echo strtoupper(substr($logged_in_officer['name'], 0, 1)); ?></span>
            <?php endif; ?>
        </div>
        <span style="font-weight:600; color:var(--text); font-size: 0.95rem;"><?php echo sanitize($logged_in_officer['name']); ?></span>
    </div>
</div>

<div class="search-section">
    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="search" class="form-control"
                   placeholder="Enter QR token, phone number, or bike plate..."
                   value="<?php echo htmlspecialchars($search); ?>"
                   autofocus required>
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <?php if (!empty($search) && !$rider): ?>
        <div class="alert alert-error mt-5">No rider found matching "<?php echo sanitize($search); ?>"</div>
    <?php endif; ?>

    <?php if ($rider): ?>
        <div class="card search-result-card">
            <div class="flex-between mb-4">
                <div>
                    <h2 style="font-size:1.25rem;"><?php echo sanitize($rider['full_name']); ?></h2>
                    <div style="color:var(--grey-light);"><?php echo sanitize($rider['bike_plate']); ?> &middot; <?php echo sanitize($rider['sacco_name']); ?></div>
                </div>
                <div><?php echo statusBadge($rider['status']); ?></div>
            </div>

            <div class="text-center mb-5">
                <div style="font-size:3rem; font-weight:700;" class="score-<?php echo $rider['status']; ?>">
                    <?php echo $rider['safety_score']; ?>
                </div>
                <div style="font-size:0.85rem; color:var(--grey);">Safety Score</div>
            </div>

            <!-- Compliance checklist for officer view -->
            <div style="margin-bottom:var(--sp-5);">
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

            <div class="scan-details">
                <div>
                    <div class="scan-detail-label">Phone</div>
                    <div class="scan-detail-value"><?php echo sanitize($rider['phone_number']); ?></div>
                </div>
                <div>
                    <div class="scan-detail-label">National ID</div>
                    <div class="scan-detail-value"><?php echo sanitize($rider['national_id']); ?></div>
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
                    <div class="scan-detail-label">QR Token</div>
                    <div class="scan-detail-value" style="font-family:var(--font-mono); font-size:0.8rem;"><?php echo sanitize($rider['qr_token']); ?></div>
                </div>
            </div>

            <div class="mt-5">
                <a href="<?php echo $base; ?>/officer/log_violation.php?rider_id=<?php echo $rider['id']; ?>"
                   class="btn btn-danger" style="width:100%;">Log Violation</a>
            </div>

            <?php if (!empty($violations)): ?>
                <div class="mt-5">
                    <h3 style="font-size:1rem; color:var(--grey-light); margin-bottom:var(--sp-3);">Violation History</h3>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Date</th><th>Type</th><th>Points</th><th>Location</th></tr></thead>
                            <tbody>
                                <?php foreach ($violations as $v): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($v['created_at'])); ?></td>
                                        <td><?php echo sanitize($v['violation_type']); ?></td>
                                        <td style="color:var(--red);">-<?php echo $v['points_deducted']; ?></td>
                                        <td><?php echo sanitize($v['location']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
