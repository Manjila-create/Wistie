<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    $conn->begin_transaction();
    try {
        // Update users table
        $stmt = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt->bind_param("si", $fullname, $userId);
        $stmt->execute();
        
        // Update wishes table
        $stmt = $conn->prepare("
            INSERT INTO wishes (user_id, bio) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE bio = ?
        ");
        $stmt->bind_param("iss", $userId, $bio, $bio);
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Profile updated!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Update failed: " . $e->getMessage();
    }
    header("Location: profile.php");
    exit();
}

// Fetch user data
$user = [];
$photos = [];
$interests = [];
$wishlists = [];

// Get user from users table
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?? [];

// Get photos (from users.photos TEXT field)
if (!empty($user)) {
    $photos = json_decode($user['photos'], true) ?? [];
}

// Get ALL profile data in one optimized query
$stmt = $conn->prepare("
    SELECT u.*, w.bio, w.interests, w.wishlists 
    FROM users u
    LEFT JOIN wishes w ON u.id = w.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc() ?? [];

// Safely decode all fields
$photos = json_decode($profile['photos'] ?? '[]', true) ?? [];
$interests = json_decode($profile['interests'] ?? '[]', true) ?? [];
$wishlists = json_decode($profile['wishlists'] ?? '[]', true) ?? [];
$bio = $profile['bio'] ?? '';

?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Profile</title>
    <style>
        .profile-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .save-btn {
            background: #ff4e74;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .photos {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }
        .tags-container {
            margin: 20px 0;
        }
        .tag {
            display: inline-block;
            background: #f3f3f3;
            padding: 5px 10px;
            border-radius: 15px;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        .interest-tag { background: #ffecef; color: #ff4e74; }
        .wish-tag { background: #e6f7ff; color: #1890ff; }
        .no-data {
            color: #777;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h1>Your Profile</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div style="color: green; margin-bottom: 15px;"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="save-btn">Save Changes</button>
        </form>

        <div class="tags-container">
            <h2>Interests</h2>
            <?php if (!empty($interests)): ?>
                <?php foreach ($interests as $interest): ?>
                    <span class="tag interest-tag"><?= htmlspecialchars($interest) ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data">No interests added yet</p>
            <?php endif; ?>
        </div>

        <div class="tags-container">
            <h2>Wishlist</h2>
            <?php if (!empty($wishlists)): ?>
                <?php foreach ($wishlists as $wish): ?>
                    <span class="tag wish-tag"><?= htmlspecialchars($wish) ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data">No wishlist items yet</p>
            <?php endif; ?>
        </div>
        
        <h2>Your Photos</h2>
        <div class="photos">
            <?php if (!empty($photos)): ?>
                <?php foreach ($photos as $photo): ?>
                    <img src="<?= htmlspecialchars($photo) ?>" class="photo" alt="Profile photo">
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data">No photos uploaded yet</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>