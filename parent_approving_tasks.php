<?php
session_start();
include 'db.php';

$parent_id = $_SESSION['user_id'];
$message = '';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve_task'])) {
        $task_id = intval($_POST['task_id']);
        $user_id = intval($_POST['user_id']);

        // Get task value and name
        $stmt = $conn->prepare("SELECT task_value, task_name FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $task = $result->fetch_assoc();
        $task_value = $task['task_value'];
        $task_name = $task['task_name'];

        // Start transaction
        $conn->begin_transaction();
        try {
            // Update task status to approved
            $stmt = $conn->prepare("UPDATE tasks SET status='approved' WHERE id=?");
            $stmt->bind_param("i", $task_id);
            $stmt->execute();

            // Insert earnings entry
            $stmt = $conn->prepare("INSERT INTO earnings (user_id, task_id, source, earned_date, amount) 
                                    VALUES (?, ?, ?, NOW(), ?)");
            $stmt->bind_param("iisd", $user_id, $task_id, $task_name, $task_value);
            $stmt->execute();

            // Update user's total earnings
            $stmt = $conn->prepare("UPDATE user_earnings SET total_earnings = total_earnings + ? WHERE user_id = ?");
            $stmt->bind_param("di", $task_value, $user_id);
            $stmt->execute();

            // Update current goal's earnings if exists
            $stmt = $conn->prepare("UPDATE savings_goals SET current_earnings = current_earnings + ? WHERE user_id = ? AND status = 'active'");
            $stmt->bind_param("di", $task_value, $user_id);
            $stmt->execute();

            $conn->commit();
            $message = "Task approved and earnings added.";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error approving task: " . $e->getMessage();
        }
    }

    if (isset($_POST['reject_task'])) {
        $task_id = intval($_POST['task_id']);

        // Reset task status to pending
        $stmt = $conn->prepare("UPDATE tasks SET status='pending' WHERE id=?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();

        $message = "Task rejected and set back to pending.";
    }
}

// Fetch completed tasks assigned by this parent
$stmt = $conn->prepare("SELECT t.*, u.first_name, u.last_name 
                        FROM tasks t 
                        JOIN users u ON t.assigned_to = u.id 
                        WHERE t.assigned_by = ? AND t.status = 'completed' 
                        ORDER BY t.completed_at DESC");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$completed_tasks_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Completed Tasks</title>
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
            <p>Review and approve or reject tasks completed by your children.</p>

            <?php if (!empty($message)): ?>
            <p class="success"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

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
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $task['assigned_to'] ?>">
                                    <button type="submit" name="approve_task" class="approve-btn">Approve</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <button type="submit" name="reject_task" class="reject-btn">Reject</button>
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