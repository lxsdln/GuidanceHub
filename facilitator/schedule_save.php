<?php
include '../config.php';

// Ensure facilitator is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT facilitator_id FROM facilitator WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$facilitator = $res->fetch_assoc();
$facilitator_id = $facilitator['facilitator_id'];

$id = $_POST['id'] ?? null;
$available_day = $_POST['day'] ?? null;
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;

if (!$available_day || !$start_time || !$end_time) die("All fields are required!");
if ($start_time >= $end_time) die("End time must be after start time!");

if ($id) {
    $stmt = $conn->prepare("UPDATE schedules SET available_day=?, start_time=?, end_time=? WHERE id=? AND facilitator_id=?");
    $stmt->bind_param("sssii", $available_day, $start_time, $end_time, $id, $facilitator_id);
} else {
    $stmt = $conn->prepare("INSERT INTO schedules (facilitator_id, available_day, start_time, end_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $facilitator_id, $available_day, $start_time, $end_time);
}

if ($stmt->execute()) {
    header("Location: schedule.php");
    exit();
} else {
    die("Database error: " . $stmt->error);
}
?>