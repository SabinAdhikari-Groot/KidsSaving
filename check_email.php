<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['exists' => false, 'message' => 'Invalid email address']);
        exit;
    }

    try {
        // Use $conn for MySQLi and bind_param
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['exists' => true]);
        } else {
            echo json_encode(['exists' => false, 'message' => 'No account found with this email']);
        }
    } catch (Exception $e) { // Use generic Exception for broader error catching
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['exists' => false, 'message' => 'Database error occurred']);
    }
}
?>