<?php
require_once 'auth_check.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT a.*, u.name as owner_name, u.email as owner_email FROM apartments a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.status = 'approved'");
$stmt->execute([$id]);
$apt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$apt) {
    header("Location: index.php");
    exit;
}

// Increment view count
$pdo->prepare("UPDATE apartments SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
try {
    $pdo->prepare("INSERT INTO daily_views (apartment_id, user_id, view_date, views) VALUES (?, ?, CURDATE(), 1) ON DUPLICATE KEY UPDATE views = views + 1")->execute([$id, $apt['user_id']]);
} catch (Exception $e) {}

$images = [];
try { $images = json_decode($apt['images'], true); } catch(Exception $e) {}
if (!$images || count($images) === 0) {
    $images = ['https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=800'];
}

$features = $apt['features'] ? explode(',', $apt['features']) : [];

$my_bids = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM bids WHERE apartment_id = ? AND user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $my_bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($apt['title']) ?> - MyHomeMyLand.LK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="terminal.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <script>try{if(localStorage.getItem('theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}</script>
    <style>
        /* ══════════════════════════════════════════
           DETAIL PAGE — REDESIGNED LAYOUT
           ══════════════════════════════════════════ */

        .detail-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem 1.2rem 6rem;
        }

        /* ── Back Link ── */
        .detail-back {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.88rem;
            margin-bottom: 1.2rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        .detail-back:hover { color: var(--primary); }

        /* ═══════════════════════
           GALLERY
           ═══════════════════════ */
        .gallery-wrapper { position: relative; margin-bottom: 1.5rem; }
        .gallery {
            display: grid;
            grid-template-columns: 1.8fr 1fr;
            grid-template-rows: 200px 200px;
            gap: 6px;
            border-radius: 16px;
            overflow: hidden;
        }
        .gallery-item { overflow: hidden; cursor: pointer; position: relative; }
        .gallery-item img {
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }
        .gallery-item:hover img { transform: scale(1.04); }
        .gallery-item.main { grid-row: 1 / -1; }
        .gallery-count {
            position: absolute;
            bottom: 14px; right: 14px;
            background: rgba(0,0,0,0.65);
            color: #fff;
            padding: 0.35rem 0.8rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            backdrop-filter: blur(6px);
            pointer-events: none;
        }

        /* ═══════════════════════
           MAIN LAYOUT — TWO COL
           ═══════════════════════ */
        .detail-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 1.5rem;
            align-items: start;
        }

        /* ── Left Column ── */
        .detail-left {}

        /* ── Header ── */
        .detail-header { margin-bottom: 1.5rem; }
        .detail-header h1 {
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 0.4rem;
            letter-spacing: -0.01em;
        }
        .detail-address {
            color: var(--text-secondary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .detail-price-row {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin-top: 0.6rem;
        }
        .detail-price {
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.02em;
        }
        .detail-price-suffix {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        /* ── Tags ── */
        .detail-tags {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
            margin-top: 0.8rem;
        }
        .detail-tag {
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            padding: 0.28rem 0.7rem;
            border-radius: 50px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .detail-tag.tag-mode {
            background: var(--primary);
            color: white;
            border: none;
            font-weight: 600;
        }

        /* ── Stat Ribbon ── */
        .stat-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-card);
        }
        .stat-cell {
            padding: 1rem;
            text-align: center;
            border-right: 1px solid var(--border-glass);
        }
        .stat-cell:last-child { border-right: none; }
        .stat-cell .stat-val {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            display: block;
        }
        .stat-cell .stat-lbl {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-secondary);
            font-weight: 500;
            margin-top: 0.15rem;
            display: block;
        }

        /* ── Section Cards ── */
        .detail-section {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 14px;
            padding: 1.4rem;
            box-shadow: var(--shadow-card);
            margin-bottom: 1rem;
        }
        .detail-section h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.9rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .detail-section h3 i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        /* ── Spec Grid ── */
        .spec-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.9rem;
        }
        .spec-item { display: flex; flex-direction: column; gap: 0.15rem; }
        .spec-label {
            font-size: 0.72rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .spec-value { font-size: 0.92rem; font-weight: 600; color: var(--text-primary); }

        /* ── Features ── */
        .feature-list { display: flex; flex-wrap: wrap; gap: 0.4rem; }
        .feature-chip {
            background: rgba(79, 70, 229, 0.08);
            color: var(--primary);
            border: 1px solid rgba(79, 70, 229, 0.15);
            padding: 0.28rem 0.75rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
        }

        /* ── Description ── */
        .description-text {
            line-height: 1.75;
            color: var(--text-secondary);
            font-size: 0.92rem;
        }

        /* ── Map ── */
        .detail-map {
            height: 260px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-glass);
            margin-top: 0.5rem;
        }

        /* ── Owner Card ── */
        .owner-row {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
        }
        .owner-avatar {
            width: 44px; height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .owner-info h4 { font-size: 0.92rem; font-weight: 600; margin-bottom: 0; }
        .owner-info p { font-size: 0.78rem; color: var(--text-secondary); margin: 0; }
        .contact-btns {
            display: flex;
            gap: 0.4rem;
        }
        .contact-btns a {
            flex: 1;
            text-align: center;
            text-decoration: none;
            font-size: 0.82rem;
            padding: 0.55rem 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            transition: all 0.2s;
        }
        .contact-btns .btn-email {
            background: var(--primary);
            color: #fff;
        }
        .contact-btns .btn-email:hover { opacity: 0.9; }
        .contact-btns .btn-phone {
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
        }
        .contact-btns .btn-phone:hover { border-color: var(--primary); color: var(--primary); }
        .contact-btns .btn-whatsapp {
            background: #25d366;
            color: #fff;
        }
        .contact-btns .btn-whatsapp:hover { opacity: 0.9; }

        /* ═══════════════════════════════════════════
           RIGHT COLUMN — STICKY OFFER SIDEBAR
           ═══════════════════════════════════════════ */
        .detail-right {
            position: sticky;
            top: 90px;
        }

        /* ── Offer Card ── */
        .offer-card {
            background: var(--bg-card);
            border: 2px solid var(--primary);
            border-radius: 16px;
            padding: 1.4rem;
            box-shadow: 0 8px 30px rgba(79, 70, 229, 0.10);
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
        }
        .offer-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), #7c3aed, #ec4899);
        }
        .offer-card h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .offer-card h3 i { color: var(--primary); }
        .offer-card .offer-subtitle {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        .offer-card .offer-price-display {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0.8rem 0 0.3rem;
            letter-spacing: -0.02em;
        }
        .offer-card .offer-price-display small {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            display: block;
            margin-bottom: 0.3rem;
        }
        .offer-slider-wrap {
            padding: 0 0.2rem;
            margin-bottom: 0.8rem;
        }
        .offer-slider-wrap input[type="range"] {
            width: 100%;
            accent-color: var(--primary);
            height: 6px;
            cursor: pointer;
        }
        .offer-slider-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-top: 0.2rem;
        }
        .offer-msg-input {
            width: 100%;
            padding: 0.65rem 0.8rem;
            border-radius: 10px;
            border: 1px solid var(--border-glass);
            font-family: inherit;
            font-size: 0.85rem;
            margin-bottom: 0.8rem;
            background: var(--bg-main);
            color: var(--text-primary);
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .offer-msg-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .offer-submit-btn {
            width: 100%;
            padding: 0.75rem;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: white;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        .offer-submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }
        .offer-submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        #bid-feedback {
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.6rem;
        }

        /* ── Quick Stats in Sidebar ── */
        .sidebar-price-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 14px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-card);
        }
        .sidebar-price-card .price-big {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.02em;
        }
        .sidebar-price-card .price-suffix {
            font-size: 0.82rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .sidebar-price-card .price-meta {
            display: flex;
            gap: 0.8rem;
            margin-top: 0.6rem;
            padding-top: 0.6rem;
            border-top: 1px solid var(--border-glass);
        }
        .sidebar-price-card .price-meta span {
            font-size: 0.8rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .sidebar-price-card .price-meta span i { color: var(--primary); font-size: 0.75rem; }

        /* ── Bid History ── */
        .bid-history { margin-top: 0.8rem; padding-top: 0.8rem; border-top: 1px dashed var(--border-glass); }
        .bid-history h4 {
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: var(--text-secondary);
        }
        .bid-history-item {
            background: var(--bg-main);
            padding: 0.65rem 0.8rem;
            border-radius: 10px;
            margin-bottom: 0.4rem;
            border-left: 3px solid var(--primary);
        }
        .bid-history-item.status-accepted { border-left-color: #10b981; }
        .bid-history-item.status-rejected { border-left-color: #ef4444; }
        .bid-history-item .bid-h-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bid-h-amount { font-weight: 700; font-size: 0.88rem; }
        .bid-h-status {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.15rem 0.5rem;
            border-radius: 50px;
        }
        .bid-h-status.s-pending { background: rgba(79, 70, 229, 0.1); color: var(--primary); }
        .bid-h-status.s-accepted { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .bid-h-status.s-rejected { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .bid-h-date { font-size: 0.72rem; color: var(--text-secondary); margin-top: 0.15rem; }
        .bid-h-accepted-msg {
            margin-top: 0.4rem;
            padding-top: 0.4rem;
            border-top: 1px solid rgba(0,0,0,0.05);
            font-size: 0.78rem;
            color: #065f46;
            font-weight: 500;
        }

        /* ── Login Prompt in Sidebar ── */
        .login-prompt {
            text-align: center;
            padding: 1.5rem 0.5rem;
        }
        .login-prompt i {
            font-size: 1.8rem;
            color: var(--border-glass);
            margin-bottom: 0.6rem;
            display: block;
        }
        .login-prompt p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.8rem;
        }
        .login-prompt a {
            display: inline-block;
            padding: 0.55rem 1.5rem;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.88rem;
        }

        /* ═══════════════════════════════════════════
           MOBILE STICKY BOTTOM BAR
           ═══════════════════════════════════════════ */
        .mobile-offer-bar {
            display: none;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: var(--bg-card, #fff);
            border-top: 1px solid var(--border-glass);
            padding: 0.7rem 1rem;
            z-index: 1000;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
            align-items: center;
            gap: 0.8rem;
        }
        .mobile-offer-bar .mob-price {
            flex: 1;
        }
        .mobile-offer-bar .mob-price .mob-price-val {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary);
        }
        .mobile-offer-bar .mob-price .mob-price-suf {
            font-size: 0.72rem;
            color: var(--text-secondary);
            display: block;
        }
        .mobile-offer-bar .mob-offer-btn {
            padding: 0.65rem 1.4rem;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: white;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 3px 12px rgba(79, 70, 229, 0.3);
        }
        .mobile-offer-bar .mob-contact-btn {
            padding: 0.65rem;
            border-radius: 10px;
            border: 1px solid var(--border-glass);
            background: var(--bg-main);
            color: var(--text-primary);
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ═══════════════════════════════════════════
           MOBILE OFFER DRAWER
           ═══════════════════════════════════════════ */
        .offer-drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease;
        }
        .offer-drawer-overlay.active { display: block; }
        .offer-drawer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: var(--bg-card, #fff);
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
            z-index: 2001;
            transform: translateY(100%);
            transition: transform 0.35s cubic-bezier(0.32, 0.72, 0, 1);
            max-height: 85vh;
            overflow-y: auto;
        }
        .offer-drawer.active { transform: translateY(0); }
        .offer-drawer .drawer-handle {
            width: 40px; height: 4px;
            background: var(--border-glass);
            border-radius: 4px;
            margin: 0 auto 1rem;
        }
        .offer-drawer h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .offer-drawer h3 i { color: var(--primary); }

        /* ═══════════════════════
           LIGHTBOX
           ═══════════════════════ */
        .lightbox {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100dvh;
            background: rgba(0,0,0,0.92);
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }
        .lightbox.active { display: flex; animation: fadeIn 0.2s ease; }
        .lightbox img {
            max-width: 92%;
            max-height: 90vh;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .lightbox-close {
            position: absolute;
            top: 16px; right: 20px;
            color: white;
            font-size: 2rem;
            background: rgba(255,255,255,0.1);
            border: none;
            cursor: pointer;
            z-index: 3010;
            width: 44px; height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .lightbox-close:hover { background: rgba(255,255,255,0.2); }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 1.5rem;
            background: rgba(255,255,255,0.1);
            border: none;
            cursor: pointer;
            width: 48px; height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .lightbox-nav:hover { background: rgba(255,255,255,0.2); }
        .lightbox-nav.prev { left: 16px; }
        .lightbox-nav.next { right: 16px; }
        .lightbox-counter {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
            font-weight: 500;
        }

        @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }

        /* ═══════════════════════
           RESPONSIVE
           ═══════════════════════ */
        @media (max-width: 900px) {
            .detail-layout {
                grid-template-columns: 1fr;
            }
            .detail-right {
                position: static;
                display: none; /* hidden on mobile — replaced by bottom bar + drawer */
            }
            .mobile-offer-bar { display: flex; }
            .detail-page { padding-bottom: 5rem; }
        }

        @media (max-width: 640px) {
            .gallery {
                grid-template-columns: 1fr;
                grid-template-rows: 220px;
            }
            .gallery-item.main { grid-row: auto; }
            .gallery-item.side { display: none; }
            .gallery-count { bottom: 10px; right: 10px; }
            .detail-header h1 { font-size: 1.3rem; }
            .detail-price { font-size: 1.4rem; }
            .stat-ribbon { grid-template-columns: repeat(2, 1fr); }
            .stat-cell { border-bottom: 1px solid var(--border-glass); }
            .spec-grid { grid-template-columns: 1fr; }
            .detail-page { padding: 1rem 0.8rem 5rem; }
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
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <?php if($_SESSION['user_role'] === 'admin'): ?>
                        <a href="admin.php">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="detail-page">
        <a href="index.php" class="detail-back"><i class="fa-solid fa-arrow-left"></i> Back to listings</a>

        <!-- ═══ GALLERY ═══ -->
        <div class="gallery-wrapper">
            <div class="gallery">
                <div class="gallery-item main">
                    <img src="<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($apt['title']) ?>" data-idx="0">
                </div>
                <?php if(count($images) > 1): ?>
                    <?php for($i = 1; $i < min(count($images), 3); $i++): ?>
                        <div class="gallery-item side">
                            <img src="<?= htmlspecialchars($images[$i]) ?>" alt="Photo <?= $i+1 ?>" data-idx="<?= $i ?>">
                        </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
            <?php if(count($images) > 1): ?>
                <div class="gallery-count"><i class="fa-solid fa-images"></i> <?= count($images) ?> Photos</div>
            <?php endif; ?>
        </div>

        <!-- ═══ TWO-COLUMN LAYOUT ═══ -->
        <div class="detail-layout">

            <!-- ── LEFT COLUMN ── -->
            <div class="detail-left">

                <!-- Header -->
                <div class="detail-header">
                    <h1><?= htmlspecialchars($apt['title']) ?></h1>
                    <div class="detail-address"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($apt['address']) ?></div>
                    <div class="detail-price-row">
                        <span class="detail-price">Rs. <?= number_format($apt['price']) ?></span>
                        <span class="detail-price-suffix"><?= $apt['listing_mode'] === 'Buy' ? 'Total Price' : '/ month' ?></span>
                    </div>
                    <div class="detail-tags">
                        <span class="detail-tag tag-mode"><i class="fa-solid fa-tag"></i> For <?= htmlspecialchars($apt['listing_mode'] ?? 'Rent') ?></span>
                        <span class="detail-tag"><i class="fa-solid fa-building"></i> <?= htmlspecialchars($apt['type']) ?></span>
                        <span class="detail-tag"><i class="fa-solid fa-clock"></i> <?= date('M d, Y', strtotime($apt['created_at'])) ?></span>
                        <span class="detail-tag"><i class="fa-solid fa-eye"></i> <?= number_format($apt['view_count'] ?? 0) ?> views</span>
                    </div>
                </div>

                <!-- Stat Ribbon -->
                <?php if($apt['type'] !== 'Land'): ?>
                <div class="stat-ribbon">
                    <div class="stat-cell">
                        <span class="stat-val"><?= htmlspecialchars($apt['bedrooms']) ?></span>
                        <span class="stat-lbl">Bedrooms</span>
                    </div>
                    <div class="stat-cell">
                        <span class="stat-val"><?= (int)$apt['baths'] ?></span>
                        <span class="stat-lbl">Bathrooms</span>
                    </div>
                    <div class="stat-cell">
                        <span class="stat-val"><?= $apt['size_sqft'] > 0 ? number_format($apt['size_sqft']) : 'N/A' ?></span>
                        <span class="stat-lbl">Sqft</span>
                    </div>
                    <div class="stat-cell">
                        <span class="stat-val"><?= htmlspecialchars($apt['completion_status'] ?: 'Ready') ?></span>
                        <span class="stat-lbl">Status</span>
                    </div>
                    <div class="stat-cell">
                        <span class="stat-val"><?= htmlspecialchars($apt['furnished_status'] ?: 'N/A') ?></span>
                        <span class="stat-lbl">Furnished</span>
                    </div>
                </div>
                <?php else: ?>
                <div class="stat-ribbon">
                    <div class="stat-cell">
                        <span class="stat-val"><?= (float)$apt['size_perches'] ?></span>
                        <span class="stat-lbl">Perches</span>
                    </div>
                    <div class="stat-cell">
                        <span class="stat-val">Land</span>
                        <span class="stat-lbl">Type</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Description -->
                <div class="detail-section">
                    <h3><i class="fa-solid fa-file-lines"></i> Description</h3>
                    <div class="description-text"><?= nl2br(htmlspecialchars($apt['description'])) ?></div>
                </div>

                <!-- Property Details -->
                <div class="detail-section">
                    <h3><i class="fa-solid fa-circle-info"></i> Property Details</h3>
                    <div class="spec-grid">
                        <div class="spec-item">
                            <span class="spec-label">Property Type</span>
                            <span class="spec-value"><?= htmlspecialchars($apt['type']) ?></span>
                        </div>
                        <?php if($apt['type'] !== 'Land'): ?>
                            <div class="spec-item">
                                <span class="spec-label">Bedrooms</span>
                                <span class="spec-value"><?= htmlspecialchars($apt['bedrooms']) ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Bathrooms</span>
                                <span class="spec-value"><?= (int)$apt['baths'] ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Size</span>
                                <span class="spec-value"><?= $apt['size_sqft'] > 0 ? number_format($apt['size_sqft']) . ' sqft' : 'N/A' ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Completion Status</span>
                                <span class="spec-value"><?= htmlspecialchars($apt['completion_status'] ?: 'Ready') ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Furnished Status</span>
                                <span class="spec-value"><?= htmlspecialchars($apt['furnished_status'] ?: 'Unfurnished') ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Apartment Complex</span>
                                <span class="spec-value"><?= htmlspecialchars($apt['apartment_complex'] ?: 'N/A') ?></span>
                            </div>
                        <?php else: ?>
                            <div class="spec-item">
                                <span class="spec-label">Size</span>
                                <span class="spec-value"><?= (float)$apt['size_perches'] ?> Perches</span>
                            </div>
                        <?php endif; ?>
                        <div class="spec-item">
                            <span class="spec-label">Address</span>
                            <span class="spec-value"><?= htmlspecialchars($apt['address']) ?></span>
                        </div>
                    </div>

                    <?php if(count($features) > 0): ?>
                    <h3 style="margin-top: 1.3rem;"><i class="fa-solid fa-star"></i> Features & Amenities</h3>
                    <div class="feature-list">
                        <?php foreach($features as $f): ?>
                            <span class="feature-chip"><?= htmlspecialchars(trim($f)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Location Map -->
                <div class="detail-section">
                    <h3><i class="fa-solid fa-map-pin"></i> Location</h3>
                    <div id="detail-map" class="detail-map"></div>
                </div>

                <!-- Contact (visible on mobile since sidebar is hidden) -->
                <div class="detail-section" style="display: none;" id="mobile-contact-section">
                    <h3><i class="fa-solid fa-user"></i> Listed By</h3>
                    <div class="owner-row">
                        <div class="owner-avatar"><?= strtoupper(substr($apt['owner_name'], 0, 1)) ?></div>
                        <div class="owner-info">
                            <h4><?= htmlspecialchars($apt['owner_name']) ?></h4>
                            <p>Verified Lister</p>
                        </div>
                    </div>
                    <div class="contact-btns">
                        <?php $sEmail = $apt['seller_email'] ?: $apt['owner_email']; ?>
                        <a href="mailto:<?= htmlspecialchars($sEmail) ?>" class="btn-email"><i class="fa-solid fa-envelope"></i> Email</a>
                        <?php if(!empty($apt['seller_phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($apt['seller_phone']) ?>" class="btn-phone"><i class="fa-solid fa-phone"></i> Call</a>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $apt['seller_phone']) ?>" target="_blank" class="btn-whatsapp"><i class="fa-brands fa-whatsapp"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ═══ RIGHT COLUMN — STICKY SIDEBAR ═══ -->
            <div class="detail-right">

                <!-- Price Summary Card -->
                <div class="sidebar-price-card">
                    <div class="price-big">Rs. <?= number_format($apt['price']) ?></div>
                    <div class="price-suffix"><?= $apt['listing_mode'] === 'Buy' ? 'Total Price' : 'per month' ?></div>
                    <div class="price-meta">
                        <?php if($apt['type'] !== 'Land' && $apt['size_sqft'] > 0): ?>
                            <span><i class="fa-solid fa-ruler-combined"></i> Rs. <?= number_format(round($apt['price'] / $apt['size_sqft'])) ?>/sqft</span>
                        <?php endif; ?>
                        <span><i class="fa-solid fa-eye"></i> <?= number_format($apt['view_count'] ?? 0) ?> views</span>
                    </div>
                </div>

                <!-- Contact Card -->
                <div class="detail-section">
                    <h3><i class="fa-solid fa-user"></i> Listed By</h3>
                    <div class="owner-row">
                        <div class="owner-avatar"><?= strtoupper(substr($apt['owner_name'], 0, 1)) ?></div>
                        <div class="owner-info">
                            <h4><?= htmlspecialchars($apt['owner_name']) ?></h4>
                            <p>Verified Lister</p>
                        </div>
                    </div>
                    <div class="contact-btns">
                        <?php $sEmail = $apt['seller_email'] ?: $apt['owner_email']; ?>
                        <a href="mailto:<?= htmlspecialchars($sEmail) ?>" class="btn-email"><i class="fa-solid fa-envelope"></i> Email</a>
                        <?php if(!empty($apt['seller_phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($apt['seller_phone']) ?>" class="btn-phone"><i class="fa-solid fa-phone"></i> Call</a>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $apt['seller_phone']) ?>" target="_blank" class="btn-whatsapp"><i class="fa-brands fa-whatsapp"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Offer / Bid Card -->
                <div class="offer-card">
                    <h3><i class="fa-solid fa-gavel"></i> Make an Offer</h3>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['user_id'] == $apt['user_id']): ?>
                            <p style="color: var(--text-secondary); text-align: center; padding: 1rem 0; font-size: 0.88rem;">You cannot bid on your own listing.</p>
                        <?php else: ?>
                            <p class="offer-subtitle">Set your price and send directly to the seller.</p>
                            <div class="offer-slider-wrap">
                                <input type="range" id="bid-slider"
                                       min="<?= (int)$apt['price'] * 0.7 ?>"
                                       max="<?= (int)$apt['price'] * 1.5 ?>"
                                       step="5000"
                                       value="<?= (int)$apt['price'] ?>">
                                <div class="offer-slider-labels">
                                    <span>Rs. <?= number_format((int)$apt['price'] * 0.7) ?></span>
                                    <span>Rs. <?= number_format((int)$apt['price'] * 1.5) ?></span>
                                </div>
                            </div>
                            <div class="offer-price-display">
                                <small>Your Offer</small>
                                Rs. <span id="bid-amount-display"><?= number_format($apt['price']) ?></span>
                            </div>
                            <input type="text" id="bid-message" class="offer-msg-input" placeholder="Message to seller (optional)...">
                            <button id="submit-bid-btn" class="offer-submit-btn" data-apt="<?= $id ?>">
                                <i class="fa-solid fa-paper-plane"></i> Submit Offer
                            </button>
                            <div id="bid-feedback" style="display: none;"></div>

                            <?php if(count($my_bids) > 0): ?>
                                <div class="bid-history">
                                    <h4><i class="fa-solid fa-clock-rotate-left"></i> Your Offers</h4>
                                    <?php foreach($my_bids as $bid): ?>
                                        <div class="bid-history-item status-<?= $bid['status'] ?>">
                                            <div class="bid-h-top">
                                                <span class="bid-h-amount">Rs. <?= number_format($bid['amount']) ?></span>
                                                <span class="bid-h-status s-<?= $bid['status'] ?>"><?= $bid['status'] ?></span>
                                            </div>
                                            <div class="bid-h-date"><?= date('M d, Y', strtotime($bid['created_at'])) ?></div>
                                            <?php if($bid['status'] === 'accepted'): ?>
                                                <div class="bid-h-accepted-msg">
                                                    <i class="fa-solid fa-check-circle"></i> Accepted! Contact seller above.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="login-prompt">
                            <i class="fa-solid fa-lock"></i>
                            <p>Log in to place an offer on this property.</p>
                            <a href="login.php">Login to Bid</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ MOBILE STICKY BOTTOM BAR ═══ -->
    <div class="mobile-offer-bar" id="mobile-offer-bar">
        <div class="mob-price">
            <span class="mob-price-val">Rs. <?= number_format($apt['price']) ?></span>
            <span class="mob-price-suf"><?= $apt['listing_mode'] === 'Buy' ? 'Total' : '/ month' ?></span>
        </div>
        <?php if(!empty($apt['seller_phone'])): ?>
            <a href="tel:<?= htmlspecialchars($apt['seller_phone']) ?>" class="mob-contact-btn"><i class="fa-solid fa-phone"></i></a>
        <?php endif; ?>
        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $apt['user_id']): ?>
            <button class="mob-offer-btn" id="mob-offer-trigger"><i class="fa-solid fa-gavel"></i> Make Offer</button>
        <?php elseif(!isset($_SESSION['user_id'])): ?>
            <a href="login.php" class="mob-offer-btn" style="text-decoration:none;">Login to Bid</a>
        <?php endif; ?>
    </div>

    <!-- ═══ MOBILE OFFER DRAWER ═══ -->
    <div class="offer-drawer-overlay" id="drawer-overlay"></div>
    <div class="offer-drawer" id="offer-drawer">
        <div class="drawer-handle"></div>
        <h3><i class="fa-solid fa-gavel"></i> Make an Offer</h3>
        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $apt['user_id']): ?>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">Slide to set your price for this property.</p>
            <div class="offer-slider-wrap">
                <input type="range" id="mob-bid-slider"
                       min="<?= (int)$apt['price'] * 0.7 ?>"
                       max="<?= (int)$apt['price'] * 1.5 ?>"
                       step="5000"
                       value="<?= (int)$apt['price'] ?>">
                <div class="offer-slider-labels">
                    <span>Rs. <?= number_format((int)$apt['price'] * 0.7) ?></span>
                    <span>Rs. <?= number_format((int)$apt['price'] * 1.5) ?></span>
                </div>
            </div>
            <div class="offer-price-display" style="text-align:center; font-size:1.6rem; font-weight:800; color:var(--primary); margin:0.6rem 0;">
                Rs. <span id="mob-bid-amount-display"><?= number_format($apt['price']) ?></span>
            </div>
            <input type="text" id="mob-bid-message" class="offer-msg-input" placeholder="Message to seller (optional)...">
            <button id="mob-submit-bid-btn" class="offer-submit-btn" data-apt="<?= $id ?>">
                <i class="fa-solid fa-paper-plane"></i> Submit Offer
            </button>
            <div id="mob-bid-feedback" style="display: none; text-align:center; font-size:0.85rem; font-weight:600; margin-top:0.6rem;"></div>

            <?php if(count($my_bids) > 0): ?>
                <div class="bid-history">
                    <h4><i class="fa-solid fa-clock-rotate-left"></i> Your Offers</h4>
                    <?php foreach($my_bids as $bid): ?>
                        <div class="bid-history-item status-<?= $bid['status'] ?>">
                            <div class="bid-h-top">
                                <span class="bid-h-amount">Rs. <?= number_format($bid['amount']) ?></span>
                                <span class="bid-h-status s-<?= $bid['status'] ?>"><?= $bid['status'] ?></span>
                            </div>
                            <div class="bid-h-date"><?= date('M d, Y', strtotime($bid['created_at'])) ?></div>
                            <?php if($bid['status'] === 'accepted'): ?>
                                <div class="bid-h-accepted-msg"><i class="fa-solid fa-check-circle"></i> Accepted! Contact seller directly.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ═══ LIGHTBOX ═══ -->
    <div class="lightbox" id="lightbox">
        <button class="lightbox-close" id="lightbox-close"><i class="fa-solid fa-xmark"></i></button>
        <button class="lightbox-nav prev" id="lb-prev"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="lightbox-nav next" id="lb-next"><i class="fa-solid fa-chevron-right"></i></button>
        <img src="" alt="Fullscreen image" id="lightbox-img">
        <div class="lightbox-counter" id="lb-counter"></div>
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="script.js"></script>
    <script src="terminal.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // ══════════════════════
            // MAP
            // ══════════════════════
            const lat = <?= (float)$apt['lat'] ?>;
            const lng = <?= (float)$apt['lng'] ?>;
            const map = L.map('detail-map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            L.marker([lat, lng]).addTo(map)
                .bindPopup('<b><?= addslashes(htmlspecialchars($apt['title'])) ?></b><br><?= addslashes(htmlspecialchars($apt['address'])) ?>')
                .openPopup();

            // ══════════════════════
            // LIGHTBOX with nav
            // ══════════════════════
            const allImages = <?= json_encode($images) ?>;
            let lbIndex = 0;
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightbox-img');
            const lbCounter = document.getElementById('lb-counter');

            function openLightbox(idx) {
                lbIndex = idx;
                lightboxImg.src = allImages[lbIndex];
                lbCounter.textContent = (lbIndex + 1) + ' / ' + allImages.length;
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            function closeLightbox() {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }

            document.querySelectorAll('.gallery-item img').forEach(img => {
                img.addEventListener('click', () => openLightbox(parseInt(img.dataset.idx) || 0));
            });
            document.getElementById('lightbox-close').addEventListener('click', closeLightbox);
            lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });
            document.getElementById('lb-prev').addEventListener('click', (e) => {
                e.stopPropagation();
                lbIndex = (lbIndex - 1 + allImages.length) % allImages.length;
                lightboxImg.src = allImages[lbIndex];
                lbCounter.textContent = (lbIndex + 1) + ' / ' + allImages.length;
            });
            document.getElementById('lb-next').addEventListener('click', (e) => {
                e.stopPropagation();
                lbIndex = (lbIndex + 1) % allImages.length;
                lightboxImg.src = allImages[lbIndex];
                lbCounter.textContent = (lbIndex + 1) + ' / ' + allImages.length;
            });
            document.addEventListener('keydown', (e) => {
                if (!lightbox.classList.contains('active')) return;
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') document.getElementById('lb-prev').click();
                if (e.key === 'ArrowRight') document.getElementById('lb-next').click();
            });

            // ══════════════════════
            // MOBILE CONTACT SECTION
            // ══════════════════════
            const mobileContact = document.getElementById('mobile-contact-section');
            function checkMobile() {
                if (window.innerWidth <= 900 && mobileContact) mobileContact.style.display = 'block';
                else if (mobileContact) mobileContact.style.display = 'none';
            }
            checkMobile();
            window.addEventListener('resize', checkMobile);

            // ══════════════════════
            // MOBILE OFFER DRAWER
            // ══════════════════════
            const drawerOverlay = document.getElementById('drawer-overlay');
            const drawer = document.getElementById('offer-drawer');
            const mobTrigger = document.getElementById('mob-offer-trigger');

            function openDrawer() {
                drawerOverlay.classList.add('active');
                drawer.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            function closeDrawer() {
                drawerOverlay.classList.remove('active');
                drawer.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (mobTrigger) mobTrigger.addEventListener('click', openDrawer);
            if (drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);

            // ══════════════════════
            // BID LOGIC — DESKTOP
            // ══════════════════════
            function setupBid(sliderId, displayId, submitId, messageId, feedbackId) {
                const slider = document.getElementById(sliderId);
                const display = document.getElementById(displayId);
                const submitBtn = document.getElementById(submitId);
                const msgInput = document.getElementById(messageId);
                const feedback = document.getElementById(feedbackId);

                if (!slider || !display || !submitBtn) return;

                slider.addEventListener('input', () => {
                    display.innerText = Number(slider.value).toLocaleString();
                });

                submitBtn.addEventListener('click', async () => {
                    const amount = slider.value;
                    const message = msgInput ? msgInput.value : '';
                    const aptId = submitBtn.dataset.apt;

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

                    try {
                        const fd = new FormData();
                        fd.append('apartment_id', aptId);
                        fd.append('amount', amount);
                        fd.append('message', message);

                        const res = await fetch('api/place_bid.php', { method: 'POST', body: fd });
                        const data = await res.json();

                        feedback.style.display = 'block';
                        if (data.success) {
                            feedback.style.color = '#10b981';
                            feedback.innerText = 'Offer submitted successfully!';
                            submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Offer Sent';
                            submitBtn.style.background = '#10b981';
                            submitBtn.style.boxShadow = '0 4px 15px rgba(16,185,129,0.3)';
                        } else {
                            feedback.style.color = '#ef4444';
                            feedback.innerText = data.error || 'Failed to submit offer.';
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Offer';
                        }
                    } catch (e) {
                        feedback.style.display = 'block';
                        feedback.style.color = '#ef4444';
                        feedback.innerText = 'Network error. Try again.';
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Offer';
                    }
                });
            }

            // Desktop bid
            setupBid('bid-slider', 'bid-amount-display', 'submit-bid-btn', 'bid-message', 'bid-feedback');
            // Mobile bid
            setupBid('mob-bid-slider', 'mob-bid-amount-display', 'mob-submit-bid-btn', 'mob-bid-message', 'mob-bid-feedback');

            // Sync sliders if both exist
            const deskSlider = document.getElementById('bid-slider');
            const mobSlider = document.getElementById('mob-bid-slider');
            if (deskSlider && mobSlider) {
                deskSlider.addEventListener('input', () => {
                    mobSlider.value = deskSlider.value;
                    const mobDisplay = document.getElementById('mob-bid-amount-display');
                    if (mobDisplay) mobDisplay.innerText = Number(deskSlider.value).toLocaleString();
                });
                mobSlider.addEventListener('input', () => {
                    deskSlider.value = mobSlider.value;
                    const deskDisplay = document.getElementById('bid-amount-display');
                    if (deskDisplay) deskDisplay.innerText = Number(mobSlider.value).toLocaleString();
                });
            }
        });
    </script>
</body>
</html>