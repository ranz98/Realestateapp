<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch summary of activity for the profile
$listingsStmt = $pdo->prepare("SELECT count(*) FROM apartments WHERE user_id = ?");
$listingsStmt->execute([$user_id]);
$total_listings = $listingsStmt->fetchColumn();

$bidsStmt = $pdo->prepare("SELECT count(*) FROM bids WHERE user_id = ?");
$bidsStmt->execute([$user_id]);
$total_bids = $bidsStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - MyHomeMyLand.LK</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        :root { --glass: rgba(255, 255, 255, 0.05); --glass-border: rgba(255, 255, 255, 0.1); }
        [data-theme="dark"] { --glass: rgba(0, 0, 0, 0.3); --glass-border: rgba(255, 255, 255, 0.05); }

        body { background: var(--bg-main); color: var(--text-main); font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        
        .profile-container {
            max-width: 1050px;
            margin: 4rem auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2.5rem;
            align-items: start;
        }

        .mc-card { 
            background: var(--bg-card); 
            border: 1px solid var(--border-glass); 
            border-radius: var(--radius-lg); 
            padding: 2.5rem; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.05); 
            position: relative; overflow: hidden;
            backdrop-filter: blur(12px);
        }
        .mc-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--primary), #8b5cf6); }

        .profile-sidebar { text-align: center; display: flex; flex-direction: column; align-items: center; }
        
        .profile-pic-container { position: relative; margin-bottom: 1.5rem; }
        .profile-pic-glow { position: absolute; inset: -10px; background: var(--primary); filter: blur(20px); opacity: 0.3; border-radius: 50%; z-index: -1; animation: pulseGlow 3s infinite alternate; }
        
        @keyframes pulseGlow { from { opacity: 0.2; transform: scale(0.95); } to { opacity: 0.4; transform: scale(1.05); } }

        .profile-pic {
            width: 160px; height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--bg-main);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            background: #linear-gradient(135deg, var(--primary), #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: 4.5rem; color: white; font-family: 'Outfit', sans-serif; font-weight: 800;
        }

        .user-name-title { font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin: 0; font-weight: 800; letter-spacing: -0.5px; }

        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 2.5rem; width: 100%; }
        .stat-box { background: var(--glass); padding: 1.2rem; border-radius: 12px; border: 1px solid var(--glass-border); text-align: left; transition: transform 0.2s; }
        .stat-box:hover { transform: translateY(-3px); border-color: var(--primary); }
        .stat-val { font-size: 1.8rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit'; }
        .stat-label { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1.5px; margin-top: 0.2rem; font-weight: 600;}

        .form-group { margin-bottom: 1.8rem; text-align: left; }
        .form-label { display: block; margin-bottom: 0.6rem; font-weight: 600; color: var(--text-main); font-size: 0.9rem; letter-spacing: 0.3px; }
        .form-control {
            width: 100%; padding: 0.9rem 1.2rem;
            background: rgba(0,0,0,0.02); color: var(--text-main);
            border: 1px solid var(--border-glass); border-radius: 10px;
            font-family: inherit; font-size: 1rem; transition: all 0.3s ease;
        }
        [data-theme="dark"] .form-control { background: rgba(255,255,255,0.03); }
        .form-control:focus { outline: none; border-color: var(--primary); background: transparent; box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1); }
        
        textarea.form-control { resize: vertical; min-height: 130px; line-height: 1.6; }

        .btn-update {
            width: 100%; padding: 1rem; margin-top: 1rem;
            background: linear-gradient(135deg, var(--primary), #0284c7); color: white;
            border: none; border-radius: 10px;
            font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1.15rem; letter-spacing: 0.5px;
            cursor: pointer; transition: all 0.3s;
            display: flex; justify-content: center; align-items: center; gap: 0.6rem;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.25);
        }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(14, 165, 233, 0.4); opacity: 0.95; }

        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--text-secondary); text-decoration: none; font-weight: 600; font-size: 0.95rem; margin-top: 2.5rem; transition: color 0.2s; }
        .back-link:hover { color: var(--primary); }

        @media (max-width: 900px) {
            .profile-container { grid-template-columns: 1fr; margin: 2rem auto; }
            .profile-sidebar { max-width: 450px; margin: 0 auto; }
        }
    </style>
    <?php include 'get-theme.php'; ?>
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="logo"><a href="index.php">MyHomeMyLand.LK</a></div>
            <nav class="nav-links desktop-nav">
                <a href="dashboard.php" style="color: var(--primary); font-weight: 600;"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                <a href="list-apartment.php">List Property</a>
                <a href="logout.php">Logout</a>
                <button id="theme-toggle" class="btn-icon" aria-label="Toggle dark mode"><i class="fa-solid fa-moon"></i></button>
            </nav>
        </div>
    </header>

    <div class="profile-container">
        <!-- Sidebar -->
        <div class="mc-card profile-sidebar">
            <div class="profile-pic-container">
                <div class="profile-pic-glow"></div>
                <?php if(!empty($user['profile_pic'])): ?>
                    <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture" class="profile-pic">
                <?php else: ?>
                    <div class="profile-pic" style="background: linear-gradient(135deg, var(--primary), #8b5cf6); border-color: transparent;"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <?php endif; ?>
            </div>
            
            <h2 class="user-name-title"><?= htmlspecialchars($user['name']) ?></h2>
            <p style="color: var(--text-secondary); margin-top: 0.4rem; font-size: 0.95rem;"><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
            <div style="display: inline-block; background: var(--glass); padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.75rem; font-weight: 600; color: var(--primary); margin-top: 0.8rem;">
                Member since <?= date('M Y', strtotime($user['created_at'])) ?>
            </div>

            <div class="stat-grid">
                <div class="stat-box">
                    <div class="stat-val"><?= $total_listings ?></div>
                    <div class="stat-label">Properties</div>
                </div>
                <div class="stat-box">
                    <div class="stat-val"><?= $total_bids ?></div>
                    <div class="stat-label">Offers</div>
                </div>
            </div>
            
            <a href="dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <!-- Main Form -->
        <div class="mc-card" style="padding: 2.5rem 3rem;">
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.6rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-glass); padding-bottom: 1rem; font-weight: 700;">
                <i class="fa-solid fa-user-pen" style="color: var(--primary); margin-right: 0.5rem;"></i> Profile Settings
            </h3>
            
            <form id="profileForm">
                <div id="formMessage" style="display:none; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600;"></div>

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Phone Number <i class="fa-brands fa-whatsapp" style="color: #25d366;" title="Used for quick contact"></i></label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+94 77 XXXXXXX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Profile Picture URL</label>
                        <input type="url" name="profile_pic" class="form-control" value="<?= htmlspecialchars($user['profile_pic'] ?? '') ?>" placeholder="https://example.com/photo.jpg">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Address / Location</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="Colombo, Sri Lanka">
                </div>

                <div class="form-group">
                    <label class="form-label">About Me (Bio)</label>
                    <textarea name="bio" class="form-control" placeholder="Tell buyers/sellers a bit about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn-update" id="submitBtn">
                    <i class="fa-solid fa-save"></i> Save Profile Details
                </button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const msg = document.getElementById('formMessage');
            
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;
            msg.style.display = 'none';

            try {
                const fd = new FormData(e.target);
                const res = await fetch('api/update_profile.php', { method: 'POST', body: fd });
                const data = await res.json();
                
                msg.style.display = 'block';
                if (data.success) {
                    msg.style.background = 'rgba(16, 185, 129, 0.1)';
                    msg.style.color = '#10b981';
                    msg.style.border = '1px solid #10b981';
                    msg.innerHTML = '<i class="fa-solid fa-check-circle"></i> Profile updated successfully!';
                    setTimeout(() => location.reload(), 1500); // Reload to show updated pic/name
                } else {
                    msg.style.background = 'rgba(239, 68, 68, 0.1)';
                    msg.style.color = '#ef4444';
                    msg.style.border = '1px solid #ef4444';
                    msg.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' + (data.error || 'Failed to update');
                }
            } catch (err) {
                msg.style.display = 'block';
                msg.style.background = 'rgba(239, 68, 68, 0.1)';
                msg.style.color = '#ef4444';
                msg.style.border = '1px solid #ef4444';
                msg.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Network error occurred.';
            } finally {
                btn.innerHTML = '<i class="fa-solid fa-save"></i> Save Profile Details';
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
