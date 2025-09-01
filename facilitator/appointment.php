<?php
session_start();
include '../config.php';
// include 'sidebar.php';

// ✅ Restrict access: only facilitator can open this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'facilitator') {
    header("Location: facilitator.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Appointment</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="../style.css">
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
      <header class="page-header p-3 shadow-sm">Appointment</header>
      <div class="d-flex justify-content-center align-items-center flex-grow-1 px-3">
      </div>
    </main>
  </div>
</body>
</html>
