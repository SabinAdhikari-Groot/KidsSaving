<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch earnings
$earnings_query = "SELECT source, earned_date, amount FROM earnings WHERE user_id = ? ORDER BY earned_date DESC LIMIT 5";
$stmt = $conn->prepare($earnings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$earnings_result = $stmt->get_result();

// Fetch current savings goal (only one active goal)
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
</head>

<body>
    <aside class="sidebar">
        <h2>🎮 KidsSaving</h2>
        <ul>
            <li><a href="children_dashboard.php">🏠 Home</a></li>
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
            <h1>💰 Your Earnings & Savings</h1>
            <p>Track your earnings, savings goals, and progress here!</p>

            <!-- Earnings Table -->
            <div class="earnings-table">
                <h2>Your Earnings</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Source of Earning</th>
                            <th>Earned Date</th>
                            <th>Earned Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $earnings_result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['source']; ?></td>
                            <td><?php echo $row['earned_date']; ?></td>
                            <td>$<?php echo number_format($row['amount'], 2); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Savings Goals Section -->
            <div class="earnings-goals">
                <h2>Your Savings Goal</h2>
                <div id="goal-container">
                    <?php if ($current_goal) { ?>
                    <div class="goal">
                        <p>Goal: <?php echo htmlspecialchars($current_goal['goal_name']); ?> 🏆</p>
                        <p>Target Amount: $<?php echo number_format($current_goal['target_amount'], 2); ?></p>
                        <p>Amount Saved: $<?php echo number_format($current_goal['amount_saved'], 2); ?></p>
                        <progress value="<?php echo $current_goal['amount_saved']; ?>"
                            max="<?php echo $current_goal['target_amount']; ?>"></progress>
                    </div>
                    <?php } else { ?>
                    <p>No active savings goal. Create one below!</p>
                    <button class="create-goal-btn" id="show-goal-form">Create New Goal</button>
                    <?php } ?>
                </div>

                <!-- Goal Creation Form (Initially Hidden) -->
                <div id="goal-form-container" style="display: none;">
                    <h3>Create a Savings Goal</h3>
                    <form id="goal-form">
                        <label for="goal_name">Goal Name:</label>
                        <input type="text" id="goal_name" name="goal_name" required>

                        <label for="target_amount">Target Amount ($):</label>
                        <input type="number" id="target_amount" name="target_amount" required step="0.01">

                        <button type="submit">Save Goal</button>
                        <button type="button" id="cancel-goal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>

    <script>
    $(document).ready(function() {
        // Show goal creation form
        $("#show-goal-form").click(function() {
            $("#goal-form-container").show();
            $("#show-goal-form").hide();
        });

        // Hide goal creation form
        $("#cancel-goal").click(function() {
            $("#goal-form-container").hide();
            $("#show-goal-form").show();
        });

        // Submit goal form using AJAX
        $("#goal-form").submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: "save_goal.php",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    $("#goal-container").html(response);
                    $("#goal-form-container").hide();
                }
            });
        });
    });
    </script>

</body>

</html>