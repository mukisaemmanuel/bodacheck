<?php
/**
 * Admin Officers Page — Government Personnel Management
 * -----------------------------------------------------
 * Create new officer accounts and manage existing ones.
 * Part of the traffic police enforcement infrastructure.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/admin_header.php';

$error = '';
$success = '';

// Create officer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_officer'])) {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $badge    = sanitize($_POST['badge_number'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email, and password are required.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'This email is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password, role, badge_number) VALUES (?, ?, ?, "officer", ?)'
            );
            $stmt->execute([$name, $email, $hashed, $badge]);
            $success = 'Officer account created: ' . $name . ' (' . $badge . ')';
        }
    }
}

// Deactivate officer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_officer'])) {
    $officer_id = (int)($_POST['officer_id'] ?? 0);
    if ($officer_id > 0) {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND role = "officer"');
        $stmt->execute([$officer_id]);
        $success = 'Officer account deactivated.';
    }
}

// Reactivate officer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reactivate_officer'])) {
    $officer_id = (int)($_POST['officer_id'] ?? 0);
    if ($officer_id > 0) {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ? AND role = "officer"');
        $stmt->execute([$officer_id]);
        $success = 'Officer account reactivated.';
    }
}

// Fetch all officers with their violation counts
$stmt = $pdo->query(
    'SELECT u.*, COUNT(v.id) AS violation_count
     FROM users u
     LEFT JOIN violations v ON v.officer_id = u.id
     WHERE u.role = "officer"
     GROUP BY u.id
     ORDER BY u.created_at DESC'
);
$officers = $stmt->fetchAll();

$active_count = count(array_filter($officers, fn($o) => $o['is_active']));
$inactive_count = count($officers) - $active_count;
?>

<div class="admin-page-title">Traffic Officer Personnel</div>
<div class="admin-page-subtitle">Manage officer accounts for the enforcement infrastructure — <?php echo $active_count; ?> active, <?php echo $inactive_count; ?> deactivated</div>

<?php if ($error): ?>
    <div class="gov-alert gov-alert-error"><?php echo $error; ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="gov-alert gov-alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- Create officer form -->
<div class="gov-card" style="max-width:600px;">
    <div class="gov-card-header">
        <div class="gov-card-title">Create New Officer Account</div>
        <div class="gov-card-subtitle">Add a new traffic officer to the enforcement system</div>
    </div>
    <form method="POST" action="">
        <input type="hidden" name="create_officer" value="1">
        <div class="officer-form-grid">
            <div class="gov-form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="gov-form-control" required>
            </div>
            <div class="gov-form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="gov-form-control" required>
            </div>
            <div class="gov-form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="gov-form-control" required>
            </div>
            <div class="gov-form-group">
                <label for="badge_number">Badge Number</label>
                <input type="text" id="badge_number" name="badge_number" class="gov-form-control" placeholder="e.g. KLA-002">
            </div>
        </div>
        <button type="submit" class="gov-btn gov-btn-primary mt-4">Create Officer</button>
    </form>
</div>

<!-- Officers table -->
<div class="gov-card mt-6">
    <div class="gov-card-header">
        <div class="gov-card-title">Officer Roster</div>
        <div class="gov-card-subtitle"><?php echo count($officers); ?> officers on record</div>
    </div>
    <div class="gov-table-wrap">
        <table class="gov-table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Badge</th><th>Status</th><th>Violations Logged</th><th>Created</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if (empty($officers)): ?>
                    <tr><td colspan="7" style="text-align:center; color:#94a3b8;">No officers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($officers as $o): ?>
                        <tr>
                            <td style="font-weight:500;"><?php echo sanitize($o['name']); ?></td>
                            <td style="font-size:0.85rem;"><?php echo sanitize($o['email']); ?></td>
                            <td style="font-family:var(--font-mono); font-size:0.8rem;"><?php echo sanitize($o['badge_number']); ?></td>
                            <td>
                                <?php if ($o['is_active']): ?>
                                    <span style="color:var(--green); font-weight:500; font-size:0.85rem;">Active</span>
                                <?php else: ?>
                                    <span style="color:var(--red); font-weight:500; font-size:0.85rem;">Deactivated</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $o['violation_count']; ?></td>
                            <td style="font-size:0.85rem;"><?php echo date('d M Y', strtotime($o['created_at'])); ?></td>
                            <td>
                                <?php if ($o['is_active']): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="officer_id" value="<?php echo $o['id']; ?>">
                                        <button type="submit" name="deactivate_officer" class="gov-btn gov-btn-sm gov-btn-danger"
                                                onclick="return confirm('Deactivate this officer account?')">Deactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="officer_id" value="<?php echo $o['id']; ?>">
                                        <button type="submit" name="reactivate_officer" class="gov-btn gov-btn-sm gov-btn-success">Reactivate</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
