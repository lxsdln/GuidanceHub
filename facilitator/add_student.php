<?php
session_start();
include '../config.php';

// ✅ Restrict access: only facilitator
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'facilitator') {
    header("Location: facilitator.php");
    exit();
}

// Fetch colleges for dropdown
$colleges = mysqli_query($conn, "SELECT college_id, college_code, college_name FROM college ORDER BY college_name ASC");

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $m_name     = mysqli_real_escape_string($conn, $_POST['m_name']);
    $last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
    $college_id = mysqli_real_escape_string($conn, $_POST['college_id']);

    $sql = "INSERT INTO student (student_id, first_name, m_name, last_name, college_id) 
            VALUES ('$student_id', '$first_name', '$m_name', '$last_name', '$college_id')";
    if (mysqli_query($conn, $sql)) {
        header("Location: record_session.php?success=1");
        exit();
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Student</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2>Add New Student</h2>
  <?php if (!empty($message)): ?>
    <div class="alert alert-danger"><?= $message ?></div>
  <?php endif; ?>
  <form method="POST" class="mt-4">
    <div class="mb-3">
      <label class="form-label">Student ID</label>
      <input type="text" name="student_id" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">First Name</label>
      <input type="text" name="first_name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Middle Name</label>
      <input type="text" name="m_name" class="form-control">
    </div>
    <div class="mb-3">
      <label class="form-label">Last Name</label>
      <input type="text" name="last_name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">College</label>
      <select name="college_id" class="form-select" required>
        <option value="">-- Select College --</option>
        <?php while ($row = mysqli_fetch_assoc($colleges)): ?>
          <option value="<?= $row['college_id'] ?>"><?= $row['college_code'] ?> - <?= $row['college_name'] ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-success">Save</button>
    <a href="record_session.php" class="btn btn-secondary">Cancel</a>
  </form>
</body>
</html>
