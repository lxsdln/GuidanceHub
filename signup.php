<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        echo "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            echo "Email already registered.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert into database using prepared statement
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                echo "Account created. <a href='login.php'>Login here</a>";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        }

        $check_stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Account - GuidanceHub</title>
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
        input[type=text], input[type=email], input[type=password] {
            width: 60%;
            padding: 15px;
            margin: 10px auto;
            border: none;
            border-radius: 15px;
            background: #ddd;
            display: block;
            font-size: 16px;
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

<div class="header">CREATE ACCOUNT</div>

<form method="POST" action="signup.php">
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Create Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>

    <button class="btn" type="submit">Signup</button>

    <div class="footer-text">
        Already have an account? <a href="login.php">Login</a>
    </div>
</form>

</body>
</html>
