<?php
session_start();
include 'db.php';

// Dummy user data (replace with session data)
$_SESSION['user_id'] = 3; // Assume child ID is 2
$child_id = $_SESSION['user_id'];
$parent_id = 1; // Assume parent ID is 1

// Handle sending messages
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $child_id, $parent_id, $message);
        $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving Chat</title>
    <link rel="stylesheet" href="children_chat.css">
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
            <h1>💬 Chat</h1>
            <p>Communicate with your parents and stay up-to-date with reminders, goals, and more!</p>

            <div class="chat-box">
                <?php
                $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp ASC");
                $stmt->bind_param("iiii", $child_id, $parent_id, $parent_id, $child_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()): ?>
                <div class="message <?php echo ($row['sender_id'] == $child_id) ? 'sent' : 'received'; ?>">
                    <p><strong><?php echo ($row['sender_id'] == $child_id) ? 'You' : 'Parent'; ?>:</strong>
                        <?php echo htmlspecialchars($row['message']); ?></p>
                </div>
                <?php endwhile; ?>
            </div>

            <form method="POST" action="">
                <div class="message-input">
                    <input type="text" name="message" placeholder="Type your message here..." required>
                    <button type="submit">Send</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>