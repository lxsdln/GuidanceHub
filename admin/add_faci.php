<?php
session_start();
// if ($_SESSION['role'] !== 'admin') {
//     header("Location: login.php");
//     exit();  // stop further code execution
// }
include '../config.php';

if ($_POST) {
    // Generate a unique college_id
    $college_id = uniqid("COL_");  
    
    // Insert into college table
    $stmt1 = $conn->prepare("INSERT INTO college (college_id, college_name, college_code) VALUES (?, ?, ?)");
    $stmt1->bind_param("sss", $college_id, $_POST['college_name'], $_POST['college_code']);
    $stmt1->execute();

    // Generate unique facilitator_id
    $facilitator_id = uniqid("FAC_");

    // Insert into facilitator table (with middle name)
    $stmt2 = $conn->prepare("INSERT INTO facilitator (facilitator_id, first_name, m_name, last_name, college_id) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param("sssss", $facilitator_id, $_POST['first_name'], $_POST['m_name'], $_POST['last_name'], $college_id);
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
            <a href="college.php" class="btn btn-danger btn-sm">Back</a>
        </div>

        <form method="post">
            <div class="mb-3">
                <label for="code" class="form-label">College Code:</label>
                <input type="text" class="form-control" name="college_code" required>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">College Name:</label>
                <input type="text" class="form-control" name="college_name" required>
            </div>

            <div class="mb-3">
                <label for="first" class="form-label">First Name:</label>
                <input type="text" class="form-control" name="first_name" required>
            </div>            
            
            <div class="mb-3">
                <label for="mname" class="form-label">Middle Initial:</label>
                <input type="text" class="form-control" name="m_name">
            </div>
            
            <div class="mb-3">
                <label for="last" class="form-label">Last Name:</label>
                <input type="text" class="form-control" name="last_name" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Add</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
