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
            $query = "SELECT * FROM chat_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)";
            $params = [$child_id, $parent_id, $parent_id, $child_id];
            $types = "iiii";

            // If last_timestamp is provided, only fetch newer messages
            if (isset($_POST['last_timestamp'])) {
                $query .= " AND timestamp > ?";
                $params[] = $_POST['last_timestamp'];
                $types .= "s";
            }

            $query .= " ORDER BY timestamp ASC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
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
            <h1>💬 Chat</h1>
            <p>Communicate with your parents and stay up-to-date with reminders, goals, and more!</p>

            <div class="chat-box" id="chatBox">
                <div class="typing-indicator" id="typingIndicator">Parent is typing...</div>
            </div>

            <form id="chatForm">
                <div class="message-input">
                    <input type="text" name="message" id="messageInput" placeholder="Type your message here..."
                        required>
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
        let lastMessageTimestamp = null;
        let isNearBottom = true;
        let isLoading = false;

        // Function to check if user is near bottom of chat
        function isUserNearBottom() {
            const threshold = 100; // pixels from bottom
            return chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < threshold;
        }

        // Function to load messages
        function loadMessages(initialLoad = false) {
            if (isLoading) return;
            isLoading = true;

            const params = new URLSearchParams();
            params.append('action', 'fetch');
            if (lastMessageTimestamp && !initialLoad) {
                params.append('last_timestamp', lastMessageTimestamp);
            }

            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: params.toString()
                })
                .then(response => response.json())
                .then(messages => {
                    if (messages.length > 0) {
                        const wasNearBottom = isUserNearBottom();

                        // Clear chat box only on initial load
                        if (initialLoad) {
                            chatBox.innerHTML =
                                '<div class="typing-indicator" id="typingIndicator">Parent is typing...</div>';
                        }

                        // Keep track of processed messages to avoid duplicates
                        const processedMessages = new Set();

                        messages.forEach(msg => {
                            // Create a unique key for each message
                            const messageKey = `${msg.sender_id}-${msg.timestamp}-${msg.message}`;

                            // Skip if we've already processed this message
                            if (processedMessages.has(messageKey)) return;
                            processedMessages.add(messageKey);

                            const messageClass = msg.sender_id == <?php echo $child_id; ?> ?
                                'sent' : 'received';
                            const senderName = msg.sender_id == <?php echo $child_id; ?> ? 'You' :
                                'Parent';

                            // Create message group
                            const messageGroup = document.createElement('div');
                            messageGroup.className = `message ${messageClass}`;
                            messageGroup.dataset.messageKey = messageKey;

                            // Add sender header
                            const header = document.createElement('div');
                            header.className = 'header';
                            header.innerHTML = `<strong>${senderName}</strong>`;
                            messageGroup.appendChild(header);

                            // Add message content
                            const messageContent = document.createElement('p');
                            messageContent.textContent = msg.message;
                            messageGroup.appendChild(messageContent);

                            // Add timestamp
                            const timestamp = document.createElement('span');
                            timestamp.className = 'message-time';
                            timestamp.textContent = msg.timestamp;
                            messageGroup.appendChild(timestamp);

                            // Check if this message already exists in the chat
                            const existingMessage = chatBox.querySelector(
                                `[data-message-key="${messageKey}"]`);
                            if (!existingMessage) {
                                chatBox.appendChild(messageGroup);
                            }
                        });

                        // Update last message timestamp
                        lastMessageTimestamp = messages[messages.length - 1].timestamp;

                        // Scroll to bottom only if user was already near bottom
                        if (wasNearBottom || initialLoad) {
                            chatBox.scrollTop = chatBox.scrollHeight;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                })
                .finally(() => {
                    isLoading = false;
                });
        }

        // Initial load of messages
        loadMessages(true);

        // Check for new messages every 2 seconds
        const refreshInterval = setInterval(() => {
            if (isUserNearBottom()) {
                loadMessages();
            }
        }, 2000);

        // Handle scroll events
        chatBox.addEventListener('scroll', () => {
            isNearBottom = isUserNearBottom();
        });

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

        // Clean up interval when leaving the page
        window.addEventListener('beforeunload', function() {
            clearInterval(refreshInterval);
        });
    });
    </script>
</body>

</html>