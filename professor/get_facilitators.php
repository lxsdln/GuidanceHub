<?php
include '../config.php';

header('Content-Type: application/json');

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor'){
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$college_id = $_GET['college_id'] ?? '';

if(!$college_id){
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT facilitator_id, first_name, last_name FROM facilitator WHERE college_id = ? AND status = 1 ORDER BY first_name");
$stmt->bind_param("s", $college_id);
$stmt->execute();
$res = $stmt->get_result();
$facilitators = [];
while($row = $res->fetch_assoc()){
    $facilitators[] = $row;
}
echo json_encode($facilitators);
