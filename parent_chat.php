<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['user_id'];

// Fetch children connected to this parent
$children_query = "SELECT u.id, u.first_name, u.last_name FROM users u
                  JOIN parent_children_connection pc ON pc.child_id = u.id
                  WHERE pc.parent_id = ?";
$stmt = $conn->prepare($children_query);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$children_result = $stmt->get_result();

$children = [];
while ($row = $children_result->fetch_assoc()) {
    $children[$row['id']] = $row['first_name'] . ' ' . $row['last_name'];
}

// Get selected child ID from POST or default to first child
$child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : (count($children) > 0 ? array_key_first($children) : null);

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        // Handle sending messages
        if ($_POST['action'] == 'send' && isset($_POST['message']) && $child_id) {
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
        elseif ($_POST['action'] == 'fetch' && $child_id) {
            $query = "SELECT * FROM chat_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)";
            $params = [$parent_id, $child_id, $child_id, $parent_id];
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
            <li><a href="parent_chat.php" class="active">💬 Chat</a></li>
            <li><a href="parent_account.php">⚙️ Account</a></li>
            <li><a href="parent_help.php">❓ Help</a></li>
            <li><a href="logout.php">🚪 Log out</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="container">
            <?php if (empty($children)): ?>
            <div class="alert alert-info">
                <p>No children connected to your account. Please connect with your child first.</p>
            </div>
            <?php else: ?>
            <div class="chat-header">
                <h1>💬 Chat with your children</h1>
                <p>Communicate with your child and stay up-to-date with their progress!</p>
            </div>

            <div class="chat-container">
                <div class="child-selector">
                    <select id="childSelect" name="child_id">
                        <?php foreach ($children as $id => $name): ?>
                        <option value="<?= $id ?>" <?= $id == $child_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="chat-box" id="chatBox">
                    <div class="typing-indicator" id="typingIndicator">Child is typing...</div>
                </div>

                <form id="chatForm" class="message-form">
                    <div class="message-input">
                        <input type="text" name="message" id="messageInput" placeholder="Message your child..."
                            required>
                        <button type="submit">Send</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> KidsSaving | Teaching Financial Responsibility Through Fun</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatBox = document.getElementById('chatBox');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const typingIndicator = document.getElementById('typingIndicator');
        const childSelect = document.getElementById('childSelect');
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

            const formData = new FormData();
            formData.append('action', 'fetch');
            if (childSelect) {
                formData.append('child_id', childSelect.value);
            }
            if (lastMessageTimestamp && !initialLoad) {
                formData.append('last_timestamp', lastMessageTimestamp);
            }

            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(messages => {
                    if (messages.length > 0) {
                        const wasNearBottom = isUserNearBottom();

                        // Clear chat box only on initial load or child change
                        if (initialLoad) {
                            chatBox.innerHTML =
                                '<div class="typing-indicator" id="typingIndicator">Child is typing...</div>';
                        }

                        // Keep track of processed messages to avoid duplicates
                        const processedMessages = new Set();

                        messages.forEach(msg => {
                            // Create a unique key for each message
                            const messageKey = `${msg.sender_id}-${msg.timestamp}-${msg.message}`;

                            // Skip if we've already processed this message
                            if (processedMessages.has(messageKey)) return;
                            processedMessages.add(messageKey);

                            const messageClass = msg.sender_id == <?php echo $parent_id; ?> ?
                                'sent' : 'received';
                            const senderName = msg.sender_id == <?php echo $parent_id; ?> ? 'You' :
                                childSelect.options[childSelect.selectedIndex].text.replace('💬 ',
                                    '');

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

        // Handle child selection change
        if (childSelect) {
            childSelect.addEventListener('change', function() {
                lastMessageTimestamp = null;
                loadMessages(true);
            });
        }

        // Handle form submission
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (message === '') return;

            // Show "sending" state
            messageInput.disabled = true;
            chatForm.querySelector('button').disabled = true;

            // Send message via AJAX
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('message', message);
            if (childSelect) {
                formData.append('child_id', childSelect.value);
            }

            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
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

        // Clean up interval when leaving the page
        window.addEventListener('beforeunload', function() {
            clearInterval(refreshInterval);
        });
    });
    </script>
</body>

</html>