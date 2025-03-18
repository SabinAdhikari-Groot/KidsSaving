<?php
session_start();
include 'db.php'; // Ensure this file contains the database connection

// Fetch number of parents
$parent_count_query = "SELECT COUNT(*) AS total FROM users WHERE account_type = 'Parent'";
$parent_count_result = mysqli_query($conn, $parent_count_query);
$parent_count = mysqli_fetch_assoc($parent_count_result)['total'];

// Fetch number of children
$child_count_query = "SELECT COUNT(*) AS total FROM users WHERE account_type = 'Child'";
$child_count_result = mysqli_query($conn, $child_count_query);
$child_count = mysqli_fetch_assoc($child_count_result)['total'];

// Fetch number of quizzes
$quiz_count_query = "SELECT COUNT(*) AS total FROM quizzes";
$quiz_count_result = mysqli_query($conn, $quiz_count_query);
$quiz_count = mysqli_fetch_assoc($quiz_count_result)['total'];

// Fetch number of finance notes
$notes_count_query = "SELECT COUNT(*) AS total FROM finance_notes";
$notes_count_result = mysqli_query($conn, $notes_count_query);
$notes_count = mysqli_fetch_assoc($notes_count_result)['total'];

// Fetch number of tasks
$tasks_count_query = "SELECT COUNT(*) AS total FROM tasks";
$tasks_count_result = mysqli_query($conn, $tasks_count_query);
$tasks_count = mysqli_fetch_assoc($tasks_count_result)['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving Admin Dashboard</title>
    <link rel="stylesheet" href="admin_dashboard.css">
</head>

<body>
    <aside class="sidebar">
        <h1>KidsSaving</h1>
        <ul>
            <li><a href="admin_dashboard.php">Home</a></li>
            <li><a href="admin_managing_users.php">Manage Users</a></li>
            <li><a href="admin_adding_notes.php">Manage Finance Notes</a></li>
            <li><a href="admin_managing_quizzes.php">Manage Quizzes</a></li>
            <li><a href="logout.php">Log out</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <h1>Welcome to Admin Dashboard</h1>
        <table border="1">
            <tr>
                <th>Category</th>
                <th>Count</th>
            </tr>
            <tr>
                <td>Number of Parents</td>
                <td><?php echo $parent_count; ?></td>
            </tr>
            <tr>
                <td>Number of Children</td>
                <td><?php echo $child_count; ?></td>
            </tr>
            <tr>
                <td>Number of Quizzes</td>
                <td><?php echo $quiz_count; ?></td>
            </tr>
            <tr>
                <td>Number of Finance Notes</td>
                <td><?php echo $notes_count; ?></td>
            </tr>
            <tr>
                <td>Number of Tasks</td>
                <td><?php echo $tasks_count; ?></td>
            </tr>
        </table>
    </div>
</body>

</html>