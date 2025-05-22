<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$otherUserId = $_GET['user_id'] ?? 0;

// Verify this is a valid match
$stmt = $conn->prepare("
    SELECT 1 FROM matches 
    WHERE (user1_id = ? AND user2_id = ?)
    OR (user1_id = ? AND user2_id = ?)
");
$stmt->bind_param("iiii", $currentUserId, $otherUserId, $otherUserId, $currentUserId);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    header("Location: likes.php");
    exit();
}

// Get other user's info
$stmt = $conn->prepare("SELECT fullname, photos FROM users WHERE id = ?");
$stmt->bind_param("i", $otherUserId);
$stmt->execute();
$otherUser = $stmt->get_result()->fetch_assoc();
$otherUserPhoto = json_decode($otherUser['photos'], true)[0] ?? 'default_profile.jpg';

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $currentUserId, $otherUserId, $message);
        $stmt->execute();
    }
}

// Get conversation history
$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?)
    OR (sender_id = ? AND receiver_id = ?)
    ORDER BY sent_at ASC
");
$stmt->bind_param("iiii", $currentUserId, $otherUserId, $otherUserId, $currentUserId);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat with <?= htmlspecialchars($otherUser['fullname']) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f6fa;
        }
        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .chat-header {
            background: #ff4e74;
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .chat-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f0f2f5;
        }
        .message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .message.sent {
            align-items: flex-end;
        }
        .message.received {
            align-items: flex-start;
        }
        .message-content {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            margin-bottom: 5px;
            word-wrap: break-word;
        }
        .sent .message-content {
            background: #dcf8c6;
            border-top-right-radius: 0;
        }
        .received .message-content {
            background: white;
            border-top-left-radius: 0;
        }
        .message-time {
            font-size: 12px;
            color: #666;
        }
        .chat-input {
            padding: 15px;
            background: white;
            display: flex;
            position: sticky;
            bottom: 0;
            border-top: 1px solid #ddd;
        }
        .chat-input textarea {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 10px 15px;
            resize: none;
            outline: none;
            font-family: inherit;
            font-size: 14px;
            height: 40px;
            max-height: 100px;
        }
        .chat-input button {
            background: #ff4e74;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            margin-left: 10px;
            cursor: pointer;
        }
        .back-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            margin-right: 15px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <button class="back-btn" onclick="window.history.back()">←</button>
            <img src="<?= htmlspecialchars($otherUserPhoto) ?>" alt="<?= htmlspecialchars($otherUser['fullname']) ?>">
            <h2><?= htmlspecialchars($otherUser['fullname']) ?></h2>
        </div>
        
        <div class="chat-messages" id="messagesContainer">
            <?php foreach ($messages as $message): 
                $isSent = $message['sender_id'] == $currentUserId;
                $time = date("h:i A", strtotime($message['sent_at']));
            ?>
                <div class="message <?= $isSent ? 'sent' : 'received' ?>">
                    <div class="message-content"><?= htmlspecialchars($message['message']) ?></div>
                    <div class="message-time"><?= $time ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <form class="chat-input" method="POST" onsubmit="return sendMessage()">
            <textarea name="message" id="messageInput" placeholder="Type a message..." required></textarea>
            <button type="submit">→</button>
        </form>
    </div>

   <?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$otherUserId = $_GET['user_id'] ?? 0;

// [Keep all your existing verification and message handling code]
// Only updating the HTML/JS part
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat with <?= htmlspecialchars($otherUser['fullname']) ?></title>
    <style>
        /* Keep all your existing styles exactly the same */
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <button class="back-btn" onclick="window.history.back()">←</button>
            <img src="<?= htmlspecialchars($otherUserPhoto) ?>" alt="<?= htmlspecialchars($otherUser['fullname']) ?>">
            <h2><?= htmlspecialchars($otherUser['fullname']) ?></h2>
        </div>
        
        <div class="chat-messages" id="messagesContainer">
            <?php foreach ($messages as $message): 
                $isSent = $message['sender_id'] == $currentUserId;
                $time = date("h:i A", strtotime($message['sent_at']));
            ?>
                <div class="message <?= $isSent ? 'sent' : 'received' ?>">
                    <div class="message-content"><?= htmlspecialchars($message['message']) ?></div>
                    <div class="message-time"><?= $time ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <form class="chat-input" id="chatForm">
            <textarea name="message" id="messageInput" placeholder="Type a message..." required></textarea>
            <button type="submit">→</button>
        </form>
    </div>

    <script>
        // Auto-scroll to bottom
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            container.scrollTop = container.scrollHeight;
        }
        
        // Load new messages
        function loadMessages() {
            fetch(`get_messages.php?user_id=<?= $otherUserId ?>`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('messagesContainer').innerHTML = html;
                    scrollToBottom();
                });
        }
        
        // Send message via AJAX
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (message === '') return;
            
            fetch('chat.php?user_id=<?= $otherUserId ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `message=${encodeURIComponent(message)}`
            })
            .then(() => {
                messageInput.value = '';
                loadMessages(); // Refresh messages after sending
            });
        });
        
        // Poll for new messages every 2 seconds
        setInterval(loadMessages, 2000);
        
        // Initial scroll to bottom
        scrollToBottom();
    </script>
</body>
</html>