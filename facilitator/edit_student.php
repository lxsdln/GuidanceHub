<?php
session_start();
include '../config.php';

// ✅ Restrict access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'facilitator') {
    header("Location: facilitator.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: record_session.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch student
$student = mysqli_query($conn, "SELECT * FROM student WHERE id=$id");
if (mysqli_num_rows($student) == 0) {
    header("Location: record_session.php");
    exit();
}
$student = mysqli_fetch_assoc($student);

// Fetch colleges
$colleges = mysqli_query($conn, "SELECT college_id, college_code, college_name FROM college ORDER BY college_name ASC");

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $m_name     = mysqli_real_escape_string($conn, $_POST['m_name']);
    $last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
    $college_id = mysqli_real_escape_string($conn, $_POST['college_id']);

    $sql = "UPDATE student 
            SET student_id='$student_id', first_name='$first_name', m_name='$m_name', last_name='$last_name', college_id='$college_id'
            WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        header("Location: record_session.php?updated=1");
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
  <title>Edit Student</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2>Edit Student</h2>
  <?php if (!empty($message)): ?>
    <div class="alert alert-danger"><?= $message ?></div>
  <?php endif; ?>
  <form method="POST" class="mt-4">
    <div class="mb-3">
      <label class="form-label">Student ID</label>
      <input type="text" name="student_id" class="form-control" value="<?= $student['student_id'] ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">First Name</label>
      <input type="text" name="first_name" class="form-control" value="<?= $student['first_name'] ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Middle Name</label>
      <input type="text" name="m_name" class="form-control" value="<?= $student['m_name'] ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Last Name</label>
      <input type="text" name="last_name" class="form-control" value="<?= $student['last_name'] ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">College</label>
      <select name="college_id" class="form-select" required>
        <?php while ($row = mysqli_fetch_assoc($colleges)): ?>
          <option value="<?= $row['college_id'] ?>" <?= ($row['college_id'] == $student['college_id']) ? 'selected' : '' ?>>
            <?= $row['college_code'] ?> - <?= $row['college_name'] ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="record_session.php" class="btn btn-secondary">Cancel</a>
  </form>
</body>
</html>
