<?php
// Start session to store errors or success messages
session_start();

// Include database connection
require_once 'db.php'; // Assuming you have a db.php file for DB connection

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

    // Initialize an error array
    $errors = [];

    // Validate required fields
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    if (empty($firstName)) {
        $errors[] = 'First name is required.';
    }
    if (empty($lastName)) {
        $errors[] = 'Last name is required.';
    }
    if (empty($dob)) {
        $errors[] = 'Date of birth is required.';
    }
    if (empty($accountType)) {
        $errors[] = 'Account type is required.';
    }
    if ($terms !== 1) {
        $errors[] = 'You must agree to the terms and services.';
    }

    // If there are any errors, redirect back with the error messages
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: signup.php'); // Redirect back to the signup page
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert data into the database (assuming you have a `users` table)
    $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, dob, account_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $email, $hashedPassword, $firstName, $lastName, $dob, $accountType);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Sign up successful. You can now log in.';
        header('Location: login.html'); // Redirect to login page after successful sign-up
        exit();
    } else {
        $_SESSION['errors'] = ['Something went wrong. Please try again.'];
        header('Location: signup.html'); // Redirect back if there's an error
        exit();
    }
}