<?php
require_once 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS user_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (item_id) REFERENCES store_items(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "User inventory table created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 