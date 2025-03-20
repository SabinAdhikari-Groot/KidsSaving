<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kids_saving";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Approve completed task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_task'])) {
    $task_id = intval($_POST['task_id']);
    $user_id = intval($_POST['user_id']); // Child's ID
    $earnings_amount = 10; // Fixed earning (can be changed dynamically)

    // Mark task as approved
    $approve_sql = "UPDATE tasks SET status='approved' WHERE id=$task_id";
    $conn->query($approve_sql);

    // Add earnings entry
    $insert_earnings = "INSERT INTO earnings (user_id, source, earned_date, amount) VALUES ('$user_id', 'Task Completion', NOW(), '$earnings_amount')";
    $conn->query($insert_earnings);

    // Update child's total earnings
    $update_child_earnings = "UPDATE children_earnings SET current_earning = current_earning + $earnings_amount WHERE user_id = '$user_id'";
    $conn->query($update_child_earnings);
}

// Fetch completed tasks for approval
$completed_tasks_sql = "SELECT * FROM tasks WHERE status='completed'";
$completed_tasks_result = $conn->query($completed_tasks_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Tasks</title>
    <link rel="stylesheet" href="parent_approving_tasks.css">
</head>

<body>
    <aside class="sidebar">
        <h2>👨‍👩‍👧‍👦 Parent Dashboard</h2>
        <ul>
            <li><a href="parent_dashboard.php">🏠 Home</a></li>
            <li><a href="parent_managing_tasks.php">📝 Manage Tasks</a></li>
            <li><a href="parent_approving_tasks.php">✅ Approve Tasks</a></li>
            <li><a href="parent_tracking_earnings.php">💰 Track Earnings</a></li>
            <li><a href="parent_chat.php">💬 Chat</a></li>
            <li><a href="parent_account.php">⚙️ Account</a></li>
            <li><a href="parent_help.php">❓ Help</a></li>
            <li><a href="logout.php">🚪 Log out</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="container">
            <h1>✅ Approve Completed Tasks</h1>
            <p>Review and approve tasks completed by your children.</p>

            <!-- Task Approval List -->
            <div class="task-list">
                <table>
                    <tr>
                        <th>Task</th>
                        <th>Child Name</th>
                        <th>Actions</th>
                    </tr>
                    <?php while ($task = $completed_tasks_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['task_name']) ?></td>
                        <td> Sabin Adhikari</td> <!-- Ideally, fetch child's name -->
                        <td>
                            <form method="POST">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <input type="hidden" name="user_id" value="<?= $task['id'] ?>">
                                <button type="submit" name="approve_task">Approve</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>