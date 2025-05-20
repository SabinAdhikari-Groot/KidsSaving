<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendVerificationEmail($email, $verificationCode) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kidssaving0@gmail.com';
        $mail->Password = 'qmbn lwqf weod jlht';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Set timeout
        $mail->Timeout = 10; // 10 seconds timeout
        $mail->SMTPKeepAlive = true;

        // Recipients
        $mail->setFrom('your-email@gmail.com', 'KidsSaving');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - KidsSaving';
        $mail->Body = "
            <h2>Welcome to KidsSaving!</h2>
            <p>Please use the following verification code to complete your registration:</p>
            <h1 style='color: #3498db; font-size: 24px;'>{$verificationCode}</h1>
            <p>This code will expire in 60 seconds.</p>
            <p>If you didn't request this verification, please ignore this email.</p>
        ";

        // Send email asynchronously
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle verification code generation and storage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Generate a 6-digit verification code
    $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store the verification code in the session
    session_start();
    $_SESSION['verification_code'] = $verificationCode;
    $_SESSION['verification_email'] = $email;
    $_SESSION['verification_time'] = time();

    // Send the verification email
    if (sendVerificationEmail($email, $verificationCode)) {
        // Return success immediately
        echo json_encode(['success' => true, 'message' => 'Verification code sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send verification code']);
    }
}
?>