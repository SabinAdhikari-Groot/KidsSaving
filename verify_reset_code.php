<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!isset($_SESSION['verification_code']) || !isset($_SESSION['verification_email']) || 
        !isset($_SESSION['verification_time'])) {
        echo json_encode(['success' => false, 'message' => 'Verification code expired']);
        exit;
    }

    // Check if code has expired (60 seconds)
    if (time() - $_SESSION['verification_time'] > 60) {
        echo json_encode(['success' => false, 'message' => 'Verification code expired']);
        exit;
    }

    // Verify code and email match
    if ($code === $_SESSION['verification_code'] && $email === $_SESSION['verification_email']) {
        // Store verification success in session
        $_SESSION['reset_verified'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
    }
}
?> 