<?php
// Include database connection
include('db.php');

// Fetch all quizzes from the database
$query = "SELECT * FROM quizzes ORDER BY id DESC";
$result = mysqli_query($conn, $query);

// Handle Add, Update, and Delete Operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_quiz'])) {
        // Insert new quiz
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $option_a = mysqli_real_escape_string($conn, $_POST['option_a']);
        $option_b = mysqli_real_escape_string($conn, $_POST['option_b']);
        $option_c = mysqli_real_escape_string($conn, $_POST['option_c']);
        $correct_option = mysqli_real_escape_string($conn, $_POST['correct_option']);

        $insert_query = "INSERT INTO quizzes (question, option_a, option_b, option_c, correct_option) 
                         VALUES ('$question', '$option_a', '$option_b', '$option_c', '$correct_option')";
        mysqli_query($conn, $insert_query);
        header("Location: admin_managing_quizzes.php");
        exit;
    } elseif (isset($_POST['update_quiz'])) {
        // Update existing quiz
        $id = $_POST['quiz_id'];
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $option_a = mysqli_real_escape_string($conn, $_POST['option_a']);
        $option_b = mysqli_real_escape_string($conn, $_POST['option_b']);
        $option_c = mysqli_real_escape_string($conn, $_POST['option_c']);
        $correct_option = mysqli_real_escape_string($conn, $_POST['correct_option']);

        $update_query = "UPDATE quizzes SET question='$question', option_a='$option_a', option_b='$option_b', option_c='$option_c', correct_option='$correct_option' WHERE id=$id";
        mysqli_query($conn, $update_query);
        header("Location: admin_managing_quizzes.php");
        exit;
    } elseif (isset($_POST['delete_quiz'])) {
        // Delete quiz
        $id = $_POST['quiz_id'];
        $delete_query = "DELETE FROM quizzes WHERE id=$id";
        mysqli_query($conn, $delete_query);
        header("Location: admin_managing_quizzes.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving - Manage Quizzes</title>
    <link rel="stylesheet" href="admin_managing_quizzes.css">
    <script>
    function editQuiz(id, question, option_a, option_b, option_c, correct_option) {
        document.getElementById("quiz_id").value = id;
        document.getElementById("question").value = question;
        document.getElementById("option_a").value = option_a;
        document.getElementById("option_b").value = option_b;
        document.getElementById("option_c").value = option_c;
        document.getElementById("correct_option").value = correct_option;
        document.getElementById("editModal").style.display = "block";
    }

    function closeEditModal() {
        document.getElementById("editModal").style.display = "none";
    }

    function confirmDelete(quizId) {
        if (confirm("Are you sure you want to delete this quiz?")) {
            document.getElementById("delete_quiz_id").value = quizId;
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
        <h2>Manage Quizzes</h2>

        <!-- Add Quiz Form -->
        <form action="admin_managing_quizzes.php" method="POST">
            <h3>Add Quiz</h3>
            <label for="question">Question:</label><input type="text" name="question" required><br>
            <label for="option_a">Option A:</label><input type="text" name="option_a" required><br>
            <label for="option_b">Option B:</label><input type="text" name="option_b" required><br>
            <label for="option_c">Option C:</label><input type="text" name="option_c" required><br>
            <label for="correct_option">Correct Option (A/B/C):</label><input type="text" name="correct_option"
                required><br>
            <button type="submit" name="add_quiz">Add Quiz</button>
        </form>

        <!-- Update Quiz Modal -->
        <div id="editModal" style="display:none;">
            <form action="admin_managing_quizzes.php" method="POST">
                <h3>Update Quiz</h3>
                <input type="hidden" id="quiz_id" name="quiz_id">
                <label for="question">Question:</label><input type="text" id="question" name="question" required><br>
                <label for="option_a">Option A:</label><input type="text" id="option_a" name="option_a" required><br>
                <label for="option_b">Option B:</label><input type="text" id="option_b" name="option_b" required><br>
                <label for="option_c">Option C:</label><input type="text" id="option_c" name="option_c" required><br>
                <label for="correct_option">Correct Option (A/B/C):</label><input type="text" id="correct_option"
                    name="correct_option" required><br>
                <button type="submit" name="update_quiz">Update Quiz</button>
                <button type="button" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>

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
            <tbody>
                <?php while ($quiz = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $quiz['id']; ?></td>
                    <td><?php echo $quiz['question']; ?></td>
                    <td><?php echo $quiz['option_a']; ?></td>
                    <td><?php echo $quiz['option_b']; ?></td>
                    <td><?php echo $quiz['option_c']; ?></td>
                    <td><?php echo $quiz['correct_option']; ?></td>
                    <td>
                        <button
                            onclick="editQuiz('<?php echo $quiz['id']; ?>', '<?php echo addslashes($quiz['question']); ?>', '<?php echo addslashes($quiz['option_a']); ?>', '<?php echo addslashes($quiz['option_b']); ?>', '<?php echo addslashes($quiz['option_c']); ?>', '<?php echo $quiz['correct_option']; ?>')">Update</button>
                        <button onclick="confirmDelete('<?php echo $quiz['id']; ?>')">Delete</button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </main>
</body>

</html>