<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wistie";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle approval/rejection actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = $_GET['id'];
    $action = $_GET['action'];

    if ($action == "approve") {
        $conn->query("UPDATE users SET status='approved' WHERE id=$userId");
    } elseif ($action == "reject") {
        $conn->query("UPDATE users SET status='rejected' WHERE id=$userId");
    }
}

// Fetch all users
$result = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Panel - Wistie</title>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f8f8f8;
      padding: 20px;
    }
    header {
      background-color: #ff5e62;
      color: white;
      padding: 16px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    h1 {
      text-align: center;
      color: #333;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      margin-top: 30px;
    }

    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }

    th {
      background-color: #ff5e62;
      color: white;
    }

    tr:hover {
      background-color: #f1f1f1;
    }

    .btn {
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
      text-decoration: none;
      color: white;
    }

    .approve {
      background-color: #28a745;
    }

    .reject {
      background-color: #dc3545;
    }

    .status {
      text-transform: capitalize;
      font-weight: bold;
    }

    .link-file {
      color: #007bff;
      text-decoration: none;
    }
  </style>
</head>
<body>
<header>
<h1>Admin Panel - Wistie</h1>
      <nav>
        <a href="index.html">🏠 </a>
        
      </nav>
    </header>

<table>
  <thead>
    <tr>
      <th>Full Name</th>
      <th>Phone</th>
      <th>Email</th>
      <th>DOB</th>
      <th>Photos</th>
      <th>Status</th>
      <th>Actions</th>
      <th>.....</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['fullname']) ?></td>
        <td><?= htmlspecialchars($row['phone']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= htmlspecialchars($row['dob']) ?></td>
        <td colspan="2">
  <div style="display: flex; flex-wrap: wrap; gap: 8px;">
    <?php
      $photos = json_decode($row['photos'], true);
      if ($photos && is_array($photos)) {
          foreach ($photos as $photo) {
              echo "<a href='$photo' target='_blank'><img src='$photo' style='width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #ccc;'></a>";
          }
      } else {
          echo "No images";
      }
    ?>
  </div>
</td>
        <td class="status"><?= $row['status'] ?? 'pending' ?></td>
        <td>
          <?php if ($row['status'] !== 'approved'): ?>
            <a class="btn approve" href="admin.php?action=approve&id=<?= $row['id'] ?>">Approve</a>
          <?php endif; ?>
          <?php if ($row['status'] !== 'rejected'): ?>
            <a class="btn reject" href="admin.php?action=reject&id=<?= $row['id'] ?>">Reject</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

</body>
</html>
