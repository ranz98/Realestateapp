<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to place a bid.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$apartment_id = $_POST['apartment_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$message = $_POST['message'] ?? '';

if (!$apartment_id || !$amount || !is_numeric($amount)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid offer data.']);
    exit;
}

try {
    // 1. Check if the property exists and the bidder is NOT the owner
    $stmt = $pdo->prepare("SELECT user_id FROM apartments WHERE id = ?");
    $stmt->execute([$apartment_id]);
    $apartment = $stmt->fetch();

    if (!$apartment) {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found.']);
        exit;
    }

    if ($apartment['user_id'] == $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'You cannot bid on your own property.']);
        exit;
    }

    // 2. Insert the bid
    $stmt = $pdo->prepare("INSERT INTO bids (apartment_id, user_id, amount, message, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$apartment_id, $user_id, $amount, $message]);

    echo json_encode(['success' => true, 'message' => 'Your offer has been submitted successfully!']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
