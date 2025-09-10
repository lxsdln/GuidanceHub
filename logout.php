<?php
include 'config.php';

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];

    // ❌ Invalidate token in DB
    $stmt = $conn->prepare("UPDATE users SET remember_token=NULL, remember_expire=NULL WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
}

session_destroy();

// ❌ Delete cookie
setcookie("remember_me", "", time() - 3600, "/");

header("Location: login.php");
exit;
?>