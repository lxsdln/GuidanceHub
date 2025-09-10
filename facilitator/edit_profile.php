<?php
include '../config.php';

// --- Check login ---
if (!isset($_SESSION['email']) || !isset($_SESSION['username'])) {
    die("Not logged in.");
}

$email = $_SESSION['email'];
$username = $_SESSION['username'];

// --- Get user info ---
$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || strtolower($user['role']) !== 'facilitator') {
    die("Access denied.");
}

$user_id = $user['id'];

// --- Get facilitator details ---
$stmt = $conn->prepare("
    SELECT f.facilitator_id, f.first_name, f.m_name, f.last_name,
           f.college_id, c.college_code, c.college_name
    FROM facilitator f
    LEFT JOIN college c ON f.college_id = c.college_id
    WHERE f.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_assoc();

if (!$details) {
    die("Facilitator record not found. Contact admin.");
}

// --- Populate variables ---
$facilitator_id = $details['facilitator_id'];
$first_name     = $details['first_name'] ?? '';
$m_name         = $details['m_name'] ?? '';
$last_name      = $details['last_name'] ?? '';
$college_id     = $details['college_id'] ?? '';
$college_code   = $details['college_code'] ?? '';
$college_name   = $details['college_name'] ?? '';

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_first     = trim($_POST['first_name']);
    $new_m         = trim($_POST['m_name']);
    $new_last      = trim($_POST['last_name']);
    $new_col_name  = trim($_POST['college_name']);
    $new_col_code  = trim($_POST['college_code']);

    // --- Handle college ---
    $college_id_to_use = $college_id;
    if (!empty($new_col_name) && !empty($new_col_code)) {
        $stmtCheck = $conn->prepare("SELECT college_id FROM college WHERE college_code=? OR college_name=?");
        $stmtCheck->bind_param("ss", $new_col_code, $new_col_name);
        $stmtCheck->execute();
        $exists = $stmtCheck->get_result()->fetch_assoc();

        if ($exists) {
            $college_id_to_use = $exists['college_id'];
        } else {
            // Generate a unique ID for new college
            $new_college_id = 'COL' . uniqid();
            $stmtInsert = $conn->prepare("INSERT INTO college (college_id, college_name, college_code) VALUES (?, ?, ?)");
            $stmtInsert->bind_param("sss", $new_college_id, $new_col_name, $new_col_code);
            $stmtInsert->execute();
            $college_id_to_use = $new_college_id;
        }
    }

    // --- Update facilitator (DO NOT update facilitator_id) ---
    $stmtUpdate = $conn->prepare("
        UPDATE facilitator
        SET first_name=?, m_name=?, last_name=?, college_id=?
        WHERE user_id=?
    ");
    $stmtUpdate->bind_param("ssssi", $new_first, $new_m, $new_last, $college_id_to_use, $user_id);
    $stmtUpdate->execute();

    header("Location: profile.php?updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Profile - Facilitator</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4>Update Profile</h4>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="m_name" class="form-control" value="<?= htmlspecialchars($m_name); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($last_name); ?>" required>
                        </div>
                        <hr>
                        <h6 class="text-primary">College (optional)</h6>
                        <div class="mb-3">
                            <label class="form-label">College Name</label>
                            <input type="text" name="college_name" class="form-control" value="<?= htmlspecialchars($college_name); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">College Code</label>
                            <input type="text" name="college_code" class="form-control" value="<?= htmlspecialchars($college_code); ?>">
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="profile.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
