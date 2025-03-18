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

// Mark task as completed if "Done" button is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);
    $update_sql = "UPDATE tasks SET status='completed', completed_at=NOW() WHERE id=$task_id";
    $conn->query($update_sql);
}

// Fetch undone (pending) tasks
$pending_sql = "SELECT * FROM tasks WHERE status='pending'";
$pending_result = $conn->query($pending_sql);

// Fetch latest 5 completed tasks
$completed_sql = "SELECT * FROM tasks WHERE status='completed' ORDER BY completed_at DESC LIMIT 5";
$completed_result = $conn->query($completed_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving Tasks</title>
    <link rel="stylesheet" href="children_tasks.css">
</head>

<body>
    <aside class="sidebar">
        <h2>🎮 KidsSaving</h2>
        <ul>
            <li><a href="children_dashboard.php">🏠 Home</a></li>
            <li><a href="children_tasks.php">📝 Tasks</a></li>
            <li><a href="children_earnings.php">💰 Earnings</a></li>
            <li><a href="children_virtual_bank.php">🏦 Virtual Bank</a></li>
            <li><a href="virtual_store.php">🛍️ Virtual Store</a></li>
            <li><a href="leaderboard.php">🏆 Leaderboard</a></li>
            <li><a href="children_chat.php">💬 Chat</a></li>
            <li><a href="children_account.php">👤 Account</a></li>
            <li><a href="children_help.php">❓ Help</a></li>
            <li><a href="children_logout.php">🚪 Log out</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="container">
            <h1>📝 Your Tasks</h1>
            <p>Complete tasks and earn rewards to improve your financial skills!</p>

            <!-- Pending Tasks -->
            <div class="task-list">
                <h2>📌 Your Pending Tasks</h2>
                <table>
                    <tr>
                        <th>Task</th>
                        <th>Action</th>
                    </tr>
                    <?php if ($pending_result->num_rows > 0): ?>
                    <?php while ($task = $pending_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['task_name']) ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <button class="approve-btn" type="submit">Done</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="2">No pending tasks available.</td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Recent Completed Tasks -->
            <div class="task-list completed-tasks">
                <h2>✅ Recently Completed Tasks</h2>
                <table>
                    <tr>
                        <th>Task</th>
                        <th>Completed At</th>
                    </tr>
                    <?php if ($completed_result->num_rows > 0): ?>
                    <?php while ($task = $completed_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['task_name']) ?></td>
                        <td><?= date("F j, Y, g:i a", strtotime($task['completed_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="2">No completed tasks yet.</td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>