<?php
session_start();
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kids_saving";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user has already played today
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$check_attempt = "SELECT * FROM quiz_attempts WHERE user_id = ? AND attempt_date = ?";
$stmt = $conn->prepare($check_attempt);
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<script>alert('You have already earned points from the quiz today. Come back tomorrow!');
          window.location.href = 'children_dashboard.php';</script>";
    exit();
}

// Fetch 10 random quiz questions
$sql = "SELECT * FROM quizzes ORDER BY RAND() LIMIT 10";
$result = $conn->query($sql);

$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSaving Quiz</title>
    <link rel="stylesheet" href="quizzes.css">
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
            <h1>🎮 Financial Quiz</h1>
            <p>Test your knowledge of finance concepts!</p>

            <div id="quiz-container">
                <h2 id="question-text"></h2>
                <form id="quiz-form">
                    <div class="quiz-option">
                        <input type="radio" id="option_a" name="answer" value="A">
                        <label for="option_a" id="label_a"></label>
                    </div>
                    <div class="quiz-option">
                        <input type="radio" id="option_b" name="answer" value="B">
                        <label for="option_b" id="label_b"></label>
                    </div>
                    <div class="quiz-option">
                        <input type="radio" id="option_c" name="answer" value="C">
                        <label for="option_c" id="label_c"></label>
                    </div>
                    <button type="button" class="next-btn" onclick="nextQuestion()">Next</button>
                </form>
                <p id="score-text"></p>
                <button class="back-btn" onclick="goBack()" style="display:none;">🏠 Back to
                    Dashboard</button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 KidsSaving. Learn, Save, and Have Fun!</p>
    </footer>

    <script>
    let questions = <?php echo json_encode($questions); ?>;
    let currentQuestionIndex = 0;
    let score = 0;

    function loadQuestion() {
        if (currentQuestionIndex >= questions.length) {
            // Calculate points based on score (e.g., 10 points per correct answer)
            let earnedPoints = score * 10;
            
            // Send score to server
            fetch('save_quiz_result.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    score: score,
                    points: earnedPoints
                })
            });

            document.getElementById("quiz-container").innerHTML = `
                    <h2>Quiz Completed!</h2>
                    <p>Your Score: ${score} / ${questions.length}</p>
                    <p>Points Earned: ${earnedPoints}</p>
                    <button class="back-btn" onclick="goBack()">🏠 Back to Dashboard</button>
                `;
            return;
        }

        let question = questions[currentQuestionIndex];
        document.getElementById("question-text").innerText = question.question;
        document.getElementById("label_a").innerText = question.option_a;
        document.getElementById("label_b").innerText = question.option_b;
        document.getElementById("label_c").innerText = question.option_c;
    }

    function nextQuestion() {
        let selectedOption = document.querySelector('input[name="answer"]:checked');
        if (!selectedOption) {
            alert("Please select an answer!");
            return;
        }

        if (selectedOption.value === questions[currentQuestionIndex].correct_option) {
            score++;
        }

        currentQuestionIndex++;
        document.getElementById("score-text").innerText = `Score: ${score} / 10`;
        selectedOption.checked = false;
        loadQuestion();
    }

    function goBack() {
        window.location.href = "children_dashboard.php";
    }

    loadQuestion();
    </script>
</body>

</html>