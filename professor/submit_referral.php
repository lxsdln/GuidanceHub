<?php
require '../helper/send_mail.php';
ini_set('display_errors',1); 
error_reporting(E_ALL);
include '../config.php';

if(!isset($_SESSION['role']) || $_SESSION['role']!=='professor'){
    echo json_encode(["status"=>"error","message"=>"Unauthorized"]);
    exit;
}

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);
if(!$data){
    echo json_encode(["status"=>"error","message"=>"Invalid input"]);
    exit;
}

$professor_id     = $_SESSION['user_id'] ?? '';
$student_id       = $data['student_id'] ?? '';
$college_id       = $data['college_id'] ?? '';
$facilitator_id   = $data['facilitator_id'] ?? '';
$reason           = $data['reason'] ?? '';
$appointment_date = $data['appointment_date'] ?? '';
$appointment_time = $data['appointment_time'] ?? '';
$student_first    = $data['student_first'] ?? '';
$student_m        = $data['student_m'] ?? '';
$student_last     = $data['student_last'] ?? '';
$student_course   = $data['student_course'] ?? '';
$student_email    = $data['student_email'] ?? '';

// --- Step 1: Check if student exists ---
$stmt = $conn->prepare("SELECT id FROM student WHERE student_id=?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$existing = $res->fetch_assoc();
$stmt->close();

if(!$existing){
    // Insert new student (no linked user_id yet)
    $stmt = $conn->prepare("INSERT INTO student (student_id, first_name, m_name, last_name, course, college_id, user_id) VALUES (?, ?, ?, ?, ?, ?, NULL)");
    $stmt->bind_param("ssssss", $student_id, $student_first, $student_m, $student_last, $student_course, $college_id);
    $stmt->execute();
    $student_db_id = $stmt->insert_id;
    $stmt->close();
}else{
    $student_db_id = $existing['id'];
}

// --- Step 2: Validate if slot is already booked ---
$stmt = $conn->prepare("SELECT id FROM appointments WHERE facilitator_id=? AND appointment_date=? AND appointment_time=?");
$stmt->bind_param("sss", $facilitator_id, $appointment_date, $appointment_time);
$stmt->execute();
$res = $stmt->get_result();
if($res->fetch_assoc()){
    echo json_encode(["status"=>"error", "message"=>"This slot is already booked. Please choose another time."]);
    exit;
}
$stmt->close();

// --- Step 3: Validate slot availability against schedules ---
$stmt = $conn->prepare("SELECT available_day, start_time, end_time FROM schedules WHERE facilitator_id=?");
$stmt->bind_param("s",$facilitator_id);
$stmt->execute();
$res = $stmt->get_result();
$schedules = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$validSlot = false;
$dayName = date('l', strtotime($appointment_date));
foreach($schedules as $s){
    $start = strtotime($appointment_date.' '.$s['start_time']);
    $end = strtotime($appointment_date.' '.$s['end_time']);
    $chosen = strtotime($appointment_date.' '.$appointment_time);
    if($s['available_day']===$dayName && $chosen >= $start && $chosen <= $end){
        $validSlot = true;
        break;
    }
}

if(!$validSlot){
    echo json_encode(["status"=>"error","message"=>"Chosen date/time is not available for this facilitator."]);
    exit;
}

// --- Step 4: Insert appointment ---
$stmt = $conn->prepare("INSERT INTO appointments (student_id, facilitator_id, appointment_date, appointment_time, purpose) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $student_id, $facilitator_id, $appointment_date, $appointment_time, $reason);
$stmt->execute();
$appointment_id = $stmt->insert_id;
$stmt->close();

// --- Step 5: Insert referral and link with appointment_id ---
$stmt = $conn->prepare("INSERT INTO referrals (referred_by_id, referred_by_role, student_id, reason, student_email, appointment_id) VALUES (?, 'professor', ?, ?, ?, ?)");
$stmt->bind_param("ssssi", $professor_id, $student_id, $reason, $student_email, $appointment_id);
$stmt->execute();
$referral_id = $stmt->insert_id;
$stmt->close();

// --- Step 6: Fetch professor info (from users table) ---
$stmt = $conn->prepare("
    SELECT u.email, p.first_name, p.m_name, p.last_name
    FROM professor p
    INNER JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ?
");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Step 7: Fetch facilitator info (for student email content) ---
$stmt = $conn->prepare("SELECT first_name, m_name, last_name FROM facilitator WHERE facilitator_id=?");
$stmt->bind_param("s", $facilitator_id);
$stmt->execute();
$facilitator = $stmt->get_result()->fetch_assoc();
$stmt->close();

$facilitator_name = (!empty($facilitator['first_name']) && !empty($facilitator['last_name']))
    ? $facilitator['first_name'].' '.$facilitator['m_name'].' '.$facilitator['last_name']
    : "Facilitator";

// --- Step 8: Send email to professor ---
if (!empty($professor['email'])) {
    $to = $professor['email'];
    $subject = "New Student Referral & Appointment Request";
    $body = "<p>Dear {$professor['first_name']} {$professor['m_name']} {$professor['last_name']},</p>
             <p>You have successfully referred a student and an appointment has been scheduled:</p>
             <ul>
                <li><strong>Student:</strong> $student_first $student_m $student_last ($student_id)</li>
                <li><strong>Course:</strong> $student_course</li>
                <li><strong>Reason:</strong> $reason</li>
                <li><strong>Appointment Date:</strong> $appointment_date</li>
                <li><strong>Appointment Time:</strong> $appointment_time</li>
             </ul>
             <p>Please check the GuidanceHub system for more details.</p>";
    sendMail($to, $subject, $body);
}

// --- Step 9: Send email to student ---
if (!empty($student_email)) {
    $to = $student_email;
    $subject = "Appointment Request";
    $body = "<p>Dear $student_first $student_last,</p>
             <p>Your appointment has been scheduled with $facilitator_name:</p>
             <ul>
                <li><strong>Date:</strong> $appointment_date</li>
                <li><strong>Time:</strong> $appointment_time</li>
                <li><strong>Reason:</strong> $reason</li>
             </ul>
             <p>Please arrive on time and contact the facilitator if you need to reschedule.</p>";
    sendMail($to, $subject, $body);
}

// --- Final Response ---
echo json_encode([
    "status"=>"success",
    "referral_id"=>$referral_id,
    "appointment_id"=>$appointment_id
]);
