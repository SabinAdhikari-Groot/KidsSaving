<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    
    // Check if verification code exists in session
    if (!isset($_SESSION['verification_code']) || !isset($_SESSION['verification_time'])) {
        echo json_encode(['success' => false, 'message' => 'Verification session expired']);
        exit;
    }

    if (time() - $_SESSION['verification_time'] > 60) {
        echo json_encode(['success' => false, 'message' => 'Verification code has expired']);
        exit;
    }

    // Verify the code
    if ($code === $_SESSION['verification_code']) {
        // Clear verification data from session
        unset($_SESSION['verification_code']);
        unset($_SESSION['verification_time']);
        
        echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>