<?php
// db.php - Database connection

$servername = "localhost";  // Database server (e.g., localhost)
$username = "root";         // Your MySQL username
$password = "";             // Your MySQL password
$dbname = "kids_saving";    // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
