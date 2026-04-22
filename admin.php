<?php
require_once 'auth_check.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'approve_ad' || $action === 'reject_ad') {
            $status = ($action === 'approve_ad') ? 'approved' : 'rejected';
            $ad_id = $_POST['ad_id'];
            $stmt = $pdo->prepare("UPDATE apartments SET status = ? WHERE id = ?");
            $stmt->execute([$status, $ad_id]);
            header("Location: admin.php?msg=Ad+status+updated+to+" . $status);
            exit;
        }
        
        if ($action === 'delete_ad') {
            $ad_id = $_POST['ad_id'];
            $stmt = $pdo->prepare("DELETE FROM apartments WHERE id = ?");
            $stmt->execute([$ad_id]);
            header("Location: admin.php?msg=Ad+deleted");
            exit;
        }

        if ($action === 'update_user') {
            $u_id = $_POST['user_id'];
            $email = $_POST['email'];
            if (!empty($_POST['password'])) {
                $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
                $stmt->execute([$email, $hash, $u_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$email, $u_id]);
            }
            header("Location: admin.php?msg=User+updated");
            exit;
        }

        if ($action === 'delete_user') {
            $u_id = $_POST['user_id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$u_id]);
            header("Location: admin.php?msg=User+deleted");
            exit;
        }
    }
}

// Fetch all ads
$ads_stmt = $pdo->query("SELECT a.*, u.name as user_name FROM apartments a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
$ads = $ads_stmt->fetchAll();

// Fetch all users
$users_stmt = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM apartments WHERE user_id = u.id) as listing_count FROM users u ORDER BY u.created_at DESC");
$users = $users_stmt->fetchAll();

