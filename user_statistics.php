<?php
session_start();
include("db.php"); // Database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user information
$sql = "SELECT first_name, last_name FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch completed tasks
$sql_completed = "SELECT COUNT(*) AS completed_tasks FROM tasks WHERE assigned_to = ? AND status = 'approved'";
$stmt = $conn->prepare($sql_completed);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_tasks = $stmt->get_result()->fetch_assoc()['completed_tasks'] ?? 0;

// Fetch pending tasks
$sql_pending = "SELECT COUNT(*) AS pending_tasks FROM tasks WHERE assigned_to = ? AND status = 'pending'";
$stmt = $conn->prepare($sql_pending);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_tasks = $stmt->get_result()->fetch_assoc()['pending_tasks'] ?? 0;

// Fetch total earnings
$sql_earnings = "SELECT total_earnings FROM user_earnings WHERE user_id = ?";
$stmt = $conn->prepare($sql_earnings);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_earnings_result = $stmt->get_result()->fetch_assoc();
$total_earnings = $total_earnings_result['total_earnings'] ?? 0;

// Fetch savings in bank
$sql_savings = "SELECT balance FROM bank_accounts WHERE id = ?";
$stmt = $conn->prepare($sql_savings);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$savings_result = $stmt->get_result()->fetch_assoc();
$savings = $savings_result['balance'] ?? 0;

// Fetch recent task history
$sql_recent_tasks = "SELECT t.title, t.amount, t.status, t.completed_at 
                     FROM tasks t 
                     WHERE t.assigned_to = ? 
                     ORDER BY t.completed_at DESC 
                     LIMIT 5";
$stmt = $conn->prepare($sql_recent_tasks);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Statistics - KidsSaving</title>
    <link rel="stylesheet" href="parent_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <aside class="sidebar">
        <h2>👤 My Dashboard</h2>
        <ul>
            <li><a href="user_dashboard.php">🏠 Home</a></li>
            <li><a href="user_statistics.php">📊 Statistics</a></li>
            <li><a href="user_tasks.php">📝 My Tasks</a></li>
            <li><a href="user_bank.php">🏦 My Bank</a></li>
            <li><a href="user_chat.php">💬 Chat</a></li>
            <li><a href="user_account.php">⚙️ Account</a></li>
            <li><a href="user_help.php">❓ Help</a></li>
            <li><a href="logout.php">🚪 Log out</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="container">
            <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
            <p>Track your progress and financial journey.</p>

            <div class="overview">
                <h2>📊 My Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>✅ Completed Tasks</h3>
                        <p class="stat-number"><?php echo $completed_tasks; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>⏳ Pending Tasks</h3>
                        <p class="stat-number"><?php echo $pending_tasks; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>💰 Total Earnings</h3>
                        <p class="stat-number">$<?php echo number_format($total_earnings, 2); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>🏦 Bank Savings</h3>
                        <p class="stat-number">$<?php echo number_format($savings, 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="charts-container">
                <div class="chart-section">
                    <h2>📈 Recent Earnings</h2>
                    <canvas id="earningsChart"></canvas>
                </div>
                <div class="chart-section">
                    <h2>📊 Task Completion</h2>
                    <canvas id="tasksChart"></canvas>
                </div>
            </div>

            <div class="recent-tasks">
                <h2>📝 Recent Tasks</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Completed Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_tasks as $task): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['title']); ?></td>
                            <td>$<?php echo number_format($task['amount'], 2); ?></td>
                            <td><?php echo ucfirst($task['status']); ?></td>
                            <td><?php echo $task['completed_at'] ? date('M d, Y', strtotime($task['completed_at'])) : 'Pending'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <footer class="footer">
            <p>&copy; 2025 KidsSaving. Learn, Earn, and Save!</p>
        </footer>
    </div>

    <script>
    // Create the earnings chart
    const earningsCtx = document.getElementById('earningsChart').getContext('2d');
    new Chart(earningsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Monthly Earnings ($)',
                data: [<?php echo $total_earnings/6; ?>, <?php echo $total_earnings/5; ?>, 
                       <?php echo $total_earnings/4; ?>, <?php echo $total_earnings/3; ?>, 
                       <?php echo $total_earnings/2; ?>, <?php echo $total_earnings; ?>],
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.raw.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });

    // Create the tasks chart
    const tasksCtx = document.getElementById('tasksChart').getContext('2d');
    new Chart(tasksCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending'],
            datasets: [{
                data: [<?php echo $completed_tasks; ?>, <?php echo $pending_tasks; ?>],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(255, 206, 86, 0.6)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>
</body>
</html> 