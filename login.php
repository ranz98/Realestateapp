<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - MyHomeMyLand.LK</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container form-page" style="margin: auto; display: flex; align-items: center; min-height: 100vh;">
        <div class="form-wrapper">
            <h2 class="text-center" style="font-family: 'Outfit'; margin-bottom: 2rem;">Welcome Back</h2>
            <?php if(isset($error)): ?>
                <div style="background: #ef4444; color: white; padding: 10px; border-radius: 8px; margin-bottom: 1rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="listing-form">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary w-100 mt-3">Login</button>
                <p style="text-align: center; margin-top: 1rem;">
                    Don't have an account? <a href="register.php" style="color: var(--primary);">Sign Up</a>
                </p>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="index.php" style="color: var(--text-secondary); text-decoration: none;">&larr; Back to Home</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Site Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> MyHomeMyLand.LK. All rights reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Us</a>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
