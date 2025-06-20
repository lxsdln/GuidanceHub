<?php
session_start();
include '../config.php';

// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../login.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="profile-image text-primary">
        <span class="material-icons">person</span>
      </div>
      <div class="user-name">Admin</div>
      <a href="dashboard.php" class="btn btn-outline-dark">Dashboard</a>
      <a href="user_management.php" class="btn btn-warning">User Management</a>
      <a href="view_report.php" class="btn btn-outline-dark">View Report</a>
      <a href="college.php" class="btn btn-outline-dark">College</a>
      <a href="../logout.php" class="btn btn-danger">Logout</a>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <div class="page-header">User Management</div>
      <div class="mt-4">
        <a href="add_user.php" class="btn btn-success mb-3">➕ Add New User</a>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th style="width: 150px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $query = "SELECT * FROM users";
              $result = mysqli_query($conn, $query);

              if (mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                      echo "<tr>
                              <td>{$row['username']}</td>
                              <td>{$row['email']}</td>
                              <td>{$row['role']}</td>
                              <td>
                                <a href='edit_user.php?id={$row['id']}' class='btn btn-sm btn-primary'>Edit</a>
                                <a href='delete_user.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Delete this user?\")'>Delete</a>
                              </td>
                            </tr>";
                  }
              } else {
                  echo "<tr><td colspan='5' class='text-center'>No users found.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
