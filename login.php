<?php
session_start();
require_once 'db.php';

$error = '';
$errorField = '';   // 'email' | 'password' | ''
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $emailVal = $email;

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
        $errorField = 'email';
    } elseif ($password === '') {
        $error = "Please enter your password.";
        $errorField = 'password';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "We couldn't find an account with that email.";
            $errorField = 'email';
        } elseif (!password_verify($password, $user['password'])) {
            $error = "Wrong password. Try again or reset it.";
            $errorField = 'password';
        } else {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Log in — MyHomeMyLand.LK</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="style.css?v=5.6">
<link rel="stylesheet" href="auth.css?v=<?= time() ?>">
<script>try{if(localStorage.getItem('theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}</script>
</head>
<body>
<header class="site-header">
    <nav class="navbar">
        <a href="index.php" class="logo" style="text-decoration:none;">
            <i class="fa-solid fa-house-chimney-window"></i> MyHomeMyLand.LK
        </a>
        <div class="nav-links">
            <button id="theme-toggle" class="theme-toggle" title="Toggle Dark/Light Mode">
                <i class="fa-solid fa-moon"></i>
                <i class="fa-solid fa-sun" style="display:none;"></i>
            </button>
            <a href="index.php">Explore</a>
            <a href="register.php" class="btn-primary">Sign Up</a>
        </div>
    </nav>
</header>

<div class="auth-shell">
    <aside class="auth-hero" aria-hidden="true">
        <div class="auth-hero-inner">
            <span class="auth-hero-eyebrow"><i class="fa-solid fa-house-chimney-window"></i> MyHomeMyLand</span>
            <h2 class="auth-hero-title">Find a home you'll love — across Sri Lanka.</h2>
            <p class="auth-hero-sub">Browse thousands of verified listings, place bids, and message owners directly. Built for Sri Lankan buyers, renters and agents.</p>
            <ul class="auth-hero-bullets">
                <li><i class="fa-solid fa-check"></i> Real-time bidding on listings</li>
                <li><i class="fa-solid fa-check"></i> Save favourites &amp; get alerts</li>
                <li><i class="fa-solid fa-check"></i> Verified owner messaging</li>
            </ul>
        </div>
    </aside>

    <div class="auth-card">
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-subtitle">Log in to manage your listings, bids, and saved properties.</p>

        <?php if ($error && $errorField === ''): ?>
            <div class="auth-banner"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
        <?php endif; ?>

        <div class="auth-social">
            <button type="button" class="auth-social-btn google" data-coming-soon>
                <span class="auth-social-icon"><i class="fa-brands fa-google"></i></span>
                <span>Continue with Google</span>
            </button>
            <button type="button" class="auth-social-btn facebook" data-coming-soon>
                <span class="auth-social-icon"><i class="fa-brands fa-facebook"></i></span>
                <span>Continue with Facebook</span>
            </button>
        </div>

        <div class="auth-divider">or</div>

        <form method="POST" class="auth-form" id="login-form" novalidate>
            <div class="auth-field <?= $errorField==='email' ? 'has-error' : '' ?>" data-field="email">
                <label for="login-email">Email</label>
                <div class="auth-input-wrap">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" id="login-email" name="email" autocomplete="email"
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($emailVal) ?>" required>
                </div>
                <div class="auth-error-msg"><?= $errorField==='email' ? htmlspecialchars($error) : 'Enter a valid email address.' ?></div>
            </div>

            <div class="auth-field <?= $errorField==='password' ? 'has-error' : '' ?>" data-field="password">
                <label for="login-password">Password</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="login-password" name="password" autocomplete="current-password"
                           placeholder="Your password" required minlength="6">
                    <button type="button" class="auth-password-toggle" aria-label="Show password" data-toggle="login-password">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <div class="auth-error-msg"><?= $errorField==='password' ? htmlspecialchars($error) : 'Password must be at least 6 characters.' ?></div>
            </div>

            <button type="submit" class="auth-submit">Log in</button>
        </form>

        <p class="auth-foot">New to MyHomeMyLand? <a href="register.php">Create an account</a></p>
        <p class="auth-fine">By continuing, you agree to our <a href="#">Terms of Use</a> and <a href="#">Privacy Policy</a>.</p>
    </div>
</div>

<div class="auth-toast" id="auth-toast">Coming soon</div>

<script src="script.js"></script>
<script src="auth.js?v=<?= time() ?>"></script>
</body>
</html>
