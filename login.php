<?php
/**
 * Login Page — login.php
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
$base = baseUrl();

// Redirect if already logged in
if (isLoggedIn()) {
    if (isRider()) header('Location: ' . $base . '/rider/dashboard.php');
    elseif (isOfficer()) header('Location: ' . $base . '/officer/scan.php');
    elseif (isAdmin()) header('Location: ' . $base . '/admin/dashboard.php');
    exit;
}

$error = '';
$active_tab = 'rider';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? 'rider';

    if ($login_type === 'rider') {
        // ---- Rider login: phone + password ----
        $active_tab = 'rider';
        $phone    = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($phone) || empty($password)) {
            $error = 'Phone number and password are required.';
        } else {
            $stmt = $pdo->prepare('SELECT id, full_name, phone_number, password FROM riders WHERE phone_number = ?');
            $stmt->execute([$phone]);
            $rider = $stmt->fetch();

            if ($rider && password_verify($password, $rider['password'])) {
                $_SESSION['user_id']   = $rider['id'];
                $_SESSION['user_name'] = $rider['full_name'];
                $_SESSION['role']      = 'rider';
                header('Location: ' . $base . '/rider/dashboard.php');
                exit;
            } else {
                $error = 'Invalid phone number or password.';
            }
        }
    } else {
        // ---- Officer/Admin login: email + password ----
        $active_tab = 'staff';
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } else {
            $stmt = $pdo->prepare('SELECT id, name, email, password, role, is_active FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $user['is_active'] && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role']      = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: ' . $base . '/admin/dashboard.php');
                } else {
                    header('Location: ' . $base . '/officer/scan.php');
                }
                exit;
            } else {
                $error = 'Invalid email or password, or account is deactivated.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="card">
        <h2 class="auth-title">Login to BodaCheck</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn <?php echo $active_tab === 'rider' ? 'active' : ''; ?>"
                    onclick="switchTab('rider')">Rider Login</button>
            <button class="tab-btn <?php echo $active_tab === 'staff' ? 'active' : ''; ?>"
                    onclick="switchTab('staff')">Officer / Admin</button>
        </div>

        <!-- Rider login form -->
        <div id="tab-rider" class="tab-content <?php echo $active_tab === 'rider' ? 'active' : ''; ?>">
            <form method="POST" action="">
                <input type="hidden" name="login_type" value="rider">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                           placeholder="+2567XXXXXXXX" required
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="rider-pass">Password</label>
                    <input type="password" id="rider-pass" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
            </form>
            <p class="text-center mt-4">
                Not registered? <a href="<?php echo $base; ?>/register.php">Register here</a>
            </p>
        </div>

        <!-- Officer/Admin login form -->
        <div id="tab-staff" class="tab-content <?php echo $active_tab === 'staff' ? 'active' : ''; ?>">
            <form method="POST" action="">
                <input type="hidden" name="login_type" value="staff">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="officer@bodacheck.ug" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="staff-pass">Password</label>
                    <input type="password" id="staff-pass" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(function(el) { el.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(el) { el.classList.remove('active'); });
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
