<?php
// chatbot.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Chatbot - GuidanceHub</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <style>
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: #f0f9ff;
      display: flex;
      flex-direction: column;
      height: 100vh;
    }

    header {
      background-color: #4fc3f7;
      padding: 20px;
      text-align: center;
      color: white;
      font-size: 1.5rem;
      font-weight: bold;
    }

    .chat-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      padding: 16px;
      overflow-y: auto;
    }

    .chat-message {
      max-width: 70%;
      padding: 10px 14px;
      margin-bottom: 10px;
      border-radius: 12px;
    }

    .chat-user {
      align-self: flex-end;
      background-color: #d1ecf1;
    }

    .chat-bot {
      align-self: flex-start;
      background-color: #ffffff;
      border: 1px solid #cce5ff;
    }

    form.chat-input {
      display: flex;
      padding: 10px;
      background: #fff;
      border-top: 1px solid #ccc;
    }

    form.chat-input input {
      flex: 1;
      padding: 10px;
      border: 1px solid #aaa;
      border-radius: 8px;
      margin-right: 10px;
      font-size: 1rem;
    }

    form.chat-input button {
      background-color: #4fc3f7;
      border: none;
      border-radius: 8px;
      padding: 10px 16px;
      color: white;
      font-size: 1rem;
      cursor: pointer;
    }

    form.chat-input button:hover {
      background-color: #03a9f4;
    }
  </style>
</head>
<body>

<header>Guidance Chatbot</header>

<div class="chat-container" id="chatBox">
  <div class="chat-message chat-bot">Hi! Welcome to GuidanceHub, your digital companion 
for guidance services at CatSU-CICT. How can I assist you today?</div>
  <!-- More messages will be appended here -->
</div>

<form class="chat-input" onsubmit="handleUserMessage(event)">
  <input type="text" id="userInput" placeholder="Type your message..." autocomplete="off" required />
  <button type="submit">Send</button>
</form>

<script>
  function handleUserMessage(event) {
    event.preventDefault();
    const input = document.getElementById("userInput");
    const message = input.value.trim();
    if (!message) return;

    const chatBox = document.getElementById("chatBox");

    // Display user message
    const userMsg = document.createElement("div");
    userMsg.className = "chat-message chat-user";
    userMsg.textContent = message;
    chatBox.appendChild(userMsg);

    // Placeholder bot reply
    const botReply = document.createElement("div");
    botReply.className = "chat-message chat-bot";
    botReply.textContent = "Thank you for your message. A response will appear here soon.";
    chatBox.appendChild(botReply);

    input.value = "";
    chatBox.scrollTop = chatBox.scrollHeight;
  }
</script>

</body>
</html>
