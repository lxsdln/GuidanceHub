<?php
include '../config.php';

// ✅ Ensure only admin can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// --- ADD College ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    try {
        $college_code = trim($_POST['college_code']);
        $college_name = trim($_POST['college_name']);

        if (empty($college_code) || empty($college_name)) {
            throw new Exception("College code and name cannot be empty.");
        }

        $stmt = $conn->prepare("INSERT INTO college (college_code, college_name) VALUES (?, ?)");
        $stmt->bind_param("ss", $college_code, $college_name);

        if (!$stmt->execute()) {
            throw new Exception("Error adding college: " . $stmt->error);
        }

        $_SESSION['success'] = "College added successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: college.php");
    exit();
}
// --- TOGGLE FACILITATOR STATUS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'toggle_status') {
    try {
        $facilitator_id = $_POST['facilitator_id'];
        $new_status = intval($_POST['new_status']); // 1 = active, 0 = inactive

        if ($facilitator_id <= 0) throw new Exception("Invalid Facilitator ID.");

        $stmt = $conn->prepare("UPDATE facilitator SET status=? WHERE facilitator_id=?");
        $stmt->bind_param("is", $new_status, $facilitator_id);

        if (!$stmt->execute()) {
            throw new Exception("Error updating status: " . $stmt->error);
        }

        $_SESSION['success'] = $new_status ? "Facilitator activated." : "Facilitator deactivated.";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: college.php");
    exit();
}


// --- SEARCH ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>College Management</title>
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
      <a href="user_management.php" class="btn btn-outline-light w-100 text-start mb-4">User Management</a>
      <a href="view_report.php" class="btn btn-outline-light w-100 text-start mb-4">View Report</a>
      <a href="college.php" class="btn btn-outline-light w-100 text-start mb-4">College</a>
    </div>

    <!-- Logout -->
    <div class="mt-auto">
      <a href="../logout.php" class="btn btn-danger w-100 text-start">Logout</a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-grow-1 d-flex flex-column">
    <header class="page-header p-3 shadow-lg bg-white fw-bold text-center mb-3">College & Facilitator List</header>
    <!-- Add College Button -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCollegeModal">
        ➕ Add College
      </button>
    </div>

    <!-- ✅ Error & Success Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Search -->
    <form method="GET" class="input-group mb-3">
        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>


    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>College Code</th>
                        <th>College Name</th>
                        <th>Guidance Facilitator</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            <tbody>
            <?php
            $query = "SELECT c.college_id, c.college_code, c.college_name, 
                             f.facilitator_id, f.first_name, f.m_name, f.last_name, f.status 
                      FROM college c 
                      LEFT JOIN facilitator f ON c.college_id = f.college_id";

            if ($search !== '') {
                $query .= " WHERE c.college_code LIKE '%$search%' 
                         OR c.college_name LIKE '%$search%' 
                         OR CONCAT(f.first_name,' ',f.m_name,' ',f.last_name) LIKE '%$search%'";
            }

            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $middle = !empty($row['m_name']) ? " ".strtoupper(substr($row['m_name'],0,1))."." : "";
                    $facilitator = $row['first_name'] ? $row['first_name'].$middle." ".$row['last_name'] : '<em>No Facilitator Assigned</em>';
                    $status = $row['status'] == 1 ? "<span class='badge bg-success'>Active</span>" : "<span class='badge bg-danger'>Inactive</span>";
                    ?>
                    <tr>
                        <td><?= $row['college_code'] ?></td>
                        <td><?= $row['college_name'] ?></td>
                        <td><?= $facilitator ?></td>
                        <td><?= $status ?></td>
                        <td>
                          <?php if (!empty($row['facilitator_id'])): ?>
                              <form method="post" style="display:inline;">
                                  <input type="hidden" name="action" value="toggle_status">
                                  <input type="hidden" name="facilitator_id" value="<?= $row['facilitator_id'] ?>">
                                  <input type="hidden" name="new_status" value="<?= $row['status'] == 1 ? 0 : 1 ?>">
                                  <button type="submit" class="btn btn-sm <?= $row['status'] == 1 ? 'btn-danger' : 'btn-success' ?>">
                                      <?= $row['status'] == 1 ? 'Deactivate' : 'Activate' ?>
                                  </button>
                              </form>
                          <?php else: ?>
                              <span class="text-muted">No Facilitator</span>
                          <?php endif; ?>
                      </td>

                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>No results found.</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Add College Modal -->
<div class="modal fade" id="addCollegeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Add College</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>College Code</label>
            <input type="text" class="form-control" name="college_code" required>
          </div>
          <div class="mb-3">
            <label>College Name</label>
            <input type="text" class="form-control" name="college_name" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add College</button>
        </div>
      </form>
    </div>
  </div>
</div>
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
