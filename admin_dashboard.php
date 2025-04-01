<?php
session_start();
include 'db.php';

// Check if user is admin (add your authentication logic here)

// Fetch dashboard statistics
$parent_count_query = "SELECT COUNT(*) AS total FROM users WHERE account_type = 'Parent'";
$parent_count_result = mysqli_query($conn, $parent_count_query);
$parent_count = mysqli_fetch_assoc($parent_count_result)['total'];

$child_count_query = "SELECT COUNT(*) AS total FROM users WHERE account_type = 'Child'";
$child_count_result = mysqli_query($conn, $child_count_query);
$child_count = mysqli_fetch_assoc($child_count_result)['total'];

$quiz_count_query = "SELECT COUNT(*) AS total FROM quizzes";
$quiz_count_result = mysqli_query($conn, $quiz_count_query);
$quiz_count = mysqli_fetch_assoc($quiz_count_result)['total'];

$notes_count_query = "SELECT COUNT(*) AS total FROM finance_notes";
$notes_count_result = mysqli_query($conn, $notes_count_query);
$notes_count = mysqli_fetch_assoc($notes_count_result)['total'];

$tasks_count_query = "SELECT COUNT(*) AS total FROM tasks";
$tasks_count_result = mysqli_query($conn, $tasks_count_query);
$tasks_count = mysqli_fetch_assoc($tasks_count_result)['total'];

// Fetch data for each section with search and sort functionality
$users_query = "SELECT * FROM users";
$notes_query = "SELECT * FROM finance_notes ORDER BY created_at DESC";
$quizzes_query = "SELECT * FROM quizzes ORDER BY id DESC";
$store_items_query = "SELECT * FROM store_items";

