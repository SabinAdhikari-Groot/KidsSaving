-- Create store items table
CREATE TABLE IF NOT EXISTS store_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,
    item_image VARCHAR(255) NOT NULL,
    item_description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create user earnings table
CREATE TABLE IF NOT EXISTS user_earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_earnings DECIMAL(10,2) DEFAULT 0.00,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create user inventory table
CREATE TABLE IF NOT EXISTS user_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (item_id) REFERENCES store_items(id)
);

-- Insert sample store items
INSERT INTO store_items (item_name, item_price, item_image, item_description) VALUES
('Virtual Pet', 50.00, 'images/virtual_pet.png', 'A cute virtual pet to keep you company'),
('Game Console', 100.00, 'images/game_console.png', 'Play exciting virtual games'),
('Magic Wand', 75.00, 'images/magic_wand.png', 'Cast fun spells in your virtual world'),
('Treasure Chest', 150.00, 'images/treasure_chest.png', 'Store your valuable virtual items'),
('Flying Carpet', 200.00, 'images/flying_carpet.png', 'Soar through the virtual skies');

-- Add indexes for better performance
ALTER TABLE user_inventory ADD INDEX idx_user_id (user_id);
ALTER TABLE user_inventory ADD INDEX idx_item_id (item_id);
ALTER TABLE user_earnings ADD INDEX idx_user_id (user_id); 