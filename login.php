<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db = "wistie";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$loginError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Special admin hardcoded login
    if ($email === "mangila.adhikari111@gmail.com" && $password === "1234") {
        $_SESSION['user_id'] = 0; // Special ID for admin
        $_SESSION['username'] = "Admin";
        header("Location: admin.php");
        exit();
    }

    // Normal user login from database
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if ($password === $user['password']) { // Direct comparison as you want
            if ($user['status'] === 'approved') {
                // Set ALL required session variables
                $_SESSION['user_id'] = $user['id']; // This is critical for feed.php
                $_SESSION['username'] = $user['fullname'];
                $_SESSION['wishlist'] = $user['wishlist'] ?? '';
                
                // Debugging - uncomment to see what's being set
                // echo "<pre>"; print_r($_SESSION); echo "</pre>"; exit();
                
                header("Location: feed.php");
                exit(); // This is IMPORTANT to prevent further execution
            } else {
                $loginError = "Your account is still pending approval.";
            }
        } else {
            $loginError = "Invalid login credentials.";
        }
    } else {
        $loginError = "Invalid login credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - Wistie</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      background: linear-gradient(to right, #ffd5ec, #ffeedd);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .form-section {
      background: #fff;
      padding: 40px 30px;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      width: 100%;
      max-width: 420px;
      text-align: center;
    }

    h2 {
      color: #ff4081;
      margin-bottom: 25px;
    }

    form input {
      width: 100%;
      padding: 10px;
      margin-bottom: 18px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 14px;
    }

    form button {
      width: 100%;
      background: #ff4081;
      color: white;
      padding: 12px;
      font-size: 16px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s ease;
    }

    form button:hover {
      background: #e03a72;
    }

    .error {
      color: red;
      margin-bottom: 15px;
      font-size: 14px;
    }

    .nav-links {
      margin-top: 15px;
    }

    .nav-links a {
      color: #555;
      text-decoration: none;
      margin: 0 5px;
    }

    .nav-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="form-section">
    <h2>Login to Wistie</h2>

    <?php if ($loginError): ?>
      <div class="error"><?= $loginError ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Password" required />
      <button type="submit">Login</button>
    </form>

    <div class="nav-links">
      <a href="index.html">Home</a> | <a href="register.php">Register</a>
    </div>
  </div>

</body>
</html>
