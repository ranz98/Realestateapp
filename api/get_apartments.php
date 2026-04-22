<?php
// Buffer all output so stray PHP warnings/notices never corrupt the JSON response.
ob_start();

// Close any auto-started session immediately so this request is never
// blocked by a session file lock held by the parent page load.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
require_once '../db.php';

$query = "SELECT * FROM apartments WHERE status = 'approved'";
$params = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = '%' . trim($_GET['search']) . '%';
    $query .= " AND (title LIKE ? OR description LIKE ? OR address LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

if (isset($_GET['type']) && $_GET['type'] !== 'All') {
    $query .= " AND type = ?";
    $params[] = $_GET['type'];
}

if (isset($_GET['location']) && $_GET['location'] !== 'All') {
    $query .= " AND address LIKE ?";
    $params[] = '%' . trim($_GET['location']) . '%';
}

if (isset($_GET['beds']) && $_GET['beds'] !== 'All') {
    if ($_GET['beds'] === '3+') {
        $query .= " AND (bedrooms = '3' OR bedrooms = '3+')";
    } else {
        $query .= " AND bedrooms = ?";
        $params[] = $_GET['beds'];
    }
}

if (isset($_GET['baths']) && $_GET['baths'] !== 'All') {
    if ($_GET['baths'] === '3+') {
        $query .= " AND baths >= 3";
    } else {
        $query .= " AND baths = ?";
        $params[] = $_GET['baths'];
    }
}

if (isset($_GET['min_price']) && is_numeric($_GET['min_price']) && $_GET['min_price'] > 0) {
    $query .= " AND price >= ?";
    $params[] = $_GET['min_price'];
}

if (isset($_GET['max_price']) && is_numeric($_GET['max_price']) && $_GET['max_price'] > 0) {
    $query .= " AND price <= ?";
    $params[] = $_GET['max_price'];
}

if (isset($_GET['listing_mode']) && in_array($_GET['listing_mode'], ['Buy', 'Rent'])) {
    $query .= " AND listing_mode = ?";
    $params[] = $_GET['listing_mode'];
}

if (isset($_GET['sort'])) {
    if ($_GET['sort'] === 'price_low') {
        $query .= " ORDER BY price ASC";
    } else if ($_GET['sort'] === 'price_high') {
        $query .= " ORDER BY price DESC";
    } else if ($_GET['sort'] === 'oldest') {
        $query .= " ORDER BY created_at ASC";
    } else if ($_GET['sort'] === 'trending') {
        $query .= " ORDER BY RAND()"; // Mock trending for now
    } else {
        $query .= " ORDER BY created_at DESC";
    }
} else {
    $query .= " ORDER BY created_at DESC";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean(); // discard any stray warnings before sending JSON
    header('Content-Type: application/json');
    echo json_encode($apartments);
} catch (PDOException $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage(), 'hint' => 'Did you run the migration script on the live server?']);
}
?>
