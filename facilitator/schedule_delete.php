<?php
include '../config.php';

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

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id=? AND facilitator_id=?");
    $stmt->bind_param("ii", $id, $facilitator_id);
    $stmt->execute();
}

header("Location: schedule.php");
exit();
