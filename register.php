<?php
/**
 * Rider Registration Page — register.php
 * ---------------------------------------
 * New riders register with their details and pay the annual
 * registration fee via Mobile Money (MTN/Airtel) (UGX 10,000/year).
 * The system generates a unique QR token on registration.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
$base = baseUrl();

// Redirect if already logged in
if (isLoggedIn() && isRider()) {
    header('Location: ' . $base . '/rider/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Fetch all SACCOs for the dropdown
$stmt = $pdo->query('SELECT id, name, district FROM saccos ORDER BY name');
$saccos = $stmt->fetchAll();

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = sanitize($_POST['full_name'] ?? '');
    $phone        = sanitize($_POST['phone_number'] ?? '');
    $national_id  = sanitize($_POST['national_id'] ?? '');
    $bike_plate   = sanitize($_POST['bike_plate'] ?? '');
    $sacco_id     = (int)($_POST['sacco_id'] ?? 0);
    $momo_phone   = sanitize($_POST['momo_phone'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($full_name) || empty($phone) || empty($national_id) || empty($bike_plate) || empty($sacco_id) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_pass) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check for duplicate phone number
        $stmt = $pdo->prepare('SELECT id FROM riders WHERE phone_number = ?');
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            $error = 'This phone number is already registered.';
        } else {
            // Check for duplicate national ID
            $stmt = $pdo->prepare('SELECT id FROM riders WHERE national_id = ?');
            $stmt->execute([$national_id]);
            if ($stmt->fetch()) {
                $error = 'This national ID is already registered.';
            } else {
                // Check for duplicate bike plate
                $stmt = $pdo->prepare('SELECT id FROM riders WHERE bike_plate = ?');
                $stmt->execute([$bike_plate]);
                if ($stmt->fetch()) {
                    $error = 'This bike plate is already registered.';
                } else {
                    // Generate a unique QR token — this never changes
                    $qr_token = generateQrToken($pdo);

                    // Hash the password — never store plain text
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Use the rider's phone as MoMo phone if not provided
                    if (empty($momo_phone)) {
                        $momo_phone = $phone;
                    }

                    // Insert the new rider into the database
                    // Payment status starts as 'unpaid' — rider pays via MoMo after registration
                    $stmt = $pdo->prepare(
                        'INSERT INTO riders (full_name, phone_number, national_id, bike_plate, sacco_id, qr_token, password, momo_phone, payment_status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, "unpaid")'
                    );
                    $stmt->execute([$full_name, $phone, $national_id, $bike_plate, $sacco_id, $qr_token, $hashed_password, $momo_phone]);

                    // Record the payment as pending (rider will complete MoMo payment from dashboard)
                    $rider_id = $pdo->lastInsertId();
                    $fee = getRegistrationFee();
                    $stmt = $pdo->prepare(
                        'INSERT INTO payments (rider_id, amount, momo_phone, payment_type, status)
                         VALUES (?, ?, ?, "registration", "pending")'
                    );
                    $stmt->execute([$rider_id, $fee, $momo_phone]);

                    $success = 'Registration successful! Your QR token is: ' . $qr_token . '. Login to complete your Mobile Money payment and activate your account.';
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="card">
        <h2 class="auth-title">Register as a Rider</h2>

        <!-- Registration fee notice -->
        <div class="registration-fee-notice">
            <div class="fee-amount"><?php echo formatUGX(getRegistrationFee()); ?>/year</div>
            <div class="fee-label">Annual Registration Fee — Pay via Mobile Money after registration</div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <div class="text-center mt-4">
                <a href="<?php echo $base; ?>/login.php" class="btn btn-primary">Login Now</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" class="form-control"
                           placeholder="+2567XXXXXXXX" required
                           value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="national_id">National ID Number</label>
                    <input type="text" id="national_id" name="national_id" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['national_id'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="bike_plate">Bike Plate Number</label>
                    <input type="text" id="bike_plate" name="bike_plate" class="form-control"
                           placeholder="e.g. UAX 123A" required
                           value="<?php echo htmlspecialchars($_POST['bike_plate'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="sacco_id">SACCO</label>
                    <select id="sacco_id" name="sacco_id" class="form-control" required>
                        <option value="">— Select your SACCO —</option>
                        <?php foreach ($saccos as $sacco): ?>
                            <option value="<?php echo $sacco['id']; ?>"
                                <?php echo (isset($_POST['sacco_id']) && $_POST['sacco_id'] == $sacco['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($sacco['name'] . ' — ' . $sacco['district']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="momo_phone">Mobile Money Phone Number (for payments)</label>
                    <input type="tel" id="momo_phone" name="momo_phone" class="form-control"
                           placeholder="Same as phone if left empty"
                           value="<?php echo htmlspecialchars($_POST['momo_phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">Register</button>
            </form>

            <p class="text-center mt-4">
                Already registered? <a href="<?php echo $base; ?>/login.php">Login here</a>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
