<?php
// Include your database connection
include('db.php');

// Fetch all finance notes from the database
$query = "SELECT * FROM finance_notes ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Handle Add, Update, and Delete Operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_note'])) {
        // Insert new note
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);

        $insert_query = "INSERT INTO finance_notes (title, content, created_at) 
                         VALUES ('$title', '$content', NOW())";
        mysqli_query($conn, $insert_query);
        header("Location: admin_managing_finance_notes.php");
        exit;
    } elseif (isset($_POST['update_note'])) {
        // Update existing note
        $id = $_POST['note_id'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);

        $update_query = "UPDATE finance_notes SET title='$title', content='$content' WHERE id=$id";
        mysqli_query($conn, $update_query);
        header("Location: admin_managing_finance_notes.php");
        exit;
    } elseif (isset($_POST['delete_note'])) {
        // Delete note
        $id = $_POST['note_id'];
        $delete_query = "DELETE FROM finance_notes WHERE id=$id";
        mysqli_query($conn, $delete_query);
        header("Location: admin_managing_finance_notes.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving - Manage Finance Notes</title>
    <link rel="stylesheet" href="admin_adding_notes.css">
    <script>
    function editNote(id, title, content) {
        document.getElementById("note_id").value = id;
        document.getElementById("title").value = title;
        document.getElementById("content").value = content;
        document.getElementById("editModal").style.display = "block";
    }

    function closeEditModal() {
        document.getElementById("editModal").style.display = "none";
    }

    function confirmDelete(noteId) {
        if (confirm("Are you sure you want to delete this note?")) {
            document.getElementById("delete_note_id").value = noteId;
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
        <h2>Manage Finance Notes</h2>

        <!-- Add Finance Note Form -->
        <form action="admin_managing_finance_notes.php" method="POST">
            <h3>Add Finance Note</h3>
            <label for="title">Title:</label><input type="text" name="title" required><br>
            <label for="content">Content:</label><textarea name="content" required></textarea><br>
            <button type="submit" name="add_note">Add Note</button>
        </form>

        <!-- Update Note Modal -->
        <div id="editModal" style="display:none;">
            <form action="admin_managing_finance_notes.php" method="POST">
                <h3>Update Finance Note</h3>
                <input type="hidden" id="note_id" name="note_id">
                <label for="title">Title:</label><input type="text" id="title" name="title" required><br>
                <label for="content">Content:</label><textarea id="content" name="content" required></textarea><br>
                <button type="submit" name="update_note">Update Note</button>
                <button type="button" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>

        <h3>Existing Finance Notes</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Content</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($note = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $note['id']; ?></td>
                    <td><?php echo $note['title']; ?></td>
                    <td><?php echo substr($note['content'], 0, 50) . '...'; ?></td>
                    <td><?php echo $note['created_at']; ?></td>
                    <td>
                        <button
                            onclick="editNote('<?php echo $note['id']; ?>', '<?php echo addslashes($note['title']); ?>', '<?php echo addslashes($note['content']); ?>')">Update</button>
                        <button onclick="confirmDelete('<?php echo $note['id']; ?>')">Delete</button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Delete Form -->
        <form id="deleteForm" action="admin_managing_finance_notes.php" method="POST" style="display:none;">
            <input type="hidden" id="delete_note_id" name="note_id">
            <input type="hidden" name="delete_note">
        </form>
    </main>
</body>

</html>