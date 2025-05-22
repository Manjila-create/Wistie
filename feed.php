<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
require 'db_connection.php';
$conn = $GLOBALS['conn'];
$currentUserId = $_SESSION['user_id'];

// Handle swipe action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['liked_id'])) {
    $action = $_POST['action'];
    $likedId = (int)$_POST['liked_id'];
    
    // Only record right swipes (likes)
    if ($action === 'right') {
        // Record the swipe
        $stmt = $conn->prepare("INSERT INTO likes (liker_id, liked_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $currentUserId, $likedId);
        $stmt->execute();
        
        // Check for a match
        $matchCheck = $conn->prepare("SELECT id FROM likes WHERE liker_id = ? AND liked_id = ?");
        $matchCheck->bind_param("ii", $likedId, $currentUserId);
        $matchCheck->execute();
        $matchResult = $matchCheck->get_result();
        
        if ($matchResult->num_rows > 0) {
           // Insert the match ensuring user1_id is always the smaller ID to avoid duplicates
            $user1_id = min($currentUserId, $likedId);
            $user2_id = max($currentUserId, $likedId);
            
            // Use INSERT IGNORE to prevent duplicate matches
            $conn->query("INSERT IGNORE INTO matches (user1_id, user2_id) VALUES ($user1_id, $user2_id)");
            
            // Set session variable to show match notification
            $_SESSION['new_match'] = $likedId;
            
            // If this is the second swipe (User B swiping on User A), show the match to User B
            if (isset($_SESSION['new_match_check']) && $likedId == $_SESSION['new_match_check']) {
                $_SESSION['show_match_notification'] = true;
            } else {
                // For the first swipe (User A swiping on User B), store for potential future match
                $_SESSION['new_match_check'] = $likedId;
            }
        }
    }
    
    exit();
}

// Get users to show in feed
$stmt = $conn->prepare("
    SELECT DISTINCT u.*, w.interests, w.wishlists 
    FROM users u
    LEFT JOIN wishes w ON u.id = w.user_id
    WHERE u.id != ?  -- Exclude current user
    AND u.status = 'approved'
    AND NOT EXISTS (
        SELECT 1 FROM likes 
        WHERE liker_id = ? AND liked_id = u.id
    )
    ORDER BY RAND()
    LIMIT 20
");

$stmt->bind_param("ii", $currentUserId, $currentUserId);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wistie | Feed</title>
  <style>
    :root {
      --primary: #ff4e74;
      --secondary: #5cc77c;
      --dark: #2d3436;
      --light: #f5f6fa;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    
    .topbar {
      background: #c766ab;
      padding: 15px;
      text-align: center;
      font-size: 24px;
      font-weight: 700;
      color: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      position: relative;
    }
     .playful-w {
      color: #ffeb3b;
      font-size: 1.2em;
      text-shadow: 2px 2px 0px #ff4081, -2px -2px 0px #7e57c2;
      animation: bounce 1.2s infinite;
      display: inline-block;
    }
    
    /* Add this keyframes definition */
    @keyframes bounce {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-10px);
      }
    }
    
    .home-btn {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 24px;
      color: var(--primary);
      text-decoration: none;
    }
    
    .main-container {
      display: flex;
      flex: 1;
      padding: 20px;
      gap: 20px;
    }
    .star-nav {
      width:275px;
      height:33px;
      margin-top:38%;
       margin-left: 0px;
       margin-right: 100px;
      padding-right: 100px;
    }
    
    .star-nav button {
      background: none;
      border: none;
      color: var(--primary);
      font-size: 25px;
      cursor: pointer;
      transition: transform 0.3s;
      gap:100px;
    }
    
    .star-nav button:hover {
      transform: scale(1.1);
    }
    
    /* Swipe Card */
    .swipe-container {
      flex: 1;
      max-width: 400px;
       margin-left: 30%;
      height: 75vh;
      position: relative;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    
    .card {
      position: absolute;
      width: 100%;
      height: 100%;
      background: white;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      overflow: hidden;
      display: none; /* Changed from flex to none */
      flex-direction: column;
      transition: transform 0.5s ease, opacity 0.3s ease;
    }
    
    .card.active {
      display: flex;
    }
    
    .card-img {
      width: 400px;
      height: 8000px;
      background-size: cover;
      background-position: center;
    }
    
    .card-content {
      padding: 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }
    
    .card-name {
      font-size: 24px;
      font-weight: 700;
      color: var(--dark);
    }
    
    .card-age {
      font-size: 20px;
      color: #777;
    }
    
    .card-bio {
      color: #555;
      margin-bottom: 15px;
      line-height: 1.4;
    }
    
    .card-actions {
      display: flex;
      justify-content: center;
      gap: 30px;
      padding: 15px 0;
    }
    
    .action-btn {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      border: none;
      font-size: 24px;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }
    
    .dislike-btn {
      background: white;
      color: #ff6b81;
      border: 2px solid #ff6b81;
    }
    
    .like-btn {
      background: var(--secondary);
      color: white;
    }
    
    .action-btn:hover {
      transform: scale(1.1);
    }
    
    /* User Info Panel */
    .info-panel {
      width: 300px;
      background: white;
      border-radius: 20px;
      padding: 25px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      margin-left: 20px;
      z-index: 1;
      overflow-y: auto;
      max-height: 75vh;
    }
    
    .info-section {
      margin-bottom: 20px;
    }
    
    .info-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--primary);
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .info-title i {
      font-size: 20px;
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
      color: var(--primary);
    }
    
    .wish-tag {
      background: #e6f7ff;
      color: #1890ff;
    }
    
    .no-more {
        background: var(--primary);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    margin-top: 15px;
    cursor: pointer;
    }
    /* Match Popup Styles */
