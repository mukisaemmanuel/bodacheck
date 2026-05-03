<?php
/**
 * Rider Edit Profile — rider/edit_profile.php
 * --------------------------------------------
 * Riders can update their name, insurance status, and compliance items.
 * QR token, national ID, and bike plate cannot be changed here.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('rider');
$base = baseUrl();
$rider_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM riders WHERE id = ?');
$stmt->execute([$rider_id]);
$rider = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name     = sanitize($_POST['full_name'] ?? '');
    $is_insured    = isset($_POST['is_insured']) ? 1 : 0;
    $has_helmet    = isset($_POST['has_helmet']) ? 1 : 0;
    $has_licence   = isset($_POST['has_licence']) ? 1 : 0;
    $has_psv_permit = isset($_POST['has_psv_permit']) ? 1 : 0;
    $has_logbook   = isset($_POST['has_logbook']) ? 1 : 0;

    if (empty($full_name)) {
        $error = 'Full name is required.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE riders SET full_name = ?, is_insured = ?, has_helmet = ?, has_licence = ?, has_psv_permit = ?, has_logbook = ? WHERE id = ?'
        );
        $stmt->execute([$full_name, $is_insured, $has_helmet, $has_licence, $has_psv_permit, $has_logbook, $rider_id]);
        $_SESSION['user_name'] = $full_name;
        $success = 'Profile updated successfully.';

        $stmt = $pdo->prepare('SELECT * FROM riders WHERE id = ?');
        $stmt->execute([$rider_id]);
        $rider = $stmt->fetch();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 style="margin-bottom:var(--sp-5);">Edit My Profile</h1>

<div class="auth-container" style="max-width:500px;">
    <div class="card">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control"
                       value="<?php echo sanitize($rider['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" class="form-control" disabled value="<?php echo sanitize($rider['phone_number']); ?>">
                <small style="color:var(--grey-dark);">Cannot be changed. Contact admin.</small>
            </div>

            <div class="form-group">
                <label>National ID</label>
                <input type="text" class="form-control" disabled value="<?php echo sanitize($rider['national_id']); ?>">
            </div>

            <div class="form-group">
                <label>Bike Plate</label>
                <input type="text" class="form-control" disabled value="<?php echo sanitize($rider['bike_plate']); ?>">
            </div>

            <div class="form-group">
                <label>QR Token</label>
                <input type="text" class="form-control" disabled value="<?php echo sanitize($rider['qr_token']); ?>"
                       style="font-family:var(--font-mono); font-size:0.8rem;">
                <small style="color:var(--grey-dark);">This is permanent and cannot be changed.</small>
            </div>

            <div class="form-group">
                <label style="font-size:1rem; font-weight:600; color:var(--white); display:block; margin-bottom:var(--sp-3);">Compliance Items</label>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:var(--sp-2);">
                    <input type="checkbox" name="has_helmet" value="1" <?php echo $rider['has_helmet'] ? 'checked' : ''; ?>>
                    I have a helmet
                </label>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:var(--sp-2);">
                    <input type="checkbox" name="has_licence" value="1" <?php echo $rider['has_licence'] ? 'checked' : ''; ?>>
                    I have a driving licence
                </label>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:var(--sp-2);">
                    <input type="checkbox" name="has_psv_permit" value="1" <?php echo $rider['has_psv_permit'] ? 'checked' : ''; ?>>
                    I have a PSV permit
                </label>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:var(--sp-2);">
                    <input type="checkbox" name="has_logbook" value="1" <?php echo $rider['has_logbook'] ? 'checked' : ''; ?>>
                    I have a logbook
                </label>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_insured" value="1" <?php echo $rider['is_insured'] ? 'checked' : ''; ?>>
                    I am insured
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">Save Changes</button>
        </form>

        <div class="mt-4">
            <a href="<?php echo $base; ?>/rider/dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
