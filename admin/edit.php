<?php
session_start();
// if ($_SESSION['role'] !== 'admin') {
//     header("Location: login.php");
//     exit();  // stop further code execution
// }
include '../config.php';

if (!isset($_GET['id'])) {
    header("Location: user_management.php");
    exit();
}

$id = $_GET['id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: user_management.php");
    exit();
}

// Update user
if ($_POST) {
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, role=? WHERE id=?");
    $stmt->bind_param("ssssi", $_POST['username'], $_POST['email'], $_POST['password'], $_POST['role'], $id);
    $stmt->execute();
    header("Location: user_management.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card p-4" style="width: 100%; max-width: 500px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Edit User</h4>
            <a href="user_management.php" class="btn btn-danger btn-sm">Back</a>
        </div>

        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role:</label>
                <select name="role" class="form-control" required>
                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="facilitator" <?= $user['role'] == 'facilitator' ? 'selected' : '' ?>>Facilitator</option>
                    <option value="professor" <?= $user['role'] == 'professor' ? 'selected' : '' ?>>Professor</option>
                    <option value="student" <?= $user['role'] == 'student' ? 'selected' : '' ?>>Student</option>
                </select>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>