<?php
session_start();
include '../config.php';

// ✅ Only admin access
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../login.php");
    exit();
}

// --- Handle Filters ---
$from = $_GET['from_date'] ?? null;
$to = $_GET['to_date'] ?? null;
$college = $_GET['college'] ?? null;
$status = $_GET['status'] ?? null;

$where = [];
if($from) $where[] = "DATE(a.appointment_date) >= '$from'";
if($to) $where[] = "DATE(a.appointment_date) <= '$to'";
if($college) $where[] = "c.college_code='$college'";
if($status) $where[] = "a.status='$status'";
$whereSQL = $where ? "WHERE ".implode(" AND ",$where) : "";

// --- Quick Stats ---
$totalAppointments = $conn->query("SELECT COUNT(*) AS total FROM appointments a JOIN student st ON a.student_id = st.student_id JOIN college c ON st.college_id = c.college_id $whereSQL")->fetch_assoc()['total'] ?? 0;
$completedSessions = $conn->query("SELECT COUNT(*) AS total FROM appointments a JOIN student st ON a.student_id = st.student_id JOIN college c ON st.college_id = c.college_id $whereSQL AND a.status='completed'")->fetch_assoc()['total'] ?? 0;
$missedCanceled = $conn->query("SELECT COUNT(*) AS total FROM appointments a JOIN student st ON a.student_id = st.student_id JOIN college c ON st.college_id = c.college_id $whereSQL AND a.status IN ('missed','cancelled')")->fetch_assoc()['total'] ?? 0;
$approvedDeclined = $conn->query("SELECT COUNT(*) AS total FROM appointments a JOIN student st ON a.student_id = st.student_id JOIN college c ON st.college_id = c.college_id $whereSQL AND a.status IN ('approved','rejected')")->fetch_assoc()['total'] ?? 0;

