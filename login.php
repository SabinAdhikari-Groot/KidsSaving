<?php
// Start session to store error messages or success
session_start();

// Include database connection
require_once 'db.php'; // Assuming you have a db.php file for DB connection

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize form data
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password'];
    $terms = isset($_POST['terms']) ? 1 : 0; // Assuming user must accept terms

    // Check for admin login first
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_email'] = 'admin';
        $_SESSION['user_type'] = 'Admin';
        header('Location: admin_dashboard.php');
        exit();
    }

    // Initialize an error array
    $errors = [];

    // Validate required fields
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    if ($terms !== 1) {
        $errors[] = 'You must agree to the terms and services.';
    }

    // If there are validation errors, display them
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: login.php');
        exit();
    }

    // Query the database for the user with the provided username
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Store user data in session for later use
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['account_type'];

            // Redirect to the appropriate dashboard based on user type
            if ($user['account_type'] == 'Child') {
                header('Location: children_dashboard.php'); // Child dashboard page
            } else {
                header('Location: parent_dashboard.php'); // Parent dashboard page
            }
            exit();
        } else {
            // If password is incorrect
            $_SESSION['errors'] = ['Incorrect password. Please try again.'];
            header('Location: login.html');
            exit();
        }
    } else {
        // If no user is found
        $_SESSION['errors'] = ['No user found with this email address.'];
        header('Location: login.html');
        exit();
    }
}
?>