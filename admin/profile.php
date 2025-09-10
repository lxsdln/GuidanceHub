<?php
include '../config.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['username'])) {
    die("Not logged in.");
}

$email = $_SESSION['email'];
$username =$_SESSION['username'];

// Step 1: Get user and role
$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

if (!$user) {
    die("User not found.");
}

$user_id = $user['id'];
$role    = strtolower($user['role']); // normalize role

// Step 2: Fetch role-specific details
switch ($role) {
    case 'student':
        $stmt = $conn->prepare("SELECT first_name, m_name, last_name FROM student WHERE user_id = ?");
        break;
    case 'professor':
        $stmt = $conn->prepare("SELECT first_name, m_name, last_name FROM professor WHERE user_id = ?");
        break;
    case 'facilitator':
        $stmt = $conn->prepare("SELECT first_name, m_name, last_name FROM facilitator WHERE user_id = ?");
        break;
    case 'admin':
        $stmt = $conn->prepare("SELECT first_name, m_name, last_name FROM admin WHERE user_id = ?");
        break;
    default:
        die("Unknown role.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_assoc();

// Step 3: Pre-fill values
$username   = htmlspecialchars($user['username']);
$email      = htmlspecialchars($user['email']);
$first_name = htmlspecialchars($details['first_name'] ?? '');
$m_name     = htmlspecialchars($details['m_name'] ?? '');
$last_name  = htmlspecialchars($details['last_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      <?php echo htmlspecialchars($_SESSION['username']); ?>
      <a href="profile.php" title="Edit Profile" class="ms-2 text-white text-decoration-none">
        <span class="material-icons" style="vertical-align: middle;">edit</span>
      </a>
    </div>

    <!-- Sidebar Links -->
    <div class="flex-grow-1">
      <a href="dashboard.php" class="btn btn-outline-light w-100 text-start mb-4">Dashboard</a>
      <a href="user_management.php" class="btn btn-outline-light w-100 text-start mb-4">User Management</a>
      <a href="view_report.php" class="btn btn-outline-light w-100 text-start mb-4">View Report</a>
      <a href="college.php" class="btn btn-outline-light w-100 text-start mb-4">College</a>
    </div>

    <!-- Logout -->
    <div class="mt-auto">
      <a href="../logout.php" class="btn btn-danger w-100 text-start">Logout</a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-grow-1 d-flex flex-column">
    <header class="page-header p-3 shadow-sm bg-white fw-bold text-center">My Profile</header>
      <div class="container my-4">
        <div class="row justify-content-center">
          <div class="col-md-8">
            <div class="card shadow-sm">
              <div class="card-body">
                <dl class="row">
                  <dt class="col-sm-3">Username</dt>
                  <dd class="col-sm-9"><?php echo $username; ?></dd>

                  <dt class="col-sm-3">Email</dt>
                  <dd class="col-sm-9"><?php echo $email; ?></dd>

                  <dt class="col-sm-3">Fullname</dt>
                  <dd class="col-sm-9">
                    <?php 
                      // add middle initial only if exists
                      $fullname = trim("$first_name " . (!empty($m_name) ? $m_name[0] . ". " : "") . $last_name);
                      echo $fullname;
                    ?>
                  </dd>
                </dl>

                <div class="mt-3">
                  <a href="update_profile.php" class="btn btn-primary">Edit Profile</a>
                </div>
              </div>
            </div>
          </div>
         </div>
      </div>
    </main>
  </div>
</body>
</html>