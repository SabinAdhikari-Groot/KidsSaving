<?php
session_start();
include 'db.php';

// Dummy user data (replace with session data)
$_SESSION['user_id'] = 1; // Parent ID
$parent_id = $_SESSION['user_id'];
$child_id = 3; // Child ID

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        // Handle sending messages
        if ($_POST['action'] == 'send' && isset($_POST['message'])) {
            $message = trim($_POST['message']);
            if (!empty($message)) {
                $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $parent_id, $child_id, $message);
                $stmt->execute();
                echo json_encode(['status' => 'success']);
                exit;
            }
        }
        // Handle fetching messages
        elseif ($_POST['action'] == 'fetch') {
            $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp ASC");
            $stmt->bind_param("iiii", $parent_id, $child_id, $child_id, $parent_id);
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
    <link rel="stylesheet" href="parent_chat.css">
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
        <h2>👨‍👩‍👧‍👦 Parent Dashboard</h2>
        <ul>
            <li><a href="parent_dashboard.php">🏠 Home</a></li>
            <li><a href="parent_managing_tasks.php">📝 Manage Tasks</a></li>
            <li><a href="parent_approving_tasks.php">✅ Approve Tasks</a></li>
            <li><a href="parent_tracking_earnings.php">💰 Track Earnings</a></li>
            <li><a href="parent_chat.php">💬 Chat</a></li>
            <li><a href="parent_account.php">⚙️ Account</a></li>
            <li><a href="parent_help.php">❓ Help</a></li>
            <li><a href="logout.php">🚪 Log out</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="container">
            <h1>💬 Chat</h1>
            <p>Communicate with your child and stay up-to-date with their progress!</p>

            <div class="chat-box" id="chatBox">
                <div class="typing-indicator" id="typingIndicator">Child is typing...</div>
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
            setInterval(loadMessages, 20000);
            
            // Handle form submission with AJAX
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
                .catch(error => console.error('Error:', error))
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
                    body: 'action=fetch'
                })
                .then(response => response.json())
                .then(messages => {
                    chatBox.innerHTML = '<div class="typing-indicator" id="typingIndicator">Child is typing...</div>';
                    
                    messages.forEach(msg => {
                        const messageClass = msg.sender_id == <?php echo $parent_id; ?> ? 'sent' : 'received';
                        const senderName = msg.sender_id == <?php echo $parent_id; ?> ? 'You' : 'Child';
                        
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${messageClass}`;
                        messageDiv.innerHTML = `
                            <p><strong>${senderName}:</strong> ${msg.message}</p>
                            <span class="message-time">${msg.timestamp}</span>
                        `;
                        
                        chatBox.appendChild(messageDiv);
                    });
                    
                    chatBox.scrollTop = chatBox.scrollHeight;
                })
                .catch(error => console.error('Error loading messages:', error));
            }
        });
    </script>
</body>
</html>