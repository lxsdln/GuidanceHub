<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // ✅ Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // ✅ Handle remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32)); // raw token
            $hashed = hash('sha256', $token);   // hashed for DB
            $expires = date("Y-m-d H:i:s", time() + (30*24*60*60)); // 30 days

            $stmt = $conn->prepare("UPDATE users SET remember_token=?, remember_expire=? WHERE id=?");
            $stmt->bind_param("ssi", $hashed, $expires, $user['id']);
            $stmt->execute();

            setcookie(
                "remember_me",
                $user['id'] . ':' . $token,
                time() + (30*24*60*60),
                "/",
                "",
                true,  // secure (use HTTPS para magwork)
                true   // httponly
            );
        }

        header("Location: " . $user['role'] . "/dashboard.php");
        exit;
    } else {
        $error = "Invalid login credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - GuidanceHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Background gradient */
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #007bff, #28a745);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 15px;
    }

    /* Card */
    .card {
      border-radius: 20px;
      box-shadow: 0 6px 25px rgba(0,0,0,0.25);
      background-color: #fff;
    }

    /* Header */
    .header-title {
      font-size: 28px;
      font-weight: bold;
      background: linear-gradient(90deg, #007bff, #28a745);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    /* Custom button */
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
    .form-control:focus {
      border-color: #28a745;
      box-shadow: 0 0 0 0.2rem rgba(40,167,69,.25);
    }
  </style>
</head>
<body>
  <div class="card p-4 w-100" style="max-width: 420px;">
    <div class="text-center mb-3">
      <div class="header-title">GUIDANCEHUB</div>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="alert alert-danger" role="alert">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control" name="email" id="email" placeholder="Enter email" required>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remember" id="remember">
          <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <a href="forgot_password.php" class="text-decoration-none text-success fw-semibold">Forgot Password?</a>
      </div>

      <button type="submit" class="btn btn-custom w-100">Login</button>
    </form>

    <div class="text-center mt-3">
      <p class="mb-0">Don't have an account? <a href="signup.php" class="fw-bold text-success text-decoration-none">Signup</a></p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
