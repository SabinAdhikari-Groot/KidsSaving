<?php
session_start();
include 'db.php';

$parent_id = $_SESSION['user_id'];

// Fetch children for dropdown
$children_query = "SELECT u.id, u.first_name, u.last_name FROM users u
                  JOIN parent_children_connection pc ON pc.child_id = u.id
                  WHERE pc.parent_id = ?";
$stmt = $conn->prepare($children_query);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$children_result = $stmt->get_result();

// Add new task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_task'])) {
    $task_name = $_POST['task_name'];
    $task_value = floatval($_POST['task_value']);
    $assigned_to = intval($_POST['assigned_to']);
    
    $insert_sql = "INSERT INTO tasks (task_name, task_value, assigned_to, assigned_by) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sdii", $task_name, $task_value, $assigned_to, $parent_id);
    $stmt->execute();
}

// Update task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_task'])) {
    $task_id = intval($_POST['task_id']);
    $task_name = $_POST['task_name'];
    $task_value = floatval($_POST['task_value']);
    $assigned_to = intval($_POST['assigned_to']);
    
    $update_sql = "UPDATE tasks SET task_name = ?, task_value = ?, assigned_to = ? WHERE id = ? AND assigned_by = ? AND status = 'pending'";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sdiis", $task_name, $task_value, $assigned_to, $task_id, $parent_id);
    $stmt->execute();
}

// Delete task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_task'])) {
    $task_id = intval($_POST['task_id']);
    $delete_sql = "DELETE FROM tasks WHERE id = ? AND assigned_by = ? AND status = 'pending'";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $task_id, $parent_id);
    $stmt->execute();
}

// Fetch all tasks assigned by this parent
$tasks_sql = "SELECT t.*, u.first_name, u.last_name FROM tasks t
             JOIN users u ON t.assigned_to = u.id
             WHERE t.assigned_by = ? ORDER BY t.created_at DESC";
$stmt = $conn->prepare($tasks_sql);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$tasks_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tasks</title>
    <link rel="stylesheet" href="parent_managing_tasks.css">
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
            <h1>📝 Manage Tasks</h1>
            <p>Create and assign tasks to your children.</p>

            <!-- Add Task Form -->
            <form method="POST" class="task-form">
                <div class="form-group">
                    <label for="task_name">Task Name:</label>
                    <input type="text" id="task_name" name="task_name" required>
                </div>
                <div class="form-group">
                    <label for="task_value">Value ($):</label>
                    <input type="number" id="task_value" name="task_value" min="0.01" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="assigned_to">Assign To:</label>
                    <select id="assigned_to" name="assigned_to" required>
                        <?php while ($child = $children_result->fetch_assoc()): ?>
                        <option value="<?= $child['id'] ?>">
                            <?= htmlspecialchars($child['first_name'] . ' ' . $child['last_name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" name="add_task">Add Task</button>
            </form>

            <!-- Task List -->
            <div class="task-list">
                <h2>📌 Assigned Tasks</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Assigned To</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($task = $tasks_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($task['status'] == 'pending'): ?>
                                <form method="POST" class="edit-task-form" style="display: inline;">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <input type="text" name="task_name"
                                        value="<?= htmlspecialchars($task['task_name']) ?>" required>
                                    <?php else: ?>
                                    <?= htmlspecialchars($task['task_name']) ?>
                                    <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($task['status'] == 'pending'): ?>
                                <select name="assigned_to" required>
                                    <?php 
                                        $children_result->data_seek(0); // Reset the result pointer
                                        while ($child = $children_result->fetch_assoc()): 
                                        ?>
                                    <option value="<?= $child['id'] ?>"
                                        <?= $child['id'] == $task['assigned_to'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($child['first_name'] . ' ' . $child['last_name']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php else: ?>
                                <?= htmlspecialchars($task['first_name'] . ' ' . $task['last_name']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($task['status'] == 'pending'): ?>
                                <input type="number" name="task_value" value="<?= $task['task_value'] ?>" min="0.01"
                                    step="0.01" required>
                                <?php else: ?>
                                $<?= number_format($task['task_value'], 2) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= ucfirst($task['status']) ?></td>
                            <td>
                                <?php if ($task['status'] == 'pending'): ?>
                                <button type="submit" name="update_task" class="update-btn">Update</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <button type="submit" name="delete_task" class="delete-btn">Delete</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>