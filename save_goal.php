<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $goal_name = $_POST['goal_name'];
    $target_amount = $_POST['target_amount'];

    // Insert the new goal into the database
    $insert_query = "INSERT INTO savings_goals (user_id, goal_name, target_amount, amount_saved) VALUES (?, ?, ?, 0)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isd", $user_id, $goal_name, $target_amount);

    if ($stmt->execute()) {
        // Return the updated goal display
        echo "<div class='goal'>
                <p>Goal: " . htmlspecialchars($goal_name) . " 🏆</p>
                <p>Target Amount: $" . number_format($target_amount, 2) . "</p>
                <p>Amount Saved: $0.00</p>
                <progress value='0' max='$target_amount'></progress>
              </div>";
    } else {
        echo "<p>Error: " . $conn->error . "</p>";
    }
}