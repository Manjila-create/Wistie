<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT users.* FROM matches 
                       JOIN users ON users.id = CASE 
                           WHEN matches.user1_id = ? THEN matches.user2_id 
                           ELSE matches.user1_id 
                       END
                       WHERE ? IN (matches.user1_id, matches.user2_id)");
$stmt->bind_param("ii", $userId, $userId);
$stmt->execute();
$matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Matches</title>
    <style>
        .matches-container {
            max-width: 800px;
            margin: 20px auto;
        }
        .match-card {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .match-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
        }
        .match-item {
            padding: 15px;
            margin: 10px 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .match-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .match-info {
            flex: 1;
        }
        .match-name {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .match-actions {
            display: flex;
            gap: 10px;
        }
        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .chat-btn {
            background: #ff4e74;
            color: white;
        }
        .view-btn {
            background: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="matches-container">
        <h1>Your Matches</h1>
        <!-- Search bar -->
        <div style="margin-bottom: 20px; display: flex; align-items: center;">
            <input type="text" id="searchInput" placeholder="Search matches..." 
                   style="padding: 10px; font-size: 16px; flex: 1; border-radius: 5px; border: 1px solid #ccc;">
            <span style="margin-left: -35px; cursor: pointer;">🔍</span>
        </div>
        
        <div id="matchesList">
            <?php if (empty($matches)): ?>
                <p>You don't have any matches yet. Keep swiping!</p>
            <?php else: ?>
                <?php foreach ($matches as $match): 
                    $photos = json_decode($match['photos'], true);
                    $age = date_diff(date_create($match['dob']), date_create('today'))->y;
                ?>
                    <div class="match-item">
                        <div class="match-card">
                            <img src="<?= htmlspecialchars($photos[0]) ?>" class="match-img">
                            <div class="match-info">
                                <div class="match-name"><?= htmlspecialchars($match['fullname']) ?></div>
                                <div class="match-age"><?= $age ?> years</div>
                            </div>
                            <div class="match-actions">
                                <button class="action-btn chat-btn" onclick="location.href='chat.php?user_id=<?= $match['id'] ?>'">Chat</button>
                                <button class="action-btn view-btn" onclick="location.href='viewprofile.php?user_id=<?= $match['id'] ?>'">View Profile</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const matchItems = document.querySelectorAll('.match-item');
            
            matchItems.forEach(item => {
                const nameElement = item.querySelector('.match-name');
                const name = nameElement.textContent.toLowerCase();
                
                if (name.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>