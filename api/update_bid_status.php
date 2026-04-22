<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$bid_id = $_POST['bid_id'] ?? null;
$status = $_POST['status'] ?? null;
$accept_counter = $_POST['accept_counter'] ?? '0';

if (!$bid_id || !in_array($status, ['accepted', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

try {
    // Determine who is allowed to change this bid
    $stmt = $pdo->prepare("
        SELECT b.user_id as buyer_id, a.user_id as seller_id, b.counter_amount 
        FROM bids b 
        JOIN apartments a ON b.apartment_id = a.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$bid_id]);
    $bidData = $stmt->fetch();

    if (!$bidData) {
        http_response_code(404);
        echo json_encode(['error' => 'Bid not found.']);
        exit;
    }

    if ($accept_counter === '1') {
        // Buyer is accepting/rejecting the seller's counter offer
        if ($bidData['buyer_id'] != $user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to accept this counter offer.']);
            exit;
        }

        if ($status === 'accepted') {
            $update = $pdo->prepare("UPDATE bids SET status = 'accepted', amount = ? WHERE id = ?");
            $update->execute([$bidData['counter_amount'], $bid_id]);
        } else {
            $update = $pdo->prepare("UPDATE bids SET status = 'rejected' WHERE id = ?");
            $update->execute([$bid_id]);
        }
    } else {
        // Seller is accepting/rejecting the buyer's original offer
        if ($bidData['seller_id'] != $user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to manage this bid.']);
            exit;
        }

        $update = $pdo->prepare("UPDATE bids SET status = ? WHERE id = ?");
        $update->execute([$status, $bid_id]);
    }

    echo json_encode(['success' => true, 'message' => "Offer has been " . $status . "."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
