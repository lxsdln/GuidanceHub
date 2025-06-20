<?php
session_start();
include '../config.php';

$search = '';
if (isset($_GET['search'])) {
  $search = mysqli_real_escape_string($conn, $_GET['search']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>College Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="../style.css">
  <style>
    .search-wrapper {
      position: relative;
    }
    .search-wrapper input {
      padding-left: 35px;
    }
    .search-wrapper .material-icons {
      position: absolute;
      top: 50%;
      left: 10px;
      transform: translateY(-50%);
      color: #888;
    }
  </style>
</head>
<body>
  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar text-center">
      <div class="profile-image text-primary">
        <span class="material-icons">person</span>
      </div>
      <div class="user-name">Admin</div>
      <a href="dashboard.php" class="btn btn-outline-dark w-100 mb-2">Dashboard</a>
      <a href="user_management.php" class="btn btn-outline-dark w-100 mb-2">User Management</a>
      <a href="view_report.php" class="btn btn-outline-dark w-100 mb-2">View Report</a>
      <a href="college.php" class="btn btn-warning w-100 mb-2">College</a>
      <a href="../logout.php" class="btn btn-danger w-100">Logout</a>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <div class="page-header">College & Facilitator List</div>
        <div class="mt-4">
            <div class="row mb-3 align-items-center">
            <!-- Search Bar with Button -->
                <div class="col-md-8">
                    <form method="GET" action="college.php" class="input-group">
                        <span class="input-group-text bg-white">
                        <span class="material-icons">search</span>
                    </span>
                    <input type="text" name="search" class="form-control" placeholder="Search college or facilitator..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary">Search</button>
                    </form>
                </div>
                <div class="col-md-4 text-end mt-2 mt-md-0">
                    <a href="add_college.php" class="btn btn-success">➕ Add New College</a>
            </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>College Code</th>
                <th>College Name</th>
                <th>Guidance Facilitator</th>
                <th style="width: 160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $query = "
                SELECT 
                  c.college_code, 
                  c.college_name, 
                  f.first_name, 
                  f.last_name, 
                  c.id AS college_id
                FROM college c
                LEFT JOIN facilitator f ON c.college_id = f.college_id
              ";

              if (!empty($search)) {
                $query .= " WHERE 
                  c.college_code LIKE '%$search%' OR
                  c.college_name LIKE '%$search%' OR
                  CONCAT(f.first_name, ' ', f.last_name) LIKE '%$search%'";
              }

              $result = mysqli_query($conn, $query);

              if (mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                      $facilitator = $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : '<em>No Facilitator Assigned</em>';
                      echo "<tr>
                              <td>{$row['college_code']}</td>
                              <td>{$row['college_name']}</td>
                              <td>$facilitator</td>
                              <td>
                                <a href='edit_college.php?id={$row['college_id']}' class='btn btn-sm btn-primary'>Edit</a>
                                <a href='delete_college.php?id={$row['college_id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Delete this college?\")'>Delete</a>
                              </td>
                            </tr>";
                  }
              } else {
                  echo "<tr><td colspan='4' class='text-center'>No results found.</td></tr>";
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
