<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Appointment Booking</title>
  <!-- Google Material Icons CDN -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <style>
    * {
      box-sizing: border-box;
    }
    body, html {
      margin: 0;
      padding: 0;
      font-family: 'Inter', sans-serif;
      background: #ffffff;
      color: #111;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .app-container {
      flex: 1;
      display: grid;
      grid-template-columns: 240px 1fr;
      height: 100vh;
      position: relative;
    }

    aside.sidebar {
      background-color: #a2e1ca;
      padding: 32px 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 48px;
    }

    aside .profile-image {
      background-color: #ddd6fe;
      border-radius: 50%;
      width: 130px;
      height: 130px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #4c2882;
      font-size: 70px;
    }

    aside .user-name {
      font-weight: 700;
      font-size: 1.25rem;
      display: flex;
      align-items: center;
      gap: 6px;
      color: #000;
    }

    aside .user-name .material-icons {
      font-size: 20px;
      cursor: pointer;
      color: #333;
      transition: color 0.3s ease;
    }
    aside .user-name .material-icons:hover {
      color: #4c2882;
    }

    aside button {
      width: 160px;
      padding: 12px 0;
      color: #000;
      font-size: 1rem;
      font-weight: 500;
      background-color: #4fc3f7;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    aside button:hover {
      background-color: #03a9f4;
      color: #fff;
    }

    main.content {
      background: #fff;
      display: flex;
      flex-direction: column;
      position: relative;
    }

    header.page-header {
      background-color: #6ec1e4;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      padding: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1.8rem;
      color: #000;
      user-select: none;
    }

    .message-btn {
      position: absolute;
      bottom: 24px;
      left: 24px; 
      background-color: #4fc3f7;
      border: none;
      border-radius: 50%;
      width: 56px;
      height: 56px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .message-btn .material-icons {
      color: white;
      font-size: 28px;
    }

    .message-btn:hover {
      background-color: #03a9f4;
    }

    @media (max-width: 767px) {
      .app-container {
        grid-template-columns: 1fr;
        height: auto;
        min-height: 100vh;
      }

      aside.sidebar {
        flex-direction: row;
        justify-content: space-around;
        padding: 16px 0;
        width: 100%;
      }
      aside .profile-image {
        width: 70px;
        height: 70px;
        font-size: 35px;
      }
      aside .user-name {
        font-size: 1rem;
      }
      main.content header.page-header {
        font-size: 1.4rem;
        padding: 16px;
      }
    }
  </style>
</head>
<body>

<div class="app-container" role="main">
  <aside class="sidebar" aria-label="User profile and navigation">
    <div class="profile-image" aria-hidden="true">
      <span class="material-icons" aria-hidden="true">person</span>
    </div>
    <div class="user-name">
      Student
      <span class="material-icons" role="button" aria-label="Edit profile name" tabindex="0">edit</span>
    </div>
    <button id="bookAppointmentBtn" type="button">Book Appointment</button>
    <form id="logoutForm" method="POST" action="../logout.php" style="width: 100%;">
      <button type="submit" style="width: 100%;">Logout</button>
    </form>
  </aside>

  <main class="content" tabindex="-1">
    <header class="page-header" role="banner">
      Book Appointment
    </header>

    <form method="POST" action="chatbot.php">
      <button class="message-btn" type="submit" title="Messages">
        <span class="material-icons">message</span>
      </button>
    </form>
  </main>
</div>

</body>
</html>
