<?php
/**
 * Admin Riders Page — Government National Registry
 * --------------------------------------------------
 * Searchable national registry of all registered boda boda riders.
 * Admins can manually adjust a rider's score with a documented reason.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

$search = '';
$edit_rider = null;
$edit_success = '';
$edit_error = '';

// Handle score adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_score'])) {
    $rider_id  = (int)($_POST['rider_id'] ?? 0);
    $new_score = (int)($_POST['new_score'] ?? 0);
    $reason    = sanitize($_POST['reason'] ?? '');

    if ($rider_id > 0 && $new_score >= 0 && $new_score <= 100 && !empty($reason)) {
        $new_status = calculateStatus($new_score);
        $is_insured = isset($_POST['is_insured']) ? 1 : 0;
        $insurance_provider = sanitize($_POST['insurance_provider'] ?? '');
        $insurance_policy_number = sanitize($_POST['insurance_policy_number'] ?? '');
        $insurance_expiry_date = $_POST['insurance_expiry_date'] ?? null;
        if (empty($insurance_expiry_date)) $insurance_expiry_date = null;

        $stmt = $pdo->prepare('UPDATE riders SET safety_score = ?, status = ?, is_insured = ?, insurance_provider = ?, insurance_policy_number = ?, insurance_expiry_date = ? WHERE id = ?');
        $stmt->execute([$new_score, $new_status, $is_insured, $insurance_provider, $insurance_policy_number, $insurance_expiry_date, $rider_id]);
        $edit_success = 'Details updated. New status: ' . strtoupper($new_status) . ' (' . $new_score . '/100). Reason: ' . $reason;
    } else {
        $edit_error = 'Please provide a valid score (0-100) and a reason for the adjustment.';
    }
}

// Handle search
if (!empty($_GET['search'])) {
    $search = sanitize($_GET['search']);
}

// Check if editing a specific rider
if (!empty($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT r.*, s.name AS sacco_name FROM riders r JOIN saccos s ON r.sacco_id = s.id WHERE r.id = ?');
    $stmt->execute([$edit_id]);
    $edit_rider = $stmt->fetch();
}

// Fetch riders with optional search
if (!empty($search)) {
    $stmt = $pdo->prepare(
        'SELECT r.*, s.name AS sacco_name
         FROM riders r JOIN saccos s ON r.sacco_id = s.id
         WHERE r.full_name LIKE ? OR r.phone_number LIKE ? OR r.bike_plate LIKE ? OR r.qr_token LIKE ?
         ORDER BY r.created_at DESC'
    );
    $like = "%$search%";
    $stmt->execute([$like, $like, $like, $like]);
} else {
    $stmt = $pdo->query(
        'SELECT r.*, s.name AS sacco_name
         FROM riders r JOIN saccos s ON r.sacco_id = s.id
         ORDER BY r.created_at DESC'
    );
}
$riders = $stmt->fetchAll();
?>

<div class="admin-page-title">National Rider Registry</div>
<div class="admin-page-subtitle">All registered boda boda riders in the BodaCheck system</div>

<!-- Search bar -->
<div class="gov-filter-bar" style="max-width:500px; margin-bottom:var(--sp-5);">
    <form method="GET" action="" style="display:flex; gap:var(--sp-3); width:100%;">
        <input type="text" name="search" class="gov-form-control"
               placeholder="Search by name, phone, plate, or token..."
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="gov-btn gov-btn-primary">Search</button>
    </form>
</div>

<!-- Score adjustment panel -->
<?php if ($edit_rider): ?>
    <div class="gov-card" style="max-width:500px;">
        <div class="gov-card-header">
            <div>
                <div class="gov-card-title">Edit Rider Details & Score — <?php echo sanitize($edit_rider['full_name']); ?></div>
                <div class="gov-card-subtitle"><?php echo sanitize($edit_rider['bike_plate']); ?> &middot; <?php echo sanitize($edit_rider['sacco_name']); ?></div>
            </div>
            <a href="<?php echo $base; ?>/admin/riders.php" class="gov-btn gov-btn-sm">Cancel</a>
        </div>
        <?php if ($edit_success): ?>
            <div class="gov-alert gov-alert-success"><?php echo $edit_success; ?></div>
        <?php endif; ?>
        <?php if ($edit_error): ?>
            <div class="gov-alert gov-alert-error"><?php echo $edit_error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="adjust_score" value="1">
            <input type="hidden" name="rider_id" value="<?php echo $edit_rider['id']; ?>">
            <div class="gov-form-group">
                <label>Current Score</label>
                <input type="text" class="gov-form-control" disabled value="<?php echo $edit_rider['safety_score']; ?> / 100 — <?php echo strtoupper($edit_rider['status']); ?>">
            </div>
            <div class="gov-form-group">
                <label for="new_score">New Score (0-100)</label>
                <input type="number" id="new_score" name="new_score" class="gov-form-control"
                       min="0" max="100" value="<?php echo $edit_rider['safety_score']; ?>" required>
            </div>
            <div class="gov-form-group">
                <label for="reason">Reason for Adjustment (required)</label>
                <textarea id="reason" name="reason" class="gov-form-control" required
                          placeholder="Document the reason for this score adjustment..."></textarea>
            </div>
            
            <div style="margin-top:var(--sp-5); padding-top:var(--sp-4); border-top:1px solid var(--navy-lighter);">
                <div class="gov-form-group">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="is_insured" value="1" <?php echo $edit_rider['is_insured'] ? 'checked' : ''; ?>>
                        Rider claims to be insured
                    </label>
                </div>
                <div class="gov-form-group">
                    <label>Insurance Provider</label>
                    <input type="text" name="insurance_provider" class="gov-form-control" value="<?php echo sanitize($edit_rider['insurance_provider'] ?? ''); ?>">
                </div>
                <div class="gov-form-group">
                    <label>Policy Number</label>
                    <input type="text" name="insurance_policy_number" class="gov-form-control" value="<?php echo sanitize($edit_rider['insurance_policy_number'] ?? ''); ?>">
                </div>
                <div class="gov-form-group">
                    <label>Expiry Date</label>
                    <input type="date" name="insurance_expiry_date" class="gov-form-control" value="<?php echo sanitize($edit_rider['insurance_expiry_date'] ?? ''); ?>">
                </div>
            </div>

            <button type="submit" class="gov-btn gov-btn-primary">Save Changes</button>
        </form>
    </div>
<?php endif; ?>

<!-- Riders table -->
<div class="gov-card">
    <div class="gov-card-header">
        <div>
            <div class="gov-card-title">Registered Riders</div>
            <div class="gov-card-subtitle"><?php echo number_format(count($riders)); ?> records found</div>
        </div>
    </div>
    <div class="gov-table-wrap">
        <table class="gov-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Plate</th>
                    <th>SACCO</th>
                    <th>Score</th>
                    <th>Status</th>
                    <th>Compliance</th>
                    <th>Paid</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riders)): ?>
                    <tr><td colspan="9" style="text-align:center; color:#94a3b8;">No riders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($riders as $r): ?>
                        <tr>
                            <td style="font-weight:500;"><?php echo sanitize($r['full_name']); ?></td>
                            <td style="font-family:var(--font-mono); font-size:0.8rem;"><?php echo sanitize($r['phone_number']); ?></td>
                            <td style="font-family:var(--font-mono); font-size:0.8rem;"><?php echo sanitize($r['bike_plate']); ?></td>
                            <td><?php echo sanitize($r['sacco_name']); ?></td>
                            <td style="font-weight:700;" class="score-<?php echo $r['status']; ?>"><?php echo $r['safety_score']; ?></td>
                            <td><?php echo statusBadge($r['status']); ?></td>
                            <td style="font-size:0.78rem; color:#64748b;">
                                <?php
                                    $items = [];
                                    if ($r['has_helmet']) $items[] = 'H';
                                    if ($r['has_licence']) $items[] = 'L';
                                    if ($r['has_psv_permit']) $items[] = 'P';
                                    if ($r['has_logbook']) $items[] = 'B';
                                    if (isRiderInsured($r)) $items[] = 'I';
                                    echo empty($items) ? '<span style="color:var(--red);">None</span>' : implode(' ', $items);
                                ?>
                            </td>
                            <td>
                                <span style="font-size:0.78rem; font-weight:500; color:<?php echo $r['payment_status'] === 'paid' ? 'var(--green)' : 'var(--red)'; ?>;">
                                    <?php echo ucfirst($r['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo $base; ?>/admin/riders.php?edit=<?php echo $r['id']; ?>"
                                   class="gov-btn gov-btn-sm">Adjust</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
