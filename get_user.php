<?php
session_start();
require 'db_connection.php';

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'No user ID provided']));
}

$userId = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: application/json');
if ($result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode(['error' => 'User not found']);
}
?>