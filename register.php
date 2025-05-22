<?php 
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wistie";

// Create connection for validation only
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate inputs
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $dob = $_POST['dob'];
    
    // Check if email or phone exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
    $check->bind_param("ss", $email, $phone);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        echo "<script>alert('Email or phone already registered.');</script>";
        exit;
    }
    
    // Age validation
    $dobDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($dobDate)->y;

    if ($age < 16) {
        echo "<script>alert('Only users aged 16 and above can register.');</script>";
        exit;
    } 

    // Handle file uploads
    $photos = $_FILES['photos'];
    $photoPaths = [];
    $totalPhotos = count($photos['name']);

    if ($totalPhotos < 2 || $totalPhotos > 6) {
        echo "<script>alert('Please upload 2 to 6 images.');</script>";
        exit;
    }

    // Store files temporarily in session (or move to temp folder)
    $_SESSION['register_data'] = [
        'fullname' => $fullname,
        'phone' => $phone,
        'email' => $email,
        'password' => $password, // Note: You should hash this before storing in DB
        'dob' => $dob,
        'photo_count' => $totalPhotos,
        'photo_names' => $photos['name']
    ];
    
    // Move uploaded files to temp location
    if (!file_exists('temp_uploads')) {
        mkdir('temp_uploads', 0777, true);
    }
    
    for ($i = 0; $i < $totalPhotos; $i++) {
        $temp_name = $photos['tmp_name'][$i];
        $temp_path = "temp_uploads/" . uniqid() . "_" . $photos['name'][$i];
        move_uploaded_file($temp_name, $temp_path);
        $_SESSION['register_data']['temp_paths'][$i] = $temp_path;
    }

    header("Location: register2.php");
    exit;
}

$conn->close();
?>

<!-- Keep your existing HTML form -->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wistie | Register</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }

    body {
      background: url(images/floral.jpg);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .register-box {
      background: white;
      padding: 40px 30px;
      border-radius: 16px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .register-box h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #ff5e62;
    }

    label {
      display: block;
      font-size: 14px;
      margin-bottom: 6px;
      font-weight: 500;
      color: #333;
    }

    input[type="text"],
    input[type="tel"],
    input[type="email"],
    input[type="password"],
    input[type="date"],
    input[type="file"] {
      width: 100%;
      padding: 10px 12px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 14px;
    }

    input[type="file"] {
      padding: 6px 10px;
    }

    button {
      width: 100%;
      background: #ff5e62;
      color: white;
      padding: 12px;
      font-size: 16px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #e04c52;
    }

    .note {
      font-size: 13px;
      color: #777;
      text-align: center;
      margin-top: 10px;
    }
    .image-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 20px;
  cursor: pointer;
}

.img-slot {
  width: 100%;
  padding-top: 100%; /* Square ratio */
  background-color: #f3f3f3;
  border: 2px dashed #ccc;
  border-radius: 12px;
  position: relative;
  overflow: hidden;
}

.img-slot span {
  position: absolute;
  top: 40%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 24px;
  color: #bbb;
}

.img-slot img {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  object-fit: cover;
}


    @media (max-width: 480px) {
      .register-box {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>

<div class="register-box">
    <h2>Create Your Account</h2>
    <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateAge();">
      <label for="fullname">Full Name</label>
      <input type="text" name="fullname" id="fullname" pattern="[A-Za-z ]+" oninput="this.reportValidity()" required=""> <br>


      <label for="phone">Phone Number</label>
      <input type="tel" name="phone" id="phone"  pattern="98[0-9]{8}" oninput="this.reportValidity()" required=""><br>

      <label for="email">Email</label>
      <input type="email" name="email" id="email" pattern="^[a-zA-Z0-9._%+-]+@(gmail\.com|email\.com)$" required oninput="this.reportValidity()"><br>

      <label for="password">Password</label>
      <input type="password" name="password" id="password" required oninput="this.reportValidity()" >

      <label for="dob">Date of Birth</label>
      <input type="date" name="dob" id="dob" required >

      <label for="photos">Add Your Recent Pictures</label>
<div class="image-grid" id="imageGrid">
  <!-- 6 empty boxes -->
  <div class="img-slot"><span>+</span></div>
  <div class="img-slot"><span>+</span></div>
  <div class="img-slot"><span>+</span></div>
  <div class="img-slot"><span>+</span></div>
  <div class="img-slot"><span>+</span></div>
  <div class="img-slot"><span>+</span></div>
</div>

<input type="file" name="photos[]" id="photos" accept="image/jpeg, image/png, image/webp" multiple required style="display: none;">
<small style="color: #777;">Click a box to upload. 2–6 JPG/PNG/WEBP images allowed.</small>

      <button type="submit">Register</button>
      <div class="note">Must be 16 years or older to join Wistie.</div>
    </form>
  </div>

  <script>
function validateAge() {
  const dob = new Date(document.getElementById('dob').value);
  const today = new Date();
  let age = today.getFullYear() - dob.getFullYear();
  const m = today.getMonth() - dob.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
    age--;
  }

  if (age < 16) {
    alert("Only users aged 16 and above can register.");
    return false;
  }
  return true;
}

document.getElementById('fullname').oninput = function () {
  const regex = /^[A-Za-z ]+$/;
  if (!regex.test(this.value)) {
    this.setCustomValidity("Only letters and spaces allowed.");
  } else {
    this.setCustomValidity("");
  }
  this.reportValidity();
};

document.getElementById('phone').oninput = function () {
  const regex = /^98[0-9]{8}$/;
  if (!regex.test(this.value)) {
    this.setCustomValidity("Enter valid 10 digit number.");
  } else {
    this.setCustomValidity("");
  }
  this.reportValidity();
};

document.getElementById('email').oninput = function () {
  const regex = /^[a-zA-Z0-9._%+-]+@(gmail\.com|email\.com)$/;
  if (!regex.test(this.value)) {
    this.setCustomValidity("Use a valid Gmail or Email address.");
  } else {
    this.setCustomValidity("");
  }
  this.reportValidity();
};

document.getElementById('password').oninput = function () {
  if (this.value.length < 6) {
    this.setCustomValidity("Password must be at least 6 characters.");
  } else {
    this.setCustomValidity("");
  }
  this.reportValidity();
};

const imgSlots = document.querySelectorAll('.img-slot');
const fileInput = document.getElementById('photos');
let selectedFiles = [];

imgSlots.forEach((slot, index) => {
  slot.addEventListener('click', () => {
    fileInput.click(); // open the file dialog
  });
});

fileInput.addEventListener('change', function () {
  const newFiles = Array.from(this.files);

  // Combine existing and new files, but limit to 6
  selectedFiles = [...selectedFiles, ...newFiles].slice(0, 6);

  // Update the file input to reflect only selectedFiles (required for backend upload)
  const dataTransfer = new DataTransfer();
  selectedFiles.forEach(file => dataTransfer.items.add(file));
  fileInput.files = dataTransfer.files;

  // Update UI previews
  imgSlots.forEach((slot, index) => {
    slot.innerHTML = ''; // clear the slot
    if (selectedFiles[index]) {
      const reader = new FileReader();
      reader.onload = function (e) {
        const newImg = document.createElement('img');
        newImg.src = e.target.result;
        slot.appendChild(newImg);
      };
      reader.readAsDataURL(selectedFiles[index]);
    } else {
      slot.innerHTML = '<span>+</span>';
    }
  });
 if (selectedFiles.length < 2 || selectedFiles.length > 6) {
    alert("Please select between 2 and 6 images.");
  }

});
</script>

</body>
</html>  