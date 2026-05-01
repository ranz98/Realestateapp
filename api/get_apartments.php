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

// --- Total count BEFORE bbox/limit (so frontend can decide if lazy mode is needed) ---
// We splice the SELECT * into SELECT COUNT(*) and run with the current params (no bbox / limit yet).
$countQuery = 'SELECT COUNT(*) ' . substr($query, strlen('SELECT *'));
$totalCount = null;
try {
    $cstmt = $pdo->prepare($countQuery);
    $cstmt->execute($params);
    $totalCount = (int)$cstmt->fetchColumn();
    header('X-Total-Count: ' . $totalCount);
    header('Access-Control-Expose-Headers: X-Total-Count');
} catch (PDOException $e) { /* non-fatal — continue */ }

// --- Bbox filtering for lazy map loading ---
// Format: bbox=minLng,minLat,maxLng,maxLat
if (isset($_GET['bbox']) && !empty($_GET['bbox'])) {
    $parts = explode(',', $_GET['bbox']);
    if (count($parts) === 4 && array_filter($parts, 'is_numeric') === $parts) {
        $minLng = (float)$parts[0]; $minLat = (float)$parts[1];
        $maxLng = (float)$parts[2]; $maxLat = (float)$parts[3];
        // Handle antimeridian crossing (minLng > maxLng means we wrap around)
        if ($minLng <= $maxLng) {
            $query .= " AND lng BETWEEN ? AND ? AND lat BETWEEN ? AND ?";
            $params[] = $minLng; $params[] = $maxLng;
            $params[] = $minLat; $params[] = $maxLat;
        } else {
            $query .= " AND (lng >= ? OR lng <= ?) AND lat BETWEEN ? AND ?";
            $params[] = $minLng; $params[] = $maxLng;
            $params[] = $minLat; $params[] = $maxLat;
        }
    }
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

// --- Zoom-aware LIMIT (only applied when bbox + zoom passed, i.e. map viewport requests) ---
if (isset($_GET['zoom']) && is_numeric($_GET['zoom']) && isset($_GET['bbox'])) {
    $z = (int)$_GET['zoom'];
    if ($z <= 7)        $limit = 60;    // country view — sparse
    elseif ($z <= 10)   $limit = 150;   // city view
    elseif ($z <= 13)   $limit = 350;   // district
    else                $limit = 1000;  // street level
    // Allow client override but cap it
    if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
        $limit = min((int)$_GET['limit'], 1000);
    }
    $query .= " LIMIT " . (int)$limit;
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
