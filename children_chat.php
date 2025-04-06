<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: children_login.php");
    exit();
}

$child_id = $_SESSION['user_id'];

// Get the parent ID this child is connected to
$parent_id = null;
$stmt = $conn->prepare("SELECT parent_id FROM parent_children_connection WHERE child_id = ?");
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $parent_id = $row['parent_id'];
} else {
    // No parent connected - handle this case appropriately
    die("No parent connected to this account.");
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        // Handle sending messages
        if ($_POST['action'] == 'send' && isset($_POST['message'])) {
            $message = trim($_POST['message']);
            if (!empty($message)) {
                $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $child_id, $parent_id, $message);
                $stmt->execute();
                echo json_encode(['status' => 'success']);
                exit;
            }
        }
        // Handle fetching messages
        elseif ($_POST['action'] == 'fetch') {
            $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp ASC");
            $stmt->bind_param("iiii", $child_id, $parent_id, $parent_id, $child_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = [
                    'sender_id' => $row['sender_id'],
                    'message' => htmlspecialchars($row['message']),
                    'timestamp' => date('h:i A', strtotime($row['timestamp']))
                ];
            }
            echo json_encode($messages);
            exit;
        }
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
    <style>
        .message-time {
            font-size: 12px;
            color: #888;
            display: block;
            text-align: right;
            margin-top: 5px;
        }
        
        .typing-indicator {
            color: #999;
            font-style: italic;
            margin: 5px 0;
            display: none;
        }
    </style>
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

            <div class="chat-box" id="chatBox">
                <div class="typing-indicator" id="typingIndicator">Parent is typing...</div>
            </div>

            <form id="chatForm">
                <div class="message-input">
                    <input type="text" name="message" id="messageInput" placeholder="Type your message here..." required>
                    <button type="submit">Send</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chatBox');
            const chatForm = document.getElementById('chatForm');
            const messageInput = document.getElementById('messageInput');
            const typingIndicator = document.getElementById('typingIndicator');
            
            // Load messages when page loads
            loadMessages();
            
            // Auto-refresh messages every 2 seconds
            const refreshInterval = setInterval(loadMessages, 10000);
            
            // Handle form submission
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const message = messageInput.value.trim();
                if (message === '') return;
                
                // Show "sending" state
                messageInput.disabled = true;
                chatForm.querySelector('button').disabled = true;
                
                // Send message via AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=send&message=${encodeURIComponent(message)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        messageInput.value = '';
                        loadMessages();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                })
                .finally(() => {
                    messageInput.disabled = false;
                    chatForm.querySelector('button').disabled = false;
                    messageInput.focus();
                });
            });
            
            // Function to load messages
            function loadMessages() {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=fetch`
                })
                .then(response => response.json())
                .then(messages => {
                    if (messages.length > 0) {
                        chatBox.innerHTML = '<div class="typing-indicator" id="typingIndicator">Parent is typing...</div>';
                        
                        messages.forEach(msg => {
                            const messageClass = msg.sender_id == <?php echo $child_id; ?> ? 'sent' : 'received';
                            const senderName = msg.sender_id == <?php echo $child_id; ?> ? 'You' : 'Parent';
                            
                            const messageDiv = document.createElement('div');
                            messageDiv.className = `message ${messageClass}`;
                            messageDiv.innerHTML = `
                                <p><strong>${senderName}:</strong> ${msg.message}</p>
                                <span class="message-time">${msg.timestamp}</span>
                            `;
                            
                            chatBox.appendChild(messageDiv);
                        });
                        
                        // Auto-scroll to bottom
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
            }
            
            // Clean up interval when leaving the page
            window.addEventListener('beforeunload', function() {
                clearInterval(refreshInterval);
            });
        });
    </script>
</body>

</html>