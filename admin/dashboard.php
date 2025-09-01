<?php
session_start();
include '../config.php';

// ✅ Restrict access to admin only
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ✅ Fetch distinct months in correct order
$months = [];
$monthQuery = $conn->query("
    SELECT DISTINCT month 
    FROM cases 
    ORDER BY FIELD(month, 'January','February','March','April','May','June','July','August','September','October','November','December')
");
while ($row = $monthQuery->fetch_assoc()) {
    $months[] = $row['month'];
}

// ✅ Define categories
$categories = ['Bullying','Financial','Adjustment Issue'];
$categoryData = [];

// ✅ Fetch case counts by category & month
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
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar text-center">
      <div class="profile-image">
        <span class="material-icons">person</span>
      </div>
      <div class="user-name">
        <?php echo htmlspecialchars($_SESSION['email']); ?> (Admin)
        <span class="material-icons" title="Edit profile">edit</span>
      </div>
      <nav class="w-100">
        <a href="dashboard.php" class="btn btn-info w-100 text-start mb-2">Dashboard</a>
        <a href="user_management.php" class="btn btn-info w-100 text-start mb-2">User Management</a>
        <a href="view_report.php" class="btn btn-info w-100 text-start mb-2">View Report</a>
        <a href="college.php" class="btn btn-info w-100 text-start mb-2">College</a>
        <a href="../logout.php" class="btn btn-danger w-100 text-start">Logout</a>
      </nav>
    </aside>

    <main class="content">
      <header class="page-header">Admin Dashboard</header>
      <div class="d-flex justify-content-center align-items-center px-3" style="min-height: 400px;">
        <div style="width: 100%; max-width: 800px;">
          <canvas id="issueChart"></canvas>
        </div>
      </div>
    </main>
  </div>

  <script>
    const ctx = document.getElementById('issueChart').getContext('2d');
    const chart = new Chart(ctx,{
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
      options:{
        responsive: true,
        plugins:{
          title:{
            display: true,
            text: 'Cases by Month and Category'
          }
        },
        scales:{
          y:{
            beginAtZero: true
          }
        }
      }
    });
  </script>
</body>
</html>
