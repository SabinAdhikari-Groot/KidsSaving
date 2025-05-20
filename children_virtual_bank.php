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

// Function to calculate interest
function calculateInterest($amount, $days, $rate = 0.015)
{
    return $amount * $rate * $days;
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

// Handle fixed deposit action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fixed-deposit-amount'])) {
    $deposit_amount = floatval($_POST['fixed-deposit-amount']);
    $days = intval($_POST['fixed-deposit-days']);

    if ($deposit_amount <= 0 || $days <= 0) {
        echo "<script>alert('Amount and days must be positive!');</script>";
    } elseif ($deposit_amount > $balance) {
        echo "<script>alert('Insufficient balance for fixed deposit!');</script>";
    } else {
        $new_balance = $balance - $deposit_amount;
        $maturity_date = date('Y-m-d', strtotime("+$days days"));

        // Start transaction
        $conn->begin_transaction();

        try {
            // Update bank account
            $sql = "UPDATE bank_accounts SET balance = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $new_balance, $user_id);
            $stmt->execute();

            // Create fixed deposit record
            $sql = "INSERT INTO fixed_deposits (user_id, amount, start_date, maturity_date, interest_rate) 
                    VALUES (?, ?, CURDATE(), ?, 0.015)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ids", $user_id, $deposit_amount, $maturity_date);
            $stmt->execute();

            // Record transaction
            $sql = "INSERT INTO transactions (account_id, amount, type) VALUES (?, ?, 'fixed_deposit')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("id", $user_id, $deposit_amount);
            $stmt->execute();

            $conn->commit();
            header('Location: children_virtual_bank.php');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('An error occurred. Please try again.');</script>";
        }
    }
}

// Check for matured fixed deposits and add interest
$sql = "SELECT * FROM fixed_deposits WHERE user_id = ? AND maturity_date <= CURDATE() AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($fd = $result->fetch_assoc()) {
    $days = (strtotime($fd['maturity_date']) - strtotime($fd['start_date'])) / (60 * 60 * 24);
    $interest = calculateInterest($fd['amount'], $days);
    $total_amount = $fd['amount'] + $interest;

    $conn->begin_transaction();
    try {
        // Update bank balance
        $sql = "UPDATE bank_accounts SET balance = balance + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $total_amount, $user_id);
        $stmt->execute();

        // Record interest transaction
        $sql = "INSERT INTO transactions (account_id, amount, type) VALUES (?, ?, 'interest_received')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("id", $user_id, $interest);
        $stmt->execute();

        // Update fixed deposit status
        $sql = "UPDATE fixed_deposits SET status = 'completed' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $fd['id']);
        $stmt->execute();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
    }
}

