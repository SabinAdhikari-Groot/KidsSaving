<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize variables to prevent undefined variable warnings
$user = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'profile_pic' => 'default-avatar.png'
];
$error = '';
$success = '';

try {
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Update Profile
        if (isset($_POST['update_profile'])) {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            
            // Validate inputs
            if (empty($first_name) || empty($last_name) || empty($email)) {
                $error = "All fields are required!";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format!";
            } else {
                // Handle profile picture upload
                $profile_pic = $user['profile_pic']; // Keep existing if no new upload
                
                if (!empty($_FILES['profile_pic']['name'])) {
                    $target_dir = "uploads/";
                    $file_extension = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
                    $new_filename = "profile_" . $user_id . "_" . time() . "." . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    // Check if image file is a actual image
                    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
                    if ($check === false) {
                        $error = "File is not an image.";
                    } elseif ($_FILES["profile_pic"]["size"] > 500000) { // 500KB limit
                        $error = "Sorry, your file is too large.";
                    } else {
                        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                            // Delete old profile picture if it's not the default
                            if ($user['profile_pic'] !== 'default-avatar.png' && file_exists($user['profile_pic'])) {
                                unlink($user['profile_pic']);
                            }
                            $profile_pic = $target_file;
                        } else {
                            $error = "Sorry, there was an error uploading your file.";
                        }
                    }
                }
                
                if (empty($error)) {
                    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, profile_pic = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $first_name, $last_name, $email, $profile_pic, $user_id);
                    if ($stmt->execute()) {
                        $success = "Profile updated successfully!";
                    } else {
                        $error = "Error updating profile: " . $conn->error;
                    }
                }
            }
        }
        
        // Change Password
        if (isset($_POST['change_password'])) {
            $new_password = trim($_POST['new_password']);
            $confirm_password = trim($_POST['confirm_password']);
            
            if (empty($new_password) || empty($confirm_password)) {
                $error = "Both password fields are required!";
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords don't match!";
            } elseif (strlen($new_password) < 8) {
                $error = "Password must be at least 8 characters!";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                if ($stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Error changing password: " . $conn->error;
                }
            }
        }
    }

    // Fetch user details
    $stmt = $conn->prepare("SELECT first_name, last_name, email, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Ensure profile_pic has a value
        if (empty($user['profile_pic'])) {
            $user['profile_pic'] = 'default-avatar.png';
        }
    } else {
        $error = "User not found!";
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

$full_name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving - Parent Account</title>
    <link rel="stylesheet" href="parent_account.css">
    <style>
    .error {
        color: red;
        margin: 10px 0;
    }

    .success {
        color: green;
        margin: 10px 0;
    }

    .profile-pic {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #ff6f61;
    }

    form {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    input,
    button {
        display: block;
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        border: 1px solid #ddd;
    }

    button {
        background-color: #ff6f61;
        color: white;
        border: none;
        cursor: pointer;
    }

    button:hover {
        background-color: #ff5733;
    }
    </style>
</head>

<body>
    <!-- Sidebar -->
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
            <h1>👤 My Account</h1>

            <!-- Display error/success messages -->
            <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="profile-card">
                <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture"
                    class="profile-pic">
                <h2>Name: <?php echo $full_name; ?></h2>
                <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <h2>Update Profile</h2>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>"
                    placeholder="First Name" required>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>"
                    placeholder="Last Name" required>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                    placeholder="Email" required>
                <label for="profile_pic">Profile Picture:</label>
                <input type="file" name="profile_pic" accept="image/*">
                <button type="submit" name="update_profile">Update Profile</button>
            </form>

            <form method="POST" action="">
                <h2>Change Password</h2>
                <input type="password" name="new_password" placeholder="New Password" required minlength="8">
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required
                    minlength="8">
                <button type="submit" name="change_password">Change Password</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>