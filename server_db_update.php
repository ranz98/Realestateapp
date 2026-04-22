<?php
require_once 'db.php'; // Ensure this points to your production DB credentials

echo "<h2>Production Database Synchronizer</h2>";
echo "Starting schema updates...<br><br>";

try {
    // 1. UPDATE ACCOUNTS TABLE (users)
    $columnsToAddUsers = [
        'phone' => "VARCHAR(20) DEFAULT NULL",
        'bio' => "TEXT DEFAULT NULL",
        'profile_pic' => "VARCHAR(255) DEFAULT NULL",
        'address' => "VARCHAR(255) DEFAULT NULL"
    ];

    foreach ($columnsToAddUsers as $col => $type) {
        $check = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
        $check->execute([$col]);
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $type");
            echo "<span style='color:green'>&#10003; Added column '$col' to 'users' table.</span><br>";
        } else {
            echo "<span style='color:gray'>- Column '$col' already exists in 'users' table.</span><br>";
        }
    }

    // 2. UPDATE LISTINGS TABLE (apartments)
    $columnsToAddApts = [
        'listing_mode' => "VARCHAR(50) NOT NULL DEFAULT 'Rent'",
        'view_count' => "INT DEFAULT 0"
    ];

    foreach ($columnsToAddApts as $col => $type) {
        $check = $pdo->prepare("SHOW COLUMNS FROM apartments LIKE ?");
        $check->execute([$col]);
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE apartments ADD COLUMN $col $type");
            echo "<span style='color:green'>&#10003; Added column '$col' to 'apartments' table.</span><br>";
        } else {
            echo "<span style='color:gray'>- Column '$col' already exists in 'apartments' table.</span><br>";
        }
    }

    // Update existing null/empty listing_mode rows safely
    $pdo->exec("UPDATE apartments SET listing_mode = 'Rent' WHERE listing_mode IS NULL OR listing_mode = ''");
    echo "<span style='color:blue'>&#10003; Standardized existing listing modes to 'Rent'.</span><br>";

    // 3. UPDATE OFFERS TABLE (bids)
    $checkStatus = $pdo->prepare("SHOW COLUMNS FROM bids LIKE 'status'");
    $checkStatus->execute();
    $statusCol = $checkStatus->fetch(PDO::FETCH_ASSOC);
    
    // Update ENUM if 'countered' is missing
    if ($statusCol && strpos($statusCol['Type'], 'countered') === false) {
        $pdo->exec("ALTER TABLE bids MODIFY COLUMN status ENUM('pending','accepted','rejected','countered') DEFAULT 'pending'");
        echo "<span style='color:green'>&#10003; Updated 'status' ENUM in 'bids' table to include 'countered'.</span><br>";
    } else {
        echo "<span style='color:gray'>- 'status' ENUM already up to date in 'bids'.</span><br>";
    }

    $columnsToAddBids = [
        'counter_amount' => "INT DEFAULT NULL",
        'counter_message' => "TEXT DEFAULT NULL"
    ];

    foreach ($columnsToAddBids as $col => $type) {
        $check = $pdo->prepare("SHOW COLUMNS FROM bids LIKE ?");
        $check->execute([$col]);
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE bids ADD COLUMN $col $type");
            echo "<span style='color:green'>&#10003; Added column '$col' to 'bids' table.</span><br>";
        } else {
            echo "<span style='color:gray'>- Column '$col' already exists in 'bids' table.</span><br>";
        }
    }

    // 4. CREATE REAL-TIME ANALYTICS TABLE (daily_views)
    $dailyViewsSql = "CREATE TABLE IF NOT EXISTS daily_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        apartment_id INT NOT NULL,
        user_id INT NOT NULL,
        view_date DATE NOT NULL,
        views INT DEFAULT 1,
        UNIQUE KEY unique_daily_view (apartment_id, view_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($dailyViewsSql);
    echo "<span style='color:green'>&#10003; Ensured 'daily_views' table exists.</span><br>";

    // Done
    echo "<br><strong><span style='color:green'>All database schema changes have been synchronized successfully!</span></strong>";
    echo "<br><br><small>Note: For security reasons, please <strong>delete</strong> this file from your production server after it has been executed.</small>";

} catch (PDOException $e) {
    echo "<br><strong style='color:red'>Database Error occurred: </strong>" . htmlspecialchars($e->getMessage());
}
?>
