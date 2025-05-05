<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch bank balance
$sql = "SELECT balance FROM bank_accounts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$balance = $result->num_rows > 0 ? $result->fetch_assoc()['balance'] : 0;

// Fetch total earnings
$sql = "SELECT total_earnings FROM user_earnings WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_earnings = $result->num_rows > 0 ? $result->fetch_assoc()['total_earnings'] : 0;

// Fetch task statistics
$sql = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tasks,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_tasks
    FROM tasks WHERE assigned_to = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$task_stats = $stmt->get_result()->fetch_assoc();

// Fetch saving goals
$sql = "SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$saving_goals = $stmt->get_result();

// Fetch recent transactions for chart
$sql = "SELECT amount, type, date FROM transactions WHERE account_id = ? ORDER BY date DESC LIMIT 7";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result();

// Fetch monthly earnings for chart
$sql = "SELECT DATE_FORMAT(earned_date, '%Y-%m') as month, SUM(amount) as total, user_id 
        FROM earnings e
        WHERE user_id IN (
            SELECT child_id 
            FROM parent_children_connection 
            WHERE parent_id = (
                SELECT parent_id 
                FROM parent_children_connection 
                WHERE child_id = ?
            )
        )
        GROUP BY DATE_FORMAT(earned_date, '%Y-%m'), user_id 
        ORDER BY month DESC LIMIT 30";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$monthly_earnings = $stmt->get_result();

// Get child names for the chart
$child_names = [];
$sql = "SELECT u.id, u.first_name, u.last_name 
        FROM users u
        JOIN parent_children_connection pcc ON u.id = pcc.child_id
        WHERE pcc.parent_id = (
            SELECT parent_id 
            FROM parent_children_connection 
            WHERE child_id = ?
        )";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$children_result = $stmt->get_result();
while ($row = $children_result->fetch_assoc()) {
    $child_names[$row['id']] = $row['first_name'] . ' ' . $row['last_name'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving Dashboard</title>
    <link rel="stylesheet" href="children_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <aside class="sidebar">
        <h2>🎮 KidsSaving</h2>
        <ul>
            <li><a href="children_dashboard.php" class="active">🏠 Home</a></li>
            <li><a href="children_learning.php">📚 Learning</a></li>
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
            <h1>Welcome to Your Dashboard! 🎉</h1>

            <!-- Quick Stats -->
            <div class="stats-table">
                <table>
                    <tbody>
                        <tr>
                            <th>💰 Bank Balance</th>
                            <th>💵 Total Earnings</th>
                            <th>📝 Pending Tasks</th>
                            <th>✅ Completed Tasks</th>
                        </tr>
                        <tr>
                            <td>$<?php echo number_format($balance, 2); ?></td>
                            <td>$<?php echo number_format($total_earnings, 2); ?></td>
                            <td><?php echo $task_stats['pending_tasks']; ?></td>
                            <td><?php echo $task_stats['completed_tasks']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <style>
            .stats-table {
                margin: 20px 0;
                overflow-x: auto;
            }

            .stats-table table {
                width: 100%;
                border-collapse: collapse;
                background: white;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
            }

            .stats-table th {
                background: #f8f9fa;
                padding: 15px;
                text-align: center;
                font-size: 1.1em;
                color: #333;
                border-bottom: 2px solid #dee2e6;
            }

            .stats-table td {
                padding: 15px;
                text-align: center;
                font-size: 1.2em;
                font-weight: 600;
                color: #2c3e50;
            }

            .stats-table tr:hover {
                background-color: #f8f9fa;
            }

            .stats-table th:first-child {
                border-top-left-radius: 8px;
            }

            .stats-table th:last-child {
                border-top-right-radius: 8px;
            }
            </style>

            <!-- Charts Section -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>Monthly Earnings</h3>
                    <canvas id="earningsChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Recent Transactions</h3>
                    <canvas id="transactionsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Monthly Earnings Chart
    const earningsCtx = document.getElementById('earningsChart').getContext('2d');
    new Chart(earningsCtx, {
        type: 'bar',
        data: {
            labels: <?php 
                    $labels = [];
                    $datasets = [];
                    $current_month = null;
                    $monthly_data = [];
                    
                    while ($row = $monthly_earnings->fetch_assoc()) {
                        $month = date('M Y', strtotime($row['month'] . '-01'));
                        if (!in_array($month, $labels)) {
                            $labels[] = $month;
                        }
                        if (!isset($monthly_data[$month])) {
                            $monthly_data[$month] = [];
                        }
                        $monthly_data[$month][$row['user_id']] = $row['total'];
                    }
                    
                    // Prepare datasets for each child
                    foreach ($child_names as $child_id => $child_name) {
                        $data = [];
                        foreach ($labels as $month) {
                            $data[] = $monthly_data[$month][$child_id] ?? 0;
                        }
                        $datasets[] = [
                            'label' => $child_name,
                            'data' => $data,
                            'backgroundColor' => sprintf('rgba(%d, %d, %d, 0.2)', 
                                rand(0, 255), rand(0, 255), rand(0, 255)),
                            'borderColor' => sprintf('rgba(%d, %d, %d, 1)', 
                                rand(0, 255), rand(0, 255), rand(0, 255)),
                            'borderWidth' => 1
                        ];
                    }
                    
                    echo json_encode(array_reverse($labels));
                ?>,
            datasets: <?php echo json_encode($datasets); ?>
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Earnings ($)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Monthly Earnings Comparison'
                }
            }
        }
    });

    // Recent Transactions Chart
    const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
    new Chart(transactionsCtx, {
        type: 'line',
        data: {
            labels: <?php 
                    $labels = [];
                    $deposits = [];
                    $withdrawals = [];
                    while ($row = $transactions->fetch_assoc()) {
                        $labels[] = date('M j', strtotime($row['date']));
                        if ($row['type'] === 'deposit') {
                            $deposits[] = $row['amount'];
                            $withdrawals[] = 0;
                        } else {
                            $deposits[] = 0;
                            $withdrawals[] = $row['amount'];
                        }
                    }
                    echo json_encode(array_reverse($labels));
                ?>,
            datasets: [{
                label: 'Deposits',
                data: <?php echo json_encode(array_reverse($deposits)); ?>,
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.1
            }, {
                label: 'Withdrawals',
                data: <?php echo json_encode(array_reverse($withdrawals)); ?>,
                borderColor: 'rgba(255, 99, 132, 1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>

</html>