// --- Table Data ---
$tableData = $conn->query("
    SELECT a.appointment_date,a.appointment_time,CONCAT(st.first_name,' ',st.last_name) AS student, c.college_code AS college, a.status, f.first_name AS facilitator_name
    FROM appointments a
    JOIN student st ON a.student_id=st.student_id
    JOIN college c ON st.college_id=c.college_id
    LEFT JOIN facilitator f ON a.facilitator_id=f.facilitator_id
    $whereSQL
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");

// --- Chart Data (Appointments per Month) ---
$chartData = $conn->query("
    SELECT MONTH(appointment_date) AS month, COUNT(*) AS total
    FROM appointments a
    JOIN student st ON a.student_id=st.student_id
    JOIN college c ON st.college_id=c.college_id
    $whereSQL
    GROUP BY MONTH(appointment_date)
");
$appointmentsMonth = array_fill(1,12,0);
if($chartData){
    while($row=$chartData->fetch_assoc()){
        $appointmentsMonth[(int)$row['month']] = (int)$row['total'];
    }
}
$monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

// --- Referral Trends ---
$referralData = $conn->query("
    SELECT MONTH(referral_date) AS month, COUNT(*) AS total
    FROM referrals
    GROUP BY MONTH(referral_date)
");
$referralMonth = array_fill(1,12,0);
if($referralData){
    while($row=$referralData->fetch_assoc()){
        $referralMonth[(int)$row['month']] = (int)$row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin View Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body{background:#f8f9fa;}
    .card-stat{display:flex;align-items:center;padding:15px;color:#fff;}
    .card-stat i{font-size:40px;margin-right:15px;}
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
  <main class="flex-grow-1 d-flex flex-column">
    <header class="page-header p-3 shadow-sm bg-white fw-bold text-center">View Report</header>
      <div class="container-fluid py-4">
<!-- Filters -->
<div class="card mb-4 shadow-sm">
<div class="card-body">
<form class="row g-3">
<div class="col-md-3"><label>From</label><input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from) ?>"></div>
<div class="col-md-3"><label>To</label><input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to) ?>"></div>
<div class="col-md-3"><label>College</label>
<select name="college" class="form-select">
<option value="">All</option>
<option value="CICT" <?= $college=='CICT'?'selected':'' ?>>CICT</option>
<option value="CBA" <?= $college=='CBA'?'selected':'' ?>>CBA</option>
<option value="CAS" <?= $college=='CAS'?'selected':'' ?>>CAS</option>
<option value="COE" <?= $college=='COE'?'selected':'' ?>>COE</option>
</select>
</div>
<div class="col-md-3"><label>Status</label>
<select name="status" class="form-select">
<option value="">All</option>
<option value="completed" <?= $status=='completed'?'selected':'' ?>>Completed</option>
<option value="missed" <?= $status=='missed'?'selected':'' ?>>Missed</option>
<option value="canceled" <?= $status=='canceled'?'selected':'' ?>>Canceled</option>
<option value="approved" <?= $status=='approved'?'selected':'' ?>>Approved</option>
<option value="declined" <?= $status=='declined'?'selected':'' ?>>Declined</option>
</select>
</div>
<div class="col-12 text-end"><button class="btn btn-primary" type="submit">Filter</button></div>
</form>
</div>
</div>

<!-- Quick Stats -->
<div class="row g-3 mb-4">
<div class="col-md-3 col-sm-6">
<div class="card bg-success shadow-sm"><div class="card-body card-stat">
<div><h6>Total Appointments</h6><h3><?= $totalAppointments ?></h3></div>
</div></div>
</div>
<div class="col-md-3 col-sm-6">
<div class="card bg-danger shadow-sm"><div class="card-body card-stat">
<div><h6>Missed / Cancelled</h6><h3><?= $missedCanceled ?></h3></div>
</div></div>
</div>
<div class="col-md-3 col-sm-6">
<div class="card bg-secondary shadow-sm"><div class="card-body card-stat">
<div><h6>Approved / Declined</h6><h3><?= $approvedDeclined ?></h3></div>
</div></div>
</div>
<div class="col-md-3 col-sm-6">
<div class="card bg-info shadow-sm"><div class="card-body card-stat">
<div><h6>Completed Sessions</h6><h3><?= $completedSessions ?></h3></div>
</div></div>
</div>
</div>

<!-- Table -->
<div class="card mb-4 shadow-sm">
<div class="card-body">
<h5>Appointments / Referrals Table</h5>
<div class="table-responsive">
<table class="table table-striped table-hover">
<thead>
<tr>
<th>Date</th><th>Time</th><th>Student</th><th>College</th><th>Status</th><th>Facilitator</th>
</tr>
</thead>
<tbody>
<?php if($tableData && $tableData->num_rows>0){
while($row=$tableData->fetch_assoc()){
    echo "<tr><td>{$row['appointment_date']}</td><td>{$row['appointment_time']}</td><td>{$row['student']}</td><td>{$row['college']}</td><td>{$row['status']}</td><td>{$row['facilitator_name']}</td></tr>";
}}else{
    echo "<tr><td colspan='6' class='text-center'>No data found</td></tr>";
} ?>
</tbody>
</table>
</div>
</div>
</div>


<!-- Charts -->
<div class="row mb-4">
<div class="col-md-6"><div class="card shadow-sm"><div class="card-body">
<h5>Appointments per Month</h5>
<canvas id="appointmentsChart"></canvas>
</div></div></div>
<div class="col-md-6"><div class="card shadow-sm"><div class="card-body">
<h5>Referral Trends per Month</h5>
<canvas id="referralChart"></canvas>
</div></div></div>
</div>

</div> 
</main><!-- container -->

<script>
      // Sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('main');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      main.classList.toggle('full-width');
    });
// Appointments per Month Chart
const appointmentsChart = new Chart(document.getElementById('appointmentsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($monthLabels) ?>,
        datasets: [{
            label: 'Appointments',
            data: <?= json_encode(array_values($appointmentsMonth)) ?>,
            backgroundColor: '#198754'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Appointments per Month' }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Referral Trends Chart
const referralChart = new Chart(document.getElementById('referralChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($monthLabels) ?>,
        datasets: [{
            label: 'Referrals',
            data: <?= json_encode(array_values($referralMonth)) ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Referral Trends per Month' }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

