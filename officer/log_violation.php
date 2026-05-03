<?php
/**
 * Officer Log Violation Page — officer/log_violation.php
 * ------------------------------------------------------
 * Officers select a violation type, enter a location, add optional notes,
 * and submit. The rider's score is updated automatically.
 * Every violation is recorded with the officer's ID and a timestamp.
 * In 2024, 32,308 riders were arrested for no helmet — BodaCheck
 * makes that history visible and permanent.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('officer');
$base = baseUrl();
$officer_id = $_SESSION['user_id'];

$rider_id = (int)($_GET['rider_id'] ?? 0);
if ($rider_id <= 0) {
    header('Location: ' . $base . '/officer/scan.php');
    exit;
}

// Fetch the rider
$stmt = $pdo->prepare(
    'SELECT r.*, s.name AS sacco_name
     FROM riders r
     JOIN saccos s ON r.sacco_id = s.id
     WHERE r.id = ?'
);
$stmt->execute([$rider_id]);
$rider = $stmt->fetch();

if (!$rider) {
    header('Location: ' . $base . '/officer/scan.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $violation_type = sanitize($_POST['violation_type'] ?? '');
    $location       = sanitize($_POST['location'] ?? '');
    $notes          = sanitize($_POST['notes'] ?? '');

    if (empty($violation_type)) {
        $error = 'Please select a violation type.';
    } else {
        // Calculate points to deduct
        $points = getPointsForViolation($violation_type);

        // Insert the violation record
        $stmt = $pdo->prepare(
            'INSERT INTO violations (rider_id, officer_id, violation_type, points_deducted, location, notes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$rider_id, $officer_id, $violation_type, $points, $location, $notes]);

        // Update the rider's score and status
        updateRiderScore($pdo, $rider_id, $points);

        // Refresh rider data
        $stmt = $pdo->prepare('SELECT * FROM riders WHERE id = ?');
        $stmt->execute([$rider_id]);
        $rider = $stmt->fetch();

        $success = 'Violation logged. ' . $points . ' points deducted. New score: ' . $rider['safety_score'] . ' (' . strtoupper($rider['status']) . ')';
    }
}

$violation_types = getViolationTypes();

require_once __DIR__ . '/../includes/header.php';
?>

<h1 style="margin-bottom:var(--sp-5);">Log Violation</h1>

<div class="card mb-5" style="max-width:600px;">
    <div class="flex-between">
        <div>
            <strong><?php echo sanitize($rider['full_name']); ?></strong><br>
            <span style="color:var(--grey-light);"><?php echo sanitize($rider['bike_plate']); ?> &middot; <?php echo sanitize($rider['sacco_name']); ?></span>
        </div>
        <div>
            <?php echo statusBadge($rider['status']); ?>
            <div style="font-size:1.5rem; font-weight:700; text-align:center;" class="score-<?php echo $rider['status']; ?>">
                <?php echo $rider['safety_score']; ?>
            </div>
        </div>
    </div>
</div>

<div style="max-width:600px;">
    <div class="card">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="violation_type">Violation Type</label>
                <select id="violation_type" name="violation_type" class="form-control" required>
                    <option value="">— Select violation type —</option>
                    <?php foreach ($violation_types as $type): ?>
                        <option value="<?php echo $type; ?>">
                            <?php echo $type; ?> (-<?php echo getPointsForViolation($type); ?> points)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" class="form-control"
                       placeholder="e.g. Kampala Road, Clock Tower"
                       value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="notes">Notes (optional)</label>
                <textarea id="notes" name="notes" class="form-control"
                          placeholder="Any additional details..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-danger" style="width:100%;">Log Violation</button>
        </form>

        <div class="mt-4">
            <a href="<?php echo $base; ?>/officer/scan.php">Back to Scan</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
