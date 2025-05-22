<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the profile user ID from URL
$profileId = $_GET['user_id'] ?? 0;

// Verify this is a valid match
$stmt = $conn->prepare("
    SELECT 1 FROM matches 
    WHERE (user1_id = ? AND user2_id = ?)
    OR (user1_id = ? AND user2_id = ?)
");
$stmt->bind_param("iiii", $_SESSION['user_id'], $profileId, $profileId, $_SESSION['user_id']);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    header("Location: likes.php");
    exit();
}

// Get user profile data with bio from wishes table
$stmt = $conn->prepare("
    SELECT u.*, w.bio, w.interests, w.wishlists 
    FROM users u
    LEFT JOIN wishes w ON u.id = w.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $profileId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Decode JSON fields
$photos = json_decode($user['photos'] ?? '[]', true) ?? [];
$interests = json_decode($user['interests'] ?? '[]', true) ?? [];
$wishlists = json_decode($user['wishlists'] ?? '[]', true) ?? [];
$age = date_diff(date_create($user['dob']), date_create('today'))->y;
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($user['fullname']) ?>'s Profile</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 20px;
        }
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
        }
        .profile-name {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .profile-age {
            color: #666;
            margin-bottom: 10px;
        }
        .profile-bio {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 18px;
            margin: 20px 0 10px;
            color: #ff4e74;
        }
        .photo-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .gallery-photo {
            width: calc(33.333% - 10px);
            height: 120px;
            border-radius: 5px;
            object-fit: cover;
        }
        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .tag {
            background: #f3f3f3;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 14px;
        }
        .interest-tag {
            background: #ffecef;
            color: #ff4e74;
        }
        .wish-tag {
            background: #e6f7ff;
            color: #1890ff;
        }
        .back-btn {
            background: #f0f0f0;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($photos[0] ?? 'default_profile.jpg') ?>" class="profile-img" alt="Profile Photo">
            <div>
                <h1 class="profile-name"><?= htmlspecialchars($user['fullname']) ?></h1>
                <div class="profile-age"><?= $age ?> years old</div>
            </div>
        </div>
        
        <div class="profile-bio">
            <h3>About</h3>
            <p><?= htmlspecialchars($user['bio'] ?? 'No bio available') ?></p>
        </div>
        
        <?php if (!empty($photos) && count($photos) > 1): ?>
            <h3 class="section-title">Photos</h3>
            <div class="photo-gallery">
                <?php foreach (array_slice($photos, 1) as $photo): ?>
                    <img src="<?= htmlspecialchars($photo) ?>" class="gallery-photo" alt="Profile Photo">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($interests)): ?>
            <h3 class="section-title">Interests</h3>
            <div class="tags">
                <?php foreach ($interests as $interest): ?>
                    <div class="tag interest-tag"><?= htmlspecialchars($interest) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($wishlists)): ?>
            <h3 class="section-title">Wishlist</h3>
            <div class="tags">
                <?php foreach ($wishlists as $wish): ?>
                    <div class="tag wish-tag"><?= htmlspecialchars($wish) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <button class="back-btn" onclick="window.history.back()">Back to Matches</button>
    </div>
</body>
</html>