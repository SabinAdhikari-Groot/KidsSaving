<?php
session_start();
include 'db.php';

$parent_id = $_SESSION['user_id'];

// Fetch child IDs and names associated with the parent
$children_query = "SELECT u.id, u.first_name, u.last_name FROM users u
                  JOIN parent_children_connection pc ON pc.child_id = u.id
                  WHERE pc.parent_id = ?";
$stmt = $conn->prepare($children_query);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$children_result = $stmt->get_result();

$children = [];
while ($row = $children_result->fetch_assoc()) {
    $children[$row['id']] = $row['first_name'] . ' ' . $row['last_name'];
}

// Fetch earnings summary for each child
$earnings_summary = [];
if (!empty($children)) {
    $child_ids = array_keys($children);
    $placeholders = implode(',', array_fill(0, count($child_ids), '?'));
    
    // Get total earnings for each child
    $summary_query = "SELECT user_id, SUM(amount) as total_earnings FROM earnings 
                     WHERE user_id IN ($placeholders) GROUP BY user_id";
    $stmt = $conn->prepare($summary_query);
    $stmt->bind_param(str_repeat("i", count($child_ids)), ...$child_ids);
    $stmt->execute();
    $summary_result = $stmt->get_result();
    
    while ($row = $summary_result->fetch_assoc()) {
        $earnings_summary[$row['user_id']] = $row['total_earnings'];
    }
}

// Fetch recent earnings
$recent_earnings = [];
if (!empty($children)) {
    $earnings_query = "SELECT e.*, u.first_name, u.last_name FROM earnings e
                      JOIN users u ON e.user_id = u.id
                      WHERE e.user_id IN ($placeholders) 
                      ORDER BY e.earned_date DESC LIMIT 10";
    $stmt = $conn->prepare($earnings_query);
    $stmt->bind_param(str_repeat("i", count($child_ids)), ...$child_ids);
    $stmt->execute();
    $earnings_result = $stmt->get_result();
    $recent_earnings = $earnings_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving - Track Children's Earnings</title>
    <link rel="stylesheet" href="parent_tracking_earnings.css">
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
            <h1>💰 Children's Earnings</h1>
            <p>Track your children's earnings and progress.</p>

            <!-- Earnings Summary -->
            <div class="earnings-summary">
                <h2>Earnings Summary</h2>
                <?php if (!empty($children)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Child</th>
                            <th>Total Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($children as $child_id => $child_name): ?>
                        <tr>
                            <td><?= htmlspecialchars($child_name) ?></td>
                            <td>$<?= number_format($earnings_summary[$child_id] ?? 0, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No children linked to your account.</p>
                <?php endif; ?>
            </div>

            <!-- Recent Earnings -->
            <div class="recent-earnings">
                <h2>Recent Earnings</h2>
                <?php if (!empty($recent_earnings)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Child</th>
                            <th>Source</th>
                            <th>Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_earnings as $earning): ?>
                        <tr>
                            <td><?= htmlspecialchars($earning['first_name'] . ' ' . $earning['last_name']) ?></td>
                            <td><?= htmlspecialchars($earning['source']) ?></td>
                            <td><?= $earning['earned_date'] ?></td>
                            <td>$<?= number_format($earning['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No earnings recorded for your children yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>