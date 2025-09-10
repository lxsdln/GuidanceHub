<?php
include 'config.php';
require './helper/otp_mail.php';

// --- Initialize error message ---
$error = "";

// --- Handle form submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim and normalize input
    $first_name = trim($_POST['first_name']);
    $m_name     = trim($_POST['m_name']);
    $last_name  = trim($_POST['last_name']);
    $username   = trim(strtolower($_POST['username']));
    $email      = trim(strtolower($_POST['email']));
    $password   = $_POST['password'];
    $confirm_pw = $_POST['confirm_password'];
    $role       = $_POST['role'];

    // --- Password validation ---
    $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
    if (!preg_match($pattern, $password)) {
        $error = "❌ Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
    } elseif ($password !== $confirm_pw) {
        $error = "❌ Passwords do not match.";
    } else {
        // --- Check if username/email already exists (case-insensitive) ---
        $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(username)=? OR LOWER(email)=? LIMIT 1");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "❌ Username or Email already exists.";
        } else {
            // --- Hash password ---
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // --- Insert into users table ---
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $passwordHash, $role);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;

                // --- Insert into role-specific table ---
                switch ($role) {
                    case 'student':
                        $student_id = uniqid("STU");
                        $stmt2 = $conn->prepare("INSERT INTO student (student_id, first_name, m_name, last_name, user_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt2->bind_param("ssssi", $student_id, $first_name, $m_name, $last_name, $user_id);
                        $stmt2->execute();
                        break;

                    case 'facilitator':
                        $facilitator_id = uniqid("FAC");
                        $stmt2 = $conn->prepare("INSERT INTO facilitator (facilitator_id, first_name, m_name, last_name, user_id, status) VALUES (?, ?, ?, ?, ?, 1)");
                        $stmt2->bind_param("ssssi", $facilitator_id, $first_name, $m_name, $last_name, $user_id);
                        $stmt2->execute();
                        break;

                    case 'professor':
                        $professor_id = uniqid("PROF");
                        $stmt2 = $conn->prepare("INSERT INTO professor (professor_id, first_name, m_name, last_name, user_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt2->bind_param("ssssi", $professor_id, $first_name, $m_name, $last_name, $user_id);
                        $stmt2->execute();
                        break;

                    case 'admin':
                        $admin_id = uniqid("ADM");
                        $stmt2 = $conn->prepare("INSERT INTO admin (admin_id, first_name, m_name, last_name, email, user_id) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt2->bind_param("sssssi", $admin_id, $first_name, $m_name, $last_name, $email, $user_id);
                        $stmt2->execute();
                        break;
                }

                // --- Generate OTP ---
                $otp = rand(100000, 999999);
                $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                $stmtOtp = $conn->prepare("UPDATE users SET otp=?, otp_expiry=?, is_verified=0 WHERE id=?");
                $stmtOtp->bind_param("ssi", $otp, $otp_expiry, $user_id);
                $stmtOtp->execute();

                // --- Send OTP email ---
                sendOTPEmail($email, $otp);

                // Redirect to verification page
                header("Location: signup.php?verify=1&user_id=$user_id");
                exit();
            } else {
                $error = "❌ Database error: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account - GuidanceHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    /* Background gradient overlay */
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #007bff, #28a745);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 15px;
    }

    /* Card container */
    .card {
      border-radius: 20px;
      box-shadow: 0 6px 25px rgba(0,0,0,0.2);
      background-color: #fff;
    }

    /* Header */
    .header-title {
      font-size: 26px;
      font-weight: bold;
      color: #007bff;
    }

    /* Buttons */
    .btn-custom {
      background: linear-gradient(90deg, #007bff, #28a745);
      border: none;
      color: #fff;
      font-weight: bold;
      transition: 0.3s;
    }
    .btn-custom:hover {
      background: linear-gradient(90deg, #28a745, #007bff);
      transform: translateY(-2px);
    }

    /* Input focus */
    .form-control:focus, .form-select:focus {
      border-color: #28a745;
      box-shadow: 0 0 0 0.2rem rgba(40,167,69,.25);
    }

    /* Modal header */
    .modal-header {
      background: linear-gradient(90deg, #007bff, #28a745);
      color: #fff;
    }
  </style>
</head>
<body>

  <div class="card p-4 w-100" style="max-width: 420px;">
    <div class="text-center mb-3">
      <div class="header-title">Create Account</div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" id="signupForm">
      <div class="row g-2 mb-3">
        <div class="col-5"><input type="text" name="first_name" class="form-control" placeholder="First Name" required></div>
        <div class="col-2"><input type="text" name="m_name" class="form-control text-center" placeholder="M.I." maxlength="1"></div>
        <div class="col-5"><input type="text" name="last_name" class="form-control" placeholder="Last Name" required></div>
      </div>

      <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
      <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
      <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="Create Password" required>
        <small class="text-muted">At least 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char.</small>
      </div>
      <div class="mb-3"><input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required></div>

      <div class="mb-3">
        <select name="role" class="form-select" required>
          <option value="">-- Choose Role --</option>
          <option value="student">Student</option>
          <option value="facilitator">Facilitator</option>
          <option value="professor">Professor</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <div class="d-grid"><button class="btn btn-custom btn-lg" type="submit">Sign Up</button></div>
      <div class="text-center mt-3">
        <small>Already have an account? <a href="login.php" class="fw-bold text-decoration-none text-success">Login</a></small>
      </div>
    </form>
  </div>

  <?php if (isset($_GET['verify']) && $_GET['verify'] == 1): ?>
  <!-- OTP Modal -->
  <div class="modal fade show" id="otpModal" style="display:block;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 shadow-lg">
        <div class="modal-header">
          <h5 class="modal-title">Verify Your Account</h5>
        </div>
        <div class="modal-body">
          <form method="POST" action="verify_otp.php">
            <input type="hidden" name="user_id" value="<?= $_GET['user_id'] ?>">
            <div class="mb-3">
              <label class="form-label">Enter OTP</label>
              <input type="text" class="form-control" name="otp" required>
            </div>
            <button type="submit" class="btn btn-custom w-100">Verify</button>
          </form>
          <form method="POST" action="resend_otp.php" class="mt-2">
            <input type="hidden" name="user_id" value="<?= $_GET['user_id'] ?>">
            <button type="submit" class="btn btn-link w-100">Resend OTP</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>