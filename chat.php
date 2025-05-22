<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$otherUserId = $_GET['user_id'] ?? null;

// Verify they are matched
if ($otherUserId) {
    $stmt = $conn->prepare("SELECT id FROM matches 
                           WHERE (user1_id = ? AND user2_id = ?) 
                           OR (user1_id = ? AND user2_id = ?)");
    $stmt->bind_param("iiii", $currentUserId, $otherUserId, $otherUserId, $currentUserId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        header("Location: likes.php");
        exit();
    }
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['receiver_id'])) {
    $message = $_POST['message'];
    $receiverId = (int)$_POST['receiver_id'];
    
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $currentUserId, $receiverId, $message);
    $stmt->execute();
    
    header("Location: chat.php?user_id=$receiverId");
    exit();
}

// Get conversation history
$messages = [];
if ($otherUserId) {
    $stmt = $conn->prepare("SELECT * FROM messages 
                           WHERE (sender_id = ? AND receiver_id = ?) 
                           OR (sender_id = ? AND receiver_id = ?) 
                           ORDER BY sent_at ASC");
    $stmt->bind_param("iiii", $currentUserId, $otherUserId, $otherUserId, $currentUserId);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Mark messages as read
    $conn->query("UPDATE messages SET is_read = TRUE 
                 WHERE receiver_id = $currentUserId AND sender_id = $otherUserId");
}

// Get all conversations
$conversations = $conn->query("
    SELECT users.id, users.fullname, users.photos, MAX(messages.sent_at) as last_message_time
    FROM users
    JOIN messages ON users.id = CASE 
        WHEN messages.sender_id = $currentUserId THEN messages.receiver_id
        ELSE messages.sender_id
    END
    WHERE $currentUserId IN (messages.sender_id, messages.receiver_id)
    GROUP BY users.id
    ORDER BY last_message_time DESC
")->fetch_all(MYSQLI_ASSOC);

// Get other user info if in conversation
$otherUser = null;
if ($otherUserId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $otherUserId);
    $stmt->execute();
    $otherUser = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Messages</title>
    <style>
        .chat-container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
            height: 80vh;
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
        }
        .conversation-list {
            width: 300px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }
        .conversation {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .conversation:hover {
            background: #f9f9f9;
        }
        .conversation-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            text-align: center;
            font-weight: bold;
        }
        .messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 15px;
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 20px;
        }
        .sent {
            background: #ff4e74;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        .received {
            background: #f0f0f0;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        .message-form {
            display: flex;
            padding: 15px;
            border-top: 1px solid #ddd;
        }
        .message-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            margin-right: 10px;
        }
        .send-btn {
            background: #ff4e74;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="conversation-list">
            <h3 style="padding: 15px; margin: 0; border-bottom: 1px solid #ddd;">Conversations</h3>
            <?php foreach ($conversations as $conv): 
                $photos = json_decode($conv['photos'], true);
            ?>
                <div class="conversation" onclick="location.href='chat.php?user_id=<?= $conv['id'] ?>'">
                    <img src="<?= htmlspecialchars($photos[0]) ?>" class="conversation-img">
                    <div>
                        <div><?= htmlspecialchars($conv['fullname']) ?></div>
                        <small><?= date('M j, g:i a', strtotime($conv['last_message_time'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($otherUserId && $otherUser): 
            $otherPhotos = json_decode($otherUser['photos'], true);
        ?>
            <div class="chat-area">
                <div class="chat-header">
                    <?= htmlspecialchars($otherUser['fullname']) ?>
                </div>
                
                <div class="messages">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?= $msg['sender_id'] == $currentUserId ? 'sent' : 'received' ?>">
                            <?= htmlspecialchars($msg['content']) ?>
                            <div style="font-size: 12px; text-align: right; margin-top: 5px;">
                                <?= date('g:i a', strtotime($msg['sent_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <form method="POST" class="message-form">
                    <input type="hidden" name="receiver_id" value="<?= $otherUserId ?>">
                    <input type="text" name="message" placeholder="Type a message..." class="message-input" required>
                    <button type="submit" class="send-btn">Send</button>
                </form>
            </div>
        <?php else: ?>
            <div style="flex: 1; display: flex; justify-content: center; align-items: center;">
                <p>Select a conversation to start chatting</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>