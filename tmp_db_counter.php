<?php
require_once 'db.php';

try {
    // 1. Update ENUM to include 'countered'
    $pdo->exec("ALTER TABLE bids MODIFY COLUMN status ENUM('pending','accepted','rejected','countered') DEFAULT 'pending'");
    echo "Updated status ENUM.\n";

    // 2. Add counter_amount column if not exists
    $check = $pdo->query("SHOW COLUMNS FROM bids LIKE 'counter_amount'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE bids ADD COLUMN counter_amount INT DEFAULT NULL AFTER amount");
        echo "Added counter_amount column.\n";
    }

    // 3. Add counter_message column if not exists (optional, but good for context)
    $check2 = $pdo->query("SHOW COLUMNS FROM bids LIKE 'counter_message'");
    if ($check2->rowCount() == 0) {
        $pdo->exec("ALTER TABLE bids ADD COLUMN counter_message TEXT DEFAULT NULL AFTER counter_amount");
        echo "Added counter_message column.\n";
    }

    echo "Database updated for counter offers successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
