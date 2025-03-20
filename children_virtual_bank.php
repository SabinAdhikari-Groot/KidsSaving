<?php
session_start();
require_once 'db.php'; // Database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the current bank balance
$sql = "SELECT balance FROM bank_accounts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $bank_account = $result->fetch_assoc();
    $balance = $bank_account['balance'];
} else {
    echo "<script>alert('No bank account found for this user.');</script>";
    exit();
}

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
    $sql = "INSERT INTO user_earning (user_id, total_earnings) VALUES (?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Handle deposit action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deposit-amount'])) {
    $deposit_amount = $_POST['deposit-amount'];

    if ($deposit_amount > $total_earnings) {
        echo "<script>alert('You do not have sufficient funds in earnings!');</script>";
    } else {
        $new_balance = $balance + $deposit_amount;
        $new_total_earnings = $total_earnings - $deposit_amount;

        $sql = "UPDATE bank_accounts SET balance = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $new_balance, $user_id);
        $stmt->execute();

        $sql = "UPDATE user_earnings SET total_earnings = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $new_total_earnings, $user_id);
        $stmt->execute();

        header('Location: children_virtual_bank.php');
        exit();
    }
}

// Handle withdraw action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['withdraw-amount'])) {
    $withdraw_amount = $_POST['withdraw-amount'];

    if ($balance >= $withdraw_amount) {
        $new_balance = $balance - $withdraw_amount;
        $new_total_earnings = $total_earnings + $withdraw_amount;

        $sql = "UPDATE bank_accounts SET balance = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $new_balance, $user_id);
        $stmt->execute();

        $sql = "UPDATE user_earnings SET total_earnings = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $new_total_earnings, $user_id);
        $stmt->execute();

        header('Location: children_virtual_bank.php');
        exit();
    } else {
        echo "<script>alert('Insufficient balance for withdrawal!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving Virtual Bank</title>
    <link rel="stylesheet" href="children_virtual_bank.css">
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
            <h1>🏦 Your Virtual Bank</h1>
            <p>Manage your money by depositing and withdrawing funds!</p>

            <div class="bank-account">
                <h2>Bank Balance: $<?php echo number_format($balance, 2); ?> | Earnings:
                    $<?php echo number_format($total_earnings, 2); ?></h2>
            </div>

            <div class="transaction-section">
                <h2>Deposit Money</h2>
                <form action="children_virtual_bank.php" method="POST">
                    <input type="number" id="deposit-amount" name="deposit-amount" placeholder="Amount to Deposit"
                        min="1" required>
                    <button type="submit" class="transaction-btn">Deposit</button>
                </form>
            </div>

            <div class="transaction-section">
                <h2>Withdraw Money</h2>
                <form action="children_virtual_bank.php" method="POST">
                    <input type="number" id="withdraw-amount" name="withdraw-amount" placeholder="Amount to Withdraw"
                        min="1" required>
                    <button type="submit" class="transaction-btn">Withdraw</button>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>