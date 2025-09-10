<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $otp     = $_POST['otp'];

    // Fetch the OTP and expiry for this user
    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($db_otp, $otp_expiry);
    $stmt->fetch();

    if ($stmt->num_rows == 0) {
        die("❌ Invalid user.");
    }

    $current_time = date("Y-m-d H:i:s");

    if ($otp != $db_otp) {
        $error = "❌ Invalid OTP.";
    } elseif ($current_time > $otp_expiry) {
        $error = "❌ OTP expired. Please resend OTP.";
    } else {
        // ✅ Mark user as verified
        $stmt = $conn->prepare("UPDATE users SET is_verified=1, otp=NULL, otp_expiry=NULL WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // ✅ Redirect to login page
        header("Location: login.php?verified=1");
        exit();
    }
}
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
