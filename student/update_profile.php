<?php
include '../config.php';

// ✅ Restrict access to students
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];

// --- Fetch student info ---
$stmt = $conn->prepare("SELECT s.id, s.student_id, s.first_name, s.m_name, s.last_name, s.college_id 
                        FROM student s
                        JOIN users u ON s.user_id = u.id
                        WHERE u.email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student record not found.");
}

// --- Fetch colleges ---
$colleges = mysqli_query($conn, "SELECT college_id, college_code, college_name FROM college ORDER BY college_name ASC");

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $m_name     = mysqli_real_escape_string($conn, $_POST['m_name']);
    $last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
    $college_id = mysqli_real_escape_string($conn, $_POST['college_id']);
    $course     = mysqli_real_escape_string($conn, $_POST['course'] ?? ''); // <-- add this line

    // Updated SQL to include course
    $sql = "UPDATE student 
            SET student_id='$student_id', first_name='$first_name', m_name='$m_name', last_name='$last_name', 
                college_id='$college_id', course='$course' 
            WHERE id=" . $student['id'];

    if (mysqli_query($conn, $sql)) {
        $message = "Profile updated successfully!";
        // Refresh student info
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile - Student</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
  <h2>Edit My Profile</h2>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?= strpos($message, 'Error') !== false ? 'danger' : 'success' ?>"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST" class="mt-4">
    <div class="mb-3">
      <label class="form-label">Student ID</label>
      <input type="text" name="student_id" class="form-control" value="<?= htmlspecialchars($student['student_id']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">First Name</label>
      <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($student['first_name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Middle Name</label>
      <input type="text" name="m_name" class="form-control" value="<?= htmlspecialchars($student['m_name']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Last Name</label>
      <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($student['last_name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Course</label>
      <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($student['course'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">College</label>
      <select name="college_id" class="form-select" required>
        <?php while ($row = mysqli_fetch_assoc($colleges)): ?>
          <option value="<?= $row['college_id'] ?>" <?= ($row['college_id'] == $student['college_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($row['college_code']) ?> - <?= htmlspecialchars($row['college_name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="profile.php" class="btn btn-secondary">Cancel</a>
  </form>
</body>
</html>
