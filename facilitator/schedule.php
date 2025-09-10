<?php
include '../config.php';

// Make sure facilitator is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// Fetch facilitator_id
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT facilitator_id FROM facilitator WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$facilitator = $res->fetch_assoc();
$facilitator_id = $facilitator['facilitator_id'];

// For edit
$editId = $_GET['edit'] ?? null;

// Fetch schedules
$query = $conn->prepare("SELECT * FROM schedules WHERE facilitator_id = ? 
                         ORDER BY FIELD(available_day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
$query->bind_param("s", $facilitator_id);
$query->execute();
$schedules_result = $query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage My Availability</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

  <style>
    :root{
      --sidebar-width: 250px;
    }

    body {
      display: flex;
      min-height: 100vh;
      margin: 0;
      background-color: #f8f9fa;
    }

    /* Prevent body scroll when mobile sidebar open */
    .no-scroll {
      overflow: hidden;
    }

    /* Sidebar */
    .sidebar {
      background: linear-gradient(180deg, #007bff, #28a745); /* Blue → Green gradient */
      color: white;
      min-height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      width: var(--sidebar-width);
      transition: all 0.25s ease;
      z-index: 1040; /* above overlay */
      overflow-x: hidden;
    }

    /* Desktop: collapse to 0 width */
    .sidebar.collapsed {
      width: 0;
      padding: 0;
      overflow: hidden;
    }

    .sidebar .profile-image { margin-top: 8px; }
    .sidebar a {
      color: white;
      font-weight: 500;
    }
    .sidebar a:hover {
      background-color: rgba(255, 255, 255, 0.12);
      color: #fff;
      text-decoration: none;
    }

    /* Overlay shown on mobile when sidebar opens */
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 1035; /* below sidebar */
    }
    .sidebar-overlay.active {
      display: block;
    }

    /* Main content */
    main {
      margin-left: 250px;
      transition: margin-left 0.3s ease;
      width: calc(100% - 250px);
    }
    /* When sidebar collapsed on desktop */
    main.full-width {
      margin-left: 0;
      width: 100%;
    }

    /* Mobile toggle button */
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
      border: none;
    }

    /* Mobile specific: hide sidebar off-screen by default and slide in when `.show` present */
    @media (max-width: 768px) {
      .sidebar {
        left: calc(-1 * var(--sidebar-width));
        width: var(--sidebar-width); /* keep logical width */
      }
      .sidebar.show {
        left: 0;
      }
      /* Make sure desktop collapsed behavior doesn't interfere on mobile */
      .sidebar.collapsed { left: calc(-1 * var(--sidebar-width)); width: var(--sidebar-width); padding: 1rem; }
      main {
        margin-left: 0;
        width: 100%;
      }
    }

    /* Small styling tweaks */
    .table-responsive { max-height: 65vh; overflow:auto; }
  </style>
</head>
<body>

  <!-- Sidebar Toggle (Mobile) -->
  <button class="sidebar-toggle d-md-none shadow" id="sidebarToggle" aria-label="Toggle navigation">
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
      <a href="record_session.php" class="btn btn-outline-light w-100 text-start mb-4">Record Seasion</a>
      <a href="schedule.php" class="btn btn-outline-light w-100 text-start mb-4">Schedule</a>
      <a href="appointment.php" class="btn btn-outline-light w-100 text-start mb-4">Appointment</a>
    </div>

    <!-- Logout -->
    <div class="mt-auto">
      <a href="../logout.php" class="btn btn-danger w-100 text-start">Logout</a>
    </div>
  </aside>

  <!-- Overlay for mobile (click to close) -->
  <div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true"></div>
<!-- Main Content -->
<main class="flex-grow-1 d-flex flex-column">
  <header class="page-header p-3 shadow-lg bg-white fw-bold text-center mb-3">
    Manage My Schedule
  </header>


  <!-- Add Availability Button -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
      ➕ Add Availability
    </button>
  </div>

  <!-- Schedules Table -->
  <div class="card shadow-sm">
      <div class="card-body table-responsive">
        <table class="table table-hover table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Day</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($schedules_result->num_rows > 0): ?>
              <?php while ($row = $schedules_result->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['available_day']) ?></td>
                  <td><?= date("h:i A", strtotime($row['start_time'])) ?></td>
                  <td><?= date("h:i A", strtotime($row['end_time'])) ?></td>
                  <td class="text-center">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                      <a href="schedule.php?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                      <a href="schedule_delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                         onclick="return confirm('Delete this schedule?')">Delete</a>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                  No schedules added yet.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

  <!-- Include schedule form (modal) -->
  <?php include 'schedule_form.php'; ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.querySelector('.sidebar');
      const main = document.querySelector('main');
      const toggleBtn = document.getElementById('sidebarToggle');
      const overlay = document.getElementById('sidebarOverlay');
      const navLinks = document.querySelectorAll('.sidebar a');

      function openMobileSidebar() {
        sidebar.classList.add('show');
        overlay.classList.add('active');
        document.body.classList.add('no-scroll');
      }
      function closeMobileSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('active');
        document.body.classList.remove('no-scroll');
      }

      toggleBtn.addEventListener('click', function () {
        if (window.innerWidth <= 768) {
          // Mobile: slide in/out
          if (sidebar.classList.contains('show')) {
            closeMobileSidebar();
          } else {
            openMobileSidebar();
          }
        } else {
          // Desktop: collapse width
          sidebar.classList.toggle('collapsed');
          main.classList.toggle('full-width');
        }
      });

      // Clicking overlay closes mobile sidebar
      overlay.addEventListener('click', function () {
        closeMobileSidebar();
      });

      // Close mobile sidebar when clicking a nav link (so users can navigate)
      navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
          if (window.innerWidth <= 768) closeMobileSidebar();
        });
      });

      // Handle window resize to keep layout consistent
      window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
          // remove mobile-only states
          sidebar.classList.remove('show');
          overlay.classList.remove('active');
          document.body.classList.remove('no-scroll');
        } else {
          // if switching to mobile, ensure desktop "collapsed" doesn't hide the sidebar wrong
          sidebar.classList.remove('collapsed');
          main.classList.remove('full-width');
        }
      });
    });
  </script>

  <!-- If you auto-open schedule modal when editing -->
  <?php if (!empty($editId)): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      var modalEl = document.getElementById("scheduleModal");
      if (modalEl) {
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
      }
    });
  </script>
  <?php endif; ?>

</body>
</html>