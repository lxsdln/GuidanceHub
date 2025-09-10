<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && strtotime($user['reset_expires']) > time()) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user['id']);
        $stmt->execute();
        echo "Password reset successfully. <a href='login.php'>Login</a>";
    } else {
        echo "Invalid or expired token.";
    }
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    echo "<form method='POST'>
            <input type='hidden' name='token' value='$token'>
            <input type='password' name='password' placeholder='New Password' required>
            <button type='submit'>Reset Password</button>
          </form>";
}
?>
