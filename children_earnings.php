<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];

// Ensure the user has an entry in the earnings table
$init_query = "INSERT INTO user_earnings (user_id, total_earnings) SELECT ?, 0.00 WHERE NOT EXISTS (SELECT 1 FROM user_earnings WHERE user_id = ?)";
$stmt = $conn->prepare($init_query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();

// Fetch total earnings
$total_earnings_query = "SELECT total_earnings FROM user_earnings WHERE user_id = ?";
$stmt = $conn->prepare($total_earnings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_earnings = $result->fetch_assoc()['total_earnings'] ?? 0.00;

// Fetch earnings details (both from tasks and allowances)
$earnings_query = "SELECT source, earned_date, amount FROM earnings WHERE user_id = ? ORDER BY earned_date DESC";
$stmt = $conn->prepare($earnings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$earnings_result = $stmt->get_result();

// Fetch current savings goal
$goal_query = "SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($goal_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$goal_result = $stmt->get_result();
$current_goal = $goal_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving Earnings</title>
    <link rel="stylesheet" href="children_earnings.css">
    <style>
        .progress-container {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 5px;
            margin: 10px 0;
        }
        .progress-bar {
            height: 20px;
            background-color: #4CAF50;
            border-radius: 5px;
            text-align: center;
            line-height: 20px;
            color: white;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>🎮 KidsSaving</h2>
        <ul>
            <li><a href="children_dashboard.php">🏠 Home</a></li>
            <li><a href="children_tasks.php">📝 Tasks</a></li>
            <li><a href="children_earnings.php">💰 Earnings</a></li>
            <li><a href="children_virtual_bank.php">🏦 Virtual Bank</a></li>
            <li><a href="virtual_store.php">🛒 Virtual Store</a></li>
            <li><a href="leaderboard.php">🏆 Leaderboard</a></li>
            <li><a href="children_chat.php">💬 Chat</a></li>
            <li><a href="children_account.php">👤 Account</a></li>
            <li><a href="children_help.php">❓ Help</a></li>
            <li><a href="children_logout.php">🚪 Log out</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="container">
            <h1>💰 Your Earnings: $<?php echo number_format($total_earnings, 2); ?></h1>
            <p>Track your earnings, savings goals, and progress here!</p>

            <!-- Earnings Table -->
            <div class="earnings-table">
                <h2>Your Earnings History</h2>
                <?php if ($earnings_result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $earnings_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['source']) ?></td>
                            <td><?= $row['earned_date'] ?></td>
                            <td>$<?= number_format($row['amount'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No earnings recorded yet.</p>
                <?php endif; ?>
            </div>

            <!-- Savings Goals Section -->
            <div class="earnings-goals">
                <h2>Your Savings Goal</h2>
                <?php if ($current_goal): ?>
                <div class="goal">
                    <h3><?= htmlspecialchars($current_goal['goal_name']) ?></h3>
                    <p>Target: $<?= number_format($current_goal['target_amount'], 2) ?></p>
                    <p>Saved: $<?= number_format($current_goal['amount_saved'], 2) ?></p>
                    <div class="progress-container">
                        <?php 
                        $percentage = ($current_goal['amount_saved'] / $current_goal['target_amount']) * 100;
                        $percentage = min(100, max(0, $percentage)); // Ensure between 0-100
                        ?>
                        <div class="progress-bar" style="width: <?= $percentage ?>%">
                            <?= round($percentage) ?>%
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <p>No active savings goal. Create one in your account settings!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>