<?php
include '../config.php';

header('Content-Type: application/json');

// ✅ Only professor access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$facilitator_id = $_GET['facilitator_id'] ?? '';
if (!$facilitator_id) {
    echo json_encode([]);
    exit;
}

// ✅ Exclude Sunday schedules directly from DB
$stmt = $conn->prepare("
    SELECT id, available_day, start_time, end_time
    FROM schedules
    WHERE facilitator_id = ?
      AND available_day <> 'Sunday'
    ORDER BY FIELD(available_day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')
");
$stmt->bind_param("s", $facilitator_id);
$stmt->execute();
$res = $stmt->get_result();

$schedules = [];
while ($row = $res->fetch_assoc()) {
    $schedules[] = [
        'id' => $row['id'],
        'available_day' => $row['available_day'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time']
    ];
}

echo json_encode($schedules);
$stmt->close();
$conn->close();
exit;
?>
