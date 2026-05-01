<?php
// Buffer all output so stray PHP warnings/notices never corrupt the JSON response.
ob_start();

// Close any auto-started session immediately so this request is never
// blocked by a session file lock held by the parent page load.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
require_once '../db.php';

/**
 * Slim column set for listing cards. Description and other heavy fields are
 * fetched only on the detail page (apartment.php) — at 10k rows this trims
 * the JSON payload by ~70%.
 */
$cardCols = "id, title, address, type, bedrooms, baths, price, listing_mode, "
          . "view_count, size_perches, images, lat, lng, created_at";

$where  = ["status = 'approved'"];
$params = [];

// --- Search: prefer FULLTEXT (added by migration), fall back to LIKE ---
$searchRaw = isset($_GET['search']) ? trim($_GET['search']) : '';
$useFulltext = false;
if ($searchRaw !== '') {
    // Detect FULLTEXT index once per request and cache for this connection.
    static $hasFt = null;
    if ($hasFt === null) {
        try {
            $r = $pdo->query("SHOW INDEX FROM apartments WHERE Key_name = 'ft_search'")->fetch();
            $hasFt = (bool) $r;
        } catch (Throwable $e) { $hasFt = false; }
    }
    if ($hasFt && mb_strlen($searchRaw) >= 3) {
        // Boolean mode + prefix wildcard so partial words match.
        $tokens = preg_split('/\s+/', $searchRaw, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_map(fn($t) => '+' . preg_replace('/[^\p{L}\p{N}_]/u', '', $t) . '*', $tokens);
        $boolean = implode(' ', array_filter($tokens));
        if ($boolean !== '') {
            $where[] = "MATCH(title, address, description) AGAINST (? IN BOOLEAN MODE)";
            $params[] = $boolean;
            $useFulltext = true;
        }
    }
    if (!$useFulltext) {
        $like = '%' . $searchRaw . '%';
        $where[] = "(title LIKE ? OR address LIKE ?)";
        $params[] = $like;
        $params[] = $like;
    }
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

// Bounding-box filter (used by the draw-shape feature for a fast SQL prefilter;
// exact point-in-polygon refinement happens on the client with Turf).
foreach (['min_lat' => 'lat >= ?', 'max_lat' => 'lat <= ?',
          'min_lng' => 'lng >= ?', 'max_lng' => 'lng <= ?'] as $k => $cond) {
    if (isset($_GET[$k]) && is_numeric($_GET[$k])) {
        $where[] = $cond;
        $params[] = (float) $_GET[$k];
    }
}

$whereSql = implode(' AND ', $where);

// --- Sort (index-friendly, no ORDER BY RAND) ---
$sort = $_GET['sort'] ?? 'newest';
switch ($sort) {
    case 'price_low':  $orderBy = "price ASC, id DESC"; break;
    case 'price_high': $orderBy = "price DESC, id DESC"; break;
    case 'oldest':     $orderBy = "created_at ASC, id ASC"; break;
    case 'trending':   $orderBy = "view_count DESC, created_at DESC"; break;
    case 'newest':
    default:           $orderBy = "created_at DESC, id DESC"; break;
}

// --- Pagination ---
$page  = isset($_GET['page'])  ? max(1, (int) $_GET['page']) : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit']        : 24;
if ($limit < 1)   $limit = 24;
if ($limit > 100) $limit = 100; // hard cap to prevent abuse
$offset = ($page - 1) * $limit;

// Legacy mode: if no `page` param at all, return a plain array (older callers).
$paginated = isset($_GET['page']);

try {
    // Count query (uses same WHERE — runs in a few ms with the new indexes).
    $countSql  = "SELECT COUNT(*) FROM apartments WHERE $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Page query. LIMIT/OFFSET bound as ints (PDO emulation requires this).
    $sql = "SELECT $cardCols FROM apartments WHERE $whereSql ORDER BY $orderBy LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: private, max-age=10'); // brief client cache to absorb slider chatter

    if ($paginated) {
        echo json_encode([
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'limit'    => $limit,
            'has_more' => ($offset + count($items)) < $total,
        ]);
    } else {
        echo json_encode($items);
    }
} catch (PDOException $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'hint'  => 'Did you run migrations/add_perf_indexes.php on the live server?',
    ]);
}
