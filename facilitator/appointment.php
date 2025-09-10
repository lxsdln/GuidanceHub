<?php
include '../config.php';
include 'mailer.php';
include '../helper/send_mail.php';

// --- Check facilitator login ---
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
if (!$user_id || $role !== 'facilitator') die("Unauthorized. Please log in.");

// --- Handle status update ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $appointment_id);
    $stmt->execute();

    // Fetch student email
    $stmt2 = $conn->prepare("
        SELECT u.email, CONCAT(s.first_name,' ',s.m_name,' ',s.last_name) AS student_name,
               a.appointment_date, a.appointment_time
        FROM appointments a
        INNER JOIN student s ON a.student_id = s.student_id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE a.id = ?
    ");
    $stmt2->bind_param("i", $appointment_id);
    $stmt2->execute();
    $res = $stmt2->get_result()->fetch_assoc();

    if (!empty($res['email'])) {
        sendStatusEmail($res['email'], $res['student_name'], $res['appointment_date'], $res['appointment_time'], $status);
        $message = "Appointment status updated and email sent.";
    } else {
        $message = "Appointment status updated (student has no email).";
    }
}

// --- Get facilitator ID ---
$stmt = $conn->prepare("SELECT facilitator_id FROM facilitator WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$facilitator_id = $stmt->get_result()->fetch_assoc()['facilitator_id'] ?? null;
if (!$facilitator_id) die("Facilitator record not found.");

// --- Fetch appointments ---
$stmt = $conn->prepare("
    SELECT a.id, a.appointment_date, a.appointment_time, a.status,
           CONCAT(s.first_name,' ',s.last_name) AS student_name
    FROM appointments a
    INNER JOIN student s ON a.student_id = s.student_id
    WHERE a.facilitator_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->bind_param("s", $facilitator_id);
$stmt->execute();
$appointments = $stmt->get_result();
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
    <header class="page-header p-3 shadow-lg bg-white fw-bold text-center mb-3">Appointment</header>

    <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
    <div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if($appointments->num_rows > 0): ?>
                <?php while($row = $appointments->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['appointment_date']); ?></td>
                        <td><?= htmlspecialchars($row['appointment_time']); ?></td>
                        <td><?= htmlspecialchars($row['student_name']); ?></td>
                        <td>
                            <span class="badge 
                                <?= $row['status'] === 'pending' ? 'bg-warning text-dark' : 
                                   ($row['status'] === 'approved' ? 'bg-success' : 'bg-secondary'); ?>">
                                <?= ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="appointment_details.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-primary mb-1">View Details</a>
                            <?php if($row['status'] === 'pending'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="appointment_id" value="<?= $row['id']; ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-sm btn-success mb-1">Approve</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="appointment_id" value="<?= $row['id']; ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-sm btn-danger mb-1">Reject</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-muted">No appointments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
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