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
$myBidsStmt = $pdo->prepare("SELECT b.*, a.title, a.seller_email, a.seller_phone, u.name as seller_name 
                             FROM bids b 
                             JOIN apartments a ON b.apartment_id = a.id 
                             JOIN users u ON a.user_id = u.id 
                             WHERE b.user_id = ? 
                             ORDER BY b.created_at DESC");
$myBidsStmt->execute([$user_id]);
$myBids = $myBidsStmt->fetchAll();

// Fetch Incoming Bids (bids on my properties)
$incomingBidsStmt = $pdo->prepare("SELECT b.*, a.title, u.name as bidder_name, u.email as bidder_email 
                                   FROM bids b 
                                   JOIN apartments a ON b.apartment_id = a.id 
                                   JOIN users u ON b.user_id = u.id 
                                   WHERE a.user_id = ? 
                                   ORDER BY b.created_at DESC");
$incomingBidsStmt->execute([$user_id]);
$incomingBids = $incomingBidsStmt->fetchAll();

$total_views = array_sum(array_column($listings, 'view_count'));
$bids_received_count = count($incomingBids);
$offers_sent_count = count($myBids);
$accepted_count = count(array_filter($incomingBids, fn($b) => $b['status'] === 'accepted'));

// Fetch daily views for Chart (last 7 days)
$viewDataStmt = $pdo->prepare("SELECT view_date, SUM(views) as total_views FROM daily_views WHERE user_id = ? AND view_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY view_date");
$viewDataStmt->execute([$user_id]);
$viewRows = $viewDataStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch daily bids for Chart (last 7 days)
$bidsDataStmt = $pdo->prepare("SELECT DATE(b.created_at) as bid_date, COUNT(*) as total_bids FROM bids b JOIN apartments a ON b.apartment_id = a.id WHERE a.user_id = ? AND DATE(b.created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(b.created_at)");
$bidsDataStmt->execute([$user_id]);
$bidsRows = $bidsDataStmt->fetchAll(PDO::FETCH_ASSOC);

$chart_data = [];
for($i=6; $i>=0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $views = 0; $bids = 0;
    foreach($viewRows as $r) { if ($r['view_date'] === $date) { $views = (int)$r['total_views']; break; } }
    foreach($bidsRows as $r) { if ($r['bid_date'] === $date) { $bids = (int)$r['total_bids']; break; } }
    $chart_data[] = ['date' => date('M d', strtotime($date)), 'views' => $views, 'bids' => $bids];
}
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
    <link rel="stylesheet" href="terminal.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <script>try{if(localStorage.getItem('theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}</script>
    <style>
        :root { --glass: rgba(255, 255, 255, 0.05); --glass-border: rgba(255, 255, 255, 0.1); }
        [data-theme="dark"] { --glass: rgba(0, 0, 0, 0.2); --glass-border: rgba(255, 255, 255, 0.05); }

        .mission-header { margin-bottom: 2rem; }
        .mission-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        
        .mc-card { 
            background: var(--bg-card); 
            border: 1px solid var(--border-glass); 
            border-radius: var(--radius-md); 
            padding: 1.5rem; 
            box-shadow: var(--shadow-card); 
            position: relative; overflow: hidden;
            backdrop-filter: blur(10px);
        }
        .mc-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--primary); opacity: 0.8; }
        .stat-val { font-size: 2.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.2rem; }
        .stat-label { font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; }
        
        .chart-container { height: 250px; margin-top: 1rem; }

        .control-panel { display: flex; flex-direction: column; gap: 2rem; }
        @media (max-width: 600px) { .mission-grid { grid-template-columns: 1fr; } }

        .offer-card { 
            background: var(--glass); border: 1px solid var(--glass-border); border-radius: 12px; padding: 1.2rem; margin-bottom: 1rem;
            display: flex; justify-content: space-between; align-items: center; transition: all 0.3s ease;
        }
        .offer-card:hover { transform: translateY(-3px); border-color: var(--primary); box-shadow: 0 10px 30px rgba(14, 165, 233, 0.1); }
        
        .bidder-info { display: flex; align-items: center; gap: 1rem; }
        .bidder-avatar { width: 45px; height: 45px; border-radius: 12px; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; }
        
        .status-glow-accepted { box-shadow: 0 0 15px rgba(16, 185, 129, 0.2); border-left: 4px solid #10b981; }
        .status-glow-rejected { border-left: 4px solid #ef4444; }
        .status-glow-pending { border-left: 4px solid #f59e0b; }

        .action-hub { display: flex; gap: 0.5rem; }
        .btn-action { padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-whatsapp { background: #25d366; color: white; }
        .btn-email { background: var(--primary); color: white; }
        .btn-phone { background: #6366f1; color: white; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <div class="mission-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 2.2rem; font-weight: 800;">My Dashboard</h2>
                <p style="color: var(--text-secondary);">Welcome, <?= htmlspecialchars($user['name']) ?> &middot; <?= htmlspecialchars($user['email']) ?></p>
            </div>
            <a href="profile.php" class="btn-action btn-email"><i class="fa-solid fa-user-gear"></i> My Account</a>
        </div>

        <!-- Analytics -->
        <div class="mission-grid">
            <div class="mc-card">
                <div class="stat-val"><?= count($listings) ?></div>
                <div class="stat-label">Active Listings</div>
            </div>
            <div class="mc-card">
                <div class="stat-val"><?= number_format($total_views) ?></div>
                <div class="stat-label">Total Views</div>
            </div>
            <div class="mc-card">
                <div class="stat-val"><?= $bids_received_count ?></div>
                <div class="stat-label">Offers Received</div>
            </div>
            <div class="mc-card" style="border-color: #10b981;">
                <div class="stat-val" style="color: #10b981;"><?= $accepted_count ?></div>
                <div class="stat-label">Accepted Offers</div>
            </div>
        </div>

        <div class="mc-card" style="margin-bottom: 2rem; height: 350px;">
            <div class="stat-label" style="margin-bottom: 1rem;">Activity Overview</div>
            <canvas id="activityChart"></canvas>
        </div>

        <!-- System Operations -->
        <div class="control-panel">
            
            <!-- User Listings -->
            <div>
                <h3 style="margin-top: 0;"><i class="fa-solid fa-building"></i> My Active Listings</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                    <?php if(count($listings) > 0): ?>
                        <?php foreach($listings as $row): 
                            $imgs = json_decode($row['images'], true);
                            $thumb = ($imgs && count($imgs) > 0) ? $imgs[0] : 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=400';
                        ?>
                        <div class="offer-card" style="padding: 0.8rem;">
                            <img src="<?= htmlspecialchars($thumb) ?>" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover;">
                            <div style="flex: 1; margin-left: 1.5rem;">
                                <h4 style="margin:0; font-size: 1.1rem;"><?= htmlspecialchars($row['title']) ?></h4>
                                <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                    <span style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fa-solid fa-eye"></i> <?= $row['view_count'] ?> views</span>
                                    <span style="font-size: 0.85rem; color: var(--text-secondary);"><i class="fa-solid fa-info-circle"></i> <?= ucfirst($row['status']) ?></span>
                                    <span style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">Rs. <?= formatPriceShort($row['price']) ?></span>
                                </div>
                            </div>
                            <div class="action-hub">
                                <a href="apartment.php?id=<?= $row['id'] ?>" class="btn-action" style="background: var(--bg-main); color: var(--text-main);"><i class="fa-solid fa-eye"></i></a>
                                <a href="edit-apartment.php?id=<?= $row['id'] ?>" class="btn-action" style="background: var(--bg-main); color: var(--text-main);"><i class="fa-solid fa-pen"></i></a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="offer-card" style="justify-content: center; color: var(--text-secondary);">You have no active listings. <a href="list-apartment.php" style="color: var(--primary); margin-left: 0.5rem;">Create one</a>.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Incoming Offers -->
            <div>
                <h3><i class="fa-solid fa-inbox"></i> Incoming Offers</h3>
                <div id="incoming-bids-container" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                    <!-- Rendered via JS -->
                    <div style="text-align: center; color: var(--text-secondary);"><i class="fa-solid fa-spinner fa-spin"></i> Loading intel...</div>
                </div>
            </div>

            <!-- Outgoing Offers -->
            <div>
                <h3><i class="fa-solid fa-paper-plane"></i> My Sent Offers</h3>
                <div id="outgoing-bids-container" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                    <!-- Rendered via JS -->
                    <div style="text-align: center; color: var(--text-secondary);"><i class="fa-solid fa-spinner fa-spin"></i> Loading ops...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Site Footer (Keep existing) -->
    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> MyHomeMyLand.LK &middot; Dashboard Interface</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script src="terminal.js"></script>
    <script>
        // Chart.js - Mission Activity Pulse
        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($chart_data, 'date')) ?>,
                datasets: [{
                    label: 'Property Views',
                    data: <?= json_encode(array_column($chart_data, 'views')) ?>,
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#0ea5e9'
                }, {
                    label: 'Intel (Bids)',
                    data: <?= json_encode(array_column($chart_data, 'bids')) ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#6366f1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top', labels: { color: getComputedStyle(document.body).getPropertyValue('--text-main').trim(), font: { family: 'Outfit', size: 12 } } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' }, border: { display: false }, ticks: { color: 'var(--text-secondary)' } },
                    x: { grid: { display: false }, ticks: { color: 'var(--text-secondary)' } }
                }
            }
        });

        // Real-Time Bids Engine
        let bidsCache = { incoming: [], outgoing: [] };
        
        const formatNumber = (num) => Number(num).toLocaleString('en-US');
        
        const renderIncoming = (bids) => {
            const container = document.getElementById('incoming-bids-container');
            if (bids.length === 0) {
                container.innerHTML = '<div class="offer-card" style="justify-content: center; color: var(--text-secondary);">No offers received yet.</div>';
                return;
            }
            
            container.innerHTML = bids.map(bid => {
                let actionHtml = '';
                if (bid.status === 'pending') {
                    actionHtml = `
                        <button class="btn-action" style="background:var(--primary);color:white;" onclick="updateBid(${bid.id}, 'accepted')"><i class="fa-solid fa-check"></i> Accept</button>
                        <button class="btn-action" style="background:#f59e0b;color:white;" onclick="counterBid(${bid.id}, ${bid.amount})"><i class="fa-solid fa-arrow-right-arrow-left"></i> Counter</button>
                        <button class="btn-action" style="background:#ef4444;color:white;" onclick="updateBid(${bid.id}, 'rejected')"><i class="fa-solid fa-xmark"></i> Reject</button>
                    `;
                } else if (bid.status === 'accepted') {
                    const wa = bid.bidder_phone ? bid.bidder_phone.replace(/[^0-9]/g, '') : '';
                    actionHtml = `
                        <a href="mailto:${bid.bidder_email}" class="btn-action btn-email" title="Email"><i class="fa-solid fa-envelope"></i></a>
                        ${wa ? `<a href="https://wa.me/${wa}" class="btn-action btn-whatsapp" target="_blank" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>` : ''}
                    `;
                } else if (bid.status === 'countered') {
                    actionHtml = `<span class="badge badge-pending" style="font-size: 0.75rem;">COUNTERED: Rs. ${formatNumber(bid.counter_amount)}</span>`;
                } else {
                    actionHtml = `<span class="badge badge-rejected" style="font-size: 0.75rem;">REJECTED</span>`;
                }

                return `
                    <div class="offer-card status-glow-${bid.status === 'countered' ? 'pending' : bid.status}">
                        <div class="bidder-info">
                            <div class="bidder-avatar">${(bid.bidder_name || 'A')[0].toUpperCase()}</div>
                            <div>
                                <h4 style="margin:0;">${bid.title}</h4>
                                <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                                    From <strong>${bid.bidder_name}</strong> &middot; ${new Date(bid.created_at).toLocaleDateString()}
                                </p>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.3rem; font-weight: 800; color: var(--primary);">Rs. ${formatNumber(bid.amount)}</div>
                            <div class="action-hub" style="margin-top: 0.8rem; justify-content: flex-end;">
                                <a href="apartment.php?id=${bid.apartment_id}" class="btn-action" style="background:var(--bg-main); color:var(--text-main); margin-right: 0.5rem;" title="View Property"><i class="fa-solid fa-eye"></i></a>
                                ${actionHtml}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        };

        const renderOutgoing = (bids) => {
            const container = document.getElementById('outgoing-bids-container');
            if (bids.length === 0) {
                container.innerHTML = '<div class="offer-card" style="justify-content: center; color: var(--text-secondary); font-size: 0.9rem;">You haven\'t made any offers yet.</div>';
                return;
            }

            container.innerHTML = bids.map(bid => {
                let statusHtml = '';
                if (bid.status === 'countered') {
                    statusHtml = `
                        <div style="padding: 0.8rem; background: rgba(245, 158, 11, 0.1); border-radius: 8px; font-size: 0.85rem; margin-top: 0.8rem; border: 1px solid rgba(245, 158, 11, 0.3);">
                            <div style="color: #d97706; font-weight: 700; margin-bottom: 0.4rem;">SELLER COUNTER-OFFER: Rs. ${formatNumber(bid.counter_amount)}</div>
                            ${bid.counter_message ? `<div style="color: var(--text-secondary); margin-bottom: 0.5rem; font-style: italic;">"${bid.counter_message}"</div>` : ''}
                            <div class="action-hub">
                                <button class="btn-action" style="background:var(--primary);color:white;" onclick="acceptCounter(${bid.id}, ${bid.counter_amount})"><i class="fa-solid fa-check"></i> Accept Counter</button>
                                <button class="btn-action" style="background:#ef4444;color:white;" onclick="updateBid(${bid.id}, 'rejected')"><i class="fa-solid fa-xmark"></i> Reject</button>
                            </div>
                        </div>
                    `;
                } else if (bid.status === 'accepted') {
                    statusHtml = `
                        <div style="padding: 0.8rem; background: rgba(16, 185, 129, 0.1); border-radius: 8px; font-size: 0.8rem; margin-top: 0.8rem;">
                            <div style="margin-bottom: 0.5rem; color: #065f46; font-weight: 600;">OFFER ACCEPTED: Contact Seller</div>
                            <div class="action-hub">
                                <a href="mailto:${bid.seller_email}" class="btn-action btn-email" style="flex:1; justify-content: center;"><i class="fa-solid fa-envelope"></i> Email</a>
                                ${bid.seller_phone ? `<a href="tel:${bid.seller_phone}" class="btn-action btn-phone" style="flex:1; justify-content: center;"><i class="fa-solid fa-phone"></i> Call</a>` : ''}
                            </div>
                        </div>
                    `;
                }

                return `
                    <div class="offer-card status-glow-${bid.status === 'countered' ? 'pending' : bid.status}" style="flex-direction: column; align-items: stretch;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h4 style="margin:0; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                                    ${bid.title} 
                                    <a href="apartment.php?id=${bid.apartment_id}" style="color:var(--text-secondary);" title="View Property"><i class="fa-solid fa-eye"></i></a>
                                </h4>
                                <span class="badge badge-${bid.status === 'countered' ? 'pending' : bid.status}" style="margin-top:0.4rem; font-size: 0.7rem;">${bid.status.toUpperCase()}</span>
                            </div>
                            <div style="font-weight: 800; color: var(--primary);">Rs. ${formatNumber(bid.amount)}</div>
                        </div>
                        ${statusHtml}
                    </div>
                `;
            }).join('');
        };

        const fetchBids = async () => {
            try {
                const res = await fetch('api/get_dashboard_bids.php');
                const data = await res.json();
                if (!data.success) return;

                const cacheStr = JSON.stringify(bidsCache);
                const newDataStr = JSON.stringify(data);

                if (cacheStr !== newDataStr && cacheStr !== '{"incoming":[],"outgoing":[]}') {
                    // Detect specific changes for notifications
                    data.outgoing.forEach(newBid => {
                        const oldBid = bidsCache.outgoing.find(b => b.id === newBid.id);
                        if (oldBid) {
                            if (oldBid.status === 'pending' && newBid.status === 'accepted') {
                                Swal.fire({ icon: 'success', title: 'Offer Accepted! 🎉', text: `Your offer for ${newBid.title} was accepted!`, timer: 4000 });
                            } else if (oldBid.status === 'pending' && newBid.status === 'rejected') {
                                Swal.fire({ icon: 'error', title: 'Offer Rejected', text: `Your offer for ${newBid.title} was declined.`, timer: 4000 });
                            } else if (oldBid.status === 'pending' && newBid.status === 'countered') {
                                Swal.fire({ icon: 'info', title: 'Counter Offer Received!', text: `Seller countered your offer for ${newBid.title} at Rs. ${formatNumber(newBid.counter_amount)}`, timer: 6000 });
                            }
                        }
                    });

                    data.incoming.forEach(newBid => {
                        const oldBid = bidsCache.incoming.find(b => b.id === newBid.id);
                        if (!oldBid) {
                            Swal.fire({ icon: 'info', title: 'New Offer Received! 📨', text: `${newBid.bidder_name} made an offer on ${newBid.title}.`, timer: 5000 });
                        }
                    });
                }

                if (cacheStr !== newDataStr) {
                    bidsCache = data;
                    renderIncoming(data.incoming);
                    renderOutgoing(data.outgoing);
                }
            } catch (err) { console.error('Error fetching bids:', err); }
        };

        // Window exposed functions for buttons
        window.updateBid = async (id, status) => {
            if (!await confirmAction(`Are you sure you want to ${status} this offer?`)) return;
            const fd = new FormData(); fd.append('bid_id', id); fd.append('status', status);
            await fetch('api/update_bid_status.php', { method: 'POST', body: fd });
            fetchBids(); // Refresh instantly
        };

        window.counterBid = async (id, originalAmount) => {
            const { value: formValues } = await Swal.fire({
                title: 'Submit Counter Offer',
                html: `
                    <label style="display:block; text-align:left; font-size: 0.9rem; margin-bottom: 0.3rem;">Counter Amount (Rs):</label>
                    <input id="swal-input1" class="swal2-input" type="number" value="${originalAmount}" step="1000" style="margin-top:0;">
                    <br><br>
                    <label style="display:block; text-align:left; font-size: 0.9rem; margin-bottom: 0.3rem;">Message (Optional):</label>
                    <input id="swal-input2" class="swal2-input" type="text" placeholder="e.g. Can't go below this." style="margin-top:0;">
                `,
                focusConfirm: false,
                showCancelButton: true,
                preConfirm: () => {
                    return [
                        document.getElementById('swal-input1').value,
                        document.getElementById('swal-input2').value
                    ]
                }
            });

            if (formValues && formValues[0]) {
                const fd = new FormData();
                fd.append('bid_id', id);
                fd.append('counter_amount', formValues[0]);
                fd.append('counter_message', formValues[1] || '');
                const res = await fetch('api/counter_bid.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Counter offer sent!' });
                    fetchBids();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.error });
                }
            }
        };

        window.acceptCounter = async (id, counterAmount) => {
            // This just updates the bid amount to the counter amount AND sets status to accepted.
            // Wait, we need an endpoint for buyer accepting a counter. 
            // We can just use update_bid_status, but we also need to update 'amount = counter_amount'.
            const fd = new FormData(); fd.append('bid_id', id); fd.append('status', 'accepted'); fd.append('accept_counter', '1');
            const res = await fetch('api/update_bid_status.php', { method: 'POST', body: fd });
            fetchBids();
        };

        const confirmAction = async (msg) => {
            const result = await Swal.fire({ title: 'Confirm', text: msg, icon: 'warning', showCancelButton: true, confirmButtonColor: '#0ea5e9' });
            return result.isConfirmed;
        };

        // Init
        fetchBids();
        setInterval(fetchBids, 5000); // Poll every 5 seconds

    </script>
</body>
</html>
