<?php
session_start();
require_once 'db.php';

$DUMMY_OTP = '123456'; // dev OTP — accepts this code on the OTP step

$error = '';
$errorField = '';
$nameVal = $emailVal = '';
$step = 'form';      // 'form' | 'otp' | 'done'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'submit_form';

    if ($action === 'submit_form') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm']  ?? '';
        $nameVal  = $name;
        $emailVal = $email;

        if ($name === '') {
            $error = "Please tell us your name."; $errorField = 'name';
        } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address."; $errorField = 'email';
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters."; $errorField = 'password';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = "Use at least 1 uppercase letter and 1 number."; $errorField = 'password';
        } elseif ($confirm !== $password) {
            $error = "Passwords don't match."; $errorField = 'confirm';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "This email is already registered. Try logging in."; $errorField = 'email';
            } else {
                // Store pending registration in session, jump to OTP step
                $_SESSION['pending_signup'] = [
                    'name'     => $name,
                    'email'    => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'otp'      => $DUMMY_OTP,
                ];
                $step = 'otp';
            }
        }
    } elseif ($action === 'verify_otp') {
        if (empty($_SESSION['pending_signup'])) {
            $error = "Your signup session has expired. Please start over.";
            $step = 'form';
        } else {
            $entered = preg_replace('/\D+/', '', implode('', $_POST['otp'] ?? []));
            $expected = $_SESSION['pending_signup']['otp'];

            if ($entered === '' || strlen($entered) !== 6) {
                $error = "Enter the 6-digit code."; $errorField = 'otp';
                $step = 'otp';
            } elseif ($entered !== $expected) {
                $error = "That code is incorrect. Try again."; $errorField = 'otp';
                $step = 'otp';
            } else {
                // Create user
                $p = $_SESSION['pending_signup'];
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
                if ($stmt->execute([$p['name'], $p['email'], $p['password']])) {
                    $_SESSION['user_id']   = $pdo->lastInsertId();
                    $_SESSION['user_name'] = $p['name'];
                    $_SESSION['user_role'] = 'user';
                    unset($_SESSION['pending_signup']);
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Couldn't create your account. Please try again.";
                    $step = 'form';
                }
            }
        }
        // Re-show OTP step — re-fetch saved values for the form fallback
        if (!empty($_SESSION['pending_signup'])) {
            $nameVal  = $_SESSION['pending_signup']['name'];
            $emailVal = $_SESSION['pending_signup']['email'];
        }
    } elseif ($action === 'back_to_form') {
        unset($_SESSION['pending_signup']);
        $step = 'form';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sign up — MyHomeMyLand.LK</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="style.css?v=5.6">
<link rel="stylesheet" href="auth.css?v=<?= time() ?>">
<script>try{if(localStorage.getItem('theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}</script>
</head>
<body>
<header class="site-header auth-header">
    <nav class="navbar">
        <a href="index.php" class="t-logo" style="text-decoration:none;">
            <i class="fa-solid fa-house-chimney-window t-logo-icon"></i>
            <span class="t-logo-words">
                <span class="t-logo-top">MyHome</span>
                <span class="t-logo-bot">MyLand</span>
            </span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="active">Explore</a>
        </div>
    </nav>
</header>

<div class="auth-shell">
    <aside class="auth-hero" aria-hidden="true">
        <div class="auth-hero-inner">
            <span class="auth-hero-eyebrow"><i class="fa-solid fa-house-chimney-window"></i> MyHomeMyLand</span>
            <h2 class="auth-hero-title">Your next home is one signup away.</h2>
            <p class="auth-hero-sub">Join thousands of Sri Lankans buying, renting and listing property — all in one place.</p>
            <ul class="auth-hero-bullets">
                <li><i class="fa-solid fa-check"></i> Free to list — no hidden fees</li>
                <li><i class="fa-solid fa-check"></i> Bidding &amp; counter-offer tools</li>
                <li><i class="fa-solid fa-check"></i> Trusted by verified owners</li>
            </ul>
        </div>
    </aside>

    <div class="auth-card">
        <?php if ($step === 'form'): ?>
            <h1 class="auth-title">Create your account</h1>
            <p class="auth-subtitle">Sign up to list properties, place bids, and save favourites.</p>

            <?php if ($error && $errorField === ''): ?>
                <div class="auth-banner"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
            <?php endif; ?>

            <div class="auth-social">
                <button type="button" class="auth-social-btn google" data-coming-soon>
                    <span class="auth-social-icon"><i class="fa-brands fa-google"></i></span>
                    <span>Sign up with Google</span>
                </button>
                <button type="button" class="auth-social-btn facebook" data-coming-soon>
                    <span class="auth-social-icon"><i class="fa-brands fa-facebook"></i></span>
                    <span>Sign up with Facebook</span>
                </button>
            </div>

            <div class="auth-divider">or</div>

            <form method="POST" class="auth-form" id="register-form" novalidate>
                <input type="hidden" name="action" value="submit_form">

                <div class="auth-field <?= $errorField==='name' ? 'has-error' : '' ?>" data-field="name">
                    <label for="reg-name">Full name</label>
                    <div class="auth-input-wrap">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" id="reg-name" name="name" autocomplete="name"
                               placeholder="Your name"
                               value="<?= htmlspecialchars($nameVal) ?>" required>
                    </div>
                    <div class="auth-error-msg"><?= $errorField==='name' ? htmlspecialchars($error) : 'Please enter your name.' ?></div>
                </div>

                <div class="auth-field <?= $errorField==='email' ? 'has-error' : '' ?>" data-field="email">
                    <label for="reg-email">Email</label>
                    <div class="auth-input-wrap">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" id="reg-email" name="email" autocomplete="email"
                               placeholder="you@example.com"
                               value="<?= htmlspecialchars($emailVal) ?>" required>
                    </div>
                    <div class="auth-error-msg"><?= $errorField==='email' ? htmlspecialchars($error) : 'Enter a valid email.' ?></div>
                </div>

                <div class="auth-field <?= $errorField==='password' ? 'has-error' : '' ?>" data-field="password">
                    <label for="reg-password">Password</label>
                    <div class="auth-input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="reg-password" name="password" autocomplete="new-password"
                               placeholder="Min. 8 chars, 1 uppercase, 1 number" required minlength="8">
                        <button type="button" class="auth-password-toggle" aria-label="Show password" data-toggle="reg-password">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <div class="auth-strength" id="strength"><span></span></div>
                    <ul class="auth-pwd-rules" id="pwd-rules">
                        <li data-rule="len"><i class="fa-solid fa-circle"></i> 8+ characters</li>
                        <li data-rule="upper"><i class="fa-solid fa-circle"></i> 1 uppercase</li>
                        <li data-rule="num"><i class="fa-solid fa-circle"></i> 1 number</li>
                        <li data-rule="sym"><i class="fa-solid fa-circle"></i> 1 symbol (bonus)</li>
                    </ul>
                    <div class="auth-error-msg"><?= $errorField==='password' ? htmlspecialchars($error) : '' ?></div>
                </div>

                <div class="auth-field <?= $errorField==='confirm' ? 'has-error' : '' ?>" data-field="confirm">
                    <label for="reg-confirm">Confirm password</label>
                    <div class="auth-input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="reg-confirm" name="confirm" autocomplete="new-password"
                               placeholder="Re-enter password" required minlength="8">
                        <button type="button" class="auth-password-toggle" aria-label="Show password" data-toggle="reg-confirm">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <div class="auth-error-msg"><?= $errorField==='confirm' ? htmlspecialchars($error) : 'Passwords must match.' ?></div>
                </div>

                <button type="submit" class="auth-submit">Create account</button>
            </form>

            <p class="auth-foot">Already have an account? <a href="login.php">Log in</a></p>
            <p class="auth-fine">By continuing, you agree to our <a href="#">Terms of Use</a> and <a href="#">Privacy Policy</a>.</p>

        <?php elseif ($step === 'otp'): ?>
            <h1 class="auth-title">Verify your email</h1>
            <p class="auth-subtitle">We sent a 6-digit code to <strong><?= htmlspecialchars($emailVal) ?></strong>. Enter it below to finish signup.</p>

            <?php if ($error): ?>
                <div class="auth-banner"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
            <?php endif; ?>

            <form method="POST" class="auth-form" id="otp-form">
                <input type="hidden" name="action" value="verify_otp">
                <div class="auth-otp-wrap" id="otp-wrap">
                    <?php for ($i=0; $i<6; $i++): ?>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" name="otp[]" autocomplete="one-time-code" aria-label="Digit <?= $i+1 ?>">
                    <?php endfor; ?>
                </div>
                <div class="auth-otp-hint">Demo build — use code <code><?= htmlspecialchars($DUMMY_OTP) ?></code> to verify.</div>
                <button type="submit" class="auth-submit">Verify &amp; create account</button>
                <button type="button" class="auth-otp-resend" id="otp-resend">Didn't get it? Resend code</button>
            </form>

            <form method="POST" style="text-align:center;margin-top:.6rem;">
                <input type="hidden" name="action" value="back_to_form">
                <button type="submit" class="auth-back" style="background:transparent;border:none;cursor:pointer;font-size:.85rem;">
                    <i class="fa-solid fa-arrow-left"></i> Use a different email
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="auth-toast" id="auth-toast">Coming soon</div>

<script src="script.js"></script>
<script src="auth.js?v=<?= time() ?>"></script>
</body>
</html>
