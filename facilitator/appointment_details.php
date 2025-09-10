<?php
include '../config.php';
include 'mailer.php';
include '../helper/send_mail.php';

// --- Validate facilitator login ---
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
if (!$user_id || $role !== 'facilitator') 
    die("Unauthorized. Please log in.");

// --- Get facilitator ID ---
$stmt = $conn->prepare("SELECT facilitator_id, first_name, last_name FROM facilitator WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$facilitator = $stmt->get_result()->fetch_assoc();
$facilitator_id = $facilitator['facilitator_id'] ?? null;
if (!$facilitator_id) die("Facilitator record not found.");

// --- Get appointment ID ---
$appointment_id = $_GET['id'] ?? null;
if (!$appointment_id) die("Invalid appointment ID.");

// --- Fetch appointment + student info ---
$stmt = $conn->prepare("
    SELECT a.*, 
           CONCAT(s.first_name,' ',s.m_name,' ',s.last_name) AS student_name,
           s.student_id, s.first_name, s.m_name, s.last_name, s.course, s.college_id
    FROM appointments a
    INNER JOIN student s ON a.student_id = s.student_id
    WHERE a.id = ? AND a.facilitator_id = ?
");
$stmt->bind_param("ii", $appointment_id, $facilitator_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
if (!$appointment) die("Appointment not found.");

// --- Fetch student concerns ---
$stmt2 = $conn->prepare("
    SELECT * FROM student_concerns 
    WHERE f_name = ? AND l_name = ? 
    ORDER BY submitted_at DESC LIMIT 1
");
$stmt2->bind_param("ss", $appointment['first_name'], $appointment['last_name']);
$stmt2->execute();
$concerns = $stmt2->get_result()->fetch_assoc();

// --- Counseling questions ---
$questions = [
    "Do you currently feel stressed or overwhelmed?",
    "Are you experiencing difficulties sleeping?",
    "Have you felt sad or down most days recently?",
    "Do you often feel anxious or worried?",
    "Are you finding your academic workload challenging to manage?",
    "Do you have trouble concentrating on school tasks?",
    "Have you missed classes due to emotional or personal reasons?",
    "Do you feel you have people you can rely on for support?",
    "Are you experiencing conflicts or difficulties in your relationships?",
    "Have you felt isolated or lonely recently?",
    "Do you have a history of mental health treatment or counseling?"
];

// --- Fetch professor referrals ---
$student_id = $appointment['student_id'];
$stmt3 = $conn->prepare("
    SELECT r.id, r.reason, r.referral_date,
           CONCAT(p.first_name,' ',p.m_name,' ',p.last_name) AS professor_name
    FROM referrals r
    INNER JOIN professor p ON r.referred_by_id = p.user_id
    WHERE r.student_id = ?
    ORDER BY r.referral_date DESC
");
$stmt3->bind_param("s", $student_id);
$stmt3->execute();
$referrals = $stmt3->get_result();

// --- Handle status update and send notifications ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $remarks = trim($_POST['remarks'] ?? '');

    // --- Handle reschedule ---
    if($status === 'rescheduled'){
        $new_date = $_POST['new_date'] ?? null;
        $new_time = $_POST['new_time'] ?? null;

        if($new_date && $new_time){
            $stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ? WHERE id = ? AND facilitator_id = ?");
            $stmt->bind_param("ssss", $new_date, $new_time, $appointment_id, $facilitator_id);
            $stmt->execute();

            $appointment['appointment_date'] = $new_date;
            $appointment['appointment_time'] = $new_time;

            $remarks .= " (Rescheduled to $new_date $new_time)";
        } else {
            die("Please provide a new date and time for rescheduling.");
        }
    }

    // Update appointment status
    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND facilitator_id = ?");
    $stmt->bind_param("sis", $status, $appointment_id, $facilitator_id);
    $stmt->execute();

    // Log action
    $action_by = "facilitator:" . $facilitator_id;
    $log = $conn->prepare("INSERT INTO appointment_logs (appointment_id, action, action_by, remarks) VALUES (?, ?, ?, ?)");
    $log->bind_param("isss", $appointment_id, $status, $action_by, $remarks);
    $log->execute();

    // --- Prepare list of students to notify ---
    $students = [];

    // 1) Booked student
    $stmt = $conn->prepare("
        SELECT u.email, s.first_name, s.m_name, s.last_name
        FROM student s
        INNER JOIN users u ON s.user_id = u.id
        WHERE s.student_id = ?
    ");
    $stmt->bind_param("s", $appointment['student_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        if(!empty($row['email'])){
            $students[] = [
                'email' => $row['email'],
                'first_name' => $row['first_name'],
                'm_name' => $row['m_name'],
                'last_name' => $row['last_name']
            ];
        }
    }

    // 2) Referred student(s)
    $stmt = $conn->prepare("
        SELECT student_email
        FROM referrals
        WHERE student_id = ?
    ");
    $stmt->bind_param("s", $appointment['student_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        if (!empty($row['student_email'])) {
            $students[] = [
                'email' => $row['student_email'],
                'first_name' => $appointment['first_name'],
                'm_name' => $appointment['m_name'],
                'last_name' => $appointment['last_name']
            ];
        }
    }

    // --- Send emails to all students ---
    foreach($students as $stu){
        sendStatusEmail(
            $stu['email'],
            trim($stu['first_name'].' '.$stu['m_name'].' '.$stu['last_name']),
            $appointment['appointment_date'],
            $appointment['appointment_time'],
            $status,
            $remarks
        );
    }

    // --- Send email to all professors who referred this student ---
    $stmt = $conn->prepare("
        SELECT u.email, p.first_name, p.m_name, p.last_name
        FROM referrals r
        INNER JOIN professor p ON r.referred_by_id = p.user_id
        INNER JOIN users u ON p.user_id = u.id
        WHERE r.student_id = ?
    ");
    $stmt->bind_param("s", $appointment['student_id']);
    $stmt->execute();
    $professors = $stmt->get_result();

    while($prof = $professors->fetch_assoc()) {
        if (!empty($prof['email'])) {
            $prof_name = trim($prof['first_name'].' '.$prof['m_name'].' '.$prof['last_name']);
            sendMail(
                $prof['email'],
                "Student Appointment Status Update",
                "<p>Dear $prof_name,</p>
                 <p>The student you referred, <strong>{$appointment['student_name']}</strong>, has an updated appointment status:</p>
                 <ul>
                    <li><strong>Date:</strong> {$appointment['appointment_date']}</li>
                    <li><strong>Time:</strong> {$appointment['appointment_time']}</li>
                    <li><strong>Status:</strong> $status</li>
                    <li><strong>Remarks:</strong> $remarks</li>
                 </ul>
                 <p>Please check the system for more details.</p>"
            );
        }
    }

    $message = "Status updated successfully. Notifications sent to all students and professors.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointment Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
<style>
        body {
      display: flex;
      min-height: 100vh;
      margin: 0;
      background-color: #f8f9fa;
    }

    /* Sidebar */
    .sidebar {
      background: linear-gradient(180deg, #007bff, #28a745); /* Blue → Green gradient */
      color: white;
      min-height: 100vh;
      position: fixed;
      transition: all 0.3s ease;
      z-index: 1000;
    }
    .sidebar.collapsed {
      width: 0;
      padding: 0;
      overflow: hidden;
    }
    .sidebar a {
      color: white;
      font-weight: 500;
    }
    .sidebar a:hover {
      background-color: rgba(255, 255, 255, 0.15);
    }

    /* Main */
    main {
      margin-left: 250px;
      transition: margin-left 0.3s ease;
      width: calc(100% - 250px);
    }
    main.full-width {
      margin-left: 0;
      width: 100%;
    }

    /* Mobile toggle */
    .sidebar-toggle {
      position: fixed;
      top: 15px;
      left: 15px;
      z-index: 1100;
      background-color: #007bff;
      color: white;
      border-radius: 50%;
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        left: -250px;
        top: 0;
        height: 100%;
        width: 250px;
      }
      .sidebar.collapsed {
        left: 0;
      }
      main {
        margin-left: 0;
        width: 100%;
      }
    }
</style>
</head>
<body>

<!-- Sidebar Toggle (Mobile) -->
<button class="btn sidebar-toggle d-md-none" id="sidebarToggle">
  <span class="material-icons">menu</span>
</button>

<!-- Sidebar -->
<aside class="sidebar d-flex flex-column p-3" style="width:250px;">
    <div class="profile-image mb-3 text-center">
        <span class="material-icons" style="font-size:48px;">person</span>
    </div>
    <div class="user-name mb-4 text-center">
        <?= htmlspecialchars($_SESSION['username']); ?>
        <a href="profile.php" title="Edit Profile" class="ms-2 text-white text-decoration-none">
            <span class="material-icons" style="vertical-align: middle;">edit</span>
        </a>
    </div>
    <div class="flex-grow-1">
        <a href="dashboard.php" class="btn btn-outline-light w-100 text-start mb-4">Dashboard</a>
        <a href="record_session.php" class="btn btn-outline-light w-100 text-start mb-4">Record Session</a>
        <a href="schedule.php" class="btn btn-outline-light w-100 text-start mb-4">Schedule</a>
        <a href="appointment.php" class="btn btn-outline-light w-100 text-start mb-4">Appointment</a>
    </div>
    <div class="mt-auto">
        <a href="../logout.php" class="btn btn-danger w-100 text-start">Logout</a>
    </div>
</aside>

<main class="flex-grow-1 d-flex flex-column">
<header class="page-header p-3 shadow-sm bg-white fw-bold text-center mb-3">Appointment Details</header>

<?php if($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Appointment Info -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5><strong>Student:</strong> <?= htmlspecialchars($appointment['student_name']); ?></h5>
        <h5><strong>Appointment Date:</strong> <?= htmlspecialchars($appointment['appointment_date']); ?></h5>
        <h5><strong>Time:</strong> <?= htmlspecialchars($appointment['appointment_time']); ?></h5>
        <h5><strong>Status:</strong> 
            <span class="badge <?= $appointment['status']=='pending'?'bg-warning text-dark':($appointment['status']=='approved'?'bg-success':'bg-secondary') ?>">
                <?= ucfirst($appointment['status']); ?>
            </span>
        </h5>
        <h5><strong>Purpose:</strong> <?= htmlspecialchars($appointment['purpose'] ?? 'N/A'); ?></h5>
        <h5><strong>Course:</strong> <?= htmlspecialchars($appointment['course']); ?></h5>

        <!-- Student Responses -->
        <h4 class="mt-4">Student Responses</h4>
        <?php if ($concerns): ?>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Question</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $i => $q): 
                            $key = 'q'.($i+1);
                            if(isset($concerns[$key])): ?>
                            <tr>
                                <td><?= htmlspecialchars($q) ?></td>
                                <td>
                                    <?php if ($concerns[$key]): ?>
                                        <span class="badge bg-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No responses submitted by student.</p>
        <?php endif; ?>

        <!-- Update Status -->
        <?php if (!in_array($appointment['status'], ['cancelled','completed'])): ?>
            <h4>Update Status</h4>
            <form method="POST" class="mb-4" id="statusForm">
                <select name="status" class="form-select mb-2" id="statusSelect" required>
                    <option value="">Select Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="rescheduled">Rescheduled</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="completed">Completed</option>
                </select>
                <div id="rescheduleFields" style="display:none;">
                    <input type="date" name="new_date" class="form-control mb-2" placeholder="New Date">
                    <input type="time" name="new_time" class="form-control mb-2" placeholder="New Time">
                </div>
                <input type="text" name="remarks" class="form-control mb-2" placeholder="Remarks (optional)">
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
        <?php else: ?>
            <p class="text-muted">No further actions allowed.</p>
        <?php endif; ?>

        <!-- Professor Referrals -->
        <?php if($referrals->num_rows > 0): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5>Professor Referrals</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Professor</th>
                                <th>Reason</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($r = $referrals->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['professor_name']); ?></td>
                                <td><?= htmlspecialchars($r['reason']); ?></td>
                                <td><?= htmlspecialchars($r['referral_date']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <a href="appointment.php" class="btn btn-secondary mt-3">Back to Appointments</a>
    </div>
</div>
</main>

<script>
    // Sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('main');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      main.classList.toggle('full-width');
    });

    // Show/hide reschedule fields
    const statusSelect = document.getElementById('statusSelect');
    const rescheduleFields = document.getElementById('rescheduleFields');

    statusSelect.addEventListener('change', () => {
        if(statusSelect.value === 'rescheduled'){
            rescheduleFields.style.display = 'block';
        } else {
            rescheduleFields.style.display = 'none';
        }
    });
</script>

</body>
</html>
