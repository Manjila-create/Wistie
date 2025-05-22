<!-- File: dashboard.php -->
<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "wistie";

$conn = mysqli_connect($host, $user, $pass, $db);

$username = $_SESSION['username'];
$wishlist = explode(",", $_SESSION['wishlist']);

$wishlist_conditions = [];
foreach ($wishlist as $item) {
    $item = trim($item);
    $wishlist_conditions[] = "wishlist LIKE '%$item%'";
}
$conditions_sql = implode(" OR ", $wishlist_conditions);

$sql = "SELECT * FROM users WHERE username != '$username' AND ($conditions_sql)";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard - Wistie</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
<header>
  <div class="logo">Wistie 🌟</div>
  <nav>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<section class="matches">
  <h2>Hi <?php echo $username; ?>! Here are your matches:</h2>
  <?php
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<div class='match-card'>";
        echo "<h3>" . $row['username'] . "</h3>";
        echo "<p><strong>Wishlist:</strong> " . $row['wishlist'] . "</p>";
        echo "</div>";
    }
  ?>
</section>
</body>
</html>
