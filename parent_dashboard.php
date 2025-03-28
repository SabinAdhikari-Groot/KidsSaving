<?php
session_start();
include("db.php"); // Database connection file

// Assuming parent_id is stored in session after login
$parent_id = $_SESSION['id'] ?? 3; // Defaulting to 3 for testing

// Fetch children of the logged-in parent
$sql = "SELECT u.id, u.first_name, u.last_name 
        FROM users u
        INNER JOIN parent_children_connection pcr ON u.id = pcr.child_id
        WHERE pcr.parent_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$children = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - KidsSaving</title>
    <link rel="stylesheet" href="parent_dashboard.css">
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
            <h1>Welcome, Parent!</h1>
            <p>Monitor your child's progress and manage their financial journey.</p>
            <div class="overview">
                <h2>📊 Child Overview</h2>
                <table>
                    <thead>
                        <tr>
                            <th>👦 Child Name</th>
                            <th>✅ Completed Tasks</th>
                            <th>⏳ Pending Tasks</th>
                            <th>💰 Total Earnings</th>
                            <th>🏦 Savings in Bank</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($children as $child) {
                            $child_id = $child['id'];
                            $child_name = $child['first_name'] . ' ' . $child['last_name'];

                            // Fetch completed tasks
                            $sql_completed = "SELECT COUNT(*) AS completed_tasks FROM tasks WHERE id = ? AND status = 'completed'";
                            $stmt = $conn->prepare($sql_completed);
                            $stmt->bind_param("i", $child_id);
                            $stmt->execute();
                            $completed_tasks = $stmt->get_result()->fetch_assoc()['completed_tasks'] ?? 0;

                            // Fetch pending tasks
                            $sql_pending = "SELECT COUNT(*) AS pending_tasks FROM tasks WHERE id = ? AND status = 'pending'";
                            $stmt = $conn->prepare($sql_pending);
                            $stmt->bind_param("i", $child_id);
                            $stmt->execute();
                            $pending_tasks = $stmt->get_result()->fetch_assoc()['pending_tasks'] ?? 0;

                            // Fetch total earnings
                            // $sql_earnings = "SELECT SUM(amount) AS total_earnings FROM user_earnings WHERE user_id = ?";
                            // $stmt = $conn->prepare($sql_earnings);
                            // $stmt->bind_param("i", $child_id);
                            // $stmt->execute();
                            // $total_earnings = $stmt->get_result()->fetch_assoc()['total_earnings'] ?? 0;

                            // Fetch savings in bank
                            $sql_savings = "SELECT balance FROM bank_accounts WHERE account_id = ?";
                            $stmt = $conn->prepare($sql_savings);
                            $stmt->bind_param("i", $child_id);
                            $stmt->execute();
                            $savings = $stmt->get_result()->fetch_assoc()['balance'] ?? 0;

                            echo "<tr>
                                    <td>$child_name</td>
                                    <td>$completed_tasks</td>
                                    <td>$pending_tasks</td>
                                    <td>$14</td>
                                    <td>$$savings</td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <footer class="footer">
            <p>&copy; 2025 KidsSaving. Teach, Track, and Guide!</p>
        </footer>
    </div>
</body>

</html>