.match-popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.match-content {
    background: white;
    padding: 30px;
    border-radius: 20px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    animation: popIn 0.5s;
}

.match-title {
    color: #ff4e74;
    margin-bottom: 15px;
    font-size: 28px;
}

.match-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 20px;
}

@keyframes popIn {
    0% {
        transform: scale(0.5);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Confetti canvas */
#confetti-canvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 999;
}
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.4.0/dist/confetti.browser.min.js"></script>
<canvas id="confetti-canvas"></canvas>
<body>

  <div class="topbar">
    <span class="playful-w">W</span>istie 🌟
    <a href="index.html" class="home-btn">🏠</a>
  </div>
  
  <div class="main-container">
    
    <!-- Swipe Cards -->
    <div class="swipe-container" id="cardContainer">
    <?php 
    // Filter out current user just in case
    $filteredUsers = array_filter($users, function($user) use ($currentUserId) {
        return $user['id'] != $currentUserId;
    });
    
    if (!empty($filteredUsers)): 
        foreach ($filteredUsers as $index => $user): 
            $photos = json_decode($user['photos'], true) ?? [];
            $age = date_diff(date_create($user['dob']), date_create('today'))->y;
    ?>
            <div class="card <?= $index === 0 ? 'active' : '' ?>" data-user-id="<?= $user['id'] ?>">
                    <div class="card-img" style="background-image: url('<?= htmlspecialchars($photos[0]) ?>')"></div>
                    <div class="card-info">
                        <div class="card-name"><?= htmlspecialchars($user['fullname']) ?></div>
                        <div class="card-age"><?= $age ?> yrs</div>
                        <p class="card-bio"><?= htmlspecialchars($user['bio'] ?? 'No bio yet') ?></p>
                    </div>
                    <div class="card-actions">
                        <button class="action-btn dislike-btn" onclick="swipe('left', <?= $user['id'] ?>)">✘</button>
                        <button class="action-btn like-btn" onclick="swipe('right', <?= $user['id'] ?>)">❤</button>
                    </div>
                </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-more">
            No profiles available to show.<br>
            <button onclick="location.reload()">Check for new profiles</button>
        </div>
    <?php endif; ?>
</div>
        
        <!-- Info Panel -->
<div class="info-panel" id="infoPanel">
    <?php if (!empty($users)): 
        $firstUser = $users[0];
        // Safely decode JSON fields (return empty array if invalid/empty)
        $interests = json_decode($firstUser['interests'] ?? '[]', true) ?? [];
        $wishlists = json_decode($firstUser['wishlists'] ?? '[]', true) ?? [];
    ?>
        <div class="info-title">
            <i class="fas fa-heart"></i>
            <span>Interests</span>
        </div>
        <div class="tags">
            <?php if (!empty($interests)): ?>
                <?php foreach ($interests as $interest): ?>
                    <div class="tag interest-tag"><?= htmlspecialchars($interest) ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="tag">No interests added yet.</div>
            <?php endif; ?>
        </div>
        
        <div class="info-title">
            <i class="fas fa-gift"></i>
            <span>Wishlist</span>
        </div>
        <div class="tags">
            <?php if (!empty($wishlists)): ?>
                <?php foreach ($wishlists as $wish): ?>
                    <div class="tag wish-tag"><?= htmlspecialchars($wish) ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="tag">No wishlist items yet.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

  <!-- Star Sidebar -->
    <!-- <div class="star-sidebar"> -->
      <div class="star-nav">
         <button onclick="location.href='profile.php'"><i class="fas fa-user"></i></button>
                <button onclick="location.href='likes.php'"><i class="fas fa-heart"></i></button>
            <!-- </div> -->
        </div>

    <!-- Match Popup -->
    <div id="matchPopup" class="match-popup" style="display: none;">
        <div class="match-content">
            <h1 class="match-title">It's a Match! 💖</h1>
            <p>You and <span id="matchedUserName"></span> have liked each other!</p>
            <div class="match-buttons">
                <button onclick="closeMatchPopup()" class="action-btn dislike-btn">Keep Swiping</button>
                <button onclick="sendMessage()" class="action-btn like-btn">Send Message</button>
            </div>
        </div>
    </div>

    <script>
        // Swipe function
      let currentVisibleCardIndex = 0;

// Update the swipe function in feed.php
function swipe(direction, userId) {
    if (direction !== 'right') {
        // Handle left swipe normally
        const currentCard = document.querySelector('.card.active');
        if (!currentCard) return;
        
        currentCard.style.transform = 'translateX(-100%)';
        currentCard.style.opacity = '0';
        
        setTimeout(() => {
            showNextCard();
        }, 300);
        return;
    }

    fetch('feed.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${direction}&liked_id=${userId}`
    }).then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.text();
    }).then(() => {
        const currentCard = document.querySelector('.card.active');
        if (!currentCard) return;
        
        // Animate swipe right with heart animation
        currentCard.style.transform = 'translateX(100%) rotate(10deg)';
        currentCard.style.opacity = '0';
        
        // Create heart animation
        const heart = document.createElement('div');
        heart.style.position = 'absolute';
        heart.style.left = '50%';
        heart.style.top = '50%';
        heart.style.transform = 'translate(-50%, -50%)';
        heart.style.fontSize = '100px';
        heart.style.color = '#ff4e74';
        heart.style.opacity = '0';
        heart.style.transition = 'all 0.5s';
        heart.style.zIndex = '1000';
        heart.innerHTML = '❤️';
        document.body.appendChild(heart);
        
        setTimeout(() => {
            heart.style.opacity = '1';
            heart.style.transform = 'translate(-50%, -50%) scale(1.5)';
        }, 10);
        
        setTimeout(() => {
            heart.style.opacity = '0';
            heart.style.transform = 'translate(-50%, -150%) scale(0.5)';
            setTimeout(() => heart.remove(), 500);
        }, 800);
        
        setTimeout(() => {
            currentCard.style.display = 'none';
            showNextCard();
        }, 300);
    }).catch(error => {
        console.error('Error:', error);
    });
}

