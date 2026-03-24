<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to view bids.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$mode = $_GET['mode'] ?? 'buyer'; // 'buyer' or 'seller'

try {
    if ($mode === 'seller') {
        // 1. Incoming Bids for My Properties
        $query = "SELECT b.*, 
                         a.title AS property_title, a.price AS asking_price, a.images, 
                         u.name AS bidder_name, u.email AS bidder_email
                  FROM bids b
                  JOIN apartments a ON b.apartment_id = a.id
                  JOIN users u ON b.user_id = u.id
                  WHERE a.user_id = ?
                  ORDER BY b.created_at DESC";
    } else {
        // 2. Outgoing Bids (My Offers)
        $query = "SELECT b.*, 
                         a.title AS property_title, a.price AS asking_price, a.images, a.seller_email, a.seller_phone,
                         u_seller.name AS seller_name
                  FROM bids b
                  JOIN apartments a ON b.apartment_id = a.id
                  JOIN users u_seller ON a.user_id = u_seller.id
                  WHERE b.user_id = ?
                  ORDER BY b.created_at DESC";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($bids);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
