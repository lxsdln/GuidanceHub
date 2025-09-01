<?php
session_start();
// if ($_SESSION['role'] !== 'admin') {
//     header("Location: login.php");
//     exit();  // stop further code execution
// }
include '../config.php';

if ($_POST) {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $_POST['username'], $_POST['email'], $_POST['password'], $_POST['role']);
    $stmt->execute();
    header("Location: user_management.php");
    exit();  // stop further code execution
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
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
            <h4 class="mb-0">Add User</h4> 
            <a href="user_management.php" class="btn btn-danger btn-sm">Back</a>
        </div>

        <form method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Username:</label>
                <input type="text" class="form-control" name="username" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" name="email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control"  name="password" required>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role:</label>
                <select name="role" class="form-control" required>
                    <option value="admin">Admin</option>
                    <option value="facilitator">Facilitator</option>
                    <option value="professor">Professor</option>
                    <option value="student">student</option>
                </select>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Add</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
