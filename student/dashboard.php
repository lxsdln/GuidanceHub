<?php
include '../config.php';

// ✅ Restrict access to students only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // from users table

// ✅ Fetch the student's unique student_id
$stmt = $conn->prepare("SELECT student_id, first_name, last_name FROM student WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    die("Student record not found.");
}

$student_id   = $res['student_id']; // use the varchar student_id
$student_name = $res['first_name'] . ' ' . $res['last_name'];


// ✅ Upcoming appointments
// ✅ Upcoming appointments
$sql_upcoming = "SELECT COUNT(*) as total 
                 FROM appointments 
                 WHERE student_id = ? 
                 AND appointment_date >= CURDATE()";
$stmt = $conn->prepare($sql_upcoming);
$stmt->bind_param("s", $student_id); // "s" because varchar
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$upcomingAppointments = $result['total'] ?? 0;

// ✅ Past sessions
$sql_past = "SELECT COUNT(*) as total 
             FROM appointments 
             WHERE student_id = ? 
             AND appointment_date < CURDATE()";
$stmt = $conn->prepare($sql_past);
$stmt->bind_param("s", $student_id); // also "s"
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$pastSessions = $result['total'] ?? 0;


// ✅ Past sessions
$sql_past = "SELECT COUNT(*) as total 
             FROM appointments 
             WHERE student_id = ? 
             AND appointment_date < CURDATE()";
$stmt = $conn->prepare($sql_past);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$pastSessions = $result['total'] ?? 0;

// Daily motivational quotes
$quotes = [
    "Believe in yourself and all that you are. You are capable of amazing things.",
    "Small steps every day lead to big achievements.",
    "Take it one day at a time—you’re doing better than you think.",
    "Your mental health is a priority. Take time to care for yourself.",
    "Every challenge is an opportunity to grow stronger.",
    "Progress is progress, no matter how small.",
    "It’s okay to ask for help—strength is in seeking support.",
    "Focus on what you can control, and let go of what you cannot.",
    "Success is the sum of small efforts, repeated day in and day out.",
    "Don’t compare your journey to anyone else’s. Your path is unique.",
    "A healthy mind is just as important as a healthy body.",
    "Mistakes are proof that you are trying—keep going.",
    "Take breaks, breathe, and give yourself grace.",
    "Believe you can, and you’re halfway there.",
    "Learning is a journey, not a race—enjoy every step."
];
$dayOfYear = date('z'); // 0-365
$quoteIndex = $dayOfYear % count($quotes);
$dailyQuote = $quotes[$quoteIndex];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="../style.css">
  <style>
    .daily-quote { background-color:#f0f8ff; padding:15px; border-radius:8px; margin-bottom:20px; font-size:1.1rem; }
    .quick-cards { display:flex; gap:15px; margin-bottom:20px; flex-wrap:wrap; }
    .quick-card { flex:1; min-width:120px; background-color:#d0f0c0; padding:15px; border-radius:8px; text-align:center; }
    .message-btn { position: fixed; bottom: 24px; right: 24px; background-color: #4fc3f7; border:none; border-radius:50%; width:56px; height:56px; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: background-color 0.3s ease; }
    .message-btn:hover { background-color: #03a9f4; }
    .message-btn .material-icons { color:white; font-size:28px; }
    .about-guidance { background-color:#e8f5e9; padding:15px; border-radius:8px; font-size:1rem; margin-bottom:20px; }
    body {
      display: flex;
      min-height: 100vh;
      margin: 0;
      background-color: #f8f9fa;
    }

    /* Sidebar */
    .sidebar {
      background: linear-gradient(180deg, #007bff, #28a745); /* Blue → Green gradient */
      color: white;
      min-height: 100vh;
      position: fixed;
      transition: all 0.3s ease;
      z-index: 1000;
    }
    .sidebar.collapsed {
      width: 0;
      padding: 0;
      overflow: hidden;
    }
    .sidebar a {
      color: white;
      font-weight: 500;
    }
    .sidebar a:hover {
      background-color: rgba(255, 255, 255, 0.15);
    }

    /* Main */
    main {
      flex-grow:1;
      padding:20px;
      margin-left: 250px;
      transition: margin-left 0.3s ease;
      width: calc(100% - 250px);
    }
    main.full-width {
      margin-left: 0;
      width: 100%;
    }

    /* Mobile toggle */
    .sidebar-toggle {
      position: fixed;
      top: 15px;
      left: 15px;
      z-index: 1100;
      background-color: #007bff;
      color: white;
      border-radius: 50%;
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        left: -250px;
        top: 0;
        height: 100%;
        width: 250px;
      }
      .sidebar.collapsed {
        left: 0;
      }
      main {
        margin-left: 0;
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar Toggle (Mobile) -->
  <button class="btn sidebar-toggle d-md-none" id="sidebarToggle">
    <span class="material-icons">menu</span>
  </button>

  <!-- Sidebar -->
  <aside class="sidebar d-flex flex-column p-3" style="width:250px;">
    <div class="profile-image mb-3 text-center">
      <span class="material-icons" style="font-size:48px;">person</span>
    </div>

    <div class="user-name mb-4 text-center">
      <?php echo htmlspecialchars($_SESSION['username']); ?>
      <a href="profile.php" title="Edit Profile" class="ms-2 text-white text-decoration-none">
        <span class="material-icons" style="vertical-align: middle;">edit</span>
      </a>
    </div>

    <!-- Sidebar Links -->
    <div class="flex-grow-1">
      <a href="dashboard.php" class="btn btn-outline-light w-100 text-start mb-2">Dashboard</a>
      <a href="appointment.php" class="btn btn-outline-light w-100 text-start mb-2">Appointment</a>
    </div>

    <!-- Logout -->
    <div class="mt-auto">
      <a href="../logout.php" class="btn btn-danger w-100 text-start">Logout</a>
    </div>
  </aside>

  <!-- Main Content -->
  <main>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

    <!-- Quote for Today -->
    <div class="daily-quote">
      <h5>Quote for Today — <?php echo date("F j, Y"); ?></h5>
      <p>💡 <?php echo $dailyQuote; ?></p>
    </div>

    <!-- Quick Cards -->
    <div class="quick-cards">
      <div class="quick-card">
        Upcoming Appointments <br><strong><?php echo $upcomingAppointments; ?></strong>
      </div>
      <div class="quick-card">
        Past Sessions <br><strong><?php echo $pastSessions; ?></strong>
      </div>
    </div>

    <!-- Wellness Tip -->
    <div class="daily-quote" style="background-color:#fff3cd;">
      🧘 Wellness Tip: Take a 5-minute break every hour to refresh your mind.
    </div>

    <!-- About Guidance Section -->
    <div class="about-guidance">
      <h5>About Guidance</h5>
      <p>
        The Guidance Office is here to support your personal, academic, and emotional growth.
        You can schedule appointments, seek advice, submit referral forms, and access resources
        that help you succeed in school and manage stress effectively.
      </p>
    </div>
    
  </main>

  <!-- Chatbot Message Button -->
  <form method="POST" action="chatbot.php">
    <button class="message-btn" type="submit" title="Messages">
      <span class="material-icons">message</span>
    </button>
  </form>

</div>
  <script>
    // Sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('main');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      main.classList.toggle('full-width');
    });
  </script>
</body>
</html>
