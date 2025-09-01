<?php
session_start();
include '../config.php';
$student_id = $_SESSION['user_id'] ?? 1; // fallback for testing
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Chatbot - GuidanceHub</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: Arial, sans-serif;
      background: #f5f7fb;
      padding: 0;
    }

    .chat-container {
      width: 100%;
      max-width: 450px;
      height: 100%; /* Full viewport height */
      background: #fff;
      border-radius: 0; /* optional: remove rounding for full screen */
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .chat-header {
      padding: 15px;
      background: #0d6efd;
      color: #fff;
      font-weight: bold;
      text-align: center;
      font-size: 1.2rem;
    }

    .chat-body {
      flex: 1;
      padding: 15px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 10px;
      font-size: 0.95rem;
    }

    .chat-bubble {
      max-width: 75%;
      padding: 10px;
      border-radius: 12px;
      word-wrap: break-word;
    }

    .bot { background: #e9ecef; align-self: flex-start; }
    .user { background: #0d6efd; color: #fff; align-self: flex-end; }

    .chat-footer {
      padding: 10px;
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 5px;
      position: sticky;
      bottom: 0;
      background: #fff;
      z-index: 10;
      box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
    }

    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      flex: 1 1 auto;
      min-width: 80px;
    }

    .btn-yes { background: #28a745; color: #fff; }
    .btn-no { background: #dc3545; color: #fff; }

    #personalAnswer {
      flex: 1 1 auto;
      min-width: 100px;
      padding: 8px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    @media (max-width: 500px) {
      .chat-header { font-size: 1rem; padding: 12px; }
      .chat-body { padding: 10px; font-size: 0.9rem; }
      .btn { padding: 6px 12px; font-size: 0.85rem; }
      #personalAnswer { padding: 6px; }
    }
  </style>
</head>
<body>
  <div class="chat-container">
    <div class="chat-header">GuidanceHub Chatbot</div>
    <div class="chat-body" id="chatBody"></div>
    <div class="chat-footer" id="chatFooter"></div>
  </div>

<script>
const studentId = <?php echo $student_id; ?>;

const personalQuestions = [
  { question: "What is your first name?", key: "first_name" },
  { question: "What is your middle name?", key: "m_name" },
  { question: "What is your last name?", key: "last_name" },
  { question: "What course are you taking?", key: "course" },
  { question: "Which college are you in?", key: "college_code" }
];

const questions = [
  "Do you often experience academic-related stress?",
  "Do you sometimes feel anxious or overwhelmed because of school?",
  "Do personal or family issues affect your studies?",
  "Do you face financial difficulties that affect your academic performance?",
  "Do you find it hard to manage your time between school and personal life?",
  "Do you feel comfortable sharing your concerns with the guidance office?",
  "Do you prefer to keep your personal problems to yourself rather than seek help?",
  "Do you think having an online system like GuidanceHub will make it easier to raise your concerns?",
  "Do you believe your concerns will be kept confidential when shared with the guidance office?",
  "Do you think seeking help from the guidance office can improve your well-being and academic performance?",
  "Do you often struggle to understand your lessons?",
  "Do you find it difficult to complete assignments on time?",
  "Do you feel overwhelmed by your academic workload?",
  "Have you ever thought about dropping a subject because of poor performance?",
  "Do you find it difficult to focus or concentrate during class?",
  "Do you experience conflicts at home that affect your well-being?",
  "Do you feel unsupported by your family in your academic journey?",
  "Do you have difficulties balancing your personal responsibilities and school tasks?",
  "Do you feel isolated or disconnected from your family?",
  "Do you sometimes feel sad or hopeless without knowing why?",
  "Do you have difficulty managing your emotions?",
  "Do you experience low self-confidence or self-esteem?",
  "Do you sometimes feel left out or excluded by classmates or peers?",
  "Have you ever been bullied (online or in person) by other students?",
  "Do you feel uncomfortable participating in group activities or class discussions?"
];

let personalStep = 0;
let currentQuestion = 0;
let personalAnswers = {};

function setFullHeight() {
  const chatContainer = document.querySelector('.chat-container');
  chatContainer.style.height = `${window.innerHeight}px`;
}
window.addEventListener('resize', setFullHeight);
setFullHeight();

function scrollToBottom() {
  const chatBody = document.getElementById("chatBody");
  setTimeout(() => {
    chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
  }, 100);
}

function addMessage(text, sender) {
  const chatBody = document.getElementById("chatBody");
  const bubble = document.createElement("div");
  bubble.classList.add("chat-bubble", sender);
  bubble.textContent = text;
  chatBody.appendChild(bubble);
  scrollToBottom();
}

function showPersonalQuestion() {
  if (personalStep < personalQuestions.length) {
    const q = personalQuestions[personalStep].question;
    addMessage(q, "bot");
    document.getElementById("chatFooter").innerHTML = `
      <input type="text" id="personalAnswer" placeholder="Type here..." />
      <button class="btn btn-yes" onclick="answerPersonal()">Submit</button>
    `;
    
    const input = document.getElementById("personalAnswer");
    input.focus();
    input.addEventListener("keydown", function(e) {
      if (e.key === "Enter") answerPersonal();
    });
  } else {
    document.getElementById("chatFooter").innerHTML = "";
    sendPersonalInfo();
  }
}

function answerPersonal() {
  const input = document.getElementById("personalAnswer");
  const val = input.value.trim();
  if (!val) return;
  addMessage(val, "user");
  personalAnswers[personalQuestions[personalStep].key] = val;
  personalStep++;
  setTimeout(showPersonalQuestion, 300);
}

async function sendPersonalInfo() {
  const formData = new URLSearchParams();
  formData.append("student_id", studentId);
  for (const key in personalAnswers) formData.append(key, personalAnswers[key]);

  try {
    const res = await fetch("save_answer.php", { method: "POST", body: formData });
    const data = await res.json();
    console.log(data.message);
  } catch (err) {
    console.error("Error saving personal info:", err);
  } finally {
    showQuestion();
  }
}

function showQuestion() {
  if (currentQuestion < questions.length) {
    addMessage(questions[currentQuestion], "bot");
    document.getElementById("chatFooter").innerHTML = `
      <button class="btn btn-yes" onclick="answer('yes')" id="btnYes">Yes</button>
      <button class="btn btn-no" onclick="answer('no')" id="btnNo">No</button>
    `;

    document.addEventListener("keydown", function handler(e) {
      if (e.key === "ArrowLeft" || e.key === "ArrowDown") {
        answer('no'); document.removeEventListener("keydown", handler);
      } else if (e.key === "ArrowRight" || e.key === "ArrowUp" || e.key === "Enter") {
        answer('yes'); document.removeEventListener("keydown", handler);
      }
    });
  } else {
    addMessage("✅ Thank you! Your answers have been submitted.", "bot");
    document.getElementById("chatFooter").innerHTML = "";
  }
}

function answer(ans) {
  addMessage(ans.toUpperCase(), "user");
  fetch("save_answer.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `student_id=${studentId}&question_no=${currentQuestion+1}&answer=${ans}`
  })
  .then(res => res.json())
  .then(data => {
    console.log(data.message);
    currentQuestion++;
    setTimeout(showQuestion, 500);
  });
}

// Swipe gestures
let touchStartX = 0;
let touchEndX = 0;
const chatContainer = document.querySelector(".chat-container");

chatContainer.addEventListener("touchstart", e => { touchStartX = e.changedTouches[0].screenX; });
chatContainer.addEventListener("touchend", e => {
  touchEndX = e.changedTouches[0].screenX;
  const delta = touchEndX - touchStartX;
  const threshold = 50;
  if (delta > threshold) answer('no');
  else if (delta < -threshold) answer('yes');
});

// Start the chatbot
showPersonalQuestion();
</script>
</body>
</html>
