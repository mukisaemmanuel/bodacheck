<?php
/**
 * Admin Violations Page — Government Enforcement Log
 * ---------------------------------------------------
 * Full enforcement log with filters by date and type.
 * Every violation is recorded with officer ID and timestamp.
 * In 2024, 32,308 riders were arrested for no helmet —
 * BodaCheck makes that history visible and permanent.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

$filter_type = $_GET['type'] ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_officer = $_GET['officer'] ?? '';

$where = [];
$params = [];

if (!empty($filter_type)) {
    $where[] = 'v.violation_type = ?';
    $params[] = $filter_type;
}
if (!empty($filter_date)) {
    $where[] = 'DATE(v.created_at) = ?';
    $params[] = $filter_date;
}
if (!empty($filter_officer)) {
    $where[] = 'u.name LIKE ?';
    $params[] = '%' . $filter_officer . '%';
}

$where_sql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT v.*, r.full_name AS rider_name, r.bike_plate, r.safety_score AS rider_score, r.status AS rider_status,
            u.name AS officer_name, u.badge_number
     FROM violations v
     JOIN riders r ON v.rider_id = r.id
     JOIN users u ON v.officer_id = u.id
     $where_sql
     ORDER BY v.created_at DESC"
);
$stmt->execute($params);
$violations = $stmt->fetchAll();

$violation_types = getViolationTypes();

// Summary stats for filtered results
$total_points = array_sum(array_column($violations, 'points_deducted'));
$unique_riders = count(array_unique(array_column($violations, 'rider_id')));
$unique_officers = count(array_unique(array_column($violations, 'officer_id')));
?>

<div class="admin-page-title">Enforcement Log</div>
<div class="admin-page-subtitle">Complete violation history with digital paper trail — every scan logged with timestamp and officer ID</div>

<!-- Filter bar -->
<div class="gov-card" style="margin-bottom:var(--sp-5);">
    <div class="gov-card-header">
        <div class="gov-card-title">Filter Violations</div>
        <div class="gov-card-subtitle"><?php echo number_format(count($violations)); ?> records &middot; <?php echo $total_points; ?> total points deducted &middot; <?php echo $unique_riders; ?> riders &middot; <?php echo $unique_officers; ?> officers</div>
    </div>
    <form method="GET" action="">
        <div class="gov-filter-bar">
            <div class="gov-form-group" style="min-width:150px;">
                <label>Violation Type</label>
                <select name="type" class="gov-form-control">
                    <option value="">All Types</option>
                    <?php foreach ($violation_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="gov-form-group" style="min-width:150px;">
                <label>Date</label>
                <input type="date" name="date" class="gov-form-control" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <div class="gov-form-group" style="min-width:150px;">
                <label>Officer Name</label>
                <input type="text" name="officer" class="gov-form-control" placeholder="Search officer..."
                       value="<?php echo htmlspecialchars($filter_officer); ?>">
            </div>
            <div style="display:flex; gap:8px; align-items:flex-end; padding-bottom:1px;">
                <button type="submit" class="gov-btn gov-btn-primary">Filter</button>
                <a href="<?php echo $base; ?>/admin/violations.php" class="gov-btn">Clear</a>
            </div>
        </div>
    </form>
</div>

<!-- Violations table -->
<div class="gov-card">
    <div class="gov-table-wrap">
        <table class="gov-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Rider</th>
                    <th>Plate</th>
                    <th>Score</th>
                    <th>Type</th>
                    <th>Points</th>
                    <th>Location</th>
                    <th>Officer</th>
                    <th>Badge</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($violations)): ?>
                    <tr><td colspan="10" style="text-align:center; color:#94a3b8;">No violations found matching your filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($violations as $v): ?>
                        <tr>
                            <td style="white-space:nowrap;"><?php echo date('d M Y H:i', strtotime($v['created_at'])); ?></td>
                            <td style="font-weight:500;"><?php echo sanitize($v['rider_name']); ?></td>
                            <td style="font-family:var(--font-mono); font-size:0.8rem;"><?php echo sanitize($v['bike_plate']); ?></td>
                            <td class="score-<?php echo $v['rider_status']; ?>" style="font-weight:600;"><?php echo $v['rider_score']; ?></td>
                            <td><?php echo sanitize($v['violation_type']); ?></td>
                            <td style="color:var(--red); font-weight:700;">-<?php echo $v['points_deducted']; ?></td>
                            <td><?php echo sanitize($v['location']); ?></td>
                            <td><?php echo sanitize($v['officer_name']); ?></td>
                            <td style="font-family:var(--font-mono); font-size:0.8rem;"><?php echo sanitize($v['badge_number']); ?></td>
                            <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                                title="<?php echo sanitize($v['notes'] ?? ''); ?>">
                                <?php echo sanitize($v['notes'] ?? '—'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
