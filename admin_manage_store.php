<?php
// Include your database connection
include('db.php');

// Fetch all store items from the database
$query = "SELECT * FROM store_items";
$result = mysqli_query($conn, $query);

// Handle Add, Update, and Delete Operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_item'])) {
        // Insert new store item
        $item_name = $_POST['item_name'];
        $item_price = $_POST['item_price'];
        $item_image = 'uploads/default_item.png'; // Placeholder image

        $insert_query = "INSERT INTO store_items (item_name, item_price, item_image) 
                         VALUES ('$item_name', '$item_price', '$item_image')";
        mysqli_query($conn, $insert_query);
        header("Location: admin_manage_store.php"); // Refresh page
        exit;
    } elseif (isset($_POST['update_item'])) {
        // Update existing store item
        $id = $_POST['item_id'];
        $item_name = $_POST['item_name'];
        $item_price = $_POST['item_price'];

        $update_query = "UPDATE store_items SET item_name='$item_name', item_price='$item_price' WHERE id=$id";
        mysqli_query($conn, $update_query);
        header("Location: admin_manage_store.php"); // Refresh page
        exit;
    } elseif (isset($_POST['delete_item'])) {
        // Delete store item
        $id = $_POST['item_id'];
        $delete_query = "DELETE FROM store_items WHERE id=$id";
        mysqli_query($conn, $delete_query);
        header("Location: admin_manage_store.php"); // Refresh page
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving - Manage Store</title>
    <link rel="stylesheet" href="admin_manage_store.css">
    <script>
    function editItem(id, name, price) {
        document.getElementById("item_id").value = id;
        document.getElementById("item_name").value = name;
        document.getElementById("item_price").value = price;
        document.getElementById("editModal").style.display = "block";
    }

    function closeEditModal() {
        document.getElementById("editModal").style.display = "none";
    }

    function confirmDelete(itemId) {
        if (confirm("Are you sure you want to delete this item?")) {
            document.getElementById("delete_item_id").value = itemId;
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
            <li><a href="admin_manage_store.php">Manage Store</a></li>
            <li><a href="logout.php">Log out</a></li>
        </ul>
    </aside>
    <main>
        <h2>Manage Store Items</h2>

        <!-- Add Item Form -->
        <form action="admin_manage_store.php" method="POST">
            <h3>Add Item</h3>
            <label for="item_name">Item Name:</label><input type="text" name="item_name" required><br>
            <label for="item_price">Item Price:</label><input type="number" name="item_price" step="0.01" required><br>
            <button type="submit" name="add_item">Add Item</button>
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
            <tbody>
                <?php while ($item = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><?php echo $item['item_name']; ?></td>
                    <td><?php echo $item['item_price']; ?></td>
                    <td>
                        <button
                            onclick="editItem('<?php echo $item['id']; ?>', '<?php echo $item['item_name']; ?>', '<?php echo $item['item_price']; ?>')">Update</button>
                        <button onclick="confirmDelete('<?php echo $item['id']; ?>')">Delete</button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Update Item Modal -->
        <div id="editModal" style="display:none;">
            <form action="admin_manage_store.php" method="POST">
                <h3>Update Item</h3>
                <input type="hidden" id="item_id" name="item_id">
                <label for="item_name">Item Name:</label><input type="text" id="item_name" name="item_name"
                    required><br>
                <label for="item_price">Item Price:</label><input type="number" id="item_price" name="item_price"
                    step="0.01" required><br>
                <button type="submit" name="update_item">Update Item</button>
                <button type="button" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>

        <form id="deleteForm" action="admin_manage_store.php" method="POST" style="display:none;">
            <input type="hidden" id="delete_item_id" name="item_id">
            <input type="hidden" name="delete_item">
        </form>
    </main>
</body>

</html>