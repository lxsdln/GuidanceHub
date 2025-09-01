<?php
session_start();
include '../config.php';
// include 'sidebar.php';

// ✅ Restrict access: only facilitator can open this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'facilitator') {
    header("Location: facilitator.php");
    exit();
}

// ✅ Fetch distinct months
$months = [];
$monthQuery = $conn->query("
    SELECT DISTINCT month 
    FROM cases 
    ORDER BY FIELD(month, 'January','February','March','April','May','June','July','August',
                             'September','October','November','December')
");
while ($row = $monthQuery->fetch_assoc()) {
    $months[] = $row['month'];
}

// ✅ Define categories
$categories = ['Bullying','Financial','Adjustment Issue'];
$categoryData = [];

// ✅ Get counts per category per month
foreach ($categories as $cat) {
    $counts = [];
    foreach ($months as $month) {
        $stmt = $conn->prepare("SELECT `count` FROM cases WHERE month = ? AND category = ?");
        $stmt->bind_param("ss", $month, $cat);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $counts[] = $row ? (int) $row['count'] : 0;
    }
    $categoryData[$cat] = $counts;
}
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
        <a href="../logout.php" class="btn btn-danger w-100 text-start">Logout</a>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow-1 d-flex flex-column">
      <header class="page-header p-3 shadow-sm">Facilitator Dashboard</header>
      <div class="d-flex justify-content-center align-items-center flex-grow-1 px-3">
        <div style="width: 100%; max-width: 800px;">
          <canvas id="issueChart"></canvas>
        </div>
      </div>
    </main>
  </div>

  <script>
    const ctx = document.getElementById('issueChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [
          {
            label: 'Bullying',
            backgroundColor:'rgba(255,99,132,0.7)',
            data: <?php echo json_encode($categoryData['Bullying']); ?>
          },
          {
            label: 'Financial Problem',
            backgroundColor:'rgba(54,162,235,0.7)',
            data: <?php echo json_encode($categoryData['Financial']); ?>
          },
          {
            label: 'Adjustment Issue',
            backgroundColor:'rgba(54, 235, 99, 0.7)',
            data: <?php echo json_encode($categoryData['Adjustment Issue']); ?>
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          title: {
            display: true,
            text: 'Cases by Month and Category'
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>
</body>
</html>
