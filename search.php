<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['term']) || !isset($_GET['type'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$searchTerm = mysqli_real_escape_string($conn, $_GET['term']);
$type = $_GET['type'];

switch($type) {
    case 'users':
        $query = "SELECT * FROM users WHERE 
                 email LIKE '%$searchTerm%' OR 
                 first_name LIKE '%$searchTerm%' OR 
                 last_name LIKE '%$searchTerm%' OR 
                 account_type LIKE '%$searchTerm%'";
        break;
    case 'notes':
        $query = "SELECT * FROM finance_notes WHERE 
                 title LIKE '%$searchTerm%' OR 
                 content LIKE '%$searchTerm%'";
        break;
    case 'quizzes':
        $query = "SELECT * FROM quizzes WHERE 
                 question LIKE '%$searchTerm%' OR 
                 option_a LIKE '%$searchTerm%' OR 
                 option_b LIKE '%$searchTerm%' OR
                 option_c LIKE '%$searchTerm%'";
        break;
    case 'store':
        $query = "SELECT * FROM store_items WHERE 
                 item_name LIKE '%$searchTerm%'";
        break;
    case 'queries':
        $query = "SELECT * FROM user_query WHERE message LIKE '%$searchTerm%' ORDER BY submitted_at DESC";
        break;
    default:
        echo json_encode(['error' => 'Invalid search type']);
        exit;
}

$result = mysqli_query($conn, $query);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    if($type == 'queries') {
        $row['message'] = htmlspecialchars($row['message']);
        $row['user_email'] = htmlspecialchars($row['user_email']);
    }
    $data[] = $row;
}

echo json_encode($data);
?>