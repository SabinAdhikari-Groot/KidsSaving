<?php
session_start();
include 'db.php';

$parent_id = $_SESSION['user_id'];

// Approve completed task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_task'])) {
    $task_id = intval($_POST['task_id']);
    $user_id = intval($_POST['user_id']);
    
    // Get task value
    $task_query = "SELECT task_value FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($task_query);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $task_result = $stmt->get_result();
    $task = $task_result->fetch_assoc();
    $task_value = $task['task_value'];

    // Mark task as approved
    $approve_sql = "UPDATE tasks SET status='approved' WHERE id=?";
    $stmt = $conn->prepare($approve_sql);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();

    // Add earnings entry
    $insert_earnings = "INSERT INTO earnings (user_id, task_id, source, earned_date, amount) 
                       VALUES (?, ?, 'Task Completion', NOW(), ?)";
    $stmt = $conn->prepare($insert_earnings);
    $stmt->bind_param("iid", $user_id, $task_id, $task_value);
    $stmt->execute();

    // Update child's total earnings
    $update_earnings = "UPDATE user_earnings SET total_earnings = total_earnings + ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_earnings);
    $stmt->bind_param("di", $task_value, $user_id);
    $stmt->execute();
}

// Fetch completed tasks from this parent's children
$completed_tasks_sql = "SELECT t.*, u.first_name, u.last_name FROM tasks t
                      JOIN users u ON t.assigned_to = u.id
                      WHERE t.assigned_by = ? AND t.status='completed' 
                      ORDER BY t.completed_at DESC";
$stmt = $conn->prepare($completed_tasks_sql);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$completed_tasks_result = $stmt->get_result();
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
                <?php if ($completed_tasks_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Child</th>
                            <th>Value</th>
                            <th>Completed On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($task = $completed_tasks_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['task_name']) ?></td>
                            <td><?= htmlspecialchars($task['first_name'] . ' ' . $task['last_name']) ?></td>
                            <td>$<?= number_format($task['task_value'], 2) ?></td>
                            <td><?= date("M j, Y", strtotime($task['completed_at'])) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $task['assigned_to'] ?>">
                                    <button type="submit" name="approve_task" class="approve-btn">Approve</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No tasks waiting for approval.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>