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
        $insert_query = "INSERT INTO savings_goals (user_id, goal_name, target_amount, status, current_earnings, created_at) VALUES (?, ?, ?, 'active', 0.00, NOW())";
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

// Reset total earnings to zero if it's the first time
$reset_query = "UPDATE user_earnings SET total_earnings = 0.00 WHERE user_id = ? AND total_earnings IS NULL";
$stmt = $conn->prepare($reset_query);
$stmt->bind_param("i", $user_id);
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

// Update current_earnings when new earnings are added
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_earning'])) {
    $amount = $_POST['amount'];
    $source = $_POST['source'];

    $conn->begin_transaction();
    try {
        // Add to earnings table
        $earnings_query = "INSERT INTO earnings (user_id, source, amount, earned_date) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($earnings_query);
        $stmt->bind_param("isd", $user_id, $source, $amount);
        $stmt->execute();

        // Update total earnings
        $update_total = "UPDATE user_earnings SET total_earnings = total_earnings + ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_total);
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();

        // Update current goal's earnings if exists
        $update_goal = "UPDATE savings_goals SET current_earnings = current_earnings + ? WHERE user_id = ? AND status = 'active'";
        $stmt = $conn->prepare($update_goal);
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success'] = "Earning added successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error adding earning.";
    }
    header("Location: children_earnings.php");
    exit;
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
            <div class="message success"><?= $_SESSION['success'];
                unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?= $_SESSION['error'];
                unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Add confirmation dialog HTML here -->
            <div id="deleteConfirmationDialog" class="confirmation-dialog">
                <div class="dialog-content">
                    <h3>Confirm Deletion</h3>
                    <p>Are you sure you want to delete this savings goal?</p>
                    <div class="dialog-buttons">
                        <button id="confirmDelete" class="confirm-btn">Yes, Delete</button>
                        <button id="cancelDelete" class="cancel-btn">Cancel</button>
                    </div>
                </div>
            </div>

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

                <cti class="current-goals">
                    <?php if ($current_goal): ?>
                    <div class="goal">
                        <form method="POST" action="children_earnings.php" id="deleteGoalForm"
                            style="text-align:right;">
                            <input type="hidden" name="delete_goal" value="1">
                            <input type="hidden" name="goal_id" value="<?= $current_goal['id'] ?>">
                            <button type="button" class="delete-goal-btn" onclick="showDeleteConfirmation()">🗑️ Delete
                                Goal</button>
                        </form>
                        <p class="goal-title"><?= htmlspecialchars($current_goal['goal_name']) ?> 🎯</p>
                        <div class="goal-details">
                            <p class="target-amount">Target: $<?= number_format($current_goal['target_amount'], 2) ?>
                            </p>
                            <p class="current-amount">Current:
                                $<?= number_format($current_goal['current_earnings'], 2) ?></p>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar"
                                style="width: <?= min(($current_goal['current_earnings'] / $current_goal['target_amount']) * 100, 100) ?>%">
                            </div>
                        </div>
                        <p class="progress-text">
                            <?= number_format(min(($current_goal['current_earnings'] / $current_goal['target_amount']) * 100, 100), 1) ?>%
                            Complete</p>

                        <?php if ($current_goal['status'] === 'completed'): ?>
                        <div class="goal-completed">
                            <p class="bonus-text">🎉 Goal Completed!</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <p>No active savings goal. Create one to start tracking your progress!</p>
                    <?php endif; ?>
                </cti>
            </div>
        </div>
    </div>

    <style>
    .goal {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .goal-title {
        font-size: 1.5em;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 15px;
    }

    .goal-details {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .target-amount,
    .current-amount {
        font-size: 1.1em;
        color: #34495e;
    }

    .current-amount {
        color: #27ae60;
        font-weight: bold;
    }

    .progress-container {
        width: 100%;
        height: 20px;
        background-color: #ecf0f1;
        border-radius: 10px;
        overflow: hidden;
        margin: 10px 0;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #3498db, #2ecc71);
        border-radius: 10px;
        transition: width 0.5s ease-in-out;
    }

    .progress-text {
        text-align: center;
        font-size: 1.1em;
        color: #7f8c8d;
        margin-top: 10px;
    }

    .goal-completed {
        margin-top: 15px;
        text-align: center;
    }

    .bonus-text {
        color: #27ae60;
        font-size: 1.2em;
        font-weight: bold;
    }

    .delete-goal-btn {
        background: #e74c3c;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9em;
        transition: background 0.3s ease;
    }

    .delete-goal-btn:hover {
        background: #c0392b;
    }

    /* Add new styles for confirmation dialog */
    .confirmation-dialog {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .dialog-content {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        text-align: center;
        max-width: 400px;
        width: 90%;
    }

    .dialog-content h3 {
        margin: 0 0 15px 0;
        color: #2c3e50;
    }

    .dialog-content p {
        margin: 0 0 20px 0;
        color: #34495e;
    }

    .dialog-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    .confirm-btn,
    .cancel-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.3s ease;
    }

    .confirm-btn {
        background-color: #e74c3c;
        color: white;
    }

    .confirm-btn:hover {
        background-color: #c0392b;
    }

    .cancel-btn {
        background-color: #95a5a6;
        color: white;
    }

    .cancel-btn:hover {
        background-color: #7f8c8d;
    }
    </style>

    <script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        function showDeleteConfirmation() {
            const dialog = document.getElementById('deleteConfirmationDialog');
            if (dialog) {
                dialog.style.display = 'flex';
            }
        }

        // Make showDeleteConfirmation available globally
        window.showDeleteConfirmation = showDeleteConfirmation;

        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        const dialog = document.getElementById('deleteConfirmationDialog');

        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                document.getElementById('deleteGoalForm').submit();
            });
        }

        if (cancelDeleteBtn) {
            cancelDeleteBtn.addEventListener('click', function() {
                dialog.style.display = 'none';
            });
        }

        if (dialog) {
            dialog.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        }
    });
    </script>
</body>

</html>