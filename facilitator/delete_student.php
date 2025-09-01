<?php
session_start();
include '../config.php';

// ✅ Restrict access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'facilitator') {
    header("Location: facilitator.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: record_session.php");
    exit();
}

$id = intval($_GET['id']);

$sql = "DELETE FROM student WHERE id=$id";
if (mysqli_query($conn, $sql)) {
    header("Location: record_session.php?deleted=1");
    exit();
} else {
    echo "Error deleting student: " . mysqli_error($conn);
}
mysqli_close($conn);
?>