// Fetch active fixed deposits
$sql = "SELECT * FROM fixed_deposits WHERE user_id = ? AND status = 'active' ORDER BY maturity_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$fixed_deposits_result = $stmt->get_result();
$fixed_deposits = [];
while ($row = $fixed_deposits_result->fetch_assoc()) {
    $fixed_deposits[] = $row;
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
    <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 500px;
        border-radius: 10px;
        text-align: center;
    }

    .modal-buttons {
        margin-top: 20px;
    }

    .modal-buttons button {
        margin: 0 10px;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .confirm-btn {
        background-color: #4CAF50;
        color: white;
    }

    .cancel-btn {
        background-color: #f44336;
        color: white;
    }
    </style>
</head>

<body>
    <!-- Confirmation Modals -->
    <div id="depositModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Deposit</h2>
            <p>Are you sure you want to deposit $<span id="depositAmount"></span>?</p>
            <div class="modal-buttons">
                <button class="confirm-btn" onclick="confirmDeposit()">Confirm</button>
                <button class="cancel-btn" onclick="closeModal('depositModal')">Cancel</button>
            </div>
        </div>
    </div>

    <div id="withdrawModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Withdrawal</h2>
            <p>Are you sure you want to withdraw $<span id="withdrawAmount"></span>?</p>
            <div class="modal-buttons">
                <button class="confirm-btn" onclick="confirmWithdraw()">Confirm</button>
                <button class="cancel-btn" onclick="closeModal('withdrawModal')">Cancel</button>
            </div>
        </div>
    </div>

    <div id="fixedDepositModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Fixed Deposit</h2>
            <p>Are you sure you want to create a fixed deposit of $<span id="fixedDepositAmount"></span> for <span
                    id="fixedDepositDays"></span> days?</p>
            <p>Interest Rate: 1.5% per day</p>
            <p>Estimated Interest: $<span id="estimatedInterest"></span></p>
            <div class="modal-buttons">
                <button class="confirm-btn" onclick="confirmFixedDeposit()">Confirm</button>
                <button class="cancel-btn" onclick="closeModal('fixedDepositModal')">Cancel</button>
            </div>
        </div>
    </div>

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
                <form id="depositForm" action="children_virtual_bank.php" method="POST">
                    <input type="number" id="deposit-amount" name="deposit-amount" placeholder="Amount to Deposit"
                        min="0.01" step="0.01" required>
                    <button type="button" class="transaction-btn" onclick="showDepositModal()">Deposit</button>
                </form>
            </div>

            <div class="transaction-section">
                <h2>Withdraw Money</h2>
                <form id="withdrawForm" action="children_virtual_bank.php" method="POST">
                    <input type="number" id="withdraw-amount" name="withdraw-amount" placeholder="Amount to Withdraw"
                        min="0.01" step="0.01" required>
                    <button type="button" class="transaction-btn" onclick="showWithdrawModal()">Withdraw</button>
                </form>
            </div>

            <div class="transaction-section">
                <h2>Fixed Deposit</h2>
                <form id="fixedDepositForm" action="children_virtual_bank.php" method="POST">
                    <input type="number" id="fixed-deposit-amount" name="fixed-deposit-amount"
                        placeholder="Amount for Fixed Deposit" min="0.01" step="0.01" required>
                    <input type="number" id="fixed-deposit-days" name="fixed-deposit-days" placeholder="Number of Days"
                        min="1" required>
                    <button type="button" class="transaction-btn" onclick="showFixedDepositModal()">Create Fixed
                        Deposit</button>
                </form>
            </div>

            <?php if (!empty($fixed_deposits)): ?>
            <div class="fixed-deposits">
                <h2>Active Fixed Deposits</h2>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Start Date</th>
                            <th>Maturity Date</th>
                            <th>Interest Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fixed_deposits as $fd): ?>
                        <tr>
                            <td>$<?php echo number_format($fd['amount'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($fd['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($fd['maturity_date'])); ?></td>
                            <td><?php echo($fd['interest_rate'] * 100); ?>% per day</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

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

    <script>
    function showModal(modalId) {
        document.getElementById(modalId).style.display = "block";
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }

    function showDepositModal() {
        const amount = document.getElementById('deposit-amount').value;
        if (!amount || amount <= 0) {
            alert('Please enter a valid amount');
            return;
        }
        document.getElementById('depositAmount').textContent = amount;
        showModal('depositModal');
    }

    function showWithdrawModal() {
        const amount = document.getElementById('withdraw-amount').value;
        if (!amount || amount <= 0) {
            alert('Please enter a valid amount');
            return;
        }
        document.getElementById('withdrawAmount').textContent = amount;
        showModal('withdrawModal');
    }

    function showFixedDepositModal() {
        const amount = document.getElementById('fixed-deposit-amount').value;
        const days = document.getElementById('fixed-deposit-days').value;
        if (!amount || amount <= 0 || !days || days <= 0) {
            alert('Please enter valid amount and days');
            return;
        }
        document.getElementById('fixedDepositAmount').textContent = amount;
        document.getElementById('fixedDepositDays').textContent = days;
        const interest = (amount * 0.015 * days).toFixed(2);
        document.getElementById('estimatedInterest').textContent = interest;
        showModal('fixedDepositModal');
    }

    function confirmDeposit() {
        document.getElementById('depositForm').submit();
    }

    function confirmWithdraw() {
        document.getElementById('withdrawForm').submit();
    }

    function confirmFixedDeposit() {
        document.getElementById('fixedDepositForm').submit();
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = "none";
        }
    }
    </script>
</body>

</html>