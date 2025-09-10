<?php
include '../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate logged-in student

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) die("Unauthorized. Please log in.");

$stmt = $conn->prepare("SELECT student_id, first_name, last_name FROM student WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) die("Unauthorized. Student not found.");
$student_id = $student['student_id'];

// Fetch colleges

$collegesResult = $conn->query("SELECT * FROM college ORDER BY college_name");

// Handle appointment booking

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['facilitator_schedule'])) {
    $schedule_id = $_POST['facilitator_schedule'];
    $purpose     = trim($_POST['purpose']);

    // Get selected schedule
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $schedule = $stmt->get_result()->fetch_assoc();

    if (!$schedule) {
        die("<p class='text-danger'>Invalid schedule selected.</p>");
    }

    $facilitator_id   = $schedule['facilitator_id'];
    $appointment_date = date('Y-m-d', strtotime('next ' . $schedule['available_day']));
    $appointment_time = $schedule['start_time'];

    // Insert appointment
    $stmt = $conn->prepare("
        INSERT INTO appointments (student_id, facilitator_id, appointment_date, appointment_time, purpose) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssss", $student_id, $facilitator_id, $appointment_date, $appointment_time, $purpose);

    if ($stmt->execute()) {
        $appointment_id = $stmt->insert_id;

        // Log creation
        $log = $conn->prepare("
            INSERT INTO appointment_logs (appointment_id, action, action_by, remarks)
            VALUES (?, 'created', ?, ?)
        ");
        $action_by = "student:" . $student_id;
        $remarks   = "Appointment requested by student.";
        $log->bind_param("iss", $appointment_id, $action_by, $remarks);
        $log->execute();

        echo "<p class='text-success'>
                Appointment booked for <b>{$appointment_date}</b> at <b>{$appointment_time}</b>.
              </p>";
    } else {
        echo "<p class='text-danger'>Error: " . $stmt->error . "</p>";
    }
    exit;
}

// AJAX: Facilitators by College

if (isset($_GET['get_facilitators'])) {
    $college_id = $_GET['college_id'];

    $query = $conn->prepare("
        SELECT facilitator_id, first_name, last_name
        FROM facilitator
        WHERE college_id = ?
        ORDER BY first_name
    ");
    $query->bind_param("s", $college_id);
    $query->execute();
    $result = $query->get_result();

    echo "<option value=''>-- Select Facilitator --</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['facilitator_id']}'>{$row['first_name']} {$row['last_name']}</option>";
    }
    exit;
}

// AJAX: Schedules by Facilitator

if (isset($_GET['get_schedules'])) {
    $facilitator_id = $_GET['facilitator_id'];

    $query = $conn->prepare("
        SELECT id AS schedule_id, available_day, start_time, end_time
        FROM schedules
        WHERE facilitator_id = ?
        ORDER BY FIELD(available_day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time
    ");
    $query->bind_param("s", $facilitator_id);
    $query->execute();
    $result = $query->get_result();

    echo "<option value=''>-- Select Time Slot --</option>";
    while ($row = $result->fetch_assoc()) {
        // Auto-disable weekend slots
        if (in_array($row['available_day'], ['Saturday','Sunday'])) continue;

        echo "<option value='{$row['schedule_id']}'>
                {$row['available_day']} ({$row['start_time']} - {$row['end_time']})
              </option>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Book Appointment</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
    body { background-color: #ADD8E6; }
    .card { border-radius: 1rem; }
    .card-header { border-top-left-radius: 1rem; border-top-right-radius: 1rem; }
</style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Book an Appointment</h4>
                </div>
                <div class="card-body">
                    <form method="POST" id="appointmentForm">
                        <!-- College -->
                        <div class="mb-3">
                            <label class="form-label">College</label>
                            <select id="college_id" class="form-select" required>
                                <option value="">-- Select College --</option>
                                <?php while($college = $collegesResult->fetch_assoc()): ?>
                                    <option value="<?= $college['college_id'] ?>"><?= $college['college_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Facilitator -->
                        <div class="mb-3">
                            <label class="form-label">Facilitator</label>
                            <select id="facilitator_id" class="form-select" required>
                                <option value="">-- Select College First --</option>
                            </select>
                        </div>

                        <!-- Schedule -->
                        <div class="mb-3">
                            <label class="form-label">Available Time Slots</label>
                            <select name="facilitator_schedule" id="facilitator_schedule" class="form-select" required>
                                <option value="">-- Select Facilitator First --</option>
                            </select>
                        </div>

                        <!-- Purpose -->
                        <div class="mb-3">
                            <label class="form-label">Purpose</label>
                            <textarea name="purpose" class="form-control" rows="3" required></textarea>
                        </div>

                        <!-- Submit -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary rounded-pill">Book Appointment</button>
                        </div>
                    </form>

                    <div id="responseMessage" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(function() {
    // Load facilitators by college
    $("#college_id").change(function() {
        let college_id = $(this).val();
        if (college_id) {
            $.get("?get_facilitators=1&college_id=" + college_id, function(data) {
                $("#facilitator_id").html(data);
                $("#facilitator_schedule").html("<option value=''>-- Select Facilitator First --</option>");
            });
        }
    });

    // Load schedules by facilitator
    $("#facilitator_id").change(function() {
        let facilitator_id = $(this).val();
        if (facilitator_id) {
            $.get("?get_schedules=1&facilitator_id=" + facilitator_id, function(data) {
                $("#facilitator_schedule").html(data);
            });
        }
    });

    // Submit form
    $("#appointmentForm").submit(function(e) {
        e.preventDefault();
        $.post("", $(this).serialize(), function(response) {
            $("#responseMessage").html(response);
            $("#appointmentForm")[0].reset();
            $("#facilitator_id").html("<option value=''>-- Select College First --</option>");
            $("#facilitator_schedule").html("<option value=''>-- Select Facilitator First --</option>");
        });
    });
});
</script>
</body>
</html>