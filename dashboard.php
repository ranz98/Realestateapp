<?php
require_once 'auth_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Deletion
if (isset($_GET['delete'])) {
    $del = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM apartments WHERE id = ? AND user_id = ?");
    $stmt->execute([$del, $user_id]);
    header("Location: dashboard.php?msg=Listing+Deleted");
    exit;
}

// Fetch user info
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Fetch user's listings
$stmt = $pdo->prepare("SELECT * FROM apartments WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$listings = $stmt->fetchAll();

// Fetch My Bids (bids I have placed)
$myBidsStmt = $pdo->prepare("SELECT b.*, a.title FROM bids b JOIN apartments a ON b.apartment_id = a.id WHERE b.user_id = ? ORDER BY b.created_at DESC");
$myBidsStmt->execute([$user_id]);
$myBids = $myBidsStmt->fetchAll();

// Fetch Incoming Bids (bids on my properties)
$incomingBidsStmt = $pdo->prepare("SELECT b.*, a.title, u.name as bidder_name, u.email as bidder_email FROM bids b JOIN apartments a ON b.apartment_id = a.id JOIN users u ON b.user_id = u.id WHERE a.user_id = ? ORDER BY b.created_at DESC");
$incomingBidsStmt->execute([$user_id]);
$incomingBids = $incomingBidsStmt->fetchAll();

// Stats
$total = count($listings);
$approved = count(array_filter($listings, fn($l) => $l['status'] === 'approved'));
$pending = count(array_filter($listings, fn($l) => $l['status'] === 'pending'));
$rejected = count(array_filter($listings, fn($l) => $l['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MyHomeMyLand.LK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <style>
        .dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 1.2rem; text-align: center; box-shadow: var(--shadow-card, none); }
        .stat-card .stat-number { font-size: 2rem; font-weight: 700; margin-bottom: 0.2rem; }
        .stat-card .stat-label { font-size: 0.85rem; color: var(--text-secondary); }
        .stat-total .stat-number { color: var(--primary); }
        .stat-approved .stat-number { color: #10b981; }
        .stat-pending .stat-number { color: #f59e0b; }
        .stat-rejected .stat-number { color: #ef4444; }
        
        .listing-card { background: var(--bg-card); border: 1px solid var(--border-glass); border-radius: var(--radius-md); overflow: hidden; display: flex; gap: 1rem; align-items: stretch; margin-bottom: 1rem; box-shadow: var(--shadow-card, none); }
        .listing-card-img { width: 140px; min-height: 120px; object-fit: cover; flex-shrink: 0; }
        .listing-card-body { padding: 1rem; flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .listing-card-body h4 { font-size: 1rem; margin-bottom: 0.3rem; }
        .listing-card-body .lc-price { color: var(--primary); font-weight: 600; font-size: 0.95rem; }
        .listing-card-body .lc-meta { font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.3rem; }
        .listing-card-actions { display: flex; gap: 0.5rem; margin-top: 0.6rem; flex-wrap: wrap; }
        
        .badge { padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        
        .btn-sm { padding: 0.3rem 0.7rem; font-size: 0.8rem; border-radius: 4px; text-decoration: none; color: white; display: inline-flex; align-items: center; gap: 0.3rem; cursor: pointer; border: none; }
        .btn-edit { background: #3b82f6; }
        .btn-del { background: #ef4444; }
        .btn-view { background: #10b981; }
        
        .panel-section { background: var(--bg-card); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 1.5rem; margin-top: 1.5rem; box-shadow: var(--shadow-card, none); }
        .panel-section h3 { margin-bottom: 1rem; padding-bottom: 0.6rem; border-bottom: 1px solid var(--border-glass); }

        @media (max-width: 768px) {
            .listing-card { flex-direction: column; }
            .listing-card-img { width: 100%; height: 160px; }
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
                <a href="dashboard.php" class="active">Dashboard</a>
                <?php if($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin.php">Admin</a>
                <?php endif; ?>
                <a href="list-apartment.php" class="btn-primary">List Property</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <div class="page-container">
        <h2>Welcome back, <?= htmlspecialchars($user['name']) ?>!</h2>
        <p style="color: var(--text-secondary);"><?= htmlspecialchars($user['email']) ?> &middot; Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>

        <?php if(isset($_GET['msg'])): ?>
            <div style="background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 0.8rem 1rem; margin: 1rem 0; border-radius: 6px; font-size: 0.9rem;">
                <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="dash-grid">
            <div class="stat-card stat-total">
                <div class="stat-number"><?= $total ?></div>
                <div class="stat-label">Total Listings</div>
            </div>
            <div class="stat-card stat-approved">
                <div class="stat-number"><?= $approved ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-number"><?= $pending ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card stat-rejected">
                <div class="stat-number"><?= $rejected ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <!-- Listings -->
        <div class="panel-section">
            <h3><i class="fa-solid fa-building"></i> Your Listings</h3>
            <?php if($total > 0): ?>
                <?php foreach($listings as $row): 
                    $imgs = [];
                    try { $imgs = json_decode($row['images'], true); } catch(Exception $e) {}
                    $thumb = ($imgs && count($imgs) > 0) ? $imgs[0] : 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=400';
                ?>
                <div class="listing-card">
                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="listing-card-img">
                    <div class="listing-card-body">
                        <h4><?= htmlspecialchars($row['title']) ?></h4>
                        <div class="lc-price">Rs. <?= number_format($row['price']) ?> /month</div>
                        <div class="lc-meta">
                            <?= htmlspecialchars($row['type']) ?> &middot; <?= htmlspecialchars($row['bedrooms']) ?> Bed &middot; <?= (int)$row['baths'] ?> Bath &middot; <?= htmlspecialchars($row['address']) ?>
                        </div>
                        <div class="listing-card-actions">
                            <span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
                            <?php if($row['status'] === 'approved'): ?>
                                <a href="apartment.php?id=<?= $row['id'] ?>" class="btn-sm btn-view"><i class="fa-solid fa-eye"></i> View</a>
                            <?php endif; ?>
                            <?php if($row['status'] !== 'approved'): ?>
                                <a href="edit-apartment.php?id=<?= $row['id'] ?>" class="btn-sm btn-edit"><i class="fa-solid fa-pen"></i> Edit</a>
                            <?php endif; ?>
                            <a href="dashboard.php?delete=<?= $row['id'] ?>" class="btn-sm btn-del" onclick="return confirm('Delete this listing?');"><i class="fa-solid fa-trash"></i> Delete</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                    <i class="fa-solid fa-house-circle-xmark" style="font-size: 2.5rem; margin-bottom: 1rem; display: block;"></i>
                    <p>You haven't listed any properties yet.</p>
                    <a href="list-apartment.php" class="btn-primary" style="margin-top: 1rem; display: inline-flex;">+ Create Your First Listing</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Incoming Bids -->
        <div class="panel-section">
            <h3><i class="fa-solid fa-bell"></i> Incoming Offers</h3>
            <?php if(count($incomingBids) > 0): ?>
                <?php foreach($incomingBids as $bid): ?>
                    <div style="padding: 1rem; border: 1px solid var(--border-glass); border-radius: 8px; margin-bottom: 0.8rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; background: var(--bg-main);">
                        <div>
                            <h4 style="margin-bottom: 0.2rem;"><a href="apartment.php?id=<?= $bid['apartment_id'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($bid['title']) ?></a></h4>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                <i class="fa-solid fa-user"></i> <?= htmlspecialchars($bid['bidder_name']) ?> (<a href="mailto:<?= htmlspecialchars($bid['bidder_email']) ?>" style="color: var(--primary);"><?= htmlspecialchars($bid['bidder_email']) ?></a>) &middot; <i class="fa-solid fa-clock"></i> <?= date('M d, g:i A', strtotime($bid['created_at'])) ?>
                            </div>
                            <?php if(!empty($bid['message'])): ?>
                                <p style="font-size: 0.85rem; margin-top: 0.5rem; font-style: italic; color: var(--text-secondary);">"<?= htmlspecialchars($bid['message']) ?>"</p>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary);">
                            Rs. <?= number_format($bid['amount']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">You have no incoming offers on your properties yet.</p>
            <?php endif; ?>
        </div>

        <!-- My Bids -->
        <div class="panel-section">
            <h3><i class="fa-solid fa-paper-plane"></i> My Offers</h3>
            <?php if(count($myBids) > 0): ?>
                <?php foreach($myBids as $bid): ?>
                    <div style="padding: 1rem; border: 1px solid var(--border-glass); border-radius: 8px; margin-bottom: 0.8rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; background: var(--bg-main);">
                        <div>
                            <h4 style="margin-bottom: 0.2rem;"><a href="apartment.php?id=<?= $bid['apartment_id'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($bid['title']) ?></a></h4>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                <i class="fa-solid fa-clock"></i> Offered on <?= date('M d, Y', strtotime($bid['created_at'])) ?>
                            </div>
                            <?php if(!empty($bid['message'])): ?>
                                <p style="font-size: 0.85rem; margin-top: 0.5rem; font-style: italic; color: var(--text-secondary);">"<?= htmlspecialchars($bid['message']) ?>"</p>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary);">
                            Rs. <?= number_format($bid['amount']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">You haven't made any offers yet.</p>
            <?php endif; ?>
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
