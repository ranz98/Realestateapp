<?php
/**
 * Returns a 24-bucket histogram of prices for the current filter set
 * (excluding the price filter itself, so the slider always shows the full
 * distribution). Used to draw the sparkline behind the price slider.
 */
ob_start();
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
require_once '../db.php';

$where  = ["status = 'approved'", "price > 0"];
$params = [];

// Same filters as get_apartments — but NEVER price.
if (isset($_GET['type']) && $_GET['type'] !== 'All' && $_GET['type'] !== '') {
    $where[] = "type = ?"; $params[] = $_GET['type'];
}
if (isset($_GET['location']) && $_GET['location'] !== 'All' && $_GET['location'] !== '') {
    $where[] = "address LIKE ?"; $params[] = '%' . trim($_GET['location']) . '%';
}
if (isset($_GET['beds']) && $_GET['beds'] !== 'All' && $_GET['beds'] !== '') {
    if ($_GET['beds'] === '3+') {
        $where[] = "(bedrooms = '3' OR bedrooms = '3+' OR bedrooms = '4' OR bedrooms = '5' OR bedrooms = '5+')";
    } else { $where[] = "bedrooms = ?"; $params[] = $_GET['beds']; }
}
if (isset($_GET['listing_mode']) && in_array($_GET['listing_mode'], ['Buy', 'Rent'], true)) {
    $where[] = "listing_mode = ?"; $params[] = $_GET['listing_mode'];
}
foreach (['min_lat' => 'lat >= ?', 'max_lat' => 'lat <= ?',
          'min_lng' => 'lng >= ?', 'max_lng' => 'lng <= ?'] as $k => $cond) {
    if (isset($_GET[$k]) && is_numeric($_GET[$k])) {
        $where[] = $cond; $params[] = (float) $_GET[$k];
    }
}

$whereSql = implode(' AND ', $where);
$BUCKETS  = 24;

try {
    // Get min/max so we can size buckets to the actual range.
    $stmt = $pdo->prepare("SELECT MIN(price) AS mn, MAX(price) AS mx FROM apartments WHERE $whereSql");
    $stmt->execute($params);
    $r = $stmt->fetch();
    $mn = (float) ($r['mn'] ?? 0);
    $mx = (float) ($r['mx'] ?? 0);
    if ($mx <= $mn) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['buckets' => array_fill(0, $BUCKETS, 0), 'max' => 0, 'min_price' => $mn, 'max_price' => $mx]);
        exit;
    }
    // Single GROUP BY query — bucket index computed in SQL.
    $sql = "SELECT FLOOR((price - ?) / ?) AS b, COUNT(*) AS c
            FROM apartments
            WHERE $whereSql
            GROUP BY b";
    $bucketSize = ($mx - $mn) / $BUCKETS;
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$mn, $bucketSize], $params));

    $buckets = array_fill(0, $BUCKETS, 0);
    while ($row = $stmt->fetch()) {
        $i = max(0, min($BUCKETS - 1, (int) $row['b']));
        $buckets[$i] += (int) $row['c'];
    }
    $max = max($buckets);

    ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: private, max-age=30');
    echo json_encode([
        'buckets'   => $buckets,
        'max'       => $max,
        'min_price' => $mn,
        'max_price' => $mx,
    ]);
} catch (PDOException $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
