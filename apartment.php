<?php
require_once 'auth_check.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT a.*, u.name as owner_name, u.email as owner_email FROM apartments a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.status = 'approved'");
$stmt->execute([$id]);
$apt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$apt) { header("Location: index.php"); exit; }

$pdo->prepare("UPDATE apartments SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
try { $pdo->prepare("INSERT INTO daily_views (apartment_id, user_id, view_date, views) VALUES (?, ?, CURDATE(), 1) ON DUPLICATE KEY UPDATE views = views + 1")->execute([$id, $apt['user_id']]); } catch (Exception $e) {}

$images = [];
try { $images = json_decode($apt['images'], true); } catch(Exception $e) {}
if (!$images || count($images) === 0) $images = ['https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=800'];

$features = $apt['features'] ? explode(',', $apt['features']) : [];

$my_bids = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM bids WHERE apartment_id = ? AND user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $my_bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$sEmail = $apt['seller_email'] ?: $apt['owner_email'];
$isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $apt['user_id'];
$isLoggedIn = isset($_SESSION['user_id']);
$pricePerSqft = ($apt['type'] !== 'Land' && $apt['size_sqft'] > 0) ? round($apt['price'] / $apt['size_sqft']) : 0;

// Feature icon map
$featureIcons = [
    'A/C' => 'fa-snowflake', 'Pool' => 'fa-person-swimming', 'Gym' => 'fa-dumbbell',
    'Parking' => 'fa-square-parking', 'Furnished' => 'fa-couch', 'Balcony' => 'fa-building',
    'Security' => 'fa-shield-halved', 'Generator' => 'fa-bolt', 'WiFi' => 'fa-wifi',
    'Garden' => 'fa-leaf', 'Elevator' => 'fa-elevator', 'CCTV' => 'fa-video',
    'Laundry' => 'fa-shirt', 'Hot Water' => 'fa-fire', 'Rooftop' => 'fa-cloud',
    'Pet Friendly' => 'fa-paw', 'Solar' => 'fa-solar-panel', 'Intercom' => 'fa-phone-volume',
];
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>try{if(localStorage.getItem('theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}</script>
    <style>
    *,*::before,*::after{box-sizing:border-box;}

    .dp{max-width:1180px;margin:0 auto;padding:1.2rem 1.2rem 2rem;}
    .dp-back{display:inline-flex;align-items:center;gap:0.35rem;color:var(--text-secondary);text-decoration:none;font-size:0.85rem;font-weight:500;margin-bottom:1rem;transition:color 0.2s;}
    .dp-back:hover{color:var(--primary);}

    /* ═══════════════════════════════
       GALLERY — DESKTOP: big+stack / MOBILE: slider
       ═══════════════════════════════ */
    .gal{display:grid;grid-template-columns:1.7fr 1fr;grid-template-rows:1fr 1fr;gap:5px;border-radius:14px;overflow:hidden;margin-bottom:1.5rem;height:400px;position:relative;}
    .gal-item{overflow:hidden;cursor:pointer;position:relative;background:var(--bg-main);}
    .gal-item img{width:100%;height:100%;object-fit:cover;display:block;transition:transform 0.45s ease;}
    .gal-item:hover img{transform:scale(1.04);}
    .gal-item.hero{grid-row:1/-1;}
    .gal-more{position:absolute;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.05rem;backdrop-filter:blur(2px);pointer-events:none;}
    .gal-badge{position:absolute;bottom:12px;left:12px;background:rgba(0,0,0,0.55);color:#fff;padding:0.25rem 0.65rem;border-radius:6px;font-size:0.75rem;font-weight:600;z-index:10;backdrop-filter:blur(6px);display:flex;align-items:center;gap:0.3rem;}

    /* Mobile slider (hidden on desktop) */
    .gal-mobile{display:none;position:relative;border-radius:14px;overflow:hidden;margin-bottom:1.5rem;height:240px;background:var(--bg-main);}
    .gal-mobile img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 0.5s ease;cursor:pointer;}
    .gal-mobile img.active{opacity:1;}
    .gal-mobile .gm-dots{position:absolute;bottom:10px;left:50%;transform:translateX(-50%);display:flex;gap:5px;z-index:10;}
    .gal-mobile .gm-dot{width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,0.4);transition:all 0.3s;}
    .gal-mobile .gm-dot.active{background:#fff;width:18px;border-radius:4px;}
    .gal-mobile .gm-counter{position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.5);color:#fff;padding:0.2rem 0.5rem;border-radius:5px;font-size:0.7rem;font-weight:600;backdrop-filter:blur(4px);}

    /* ═══════════════════════════════
       STICKY INFO BAR (desktop only)
       ═══════════════════════════════ */
    .sticky-bar{display:none;position:fixed;top:0;left:0;right:0;background:var(--bg-card,#fff);border-bottom:1px solid var(--border-glass);padding:0.55rem 1.2rem;z-index:900;box-shadow:0 2px 12px rgba(0,0,0,0.06);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);animation:slideDown 0.25s ease;}
    .sticky-bar.show{display:block;}
    @keyframes slideDown{from{transform:translateY(-100%)}to{transform:translateY(0)}}
    .sticky-inner{max-width:1180px;margin:0 auto;display:flex;align-items:center;gap:1.2rem;flex-wrap:nowrap;overflow:hidden;}
    .sticky-inner .si-title{font-family:'Outfit',sans-serif;font-size:0.88rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:320px;}
    .sticky-inner .si-price{font-family:'Outfit',sans-serif;font-size:1rem;font-weight:800;color:var(--primary);white-space:nowrap;}
    .sticky-inner .si-tag{font-size:0.72rem;font-weight:600;padding:0.15rem 0.5rem;border-radius:50px;white-space:nowrap;}
    .sticky-inner .si-tag.mode{background:var(--primary);color:#fff;}
    .sticky-inner .si-tag.views{background:var(--bg-main);border:1px solid var(--border-glass);color:var(--text-secondary);}
    .sticky-inner .si-spacer{flex:1;}
    .sticky-inner .si-btn{padding:0.4rem 1rem;border-radius:8px;border:none;background:var(--primary);color:#fff;font-family:'Outfit',sans-serif;font-size:0.8rem;font-weight:700;cursor:pointer;white-space:nowrap;text-decoration:none;}

    /* ═══════════════════
       MAIN LAYOUT
       ═══════════════════ */
    .dp-grid{display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start;}
    .dp-left{min-width:0;}

    /* Header */
    .dp-head{margin-bottom:0.8rem;}
    .dp-head h1{font-family:'Outfit',sans-serif;font-size:1.55rem;font-weight:700;line-height:1.22;margin-bottom:0.3rem;letter-spacing:-0.015em;overflow-wrap:break-word;word-break:break-word;}
    .dp-addr{color:var(--text-secondary);font-size:0.85rem;display:flex;align-items:center;gap:0.35rem;overflow-wrap:break-word;word-break:break-word;}
    .dp-addr i{color:var(--primary);font-size:0.78rem;flex-shrink:0;}
    .dp-price-row{display:flex;align-items:baseline;gap:0.45rem;margin-top:0.5rem;flex-wrap:wrap;}
    .dp-price{font-family:'Outfit',sans-serif;font-size:1.6rem;font-weight:800;color:var(--primary);letter-spacing:-0.02em;}
    .dp-price-suf{font-size:0.8rem;font-weight:500;color:var(--text-secondary);}
    .dp-price-sqft{font-size:0.75rem;font-weight:600;color:var(--text-secondary);background:var(--bg-main);border:1px solid var(--border-glass);padding:0.15rem 0.5rem;border-radius:50px;}
    .dp-price-sqft i{color:var(--primary);font-size:0.65rem;margin-right:0.12rem;}

    /* Tags */
    .dp-tags{display:flex;gap:0.3rem;flex-wrap:wrap;margin-top:0.6rem;}
    .dp-tag{background:var(--bg-main);border:1px solid var(--border-glass);padding:0.2rem 0.6rem;border-radius:50px;font-size:0.75rem;color:var(--text-secondary);font-weight:500;display:inline-flex;align-items:center;gap:0.22rem;white-space:nowrap;}
    .dp-tag i{font-size:0.68rem;}
    .dp-tag.mode{background:var(--primary);color:#fff;border-color:var(--primary);font-weight:600;}

    /* ═══════════════════════════════
       KEY SPECS — ICON ROW below price
       ═══════════════════════════════ */
    .key-specs{display:flex;gap:0.6rem;flex-wrap:wrap;margin:0.9rem 0 1.2rem;padding:0.85rem 1rem;background:var(--bg-card);border:1px solid var(--border-glass);border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,0.03);}
    .ks-item{display:flex;align-items:center;gap:0.4rem;padding-right:0.7rem;border-right:1px solid var(--border-glass);}
    .ks-item:last-child{border-right:none;padding-right:0;}
    .ks-icon{width:32px;height:32px;border-radius:8px;background:rgba(79,70,229,0.08);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:0.85rem;flex-shrink:0;}
    .ks-text{display:flex;flex-direction:column;}
    .ks-val{font-family:'Outfit',sans-serif;font-size:0.88rem;font-weight:700;color:var(--text-primary);line-height:1.1;}
    .ks-lbl{font-size:0.65rem;color:var(--text-secondary);font-weight:500;text-transform:uppercase;letter-spacing:0.3px;}

    /* ═══════════════════════════════
       AMENITIES — with icons, above description
       ═══════════════════════════════ */
    .amenities-card{background:var(--bg-card);border:1px solid var(--border-glass);border-radius:14px;padding:1.2rem;box-shadow:0 1px 4px rgba(0,0,0,0.04);margin-bottom:1rem;}
    .amenities-card h3{font-family:'Outfit',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:0.8rem;padding-bottom:0.5rem;border-bottom:1px solid var(--border-glass);display:flex;align-items:center;gap:0.4rem;}
    .amenities-card h3 i{color:var(--primary);font-size:0.85rem;}
    .am-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:0.5rem;}
    .am-chip{display:flex;align-items:center;gap:0.4rem;padding:0.45rem 0.65rem;background:rgba(79,70,229,0.05);border:1px solid rgba(79,70,229,0.1);border-radius:10px;font-size:0.8rem;font-weight:600;color:var(--text-primary);transition:all 0.2s;}
    .am-chip:hover{border-color:var(--primary);background:rgba(79,70,229,0.1);}
    .am-chip i{color:var(--primary);font-size:0.78rem;width:16px;text-align:center;flex-shrink:0;}

    /* Cards */
    .dp-card{background:var(--bg-card);border:1px solid var(--border-glass);border-radius:14px;padding:1.3rem;box-shadow:0 1px 4px rgba(0,0,0,0.04);margin-bottom:1rem;}
    .dp-card h3{font-family:'Outfit',sans-serif;font-size:0.95rem;font-weight:700;margin-bottom:0.85rem;padding-bottom:0.55rem;border-bottom:1px solid var(--border-glass);display:flex;align-items:center;gap:0.4rem;}
    .dp-card h3 i{color:var(--primary);font-size:0.85rem;}
    .sp-grid{display:grid;grid-template-columns:1fr 1fr;gap:0.85rem;}
    .sp-item{display:flex;flex-direction:column;gap:0.1rem;}
    .sp-lbl{font-size:0.7rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:0.4px;}
    .sp-val{font-size:0.88rem;font-weight:600;color:var(--text-primary);overflow-wrap:break-word;word-break:break-word;}
    .desc-text{line-height:1.75;color:var(--text-secondary);font-size:0.9rem;overflow-wrap:break-word;word-break:break-word;}
    .dp-map{height:250px;border-radius:10px;overflow:hidden;border:1px solid var(--border-glass);margin-top:0.5rem;}

    /* ═══════════════════════════════
       RIGHT SIDEBAR
       ═══════════════════════════════ */
    .dp-right{position:sticky;top:85px;}
    .contact-card{background:var(--bg-card);border:1px solid var(--border-glass);border-radius:14px;padding:1.1rem;margin-bottom:0.8rem;box-shadow:0 1px 4px rgba(0,0,0,0.04);}
    .ct-row{display:flex;align-items:center;gap:0.7rem;margin-bottom:0.7rem;}
    .ct-avatar{width:40px;height:40px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;flex-shrink:0;}
    .ct-info h4{font-size:0.88rem;font-weight:600;margin:0;overflow-wrap:break-word;}
    .ct-info p{font-size:0.75rem;color:var(--text-secondary);margin:0;}
    .ct-btns{display:flex;gap:0.35rem;}
    .ct-btns a{flex:1;text-align:center;text-decoration:none;font-size:0.78rem;padding:0.48rem;border-radius:8px;font-weight:600;display:inline-flex;align-items:center;justify-content:center;gap:0.22rem;transition:all 0.2s;white-space:nowrap;overflow:hidden;}
    .ct-btn-fill{background:var(--primary);color:#fff;}
    .ct-btn-fill:hover{opacity:0.88;}
    .ct-btn-outline{background:var(--bg-main);border:1px solid var(--border-glass);color:var(--text-primary);}
    .ct-btn-outline:hover{border-color:var(--primary);color:var(--primary);}

    /* Offer */
    .offer-card{background:var(--bg-card);border:2px solid var(--primary);border-radius:16px;padding:1.2rem;position:relative;overflow:hidden;box-shadow:0 4px 24px rgba(79,70,229,0.10);}
    .offer-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--primary);}
    .oc-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:0.2rem;}
    .oc-title{font-family:'Outfit',sans-serif;font-size:0.95rem;font-weight:700;display:flex;align-items:center;gap:0.35rem;}
    .oc-title i{color:var(--primary);}
    .oc-live{font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#10b981;display:flex;align-items:center;gap:0.25rem;}
    .oc-live::before{content:'';width:6px;height:6px;background:#10b981;border-radius:50%;animation:pulse-dot 1.5s infinite;}
    @keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:0.3}}
    .oc-sub{font-size:0.78rem;color:var(--text-secondary);margin-bottom:0.9rem;}
    .oc-display{text-align:center;padding:0.5rem 0;}
    .oc-display small{font-size:0.72rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:0.1rem;}
    .oc-display .oc-amount{font-family:'Outfit',sans-serif;font-size:1.6rem;font-weight:800;color:var(--primary);letter-spacing:-0.02em;}
    .oc-slider-wrap{padding:0 0.15rem;margin-bottom:0.5rem;}
    .oc-slider-wrap input[type="range"]{width:100%;accent-color:var(--primary);height:5px;cursor:pointer;}
    .oc-slider-labels{display:flex;justify-content:space-between;font-size:0.66rem;color:var(--text-secondary);margin-top:0.12rem;}
    .oc-msg{width:100%;padding:0.55rem 0.7rem;border-radius:8px;border:1px solid var(--border-glass);font-family:inherit;font-size:0.82rem;margin-bottom:0.6rem;background:var(--bg-main);color:var(--text-primary);transition:border-color 0.2s;box-sizing:border-box;}
    .oc-msg:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(79,70,229,0.08);}
    .oc-submit{width:100%;padding:0.65rem;border-radius:10px;border:none;background:var(--primary);color:#fff;font-family:'Outfit',sans-serif;font-size:0.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.35rem;transition:all 0.2s;}
    .oc-submit:hover{opacity:0.9;}
    .oc-submit:disabled{opacity:0.55;cursor:not-allowed;}
    .oc-feedback{text-align:center;font-size:0.82rem;font-weight:600;margin-top:0.5rem;display:none;}
    .oc-own-msg{text-align:center;padding:1rem 0.5rem;color:var(--text-secondary);font-size:0.85rem;}
    .oc-login{text-align:center;padding:1.2rem 0.5rem;}
    .oc-login i{font-size:1.5rem;color:var(--border-glass);margin-bottom:0.5rem;display:block;}
    .oc-login p{font-size:0.82rem;color:var(--text-secondary);margin-bottom:0.7rem;}
    .oc-login a{display:inline-block;padding:0.5rem 1.4rem;background:var(--primary);color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:0.85rem;}

    .bh{margin-top:0.7rem;padding-top:0.7rem;border-top:1px dashed var(--border-glass);}
    .bh h4{font-size:0.78rem;font-weight:700;margin-bottom:0.5rem;color:var(--text-secondary);display:flex;align-items:center;gap:0.25rem;}
    .bh-item{background:var(--bg-main);padding:0.55rem 0.7rem;border-radius:8px;margin-bottom:0.35rem;border-left:3px solid var(--primary);}
    .bh-item.st-accepted{border-left-color:#10b981;}
    .bh-item.st-rejected{border-left-color:#ef4444;}
    .bh-top{display:flex;justify-content:space-between;align-items:center;}
    .bh-amt{font-weight:700;font-size:0.85rem;}
    .bh-st{font-size:0.65rem;font-weight:700;text-transform:uppercase;padding:0.12rem 0.45rem;border-radius:50px;}
    .bh-st.s-pending{background:rgba(79,70,229,0.1);color:var(--primary);}
    .bh-st.s-accepted{background:rgba(16,185,129,0.1);color:#10b981;}
    .bh-st.s-rejected{background:rgba(239,68,68,0.1);color:#ef4444;}
    .bh-date{font-size:0.7rem;color:var(--text-secondary);margin-top:0.1rem;}
    .bh-ok{margin-top:0.35rem;padding-top:0.35rem;border-top:1px solid rgba(0,0,0,0.04);font-size:0.75rem;color:#065f46;font-weight:500;}

    /* ═══ MOBILE BAR ═══ */
    .mob-bar{display:none;position:fixed;bottom:0;left:0;right:0;background:var(--bg-card,#fff);border-top:1px solid var(--border-glass);padding:0.55rem 0.8rem;z-index:1000;box-shadow:0 -3px 16px rgba(0,0,0,0.07);align-items:center;gap:0.5rem;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);}
    .mob-bar .mb-price{flex:1;min-width:0;}
    .mob-bar .mb-pv{font-family:'Outfit',sans-serif;font-size:1.05rem;font-weight:800;color:var(--primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .mob-bar .mb-ps{font-size:0.68rem;color:var(--text-secondary);display:block;}
    .mob-bar .mb-call{width:38px;height:38px;border-radius:9px;border:1px solid var(--border-glass);background:var(--bg-main);color:var(--text-primary);font-size:0.95rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;text-decoration:none;}
    .mob-bar .mb-offer{padding:0.55rem 1rem;border-radius:9px;border:none;background:var(--primary);color:#fff;font-family:'Outfit',sans-serif;font-size:0.82rem;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0;text-decoration:none;display:inline-flex;align-items:center;gap:0.3rem;}

    .drawer-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;backdrop-filter:blur(3px);}
    .drawer-bg.on{display:block;animation:fadeIn 0.15s ease;}
    .drawer{position:fixed;bottom:0;left:0;right:0;background:var(--bg-card,#fff);border-radius:18px 18px 0 0;padding:1.3rem 1.2rem 1.8rem;z-index:2001;transform:translateY(100%);transition:transform 0.35s cubic-bezier(0.32,0.72,0,1);max-height:82vh;overflow-y:auto;box-shadow:0 -8px 30px rgba(0,0,0,0.12);}
    .drawer.on{transform:translateY(0);}
    .drawer-handle{width:36px;height:4px;background:var(--border-glass);border-radius:4px;margin:0 auto 1rem;}
    .drawer h3{font-family:'Outfit',sans-serif;font-size:1.05rem;font-weight:700;margin-bottom:0.8rem;display:flex;align-items:center;gap:0.35rem;}
    .drawer h3 i{color:var(--primary);}

    .lb{position:fixed;inset:0;background:rgba(0,0,0,0.92);z-index:3000;display:none;align-items:center;justify-content:center;backdrop-filter:blur(10px);}
    .lb.on{display:flex;animation:fadeIn 0.2s ease;}
    .lb img{max-width:92%;max-height:88vh;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,0.4);object-fit:contain;}
    .lb-btn{position:absolute;color:#fff;background:rgba(255,255,255,0.1);border:none;cursor:pointer;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:background 0.2s;}
    .lb-btn:hover{background:rgba(255,255,255,0.2);}
    .lb-close{top:14px;right:14px;width:42px;height:42px;font-size:1.3rem;z-index:3010;}
    .lb-prev,.lb-next{top:50%;transform:translateY(-50%);width:44px;height:44px;font-size:1.2rem;}
    .lb-prev{left:14px;}
    .lb-next{right:14px;}
    .lb-count{position:absolute;bottom:18px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,0.6);font-size:0.82rem;font-weight:500;}
    @keyframes fadeIn{from{opacity:0}to{opacity:1}}

    /* ═══ RESPONSIVE ═══ */
    @media(max-width:920px){
        .dp-grid{grid-template-columns:1fr;}
        .dp-right{position:static;display:none;}
        .mob-bar{display:flex;}
        .mob-contact-card{display:block !important;}
        .sticky-bar{display:none !important;}
        .dp{padding-bottom:5rem;}
    }
    @media(max-width:768px){
        .gal{display:none;}
        .gal-mobile{display:block;}
        .key-specs{gap:0.5rem;padding:0.7rem 0.8rem;}
        .ks-item{padding-right:0.5rem;}
        .am-grid{grid-template-columns:repeat(auto-fill,minmax(110px,1fr));}
    }
    @media(max-width:640px){
        .dp-head h1{font-size:1.2rem;}
        .dp-price{font-size:1.3rem;}
        .sp-grid{grid-template-columns:1fr;}
        .dp{padding:0.8rem 0.8rem 5rem;}
        .dp-map{height:200px;}
        .key-specs{flex-wrap:wrap;}
        .ks-item{border-right:none;padding-right:0;}
        .ks-icon{width:28px;height:28px;font-size:0.75rem;border-radius:6px;}
        .ks-val{font-size:0.82rem;}
        .gal-mobile{height:210px;}
        .am-grid{grid-template-columns:repeat(2,1fr);}
        .am-chip{font-size:0.75rem;padding:0.38rem 0.5rem;}
    }
    @media(max-width:400px){
        .gal-mobile{height:190px;border-radius:10px;}
        .mob-bar .mb-offer{padding:0.5rem 0.8rem;font-size:0.78rem;}
        .mob-bar .mb-pv{font-size:0.95rem;}
        .mob-bar{padding:0.5rem 0.6rem;gap:0.4rem;}
        .dp-tag{padding:0.16rem 0.45rem;font-size:0.7rem;}
        .dp-card{padding:1rem;}
        .dp-card h3{font-size:0.88rem;}
        .dp-price-sqft{font-size:0.68rem;}
        .key-specs{padding:0.6rem;}
    }
    @media(max-width:340px){
        .dp-head h1{font-size:1.08rem;}
        .dp-price{font-size:1.15rem;}
    }
    </style>
    <?php include 'get-theme.php'; ?>
</head>
<body>
    <header class="site-header">
        <nav class="navbar">
            <a href="index.php" class="logo" style="text-decoration:none;"><i class="fa-solid fa-house-chimney-window"></i> MyHomeMyLand.LK</a>
            <div class="nav-links">
                <button id="theme-toggle" class="theme-toggle" title="Toggle Dark/Light Mode"><i class="fa-solid fa-moon"></i><i class="fa-solid fa-sun" style="display:none;"></i></button>
                <a href="index.php">Explore</a>
                <?php if($isLoggedIn): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <?php if($_SESSION['user_role']==='admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- ═══ STICKY INFO BAR (desktop scroll) ═══ -->
    <div class="sticky-bar" id="sticky-bar">
        <div class="sticky-inner">
            <span class="si-title"><?= htmlspecialchars($apt['title']) ?></span>
            <span class="si-price">Rs. <?= number_format($apt['price']) ?> <span style="font-size:0.72rem;font-weight:500;color:var(--text-secondary);"><?= $apt['listing_mode']==='Buy'?'':'/ mo' ?></span></span>
            <span class="si-tag mode">For <?= htmlspecialchars($apt['listing_mode'] ?? 'Rent') ?></span>
            <span class="si-tag views"><i class="fa-solid fa-eye" style="font-size:0.6rem;margin-right:2px;"></i> <?= number_format($apt['view_count'] ?? 0) ?></span>
            <span class="si-spacer"></span>
            <?php if($isLoggedIn && !$isOwner): ?>
                <a href="#offer-section" class="si-btn"><i class="fa-solid fa-gavel"></i> Make Offer</a>
            <?php elseif(!$isLoggedIn): ?>
                <a href="login.php" class="si-btn">Login to Bid</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dp">
        <a href="index.php" class="dp-back"><i class="fa-solid fa-arrow-left"></i> Back to listings</a>

        <!-- ═══ DESKTOP GALLERY ═══ -->
        <div class="gal">
            <div class="gal-item hero" data-i="0"><img src="<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($apt['title']) ?>"></div>
            <?php
            $thumbCount = min(count($images)-1, 2);
            $remaining = count($images)-1-$thumbCount;
            for($i=1;$i<=$thumbCount;$i++):
            ?>
                <div class="gal-item thumb" data-i="<?= $i ?>">
                    <img src="<?= htmlspecialchars($images[$i]) ?>" alt="Photo <?= $i+1 ?>">
                    <?php if($i===$thumbCount && $remaining>0): ?><div class="gal-more">+<?= $remaining ?> more</div><?php endif; ?>
                </div>
            <?php endfor; ?>
            <?php if(count($images)>1): ?><div class="gal-badge"><i class="fa-solid fa-images"></i> <?= count($images) ?></div><?php endif; ?>
        </div>

        <!-- ═══ MOBILE GALLERY — AUTO SLIDER ═══ -->
        <div class="gal-mobile" id="gal-mobile">
            <?php foreach($images as $i=>$img): ?>
                <img src="<?= htmlspecialchars($img) ?>" alt="Photo <?= $i+1 ?>" class="<?= $i===0?'active':'' ?>" data-i="<?= $i ?>">
            <?php endforeach; ?>
            <div class="gm-counter" id="gm-counter">1/<?= count($images) ?></div>
            <?php if(count($images)>1): ?>
            <div class="gm-dots">
                <?php foreach($images as $i=>$img): ?>
                    <div class="gm-dot <?= $i===0?'active':'' ?>" data-i="<?= $i ?>"></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="dp-grid">
            <div class="dp-left">
                <!-- Header -->
                <div class="dp-head" id="dp-head-anchor">
                    <h1><?= htmlspecialchars($apt['title']) ?></h1>
                    <div class="dp-addr"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($apt['address']) ?></div>
                    <div class="dp-price-row">
                        <span class="dp-price">Rs. <?= number_format($apt['price']) ?></span>
                        <span class="dp-price-suf"><?= $apt['listing_mode']==='Buy'?'Total Price':'/ month' ?></span>
                        <?php if($pricePerSqft>0): ?>
                            <span class="dp-price-sqft"><i class="fa-solid fa-ruler-combined"></i> Rs. <?= number_format($pricePerSqft) ?>/sqft</span>
                        <?php endif; ?>
                    </div>
                    <div class="dp-tags">
                        <span class="dp-tag mode"><i class="fa-solid fa-tag"></i> For <?= htmlspecialchars($apt['listing_mode'] ?? 'Rent') ?></span>
                        <span class="dp-tag"><i class="fa-solid fa-building"></i> <?= htmlspecialchars($apt['type']) ?></span>
                        <span class="dp-tag"><i class="fa-solid fa-eye"></i> <?= number_format($apt['view_count'] ?? 0) ?> views</span>
                        <span class="dp-tag"><i class="fa-solid fa-calendar"></i> <?= date('M d, Y', strtotime($apt['created_at'])) ?></span>
                    </div>
                </div>

                <!-- Key Specs with Icons -->
                <?php if($apt['type']!=='Land'): ?>
                <div class="key-specs">
                    <div class="ks-item"><div class="ks-icon"><i class="fa-solid fa-bed"></i></div><div class="ks-text"><span class="ks-val"><?= htmlspecialchars($apt['bedrooms']) ?></span><span class="ks-lbl">Beds</span></div></div>
                    <div class="ks-item"><div class="ks-icon"><i class="fa-solid fa-bath"></i></div><div class="ks-text"><span class="ks-val"><?= (int)$apt['baths'] ?></span><span class="ks-lbl">Baths</span></div></div>
                    <?php if($apt['size_sqft']>0): ?>
                    <div class="ks-item"><div class="ks-icon"><i class="fa-solid fa-vector-square"></i></div><div class="ks-text"><span class="ks-val"><?= number_format($apt['size_sqft']) ?></span><span class="ks-lbl">Sqft</span></div></div>
                    <?php endif; ?>
                    <div class="ks-item"><div class="ks-icon"><i class="fa-solid fa-couch"></i></div><div class="ks-text"><span class="ks-val"><?= htmlspecialchars($apt['furnished_status']?:'N/A') ?></span><span class="ks-lbl">Furnished</span></div></div>
                    <div class="ks-item"><div class="ks-icon"><i class="fa-solid fa-hammer"></i></div><div class="ks-text"><span class="ks-val"><?= htmlspecialchars($apt['completion_status']?:'Ready') ?></span><span class="ks-lbl">Status</span></div></div>
                </div>
                <?php else: ?>
                <div class="key-specs">
                    <div class="ks-item"><div class="ks-icon"><i class="fa-solid fa-ruler-combined"></i></div><div class="ks-text"><span class="ks-val"><?= (float)$apt['size_perches'] ?></span><span class="ks-lbl">Perches</span></div></div>
                    <div class="ks-item"><div class="ks-icon"><i class="fa-solid fa-seedling"></i></div><div class="ks-text"><span class="ks-val">Land</span><span class="ks-lbl">Type</span></div></div>
                </div>
                <?php endif; ?>

                <!-- Amenities (ABOVE description) -->
                <?php if(count($features)>0): ?>
                <div class="amenities-card">
                    <h3><i class="fa-solid fa-sparkles"></i> Amenities</h3>
                    <div class="am-grid">
                        <?php foreach($features as $f):
                            $fn = trim($f);
                            $icon = $featureIcons[$fn] ?? 'fa-check';
                        ?>
                            <div class="am-chip"><i class="fa-solid <?= $icon ?>"></i> <?= htmlspecialchars($fn) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Description -->
                <div class="dp-card">
                    <h3><i class="fa-solid fa-file-lines"></i> Description</h3>
                    <div class="desc-text"><?= nl2br(htmlspecialchars($apt['description'])) ?></div>
                </div>

                <!-- Property Details -->
                <div class="dp-card">
                    <h3><i class="fa-solid fa-circle-info"></i> Property Details</h3>
                    <div class="sp-grid">
                        <div class="sp-item"><span class="sp-lbl">Type</span><span class="sp-val"><?= htmlspecialchars($apt['type']) ?></span></div>
                        <?php if($apt['type']!=='Land'): ?>
                            <div class="sp-item"><span class="sp-lbl">Bedrooms</span><span class="sp-val"><?= htmlspecialchars($apt['bedrooms']) ?></span></div>
                            <div class="sp-item"><span class="sp-lbl">Bathrooms</span><span class="sp-val"><?= (int)$apt['baths'] ?></span></div>
                            <div class="sp-item"><span class="sp-lbl">Size</span><span class="sp-val"><?= $apt['size_sqft']>0?number_format($apt['size_sqft']).' sqft':'N/A' ?></span></div>
                            <div class="sp-item"><span class="sp-lbl">Completion</span><span class="sp-val"><?= htmlspecialchars($apt['completion_status']?:'Ready') ?></span></div>
                            <div class="sp-item"><span class="sp-lbl">Furnished</span><span class="sp-val"><?= htmlspecialchars($apt['furnished_status']?:'Unfurnished') ?></span></div>
                            <div class="sp-item"><span class="sp-lbl">Complex</span><span class="sp-val"><?= htmlspecialchars($apt['apartment_complex']?:'N/A') ?></span></div>
                        <?php else: ?>
                            <div class="sp-item"><span class="sp-lbl">Size</span><span class="sp-val"><?= (float)$apt['size_perches'] ?> Perches</span></div>
                        <?php endif; ?>
                        <div class="sp-item"><span class="sp-lbl">Address</span><span class="sp-val"><?= htmlspecialchars($apt['address']) ?></span></div>
                    </div>
                </div>

                <!-- Map -->
                <div class="dp-card">
                    <h3><i class="fa-solid fa-map-pin"></i> Location</h3>
                    <div id="detail-map" class="dp-map"></div>
                </div>

                <!-- Contact — mobile -->
                <div class="dp-card mob-contact-card" style="display:none;">
                    <h3><i class="fa-solid fa-user"></i> Listed By</h3>
                    <div class="ct-row">
                        <div class="ct-avatar"><?= strtoupper(substr($apt['owner_name'],0,1)) ?></div>
                        <div class="ct-info"><h4><?= htmlspecialchars($apt['owner_name']) ?></h4><p>Verified Lister</p></div>
                    </div>
                    <div class="ct-btns">
                        <a href="mailto:<?= htmlspecialchars($sEmail) ?>" class="ct-btn-fill"><i class="fa-solid fa-envelope"></i> Email</a>
                        <?php if(!empty($apt['seller_phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($apt['seller_phone']) ?>" class="ct-btn-outline"><i class="fa-solid fa-phone"></i> Call</a>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/','', $apt['seller_phone']) ?>" target="_blank" class="ct-btn-fill"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ═══ RIGHT SIDEBAR ═══ -->
            <div class="dp-right">
                <div class="contact-card">
                    <div class="ct-row">
                        <div class="ct-avatar"><?= strtoupper(substr($apt['owner_name'],0,1)) ?></div>
                        <div class="ct-info"><h4><?= htmlspecialchars($apt['owner_name']) ?></h4><p>Verified Lister</p></div>
                    </div>
                    <div class="ct-btns">
                        <a href="mailto:<?= htmlspecialchars($sEmail) ?>" class="ct-btn-fill"><i class="fa-solid fa-envelope"></i> Email</a>
                        <?php if(!empty($apt['seller_phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($apt['seller_phone']) ?>" class="ct-btn-outline"><i class="fa-solid fa-phone"></i> Call</a>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/','', $apt['seller_phone']) ?>" target="_blank" class="ct-btn-fill"><i class="fa-brands fa-whatsapp"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="offer-card" id="offer-section">
                    <div class="oc-head">
                        <span class="oc-title"><i class="fa-solid fa-gavel"></i> Make an Offer</span>
                        <?php if($isLoggedIn && !$isOwner): ?><span class="oc-live">Live</span><?php endif; ?>
                    </div>
                    <?php if($isLoggedIn): ?>
                        <?php if($isOwner): ?>
                            <p class="oc-own-msg">You cannot bid on your own listing.</p>
                        <?php else: ?>
                            <p class="oc-sub">Slide to set your offer amount.</p>
                            <div class="oc-slider-wrap">
                                <input type="range" id="bid-slider" min="<?= (int)$apt['price']*0.7 ?>" max="<?= (int)$apt['price']*1.5 ?>" step="5000" value="<?= (int)$apt['price'] ?>">
                                <div class="oc-slider-labels"><span>Rs. <?= number_format((int)$apt['price']*0.7) ?></span><span>Rs. <?= number_format((int)$apt['price']*1.5) ?></span></div>
                            </div>
                            <div class="oc-display"><small>Your Offer</small><div class="oc-amount">Rs. <span id="bid-amount-display"><?= number_format($apt['price']) ?></span></div></div>
                            <input type="text" id="bid-message" class="oc-msg" placeholder="Message to seller (optional)...">
                            <button id="submit-bid-btn" class="oc-submit" data-apt="<?= $id ?>"><i class="fa-solid fa-paper-plane"></i> Submit Offer</button>
                            <div id="bid-feedback" class="oc-feedback"></div>
                            <?php if(count($my_bids)>0): ?>
                            <div class="bh"><h4><i class="fa-solid fa-clock-rotate-left"></i> Your Offers</h4>
                                <?php foreach($my_bids as $bid): ?>
                                <div class="bh-item st-<?= $bid['status'] ?>">
                                    <div class="bh-top"><span class="bh-amt">Rs. <?= number_format($bid['amount']) ?></span><span class="bh-st s-<?= $bid['status'] ?>"><?= $bid['status'] ?></span></div>
                                    <div class="bh-date"><?= date('M d, Y', strtotime($bid['created_at'])) ?></div>
                                    <?php if($bid['status']==='accepted'): ?><div class="bh-ok"><i class="fa-solid fa-check-circle"></i> Accepted! Contact seller above.</div><?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="oc-login"><i class="fa-solid fa-lock"></i><p>Log in to place an offer.</p><a href="login.php">Login to Bid</a></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MOBILE BAR -->
    <div class="mob-bar" id="mob-bar">
        <div class="mb-price"><span class="mb-pv">Rs. <?= number_format($apt['price']) ?></span><span class="mb-ps"><?= $apt['listing_mode']==='Buy'?'Total':'/ month' ?></span></div>
        <?php if(!empty($apt['seller_phone'])): ?><a href="tel:<?= htmlspecialchars($apt['seller_phone']) ?>" class="mb-call"><i class="fa-solid fa-phone"></i></a><?php endif; ?>
        <?php if($isLoggedIn && !$isOwner): ?>
            <button class="mb-offer" id="mob-open"><i class="fa-solid fa-gavel"></i> Offer</button>
        <?php elseif(!$isLoggedIn): ?>
            <a href="login.php" class="mb-offer">Login to Bid</a>
        <?php endif; ?>
    </div>

    <!-- DRAWER -->
    <div class="drawer-bg" id="drw-bg"></div>
    <div class="drawer" id="drw">
        <div class="drawer-handle"></div>
        <h3><i class="fa-solid fa-gavel"></i> Make an Offer</h3>
        <?php if($isLoggedIn && !$isOwner): ?>
            <p style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:0.9rem;">Set your price and send directly to the seller.</p>
            <div class="oc-slider-wrap">
                <input type="range" id="mob-bid-slider" min="<?= (int)$apt['price']*0.7 ?>" max="<?= (int)$apt['price']*1.5 ?>" step="5000" value="<?= (int)$apt['price'] ?>">
                <div class="oc-slider-labels"><span>Rs. <?= number_format((int)$apt['price']*0.7) ?></span><span>Rs. <?= number_format((int)$apt['price']*1.5) ?></span></div>
            </div>
            <div class="oc-display"><small>Your Offer</small><div class="oc-amount">Rs. <span id="mob-bid-display"><?= number_format($apt['price']) ?></span></div></div>
            <input type="text" id="mob-bid-message" class="oc-msg" placeholder="Message to seller (optional)...">
            <button id="mob-submit-btn" class="oc-submit" data-apt="<?= $id ?>"><i class="fa-solid fa-paper-plane"></i> Submit Offer</button>
            <div id="mob-bid-feedback" class="oc-feedback"></div>
            <?php if(count($my_bids)>0): ?>
            <div class="bh"><h4><i class="fa-solid fa-clock-rotate-left"></i> Your Offers</h4>
                <?php foreach($my_bids as $bid): ?>
                <div class="bh-item st-<?= $bid['status'] ?>"><div class="bh-top"><span class="bh-amt">Rs. <?= number_format($bid['amount']) ?></span><span class="bh-st s-<?= $bid['status'] ?>"><?= $bid['status'] ?></span></div><div class="bh-date"><?= date('M d, Y', strtotime($bid['created_at'])) ?></div><?php if($bid['status']==='accepted'): ?><div class="bh-ok"><i class="fa-solid fa-check-circle"></i> Accepted!</div><?php endif; ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- LIGHTBOX -->
    <div class="lb" id="lb">
        <button class="lb-btn lb-close" id="lb-close"><i class="fa-solid fa-xmark"></i></button>
        <button class="lb-btn lb-prev" id="lb-prev"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="lb-btn lb-next" id="lb-next"><i class="fa-solid fa-chevron-right"></i></button>
        <img src="" alt="" id="lb-img">
        <div class="lb-count" id="lb-count"></div>
    </div>

    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; <?= date('Y') ?> MyHomeMyLand.LK. All rights reserved.</p>
            <div class="footer-links"><a href="#">Privacy Policy</a><a href="#">Terms of Service</a><a href="#">Contact Us</a></div>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="script.js"></script>
    <script src="terminal.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        /* ═══ MAP ═══ */
        const lat=<?= (float)$apt['lat'] ?>, lng=<?= (float)$apt['lng'] ?>;
        const map=L.map('detail-map').setView([lat,lng],15);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',{attribution:'&copy; OpenStreetMap'}).addTo(map);
        L.marker([lat,lng]).addTo(map).bindPopup('<b><?= addslashes(htmlspecialchars($apt['title'])) ?></b><br><?= addslashes(htmlspecialchars($apt['address'])) ?>').openPopup();

        /* ═══ MOBILE GALLERY AUTO-SLIDER ═══ */
        const imgs=<?= json_encode($images) ?>;
        const total=imgs.length;
        let mIdx=0, mTimer=null;
        const mSlides=document.querySelectorAll('#gal-mobile > img');
        const mDots=document.querySelectorAll('.gm-dot');
        const mCounter=document.getElementById('gm-counter');

        function mGo(i){
            mIdx=((i%total)+total)%total;
            mSlides.forEach((s,j)=>s.classList.toggle('active',j===mIdx));
            mDots.forEach((d,j)=>d.classList.toggle('active',j===mIdx));
            if(mCounter) mCounter.textContent=(mIdx+1)+'/'+total;
        }
        function mStart(){mStop();if(total>1) mTimer=setInterval(()=>mGo(mIdx+1),3500);}
        function mStop(){if(mTimer){clearInterval(mTimer);mTimer=null;}}

        mDots.forEach(d=>d.addEventListener('click',()=>{mGo(parseInt(d.dataset.i));mStop();mStart();}));

        // swipe
        let tx=0;
        const gm=document.getElementById('gal-mobile');
        if(gm){
            gm.addEventListener('touchstart',e=>{tx=e.changedTouches[0].screenX;},{passive:true});
            gm.addEventListener('touchend',e=>{const d=tx-e.changedTouches[0].screenX;if(Math.abs(d)>35){mGo(d>0?mIdx+1:mIdx-1);mStop();mStart();}},{passive:true});
        }
        mStart();

        /* ═══ LIGHTBOX ═══ */
        let ci=0;
        const lb=document.getElementById('lb'),lbImg=document.getElementById('lb-img'),lbCt=document.getElementById('lb-count');

        function lbOpen(i){ci=i;lbImg.src=imgs[ci];lbCt.textContent=(ci+1)+' / '+total;lb.classList.add('on');document.body.style.overflow='hidden';mStop();}
        function lbClose(){lb.classList.remove('on');document.body.style.overflow='';mStart();}
        function lbNav(d){ci=((ci+d)%total+total)%total;lbImg.src=imgs[ci];lbCt.textContent=(ci+1)+' / '+total;}

        document.querySelectorAll('.gal-item').forEach(item=>item.addEventListener('click',()=>lbOpen(parseInt(item.dataset.i)||0)));
        mSlides.forEach(img=>img.addEventListener('click',()=>lbOpen(parseInt(img.dataset.i)||0)));

        document.getElementById('lb-close').addEventListener('click',lbClose);
        lb.addEventListener('click',e=>{if(e.target===lb)lbClose();});
        document.getElementById('lb-prev').addEventListener('click',e=>{e.stopPropagation();lbNav(-1);});
        document.getElementById('lb-next').addEventListener('click',e=>{e.stopPropagation();lbNav(1);});
        document.addEventListener('keydown',e=>{if(!lb.classList.contains('on'))return;if(e.key==='Escape')lbClose();if(e.key==='ArrowLeft')lbNav(-1);if(e.key==='ArrowRight')lbNav(1);});

        /* ═══ STICKY BAR (desktop) ═══ */
        const stickyBar=document.getElementById('sticky-bar');
        const headAnchor=document.getElementById('dp-head-anchor');
        if(stickyBar && headAnchor && window.innerWidth>920){
            const obs=new IntersectionObserver(([e])=>{stickyBar.classList.toggle('show',!e.isIntersecting);},{threshold:0,rootMargin:'-80px 0px 0px 0px'});
            obs.observe(headAnchor);
        }

        /* ═══ DRAWER ═══ */
        const drwBg=document.getElementById('drw-bg'),drw=document.getElementById('drw'),mobOpen=document.getElementById('mob-open');
        function openDrw(){drwBg.classList.add('on');drw.classList.add('on');document.body.style.overflow='hidden';}
        function closeDrw(){drwBg.classList.remove('on');drw.classList.remove('on');document.body.style.overflow='';}
        if(mobOpen) mobOpen.addEventListener('click',openDrw);
        if(drwBg) drwBg.addEventListener('click',closeDrw);

        /* ═══ BID ═══ */
        function initBid(sId,dId,bId,mId,fId){
            const s=document.getElementById(sId),d=document.getElementById(dId),b=document.getElementById(bId),m=document.getElementById(mId),f=document.getElementById(fId);
            if(!s||!d||!b)return;
            s.addEventListener('input',()=>{d.innerText=Number(s.value).toLocaleString();});
            b.addEventListener('click',async()=>{
                b.disabled=true;b.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
                try{
                    const fd=new FormData();fd.append('apartment_id',b.dataset.apt);fd.append('amount',s.value);fd.append('message',m?m.value:'');
                    const res=await fetch('api/place_bid.php',{method:'POST',body:fd});const data=await res.json();
                    f.style.display='block';
                    if(data.success){f.style.color='#10b981';f.innerText='Offer submitted!';b.innerHTML='<i class="fa-solid fa-check"></i> Sent';b.style.background='#10b981';}
                    else{f.style.color='#ef4444';f.innerText=data.error||'Failed.';b.disabled=false;b.innerHTML='<i class="fa-solid fa-paper-plane"></i> Submit Offer';}
                }catch(e){f.style.display='block';f.style.color='#ef4444';f.innerText='Network error.';b.disabled=false;b.innerHTML='<i class="fa-solid fa-paper-plane"></i> Submit Offer';}
            });
        }
        initBid('bid-slider','bid-amount-display','submit-bid-btn','bid-message','bid-feedback');
        initBid('mob-bid-slider','mob-bid-display','mob-submit-btn','mob-bid-message','mob-bid-feedback');
        const ds=document.getElementById('bid-slider'),ms=document.getElementById('mob-bid-slider');
        if(ds&&ms){
            ds.addEventListener('input',()=>{ms.value=ds.value;const x=document.getElementById('mob-bid-display');if(x)x.innerText=Number(ds.value).toLocaleString();});
            ms.addEventListener('input',()=>{ds.value=ms.value;const x=document.getElementById('bid-amount-display');if(x)x.innerText=Number(ms.value).toLocaleString();});
        }
    });
    </script>
</body>
</html>