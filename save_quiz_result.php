<?php
// Prevent any output before JSON response
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

try {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "kids_saving";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data received');
    }

    if (!isset($data['score']) || !isset($data['points'])) {
        throw new Exception('Missing required data (score or points)');
    }

    $user_id = $_SESSION['user_id'];
    $score = (int)$data['score'];
    $points = (float)$data['points'];
    $attempt_date = date('Y-m-d');

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert quiz attempt
        $sql = "INSERT INTO quiz_attempts (user_id, attempt_date, score, points_awarded) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("isid", $user_id, $attempt_date, $score, $points);
        
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        // Update user_earnings
        $update_earnings = "UPDATE user_earnings SET total_earnings = total_earnings + ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_earnings);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("di", $points, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Quiz results saved successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>