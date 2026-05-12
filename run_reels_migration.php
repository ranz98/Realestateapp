<?php
require_once 'db.php';

echo "<h2>Reels Migration</h2>";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS reels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        caption TEXT NULL,
        video_url VARCHAR(500) NOT NULL,
        poster_url VARCHAR(500) NULL,
        apartment_id INT NULL,
        location VARCHAR(120) NULL,
        price DECIMAL(15,2) NULL,
        listing_mode ENUM('Buy','Rent') DEFAULT 'Rent',
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Created reels table.<br>";

    // Seed with demo videos if empty (uses public sample mp4s so it works out of the box)
    $count = (int)$pdo->query("SELECT COUNT(*) FROM reels")->fetchColumn();
    if ($count === 0) {
        $seed = [
            [
                'title' => 'Modern Beach Villa',
                'caption' => 'Stunning ocean view, fully furnished',
                'video_url' => 'https://cdn.pixabay.com/video/2022/10/26/136796-764787329_large.mp4',
                'poster_url' => null,
                'location' => 'Galle',
                'price' => 28500000,
                'listing_mode' => 'Buy'
            ],
            [
                'title' => 'City Apartment Tour',
                'caption' => '2 BHK in the heart of Colombo',
                'video_url' => 'https://cdn.pixabay.com/video/2019/11/21/29143-373809500_large.mp4',
                'poster_url' => null,
                'location' => 'Colombo',
                'price' => 95000,
                'listing_mode' => 'Rent'
            ],
            [
                'title' => 'Hilltop Retreat',
                'caption' => 'Wake up to misty mountain mornings',
                'video_url' => 'https://cdn.pixabay.com/video/2020/03/16/33530-399826941_large.mp4',
                'poster_url' => null,
                'location' => 'Kandy',
                'price' => 18000000,
                'listing_mode' => 'Buy'
            ],
        ];
        $stmt = $pdo->prepare("INSERT INTO reels (title, caption, video_url, poster_url, location, price, listing_mode, sort_order) VALUES (?,?,?,?,?,?,?,?)");
        $i = 0;
        foreach ($seed as $r) {
            $stmt->execute([$r['title'], $r['caption'], $r['video_url'], $r['poster_url'], $r['location'], $r['price'], $r['listing_mode'], $i++]);
        }
        echo "Seeded " . count($seed) . " demo reels.<br>";
    } else {
        echo "Reels table already has $count rows. Skipped seed.<br>";
    }

    // Ensure uploads folder exists
    $up = __DIR__ . '/uploads/reels';
    if (!is_dir($up)) {
        mkdir($up, 0775, true);
        echo "Created uploads/reels folder.<br>";
    }

    echo "<h3 style='color:green;'>Done.</h3>";
    echo "<p>You can now upload reels at <a href='admin-reels.php'>admin-reels.php</a> (admin login required).</p>";
} catch (Exception $e) {
    echo "<h3 style='color:red;'>Failed: " . htmlspecialchars($e->getMessage()) . "</h3>";
}
