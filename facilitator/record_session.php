<?php
include '../config.php';

// --- Check facilitator login ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// --- Get facilitator_id ---
$facilitator_id = $_SESSION['facilitator_id'] ?? null;
if (!$facilitator_id) {
    $stmtFac = $conn->prepare("SELECT facilitator_id FROM facilitator WHERE user_id=?");
    $stmtFac->bind_param("i", $_SESSION['user_id']);
    $stmtFac->execute();
    $facilitator_id = $stmtFac->get_result()->fetch_assoc()['facilitator_id'] ?? null;
    if (!$facilitator_id) die("Facilitator ID not found.");
}

// --- Fetch all approved appointments ---
$search = trim($_GET['search'] ?? '');
$sql = "
SELECT a.id AS appointment_id, a.appointment_date, a.appointment_time, a.duration_minutes, a.purpose,
       CONCAT(s.first_name,' ',s.last_name) AS student_name
FROM appointments a
INNER JOIN student s ON a.student_id = s.student_id
WHERE a.facilitator_id=? AND LOWER(a.status)='approved'
";

$params = [$facilitator_id];
$types = "i";

if ($search !== '') {
    $sql .= " AND CONCAT(s.first_name,' ',s.last_name) LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approved Appointments</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
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
      <a href="record_session.php" class="btn btn-outline-light w-100 text-start mb-4">Record Session</a>
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
    <header class="page-header p-3 shadow-lg bg-white fw-bold text-center mb-3">
      Record Sessions
    </header>

  <div class="container-fluid">
    <!-- Search Form -->
    <form method="GET" class="input-group mb-3">
            <input type="text" name="search" class="form-control" placeholder="Search by student name" value="<?= htmlspecialchars($search ?? '') ?>">
            <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>
    <!-- Appointments Table -->
    <div class="card shadow-sm">
      <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Student Name</th>
              <th>Date & Time</th>
              <th>Duration</th>
              <th>Purpose</th>
              <th>Actions</th>
            </tr>
          </thead>
        <tbody>
          <?php if(empty($appointments) || $appointments->num_rows === 0): ?>
            <tr>
              <td colspan="6" class="text-center text-muted">No approved appointments found.</td>
            </tr>
          <?php else: ?>
            <?php while($row = $appointments->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['student_name']) ?></td>
                <td><?= $row['appointment_date'] ?> <?= $row['appointment_time'] ?></td>
                <td><?= $row['duration_minutes'] ?> mins</td>
                <td><?= htmlspecialchars($row['purpose']) ?></td>
                <td>
                  <a href="record_session_details.php?id=<?= $row['appointment_id'] ?>" class="btn btn-sm btn-primary">Add Notes</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
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
