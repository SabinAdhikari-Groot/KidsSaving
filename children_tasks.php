<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];

// Mark task as completed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);
    $update_sql = "UPDATE tasks SET status='completed', completed_at=NOW() WHERE id=? AND assigned_to=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
}

// Fetch pending tasks assigned to this child
$pending_sql = "SELECT * FROM tasks WHERE assigned_to=? AND status='pending'";
$stmt = $conn->prepare($pending_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_result = $stmt->get_result();

// Fetch completed tasks (awaiting approval)
$completed_sql = "SELECT * FROM tasks WHERE assigned_to=? AND status='completed' ORDER BY completed_at DESC";
$stmt = $conn->prepare($completed_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_result = $stmt->get_result();

// Fetch approved tasks (with earnings)
$approved_sql = "SELECT t.*, e.amount FROM tasks t 
                LEFT JOIN earnings e ON e.task_id = t.id 
                WHERE t.assigned_to=? AND t.status='approved' 
                ORDER BY t.completed_at DESC LIMIT 5";
$stmt = $conn->prepare($approved_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$approved_result = $stmt->get_result();
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
                <h2>📌 Tasks To Do</h2>
                <?php if ($pending_result->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Task</th>
                        <th>Value</th>
                        <th>Action</th>
                    </tr>
                    <?php while ($task = $pending_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['task_name']) ?></td>
                        <td>$<?= number_format($task['task_value'], 2) ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <button class="complete-btn" type="submit">Mark as Done</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <?php else: ?>
                <p>No pending tasks available.</p>
                <?php endif; ?>
            </div>

            <!-- Completed Tasks (Waiting Approval) -->
            <div class="task-list">
                <h2>⏳ Waiting for Approval</h2>
                <?php if ($completed_result->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Task</th>
                        <th>Value</th>
                        <th>Completed On</th>
                    </tr>
                    <?php while ($task = $completed_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['task_name']) ?></td>
                        <td>$<?= number_format($task['task_value'], 2) ?></td>
                        <td><?= date("M j, Y", strtotime($task['completed_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <?php else: ?>
                <p>No tasks waiting for approval.</p>
                <?php endif; ?>
            </div>

            <!-- Approved Tasks -->
            <div class="task-list">
                <h2>✅ Approved Tasks</h2>
                <?php if ($approved_result->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Task</th>
                        <th>Earned</th>
                        <th>Approved On</th>
                    </tr>
                    <?php while ($task = $approved_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['task_name']) ?></td>
                        <td>$<?= number_format($task['amount'], 2) ?></td>
                        <td><?= date("M j, Y", strtotime($task['completed_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <?php else: ?>
                <p>No approved tasks yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>