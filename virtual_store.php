<?php
session_start();
require_once 'db.php'; // Database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['itemPrice']) && isset($_POST['itemId'])) {
        $itemPrice = floatval($_POST['itemPrice']);
        $itemId = intval($_POST['itemId']);

        if ($total_earnings >= $itemPrice) {
            // Deduct the price from total earnings
            $new_total_earnings = $total_earnings - $itemPrice;

            // Start transaction
            $conn->begin_transaction();

            try {
                // Update earnings
                $update_sql = "UPDATE user_earnings SET total_earnings = ? WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("di", $new_total_earnings, $user_id);
                $update_stmt->execute();

                // Add item to inventory
                $insert_sql = "INSERT INTO user_inventory (user_id, item_id, purchase_price) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iid", $user_id, $itemId, $itemPrice);
                $insert_stmt->execute();

                $conn->commit();
                $total_earnings = $new_total_earnings;
                $message = "Item purchased successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error processing purchase.";
            }
        } else {
            $message = "Insufficient funds!";
        }
    } elseif (isset($_POST['sell_item'])) {
        $inventory_id = intval($_POST['inventory_id']);
        $sell_price = floatval($_POST['sell_price']);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Add half price to earnings
            $new_total_earnings = $total_earnings + $sell_price;
            
            // Update earnings
            $update_sql = "UPDATE user_earnings SET total_earnings = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("di", $new_total_earnings, $user_id);
            $update_stmt->execute();
            
            // Remove from inventory
            $delete_sql = "DELETE FROM user_inventory WHERE id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $inventory_id, $user_id);
            $delete_stmt->execute();
            
            $conn->commit();
            $total_earnings = $new_total_earnings;
            $message = "Item sold successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error selling item.";
        }
    }
}

// Fetch items from the database
$item_sql = "SELECT * FROM store_items";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->execute();
$item_result = $item_stmt->get_result();

// Fetch user's inventory
$inventory_sql = "SELECT ui.*, si.item_name, si.item_image, si.item_price 
                 FROM user_inventory ui 
                 JOIN store_items si ON ui.item_id = si.id 
                 WHERE ui.user_id = ?";
$inventory_stmt = $conn->prepare($inventory_sql);
$inventory_stmt->bind_param("i", $user_id);
$inventory_stmt->execute();
$inventory_items = $inventory_stmt->get_result();
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
            event.preventDefault();
        }
    }

    function confirmSale(event) {
        if (!confirm("Are you sure you want to sell this item? You'll receive half of the original price.")) {
            event.preventDefault();
        }
    }
    </script>
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
            <h1>🛍️ Welcome to the Virtual Store!</h1>
            <p class="welcome-text">Spend your hard-earned money on fun items!</p>

            <!-- Display Success or Error Message -->
            <?php if ($message): ?>
            <p class="message <?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message); ?>
            </p>
            <?php endif; ?>

            <!-- Current Earnings -->
            <div class="earnings-info">
                <h2>Your Current Earnings: $<?= number_format($total_earnings, 2); ?></h2>
            </div>

            <!-- My Inventory Section -->
            <div class="inventory-section">
                <h2><img src="images/backpack-icon.svg" alt="Backpack">My Items</h2>
                <div class="inventory-items">
                    <?php if ($inventory_items->num_rows > 0): ?>
                    <?php while ($item = $inventory_items->fetch_assoc()): ?>
                    <div class="item-card inventory-card">
                        <img src="<?= htmlspecialchars($item['item_image']); ?>"
                            alt="<?= htmlspecialchars($item['item_name']); ?>">
                        <h3><?= htmlspecialchars($item['item_name']); ?></h3>
                        <p>Purchased for: $<?= number_format($item['purchase_price'], 2); ?></p>
                        <p class="sell-price">Sell for: $<?= number_format($item['purchase_price'] / 2, 2); ?></p>
                        <form method="POST" onsubmit="confirmSale(event)">
                            <input type="hidden" name="inventory_id" value="<?= $item['id']; ?>">
                            <input type="hidden" name="sell_price" value="<?= $item['purchase_price'] / 2; ?>">
                            <button type="submit" name="sell_item" class="sell-btn">Sell Item</button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <div class="empty-inventory">
                        <p>You haven't purchased any items yet!</p>
                        <p>Browse the store below to find something you like.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Store Items -->
            <div class="store-section">
                <h2><img src="images/store-icon.svg" alt="Store">Available Items</h2>
                <div class="store-items">
                    <?php while ($item = $item_result->fetch_assoc()) : ?>
                    <form method="POST" onsubmit="confirmPurchase(event)">
                        <div class="item-card">
                            <img src="<?= htmlspecialchars($item['item_image']); ?>"
                                alt="<?= htmlspecialchars($item['item_name']); ?>">
                            <h3><?= htmlspecialchars($item['item_name']); ?></h3>
                            <p>$<?= number_format($item['item_price'], 2); ?></p>
                            <input type="hidden" name="itemId" value="<?= $item['id']; ?>">
                            <button type="submit" name="itemPrice" value="<?= $item['item_price']; ?>">Buy</button>
                        </div>
                    </form>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>