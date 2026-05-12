<?php
ob_start();
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
require_once '../db.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT r.id, r.title, r.caption, r.video_url, r.poster_url, r.apartment_id,
                   r.location, r.price, r.listing_mode,
                   a.id AS a_id, a.title AS a_title, a.price AS a_price, a.address AS a_address,
                   a.type AS a_type, a.bedrooms AS a_bedrooms, a.baths AS a_baths,
                   a.lat AS a_lat, a.lng AS a_lng, a.images AS a_images,
                   a.listing_mode AS a_listing_mode
            FROM reels r
            LEFT JOIN apartments a ON a.id = r.apartment_id AND a.status = 'approved'
            WHERE r.is_active = 1
            ORDER BY r.sort_order ASC, r.id DESC";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    $out = [];
    foreach ($rows as $r) {
        $listing = null;
        if (!empty($r['a_id'])) {
            $listing = [
                'id'           => (int)$r['a_id'],
                'title'        => $r['a_title'],
                'price'        => $r['a_price'],
                'address'      => $r['a_address'],
                'type'         => $r['a_type'],
                'bedrooms'     => $r['a_bedrooms'],
                'baths'        => $r['a_baths'],
                'lat'          => $r['a_lat'] !== null ? (float)$r['a_lat'] : null,
                'lng'          => $r['a_lng'] !== null ? (float)$r['a_lng'] : null,
                'images'       => $r['a_images'],
                'listing_mode' => $r['a_listing_mode'],
            ];
        }
        $out[] = [
            'id'           => (int)$r['id'],
            'title'        => $r['title'],
            'caption'      => $r['caption'],
            'video_url'    => $r['video_url'],
            'poster_url'   => $r['poster_url'],
            'apartment_id' => $r['apartment_id'] !== null ? (int)$r['apartment_id'] : null,
            'location'     => $r['location'],
            'price'        => $r['price'],
            'listing_mode' => $r['listing_mode'],
            'listing'      => $listing,
        ];
    }

    ob_end_clean();
    echo json_encode(['ok' => true, 'reels' => $out]);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