// Handle search for users
if (isset($_GET['search_users']) && !empty($_GET['search_users'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search_users']);
    $users_query = "SELECT * FROM users WHERE 
                    email LIKE '%$search%' OR 
                    first_name LIKE '%$search%' OR 
                    last_name LIKE '%$search%' OR 
                    account_type LIKE '%$search%'";
}

// Handle search for notes
if (isset($_GET['search_notes']) && !empty($_GET['search_notes'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search_notes']);
    $notes_query = "SELECT * FROM finance_notes WHERE 
                    title LIKE '%$search%' OR 
                    content LIKE '%$search%'";
}

// Handle search for quizzes
if (isset($_GET['search_quizzes']) && !empty($_GET['search_quizzes'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search_quizzes']);
    $quizzes_query = "SELECT * FROM quizzes WHERE 
                      question LIKE '%$search%' OR 
                      option_a LIKE '%$search%' OR 
                      option_b LIKE '%$search%' OR 
                      option_c LIKE '%$search%'";
}

// Handle search for store items
if (isset($_GET['search_store']) && !empty($_GET['search_store'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search_store']);
    $store_items_query = "SELECT * FROM store_items WHERE 
                          item_name LIKE '%$search%'";
}

// Handle sorting
if (isset($_GET['sort'])) {
    $table = $_GET['table'];
    $column = $_GET['column'];
    $direction = $_GET['direction'];
    
    if ($table == 'users') {
        $users_query = "SELECT * FROM users ORDER BY $column $direction";
    } elseif ($table == 'notes') {
        $notes_query = "SELECT * FROM finance_notes ORDER BY $column $direction";
    } elseif ($table == 'quizzes') {
        $quizzes_query = "SELECT * FROM quizzes ORDER BY $column $direction";
    } elseif ($table == 'store') {
        $store_items_query = "SELECT * FROM store_items ORDER BY $column $direction";
    }
}

$users = mysqli_query($conn, $users_query);
$notes = mysqli_query($conn, $notes_query);
$quizzes = mysqli_query($conn, $quizzes_query);
$store_items = mysqli_query($conn, $store_items_query);

// Handle all form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle Users
    if (isset($_POST['add_user'])) {
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
        header("Location: admin_dashboard.php#users");
        exit;
    } elseif (isset($_POST['update_user'])) {
        $id = $_POST['user_id'];
        $email = $_POST['email'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $dob = $_POST['dob'];
        $account_type = $_POST['account_type'];

        $update_query = "UPDATE users SET email='$email', first_name='$first_name', last_name='$last_name', dob='$dob', account_type='$account_type' WHERE id=$id";
        mysqli_query($conn, $update_query);
        header("Location: admin_dashboard.php#users");
        exit;
    } elseif (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        $delete_query = "DELETE FROM users WHERE id=$id";
        mysqli_query($conn, $delete_query);
        header("Location: admin_dashboard.php#users");
        exit;
    }
    
    // Handle Finance Notes
    elseif (isset($_POST['add_note'])) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);

        $insert_query = "INSERT INTO finance_notes (title, content, created_at) 
                         VALUES ('$title', '$content', NOW())";
        mysqli_query($conn, $insert_query);
        header("Location: admin_dashboard.php#notes");
        exit;
    } elseif (isset($_POST['update_note'])) {
        $id = $_POST['note_id'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);

        $update_query = "UPDATE finance_notes SET title='$title', content='$content' WHERE id=$id";
        mysqli_query($conn, $update_query);
        header("Location: admin_dashboard.php#notes");
        exit;
    } elseif (isset($_POST['delete_note'])) {
        $id = $_POST['note_id'];
        $delete_query = "DELETE FROM finance_notes WHERE id=$id";
        mysqli_query($conn, $delete_query);
        header("Location: admin_dashboard.php#notes");
        exit;
    }
    
    // Handle Quizzes
    elseif (isset($_POST['add_quiz'])) {
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $option_a = mysqli_real_escape_string($conn, $_POST['option_a']);
        $option_b = mysqli_real_escape_string($conn, $_POST['option_b']);
        $option_c = mysqli_real_escape_string($conn, $_POST['option_c']);
        $correct_option = mysqli_real_escape_string($conn, $_POST['correct_option']);

        $insert_query = "INSERT INTO quizzes (question, option_a, option_b, option_c, correct_option) 
                         VALUES ('$question', '$option_a', '$option_b', '$option_c', '$correct_option')";
        mysqli_query($conn, $insert_query);
        header("Location: admin_dashboard.php#quizzes");
        exit;
    } elseif (isset($_POST['update_quiz'])) {
        $id = $_POST['quiz_id'];
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $option_a = mysqli_real_escape_string($conn, $_POST['option_a']);
        $option_b = mysqli_real_escape_string($conn, $_POST['option_b']);
        $option_c = mysqli_real_escape_string($conn, $_POST['option_c']);
        $correct_option = mysqli_real_escape_string($conn, $_POST['correct_option']);

        $update_query = "UPDATE quizzes SET question='$question', option_a='$option_a', option_b='$option_b', option_c='$option_c', correct_option='$correct_option' WHERE id=$id";
        mysqli_query($conn, $update_query);
        header("Location: admin_dashboard.php#quizzes");
        exit;
    } elseif (isset($_POST['delete_quiz'])) {
        $id = $_POST['quiz_id'];
        $delete_query = "DELETE FROM quizzes WHERE id=$id";
        mysqli_query($conn, $delete_query);
        header("Location: admin_dashboard.php#quizzes");
        exit;
    }
    
    // Handle Store Items
    elseif (isset($_POST['add_item'])) {
        $item_name = $_POST['item_name'];
        $item_price = $_POST['item_price'];
        $item_image = 'uploads/default_item.png';

        $insert_query = "INSERT INTO store_items (item_name, item_price, item_image) 
                         VALUES ('$item_name', '$item_price', '$item_image')";
        mysqli_query($conn, $insert_query);
        header("Location: admin_dashboard.php#store");
        exit;
    } elseif (isset($_POST['update_item'])) {
        $id = $_POST['item_id'];
        $item_name = $_POST['item_name'];
        $item_price = $_POST['item_price'];

        $update_query = "UPDATE store_items SET item_name='$item_name', item_price='$item_price' WHERE id=$id";
        mysqli_query($conn, $update_query);
        header("Location: admin_dashboard.php#store");
        exit;
    } elseif (isset($_POST['delete_item'])) {
        $id = $_POST['item_id'];
        $delete_query = "DELETE FROM store_items WHERE id=$id";
        mysqli_query($conn, $delete_query);
        header("Location: admin_dashboard.php#store");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving - Admin Dashboard</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
        background-color: #333;
        color: white;
        padding: 20px;
        width: 250px;
        position: fixed;
        height: 100%;
        overflow-y: auto;
        transition: all 0.3s ease-in-out;
    }

    .sidebar h1 {
        color: #fff;
        text-align: center;
    }

    .sidebar ul {
        list-style: none;
        padding: 0;
    }

    .sidebar li a {
        color: white;
        text-decoration: none;
        display: block;
        padding: 10px;
        margin: 5px 0;
        transition: background 0.3s ease-in-out;
    }

    .sidebar li a:hover {
        background-color: #555;
    }

    /* Main Content */
    .main-content {
        flex: 1;
        padding: 20px;
        margin-left: 250px;
        /* Fix sidebar overlapping */
        transition: margin-left 0.3s ease-in-out;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        padding-left: 25px;
        display: block;
    }

    /* Stats Boxes */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        /* Responsive grid */
        gap: 15px;
        margin-bottom: 30px;
    }

    .stat-box {
        text-align: center;
        padding: 20px;
        background-color: #f2f2f2;
        border-radius: 5px;
    }

    /* Table Styling */
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }

    table,
    th,
    td {
        border: 1px solid #ddd;
    }

    th,
    td {
        padding: 10px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
        cursor: pointer;
        position: relative;
    }

    th.sort-asc:after {
        content: "↑";
        position: absolute;
        right: 8px;
    }

    th.sort-desc:after {
        content: "↓";
        position: absolute;
        right: 8px;
    }

    /* Forms */
    form {
        margin: 20px 0;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }

    input,
    textarea,
    select {
        margin: 5px 0 15px;
        padding: 8px;
        width: 100%;
        box-sizing: border-box;
    }

    button {
        padding: 8px 15px;
        color: white;
        border: none;
        cursor: pointer;
        margin-right: 10px;
        border-radius: 4px;
    }

    .btn-primary {
        background-color: #4CAF50;
    }

    .btn-primary:hover {
        background-color: #45a049;
    }

    .btn-danger {
        background-color: #f44336;
    }

    .btn-danger:hover {
        background-color: #d32f2f;
    }

    .btn-info {
        background-color: #2196F3;
    }

    .btn-info:hover {
        background-color: #0b7dda;
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
    }

    .modal-content {
        background-color: #fefefe;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: black;
    }

    /* Search Box */
    .search-box {
        margin: 20px 0;
        display: flex;
    }

    .search-box input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-right: 10px;
    }

    .search-box button {
        padding: 10px 15px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
        }

        .main-content {
            margin-left: 0;
        }

        .stat-box {
            width: 100%;
        }
    }
    </style>
    <script>
    // Tab switching
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;

        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].classList.remove("active");
        }

        tablinks = document.getElementsByClassName("tablink");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }

        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");

        // Update URL hash
        window.location.hash = tabName;
    }

    // Set active tab based on URL hash
    window.onload = function() {
        var hash = window.location.hash.substring(1);
        if (hash) {
            var tab = document.getElementById(hash);
            if (tab) {
                var tabcontent = document.getElementsByClassName("tab-content");
                for (var i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].classList.remove("active");
                }

                var tablinks = document.getElementsByClassName("tablink");
                for (var i = 0; i < tablinks.length; i++) {
                    tablinks[i].classList.remove("active");
                }

                tab.classList.add("active");
                document.querySelector('[onclick="openTab(event, \'' + hash + '\')"]').classList.add("active");
            }
        } else {
            // Default to dashboard tab
            document.getElementById("dashboard").classList.add("active");
            document.querySelector('[onclick="openTab(event, \'dashboard\')"]').classList.add("active");
        }
    };

    // Modal functions for each section
    function editUser(id, email, first_name, last_name, dob, account_type) {
        document.getElementById("user_id").value = id;
        document.getElementById("edit_email").value = email;
        document.getElementById("edit_first_name").value = first_name;
        document.getElementById("edit_last_name").value = last_name;
        document.getElementById("edit_dob").value = dob;
        document.getElementById("edit_account_type").value = account_type;
        document.getElementById("userModal").style.display = "block";
    }

    function editNote(id, title, content) {
        document.getElementById("note_id").value = id;
        document.getElementById("edit_title").value = title;
        document.getElementById("edit_content").value = content;
        document.getElementById("noteModal").style.display = "block";
    }

    function editQuiz(id, question, option_a, option_b, option_c, correct_option) {
        document.getElementById("quiz_id").value = id;
        document.getElementById("edit_question").value = question;
        document.getElementById("edit_option_a").value = option_a;
        document.getElementById("edit_option_b").value = option_b;
        document.getElementById("edit_option_c").value = option_c;
        document.getElementById("edit_correct_option").value = correct_option;
        document.getElementById("quizModal").style.display = "block";
    }

    function editItem(id, name, price) {
        document.getElementById("item_id").value = id;
        document.getElementById("edit_item_name").value = name;
        document.getElementById("edit_item_price").value = price;
        document.getElementById("itemModal").style.display = "block";
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }

    function confirmDelete(type, id) {
        if (confirm("Are you sure you want to delete this " + type + "?")) {
            document.getElementById("delete_id").value = id;
            document.getElementById("delete_type").value = type;
            document.getElementById("deleteForm").submit();
        }
    }

    // Sort table columns
    function sortTable(table, column, direction) {
        window.location.href =
            `admin_dashboard.php?sort=1&table=${table}&column=${column}&direction=${direction}#${table}`;
    }

    // Add sort indicators to table headers
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('sort')) {
            const table = urlParams.get('table');
            const column = urlParams.get('column');
            const direction = urlParams.get('direction');

            const th = document.querySelector(`#${table} th[data-column="${column}"]`);
            if (th) {
                th.classList.add(`sort-${direction.toLowerCase()}`);
            }
        }
    });
    </script>
