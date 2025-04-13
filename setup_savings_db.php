<?php
require_once 'db.php';

// Read the SQL file
$sql = file_get_contents('savings_tables.sql');

// Split SQL file into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)), 'strlen');

// Execute each statement
$success = true;
$errors = [];

foreach ($statements as $statement) {
    if (trim($statement) != '') {
        try {
            if (!$conn->query($statement)) {
                $success = false;
                $errors[] = "Error executing statement: " . $conn->error;
            }
        } catch (Exception $e) {
            $success = false;
            $errors[] = "Exception: " . $e->getMessage();
        }
    }
}

// Output results
if ($success) {
    echo "Database setup completed successfully!<br>";
    echo "The following tables were created:<br>";
    echo "- savings_goals<br>";
    echo "- earnings<br>";
    echo "- user_earnings<br>";
    echo "<br>Indexes and triggers were also created.";
} else {
    echo "Error setting up database:<br>";
    foreach ($errors as $error) {
        echo "- " . htmlspecialchars($error) . "<br>";
    }
}

$conn->close();
?> 