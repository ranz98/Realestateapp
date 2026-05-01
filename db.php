<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? '';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    // If this is an API request, respond with JSON so the browser can parse it
    $isApi = isset($_SERVER['SCRIPT_FILENAME']) &&
             strpos(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']), '/api/') !== false;
    if ($isApi) {
        header('Content-Type: application/json');
        http_response_code(503);
        die(json_encode(['error' => 'Database unavailable. Please ensure MySQL is running in XAMPP.']));
    }
    die("Database Connection failed: " . $e->getMessage() . "<br>Please ensure XAMPP MySQL is running and you have imported database.sql");
}
?>
