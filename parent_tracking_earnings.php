<?php
session_start();
include 'db.php';

$parent_id = 3; // Get parent ID from session

// Fetch child IDs associated with the parent
$children_query = "SELECT child_id FROM parent_children_connection WHERE parent_id = ?";
$stmt = $conn->prepare($children_query);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$children_result = $stmt->get_result();

$child_ids = [];
while ($row = $children_result->fetch_assoc()) {
    $child_ids[] = $row['child_id'];
}

$earnings = [];
if (!empty($child_ids)) {
    // Fetch earnings for children linked to this parent
    $placeholders = implode(',', array_fill(0, count($child_ids), '?'));
    $earnings_query = "SELECT e.source, e.earned_date, e.amount, u.first_name, u.last_name FROM earnings e
                       JOIN users u ON e.user_id = u.id
                       WHERE e.user_id IN ($placeholders) ORDER BY e.earned_date DESC LIMIT 10";
    
    $stmt = $conn->prepare($earnings_query);
    $stmt->bind_param(str_repeat("i", count($child_ids)), ...$child_ids);
    $stmt->execute();
    $earnings_result = $stmt->get_result();
    $earnings = $earnings_result->fetch_all(MYSQLI_ASSOC);
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

            <div class="earnings-table">
                <h2>Earnings Overview</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Child's Name</th>
                            <th>Source of Earning</th>
                            <th>Earned Date</th>
                            <th>Amount Earned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($earnings)) { ?>
                        <?php foreach ($earnings as $earning) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($earning['first_name'] . ' ' . $earning['last_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($earning['source']); ?></td>
                            <td><?php echo $earning['earned_date']; ?></td>
                            <td>$<?php echo number_format($earning['amount'], 2); ?></td>
                        </tr>
                        <?php } ?>
                        <?php } else { ?>
                        <tr>
                            <td colspan="4">No earnings found for your children.</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>