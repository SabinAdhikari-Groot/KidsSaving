<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving Home</title>
    <link rel="stylesheet" href="children_dashboard.css">
    <script>
    function toggleContent(noteId) {
        var contentDiv = document.getElementById("content_" + noteId);
        contentDiv.style.display = (contentDiv.style.display === "none" || contentDiv.style.display === "") ? "block" :
            "none";
    }
    </script>
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
            <h1>Welcome to KidsSaving!</h1>
            <p>Manage your tasks, earnings, savings, and more in a fun way.</p>

            <div class="content">
                <h2>📚 Financial Notes</h2>
                <p>Learn important financial concepts every day!</p>
                <ul>
                    <?php 
                    // Database connection 
                    $servername="localhost" ; $username="root" ; $password="" ;
                        $dbname="kids_saving" ; 
                        // Create connection
                        $conn=new mysqli($servername, $username, $password, $dbname);
                        // Check connection
                        if ($conn->connect_error) {
                            die("<li>Connection failed: " . $conn->connect_error . "</li>");
                        }

                        // Fetch 5 random finance notes from the database
                        $sql = "SELECT id, title, content FROM finance_notes ORDER BY RAND() LIMIT 5";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                        // Output data of each row
                        while ($row = $result->fetch_assoc()) {
                        echo "<li>";
                        echo "<h3 style='color:blue;'>" . htmlspecialchars($row['title']) . "</h3>";
                        echo "<div id='content_" . $row['id']
                        . "' style='display:block; padding-left:10px; margin-top:5px; background:#f9f9f9; border-left:3px solidrgb(2, 2, 2);'>";
                        echo "<p>" . nl2br(htmlspecialchars($row['content'])) . "</p>";
                        echo "</div>";
                        echo "</li>";
                        } } else { 
                        echo "<li>No financial notes available.</li>"; 
                        }

                        // Close connection
                        $conn->close();

                        ?>
                </ul>
            </div>

            <h2>🎮 Fun Quizzes</h2>
            <p>Test your knowledge with interactive quizzes!</p>
            <button onclick="location.href='quizzes.php'">Take a Quiz</button>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>
</body>

</html>