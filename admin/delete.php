<?php
// session_start();
// if ($_SESSION['role'] !== 'admin') {
//     header("Location: login.php");
//     exit();  // stop further code execution
// }
include '../config.php';

$id = $_GET['id'];
$conn->query("DELETE FROM users WHERE id=$id");
header("Location: user_management.php");
?>