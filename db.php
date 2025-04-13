<?php
// db.php - Database connection

$servername = "localhost";  // Database server (e.g., localhost)
$username = "root";         // Your MySQL username
$password = "";             // Your MySQL password
$dbname = "kids_saving";    // Database name

// Create connection without selecting database
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if database exists
$result = mysqli_query($conn, "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
if (mysqli_num_rows($result) == 0) {
    // Create database
    if (!mysqli_query($conn, "CREATE DATABASE $dbname")) {
        die("Error creating database: " . mysqli_error($conn));
    }
}

// Select the database
if (!mysqli_select_db($conn, $dbname)) {
    die("Error selecting database: " . mysqli_error($conn));
}
