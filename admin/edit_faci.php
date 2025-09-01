<?php
session_start();
// if ($_SESSION['role'] !== 'admin') {
//     header("Location: login.php");
//     exit();
// }
include '../config.php';

// Check if facilitator_id is provided
if (!isset($_GET['id'])) {
    header("Location: college.php");
    exit();
}

$facilitator_id = $_GET['id'];

// Fetch facilitator and college data
$query = $conn->prepare("SELECT f.facilitator_id, f.first_name, f.m_name, f.last_name, 
                                c.college_id, c.college_name, c.college_code
                         FROM facilitator f
                         INNER JOIN college c ON f.college_id = c.college_id
                         WHERE f.facilitator_id = ?");
$query->bind_param("s", $facilitator_id);
$query->execute();
$result = $query->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: college.php");
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $college_name = $_POST['college_name'];
    $college_code = $_POST['college_code'];
    $first_name   = $_POST['first_name'];
    $m_name       = $_POST['m_name'];
    $last_name    = $_POST['last_name'];
    $college_id   = $data['college_id'];

    // Update college
    $stmt1 = $conn->prepare("UPDATE college SET college_name = ?, college_code = ? WHERE college_id = ?");
    $stmt1->bind_param("sss", $college_name, $college_code, $college_id);
    $stmt1->execute();

    // Update facilitator
    $stmt2 = $conn->prepare("UPDATE facilitator SET first_name = ?, m_name = ?, last_name = ? WHERE facilitator_id = ?");
    $stmt2->bind_param("ssss", $first_name, $m_name, $last_name, $facilitator_id);
    $stmt2->execute();

    header("Location: college.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Facilitator</title>
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
            <h4 class="mb-0">Edit Facilitator</h4> 
            <a href="college.php" class="btn btn-danger btn-sm">Back</a>
        </div>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">College Code:</label>
                <input type="text" class="form-control" name="college_code" value="<?= htmlspecialchars($data['college_code']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">College Name:</label>
                <input type="text" class="form-control" name="college_name" value="<?= htmlspecialchars($data['college_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">First Name:</label>
                <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($data['first_name']) ?>" required>
            </div>            
            
            <div class="mb-3">
                <label class="form-label">Middle Initial:</label>
                <input type="text" class="form-control" name="m_name" maxlength="1" value="<?= htmlspecialchars($data['m_name']) ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Last Name:</label>
                <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($data['last_name']) ?>" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>