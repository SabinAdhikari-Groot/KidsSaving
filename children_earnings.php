<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];

// Handle goal creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_goal'])) {
    $goal_name = $_POST['goal_name'];
    $target_amount = $_POST['target_amount'];

    $check_query = "SELECT * FROM savings_goals WHERE user_id = ? AND status = 'active'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $existing_goal = $stmt->get_result();

    if ($existing_goal->num_rows > 0) {
        $_SESSION['error'] = "You already have an active savings goal.";
    } else {
        $insert_query = "INSERT INTO savings_goals (user_id, goal_name, target_amount, bonus_percentage, status, created_at) VALUES (?, ?, ?, 10, 'active', NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isd", $user_id, $goal_name, $target_amount);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Savings goal created successfully!";
        } else {
            $_SESSION['error'] = "Error creating goal. Please try again.";
        }
    }

    header("Location: children_earnings.php");
    exit;
}

// Handle goal deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_goal'])) {
    $goal_id = $_POST['goal_id'];
    $delete_query = "DELETE FROM savings_goals WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $goal_id, $user_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Savings goal deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting goal.";
    }
    header("Location: children_earnings.php");
    exit;
}

// Ensure user earnings row exists
$init_query = "INSERT INTO user_earnings (user_id, total_earnings) SELECT ?, 0.00 WHERE NOT EXISTS (SELECT 1 FROM user_earnings WHERE user_id = ?)";
$stmt = $conn->prepare($init_query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();

// Get total earnings
$total_earnings_query = "SELECT total_earnings FROM user_earnings WHERE user_id = ?";
$stmt = $conn->prepare($total_earnings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_earnings = $stmt->get_result()->fetch_assoc()['total_earnings'] ?? 0.00;

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) as total FROM earnings WHERE user_id = ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_earnings_count = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_earnings_count / $per_page);

// Get earnings
$earnings_query = "SELECT source, earned_date, amount FROM earnings WHERE user_id = ? ORDER BY earned_date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($earnings_query);
$stmt->bind_param("iii", $user_id, $per_page, $offset);
$stmt->execute();
$earnings_result = $stmt->get_result();

// Get current goal
$goal_query = "SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($goal_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_goal = $stmt->get_result()->fetch_assoc();

// Bonus for completed goal
if ($current_goal && $current_goal['status'] === 'completed' && $current_goal['bonus_earned'] == 0) {
    $bonus_amount = $current_goal['target_amount'] * ($current_goal['bonus_percentage'] / 100);
    $conn->begin_transaction();
    try {
        $update_bonus = "UPDATE savings_goals SET bonus_earned = ? WHERE id = ?";
        $stmt = $conn->prepare($update_bonus);
        $stmt->bind_param("di", $bonus_amount, $current_goal['id']);
        $stmt->execute();

        $add_bonus = "INSERT INTO earnings (user_id, source, amount, description) VALUES (?, 'Goal Bonus', ?, ?)";
        $desc = "Bonus for completing goal: " . $current_goal['goal_name'];
        $stmt = $conn->prepare($add_bonus);
        $stmt->bind_param("ids", $user_id, $bonus_amount, $desc);
        $stmt->execute();

        $update_total = "UPDATE user_earnings SET total_earnings = total_earnings + ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_total);
        $stmt->bind_param("di", $bonus_amount, $user_id);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success'] = "🎉 Bonus of $" . number_format($bonus_amount, 2) . " added!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error adding bonus.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>KidsSaving Earnings</title>
    <link rel="stylesheet" href="children_earnings.css">
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
            <h1>💰 Your Earnings: $<?= number_format($total_earnings, 2) ?></h1>
            <?php if (isset($_SESSION['success'])): ?>
            <div class="message success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

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
                            <td><?= date('M d, Y', strtotime($row['earned_date'])) ?></td>
                            <td>$<?= number_format($row['amount'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="pagination-btn <?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <p>No earnings recorded yet.</p>
                <?php endif; ?>
            </div>

            <div class="earnings-goals">
                <h2>Your Savings Goals</h2>
                <div class="create-goal-form">
                    <form method="POST" action="children_earnings.php">
                        <input type="hidden" name="create_goal" value="1">
                        <label for="goal_name">Goal Name:</label>
                        <input type="text" name="goal_name" id="goal_name" required>
                        <label for="target_amount">Target Amount ($):</label>
                        <input type="number" name="target_amount" id="target_amount" min="1" step="0.01" required>
                        <button type="submit" class="create-goal-btn">Create Goal</button>
                    </form>
                </div>

                <div class="current-goals">
                    <?php if ($current_goal): ?>
                    <div class="goal">
                        <form method="POST" action="children_earnings.php"
                            onsubmit="return confirm('Delete this goal?');" style="text-align:right;">
                            <input type="hidden" name="delete_goal" value="1">
                            <input type="hidden" name="goal_id" value="<?= $current_goal['id'] ?>">
                            <button type="submit" class="delete-goal-btn">🗑️ Delete Goal</button>
                        </form>
                        <p class="goal-title"><?= htmlspecialchars($current_goal['goal_name']) ?> 🎯</p>
                        <p>Target: $<?= number_format($current_goal['target_amount'], 2) ?></p>
                        <p>Progress: $<?= number_format($total_earnings, 2) ?> /
                            $<?= number_format($current_goal['target_amount'], 2) ?></p>
                        <progress value="<?= $total_earnings ?>" max="<?= $current_goal['target_amount'] ?>"></progress>
                        <p class="progress-text">
                            <?= number_format(min(($total_earnings / $current_goal['target_amount']) * 100, 100), 1) ?>%
                            Complete</p>

                        <?php if ($current_goal['status'] === 'completed'): ?>
                        <div class="goal-completed">
                            <p class="bonus-text">🎉 Goal Completed!</p>
                            <?php if ($current_goal['bonus_earned'] > 0): ?>
                            <p class="bonus-amount">Bonus Earned:
                                $<?= number_format($current_goal['bonus_earned'], 2) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <p>No active savings goal. Create one to start tracking your progress!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>