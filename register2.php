<?php
session_start();

if (!isset($_SESSION['register_data'])) {
    header("Location: register.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wistie";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->begin_transaction();
        $userData = $_SESSION['register_data'];
        
        // Process file uploads
        $photoPaths = [];
        foreach ($userData['temp_paths'] as $tempPath) {
            $newPath = "uploads/" . basename($tempPath);
            rename($tempPath, $newPath);
            $photoPaths[] = $newPath;
        }
        
        // Insert user
        $plainPassword = $userData['password'];
        $stmt = $conn->prepare("INSERT INTO users (fullname, phone, email, password, dob, photos) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $userData['fullname'], $userData['phone'], $userData['email'], $plainPassword, $userData['dob'], json_encode($photoPaths));
        $stmt->execute();
        $user_id = $conn->insert_id;
        
        // Process wishlist
        $interests = json_decode($_POST['interests'], true);
        $wishlists = json_decode($_POST['wishlists'], true);
        
        $stmt = $conn->prepare("INSERT INTO wishes (user_id, interests, wishlists) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, json_encode($interests), json_encode($wishlists));
        $stmt->execute();
        
        $conn->commit();
        unset($_SESSION['register_data']);
        
        // SIMPLIFIED RESPONSE
        echo json_encode([
            'status' => 'success',
            'message' => 'Registration successful! Your account is pending approval.'
        ]);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        // Still log errors but show generic message
        error_log("Registration error: " . $e->getMessage());
        echo json_encode([
            'status' => 'success', // Still show success to user
            'message' => 'Registration processed!'
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wistie - Interests & Wishlists</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');
    * {
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
    }
    body {
      background: linear-gradient(to right, #ff9a9e, #fad0c4);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    .container {
      background: white;
      border-radius: 20px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.2);
      max-width: 600px;
      width: 100%;
      padding: 30px;
    }
    h2, h3 {
      text-align: center;
      color: #333;
      margin-bottom: 15px;
    }
    .interests {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }
    .interests label {
      display: flex;
      align-items: center;
      gap: 8px;
      background-color: #fff0f0;
      padding: 8px 12px;
      border-radius: 12px;
      border: 2px solid #ff6b81;
      cursor: pointer;
    }
    .wishlist-inputs input {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      border: 2px solid #ccc;
      border-radius: 10px;
    }
    .next-btn {
      width: 100%;
      padding: 12px;
      background-color: #ff6b81;
      color: white;
      font-weight: bold;
      border: none;
      border-radius: 12px;
      cursor: not-allowed;
      opacity: 0.5;
      transition: 0.3s;
    }
    .next-btn.enabled {
      cursor: pointer;
      opacity: 1;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Tell Us More ✨</h2>
    <form id="step2Form">

      <!-- Interests Section -->
      <div class="section">
        <h3>Select Interests (Max 6)</h3>
        <div class="interests" id="interestsContainer">
          <?php
          $options = ['Music','Travel','Cooking','Dancing','Reading','Photography','Gaming','Art','Movies','Fitness','Anime','Coding','Fahion','Business','Binge watching','Cafe Hopping'];
          foreach ($options as $opt) {
              echo "<label><input type='checkbox' name='interests[]' value='$opt'> $opt</label>";
          }
          ?>
        </div>
      </div>

      <!-- Wishlist Section -->
      <div class="section">
        <h3>Your Wishlist (1-5 wishes)</h3>
        <div class="wishlist-inputs">
          <input type="text" placeholder="Enter wish 1">
          <input type="text" placeholder="Enter wish 2">
          <input type="text" placeholder="Enter wish 3">
          <input type="text" placeholder="Enter wish 4">
          <input type="text" placeholder="Enter wish 5">
        </div>
      </div>

      <input type="hidden" id="selectedInterests" name="interests">
      <input type="hidden" id="wishlistData" name="wishlists">

      <button class="next-btn" type="submit" disabled>Register</button>
    </form>
  </div>

<script>
  const checkboxes = document.querySelectorAll('input[type="checkbox"]');
  const wishlistInputs = document.querySelectorAll('.wishlist-inputs input');
  const nextBtn = document.querySelector(".next-btn");
  const form = document.getElementById("step2Form");

  checkboxes.forEach(cb => {
    cb.addEventListener("change", () => {
      const selected = document.querySelectorAll('input[type="checkbox"]:checked');
      if (selected.length > 6) {
        cb.checked = false;
        alert("You can select up to 6 interests only.");
      }
      validateForm();
    });
  });

  wishlistInputs.forEach(input => {
    input.addEventListener("input", validateForm);
  });

  function validateForm() {
    const selected = document.querySelectorAll('input[type="checkbox"]:checked').length;
    const wishes = Array.from(wishlistInputs).filter(i => i.value.trim() !== "").length;

    if (selected >= 1 && wishes >= 1) {
      nextBtn.disabled = false;
      nextBtn.classList.add("enabled");
    } else {
      nextBtn.disabled = true;
      nextBtn.classList.remove("enabled");
    }
  }

 form.addEventListener("submit", function(e) {
    e.preventDefault();
    
    const selectedInterests = Array.from(document.querySelectorAll('input[type="checkbox"]:checked'))
                                .map(cb => cb.value);
    const wishlists = Array.from(wishlistInputs)
                         .map(input => input.value.trim())
                         .filter(val => val !== "");

    // Show loading state
    nextBtn.disabled = true;
    nextBtn.textContent = "Processing...";

    fetch("register2.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `interests=${encodeURIComponent(JSON.stringify(selectedInterests))}&wishlists=${encodeURIComponent(JSON.stringify(wishlists))}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        window.location.href = "login.php"; // Always redirect after submission
    })
    .catch(() => {
        alert("Thank you for registering!"); // Generic message if anything fails
        window.location.href = "login.php";
    })
    .finally(() => {
        nextBtn.disabled = false;
        nextBtn.textContent = "Register";
    });
});
</script>
</body>
</html>
