<?php
session_start(); // Start the session to access session variables

$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password
$dbname = "Kids_saving";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the user's email from the session (ensure the user is logged in)
    if (isset($_SESSION['user_email'])) {
        $userEmail = $_SESSION['user_email'];
    } else {
        // Handle case where the user is not logged in
        echo "<script>alert('You must be logged in to submit a query.');</script>";
        exit;
    }

    $message = $_POST['message'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO user_query (user_email, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $userEmail, $message);

    // Execute the query
    if ($stmt->execute()) {
        echo "<script>alert('Message sent successfully!');</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving - Help</title>
    <link rel="stylesheet" href="parent_help.css">
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
        <div class="contact-form">
            <h2>📩 Contact Admin</h2>
            <p>Describe your issue below, and we will get back to you as soon as possible.</p>
            <form id="helpForm" method="POST" action="parent_help.php">
                <label for="message">Your Message:</label>
                <textarea id="message" name="message" required placeholder="Describe your issue"></textarea>

                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>