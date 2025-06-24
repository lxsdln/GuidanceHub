<?php
session_start();
include '../config.php';

$months = [];
$monthQuery = $conn->query("SELECT DISTINCT month FROM cases ORDER BY FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')");
while ($row = $monthQuery->fetch_assoc()) {
    $months[] = $row['month'];
}

$categories = ['Bullying','Financial'];
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
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="../style.css">

  <!-- <style>
    body, html {
      height: 100%;
      margin: 0;
      font-family: 'Inter', sans-serif;
    }

    .app-container {
      display: grid;
      grid-template-columns: 250px 1fr;
      height: 100vh;
    }

    .sidebar {
      background-color: #a2e1ca;
      padding: 30px 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .profile-image {
      background-color: #ddd6fe;
      border-radius: 50%;
      width: 120px;
      height: 120px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 60px;
      color: #4c2882;
      margin-bottom: 20px;
    }

    .user-name {
      font-weight: bold;
      font-size: 1.2rem;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .sidebar nav a {
      width: 100%;
      margin-bottom: 10px;
      text-align: left;
    }

    .page-header {
      background-color: #6ec1e4;
      padding: 20px;
      font-size: 1.8rem;
      font-weight: bold;
      text-align: center;
    }

    @media (max-width: 768px) {
      .app-container {
        grid-template-columns: 1fr;
        grid-template-rows: auto 1fr;
      }

      .sidebar {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
        padding: 15px;
      }

      .profile-image {
        width: 70px;
        height: 70px;
        font-size: 30px;
      }

      .user-name {
        font-size: 1rem;
      }
    }
  </style> -->
</head>
<body>
  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar text-center">
      <div class="profile-image">
        <span class="material-icons">person</span>
      </div>
      <div class="user-name">
        Admin
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
