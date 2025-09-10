<?php
include '../config.php';

// ✅ Restrict access: only facilitator can open this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// --- Quick Stats ---
$totalAppointments = $conn->query("SELECT COUNT(*) as total FROM appointments")->fetch_assoc()['total'] ?? 0;
$completedSessions = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status='completed'")->fetch_assoc()['total'] ?? 0;
$missedCanceled = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status IN ('missed','cancelled')")->fetch_assoc()['total'] ?? 0;
$approvedDeclined = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status IN ('approved','rejected')")->fetch_assoc()['total'] ?? 0;

// Appointments Today
$todayAppointments = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = CURDATE()")->fetch_assoc()['total'] ?? 0;

// --- Student Concerns Summary ---
$concernCounts = ['Personal'=>0,'Academic'=>0,'Social'=>0];
$personalCols = ['q1','q2','q3','q4','q10','q11'];
$academicCols = ['q5','q6','q7'];
$socialCols = ['q8','q9'];

$res = $conn->query("SELECT * FROM student_concerns");
if($res){
    while($row = $res->fetch_assoc()){
        foreach($personalCols as $col) if($row[$col]==1) $concernCounts['Personal']++;
        foreach($academicCols as $col) if($row[$col]==1) $concernCounts['Academic']++;
        foreach($socialCols as $col) if($row[$col]==1) $concernCounts['Social']++;
    }
}

// --- Appointments per College ---
$collegeAppointments = [];
$allColleges = ['CICT','CBA','CAS','COE'];
$res = $conn->query("
  SELECT c.college_code, COUNT(a.id) AS total
  FROM appointments a
  JOIN student st ON a.student_id = st.student_id
  JOIN college c ON st.college_id = c.college_id
  GROUP BY c.college_code
");
if($res){
    while($row = $res->fetch_assoc()){
        $collegeAppointments[$row['college_code']] = (int)$row['total'];
    }
}
foreach($allColleges as $college){
    if(!isset($collegeAppointments[$college])) $collegeAppointments[$college] = 0;
}

// --- Referral Trends per Month ---
$referralTrends = array_fill(1,12,0);
$res = $conn->query("
    SELECT MONTH(referral_date) AS month, COUNT(*) AS total
    FROM referrals
    GROUP BY MONTH(referral_date)
");
if($res){
    while($row = $res->fetch_assoc()){
        $referralTrends[(int)$row['month']] = (int)$row['total'];
    }
}
$monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Facilitator Dashboard</title>
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
    <header class="page-header p-3 shadow-sm bg-white fw-bold text-center">Dashboard</header>
      <div class="container-fluid py-4">
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card shadow-sm text-white" style="background-color:#198754;">
                    <div class="card-body d-flex align-items-center">
                        <span class="material-icons me-3" style="font-size:40px;">event_note</span>
                        <div>
                            <h6 class="card-title mb-1">Total Appointments</h6>
                            <h3 class="card-text"><?= $totalAppointments ?></h3>
                            <small>Today: <?= $todayAppointments ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card shadow-sm text-white" style="background-color:#dc3545;">
                    <div class="card-body d-flex align-items-center">
                        <span class="material-icons me-3" style="font-size:40px;">cancel</span>
                        <div>
                            <h6 class="card-title mb-1">Missed / Cancelled</h6>
                            <h3 class="card-text"><?= $missedCanceled ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card shadow-sm text-white" style="background-color:#6c757d;">
                    <div class="card-body d-flex align-items-center">
                        <span class="material-icons me-3" style="font-size:40px;">check_circle</span>
                        <div>
                            <h6 class="card-title mb-1">Approved / Declined</h6>
                            <h3 class="card-text"><?= $approvedDeclined ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card shadow-sm text-white" style="background-color:#0dcaf0;">
                    <div class="card-body d-flex align-items-center">
                        <span class="material-icons me-3" style="font-size:40px;">school</span>
                        <div>
                            <h6 class="card-title mb-1">Completed Sessions</h6>
                            <h3 class="card-text"><?= $completedSessions ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
          <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <h5 class="card-title mb-3">Appointments per College</h5>
                <canvas id="collegeChart" style="width:100%;height:250px;"></canvas>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <h5 class="card-title mb-3">Referral Trends</h5>
                <canvas id="referralChart" style="width:100%;height:250px;"></canvas>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-12 col-sm-12">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <h5 class="card-title mb-3">Student Concerns</h5>
                <canvas id="concernChart" style="width:100%;height:250px;"></canvas>
              </div>
            </div>
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
    // Charts
    new Chart(document.getElementById('collegeChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($collegeAppointments)) ?>,
            datasets: [{ label: 'Appointments', data: <?= json_encode(array_values($collegeAppointments)) ?>, backgroundColor: '#198754' }]
        }
    });
    new Chart(document.getElementById('referralChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($monthLabels) ?>,
            datasets: [{ label: 'Referrals', data: <?= json_encode(array_values($referralTrends)) ?>, borderColor: '#0d6efd', fill: false }]
        }
    });
    new Chart(document.getElementById('concernChart'), {
        type: 'pie',
        data: {
            labels: ['Personal','Academic','Social'],
            datasets: [{ data: [<?= $concernCounts['Personal'] ?>, <?= $concernCounts['Academic'] ?>, <?= $concernCounts['Social'] ?>], backgroundColor: ['#198754','#0d6efd','#ffc107'] }]
        }
    });
  </script>
</body>
</html>
