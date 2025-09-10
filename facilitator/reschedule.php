<?php
include '../config.php';
include '../notify.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];

    $conn->query("UPDATE appointments 
                  SET appointment_date='$new_date', appointment_time='$new_time', status='rescheduled' 
                  WHERE id=$id");

    $conn->query("INSERT INTO appointment_logs (appointment_id, action, action_by, remarks) 
                  VALUES ($id, 'rescheduled', 'facilitator', 'Rescheduled by facilitator')");

    $res = $conn->query("SELECT s.email, s.student_id FROM appointments a JOIN student s ON a.student_id=s.student_id WHERE a.id=$id");
    $row = $res->fetch_assoc();

    sendNotification($conn, $row['student_id'], "Your appointment has been rescheduled to $new_date at $new_time.", $row['email']);

    header("Location: appointment.php");
    exit();
}
?>

<form method="POST">
  <input type="hidden" name="id" value="<?= $_GET['id'] ?>">
  <label>New Date: <input type="date" name="new_date" required></label>
  <label>New Time: <input type="time" name="new_time" required></label>
  <button type="submit">Confirm Reschedule</button>
</form>
<a href="appointment.php">Back to Appointments</a>