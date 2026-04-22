<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$bio = $_POST['bio'] ?? '';
$address = $_POST['address'] ?? '';
$profile_pic = $_POST['profile_pic'] ?? ''; // URL fallback or handle upload if needed

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, bio = ?, address = ?, profile_pic = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $bio, $address, $profile_pic, $user_id]);

    // Update session name if changed
    $_SESSION['user_name'] = $name;

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
