<?php
require_once 'db.php';

try {
    // 1. Create Bids Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS bids (
        id INT AUTO_INCREMENT PRIMARY KEY,
        apartment_id INT NOT NULL,
        user_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        message TEXT,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Bids table created/verified.<br>";

    // 2. Check and fix apartments table columns
    $cols_to_add = [
        "listing_mode ENUM('Rent', 'Buy') DEFAULT 'Rent'",
        "seller_email VARCHAR(255)",
        "seller_phone VARCHAR(50)",
        "size_perches DECIMAL(10,2)"
    ];

    $existing_cols = $pdo->query("DESCRIBE apartments")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($cols_to_add as $col_def) {
        $col_name = explode(' ', trim($col_def))[0];
        if (!in_array($col_name, $existing_cols)) {
            $pdo->exec("ALTER TABLE apartments ADD COLUMN $col_def");
            echo "Added column: $col_name to apartments.<br>";
        }
    }
    $pdo->exec("UPDATE apartments SET listing_mode = 'Rent' WHERE listing_mode IS NULL OR listing_mode = ''");
    echo "Apartments table columns verified and listing_mode backfilled.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