</head>

<body>
    <aside class="sidebar">
        <h1>KidsSaving</h1>
        <ul>
            <li><a href="#" class="tablink" onclick="openTab(event, 'dashboard')">Dashboard</a></li>
            <li><a href="#" class="tablink" onclick="openTab(event, 'users')">Manage Users</a></li>
            <li><a href="#" class="tablink" onclick="openTab(event, 'notes')">Finance Notes</a></li>
            <li><a href="#" class="tablink" onclick="openTab(event, 'quizzes')">Quizzes</a></li>
            <li><a href="#" class="tablink" onclick="openTab(event, 'store')">Store</a></li>
            <li><a href="logout.php">Log out</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content">
            <h1>Admin Dashboard</h1>

            <div class="stats-container">
                <div class="stat-box">
                    <div>Parents</div>
                    <div class="stat-value"><?php echo $parent_count; ?></div>
                </div>
                <div class="stat-box">
                    <div>Children</div>
                    <div class="stat-value"><?php echo $child_count; ?></div>
                </div>
                <div class="stat-box">
                    <div>Quizzes</div>
                    <div class="stat-value"><?php echo $quiz_count; ?></div>
                </div>
                <div class="stat-box">
                    <div>Finance Notes</div>
                    <div class="stat-value"><?php echo $notes_count; ?></div>
                </div>
            </div>

            <h2>Quick Overview</h2>
            <p>Welcome to the KidsSaving admin dashboard. Use the navigation menu to manage different sections of the
                application.</p>
        </div>

        <!-- Users Tab -->
        <div id="users" class="tab-content">
            <h2>Manage Users</h2>

            <!-- Search Users -->
            <form method="GET" class="search-box">
                <input type="text" name="search_users" placeholder="Search users..."
                    value="<?php echo isset($_GET['search_users']) ? htmlspecialchars($_GET['search_users']) : ''; ?>">
                <button type="submit" class="btn-info">Search</button>
                <?php if (isset($_GET['search_users'])) : ?>
                    <a href="admin_dashboard.php#users" class="btn-danger"
   style="text-decoration: none; padding: 20px 15px; color: white; border-radius: 5px;">
   Clear
