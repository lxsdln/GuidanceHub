<?php
session_start();
include '../config.php';

$months = [];
$monthQuery = $conn->query("SELECT DISTINCT month FROM cases ORDER BY FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')");
while ($row = $monthQuery->fetch_assoc()) {
    $months[] = $row['month'];
}

$categories = ['Bullying','Financial','Adjustment'];
$categoryData = [];

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
<body>
  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar text-center">
      <div class="profile-image">
        <span class="material-icons">person</span>
      </div>
      <div class="user-name">
        Guidance Facilitator
        <a href="profile.php" title="Edit Profile" class="mb-3 text-dark text-decoration-none">
            <span class="material-icons" style="vertical-align: middle;">edit</span>
        </a>
      </div>
      <nav class="w-100">
        <a href="dashboard.php" class="btn btn-info w-100 text-start mb-2">Dashboard</a>
        <a href="record_session.php" class="btn btn-info w-100 text-start mb-2">Record Session</a>
        <a href="view_report.php" class="btn btn-info w-100 text-start mb-2">View Report</a>
        <a href="appointment.php" class="btn btn-info w-100 text-start mb-2">Appointment</a>
        <a href="../logout.php" class="btn btn-danger w-100 text-start">Logout</a>
      </nav>
    </aside>

    <main class="content">
      <header class="page-header">Dashboard</header>
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
          labels: <?php echo json_encode($months);?>,
          datasets: [
            {
              label: 'Bullying',
              backgroundColor:'rgba(255,99,132,0.7)',
              data:<?php echo json_encode($categoryData['Bullying']);?>
            },
            {
              label: 'Financial Problem',
              backgroundColor:'rgba(54,162,235,0.7)',
              data:<?php echo json_encode($categoryData['Financial']);?>
            },
            {
              label: 'Adjustment Issue',
              backgroundColor:'rgba(54, 235, 99, 0.7)',
              data:<?php echo json_encode($categoryData['Adjustment']);?>
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
