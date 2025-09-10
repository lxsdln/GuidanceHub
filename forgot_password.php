<?php
include 'config.php';
include './helper/reset_password_mail.php'; // PHPMailer function

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $token = bin2hex(random_bytes(16));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Save token in DB
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $stmt->bind_param("ssi", $token, $expires, $user['id']);
        $stmt->execute();

        // Prepare reset link
        $reset_link = "http://localhost/Guidancehub/reset_password.php?token=$token";

        // Send email using PHPMailer
        if (sendResetEmail($email, $reset_link)) {
            $_SESSION['success'] = "Password reset link sent to your email.";
        } else {
            $_SESSION['error'] = "Failed to send email. Check SMTP settings.";
        }
    } else {
        $_SESSION['error'] = "Email not found.";
    }

    header("Location: forgot_password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - GuidanceHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #007bff, #28a745);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 15px;
    }
    .card {
      border-radius: 20px;
      box-shadow: 0 6px 25px rgba(0,0,0,0.25);
      background-color: #fff;
      padding: 30px;
      max-width: 400px;
      width: 100%;
    }
    .btn-custom {
      background: linear-gradient(90deg, #007bff, #28a745);
      border: none;
      color: #fff;
      font-weight: bold;
    }
    .btn-custom:hover {
      background: linear-gradient(90deg, #28a745, #007bff);
    }
  </style>
</head>
<body>

<div class="card text-center">
    <h2 class="mb-4">Forgot Password</h2>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3 text-start">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
        </div>
        <button type="submit" class="btn btn-custom w-100">Send Reset Link</button>
    </form>

    <p class="mt-3 mb-0">Remembered your password? <a href="login.php" class="fw-bold text-success text-decoration-none">Login</a></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
