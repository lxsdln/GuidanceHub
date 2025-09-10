<?php
include '../config.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['username'])) {
    die("Not logged in.");
}

$email = $_SESSION['email'];

// --- Get user info ---
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || strtolower($user['role']) !== 'facilitator') {
    die("Access denied.");
}

$user_id = $user['id'];
$username = $user['username'];

// --- Get facilitator details ---
$stmt = $conn->prepare("
    SELECT f.facilitator_id, f.first_name, f.m_name, f.last_name,
           f.college_id, c.college_code, c.college_name
    FROM facilitator f
    LEFT JOIN college c ON f.college_id = c.college_id
    WHERE f.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_assoc();

$facilitator_id = $details['facilitator_id'] ?? '';
$first_name     = $details['first_name'] ?? '';
$m_name         = $details['m_name'] ?? '';
$last_name      = $details['last_name'] ?? '';
$college_name   = $details['college_name'] ?? 'Not set';
$college_code   = $details['college_code'] ?? 'Not set';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>College Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    <div class="profile-image mb-4 text-center">
      <span class="material-icons" style="font-size:48px;">person</span>
    </div>

    <div class="user-name mb-4 text-center">
      <?php echo htmlspecialchars($_SESSION['username']); ?>
      <a href="profile.php" title="Edit Profile" class="ms-2 text-white text-decoration-none">
        <span class="material-icons" style="vertical-align: middle;">edit</span>
      </a>
    </div>

    <!-- Sidebar Links -->
    <div class="flex-grow-1">
      <a href="dashboard.php" class="btn btn-outline-light w-100 text-start mb-4">Dashboard</a>
      <a href="record_session.php" class="btn btn-outline-light w-100 text-start mb-4">Record Season</a>
      <a href="schedule.php" class="btn btn-outline-light w-100 text-start mb-4">Schedule</a>
      <a href="appointment.php" class="btn btn-outline-light w-100 text-start mb-4">Appointment</a>
    </div>

    <!-- Logout -->
    <div class="mt-auto">
      <a href="../logout.php" class="btn btn-danger w-100 text-start">Logout</a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-grow-1 d-flex flex-column">
    <header class="page-header p-3 shadow-lg bg-white fw-bold text-center mb-3">My Profile</header>

<div class="container py-5 center">
    <div class="row justify-content-center">
        <div class="col-md-8 ">
            <div class="card shadow-sm">
                <div class="card-body ">
                    <dl class="row">
                        <dt class="col-sm-3">Username</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($username); ?></dd>

                        <dt class="col-sm-3">Full Name</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($first_name . ' ' . (!empty($m_name) ? $m_name[0] . '. ' : '') . $last_name); ?></dd>

                        <dt class="col-sm-3">College Name</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($college_name); ?></dd>

                        <dt class="col-sm-3">College Code</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($college_code); ?></dd>
                    </dl>
                    <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>
  <script>
    // Sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('main');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      main.classList.toggle('full-width');
    });
  </script>
</body>
</html>