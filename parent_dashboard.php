<?php
session_start();
include("db.php"); // Database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['user_id'];

// Check for orphaned child accounts and clean up
$cleanup_sql = "SELECT pcr.child_id 
                FROM parent_children_connection pcr 
                LEFT JOIN users u ON pcr.child_id = u.id 
                WHERE pcr.parent_id = ? AND u.id IS NULL";
$stmt = $conn->prepare($cleanup_sql);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$orphaned_children = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($orphaned_children)) {
    $conn->begin_transaction();
    try {
        foreach ($orphaned_children as $child) {
            $child_id = $child['child_id'];

            // Delete from parent_children_connection
            $stmt = $conn->prepare("DELETE FROM parent_children_connection WHERE child_id = ?");
            $stmt->bind_param("i", $child_id);
            $stmt->execute();

            // Delete from tasks
            $stmt = $conn->prepare("DELETE FROM tasks WHERE assigned_to = ?");
            $stmt->bind_param("i", $child_id);
            $stmt->execute();

            // Delete from earnings
            $stmt = $conn->prepare("DELETE FROM earnings WHERE user_id = ?");
            $stmt->bind_param("i", $child_id);
            $stmt->execute();

            // Delete from user_earnings
            $stmt = $conn->prepare("DELETE FROM user_earnings WHERE user_id = ?");
            $stmt->bind_param("i", $child_id);
            $stmt->execute();

            // Delete from bank_accounts
            $stmt = $conn->prepare("DELETE FROM bank_accounts WHERE id = ?");
            $stmt->bind_param("i", $child_id);
            $stmt->execute();

            // Delete from savings_goals
            $stmt = $conn->prepare("DELETE FROM savings_goals WHERE user_id = ?");
            $stmt->bind_param("i", $child_id);
            $stmt->execute();

            // Delete from transactions
            $stmt = $conn->prepare("DELETE FROM transactions WHERE account_id = ?");
            $stmt->bind_param("i", $child_id);
            $stmt->execute();
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        // Log error if needed
    }
}

// Fetch children of the logged-in parent
$sql = "SELECT u.id, u.first_name, u.last_name 
        FROM users u
        INNER JOIN parent_children_connection pcr ON u.id = pcr.child_id
        WHERE pcr.parent_id = ? AND u.account_type = 'Child'";
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        flex-direction: column;
    }

    .loading-spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin-bottom: 10px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
    </style>
</head>

<body>
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
        <p>Loading dashboard...</p>
    </div>

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
                            <th>💰 Current Earnings</th>
                            <th>🏦 Savings in Bank</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($children as $child) {
                            $child_id = $child['id'];
                            $child_name = htmlspecialchars($child['first_name'] . ' ' . $child['last_name']);

                            // Fetch completed tasks
                            $sql_completed = "SELECT COUNT(*) AS completed_tasks FROM tasks WHERE assigned_to = ? AND status = 'approved'";
                            $stmt = $conn->prepare($sql_completed);
                            $stmt->bind_param("i", $child_id);
                            $stmt->execute();
                            $completed_tasks = $stmt->get_result()->fetch_assoc()['completed_tasks'] ?? 0;

                            // Fetch pending tasks
                            $sql_pending = "SELECT COUNT(*) AS pending_tasks FROM tasks WHERE assigned_to = ? AND status = 'pending'";
                            $stmt = $conn->prepare($sql_pending);
                            $stmt->bind_param("i", $child_id);
                            $stmt->execute();
                            $pending_tasks = $stmt->get_result()->fetch_assoc()['pending_tasks'] ?? 0;

                            // Fetch total earnings from earnings table
                            $sql_earnings = "SELECT total_earnings FROM user_earnings WHERE user_id = ?";
                            $stmt = $conn->prepare($sql_earnings);
                            $stmt->bind_param("i", $child_id);
                            $stmt->execute();
                            $total_earnings_result = $stmt->get_result()->fetch_assoc();
                            $total_earnings = $total_earnings_result['total_earnings'] ?? 0;

                            // Fetch savings in bank
                            $sql_savings = "SELECT balance FROM bank_accounts WHERE id = ?";
                            $stmt = $conn->prepare($sql_savings);
                            $stmt->bind_param("i", $child_id);
                            $stmt->execute();
                            $savings_result = $stmt->get_result()->fetch_assoc();
                            $savings = $savings_result['balance'] ?? 0;

                            echo "<tr>
                                    <td>$child_name</td>
                                    <td>$completed_tasks</td>
                                    <td>$pending_tasks</td>
                                    <td>$" . number_format($total_earnings, 2) . "</td>
                                    <td>$" . number_format($savings, 2) . "</td>
                                  </tr>";
                        }
?>
                    </tbody>
                </table>
            </div>

            <div class="charts-container">
                <div class="chart-section">
                    <h2>💰 Earnings Comparison</h2>
                    <canvas id="earningsChart"></canvas>
                </div>
                <div class="chart-section">
                    <h2>🏦 Bank Savings Comparison</h2>
                    <canvas id="savingsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Hide loading overlay when page is fully loaded
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }, 500); // Small delay to ensure smooth transition
    });

    // Prepare data for the earnings chart
    const earningsData = <?php
            $earningsChartData = array();
foreach ($children as $child) {
    $child_id = $child['id'];
    $child_name = $child['first_name'] . ' ' . $child['last_name'];

    $sql_earnings = "SELECT total_earnings FROM user_earnings WHERE user_id = ?";
    $stmt = $conn->prepare($sql_earnings);
    $stmt->bind_param("i", $child_id);
    $stmt->execute();
    $earnings_result = $stmt->get_result()->fetch_assoc();
    $earnings = $earnings_result['total_earnings'] ?? 0;

    $earningsChartData[] = array(
        'name' => $child_name,
        'earnings' => $earnings
    );
}
echo json_encode($earningsChartData);
?>;

    // Prepare data for the savings chart
    const savingsData = <?php
    $savingsChartData = array();
foreach ($children as $child) {
    $child_id = $child['id'];
    $child_name = $child['first_name'] . ' ' . $child['last_name'];

    $sql_savings = "SELECT balance FROM bank_accounts WHERE id = ?";
    $stmt = $conn->prepare($sql_savings);
    $stmt->bind_param("i", $child_id);
    $stmt->execute();
    $savings_result = $stmt->get_result()->fetch_assoc();
    $savings = $savings_result['balance'] ?? 0;

    $savingsChartData[] = array(
        'name' => $child_name,
        'savings' => $savings
    );
}
echo json_encode($savingsChartData);
?>;

    // Create the earnings chart
    const earningsCtx = document.getElementById('earningsChart').getContext('2d');
    new Chart(earningsCtx, {
        type: 'bar',
        data: {
            labels: earningsData.map(child => child.name),
            datasets: [{
                label: 'Total Earnings ($)',
                data: earningsData.map(child => child.earnings),
                backgroundColor: [
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(153, 102, 255, 0.6)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
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

    // Create the savings chart
    const savingsCtx = document.getElementById('savingsChart').getContext('2d');
    new Chart(savingsCtx, {
        type: 'bar',
        data: {
            labels: savingsData.map(child => child.name),
            datasets: [{
                label: 'Bank Savings ($)',
                data: savingsData.map(child => child.savings),
                backgroundColor: [
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(153, 102, 255, 0.6)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
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
    </script>
</body>

</html>