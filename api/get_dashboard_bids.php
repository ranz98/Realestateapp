<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Incoming Bids
    $incomingBidsStmt = $pdo->prepare("
        SELECT b.*, 
               a.title, 
               u.name as bidder_name, 
               u.email as bidder_email,
               u.phone as bidder_phone
        FROM bids b 
        JOIN apartments a ON b.apartment_id = a.id 
        JOIN users u ON b.user_id = u.id 
        WHERE a.user_id = ? 
        ORDER BY b.created_at DESC
    ");
    $incomingBidsStmt->execute([$user_id]);
    $incomingBids = $incomingBidsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Outgoing Bids
    $myBidsStmt = $pdo->prepare("
        SELECT b.*, 
               a.title, 
               seller.email as seller_email, 
               seller.phone as seller_phone, 
               seller.name as seller_name 
        FROM bids b 
        JOIN apartments a ON b.apartment_id = a.id 
        JOIN users seller ON a.user_id = seller.id 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC
    ");
    $myBidsStmt->execute([$user_id]);
    $myBids = $myBidsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'incoming' => $incomingBids,
        'outgoing' => $myBids
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
