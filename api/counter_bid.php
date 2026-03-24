<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$bid_id = $_POST['bid_id'] ?? null;
$counter_amount = $_POST['counter_amount'] ?? null;
$counter_message = $_POST['counter_message'] ?? '';

if (!$bid_id || !$counter_amount) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Verify the user owns the apartment this bid is on
    $verifyStmt = $pdo->prepare("
        SELECT a.user_id 
        FROM bids b 
        JOIN apartments a ON b.apartment_id = a.id 
        WHERE b.id = ?
    ");
    $verifyStmt->execute([$bid_id]);
    $aptOwner = $verifyStmt->fetchColumn();

    if ($aptOwner != $user_id) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }

    // Update the bid to countered
    $updateStmt = $pdo->prepare("
        UPDATE bids 
        SET status = 'countered', counter_amount = ?, counter_message = ? 
        WHERE id = ?
    ");
    $updateStmt->execute([$counter_amount, $counter_message, $bid_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
