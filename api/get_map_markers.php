<?php
/**
 * Lightweight endpoint for the map view: returns ONLY the fields needed to
 * draw a marker (id, lat, lng, price, type). At 10k rows this is ~10x smaller
 * than the full listings payload and renders instantly with markercluster.
 *
 * Accepts the same filter params as get_apartments.php so the map and the
 * paginated list stay in sync. No pagination — map shows all matches.
 */
ob_start();
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
require_once '../db.php';

$where  = ["status = 'approved'", "lat IS NOT NULL", "lng IS NOT NULL"];
$params = [];

if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $like = '%' . trim($_GET['search']) . '%';
    $where[] = "(title LIKE ? OR address LIKE ?)";
    $params[] = $like;
    $params[] = $like;
}
if (isset($_GET['type']) && $_GET['type'] !== 'All' && $_GET['type'] !== '') {
    $where[] = "type = ?";
    $params[] = $_GET['type'];
}
if (isset($_GET['location']) && $_GET['location'] !== 'All' && $_GET['location'] !== '') {
    $where[] = "address LIKE ?";
    $params[] = '%' . trim($_GET['location']) . '%';
}
if (isset($_GET['beds']) && $_GET['beds'] !== 'All' && $_GET['beds'] !== '') {
    if ($_GET['beds'] === '3+') {
        $where[] = "(bedrooms = '3' OR bedrooms = '3+' OR bedrooms = '4' OR bedrooms = '5' OR bedrooms = '5+')";
    } else {
        $where[] = "bedrooms = ?";
        $params[] = $_GET['beds'];
    }
}
if (isset($_GET['baths']) && $_GET['baths'] !== 'All' && $_GET['baths'] !== '') {
    if ($_GET['baths'] === '3+') {
        $where[] = "baths >= 3";
    } else {
        $where[] = "baths = ?";
        $params[] = $_GET['baths'];
    }
}
if (isset($_GET['min_price']) && is_numeric($_GET['min_price']) && $_GET['min_price'] > 0) {
    $where[] = "price >= ?";
    $params[] = (float) $_GET['min_price'];
}
if (isset($_GET['max_price']) && is_numeric($_GET['max_price']) && $_GET['max_price'] > 0) {
    $where[] = "price <= ?";
    $params[] = (float) $_GET['max_price'];
}
if (isset($_GET['listing_mode']) && in_array($_GET['listing_mode'], ['Buy', 'Rent'], true)) {
    $where[] = "listing_mode = ?";
    $params[] = $_GET['listing_mode'];
}

$whereSql = implode(' AND ', $where);

// Hard cap to avoid pathological full-table dumps.
$cap = 5000;

try {
    $sql = "SELECT id, lat, lng, price, type, title FROM apartments WHERE $whereSql LIMIT $cap";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: private, max-age=10');
    echo json_encode(['markers' => $rows, 'capped' => count($rows) >= $cap]);
} catch (PDOException $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
