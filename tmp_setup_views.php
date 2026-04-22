<?php
require_once 'db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS daily_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        apartment_id INT NOT NULL,
        user_id INT NOT NULL,
        view_date DATE NOT NULL,
        views INT DEFAULT 1,
        UNIQUE KEY unique_daily_view (apartment_id, view_date),
        FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Successfully created daily_views table.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
