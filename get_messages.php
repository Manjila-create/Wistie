<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$currentUserId = $_SESSION['user_id'];
$otherUserId = $_GET['user_id'] ?? 0;

// Verify match (same as before)
$stmt = $conn->prepare("SELECT 1 FROM matches WHERE (user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)");
$stmt->bind_param("iiii", $currentUserId, $otherUserId, $otherUserId, $currentUserId);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    die("No match found");
}

// Get messages
$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id=? AND receiver_id=?) 
    OR (sender_id=? AND receiver_id=?)
    ORDER BY sent_at ASC
");
$stmt->bind_param("iiii", $currentUserId, $otherUserId, $otherUserId, $currentUserId);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($messages as $message) {
    $isSent = $message['sender_id'] == $currentUserId;
    $time = date("h:i A", strtotime($message['sent_at']));
    ?>
    <div class="message <?= $isSent ? 'sent' : 'received' ?>">
        <div class="message-content"><?= htmlspecialchars($message['message']) ?></div>
        <div class="message-time"><?= $time ?></div>
    </div>
    <?php
}
?>