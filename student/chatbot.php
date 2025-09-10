<?php
include '../config.php';

// Use user_id from session, fallback for testing
$user_id = $_SESSION['user_id'] ?? 1;

// ✅ Query student details with JOIN to college table
$stmt = $conn->prepare("SELECT s.student_id, s.first_name, s.m_name, s.last_name, s.course,
                               c.college_code, c.college_name
                        FROM student s
                        LEFT JOIN college c ON s.college_id = c.college_id
                        WHERE s.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// ✅ Debug: check kung may record talaga
if (!$student) {
    die("❌ No student record found for user_id: " . $user_id);
}

// Encode student info to JSON for JS
$student_json = json_encode($student);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Chatbot - GuidanceHub</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <style>
    html, body { height: 100%; margin: 0; padding: 0; box-sizing: border-box; }
    body { display: flex; justify-content: center; align-items: center; font-family: Arial, sans-serif; background: #f5f7fb; padding: 0; }
    .chat-container { width: 100%; max-width: 450px; height: 100%; background: #fff; display: flex; flex-direction: column; }
    .chat-header { padding: 20px; background: #86d3ff; color: #000; font-weight: bold; text-align: center; font-size: 1.2rem; border-bottom: 2px solid #ccc; }
    .chat-body { flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; font-size: 0.95rem; background: #F0FFFF; }
    .chat-bubble { max-width: 75%; padding: 10px; border-radius: 12px; word-wrap: break-word; }
    .bot { background: #e9ecef; align-self: flex-start; }
    .user { background: #3c97b7; color: #fff; align-self: flex-end; }
    .chat-footer { padding: 10px; display: flex; justify-content: center; flex-wrap: wrap; gap: 5px; position: sticky; bottom: 0; background: #fff; z-index: 10; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); }
    .btn { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; flex: 1 1 auto; min-width: 80px; }
    .btn-yes { background: #4F7942; color: #fff; }
    .btn-no { background: #dc3545; color: #fff; }
  </style>
</head>
<body>
  <div class="chat-container">
    <div class="chat-header">GuidanceHub Chatbot</div>
    <div class="chat-body" id="chatBody"></div>
    <div class="chat-footer" id="chatFooter"></div>
  </div>

<script>
const studentInfo = <?php echo $student_json; ?>;

// ✅ Debug log
console.log("Student Info:", studentInfo);

const questions = [
    "Do you currently feel stressed or overwhelmed?",
    "Are you experiencing difficulties sleeping?",
    "Have you felt sad or down most days recently?",
    "Do you often feel anxious or worried?",
    "Are you finding your academic workload challenging to manage?",
    "Do you have trouble concentrating on school tasks?",
    "Have you missed classes due to emotional or personal reasons?",
    "Do you feel you have people you can rely on for support?",
    "Are you experiencing conflicts or difficulties in your relationships?",
    "Have you felt isolated or lonely recently?",
    "Do you have a history of mental health treatment or counseling?"
];

let currentQuestion = 0;

function scrollToBottom() {
  const chatBody = document.getElementById("chatBody");
  setTimeout(() => { chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' }); }, 100);
}

function addMessage(text, sender) {
  const chatBody = document.getElementById("chatBody");
  const bubble = document.createElement("div");
  bubble.classList.add("chat-bubble", sender);
  bubble.textContent = text;
  chatBody.appendChild(bubble);
  scrollToBottom();
}

function showQuestion() {
  if (currentQuestion === 0) {
    // ✅ Greeting shown once only
    if (studentInfo && studentInfo.first_name) {
      addMessage("👋 Hi " + studentInfo.first_name + "! Welcome to GuidanceHub. We'll ask you some questions to better understand your concerns.", "bot");
    } else {
      addMessage("👋 Welcome to GuidanceHub! Let's begin.", "bot");
    }
  }

  if (currentQuestion < questions.length) {
    addMessage(questions[currentQuestion], "bot");
    document.getElementById("chatFooter").innerHTML = `
      <button class="btn btn-yes" onclick="answer('yes')">Yes</button>
      <button class="btn btn-no" onclick="answer('no')">No</button>
    `;
  } else {
    addMessage("✅ Thank you! Your answers have been submitted.", "bot");
    document.getElementById("chatFooter").innerHTML = `
      <button class="btn btn-yes" onclick="bookAppointment()">📅 Book Appointment</button>
    `;
  }
}

function answer(ans) {
  addMessage(ans.toUpperCase(), "user");

  // ✅ Debug: log student_id
  console.log("Saving answer for student_id:", studentInfo.student_id);

  fetch("save_answer.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `student_id=${studentInfo.student_id}&question_no=${currentQuestion+1}&answer=${ans}`
  })
  .then(res => res.json())
  .then(data => {
    console.log("Save response:", data);
  })
  .catch(err => {
    console.error("Save answer error:", err);
  })
  .finally(() => {
    currentQuestion++;
    setTimeout(showQuestion, 500);
  });
}

function bookAppointment() {
  window.location.href = "/Guidancehub/student/book_appointment.php";
}

// Start chatbot
showQuestion();
</script>
</body>
</html>