<?php
// Include your database connection
include('db.php');

// Fetch all users from the database
$query = "SELECT * FROM users";
$result = mysqli_query($conn, $query);

// Handle Add, Update, and Delete Operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        // Insert new user
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $dob = $_POST['dob'];
        $account_type = $_POST['account_type'];
        $profile_pic = 'uploads/avatar.png';

        $insert_query = "INSERT INTO users (email, password, first_name, last_name, dob, account_type, profile_pic) 
                         VALUES ('$email', '$password', '$first_name', '$last_name', '$dob', '$account_type', '$profile_pic')";
        mysqli_query($conn, $insert_query);
        header("Location: admin_managing_users.php"); // Refresh page
        exit;
    } elseif (isset($_POST['update_user'])) {
        // Update existing user
        $id = $_POST['user_id'];
        $email = $_POST['email'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $dob = $_POST['dob'];
        $account_type = $_POST['account_type'];

        $update_query = "UPDATE users SET email='$email', first_name='$first_name', last_name='$last_name', dob='$dob', account_type='$account_type' WHERE id=$id";
        mysqli_query($conn, $update_query);
        header("Location: admin_managing_users.php"); // Refresh page
        exit;
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $id = $_POST['user_id'];
        $delete_query = "DELETE FROM users WHERE id=$id";
        mysqli_query($conn, $delete_query);
        header("Location: admin_managing_users.php"); // Refresh page
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving - Manage Users</title>
    <link rel="stylesheet" href="admin_managing_users.css">
    <script>
    function editUser(id, email, first_name, last_name, dob, account_type) {
        document.getElementById("user_id").value = id;
        document.getElementById("email").value = email;
        document.getElementById("first_name").value = first_name;
        document.getElementById("last_name").value = last_name;
        document.getElementById("dob").value = dob;
        document.getElementById("account_type").value = account_type;
        document.getElementById("editModal").style.display = "block";
    }

    function closeEditModal() {
        document.getElementById("editModal").style.display = "none";
    }

    function confirmDelete(userId) {
        if (confirm("Are you sure you want to delete this user?")) {
            document.getElementById("delete_user_id").value = userId;
            document.getElementById("deleteForm").submit();
        }
    }
    </script>
</head>

<body>
    <aside class="sidebar">
        <h1>KidsSaving</h1>
        <ul>
            <li><a href="admin_dashboard.php">Home</a></li>
            <li><a href="admin_managing_users.php">Manage Users</a></li>
            <li><a href="admin_adding_notes.php">Manage Finance Notes</a></li>
            <li><a href="admin_managing_quizzes.php">Manage Quizzes</a></li>
            <li><a href="logout.php">Log out</a></li>
        </ul>
    </aside>

    <main>
        <h2>Manage Users</h2>

        <!-- Add User Form -->
        <form action="admin_managing_users.php" method="POST">
            <h3>Add User</h3>
            <label for="email">Email:</label><input type="email" name="email" required><br>
            <label for="password">Password:</label><input type="password" name="password" required><br>
            <label for="first_name">First Name:</label><input type="text" name="first_name" required><br>
            <label for="last_name">Last Name:</label><input type="text" name="last_name" required><br>
            <label for="dob">Date of Birth:</label><input type="date" name="dob" required><br>
            <label for="account_type">Account Type:</label>
            <select name="account_type" required>
                <option value="Child">Child</option>
                <option value="Parent">Parent</option>
            </select><br>
            <button type="submit" name="add_user">Add User</button>
        </form>

        <h3>Existing Users</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Date of Birth</th>
                    <th>Account Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['first_name'] . " " . $user['last_name']; ?></td>
                    <td><?php echo $user['dob']; ?></td>
                    <td><?php echo $user['account_type']; ?></td>
                    <td>
                        <button
                            onclick="editUser('<?php echo $user['id']; ?>', '<?php echo $user['email']; ?>', '<?php echo $user['first_name']; ?>', '<?php echo $user['last_name']; ?>', '<?php echo $user['dob']; ?>', '<?php echo $user['account_type']; ?>')">Update</button>
                        <button onclick="confirmDelete('<?php echo $user['id']; ?>')">Delete</button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Update User Modal -->
        <div id="editModal" style="display:none;">
            <form action="admin_managing_users.php" method="POST">
                <h3>Update User</h3>
                <input type="hidden" id="user_id" name="user_id">
                <label for="email">Email:</label><input type="email" id="email" name="email" required><br>
                <label for="first_name">First Name:</label><input type="text" id="first_name" name="first_name"
                    required><br>
                <label for="last_name">Last Name:</label><input type="text" id="last_name" name="last_name"
                    required><br>
                <label for="dob">Date of Birth:</label><input type="date" id="dob" name="dob" required><br>
                <label for="account_type">Account Type:</label>
                <select id="account_type" name="account_type" required>
                    <option value="Child">Child</option>
                    <option value="Parent">Parent</option>
                </select><br>
                <button type="submit" name="update_user">Update User</button>
                <button type="button" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>

        <form id="deleteForm" action="admin_managing_users.php" method="POST" style="display:none;">
            <input type="hidden" id="delete_user_id" name="user_id">
            <input type="hidden" name="delete_user">
        </form>
    </main>
</body>

</html>