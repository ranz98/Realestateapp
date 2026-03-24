<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Must be logged in to place a bid']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$apartment_id = $_POST['apartment_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$message = $_POST['message'] ?? '';

if (!$apartment_id || !$amount) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Check if apartment exists and isn't owned by the user
    $aptStmt = $pdo->prepare("SELECT user_id FROM apartments WHERE id = ?");
    $aptStmt->execute([$apartment_id]);
    $apt = $aptStmt->fetch();
    
    if (!$apt) {
        echo json_encode(['success' => false, 'error' => 'Property not found']);
        exit;
    }
    
    if ($apt['user_id'] == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'You cannot bid on your own property']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO bids (apartment_id, user_id, amount, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$apartment_id, $_SESSION['user_id'], $amount, $message]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
