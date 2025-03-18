<?php
session_start();
require_once 'db.php'; // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];

// Fetch current earnings from the database
$sql = "SELECT current_earning FROM children_earnings WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$earning = $result->fetch_assoc();
$current_earning = $earning['current_earning'] ?? 0.00;

// Process purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['itemPrice'])) {
    $itemPrice = floatval($_POST['itemPrice']);

    // Check if the user has enough money
    if ($current_earning >= $itemPrice) {
        // Deduct the price from current earnings
        $new_earning = $current_earning - $itemPrice;

        // Update earnings in the database
        $update_sql = "UPDATE children_earnings SET current_earning = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("di", $new_earning, $user_id);

        if ($update_stmt->execute()) {
            $current_earning = $new_earning; // Update the displayed earnings
            $message = "Item purchased successfully!";
        } else {
            $message = "Error updating earnings.";
        }
    } else {
        $message = "Insufficient funds!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving Virtual Store</title>
    <link rel="stylesheet" href="virtual_store.css">
    <script>
    function confirmPurchase(event) {
        if (!confirm("Are you sure you want to buy this item?")) {
            event.preventDefault(); // Stop form submission if canceled
        }
    }
    </script>
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
            <h1>🛍️ Welcome to the Virtual Store!</h1>
            <p>Spend your hard-earned money on fun items!</p>

            <!-- Display Success or Error Message -->
            <?php if (isset($message)) : ?>
            <p style="color: <?= $message === "Item purchased successfully!" ? 'green' : 'red' ?>;">
                <?= $message; ?>
            </p>
            <?php endif; ?>

            <!-- Current Earnings -->
            <div class="earnings-info">
                <h2>Your Current Earnings: $<?= number_format($current_earning, 2); ?></h2>
            </div>

            <!-- Store Items -->
            <div class="store-items">
                <form method="POST" onsubmit="confirmPurchase(event)">
                    <div class="item-card">
                        <img src="item1.jpg" alt="Item 1">
                        <h3>Item 1</h3>
                        <p>$10.00</p>
                        <button type="submit" name="itemPrice" value="10.00">Buy</button>
                    </div>
                </form>

                <form method="POST" onsubmit="confirmPurchase(event)">
                    <div class="item-card">
                        <img src="item2.jpg" alt="Item 2">
                        <h3>Item 2</h3>
                        <p>$15.00</p>
                        <button type="submit" name="itemPrice" value="15.00">Buy</button>
                    </div>
                </form>

                <form method="POST" onsubmit="confirmPurchase(event)">
                    <div class="item-card">
                        <img src="item3.jpg" alt="Item 3">
                        <h3>Item 3</h3>
                        <p>$20.00</p>
                        <button type="submit" name="itemPrice" value="20.00">Buy</button>
                    </div>
                </form>

                <form method="POST" onsubmit="confirmPurchase(event)">
                    <div class="item-card">
                        <img src="item4.jpg" alt="Item 4">
                        <h3>Item 4</h3>
                        <p>$25.00</p>
                        <button type="submit" name="itemPrice" value="25.00">Buy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>