// Stats
$total_ads = count($ads);
$pending_ads = count(array_filter($ads, fn($a) => $a['status'] === 'pending'));
$total_users = count($users);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MyHomeMyLand.LK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="terminal.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <script>try{if(localStorage.getItem('theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}</script>
    <style>
        .admin-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
        .admin-stat { background: var(--bg-card); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 1.2rem; display: flex; align-items: center; gap: 1rem; box-shadow: var(--shadow-card, none); }
        .admin-stat .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .admin-stat .stat-info .stat-num { font-size: 1.5rem; font-weight: 700; }
        .admin-stat .stat-info .stat-lbl { font-size: 0.8rem; color: var(--text-secondary); }
        .icon-ads { background: rgba(79, 70, 229, 0.1); color: var(--primary); }
        .icon-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .icon-users { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        
        .panel-section { background: var(--bg-card); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-card, none); }
        .panel-section h3 { margin-bottom: 1rem; padding-bottom: 0.6rem; border-bottom: 1px solid var(--border-glass); display: flex; align-items: center; gap: 0.5rem; }
        
        .admin-ad-card { display: flex; gap: 1rem; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-glass); flex-wrap: wrap; }
        .admin-ad-card:last-child { border-bottom: none; }
        .admin-ad-img { width: 80px; height: 60px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
        .admin-ad-info { flex: 1; min-width: 150px; }
        .admin-ad-info h4 { font-size: 0.95rem; margin-bottom: 0.2rem; }
        .admin-ad-info .ad-meta { font-size: 0.8rem; color: var(--text-secondary); }
        .admin-ad-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        
        .badge { padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        
        .btn-sm { padding: 0.25rem 0.6rem; font-size: 0.78rem; border-radius: 4px; text-decoration: none; color: white; display: inline-flex; align-items: center; gap: 0.2rem; cursor: pointer; border: none; white-space: nowrap; }
        .btn-approve { background: #10b981; }
        .btn-reject { background: #f59e0b; }
        .btn-delete { background: #ef4444; }
        .btn-update { background: #3b82f6; }
        
        .user-row { display: flex; gap: 1rem; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-glass); flex-wrap: wrap; }
        .user-row:last-child { border-bottom: none; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
        .user-info { flex: 1; min-width: 120px; }
        .user-info h4 { font-size: 0.95rem; margin-bottom: 0.1rem; }
        .user-info p { font-size: 0.8rem; color: var(--text-secondary); }
        .user-actions { display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap; }
        .user-actions input { padding: 0.3rem; font-size: 0.8rem; width: 150px; border: 1px solid var(--border-glass); border-radius: 4px; background: var(--bg-main); color: var(--text-primary); }

        @media (max-width: 768px) {
            .admin-ad-card { flex-direction: column; align-items: flex-start; }
            .admin-ad-img { width: 100%; height: 140px; }
            .user-row { flex-direction: column; align-items: flex-start; }
            .user-actions { width: 100%; }
            .user-actions input { width: 100%; }
        }
    </style>
    <?php include 'get-theme.php'; ?>
</head>
<body>
    <header class="site-header">
        <nav class="navbar">
            <a href="index.php" class="logo" style="text-decoration: none;">
                <i class="fa-solid fa-house-chimney-window"></i> MyHomeMyLand.LK
            </a>
            <div class="nav-links">
                <button id="theme-toggle" class="theme-toggle" title="Toggle Dark/Light Mode">
                    <i class="fa-solid fa-moon"></i>
                    <i class="fa-solid fa-sun" style="display:none;"></i>
                </button>
                <a href="index.php">Explore</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="admin.php" class="active">Admin</a>
                <a href="theme-admin.php">Theme Studio</a>

                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <div class="page-container" style="max-width: 1200px;">
        <h2><i class="fa-solid fa-shield-halved"></i> Admin Dashboard</h2>
        <p style="color: var(--text-secondary);">Manage all listings and users on the platform.</p>

        <?php if(isset($_GET['msg'])): ?>
            <div style="background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 0.8rem 1rem; margin: 1rem 0; border-radius: 6px; font-size: 0.9rem;">
                <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="admin-stats">
            <div class="admin-stat">
                <div class="stat-icon icon-ads"><i class="fa-solid fa-building"></i></div>
                <div class="stat-info">
                    <div class="stat-num"><?= $total_ads ?></div>
                    <div class="stat-lbl">Total Listings</div>
                </div>
            </div>
            <div class="admin-stat">
                <div class="stat-icon icon-pending"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-info">
                    <div class="stat-num"><?= $pending_ads ?></div>
                    <div class="stat-lbl">Pending Approval</div>
                </div>
            </div>
            <div class="admin-stat">
                <div class="stat-icon icon-users"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-num"><?= $total_users ?></div>
                    <div class="stat-lbl">Registered Users</div>
                </div>
            </div>
        </div>

        <!-- Ads Management -->
        <div class="panel-section">
            <h3><i class="fa-solid fa-rectangle-ad"></i> Manage Listings</h3>
            <?php if(count($ads) > 0): ?>
                <?php foreach($ads as $ad): 
                    $imgs = [];
                    try { $imgs = json_decode($ad['images'], true); } catch(Exception $e) {}
                    $thumb = ($imgs && count($imgs) > 0) ? $imgs[0] : 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=400';
                ?>
                <div class="admin-ad-card">
                    <img src="<?= htmlspecialchars($thumb) ?>" alt="" class="admin-ad-img">
                    <div class="admin-ad-info">
                        <h4><?= htmlspecialchars($ad['title']) ?></h4>
                        <div class="ad-meta">
                            By <?= htmlspecialchars($ad['user_name']) ?> &middot; Rs. <?= formatPriceShort($ad['price']) ?> &middot; <?= htmlspecialchars($ad['type']) ?> &middot; <?= date('M d', strtotime($ad['created_at'])) ?>
                        </div>
                        <span class="badge badge-<?= $ad['status'] ?>" style="margin-top:0.3rem;"><?= ucfirst($ad['status']) ?></span>
                    </div>
                    <div class="admin-ad-actions">
                        <?php if($ad['status'] !== 'approved'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="approve_ad">
                            <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                            <button type="submit" class="btn-sm btn-approve"><i class="fa-solid fa-check"></i> Approve</button>
                        </form>
                        <?php endif; ?>
                        <?php if($ad['status'] !== 'rejected'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="reject_ad">
                            <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                            <button type="submit" class="btn-sm btn-reject"><i class="fa-solid fa-ban"></i> Reject</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this ad?');">
                            <input type="hidden" name="action" value="delete_ad">
                            <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                            <button type="submit" class="btn-sm btn-delete"><i class="fa-solid fa-trash"></i> Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--text-secondary); padding: 1rem;">No listings yet.</p>
            <?php endif; ?>
        </div>

        <!-- Users Management -->
        <div class="panel-section">
            <h3><i class="fa-solid fa-users-gear"></i> Manage Users</h3>
            <?php foreach($users as $u): ?>
            <div class="user-row">
                <div class="user-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                <div class="user-info">
                    <h4><?= htmlspecialchars($u['name']) ?> <span class="badge" style="background: <?= $u['role']==='admin' ? 'rgba(79,70,229,0.15)' : 'rgba(100,116,139,0.15)' ?>; color: <?= $u['role']==='admin' ? 'var(--primary)' : 'var(--text-secondary)' ?>;"><?= ucfirst($u['role']) ?></span></h4>
                    <p><?= htmlspecialchars($u['email']) ?> &middot; <?= $u['listing_count'] ?> listings &middot; Joined <?= date('M d, Y', strtotime($u['created_at'])) ?></p>
                </div>
                <div class="user-actions">
                    <form method="POST" style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required>
                        <input type="password" name="password" placeholder="New password">
                        <button type="submit" class="btn-sm btn-update"><i class="fa-solid fa-save"></i> Save</button>
                    </form>
                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                    <form method="POST" onsubmit="return confirm('Delete user and all their listings?');">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn-sm btn-delete"><i class="fa-solid fa-user-xmark"></i> Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
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
    <script src="terminal.js"></script>
</body>
</html>