</a>

                <?php endif; ?>
            </form>

            <!-- Add User Form -->
            <form method="POST">
                <h3>Add User</h3>
                <label for="email">Email:</label>
                <input type="email" name="email" required>

                <label for="password">Password:</label>
                <input type="password" name="password" required>

                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" required>

                <label for="last_name">Last Name:</label>
                <input type="text" name="last_name" required>

                <label for="dob">Date of Birth:</label>
                <input type="date" name="dob" required>

                <label for="account_type">Account Type:</label>
                <select name="account_type" required>
                    <option value="Child">Child</option>
                    <option value="Parent">Parent</option>
                </select>

                <button type="submit" name="add_user" class="btn-primary">Add User</button>
            </form>

            <!-- Users Table -->
            <h3>Existing Users</h3>
            <table>
                <thead>
                    <tr>
                        <th data-column="id"
                            onclick="sortTable('users', 'id', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'id' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            ID
                        </th>

                        <th data-column="email"
                            onclick="sortTable('users', 'email', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'email' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            Email
                        </th>

                        <th data-column="first_name"
                            onclick="sortTable('users', 'first_name', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'first_name' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            Name
                        </th>
                        <th data-column="dob"
                            onclick="sortTable('users', 'dob', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'dob' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            Date of Birth
                        </th>
                        <th data-column="account_type"
                            onclick="sortTable('users', 'account_type', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'account_type' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            Account Type
                        </th>

                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users)) { ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['first_name'] . " " . $user['last_name']; ?></td>
                        <td><?php echo $user['dob']; ?></td>
                        <td><?php echo $user['account_type']; ?></td>
                        <td>
                            <button class="btn-info"
                                onclick="editUser('<?php echo $user['id']; ?>', '<?php echo $user['email']; ?>', '<?php echo $user['first_name']; ?>', '<?php echo $user['last_name']; ?>', '<?php echo $user['dob']; ?>', '<?php echo $user['account_type']; ?>')">Update</button>
                            <button class="btn-danger"
                                onclick="confirmDelete('user', '<?php echo $user['id']; ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Update User Modal -->
            <div id="userModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('userModal')">&times;</span>
                    <form method="POST">
                        <h3>Update User</h3>
                        <input type="hidden" id="user_id" name="user_id">

                        <label for="edit_email">Email:</label>
                        <input type="email" id="edit_email" name="email" required>

                        <label for="edit_first_name">First Name:</label>
                        <input type="text" id="edit_first_name" name="first_name" required>

                        <label for="edit_last_name">Last Name:</label>
                        <input type="text" id="edit_last_name" name="last_name" required>

                        <label for="edit_dob">Date of Birth:</label>
                        <input type="date" id="edit_dob" name="dob" required>

                        <label for="edit_account_type">Account Type:</label>
                        <select id="edit_account_type" name="account_type" required>
                            <option value="Child">Child</option>
                            <option value="Parent">Parent</option>
                        </select>

                        <button type="submit" name="update_user" class="btn-primary">Update User</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Finance Notes Tab -->
        <div id="notes" class="tab-content">
            <h2>Manage Finance Notes</h2>

            <!-- Search Notes -->
            <form method="GET" class="search-box">
                <input type="text" name="search_notes" placeholder="Search notes..."
                    value="<?php echo isset($_GET['search_notes']) ? htmlspecialchars($_GET['search_notes']) : ''; ?>">
                <button type="submit" class="btn-info">Search</button>
                <?php if (isset($_GET['search_notes'])) : ?>
                <a href="admin_dashboard.php#notes" class="btn-danger"
                style="text-decoration: none; padding: 20px 15px; color: white; border-radius: 5px;">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Add Note Form -->
            <form method="POST">
                <h3>Add Finance Note</h3>
                <label for="title">Title:</label>
                <input type="text" name="title" required>

                <label for="content">Content:</label>
                <textarea name="content" required></textarea>

                <button type="submit" name="add_note" class="btn-primary">Add Note</button>
            </form>

            <!-- Notes Table -->
            <h3>Existing Finance Notes</h3>
            <table>
                <thead>
                    <tr>
                        <th data-column="id"
                            onclick="sortTable('notes', 'id', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'id' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            ID
                        </th>
                        <th data-column="title"
                            onclick="sortTable('notes', 'title', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'title' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            Title
                        </th>
                        <th>Content Preview</th>
                        <th data-column="created_at"
                            onclick="sortTable('notes', 'created_at', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'created_at' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            Created At
                        </th>

                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($note = mysqli_fetch_assoc($notes)) { ?>
                    <tr>
                        <td><?php echo $note['id']; ?></td>
                        <td><?php echo $note['title']; ?></td>
                        <td><?php echo substr($note['content'], 0, 50) . '...'; ?></td>
                        <td><?php echo $note['created_at']; ?></td>
                        <td>
                            <button class="btn-info"
                                onclick="editNote('<?php echo $note['id']; ?>', '<?php echo addslashes($note['title']); ?>', '<?php echo addslashes($note['content']); ?>')">Update</button>
                            <button class="btn-danger"
                                onclick="confirmDelete('note', '<?php echo $note['id']; ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Update Note Modal -->
            <div id="noteModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('noteModal')">&times;</span>
                    <form method="POST">
                        <h3>Update Finance Note</h3>
                        <input type="hidden" id="note_id" name="note_id">

                        <label for="edit_title">Title:</label>
                        <input type="text" id="edit_title" name="title" required>

                        <label for="edit_content">Content:</label>
                        <textarea id="edit_content" name="content" required></textarea>

                        <button type="submit" name="update_note" class="btn-primary">Update Note</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Quizzes Tab -->
        <div id="quizzes" class="tab-content">
            <h2>Manage Quizzes</h2>

            <!-- Search Quizzes -->
            <form method="GET" class="search-box">
                <input type="text" name="search_quizzes" placeholder="Search quizzes..."
                    value="<?php echo isset($_GET['search_quizzes']) ? htmlspecialchars($_GET['search_quizzes']) : ''; ?>">
                <button type="submit" class="btn-info">Search</button>
                <?php if (isset($_GET['search_quizzes'])) : ?>
                <a href="admin_dashboard.php#quizzes" class="btn-danger"
                style="text-decoration: none; padding: 20px 15px; color: white; border-radius: 5px;">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Add Quiz Form -->
            <form method="POST">
                <h3>Add Quiz</h3>
                <label for="question">Question:</label>
                <input type="text" name="question" required>

                <label for="option_a">Option A:</label>
                <input type="text" name="option_a" required>

                <label for="option_b">Option B:</label>
                <input type="text" name="option_b" required>

                <label for="option_c">Option C:</label>
                <input type="text" name="option_c" required>

                <label for="correct_option">Correct Option (A/B/C):</label>
                <input type="text" name="correct_option" required>

                <button type="submit" name="add_quiz" class="btn-primary">Add Quiz</button>
            </form>

            <!-- Quizzes Table -->
            <h3>Existing Quizzes</h3>
            <table>
                <thead>
                    <tr>
                        <th data-column="id"
                            onclick="sortTable('quizzes', 'id', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'id' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            ID
                        </th>
                        <th data-column="question"
                            onclick="sortTable('quizzes', 'question', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'question' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            Question
                        </th>

                        <th>Option A</th>
                        <th>Option B</th>
                        <th>Option C</th>
                        <th>Correct Option</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($quiz = mysqli_fetch_assoc($quizzes)) { ?>
                    <tr>
                        <td><?php echo $quiz['id']; ?></td>
                        <td><?php echo $quiz['question']; ?></td>
                        <td><?php echo $quiz['option_a']; ?></td>
                        <td><?php echo $quiz['option_b']; ?></td>
                        <td><?php echo $quiz['option_c']; ?></td>
                        <td><?php echo $quiz['correct_option']; ?></td>
                        <td>
                            <button class="btn-info"
                                onclick="editQuiz('<?php echo $quiz['id']; ?>', '<?php echo addslashes($quiz['question']); ?>', '<?php echo addslashes($quiz['option_a']); ?>', '<?php echo addslashes($quiz['option_b']); ?>', '<?php echo addslashes($quiz['option_c']); ?>', '<?php echo $quiz['correct_option']; ?>')">Update</button>
                            <button class="btn-danger"
                                onclick="confirmDelete('quiz', '<?php echo $quiz['id']; ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Update Quiz Modal -->
            <div id="quizModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('quizModal')">&times;</span>
                    <form method="POST">
                        <h3>Update Quiz</h3>
                        <input type="hidden" id="quiz_id" name="quiz_id">

                        <label for="edit_question">Question:</label>
                        <input type="text" id="edit_question" name="question" required>

                        <label for="edit_option_a">Option A:</label>
                        <input type="text" id="edit_option_a" name="option_a" required>

                        <label for="edit_option_b">Option B:</label>
                        <input type="text" id="edit_option_b" name="option_b" required>

                        <label for="edit_option_c">Option C:</label>
                        <input type="text" id="edit_option_c" name="option_c" required>

                        <label for="edit_correct_option">Correct Option (A/B/C):</label>
                        <input type="text" id="edit_correct_option" name="correct_option" required>

                        <button type="submit" name="update_quiz" class="btn-primary">Update Quiz</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Store Tab -->
        <div id="store" class="tab-content">
            <h2>Manage Store Items</h2>

            <!-- Search Store Items -->
            <form method="GET" class="search-box">
                <input type="text" name="search_store" placeholder="Search store items..."
                    value="<?php echo isset($_GET['search_store']) ? htmlspecialchars($_GET['search_store']) : ''; ?>">
                <button type="submit" class="btn-info">Search</button>
                <?php if (isset($_GET['search_store'])) : ?>
                <a href="admin_dashboard.php#store" class="btn-danger"
                style="text-decoration: none; padding: 20px 15px; color: white; border-radius: 5px;">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Add Item Form -->
            <form method="POST">
                <h3>Add Item</h3>
                <label for="item_name">Item Name:</label>
                <input type="text" name="item_name" required>

                <label for="item_price">Item Price:</label>
                <input type="number" name="item_price" step="0.01" required>

                <button type="submit" name="add_item" class="btn-primary">Add Item</button>
            </form>

            <!-- Store Items Table -->
            <h3>Existing Store Items</h3>
            <table>
                <thead>
                    <tr>
                        <th data-column="id"
                            onclick="sortTable('store', 'id', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'id' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            ID
                        </th>
                        <th data-column="item_name"
                            onclick="sortTable('store', 'item_name', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'item_name' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            Item Name
                        </th>
                        <th data-column="item_price"
                            onclick="sortTable('store', 'item_price', '<?php echo (isset($_GET['column']) && $_GET['column'] == 'item_price' && $_GET['direction'] == 'ASC') ? 'DESC' : 'ASC'; ?>')">
                            Price
                        </th>

                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($store_items)) { ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo $item['item_name']; ?></td>
                        <td><?php echo $item['item_price']; ?></td>
                        <td>
                            <button class="btn-info"
                                onclick="editItem('<?php echo $item['id']; ?>', '<?php echo $item['item_name']; ?>', '<?php echo $item['item_price']; ?>')">Update</button>
                            <button class="btn-danger"
                                onclick="confirmDelete('item', '<?php echo $item['id']; ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Update Item Modal -->
            <div id="itemModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('itemModal')">&times;</span>
                    <form method="POST">
                        <h3>Update Item</h3>
                        <input type="hidden" id="item_id" name="item_id">

                        <label for="edit_item_name">Item Name:</label>
                        <input type="text" id="edit_item_name" name="item_name" required>

                        <label for="edit_item_price">Item Price:</label>
                        <input type="number" id="edit_item_price" name="item_price" step="0.01" required>

                        <button type="submit" name="update_item" class="btn-primary">Update Item</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Form (used by all sections) -->
        <form id="deleteForm" method="POST" style="display:none;">
            <input type="hidden" id="delete_id" name="user_id">
            <input type="hidden" id="delete_type" name="delete_user">
        </form>
    </div>

    <script>
    // Handle delete form submission
    document.getElementById('deleteForm').addEventListener('submit', function(e) {
        e.preventDefault();

        var type = document.getElementById('delete_type').value;
        var id = document.getElementById('delete_id').value;

        // Set the appropriate hidden field based on type
        if (type === 'user') {
            document.getElementById('deleteForm').action = '#users';
            document.querySelector('[name="delete_user"]').value = '1';
        } else if (type === 'note') {
            document.getElementById('deleteForm').action = '#notes';
            document.querySelector('[name="delete_note"]').value = '1';
        } else if (type === 'quiz') {
            document.getElementById('deleteForm').action = '#quizzes';
            document.querySelector('[name="delete_quiz"]').value = '1';
        } else if (type === 'item') {
            document.getElementById('deleteForm').action = '#store';
            document.querySelector('[name="delete_item"]').value = '1';
        }

        this.submit();
    });
    </script>
</body>

</html>