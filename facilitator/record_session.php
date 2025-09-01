<?php
session_start();
include '../config.php';

// ✅ Restrict access: only facilitator
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'facilitator') {
    header("Location: facilitator.php");
    exit();
}

$search = '';
if (isset($_GET['search'])) {
  $search = mysqli_real_escape_string($conn, $_GET['search']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Records Session</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link rel="stylesheet" href="../style.css">
  <script>
    // ✅ Auto-hide alerts after 3 seconds
    setTimeout(() => {
      document.querySelectorAll('.alert').forEach(el => {
        let bsAlert = new bootstrap.Alert(el);
        bsAlert.close();
      });
    }, 3000);
    document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".clickable-row").forEach(row => {
      row.addEventListener("click", function(e) {
        // Avoid triggering when clicking Edit/Delete buttons
        if (!e.target.closest("a, button")) {
          window.location = this.dataset.href;
        }
      });
    });
  });
  </script>
</head>
<body class="m-0">
  <div class="d-flex vh-100">
    <!-- Sidebar -->
    <aside class="sidebar d-flex flex-column p-3" style="width: 250px;">
      <div class="profile-image mb-3 text-center">
        <span class="material-icons" style="font-size:48px;">person</span>
      </div>

      <div class="user-name mb-4 text-center">
        <?php echo htmlspecialchars($_SESSION['email']); ?> (Facilitator)
        <a href="profile.php" title="Edit Profile" class="ms-2 text-dark text-decoration-none">
          <span class="material-icons" style="vertical-align: middle;">edit</span>
        </a>
      </div>

      <!-- Top menu links -->
      <div class="flex-grow-1">
        <a href="dashboard.php" class="btn btn-info w-100 text-center mb-3">Dashboard</a>
        <a href="record_session.php" class="btn btn-info w-100 text-center mb-3">Record Session</a>
        <a href="view_report.php" class="btn btn-info w-100 text-center mb-3">View Report</a>
        <a href="appointment.php" class="btn btn-info w-100 text-center mb-3">Appointment</a>
      </div>

      <!-- Logout at bottom -->
      <div class="mt-auto">
        <a href="../logout.php" class="btn btn-danger w-100 text-center">Logout</a>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow-1 d-flex flex-column">
      <header class="page-header p-3 shadow-sm">Student Records Sessions</header>
      <div class="flex-grow-1 p-4">

        <!-- ✅ Alerts -->
        <?php if (isset($_GET['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ Student added successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php elseif (isset($_GET['updated'])): ?>
          <div class="alert alert-info alert-dismissible fade show" role="alert">
            ✏️ Student updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php elseif (isset($_GET['deleted'])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            🗑️ Student deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="row mb-3 align-items-center">
          <!-- Search Bar -->
          <div class="col-md-8">
            <form method="GET" action="record_session.php" class="input-group">
              <span class="input-group-text bg-white">
                <span class="material-icons">search</span>
              </span>
              <input type="text" name="search" class="form-control" placeholder="Search student or college..." value="<?php echo htmlspecialchars($search); ?>">
              <button type="submit" class="btn btn-outline-primary">Search</button>
            </form>
          </div>
          <div class="col-md-4 text-end mt-2 mt-md-0">
            <a href="add_student.php" class="btn btn-success">➕ Add New Student</a>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Student ID</th>
                <th>Fullname</th>
                <th>College</th>
                <th style="width: 200px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $query = "
                SELECT 
                  s.id AS student_pk,
                  s.student_id,
                  s.first_name,
                  s.m_name,
                  s.last_name,
                  c.college_name,
                  c.college_code
                FROM student s
                LEFT JOIN college c ON s.college_id = c.college_id
              ";

              if (!empty($search)) {
                $query .= " WHERE 
                  s.student_id LIKE '%$search%' OR
                  s.first_name LIKE '%$search%' OR
                  s.m_name LIKE '%$search%' OR
                  s.last_name LIKE '%$search%' OR
                  c.college_name LIKE '%$search%' OR
                  c.college_code LIKE '%$search%'";
              }

              $result = mysqli_query($conn, $query);

              if ($result && mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                      $middle = !empty($row['m_name']) ? " " . strtoupper(substr($row['m_name'], 0, 1)) . "." : "";
                      $fullname = $row['first_name'] . $middle . " " . $row['last_name'];

                      echo "<tr class='clickable-row' data-href='view_record.php?id={$row['student_pk']}'>
                            <td>{$row['student_id']}</td>
                            <td>{$fullname}</td>
                            <td>{$row['college_code']} - {$row['college_name']}</td>
                            <td>
                            <a href='delete_student.php?id={$row['student_pk']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Delete this student?\")'>Delete</a>
                            </td>
                        </tr>";
                  }
              } else {
                  echo "<tr><td colspan='4' class='text-center'>No students found.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
