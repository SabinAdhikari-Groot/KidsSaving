<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: children_login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $goal_name = $_POST['goal_name'];
    $target_amount = floatval($_POST['target_amount']);

    // Validate input
    if (empty($goal_name) || $target_amount <= 0) {
        $_SESSION['error'] = "Please provide a valid goal name and target amount.";
        header('Location: children_earnings.php');
        exit();
    }

    // Insert new goal
    $insert_query = "INSERT INTO savings_goals (user_id, goal_name, target_amount, amount_saved) VALUES (?, ?, ?, 0)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isd", $user_id, $goal_name, $target_amount);

    if ($stmt->execute()) {
        $_SESSION['success'] = "New savings goal created successfully!";
    } else {
        $_SESSION['error'] = "Error creating savings goal. Please try again.";
    }

    header('Location: children_earnings.php');
    exit();
} else {
    header('Location: children_earnings.php');
    exit();
}