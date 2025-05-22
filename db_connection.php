<?php
// db_connection.php
$host = "localhost";      // Database host (usually 'localhost')
$username = "root";       // Database username (default for XAMPP is 'root')
$password = "";           // Database password (default for XAMPP is empty)
$database = "wistie";     // Your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Store connection in a global variable
$GLOBALS['conn'] = $conn;
?>