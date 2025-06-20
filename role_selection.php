<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Role</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        h2 {
            font-size: 32px;
            margin-bottom: 40px;
        }

        form {
            display: flex;
            justify-content: center;
            gap: 60px;
        }

        .role-btn {
            background: none;
            border: none;
            text-align: center;
            cursor: pointer;
        }

        .role-btn i {
            font-size: 100px;
            color: #B197FC;
            margin-bottom: 10px;
        }

        .role-btn span {
            display: block;
            font-size: 18px;
            margin-top: 10px;
            color: #000;
        }

        .role-btn:hover i {
            color: #8c77ea;
        }
    </style>
</head>
<body>

    <h2>Select Role</h2>

    <form action="role_redirect.php" method="post">
        <button class="role-btn" type="submit" name="role" value="student">
            <i class="fa-solid fa-circle-user"></i>
            <span>STUDENT</span>
        </button>

        <button class="role-btn" type="submit" name="role" value="professor">
            <i class="fa-solid fa-circle-user"></i>
            <span>PROFESSOR</span>
        </button>
    </form>

</body>
</html>
