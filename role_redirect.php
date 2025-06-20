<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $role = $_POST['role'];

    // Validate role
    if ($role === 'student' || $role === 'professor') {
        $_SESSION['role'] = $role;

        // Redirect to respective dashboard
        if ($role === 'student') {
            header("Location: ./student/dashboard.php");
        } else {
            header("Location: ./professor/dashboard.php");
        }
        exit();
    } else {
        echo "Invalid role selected.";
    }
} else {
    echo "No role selected.";
}
?>
