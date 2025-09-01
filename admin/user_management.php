<?php
session_start();
include '../config.php';

// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../login.php");
//     exit();
// }
 $query = "SELECT * FROM users";
$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
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
      <div class="user-name">Admin
        <span class="material-icons" title="Edit profile">edit</span>
      </div>
      <a href="dashboard.php" class="btn btn-outline-dark">Dashboard</a>
      <a href="user_management.php" class="btn btn-warning">User Management</a>
      <a href="view_report.php" class="btn btn-outline-dark">View Report</a>
      <a href="college.php" class="btn btn-outline-dark">College</a>
      <a href="../logout.php" class="btn btn-danger">Logout</a>
    </aside>


    <main class="content">
      <div class="page-header">User Management</div>
      <div class="flex-grow-1 p-4">
      <a href="add_user.php" class="btn btn-success mb-3">➕ Add New User</a>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center">
          <thead class="table-light">
            <tr>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th style="width: 160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = $result->fetch_assoc()): ?>

                    <tr>
                        <td><?= $row['username'] ?></td>
                        <td><?= $row['email'] ?></td>
                        <td><?= $row['role'] ?></td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a> |
                            <a href="delete.php?id=<?= $row['id'] ?>" class='btn btn-sm btn-danger' onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
