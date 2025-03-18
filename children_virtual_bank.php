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

// Check if the query returned any result
if ($result->num_rows > 0) {
    $bank_account = $result->fetch_assoc();
    $balance = $bank_account['balance'];
} else {
    // Handle the case where no bank account is found
    echo "<script>alert('No bank account found for this user.');</script>";
    exit();
}

// Handle deposit action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deposit-amount'])) {
    $deposit_amount = $_POST['deposit-amount'];

    // Update balance after deposit
    $new_balance = $balance + $deposit_amount;
    $sql = "UPDATE bank_accounts SET balance = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $new_balance, $user_id);
    $stmt->execute();

    // Record the deposit transaction
    $sql = "INSERT INTO transactions (account_id, transaction_type, amount) 
            VALUES ((SELECT account_id FROM bank_accounts WHERE id = ?), 'deposit', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $deposit_amount);
    $stmt->execute();

    // Refresh the page to show updated balance and activity
    header('Location: children_virtual_bank.php');
    exit();
}

// Handle withdraw action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['withdraw-amount'])) {
    $withdraw_amount = $_POST['withdraw-amount'];

    // Check if the balance is sufficient for withdrawal
    if ($balance >= $withdraw_amount) {
        $new_balance = $balance - $withdraw_amount;
        $sql = "UPDATE bank_accounts SET balance = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $new_balance, $user_id);
        $stmt->execute();

        // Record the withdrawal transaction
        $sql = "INSERT INTO transactions (account_id, transaction_type, amount) 
                VALUES ((SELECT account_id FROM bank_accounts WHERE id = ?), 'withdrawal', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $withdraw_amount);
        $stmt->execute();
        
        // Refresh the page to show updated balance and activity
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

            <!-- Bank Account Details -->
            <div class="bank-account">
                <h2>Bank Balance</h2>
                <p id="bank-balance">$<?php echo number_format($balance, 2); ?></p>
            </div>

            <!-- Deposit Section -->
            <div class="transaction-section">
                <h2>Deposit Money</h2>
                <form action="children_virtual_bank.php" method="POST">
                    <input type="number" id="deposit-amount" name="deposit-amount" placeholder="Amount to Deposit"
                        min="1" required>
                    <button type="submit" class="transaction-btn">Deposit</button>
                </form>
            </div>

            <!-- Withdraw Section -->
            <div class="transaction-section">
                <h2>Withdraw Money</h2>
                <form action="children_virtual_bank.php" method="POST">
                    <input type="number" id="withdraw-amount" name="withdraw-amount" placeholder="Amount to Withdraw"
                        min="1" required>
                    <button type="submit" class="transaction-btn">Withdraw</button>
                </form>
            </div>

            <!-- Bank Activity Log -->
            <div class="bank-activity">
                <h2>Recent Activity</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Transaction</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch the recent transactions
                        $sql = "SELECT transaction_type, amount, transaction_date 
                                FROM transactions 
                                WHERE account_id = (SELECT account_id FROM bank_accounts WHERE id = ?)
                                ORDER BY transaction_date DESC LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . ucfirst($row['transaction_type']) . "</td>";
                                echo "<td>$" . number_format($row['amount'], 2) . "</td>";
                                echo "<td>" . $row['transaction_date'] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>No recent transactions</td></tr>";
                        }
                        ?>
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