function showNextCard() {
    const allCards = Array.from(document.querySelectorAll('.card'));
    const currentIndex = allCards.findIndex(card => card.classList.contains('active'));
    
    if (currentIndex < allCards.length - 1) {
        allCards[currentIndex + 1].classList.add('active');
        updateInfoPanel(allCards[currentIndex + 1].dataset.userId);
    } else {
        document.getElementById('cardContainer').innerHTML = `
            <div class="no-more">
                No more profiles available.<br>
                <button onclick="location.reload()">Check Again</button>
            </div>
        `;
    }
}

// Enhanced match popup with animation
window.addEventListener('DOMContentLoaded', () => {
    <?php if (isset($_SESSION['new_match'])): ?>
        // Show confetti animation
        const confettiSettings = {
            target: 'confetti-canvas',
            max: 150,
            size: 1.5,
            animate: true,
            props: ['circle', 'square', 'triangle', 'line'],
            colors: [[255,78,116], [92,199,124], [255,235,59], [199,102,171]],
            clock: 25,
            rotate: true,
            start_from_edge: true,
            respawn: true
        };
        
        const confetti = new ConfettiGenerator(confettiSettings);
        confetti.render();
        
        fetch('get_user.php?id=<?= $_SESSION['new_match'] ?>')
            .then(response => response.json())
            .then(user => {
                document.getElementById('matchedUserName').textContent = user.fullname;
                
                // Create match popup with animation
                const popup = document.getElementById('matchPopup');
                popup.style.display = 'flex';
                popup.style.animation = 'popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                
                // Add user photo to popup
                const photos = JSON.parse(user.photos || '[]');
                if (photos.length > 0) {
                    const img = document.createElement('img');
                    img.src = photos[0];
                    img.style.width = '100px';
                    img.style.height = '100px';
                    img.style.borderRadius = '50%';
                    img.style.objectFit = 'cover';
                    img.style.margin = '20px 0';
                    document.querySelector('.match-content').insertBefore(img, document.querySelector('.match-buttons'));
                }
            });
        
        <?php unset($_SESSION['new_match']); ?>
    <?php endif; ?>
});

        function closeMatchPopup() {
            document.getElementById('matchPopup').style.display = 'none';
        }

        function sendMessage() {
            const matchedUserId = <?= $_SESSION['new_match'] ?? 'null' ?>;
            if (matchedUserId) {
                window.location.href = `chat.php?user_id=${matchedUserId}`;
            }
        }

        function updateInfoPanel(userId) {
            fetch(`get_user_info.php?user_id=${userId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('infoPanel').innerHTML = html;
                });
        }
    </script>
</body>
</html>