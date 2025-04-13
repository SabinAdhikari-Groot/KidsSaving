<?php
session_start();
include 'db.php';

// Initialize feedback message array
$_SESSION['feedback'] = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : [];

// Function to add feedback message
function addFeedback($type, $message) {
    $_SESSION['feedback'][] = ['type' => $type, 'message' => $message];
}

// Check if user is admin (add your authentication logic here)

try {
    // Fetch dashboard statistics
    $parent_count_query = "SELECT COUNT(*) AS total FROM users WHERE account_type = 'Parent'";
    $parent_count_result = mysqli_query($conn, $parent_count_query);
    if (!$parent_count_result) {
        throw new Exception("Error fetching parent count: " . mysqli_error($conn));
    }
    $parent_count = mysqli_fetch_assoc($parent_count_result)['total'];

    $child_count_query = "SELECT COUNT(*) AS total FROM users WHERE account_type = 'Child'";
    $child_count_result = mysqli_query($conn, $child_count_query);
    if (!$child_count_result) {
        throw new Exception("Error fetching child count: " . mysqli_error($conn));
    }
    $child_count = mysqli_fetch_assoc($child_count_result)['total'];

    $quiz_count_query = "SELECT COUNT(*) AS total FROM quizzes";
    $quiz_count_result = mysqli_query($conn, $quiz_count_query);
    $quiz_count = mysqli_fetch_assoc($quiz_count_result)['total'];

    $notes_count_query = "SELECT COUNT(*) AS total FROM finance_notes";
    $notes_count_result = mysqli_query($conn, $notes_count_query);
    $notes_count = mysqli_fetch_assoc($notes_count_result)['total'];

    $messages_count_query = "SELECT COUNT(*) AS total FROM user_query";
    $messages_count_result = mysqli_query($conn, $messages_count_query);
    $messages_count = mysqli_fetch_assoc($messages_count_result)['total'];

    // Pagination settings
    $items_per_page = 10;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $items_per_page;

    // Fetch data for each section with pagination and descending order
    $users_query = "SELECT * FROM users ORDER BY id DESC LIMIT $offset, $items_per_page";
    $notes_query = "SELECT * FROM finance_notes ORDER BY id DESC LIMIT $offset, $items_per_page";
    $quizzes_query = "SELECT * FROM quizzes ORDER BY id DESC LIMIT $offset, $items_per_page";
    $store_items_query = "SELECT * FROM store_items ORDER BY id DESC LIMIT $offset, $items_per_page";
    $messages_query = "SELECT * FROM user_query ORDER BY id DESC LIMIT $offset, $items_per_page";

    // Get total counts for pagination
    $total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
    $total_notes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM finance_notes"))['total'];
    $total_quizzes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM quizzes"))['total'];
    $total_store_items = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM store_items"))['total'];
    $total_messages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM user_query"))['total'];

    $users = mysqli_query($conn, $users_query);
    $notes = mysqli_query($conn, $notes_query);
    $quizzes = mysqli_query($conn, $quizzes_query);
    $store_items = mysqli_query($conn, $store_items_query);
    $messages = mysqli_query($conn, $messages_query);

    // Calculate total pages for each section
    $total_pages_users = ceil($total_users / $items_per_page);
    $total_pages_notes = ceil($total_notes / $items_per_page);
    $total_pages_quizzes = ceil($total_quizzes / $items_per_page);
    $total_pages_store = ceil($total_store_items / $items_per_page);
    $total_pages_messages = ceil($total_messages / $items_per_page);

    // Handle all form submissions
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Handle Users
        if (isset($_POST['add_user'])) {
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
            $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
            $dob = mysqli_real_escape_string($conn, $_POST['dob']);
            $account_type = mysqli_real_escape_string($conn, $_POST['account_type']);
            $profile_pic = 'uploads/avatar.png';

            // Validate inputs
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            if (strlen($_POST['password']) < 6) {
                throw new Exception("Password must be at least 6 characters long");
            }

            // Check if email already exists
            $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
            if (mysqli_num_rows($check_email) > 0) {
                throw new Exception("Email already exists");
            }

            $insert_query = "INSERT INTO users (email, password, first_name, last_name, dob, account_type, profile_pic) 
                            VALUES ('$email', '$password', '$first_name', '$last_name', '$dob', '$account_type', '$profile_pic')";
            
            if (!mysqli_query($conn, $insert_query)) {
                throw new Exception("Error adding user: " . mysqli_error($conn));
            }
            
            addFeedback('success', 'User added successfully');
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
        } elseif (isset($_POST['delete_id']) && isset($_POST['delete_type'])) {
            $id = $_POST['delete_id'];
            $type = $_POST['delete_type'];
            
            switch($type) {
                case 'user':
                    $delete_query = "DELETE FROM users WHERE id=$id";
                    $redirect = '#users';
                    break;
                case 'note':
                    $delete_query = "DELETE FROM finance_notes WHERE id=$id";
                    $redirect = '#notes';
                    break;
                case 'quiz':
                    $delete_query = "DELETE FROM quizzes WHERE id=$id";
                    $redirect = '#quizzes';
                    break;
                case 'item':
                    $delete_query = "DELETE FROM store_items WHERE id=$id";
                    $redirect = '#store';
                    break;
                case 'message':
                    $delete_query = "DELETE FROM user_query WHERE id=$id";
                    $redirect = '#messages';
                    break;
                default:
                    header("Location: admin_dashboard.php");
                    exit;
            }
            
            if (mysqli_query($conn, $delete_query)) {
                header("Location: admin_dashboard.php" . $redirect);
                exit;
            }
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
        }
    }

} catch (Exception $e) {
    addFeedback('error', $e->getMessage());
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving - Admin Dashboard</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
    /* ... existing styles ... */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        gap: 5px;
    }

    .pagination a {
        padding: 8px 12px;
        text-decoration: none;
        border: 1px solid #ddd;
        color: #333;
        border-radius: 4px;
    }

    .pagination a.active {
        background-color: #4CAF50;
        color: white;
        border: 1px solid #4CAF50;
    }

    .pagination a:hover:not(.active) {
        background-color: #ddd;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 15px;
        margin: 15px 0;
        padding: 0;
    }

    .stat-box {
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .charts-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin: 15px 0;
        padding: 0;
    }

    .chart-box {
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .main-content {
        padding: 15px;
    }

    .tab-content {
        padding: 0;
    }

    h1,
    h2 {
        margin: 15px 0;
        padding: 0;
    }

    .quick-overview {
        margin: 15px 0;
        padding: 0;
    }

    .flatpickr-input {
        background-color: white;
        border: 1px solid #ddd;
        padding: 8px;
        border-radius: 4px;
        width: 100%;
    }

    .flatpickr-input:focus {
        outline: none;
        border-color: #4e73df;
    }

    .feedback-message {
        padding: 10px 15px;
        margin: 10px 0;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .feedback-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .feedback-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .feedback-close {
        cursor: pointer;
        font-weight: bold;
        font-size: 20px;
    }
    </style>
    <script>
    // Tab switching
    function openTab(evt, tabName) {
        var i,
            tabcontent,
            tablinks;

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

        // Initialize charts
        // Users Distribution Chart
        var usersCtx = document.getElementById('usersChart').getContext('2d');
        new Chart(usersCtx, {
            type: 'pie',
            data: {
                labels: ['Parents', 'Children'],
                datasets: [{
                    data: [<?php echo $parent_count; ?>, <?php echo $child_count; ?>],
                    backgroundColor: ['#4e73df', '#1cc88a']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Users Distribution'
                    }
                }
            }
        });

        // Content Statistics Chart
        var contentCtx = document.getElementById('contentChart').getContext('2d');
        new Chart(contentCtx, {
            type: 'bar',
            data: {
                labels: ['Quizzes', 'Finance Notes', 'Messages'],
                datasets: [{
                    label: 'Content Count',
                    data: [<?php echo $quiz_count; ?>, <?php echo $notes_count; ?>,
                        <?php echo $messages_count; ?>
                    ],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Content Statistics'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

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

    // Initialize Flatpickr
    document.addEventListener('DOMContentLoaded', function() {
        // For add user form
        flatpickr("#dob", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            altInput: true,
            altFormat: "F j, Y",
            allowInput: true
        });

        // For edit user form
        flatpickr("#edit_dob", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            altInput: true,
            altFormat: "F j, Y",
            allowInput: true
        });
    });

    // Function to close feedback messages
    function closeFeedback(element) {
        element.parentElement.remove();
    }

    // Form validation functions
    function validateUserForm(form) {
        const email = form.email.value;
        const password = form.password ? form.password.value : null;
        const firstName = form.first_name.value;
        const lastName = form.last_name.value;
        const dob = form.dob.value;

        if (!email || !firstName || !lastName || !dob) {
            alert('All fields are required');
            return false;
        }

        if (password && password.length < 6) {
            alert('Password must be at least 6 characters long');
            return false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address');
            return false;
        }

        return true;
    }

    // Add form validation to all forms
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (form.classList.contains('user-form')) {
                    if (!validateUserForm(form)) {
                        e.preventDefault();
                    }
                }
                // Add other form validations as needed
            });
        });
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
            <li><a href="#" class="tablink" onclick="openTab(event, 'messages')">Messages</a></li>
            <li><a href="logout.php">Log out</a></li>
        </ul>
    </aside>
    <div class="main-content">
        <?php if (!empty($_SESSION['feedback'])): ?>
        <?php foreach ($_SESSION['feedback'] as $feedback): ?>
        <div class="feedback-message feedback-<?php echo $feedback['type']; ?>">
            <?php echo htmlspecialchars($feedback['message']); ?>
            <span class="feedback-close" onclick="closeFeedback(this)">&times;</span>
        </div>
        <?php endforeach; ?>
        <?php $_SESSION['feedback'] = []; ?>
        <?php endif; ?>
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
                <div class="stat-box">
                    <div>Messages</div>
                    <div class="stat-value"><?php echo $messages_count; ?></div>
                </div>
            </div>

            <div class="charts-container">
                <div class="chart-box">
                    <canvas id="usersChart"></canvas>
                </div>
                <div class="chart-box">
                    <canvas id="contentChart"></canvas>
                </div>
            </div>

            <div class="quick-overview">
                <h2>Quick Overview</h2>
                <p>Welcome to the KidsSaving admin dashboard. Use the navigation menu to manage different sections of
                    the application.</p>
            </div>
        </div>
        <div id="users" class="tab-content">
            <h2>Manage Users</h2>
            <form method="POST" class="user-form" onsubmit="return validateUserForm(this)">
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
                <input type="text" id="dob" name="dob" required>
                <label for="account_type">Account Type:</label>
                <select name="account_type" required>
                    <option value="Child">Child</option>
                    <option value="Parent">Parent</option>
                </select>
                <button type="submit" name="add_user" class="btn-primary">Add User</button>
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
                <tbody><?php while ($user=mysqli_fetch_assoc($users)) { ?><tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['first_name'] . " ". $user['last_name']; ?></td>
                        <td><?php echo $user['dob']; ?></td>
                        <td><?php echo $user['account_type']; ?></td>
                        <td><button class="btn-info"
                                onclick="editUser('<?php echo $user['id']; ?>', '<?php echo $user['email']; ?>', '<?php echo $user['first_name']; ?>', '<?php echo $user['last_name']; ?>', '<?php echo $user['dob']; ?>', '<?php echo $user['account_type']; ?>')">Update</button><button
                                class="btn-danger"
                                onclick="confirmDelete('user', '<?php echo $user['id']; ?>')">Delete</button>
                        </td>
                    </tr><?php } ?></tbody>
            </table>
            <div id="userModal" class="modal">
                <div class="modal-content"><span class="close" onclick="closeModal('userModal')">&times;
                    </span>
                    <form method="POST">
                        <h3>Update User</h3><input type="hidden" id="user_id" name="user_id"><label
                            for="edit_email">Email:</label><input type="email" id="edit_email" name="email"
                            required><label for="edit_first_name">First Name:</label><input type="text"
                            id="edit_first_name" name="first_name" required><label for="edit_last_name">Last
                            Name:</label><input type="text" id="edit_last_name" name="last_name" required><label
                            for="edit_dob">Date of Birth:</label><input type="text" id="edit_dob" name="dob"
                            required><label for="edit_account_type">Account Type:</label><select id="edit_account_type"
                            name="account_type" required>
                            <option value="Child">Child</option>
                            <option value="Parent">Parent</option>
                        </select><button type="submit" name="update_user" class="btn-primary">Update User</button>
                    </form>
                </div>
            </div>
            <?php if ($total_pages_users > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages_users; $i++): ?>
                <a href="?page=<?php echo $i; ?>#users" <?php echo $i == $current_page ? 'class="active"' : ''; ?>>
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <div id="notes" class="tab-content">
            <h2>Manage Finance Notes</h2>
            <form method="POST">
                <h3>Add Finance Note</h3><label for="title">Title:</label><input type="text" name="title"
                    required><label for="content">Content:</label><textarea name="content" required></textarea><button
                    type="submit" name="add_note" class="btn-primary">Add Note</button>
            </form>
            <h3>Existing Finance Notes</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Content Preview</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody><?php while ($note=mysqli_fetch_assoc($notes)) { ?><tr>
                        <td><?php echo $note['id']; ?></td>
                        <td><?php echo $note['title']; ?></td>
                        <td><?php echo substr($note['content'], 0, 50) . '...'; ?></td>
                        <td><?php echo $note['created_at']; ?></td>
                        <td><button class="btn-info"
                                onclick="editNote('<?php echo $note['id']; ?>', '<?php echo addslashes($note['title']); ?>', '<?php echo addslashes($note['content']); ?>')">Update</button><button
                                class="btn-danger"
                                onclick="confirmDelete('note', '<?php echo $note['id']; ?>')">Delete</button>
                        </td>
                    </tr><?php } ?></tbody>
            </table>
            <div id="noteModal" class="modal">
                <div class="modal-content"><span class="close" onclick="closeModal('noteModal')">&times;
                    </span>
                    <form method="POST">
                        <h3>Update Finance Note</h3><input type="hidden" id="note_id" name="note_id"><label
                            for="edit_title">Title:</label><input type="text" id="edit_title" name="title"
                            required><label for="edit_content">Content:</label><textarea id="edit_content"
                            name="content" required></textarea><button type="submit" name="update_note"
                            class="btn-primary">Update Note</button>
                    </form>
                </div>
            </div>
            <?php if ($total_pages_notes > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages_notes; $i++): ?>
                <a href="?page=<?php echo $i; ?>#notes" <?php echo $i == $current_page ? 'class="active"' : ''; ?>>
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <div id="quizzes" class="tab-content">
            <h2>Manage Quizzes</h2>
            <form method="POST">
                <h3>Add Quiz</h3><label for="question">Question:</label><input type="text" name="question"
                    required><label for="option_a">Option A:</label><input type="text" name="option_a" required><label
                    for="option_b">Option
                    B:</label><input type="text" name="option_b" required><label for="option_c">Option C:</label><input
                    type="text" name="option_c" required><label for="correct_option">Correct Option
                    (A/B/C):</label><input type="text" name="correct_option" required><button type="submit"
                    name="add_quiz" class="btn-primary">Add Quiz</button>
            </form>
            <h3>Existing Quizzes</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Question</th>
                        <th>Option A</th>
                        <th>Option B</th>
                        <th>Option C</th>
                        <th>Correct Option</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody><?php while ($quiz=mysqli_fetch_assoc($quizzes)) { ?><tr>
                        <td><?php echo $quiz['id']; ?></td>
                        <td><?php echo $quiz['question']; ?></td>
                        <td><?php echo $quiz['option_a']; ?></td>
                        <td><?php echo $quiz['option_b']; ?></td>
                        <td><?php echo $quiz['option_c']; ?></td>
                        <td><?php echo $quiz['correct_option']; ?></td>
                        <td><button class="btn-info"
                                onclick="editQuiz('<?php echo $quiz['id']; ?>', '<?php echo addslashes($quiz['question']); ?>', '<?php echo addslashes($quiz['option_a']); ?>', '<?php echo addslashes($quiz['option_b']); ?>', '<?php echo addslashes($quiz['option_c']); ?>', '<?php echo $quiz['correct_option']; ?>')">Update</button><button
                                class="btn-danger"
                                onclick="confirmDelete('quiz', '<?php echo $quiz['id']; ?>')">Delete</button>
                        </td>
                    </tr><?php } ?></tbody>
            </table>
            <div id="quizModal" class="modal">
                <div class="modal-content"><span class="close" onclick="closeModal('quizModal')">&times;
                    </span>
                    <form method="POST">
                        <h3>Update Quiz</h3><input type="hidden" id="quiz_id" name="quiz_id"><label
                            for="edit_question">Question:</label><input type="text" id="edit_question" name="question"
                            required><label for="edit_option_a">Option A:</label><input type="text" id="edit_option_a"
                            name="option_a" required><label for="edit_option_b">Option B:</label><input type="text"
                            id="edit_option_b" name="option_b" required><label for="edit_option_c">Option
                            C:</label><input type="text" id="edit_option_c" name="option_c" required><label
                            for="edit_correct_option">Correct Option
                            (A/B/C):</label><input type="text" id="edit_correct_option" name="correct_option"
                            required><button type="submit" name="update_quiz" class="btn-primary">Update Quiz</button>
                    </form>
                </div>
            </div>
            <?php if ($total_pages_quizzes > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages_quizzes; $i++): ?>
                <a href="?page=<?php echo $i; ?>#quizzes" <?php echo $i == $current_page ? 'class="active"' : ''; ?>>
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <div id="store" class="tab-content">
            <h2>Manage Store Items</h2>
            <form method="POST">
                <h3>Add Item</h3><label for="item_name">Item Name:</label><input type="text" name="item_name"
                    required><label for="item_price">Item
                    Price:</label><input type="number" name="item_price" step="0.01" required><button type="submit"
                    name="add_item" class="btn-primary">Add
                    Item</button>
            </form>
            <h3>Existing Store Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Name</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody><?php while ($item=mysqli_fetch_assoc($store_items)) { ?><tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo $item['item_name']; ?></td>
                        <td><?php echo $item['item_price']; ?></td>
                        <td><button class="btn-info"
                                onclick="editItem('<?php echo $item['id']; ?>', '<?php echo $item['item_name']; ?>', '<?php echo $item['item_price']; ?>')">Update</button><button
                                class="btn-danger"
                                onclick="confirmDelete('item', '<?php echo $item['id']; ?>')">Delete</button>
                        </td>
                    </tr><?php } ?></tbody>
            </table>
            <div id="itemModal" class="modal">
                <div class="modal-content"><span class="close" onclick="closeModal('itemModal')">&times;
                    </span>
                    <form method="POST">
                        <h3>Update Item</h3><input type="hidden" id="item_id" name="item_id"><label
                            for="edit_item_name">Item
                            Name:</label><input type="text" id="edit_item_name" name="item_name" required><label
                            for="edit_item_price">Item Price:</label><input type="number" id="edit_item_price"
                            name="item_price" step="0.01" required><button type="submit" name="update_item"
                            class="btn-primary">Update
                            Item</button>
                    </form>
                </div>
            </div>
            <?php if ($total_pages_store > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages_store; $i++): ?>
                <a href="?page=<?php echo $i; ?>#store" <?php echo $i == $current_page ? 'class="active"' : ''; ?>>
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <div id="messages" class="tab-content">
            <h2>Manage Messages</h2>
            <h3>Messages</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Email</th>
                        <th>Message</th>
                        <th>Submitted At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($message = mysqli_fetch_assoc($messages)) {
                    ?>
                    <tr>
                        <td><?php echo $message['id']; ?></td>
                        <td><?php echo $message['user_email']; ?></td>
                        <td><?php echo $message['message']; ?></td>
                        <td><?php echo $message['submitted_at']; ?></td>
                        <td>
                            <button class="btn-danger"
                                onclick="confirmDelete('message', '<?php echo $message['id']; ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <form id="deleteForm" method="POST" style="display: none;">
            <input type="hidden" id="delete_id" name="delete_id">
            <input type="hidden" id="delete_type" name="delete_type">
        </form>
    </div>

    <!-- Add a loading overlay -->
    <div id="loadingOverlay"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white;">
            Processing...
        </div>
    </div>
</body>

</html>