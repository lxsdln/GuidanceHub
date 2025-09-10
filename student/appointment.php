<?php
include '../config.php';

// -------------------------
// Validate logged-in student
// -------------------------
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
if (!$user_id || $role !== 'student') {
    die("Unauthorized. Please log in.");
}

// Fetch student_id
$stmt = $conn->prepare("SELECT student_id FROM student WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) die("Student not found.");
$student_id = $student['student_id'];

// -------------------------
// Handle appointment cancellation
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = 'cancelled';
    $remarks = 'Cancelled by student';

    $stmt = $conn->prepare("
        UPDATE appointments 
        SET status = ? 
        WHERE id = ? AND student_id = ?
    ");
    $stmt->bind_param("sis", $status, $appointment_id, $student_id);

    if ($stmt->execute()) {
        // Log action
        $action_by = "student:" . $student_id;
        $log = $conn->prepare("
            INSERT INTO appointment_logs (appointment_id, action, action_by, remarks)
            VALUES (?, ?, ?, ?)
        ");
        $log->bind_param("isss", $appointment_id, $status, $action_by, $remarks);
        $log->execute();

        $successMessage = "Appointment cancelled successfully.";
    } else {
        $errorMessage = "Error: " . $stmt->error;
    }
}

// -------------------------
// Fetch appointments
// -------------------------
$query = $conn->prepare("
    SELECT a.*, f.first_name AS fac_fname, f.last_name AS fac_lname, c.college_name
    FROM appointments a
    INNER JOIN facilitator f ON a.facilitator_id = f.facilitator_id
    INNER JOIN college c ON f.college_id = c.college_id
    WHERE a.student_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$query->bind_param("s", $student_id);
$query->execute();
$appointments = $query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Appointments</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap + icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

  <style>
    :root { --sidebar-width: 250px; }

    body {
      display: flex;
      min-height: 100vh;
      margin: 0;
      background-color: #f8f9fa;
    }

    /* Prevent body scroll when mobile sidebar open */
    .no-scroll { overflow: hidden; }

    /* Sidebar */
    .sidebar {
      background: linear-gradient(180deg, #007bff, #28a745); /* Blue → Green */
      color: white;
      min-height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      width: var(--sidebar-width);
      transition: all 0.25s ease;
      z-index: 1040;
      overflow-x: hidden;
      padding: 1rem;
    }

    /* Desktop collapsed (narrow) */
    .sidebar.collapsed {
      width: 0;
      padding: 0;
    }

    .sidebar .profile-image { margin-top: 8px; }
    .sidebar a { color: white; font-weight: 500; }
    .sidebar a:hover { background-color: rgba(255,255,255,0.12); text-decoration:none; }

    /* Overlay shown on mobile when sidebar opens */
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 1035; /* below sidebar */
    }
    .sidebar-overlay.active { display: block; }

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

    /* Mobile: hide sidebar off-screen by default and slide in when `.show` present */
    @media (max-width: 768px) {
      .sidebar {
        left: calc(-1 * var(--sidebar-width));
        width: var(--sidebar-width);
      }
      .sidebar.show { left: 0; }
      /* Make sure desktop collapsed doesn't break mobile */
      .sidebar.collapsed { left: calc(-1 * var(--sidebar-width)); width: var(--sidebar-width); padding: 1rem; }
      main { margin-left: 0; width: 100%; }
    }

    /* Header / table tweaks */
    .page-header { position: sticky; top: 0; z-index: 1010; }
    .table-responsive { max-height: 65vh; overflow:auto; }
  </style>
</head>
<body>

  <!-- Sidebar Toggle (Mobile) -->
  <button class="sidebar-toggle d-md-none shadow" id="sidebarToggle" aria-label="Toggle navigation" aria-expanded="false">
    <span class="material-icons">menu</span>
  </button>

  <!-- Sidebar -->
  <aside class="sidebar d-flex flex-column" style="width:250px;">
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
      <a href="dashboard.php" class="btn btn-outline-light w-100 text-start mb-2">Dashboard</a>
      <a href="appointment.php" class="btn btn-outline-light w-100 text-start mb-2">Appointment</a>
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
    <header class="page-header p-3 shadow-lg bg-white fw-bold text-center mb-3">📅 My Appointments</header>

    <?php if (!empty($successMessage)): ?>
      <div class="alert alert-success text-center"><?= $successMessage ?></div>
    <?php elseif (!empty($errorMessage)): ?>
      <div class="alert alert-danger text-center"><?= $errorMessage ?></div>
    <?php endif; ?>

    <div class="card shadow rounded-4">
      <div class="card-body table-responsive">
        <table class="table table-bordered align-middle mb-0">
          <thead class="table-primary text-center">
            <tr>
              <th>Date</th>
              <th>Time</th>
              <th>Facilitator</th>
              <th>College</th>
              <th>Purpose</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($appointments->num_rows > 0): ?>
              <?php while($row = $appointments->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                  <td><?= htmlspecialchars($row['appointment_time']) ?></td>
                  <td><?= htmlspecialchars($row['fac_fname'].' '.$row['fac_lname']) ?></td>
                  <td><?= htmlspecialchars($row['college_name']) ?></td>
                  <td><?= htmlspecialchars($row['purpose']) ?></td>
                  <td class="text-center">
                    <span class="badge 
                      <?php if ($row['status'] === 'approved') echo 'bg-success';
                            elseif ($row['status'] === 'pending') echo 'bg-warning text-dark';
                            elseif ($row['status'] === 'cancelled') echo 'bg-danger';
                            elseif ($row['status'] === 'completed') echo 'bg-secondary';
                            else echo 'bg-info'; ?>">
                      <?= ucfirst($row['status']) ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <?php if ($row['status'] !== 'cancelled' && $row['status'] !== 'completed'): ?>
                      <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                        <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger rounded-pill">Cancel</button>
                      </form>
                    <?php else: ?>
                      <span class="text-muted">N/A</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">No appointments found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3 text-center">
      <a href="book_appointment.php" class="btn btn-primary rounded-pill">➕ Book New Appointment</a>
    </div>
  </main>

  <!-- Bootstrap JS (placed after content) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.querySelector('.sidebar');
      const main = document.querySelector('main');
      const toggleBtn = document.getElementById('sidebarToggle');
      const overlay = document.getElementById('sidebarOverlay');
      const navLinks = document.querySelectorAll('.sidebar a');

      if (!sidebar || !toggleBtn || !main) return;

      // Helper functions
      function openMobileSidebar() {
        sidebar.classList.add('show');
        overlay.classList.add('active');
        document.body.classList.add('no-scroll');
        toggleBtn.setAttribute('aria-expanded', 'true');
      }
      function closeMobileSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('active');
        document.body.classList.remove('no-scroll');
        toggleBtn.setAttribute('aria-expanded', 'false');
      }

      // Toggle behavior uses mobile slide-in or desktop collapse
      toggleBtn.addEventListener('click', function () {
        if (window.innerWidth <= 768) {
          // Mobile: slide in/out
          if (sidebar.classList.contains('show')) closeMobileSidebar();
          else openMobileSidebar();
        } else {
          // Desktop: collapse/expand width
          sidebar.classList.toggle('collapsed');
          main.classList.toggle('full-width');
        }
      });

      // Overlay closes mobile sidebar
      overlay.addEventListener('click', closeMobileSidebar);

      // Close mobile sidebar when clicking a nav link (helpful UX)
      navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
          if (window.innerWidth <= 768) closeMobileSidebar();
        });
      });

      // Keep layout consistent on resize
      window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
          // Ensure mobile states are cleared
          sidebar.classList.remove('show');
          overlay.classList.remove('active');
          document.body.classList.remove('no-scroll');
          toggleBtn.setAttribute('aria-expanded', 'false');
        } else {
          // Ensure desktop collapsed does not break mobile
          sidebar.classList.remove('collapsed');
          main.classList.remove('full-width');
        }
      });
    });
  </script>
</body>
</html>
