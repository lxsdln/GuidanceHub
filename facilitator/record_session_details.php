<?php
ini_set('display_errors',1); 
error_reporting(E_ALL);
include '../config.php';
use Dompdf\Dompdf;
require __DIR__ . '/../vendor/autoload.php';

// --- Check facilitator login ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'facilitator') die("Unauthorized.");

// --- Get facilitator_id ---
$facilitator_id = $_SESSION['facilitator_id'] ?? null;
if (!$facilitator_id) {
    $stmtFac = $conn->prepare("SELECT facilitator_id FROM facilitator WHERE user_id=?");
    $stmtFac->bind_param("i", $_SESSION['user_id']);
    $stmtFac->execute();
    $facilitator_id = $stmtFac->get_result()->fetch_assoc()['facilitator_id'] ?? null;
    if (!$facilitator_id) die("Facilitator ID not found.");
}

// --- Get appointment or referral ID ---
$appointment_id = intval($_GET['id'] ?? 0);
if (!$appointment_id) die("Appointment not specified.");

// --- Fetch appointment or professor referral info ---
$stmt = $conn->prepare("
    SELECT 
        a.id AS appointment_id, 
        a.student_id, 
        a.appointment_date, 
        a.appointment_time, 
        a.duration_minutes, 
        a.purpose,
        s.first_name, s.m_name, s.last_name, s.course, 
        u.email AS user_email,
        r.id AS referral_id, r.referred_by_role, r.reason AS referral_reason, r.student_email
    FROM appointments a
    LEFT JOIN student s ON a.student_id = s.student_id
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN referrals r ON r.appointment_id = a.id
    WHERE a.id=? AND a.facilitator_id=?
    LIMIT 1
");
$stmt->bind_param("ii", $appointment_id, $facilitator_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();

// ✅ fallback values
$studentName   = trim(($appt['first_name'] ?? '') . ' ' . ($appt['m_name'] ?? '') . ' ' . ($appt['last_name'] ?? ''));
$studentEmail  = $appt['user_email'] ?? $appt['student_email'] ?? '';   // <-- FIXED HERE
$studentCourse = $appt['course'] ?? '';
$purpose       = $appt['purpose'] ?? $appt['referral_reason'] ?? '';



// --- Fetch existing notes ---
$stmt = $conn->prepare("SELECT notes FROM consultation_notes WHERE appointment_id=?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc()['notes'] ?? '';

// --- Handle saving notes & PDF ---
$success = '';
$pdf_path = "uploads/notes/consultation_{$appointment_id}.pdf";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notes'])) {
    $notes = trim($_POST['notes']);
    $student_fullname = "{$appt['first_name']} {$appt['m_name']} {$appt['last_name']}";

    // --- Generate PDF ---
    $dompdf = new Dompdf();
    $html = "
    <h2>Consultation Notes</h2>
    <p><strong>Student:</strong> {$student_fullname}</p>
    <p><strong>Email:</strong> {$studentEmail}</p>
    <p><strong>Course:</strong> {$studentCourse}</p>
    <p><strong>Date & Time:</strong> {$appt['appointment_date']} {$appt['appointment_time']}</p>
    <p><strong>Duration:</strong> {$appt['duration_minutes']} minutes</p>
    <p><strong>Purpose:</strong> {$purpose}</p>
    <p><strong>Notes:</strong></p><p>{$notes}</p>
    ";
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    if (!is_dir('uploads/notes')) mkdir('uploads/notes', 0777, true);
    file_put_contents($pdf_path, $dompdf->output());

    // --- Insert/update notes in DB ---
    $stmt = $conn->prepare("
        INSERT INTO consultation_notes (appointment_id, facilitator_id, student_id, notes, pdf_path)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE notes=?, pdf_path=?
    ");
    $stmt->bind_param("iiissss", $appointment_id, $facilitator_id, $appt['student_id'], $notes, $pdf_path, $notes, $pdf_path);
    $stmt->execute();

    $existing = $notes;
    $success = "Notes saved and PDF generated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Record Session Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2>Consultation Session Details</h2>
    <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>

    <!-- Student Details -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white fw-bold">Student Information</div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="row mb-2">
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Name:</strong> <?= htmlspecialchars($studentName ?: 'N/A') ?></div>
                        <div class="col-md-6"><strong>Email:</strong> <?= htmlspecialchars($studentEmail ?: 'N/A') ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Course:</strong> <?= htmlspecialchars($studentCourse ?: 'N/A') ?></div>
                        <div class="col-md-6"><strong>Date & Time:</strong> <?= htmlspecialchars(($appt['appointment_date'] ?? 'N/A') . ' ' . ($appt['appointment_time'] ?? '')) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Duration:</strong> <?= htmlspecialchars($appt['duration_minutes'] ?? '30') ?> mins</div>
                        <div class="col-md-6"><strong>Purpose:</strong> <?= htmlspecialchars($purpose ?: 'N/A') ?></div>
                    </div>
                </div>
        <!-- Notes Form -->
        <form method="POST">
            <div class="mb-3">
                <textarea name="notes" class="form-control" rows="8" placeholder="Write notes here..." required><?= htmlspecialchars($existing) ?></textarea>
            </div>
            <button type="submit" name="save_notes" class="btn btn-success">Save</button>
            <?php if(file_exists($pdf_path)): ?>
                <a href="<?= $pdf_path ?>" target="_blank" class="btn btn-primary">Download PDF</a>
            <?php endif; ?>
            <a href="record_session.php" class="btn btn-secondary">Back to Appointments</a>
        </form>        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
