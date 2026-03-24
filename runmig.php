<?php
require_once 'db.php';

echo "<h2>Starting Database Migration...</h2>";

try {
    // 1. Add listing_mode to apartments
    try {
        $pdo->exec("ALTER TABLE apartments ADD COLUMN listing_mode ENUM('Buy','Rent') DEFAULT 'Rent'");
        echo "✅ Added 'listing_mode' column to apartments table.<br>";
    } catch (PDOException $e) {
        echo "⚠️ Column 'listing_mode' might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 2. Add seller_email to apartments
    try {
        $pdo->exec("ALTER TABLE apartments ADD COLUMN seller_email VARCHAR(150) NULL");
        echo "✅ Added 'seller_email' column to apartments table.<br>";
    } catch (PDOException $e) {
        echo "⚠️ Column 'seller_email' might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 3. Add seller_phone to apartments
    try {
        $pdo->exec("ALTER TABLE apartments ADD COLUMN seller_phone VARCHAR(30) NULL");
        echo "✅ Added 'seller_phone' column to apartments table.<br>";
    } catch (PDOException $e) {
        echo "⚠️ Column 'seller_phone' might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 4. Add size_perches to apartments
    try {
        $pdo->exec("ALTER TABLE apartments ADD COLUMN size_perches DECIMAL(10,2) NULL");
        echo "✅ Added 'size_perches' column to apartments table.<br>";
    } catch (PDOException $e) {
        echo "⚠️ Column 'size_perches' might already exist or error: " . $e->getMessage() . "<br>";
    }

    // 5. Create bids table
    $createBidsTable = "
    CREATE TABLE IF NOT EXISTS bids (
        id INT AUTO_INCREMENT PRIMARY KEY,
        apartment_id INT NOT NULL,
        user_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        message VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($createBidsTable);
    echo "✅ Created 'bids' table.<br>";

    echo "<br><h3 style='color: green;'>Migration completed successfully!</h3>";
    echo "<p>Your missing columns and tables have been added to the database.</p>";
    echo "<p>Please ensure you delete this file from your live server after checking the application works, for security purposes.</p>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>Migration failed: " . $e->getMessage() . "</h3>";
}
?>
