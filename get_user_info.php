<?php
require 'db_connection.php';

if (!isset($_GET['user_id'])) {
    die('User ID not provided');
}

$userId = (int)$_GET['user_id'];
$stmt = $conn->prepare("SELECT interests, wishlists FROM wishes WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('No data found');
}

$user = $result->fetch_assoc();
$interests = json_decode($user['interests'], true) ?? [];
$wishlists = json_decode($user['wishlists'], true) ?? [];
?>

<div class="info-title">
    <i class="fas fa-heart"></i>
    <span>Interests</span>
</div>
<div class="tags">
    <?php foreach ($interests as $interest): ?>
        <div class="tag"><?= htmlspecialchars($interest) ?></div>
    <?php endforeach; ?>
</div>

<div class="info-title">
    <i class="fas fa-gift"></i>
    <span>Wishlist</span>
</div>
<div class="tags">
    <?php foreach ($wishlists as $wish): ?>
        <div class="tag"><?= htmlspecialchars($wish) ?></div>
    <?php endforeach; ?>
</div>