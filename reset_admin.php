<?php
require_once 'db.php';
// Reset Super Admin password to 'admin123'
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@fma.lk'");
if ($stmt->execute([$hash])) {
    echo "<h2>Admin Password Successfully Reset to 'admin123'!</h2>";
    echo "<p>You can now <a href='login.php'>Login</a>.</p>";
    echo "<p><em>Note: Please delete this file (reset_admin.php) after logging in for security.</em></p>";
} else {
    echo "<h2>Error resetting admin password.</h2>";
}
?>
