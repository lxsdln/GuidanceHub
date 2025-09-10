<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$password = "123456";
$dbname = "guidancehub";

$conn = mysqli_connect($host, $user, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ✅ Auto-login via remember me
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    list($uid, $token) = explode(':', $_COOKIE['remember_me']);

    $stmt = $conn->prepare("SELECT id, username, email, role, remember_token, remember_expire 
                            FROM users WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $user['remember_token'] && strtotime($user['remember_expire']) > time()) {
        if (hash_equals($user['remember_token'], hash('sha256', $token))) {
            // ✅ Restore session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
        }
    }
}
?>
