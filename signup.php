<?php
// Start session to store errors or success messages
session_start();

// Include database connection
require_once 'db.php'; // Assuming you have a db.php file for DB connection

// Set header to JSON
header('Content-Type: application/json');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize form data
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];
    $firstName = htmlspecialchars(trim($_POST['first-name']));
    $lastName = htmlspecialchars(trim($_POST['last-name']));
    $dob = $_POST['dob'];
    $accountType = $_POST['account-type'];
    $terms = isset($_POST['terms']) ? 1 : 0;

    // Initialize response array
    $response = [
        'success' => false,
        'errors' => []
    ];

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    
    if ($checkEmail->num_rows > 0) {
        $response['errors']['email'] = 'This email is already registered.';
    }

    // Validate required fields
    if (empty($email)) {
        $response['errors']['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $response['errors']['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $response['errors']['password'] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword) {
        $response['errors']['confirm-password'] = 'Passwords do not match.';
    }

    if (empty($firstName)) {
        $response['errors']['first-name'] = 'First name is required.';
    } elseif (strlen($firstName) < 2) {
        $response['errors']['first-name'] = 'First name must be at least 2 characters long.';
    }

    if (empty($lastName)) {
        $response['errors']['last-name'] = 'Last name is required.';
    } elseif (strlen($lastName) < 2) {
        $response['errors']['last-name'] = 'Last name must be at least 2 characters long.';
    }

    if (empty($dob)) {
        $response['errors']['dob'] = 'Date of birth is required.';
    }

    if (empty($accountType)) {
        $response['errors']['account-type'] = 'Account type is required.';
    }

    if ($terms !== 1) {
        $response['errors']['terms'] = 'You must agree to the terms and services.';
    }

    // If there are no errors, proceed with registration
    if (empty($response['errors'])) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert data into the database
        $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, dob, account_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $email, $hashedPassword, $firstName, $lastName, $dob, $accountType);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Sign up successful. You can now log in.';
            $response['redirect'] = 'login.html';
        } else {
            $response['errors']['general'] = 'Something went wrong. Please try again.';
        }
    }

    // Send JSON response
    echo json_encode($response);
    exit();
}