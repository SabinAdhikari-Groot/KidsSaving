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

// Add new task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_task'])) {
    $task_name = $conn->real_escape_string($_POST['task_name']);
    $insert_sql = "INSERT INTO tasks (task_name, status) VALUES ('$task_name', 'pending')";
    $conn->query($insert_sql);
}

// Edit task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_task'])) {
    $task_id = intval($_POST['task_id']);
    $task_name = $conn->real_escape_string($_POST['task_name']);
    $update_sql = "UPDATE tasks SET task_name='$task_name' WHERE id=$task_id";
    $conn->query($update_sql);
}

// Delete task
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_task'])) {
    $task_id = intval($_POST['task_id']);
    $delete_sql = "DELETE FROM tasks WHERE id=$task_id";
    $conn->query($delete_sql);
}

// Fetch all tasks
$tasks_sql = "SELECT * FROM tasks ORDER BY id DESC";
$tasks_result = $conn->query($tasks_sql);

$conn->close();
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
            <p>Create, update, or delete tasks for your children.</p>

            <!-- Add Task Form -->
            <form method="POST">
                <input type="text" name="task_name" placeholder="Enter task name" required>
                <button type="submit" name="add_task">Add Task</button>
            </form>

            <!-- Task List -->
            <div class="task-list">
                <h2>📌 Task List</h2>
                <table>
                    <tr>
                        <th>Task</th>
                        <th>Actions</th>
                    </tr>
                    <?php while ($task = $tasks_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['task_name']) ?></td>
                        <td>
                            <!-- Edit Task Form -->
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <input type="text" name="task_name" value="<?= htmlspecialchars($task['task_name']) ?>"
                                    required>
                                <button type="submit" name="edit_task">Save changes</button>
                            </form>

                            <!-- Delete Task Form -->
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <button type="submit" name="delete_task">Delete</button>
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