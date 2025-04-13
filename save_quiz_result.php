<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kids_saving";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$score = $data['score'];
$points = $data['points'];
$today = date('Y-m-d');

// Record the attempt
$sql = "INSERT INTO quiz_attempts (user_id, attempt_date, score, points_awarded) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isii", $user_id, $today, $score, $points);
$stmt->execute();

// Update user's points
$update_points = "UPDATE users SET points = points + ? WHERE id = ?";
$stmt = $conn->prepare($update_points);
$stmt->bind_param("ii", $points, $user_id);
$stmt->execute();

$conn->close();
echo json_encode(['success' => true]);
?>