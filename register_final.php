<?php
session_start();
if (!isset($_SESSION['form_data'])) {
    echo "❌ Session expired. Go back & register again.";
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wistie";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve session data
$form = $_SESSION['form_data'] ?? [];
$fullname = $form['fullname'] ?? '';
$phone = $form['phone'] ?? '';
$email = $form['email'] ?? '';
$password = $form['password'] ?? '';
$dob = $form['dob'] ?? '';
$photoPaths = $form['photos'] ?? []; // Array of file paths (from temp_uploads/)

// Move photos from temp_uploads to user_photos
$finalPhotoPaths = [];
$finalDir = 'uploads/';

if (!file_exists($finalDir)) {
    mkdir($finalDir, 0777, true);
}

foreach ($photoPaths as $tempPath) {
    $fileName = basename($tempPath); // get the file name
    $newPath = $finalDir . uniqid() . '_' . $fileName;

    if (file_exists($tempPath)) {
        if (rename($tempPath, $newPath)) {
            $finalPhotoPaths[] = $newPath;
        }else {
            echo "Failed to move file: $tempPath <br>";
        }
    } else {
        echo "Temp file does not exist: $tempPath <br>";
    }
}

// Convert photo paths array to JSON for storing
$photosJSON = json_encode($finalPhotoPaths);
//$photosJSON = json_encode($photoPaths);


// Prepare interests and wishlists
$interests = isset($_POST['interests']) ? $_POST['interests'] : [];
$wishlists = isset($_POST['wishlists']) ? array_filter($_POST['wishlists'], fn($v) => trim($v) !== '') : [];

// Convert to JSON
$interests_json = json_encode($interests);
$wishlists_json = json_encode($wishlists);

// Insert into users table using prepared statement
$stmt = $conn->prepare("INSERT INTO users (fullname, phone, email, password, dob, photos) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $fullname, $phone, $email, $password, $dob, $photos);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;

    // Insert into wishes table
    $stmt2 = $conn->prepare("INSERT INTO wishes (user_id, interests, wishlists) VALUES (?, ?, ?)");
    $stmt2->bind_param("iss", $user_id, $interests_json, $wishlists_json);

    if ($stmt2->execute()) {
        echo "<script>alert('Registration successful. Your account is pending approval.'); window.location.href = 'login.php';</script>";
    } else {
        echo "<script>alert('Error: Could not register. Please try again later.');</script>";
    }

    $stmt2->close();
} else {
    echo "<script>alert('Error: Could not register user. Please try again later.');</script>";
}
$stmt->close();
$conn->close();
session_destroy();
?>
