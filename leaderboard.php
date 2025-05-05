<?php
session_start();
require_once 'db.php'; // Include database connection

// Check if the user is logged in and redirect if not
if (!isset($_SESSION['user_id'])) {
    header("Location: children_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch leaderboard data sorted by total earnings
$sql = "SELECT u.id as user_id,
               CONCAT(u.first_name, ' ', u.last_name) AS child_name,
               SUM(e.amount) as total_earnings
        FROM users u
        LEFT JOIN earnings e ON u.id = e.user_id
        GROUP BY u.id, u.first_name, u.last_name
        ORDER BY total_earnings DESC
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$leaderboard = [];
$user_rank = null;
$rank = 1;

// Store data in an array
while ($row = $result->fetch_assoc()) {
    $leaderboard[] = [
        'rank' => $rank,
        'child_name' => $row['child_name'],
        'saved_amount' => $row['total_earnings'] ?? 0,
        'user_id' => $row['user_id']
    ];

    // Find the logged-in user's rank
    if ($row['user_id'] == $user_id) {
        $user_rank = [
            'rank' => $rank,
            'saved_amount' => $row['total_earnings'] ?? 0
        ];
    }
    $rank++;
}

// Close statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving Leaderboard</title>
    <link rel="stylesheet" href="leaderboard.css">
</head>

<body>
    <aside class="sidebar">
        <h2>🎮 KidsSaving</h2>
        <ul>
            <li><a href="children_dashboard.php">🏠 Home</a></li>
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
            <h1>🏆 KidsSaving Leaderboard</h1>
            <p>See how you rank against other kids based on your total savings!</p>

            <!-- Leaderboard Table -->
            <div class="leaderboard-table">
                <h2>Top Savers</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Child Name</th>
                            <th>Total Savings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $row): ?>
                        <tr <?= ($row['user_id'] == $user_id) ? 'class="highlight-user"' : '' ?>>
                            <td><?= htmlspecialchars($row['rank']) ?></td>
                            <td><?= htmlspecialchars($row['child_name']) ?></td>
                            <td>$<?= htmlspecialchars(number_format($row['saved_amount'], 2)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Your Rank Section -->
            <div class="your-rank">
                <h2>Your Current Rank</h2>
                <?php if ($user_rank): ?>
                <p>Rank: <?= htmlspecialchars($user_rank['rank']) ?></p>
                <p>Total Savings: $<?= htmlspecialchars(number_format($user_rank['saved_amount'], 2)) ?></p>
                <?php else: ?>
                <p>You are not ranked yet. Start saving to appear on the leaderboard!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>