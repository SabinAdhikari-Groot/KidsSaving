<?php
session_start();
require_once 'db.php'; // Database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if bank account exists, if not create one
$sql = "SELECT balance FROM bank_accounts WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create new bank account with 0 balance
    $sql = "INSERT INTO bank_accounts (id, balance) VALUES (?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Set initial balance to 0
    $balance = 0;
} else {
    $bank_account = $result->fetch_assoc();
    $balance = $bank_account['balance'];
}

// Fetch total earnings from user_earnings table
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

// Handle deposit action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deposit-amount'])) {
    $deposit_amount = floatval($_POST['deposit-amount']);

    if ($deposit_amount <= 0) {
        echo "<script>alert('Deposit amount must be positive!');</script>";
    } elseif ($deposit_amount > $total_earnings) {
        echo "<script>alert('You do not have sufficient funds in earnings!');</script>";
    } else {
        $new_balance = $balance + $deposit_amount;
        $new_total_earnings = $total_earnings - $deposit_amount;

        // Start transaction
        $conn->begin_transaction();

        try {
            // Update bank account
            $sql = "UPDATE bank_accounts SET balance = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $new_balance, $user_id);
            $stmt->execute();

            // Update earnings
            $sql = "UPDATE user_earnings SET total_earnings = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $new_total_earnings, $user_id);
            $stmt->execute();

            // Record transaction
            $sql = "INSERT INTO transactions (account_id, amount, type) VALUES (?, ?, 'deposit')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("id", $user_id, $deposit_amount);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            // Refresh page to show updated values
            header('Location: children_virtual_bank.php');
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo "<script>alert('An error occurred. Please try again.');</script>";
        }
    }
}

// Handle withdraw action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['withdraw-amount'])) {
    $withdraw_amount = floatval($_POST['withdraw-amount']);

    if ($withdraw_amount <= 0) {
        echo "<script>alert('Withdrawal amount must be positive!');</script>";
    } elseif ($balance < $withdraw_amount) {
        echo "<script>alert('Insufficient balance for withdrawal!');</script>";
    } else {
        $new_balance = $balance - $withdraw_amount;
        $new_total_earnings = $total_earnings + $withdraw_amount;

        // Start transaction
        $conn->begin_transaction();

        try {
            // Update bank account
            $sql = "UPDATE bank_accounts SET balance = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $new_balance, $user_id);
            $stmt->execute();

            // Update earnings
            $sql = "UPDATE user_earnings SET total_earnings = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $new_total_earnings, $user_id);
            $stmt->execute();

            // Record transaction
            $sql = "INSERT INTO transactions (account_id, amount, type) VALUES (?, ?, 'withdraw')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("id", $user_id, $withdraw_amount);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            // Refresh page to show updated values
            header('Location: children_virtual_bank.php');
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo "<script>alert('An error occurred. Please try again.');</script>";
        }
    }
}

// Fetch latest 5 transactions
$sql = "SELECT amount, type, date FROM transactions WHERE account_id = ? ORDER BY date DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions_result = $stmt->get_result();
$transactions = [];
while ($row = $transactions_result->fetch_assoc()) {
    $transactions[] = $row;
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
                        min="0.01" step="0.01" required>
                    <button type="submit" class="transaction-btn">Deposit</button>
                </form>
            </div>

            <div class="transaction-section">
                <h2>Withdraw Money</h2>
                <form action="children_virtual_bank.php" method="POST">
                    <input type="number" id="withdraw-amount" name="withdraw-amount" placeholder="Amount to Withdraw"
                        min="0.01" step="0.01" required>
                    <button type="submit" class="transaction-btn">Withdraw</button>
                </form>
            </div>

            <div class="transaction-history">
                <h2>Recent Transactions</h2>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                            <td class="<?php echo $transaction['type']; ?>">
                                <?php echo ucfirst($transaction['type']); ?>
                            </td>
                            <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="3">No transactions yet</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>