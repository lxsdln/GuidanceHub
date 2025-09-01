<?php
session_start();
include '../config.php';

// Check if id is passed
if (!isset($_GET['id'])) {
    header("Location: college.php");
    exit();
}

$college_id = $_GET['id'];

// First delete facilitator(s) linked to this college
$stmt1 = $conn->prepare("DELETE FROM facilitator WHERE college_id = ?");
$stmt1->bind_param("s", $college_id);
$stmt1->execute();

// Then delete the college
$stmt2 = $conn->prepare("DELETE FROM college WHERE college_id = ?");
$stmt2->bind_param("s", $college_id);
$stmt2->execute();

// Redirect back
header("Location: college.php");
exit();
