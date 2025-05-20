<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: children_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update Profile
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        
        // Validate input
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error_message = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } else {
            // Handle profile picture upload
            if (!empty($_FILES['profile_pic']['name'])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['profile_pic']['type'], $allowed_types)) {
                    $error_message = "Only JPG, PNG and GIF files are allowed.";
                } elseif ($_FILES['profile_pic']['size'] > $max_size) {
                    $error_message = "File size must be less than 5MB.";
                } else {
                    $target_dir = "uploads/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $file_extension = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, profile_pic = ? WHERE id = ?");
                        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $target_file, $user_id);
                        if ($stmt->execute()) {
                            $success_message = "Profile updated successfully!";
                        } else {
                            $error_message = "Error updating profile. Please try again.";
                        }
                    } else {
                        $error_message = "Error uploading profile picture. Please try again.";
                    }
                }
            } else {
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
                $stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
                if ($stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                } else {
                    $error_message = "Error updating profile. Please try again.";
                }
            }
        }
    }
    
    // Change Password
    if (isset($_POST['change_password'])) {
        $new_password = trim($_POST['new_password']);
        
        if (empty($new_password)) {
            $error_message = "Password cannot be empty.";
        } elseif (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            if ($stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password. Please try again.";
            }
        }
    }
    
    // Connect with Parent
    if (isset($_POST['connect_parent'])) {
        // Check if child already has a parent connection
        $check_stmt = $conn->prepare("SELECT parent_id FROM parent_children_connection WHERE child_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "You are already connected with a parent. Please disconnect from your current parent before connecting with a new one.";
        } else {
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
                $success_message = "Successfully connected with parent!";
            } else {
                $error_message = "No parent found with this email address.";
            }
        }
    }

    // Disconnect from Parent
    if (isset($_POST['disconnect_parent'])) {
        $stmt = $conn->prepare("DELETE FROM parent_children_connection WHERE child_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $success_message = "Successfully disconnected from parent.";
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
    <style>
    .error-message {
        background-color: #ffebee;
        color: #c62828;
        padding: 1rem;
        border-radius: 5px;
        margin: 1rem auto;
        border: 1px solid #ef9a9a;
        max-width: 600px;
    }

    .success-message {
        background-color: #e8f5e9;
        color: #2e7d32;
        padding: 1rem;
        border-radius: 5px;
        margin: 1rem auto;
        border: 1px solid #a5d6a7;
        max-width: 600px;
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
            <h1>👤 My Account</h1>

            <div class="profile-card">
                <img src="<?php echo $user['profile_pic'] ?: 'default-avatar.png'; ?>" alt="Profile Picture"
                    class="profile-pic">
                <h2>Name: <?php echo $full_name; ?></h2>
                <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if (isset($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
                <?php endif; ?>

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

            <h2>👨‍👩‍👧‍👦 Connected Parent</h2>
            <?php if (!empty($parents)): ?>
            <ul>
                <?php foreach ($parents as $parent): ?>
                <li>
                    <?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']) . 
                                      " (" . htmlspecialchars($parent['email']) . ")"; ?>
                    <form method="POST" action="" style="display: inline;">
                        <button type="submit" name="disconnect_parent" class="disconnect-btn">Disconnect</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p>No parent connected yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>