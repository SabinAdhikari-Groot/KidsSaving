<?php
session_start();
include 'db.php';

// Fetch user details
$_SESSION['user_id'] = 1; // Dummy user ID (replace with actual session value)
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update Profile
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        
        // Handle profile picture upload
        if (!empty($_FILES['profile_pic']['name'])) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
            move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file);
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, profile_pic = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $target_file, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
        }
        $stmt->execute();
    }
    
    // Change Password
    if (isset($_POST['change_password'])) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $user_id);
        $stmt->execute();
    }
    
    // Connect with Parent
    if (isset($_POST['connect_parent'])) {
        $parent_email = trim($_POST['parent_email']);
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND account_type = 'Parent'");
        $stmt->bind_param("s", $parent_email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $parent_id = $result->fetch_assoc()['id'];
            $stmt = $conn->prepare("INSERT INTO parent_children_connection (parent_id, child_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $parent_id, $user_id);
            $stmt->execute();
        }
    }
}

// Fetch user details again after updates
$stmt = $conn->prepare("SELECT first_name, last_name, email, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$full_name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);

// Fetch connected parent(s)
$stmt = $conn->prepare("SELECT u.first_name, u.last_name, u.email FROM users u INNER JOIN parent_children_connection pcc ON u.id = pcc.parent_id WHERE pcc.child_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$parents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving - Account</title>
    <link rel="stylesheet" href="children_account.css">
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
            <h1>👤 My Account</h1>
            <div class="profile-card">
                <img src="<?php echo $user['profile_pic'] ?: 'default-avatar.png'; ?>" alt="Profile Picture"
                    class="profile-pic">
                <h2>Name: <?php echo $full_name; ?></h2>
                <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <h2>Update Profile</h2>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>"
                    required>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>"
                    required>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                <input type="file" name="profile_pic" accept="image/*">
                <button type="submit" name="update_profile">Update Profile</button>
            </form>

            <form method="POST" action="">
                <h2>Change Password</h2>
                <input type="password" name="new_password" placeholder="New Password" required>
                <button type="submit" name="change_password">Change Password</button>
            </form>

            <form method="POST" action="">
                <h2>Connect with Parent</h2>
                <input type="email" name="parent_email" placeholder="Parent's Email" required>
                <button type="submit" name="connect_parent">Connect</button>
            </form>

            <h2>👨‍👩‍👧‍👦 Connected Parents</h2>
            <ul>
                <?php foreach ($parents as $parent): ?>
                <li><?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']) . " (" . htmlspecialchars($parent['email']) . ")"; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>