<?php
session_start();
require_once 'db.php'; // Database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch total earnings from user_earning table
$sql = "SELECT total_earnings FROM user_earnings WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $earnings = $result->fetch_assoc();
    $total_earnings = $earnings['total_earnings'];
} else {
    // If no record exists, create one with 0 earnings
    $total_earnings = 0;
    $sql = "INSERT INTO user_earnings (user_id, total_earnings) VALUES (?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Process purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['itemPrice'])) {
    $itemPrice = floatval($_POST['itemPrice']);

    // Check if the user has enough money
    if ($total_earnings >= $itemPrice) {
        // Deduct the price from total earnings
        $new_total_earnings = $total_earnings - $itemPrice;

        // Update earnings in the database
        $update_sql = "UPDATE user_earnings SET total_earnings = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("di", $new_total_earnings, $user_id);

        if ($update_stmt->execute()) {
            $total_earnings = $new_total_earnings; // Update the displayed earnings
            $message = "Item purchased successfully!";
        } else {
            $message = "Error updating earnings.";
        }
    } else {
        $message = "Insufficient funds!";
    }
}

// Fetch items from the database
$item_sql = "SELECT * FROM store_items";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->execute();
$item_result = $item_stmt->get_result();
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
                <h2>Your Current Earnings: $<?= number_format($total_earnings, 2); ?></h2>
            </div>

            <!-- Store Items -->
            <div class="store-items">
                <?php while ($item = $item_result->fetch_assoc()) : ?>
                <form method="POST" onsubmit="confirmPurchase(event)">
                    <div class="item-card">
                        <img src="<?= $item['item_image']; ?>" alt="<?= $item['item_name']; ?>">
                        <h3><?= htmlspecialchars($item['item_name']); ?></h3>
                        <p>$<?= number_format($item['item_price'], 2); ?></p>
                        <button type="submit" name="itemPrice" value="<?= $item['item_price']; ?>">Buy</button>
                    </div>
                </form>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>