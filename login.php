<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Use prepared statement to avoid SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($password, $user['password'])) {
            $_SESSION['email'] = $email;
            header("Location: role_selection.php");
            exit();
        } else {
            echo "Invalid credentials.";
        }
    } else {
        echo "Invalid credentials.";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - GuidanceHub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .header {
            background: #80d0f5;
            padding: 40px 0;
            font-size: 32px;
            font-weight: bold;
        }
        form {
            margin-top: 50px;
        }
        input[type=email], input[type=password] {
            width: 60%;
            padding: 15px;
            margin: 10px auto;
            border: none;
            border-radius: 15px;
            background: #ddd;
            display: block;
            font-size: 16px;
        }
        .options {
            display: flex;
            justify-content: space-between;
            width: 60%;
            margin: 10px auto;
            font-size: 14px;
        }
        .btn {
            background: #15d3fc;
            color: black;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 18px;
            margin-top: 20px;
            cursor: pointer;
        }
        .footer-text {
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="header">GUIDANCEHUB</div>

<form method="POST" action="login.php">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>

    <div class="options">
        <label><input type="checkbox" name="remember"> Remember</label>
        <a href="#">Forgot Password?</a>
    </div>

    <button class="btn" type="submit">Login</button>

    <div class="footer-text">
        Don't have an account? <a href="signup.php">Signup</a>
    </div>
</form>

</body>
</html>
