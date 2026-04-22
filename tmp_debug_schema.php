<?php
require_once 'db.php';

try {
    // Check bids table
    $stmt = $pdo->query("DESCRIBE bids");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $col_names = array_column($cols, 'Field');
    
    echo "Bids columns: " . implode(', ', $col_names) . "<br>";
    
    if (!in_array('status', $col_names)) {
        $pdo->exec("ALTER TABLE bids ADD COLUMN status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending'");
        echo "Added 'status' column to bids.<br>";
    } else {
        echo "'status' column already exists in bids.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
