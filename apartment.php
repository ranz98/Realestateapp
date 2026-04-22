<?php
require_once 'auth_check.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET['id'];
$stmt = $pdo->prepare("SELECT a.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone FROM apartments a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.status = 'approved'");
$stmt->execute([$id]);
$apt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$apt) {
    header("Location: index.php");
    exit;
}

$pdo->prepare("UPDATE apartments SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
try {
    $pdo->prepare("INSERT INTO daily_views (apartment_id, user_id, view_date, views) VALUES (?, ?, CURDATE(), 1) ON DUPLICATE KEY UPDATE views = views + 1")->execute([$id, $apt['user_id']]);
} catch (Exception $e) {
}

$images = [];
try {
    $images = json_decode($apt['images'], true);
} catch (Exception $e) {
}
if (!$images || count($images) === 0)
    $images = ['https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=800'];

$features = $apt['features'] ? explode(',', $apt['features']) : [];

$my_bids = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM bids WHERE apartment_id = ? AND user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $my_bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$sEmail = !empty($apt['seller_email']) ? $apt['seller_email'] : $apt['owner_email'];
$sPhone = !empty($apt['seller_phone']) ? $apt['seller_phone'] : ($apt['owner_phone'] ?? '');

$isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $apt['user_id'];
$isLoggedIn = isset($_SESSION['user_id']);
$pricePerSqft = ($apt['type'] !== 'Land' && $apt['size_sqft'] > 0) ? round($apt['price'] / $apt['size_sqft']) : 0;
$minBidValue = $apt['price'] * 0.75;
$maxBidValue = $apt['price'];

// Feature icon map
$featureIcons = [
    'A/C' => 'fa-snowflake',
    'Pool' => 'fa-person-swimming',
    'Gym' => 'fa-dumbbell',
    'Parking' => 'fa-square-parking',
    'Furnished' => 'fa-couch',
    'Balcony' => 'fa-building',
    'Security' => 'fa-shield-halved',
    'Generator' => 'fa-bolt',
    'WiFi' => 'fa-wifi',
    'Garden' => 'fa-leaf',
    'Elevator' => 'fa-elevator',
    'CCTV' => 'fa-video',
    'Laundry' => 'fa-shirt',
    'Hot Water' => 'fa-fire',
    'Rooftop' => 'fa-cloud',
    'Pet Friendly' => 'fa-paw',
    'Solar' => 'fa-solar-panel',
    'Intercom' => 'fa-phone-volume',
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
    <link rel="stylesheet" href="style.css?v=3.6">
    <link rel="stylesheet" href="terminal.css?v=3.6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>try { if (localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) { }</script>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        /* Prevent iOS/Android auto-zoom when focusing form fields
           (mobile browsers zoom when font-size < 16px). */
        @media (max-width: 768px) {

            input[type="text"],
            input[type="email"],
            input[type="tel"],
            input[type="number"],
            input[type="password"],
            input[type="search"],
            input[type="url"],
            input:not([type]),
            textarea,
            select {
                font-size: 16px !important;
            }
        }

        .dp {
            max-width: 1180px;
            margin: 0 auto;
            padding: 1.2rem 1.2rem 1.2rem;
        }

        .dp-back {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: color 0.2s;
        }

        .dp-back:hover {
            color: var(--primary);
        }

        /* ═══════════════════════════════
       GALLERY — DESKTOP: big+stack / MOBILE: slider
       ═══════════════════════════════ */
        /* ═══ DESKTOP: Image (60%) | Map (40%) side by side ═══ */
        .gal {
            display: flex;
            gap: 6px;
            margin-bottom: 1rem;
            height: 220px;
            position: relative;
        }

        .gal-hero-side {
            flex: 0 0 68%;
            /* Reduced slightly to account for the gap */
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            background: var(--bg-main);
        }

        .gal-hero-side img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.45s ease;
        }

        /* Mobile Map Fullscreen Modal */

        .mob-map-wrap.map-fullscreen {
            position: fixed !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 9999 !important;
            background: rgba(0, 0, 0, 0.75) !important;
            backdrop-filter: blur(4px);
            padding: 55px 14px 14px 14px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center;
            justify-content: flex-start;
        }

        .mob-map-wrap.map-fullscreen .dp-map {
            width: 100% !important;
            flex: 1 !important;
            min-height: 0 !important;
            border-radius: 14px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .dist-calc {
            display: none;
            width: 100%;
            background: var(--bg-card);
            border-radius: 14px;
            padding: 1.2rem;
            margin-bottom: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            flex-direction: column;
            gap: 0.5rem;
            flex-shrink: 0;
            z-index: 10001;
        }

        .mob-map-wrap.map-fullscreen .dist-calc {
            display: flex;
        }

        .dist-calc input:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.15);
        }


        .map-close-btn {
            display: none;
            position: absolute;
            top: 25px;
            right: 25px;
            background: var(--bg-card, #fff);
            color: var(--text-primary);
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            z-index: 10000;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
            font-size: 1.3rem;
            align-items: center;
            justify-content: center;
        }

        .mob-map-wrap.map-fullscreen .map-close-btn {
            display: flex;
        }

        .mob-map-wrap.map-fullscreen .map-overlay-lab {
            display: none !important;
        }

        .gal-hero-side:hover img {
            transform: scale(1.03);
        }

        .gal-map-side {
            position: relative;
            flex: 1;
            border-radius: 14px;
            overflow: hidden;
        }




        /* --- Mobile Specific Fixes --- */
        @media (max-width: 768px) {





            /* Ensure the 'Location' label stays visible underneath */
            .map-overlay-lab {
                z-index: 9999;
                /* Just below the invisible button */
                pointer-events: none;
                /* Let clicks pass through to the button */
            }

            /* Ensure the map doesn't capture the initial touch */
            #detail-map-mobile {
                pointer-events: none !important;
            }

            #mob-map-wrap .leaflet-control-zoom {
                display: none !important;
            }

            /* Optional: If you want to hide the attribution text too for a cleaner look */
            #mob-map-wrap .leaflet-control-attribution {
                display: none !important;
            }

        }

        /* Mobile Map Fullscreen Modal styling was configured earlier */
        .gal-map-side #detail-map {
            width: 100%;
            height: 100%;
            pointer-events: auto;
        }

        #map-expand-text {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 0.72rem;
            cursor: pointer;
            font-weight: 600;
            padding: 0.35rem 0.85rem;
            border-radius: 14px;
            margin-top: -2px;
            pointer-events: auto;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.2s;
            backdrop-filter: blur(4px);
        }

        #map-expand-text:hover {
            background: var(--primary);
            transform: translateY(-1px);
            border-color: var(--primary);
        }

        .gal-badge {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background: rgba(0, 0, 0, 0.55);
            color: #fff;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 10;
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .gal-map-label {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            pointer-events: none;
            /* Allows clicks to pass through to the map if not hitting buttons */
        }

        .gal-map-label span.loc-badge {
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            backdrop-filter: blur(4px);
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Mobile slider (hidden on desktop) */
        .gal-mobile {
            display: none;
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            height: 240px;
            background: var(--bg-main);
        }

        .gal-mobile img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.5s ease;
            cursor: pointer;
        }

        .gal-mobile img.active {
            opacity: 1;
        }

        .gal-mobile .gm-dots {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 5px;
            z-index: 10;
        }

        .gal-mobile .gm-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            transition: all 0.3s;
        }

        .gal-mobile .gm-dot.active {
            background: #fff;
            width: 18px;
            border-radius: 4px;
        }

        .gal-mobile .gm-counter {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.7rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }

        /* ═══════════════════════════════
       STICKY INFO BAR (desktop only)
       ═══════════════════════════════ */
        .sticky-bar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--bg-card, #fff);
            border-bottom: 1px solid var(--border-glass);
            padding: 0.55rem 1.2rem;
            z-index: 900;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            animation: slideDown 0.25s ease;
        }

        .sticky-bar.show {
            display: block;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%)
            }

            to {
                transform: translateY(0)
            }
        }

        .sticky-inner {
            max-width: 1180px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            flex-wrap: nowrap;
            overflow: hidden;
        }

        .sticky-inner .si-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 320px;
        }

        .sticky-inner .si-price {
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            color: var(--primary);
            white-space: nowrap;
        }

        .sticky-inner .si-tag {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 50px;
            white-space: nowrap;
        }

        .sticky-inner .si-tag.mode {
            background: var(--primary);
            color: #fff;
        }

        .sticky-inner .si-tag.views {
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            color: var(--text-secondary);
        }

        .sticky-inner .si-spacer {
            flex: 1;
        }

        .sticky-inner .si-btn {
            padding: 0.4rem 1rem;
            border-radius: 8px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
        }

        /* ═══════════════════
       MAIN LAYOUT
       ═══════════════════ */
        .dp-grid {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 1.5rem;
            align-items: start;
        }

        .dp-left {
            min-width: 0;
        }

        /* Header */
        .dp-head {
            margin-bottom: 0.8rem;
        }

        .dp-head h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.55rem;
            font-weight: 700;
            line-height: 1.22;
            margin-bottom: 0.3rem;
            letter-spacing: -0.015em;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .dp-addr {
            color: var(--text-secondary);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .dp-addr i {
            color: var(--primary);
            font-size: 0.78rem;
            flex-shrink: 0;
        }

        .dp-price-row {
            display: flex;
            align-items: baseline;
            gap: 0.45rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .dp-price {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.02em;
        }

        .dp-price-suf {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .dp-price-sqft {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            padding: 0.15rem 0.5rem;
            border-radius: 50px;
        }

        .dp-price-sqft i {
            color: var(--primary);
            font-size: 0.65rem;
            margin-right: 0.12rem;
        }

        /* Tags */
        .dp-tags {
            display: flex;
            gap: 0.3rem;
            flex-wrap: wrap;
            margin-top: 0.6rem;
        }


        .dp-tag {
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            padding: 0.2rem 0.6rem;
            border-radius: 50px;
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.22rem;
            white-space: nowrap;
        }

        .dp-tag i {
            font-size: 0.68rem;
        }

        .dp-tag.mode {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            font-weight: 600;
        }

        /* ═══════════════════════════════
       KEY SPECS — ICON ROW below price
       ═══════════════════════════════ */
        .key-specs {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin: 0.9rem 0 1.2rem;
            padding: 0.85rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
        }

        .ks-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding-right: 0.7rem;
            border-right: 1px solid var(--border-glass);
        }

        .ks-item:last-child {
            border-right: none;
            padding-right: 0;
        }

        .ks-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(79, 70, 229, 0.08);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .ks-text {
            display: flex;
            flex-direction: column;
        }

        .ks-val {
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.1;
        }

        .ks-lbl {
            font-size: 0.65rem;
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ═══════════════════════════════
       AMENITIES — with icons, above description
       ═══════════════════════════════ */
        .amenities-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 14px;
            padding: 1.2rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            margin-bottom: 1rem;
        }

        .amenities-card h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .amenities-card h3 i {
            color: var(--primary);
            font-size: 0.85rem;
        }

        .am-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 0.5rem;
        }

        .am-chip {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.65rem;
            background: rgba(79, 70, 229, 0.05);
            border: 1px solid rgba(79, 70, 229, 0.1);
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-primary);
            transition: all 0.2s;
        }

        .am-chip:hover {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.1);
        }

        .am-chip i {
            color: var(--primary);
            font-size: 0.78rem;
            width: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        /* Cards */
        .dp-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 14px;
            padding: 1.3rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            margin-bottom: 1rem;
        }

        .dp-card h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 0.85rem;
            padding-bottom: 0.55rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .dp-card h3 i {
            color: var(--primary);
            font-size: 0.85rem;
        }

        .sp-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem;
        }

        .sp-item {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .sp-lbl {
            font-size: 0.7rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .sp-val {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-primary);
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .desc-text {
            line-height: 1.75;
            color: var(--text-secondary);
            font-size: 0.9rem;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .dp-map {
            height: 250px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border-glass);
            margin-top: 0.5rem;
        }

        /* ═══════════════════════════════
       RIGHT SIDEBAR
       ═══════════════════════════════ */
        .dp-right {
            position: sticky;
            top: 85px;
        }

        .contact-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 14px;
            padding: 1.1rem;
            margin-bottom: 0.8rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        .ct-row {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            margin-bottom: 0.7rem;
        }

        .ct-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .ct-info h4 {
            font-size: 0.88rem;
            font-weight: 600;
            margin: 0;
            overflow-wrap: break-word;
        }

        .ct-info p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .ct-btns {
            display: flex;
            gap: 0.35rem;
        }

        .ct-btns a {
            flex: 1;
            text-align: center;
            text-decoration: none;
            font-size: 0.78rem;
            padding: 0.48rem;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.22rem;
            transition: all 0.2s;
            white-space: nowrap;
            overflow: hidden;
        }

        .ct-btn-fill {
            background: var(--primary);
            color: #fff;
        }

        .ct-btn-fill:hover {
            opacity: 0.88;
        }

        .ct-btn-outline {
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
        }

        .ct-btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Offer */
        .offer-card {
            background: var(--bg-card);
            border: 2px solid var(--primary);
            border-radius: 16px;
            padding: 1.2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(79, 70, 229, 0.10);
        }

        .offer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
        }

        .oc-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.2rem;
        }

        .oc-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .oc-title i {
            color: var(--primary);
        }

        .oc-live {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .oc-live::before {
            content: '';
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse-dot 1.5s infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: 0.3
            }
        }

        .oc-sub {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin-bottom: 0.9rem;
        }

        .oc-display {
            text-align: center;
            padding: 0.5rem 0;
        }

        .oc-display small {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 0.1rem;
        }

        .oc-display .oc-amount {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.02em;
        }

        .oc-slider-wrap {
            padding: 0 0.15rem;
            margin-bottom: 0.5rem;
        }

        /* Distance Calculator Bar */
        .dist-calc-bar {
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 0;
        }

        .dist-calc-bar__row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .dist-calc-bar__from {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            max-width: 160px;
            overflow: hidden;
        }

        .dist-calc-bar__input {
            flex: 1;
            min-width: 120px;
            padding: 5px;
            border-radius: 8px;
            border: 1px solid var(--border-glass);
            background: var(--bg-card);
            color: var(--text-primary);
            font-size: 0.83rem;
            outline: none;
            font-family: inherit;
        }

        .dist-calc-bar__input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.15);
        }

        .dist-calc-bar__btn {
            background: #0ea5e9;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            text-align: left;
            font-size: 0.82rem;
            cursor: pointer;
            padding: 5px;

            white-space: nowrap;
            font-family: inherit;
            flex-shrink: 0;
            transition: opacity 0.2s;
        }

        .dist-calc-bar__btn:hover {
            opacity: 0.88;
        }

        .dist-calc-bar__result {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: rgba(14, 165, 233, 0.07);
            border-radius: 8px;
            border: 1px solid rgba(14, 165, 233, 0.18);
            font-size: 0.85rem;
            flex-wrap: wrap;
        }

        @media(max-width:600px) {
            .dist-calc-bar__from {
                display: none;
            }
        }

        .oc-slider-wrap input[type="range"] {
            width: 100%;
            accent-color: var(--primary);
            height: 5px;
            cursor: pointer;
        }

        .sl-live-val {
            text-align: center;
            font-size: 0.95rem;
            font-weight: 800;
            color: #0ea5e9;
            margin-bottom: 0.3rem;
            letter-spacing: 0.01em;
        }

        .sl-live-val--bar {
            font-size: 0.78rem;
            margin-bottom: 0.18rem;
        }

        .oc-slider-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.66rem;
            color: var(--text-secondary);
            margin-top: 0.12rem;
        }

        .oc-msg {
            width: 100%;
            padding: 0.55rem 0.7rem;
            border-radius: 8px;
            border: 1px solid var(--border-glass);
            font-family: inherit;
            font-size: 0.82rem;
            margin-bottom: 0.6rem;
            background: var(--bg-main);
            color: var(--text-primary);
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .oc-msg:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
        }

        .oc-submit {
            width: 100%;
            padding: 0.65rem;
            border-radius: 10px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            transition: all 0.2s;
        }

        .oc-submit:hover {
            opacity: 0.9;
        }

        .oc-submit:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .oc-feedback {
            text-align: center;
            font-size: 0.82rem;
            font-weight: 600;
            margin-top: 0.5rem;
            display: none;
        }

        .oc-own-msg {
            text-align: center;
            padding: 1rem 0.5rem;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .oc-login {
            text-align: center;
            padding: 1.2rem 0.5rem;
        }

        .oc-login i {
            font-size: 1.5rem;
            color: var(--border-glass);
            margin-bottom: 0.5rem;
            display: block;
        }

        .oc-login p {
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin-bottom: 0.7rem;
        }

        .oc-login a {
            display: inline-block;
            padding: 0.5rem 1.4rem;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .bh {
            margin-top: 0.7rem;
            padding-top: 0.7rem;
            border-top: 1px dashed var(--border-glass);
        }

        .bh h4 {
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .bh-item {
            background: var(--bg-main);
            padding: 0.55rem 0.7rem;
            border-radius: 8px;
            margin-bottom: 0.35rem;
            border-left: 3px solid var(--primary);
        }

        .bh-item.st-accepted {
            border-left-color: #10b981;
        }

        .bh-item.st-rejected {
            border-left-color: #ef4444;
        }

        .bh-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bh-amt {
            font-weight: 700;
            font-size: 0.85rem;
        }

        .bh-st {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0.12rem 0.45rem;
            border-radius: 50px;
        }

        .bh-st.s-pending {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary);
        }

        .bh-st.s-accepted {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .bh-st.s-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .bh-date {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-top: 0.1rem;
        }

        .bh-ok {
            margin-top: 0.35rem;
            padding-top: 0.35rem;
            border-top: 1px solid rgba(0, 0, 0, 0.04);
            font-size: 0.75rem;
            color: #065f46;
            font-weight: 500;
        }

        /* Mobile-only location map */
        .mob-gal-split {
            display: none;
        }

        .map-overlay-lab {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            z-index: 900;
            backdrop-filter: blur(4px);
            pointer-events: none;
        }

        /* ═══ MOBILE BAR ═══ */
        .mob-bar {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-card, #fff);
            border-top: 1px solid var(--border-glass);
            padding: 0.5rem 0.8rem;
            z-index: 1000;
            box-shadow: 0 -3px 16px rgba(0, 0, 0, 0.07);
            flex-direction: row;
            align-items: center;
            gap: 0.4rem;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .mob-bar .mb-slider-wrap {
            flex: 1;
            min-width: 0;
        }

        .mob-bar .mb-range {
            width: 100%;
            height: 4px;
            accent-color: #0ea5e9;
            cursor: pointer;
        }

        .mob-bar .mb-pv {
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mob-bar .mb-ps {
            font-size: 0.68rem;
            color: var(--text-secondary);
            margin-left: 0.25rem;
        }

        .mob-bar .mb-call-btn {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .mob-bar .mb-call-btn:hover {
            opacity: 0.85;
        }

        .mob-bar .mb-wa-btn {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: var(--primary);
            color: #fff;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .mob-bar .mb-btn-disabled {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }

        .mob-bar .mb-offer {
            height: 38px;
            padding: 0 0.65rem;
            border-radius: 9px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .drawer-bg {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 2000;
            backdrop-filter: blur(3px);
        }

        .drawer-bg.on {
            display: block;
            animation: fadeIn 0.15s ease;
        }

        .drawer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-card, #fff);
            border-radius: 18px 18px 0 0;
            padding: 1.3rem 1.2rem 1.8rem;
            z-index: 2001;
            transform: translateY(100%);
            transition: transform 0.35s cubic-bezier(0.32, 0.72, 0, 1);
            max-height: 82vh;
            overflow-y: auto;
            box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.12);
        }

        .drawer.on {
            transform: translateY(0);
        }

        .drawer-handle {
            width: 36px;
            height: 4px;
            background: var(--border-glass);
            border-radius: 4px;
            margin: 0 auto 1rem;
        }

        .drawer h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .drawer h3 i {
            color: var(--primary);
        }

        .lb {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.92);
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .lb.on {
            display: flex;
            animation: fadeIn 0.2s ease;
        }

        .lb img {
            max-width: 92%;
            max-height: 88vh;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            object-fit: contain;
        }

        .lb-btn {
            position: absolute;
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            cursor: pointer;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .lb-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .lb-close {
            top: 14px;
            right: 14px;
            width: 42px;
            height: 42px;
            font-size: 1.3rem;
            z-index: 3010;
        }

        .lb-prev,
        .lb-next {
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            font-size: 1.2rem;
        }

        .lb-prev {
            left: 14px;
        }

        .lb-next {
            right: 14px;
        }

        .lb-count {
            position: absolute;
            bottom: 18px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.82rem;
            font-weight: 500;
        }

        @keyframes fadeIn {
            from {
                opacity: 0
            }

            to {
                opacity: 1
            }
        }

        /* ═══ RESPONSIVE ═══ */
        @media(max-width:920px) {
            .dp-grid {
                grid-template-columns: 1fr;
            }

            .dp-right {
                position: static;
                display: none;
            }

            .mob-bar {
                display: flex;
            }

            .mob-contact-card {
                display: block !important;
            }

            .sticky-bar {
                display: none !important;
            }

            .dp {
                padding-bottom: 5rem;
            }
        }
        }


        /* Bid slider styles now leveraged from terminal.css (.t-so-price-group) */

        @media(max-width:768px) {
            .gal {
                display: none !important;
            }

            .mob-gal-split {
                display: flex;
                flex-direction: row;
                gap: 0;
                width: calc(100% + 2.4rem);
                margin-left: -1.2rem;
                margin-right: -1.2rem;
                margin-bottom: 1.5rem;
                height: 35vh;
                position: relative;
            }

            .gal-mobile {
                display: block;
                flex: 0 0 70%;
                height: 100%;
                margin: 0;
                padding: 0;
                border-radius: 0;
                order: 1;
            }

            .mob-map-wrap {
                display: block;
                flex: 0 0 30%;
                height: 100%;
                position: relative;
                border-radius: 0;
                overflow: hidden;
                border: none;
                margin: 0;
                padding: 0;
                order: 2;
            }

            .mob-map-wrap .dp-map {
                height: 100%;
                border-radius: 0;
                border: none;
                margin: 0;
                padding: 0;
            }

            .key-specs {
                gap: 0.5rem;
                padding: 0.7rem 0.8rem;
            }

            .ks-item {
                padding-right: 0.5rem;
            }

            .am-grid {
                grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            }
        }

        @media(max-width:640px) {
            .dp-head h1 {
                font-size: 1.2rem;
            }

            .dp-price {
                font-size: 1.3rem;
            }

            .sp-grid {
                grid-template-columns: 1fr;
            }

            .dp {
                padding: 0.8rem 0.8rem 5rem;
            }

            /* ── Tighter vertical spacing ── */
            .mob-gal-split {
                margin-bottom: 0.3rem;
            }

            .dp-head {
                margin-bottom: 0.2rem;
            }

            .dp-head h1 {
                margin-bottom: 0.1rem;
            }

            .dp-price-row {
                margin-top: 0.1rem;
            }

            .dp-tags {
                margin-top: 0.15rem;
            }

            .key-specs {
                margin: 0.25rem 0 0.3rem;
            }

            .dp-card {
                margin-bottom: 0.3rem;
                padding: 0.8rem;
            }

            .dp-card h3 {
                margin-bottom: 0.35rem;
                padding-bottom: 0.25rem;
            }

            .dp-map {
                height: 200px;
            }

            .key-specs {
                flex-wrap: wrap;
                gap: 0;
            }

            .ks-item {
                flex: 0 0 33.33%;
                border-right: none;
                border-bottom: none;
                padding: 0.3rem 0.5rem;
                box-sizing: border-box;
            }

            .ks-icon {
                width: 36px;
                height: 36px;
                font-size: 1rem;
                border-radius: 8px;
            }

            .ks-val {
                font-size: 0.82rem;
            }

            .mob-gal-split {
                width: calc(100% + 1.6rem);
                margin-left: -0.8rem;
                margin-right: -0.8rem;
                height: 40vh;
            }

            .amenities-card {
                padding: 0.6rem 0.7rem;
                margin-top: 0.1rem;
                margin-bottom: 0.6rem;
                border-radius: 10px;
            }

            .amenities-card h3 {
                font-size: 0.82rem;
                margin-bottom: 0.45rem;
                padding-bottom: 0.3rem;
            }

            .am-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.3rem;
            }

            .am-chip {
                font-size: 0.7rem;
                padding: 0.28rem 0.35rem;
                gap: 0.25rem;
                border-radius: 7px;
            }
        }

        @media(max-width:400px) {
            .mob-gal-split {
                height: 42vh;
            }

            .gal-mobile {
                border-radius: 0;
            }

            .mob-map-wrap {
                border-radius: 0;
            }

            .mob-bar .mb-offer {
                padding: 0.5rem 0.8rem;
                font-size: 0.78rem;
            }

            .mob-bar .mb-pv {
                font-size: 0.95rem;
            }

            .mob-bar {
                padding: 0.45rem 0.6rem;
                gap: 0.35rem;
            }

            .dp-tag {
                padding: 0.16rem 0.45rem;
                font-size: 0.7rem;
            }

            .dp-card {
                padding: 1rem;
            }

            .dp-card h3 {
                font-size: 0.88rem;
            }

            .dp-price-sqft {
                font-size: 0.68rem;
            }

            .key-specs {
                padding: 0.6rem;
            }
        }

        @media(max-width:340px) {
            .dp-head h1 {
                font-size: 1.08rem;
            }

            .dp-price {
                font-size: 1.15rem;
            }
        }

        /* Transparent overlay to catch the initial tap */
        /* Transparent overlay to catch the initial tap */
        .map-touch-overlay {
            position: absolute;
            inset: 0;
            z-index: 9000;
            /* Increased to 9000 to firmly beat Leaflet's z-index of 1000 */
            background: rgba(0, 0, 0, 0);
            /* Forces mobile browsers to register touches on empty space */
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            /* Removes the ugly grey tap box on mobile */
        }

        /* Hide the overlay when full screen so the user can actually pan/zoom the map */
        .mob-map-wrap.map-fullscreen .map-touch-overlay {
            display: none;
        }

        /* Hide default Leaflet zoom in expanded lightbox (we use custom buttons) */
        #expanded-map .leaflet-control-zoom {
            display: none !important;
        }

        /* Left-align the "by …" label column in result grid */
        .exp-result-grid>span {
            justify-self: start;
            text-align: left;
        }

        /* Prevent Leaflet from stealing touches when NOT full screen */
        #mob-map-wrap:not(.map-fullscreen) #detail-map-mobile {
            pointer-events: none !important;
            cursor: pointer;
        }

        /* Ensure the wrapper itself looks clickable */
        #mob-map-wrap:not(.map-fullscreen) {
            cursor: pointer;
        }

        .pac-container {
            z-index: 11000 !important;
            /* Higher than your fullscreen map (9999) */
            pointer-events: auto !important;
        }

        /* Type badge (left) */
        .pc-type-badgex {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(32px);
            border: 2px solid rgba(0, 0, 0, 0.28);
            color: #000;
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 0.58rem 0.6rem;
            border-radius: 50px;
            white-space: nowrap;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        }

        .pc-type-badgex i {
            font-size: 1.15rem;
            opacity: 1.2;
            padding: 0.58rem 0.6rem;
            border-radius: 50px;
        }
    </style>
    <?php include 'get-theme.php'; ?>
</head>

<body>
    <header class="site-header">
        <nav class="navbar">

            <!-- ── Logo: icon + wordmark (2-line on mobile) ── -->
            <a href="index.php" class="t-logo" style="text-decoration:none;">
                <i class="fa-solid fa-house-chimney-window t-logo-icon"></i>
                <span class="t-logo-words">
                    <span class="t-logo-top">MyHome</span>
                    <span class="t-logo-bot">MyLand</span>
                </span>
            </a>

            <div class="t-nav-left" id="t-nav-pill" role="button" tabindex="0" aria-label="Search properties">
                <span class="t-nav-pill-tw">
                    <span id="t-nav-tw-text"></span><span class="t-tw-cursor">|</span>
                </span>
                <i class="fa-solid fa-magnifying-glass t-nav-pill-icon"></i>
            </div>

            <!-- ── Mobile Filter Trigger (Next to search) ── -->
            <button class="t-nav-filter-btn" id="t-nav-filter-btn" aria-label="Open filters" title="Search & Filters">
                <i class="fa-solid fa-sliders"></i>
            </button>

            <!-- ── Right: Desktop links + Mobile action buttons ── -->
            <div class="nav-links">
                <button id="theme-toggle" class="theme-toggle" title="Toggle Dark/Light Mode"><i
                        class="fa-solid fa-moon"></i></button>
                <a href="index.php">Explore</a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?><a href="admin.php">Admin</a><?php endif; ?>
                    <a href="list-apartment.php" class="btn-primary">List Property</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Active filters bar — mobile only -->
        <div class="t-active-bar" id="t-active-bar" aria-label="Active filters">
            <div class="t-active-pills" id="t-active-pills"></div>
            <button class="t-active-clear" id="t-active-clear" aria-label="Clear all filters" title="Clear all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

    </header>

    <!-- ═══ STICKY INFO BAR (desktop scroll) ═══ -->
    <div class="sticky-bar" id="sticky-bar">
        <div class="sticky-inner">
            <span class="si-title"><?= htmlspecialchars($apt['title']) ?></span>
            <span class="si-price">Rs. <?= formatPriceShort($apt['price']) ?> <span
                    style="font-size:0.72rem;font-weight:500;color:var(--text-secondary);"><?= $apt['listing_mode'] === 'Buy' ? '' : '/ mo' ?></span></span>
            <span class="si-tag mode">For <?= htmlspecialchars($apt['listing_mode'] ?? 'Rent') ?></span>
            <span class="si-tag views"><i class="fa-solid fa-eye" style="font-size:0.6rem;margin-right:2px;"></i>
                <?= number_format($apt['view_count'] ?? 0) ?></span>
            <span class="si-spacer"></span>
            <?php if (!$isOwner): ?>
                <a href="#offer-section" class="si-btn"><i class="fa-solid fa-gavel"></i> Make Offer</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dp">
        <a href="index.php" class="dp-back"><i class="fa-solid fa-arrow-left"></i> Back to listings</a>

        <!-- ═══ DESKTOP: Hero Image (60%) | Location Map (40%) ═══ -->
        <div class="gal">
            <!-- Left: main hero slider -->
            <div class="gal-hero-side" id="dt-hero" data-i="0">
                <?php
                $typeIcons = ['Apartment' => 'fa-building', 'House' => 'fa-house', 'Villa' => 'fa-house-chimney-window', 'Commercial' => 'fa-store', 'Land' => 'fa-tree'];
                $typeIcon = $typeIcons[$apt['type']] ?? 'fa-building';
                ?>

                <?php foreach ($images as $i => $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="dt-slide <?= $i === 0 ? 'active' : '' ?>"
                        data-i="<?= $i ?>"
                        style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:<?= $i === 0 ? '1' : '0' ?>; transition: opacity 0.3s; z-index: <?= $i === 0 ? '1' : '0' ?>;"
                        alt="Photo <?= $i + 1 ?>">
                <?php endforeach; ?>

                <?php if (count($images) > 1): ?>
                    <button class="dt-nav-btn dt-prev"
                        style="position:absolute; left:10px; top:50%; transform:translateY(-50%); z-index:10; background:rgba(0,0,0,0.5); color:#fff; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i
                            class="fa-solid fa-chevron-left"></i></button>
                    <button class="dt-nav-btn dt-next"
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%); z-index:10; background:rgba(0,0,0,0.5); color:#fff; border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;"><i
                            class="fa-solid fa-chevron-right"></i></button>

                    <div
                        style="position:absolute; bottom:10px; left:0; width:100%; z-index:10; display:flex; justify-content:center; padding:0 35px; box-sizing:border-box;">
                        <div id="dt-thumb-strip"
                            style="display:flex; gap:6px; max-width:100%; overflow-x:auto; padding-bottom:4px; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.5) transparent;">
                            <?php foreach ($images as $i => $img): ?>
                                <div class="dt-thumb <?= $i === 0 ? 'active' : '' ?>" data-i="<?= $i ?>"
                                    style="width:40px; height:30px; flex-shrink:0; cursor:pointer; border:2px solid <?= $i === 0 ? '#fff' : 'transparent' ?>; border-radius:4px; overflow:hidden;">
                                    <img src="<?= htmlspecialchars($img) ?>" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Badge moved to top-right to avoid overlapping thumbs -->

                <?php endif; ?>
            </div>
            <!-- Right: interactive location map -->
            <div class="gal-map-side">
                <div class="gal-map-label">
                    <span class="pc-type-badgex" id="map-expand-text" style="
    position:absolute;
    border: 1px solid rgba(163, 148, 148, 0.72);
    background-color: rgba(0, 0, 0, 0.1); /* 👈 transparency here */
    font-size: 0.52rem;
    top:-2px;
    left:-2x;
    right:-8px;
    z-index:10;
     color: #ffffffff;
    pointer-events:auto;
    cursor:pointer;
      text-shadow: 0 0 4px rgba(0,0,0,0.9), 0 0 8px rgba(0,0,0,0.7);

  ">
                        <img src="https://i.ibb.co/MQghr6t/Gemini-Generated-Image-2fdsiy2fdsiy2fds-removebg-preview.png"
                            alt="Expand" style="width:16px;height:16px;vertical-align:middle;margin-right:5px;">
                        Large Map
                    </span>



                </div>

                <div id="detail-map"></div>
            </div>
        </div>

        <!-- ═══ MOBILE GALLERY + MAP ═══ -->
        <div class="mob-gal-split">
            <div class="gal-mobile" id="gal-mobile" style="position:relative;">
                <!-- Property type pill on mobile image -->

                <?php foreach ($images as $i => $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" alt="Photo <?= $i + 1 ?>"
                        class="<?= $i === 0 ? 'active' : '' ?>" data-i="<?= $i ?>">
                <?php endforeach; ?>
                <div class="gm-counter" id="gm-counter">1/<?= count($images) ?></div>
                <?php if (count($images) > 1): ?>
                    <div class="gm-dots">
                        <?php foreach ($images as $i => $img): ?>
                            <div class="gm-dot <?= $i === 0 ? 'active' : '' ?>" data-i="<?= $i ?>"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile-only map (shown below slider on small screens) -->
            <div class="mob-map-wrap" id="mob-map-wrap">

                <div class="map-overlay-lab"
                    style="pointer-events:none; display:flex; justify-content:space-between; align-items:center; width:calc(100% - 16px); background:transparent; padding:0; backdrop-filter:none;">
                    <span
                        style="background:rgba(0,0,0,0.5); padding:0.25rem 0.6rem; border-radius:4px; backdrop-filter:blur(4px);"><i
                            class="fa-solid fa-map-location-dot" style="color:var(--primary);margin-right:3px;"></i>
                        Location</span>
                    <button id="mob-map-expand-btn"
                        style="pointer-events:auto; background:var(--primary); color:#fff; padding:0.28rem 0.65rem; border-radius:50px; font-size:0.7rem; font-weight:600; border:none; cursor:pointer; display:flex; align-items:center; gap:0.3rem; box-shadow:0 3px 10px rgba(14,165,233,0.3); backdrop-filter:blur(4px); transition:all 0.2s;"
                        aria-label="Expand map">
                        <i class="fa-solid fa-expand"></i> Expandxxxx
                    </button>
                </div>
                <button class="map-close-btn" id="map-close-btn"><i class="fa-solid fa-xmark"></i></button>

                <!-- Distance Calculator UI (Visible only in Fullscreen) -->
                <div class="dist-calc" id="dist-calc-card">
                    <div style="display:flex;gap:0.5rem;align-items:stretch;cursor:auto;" id="dist-calc-inputs">
                        <!-- Zoom +/- on the left, stacked -->
                        <div style="display:flex;flex-direction:column;gap:0.35rem;flex-shrink:0;">
                            <button id="mob-zoom-in"
                                style="width:38px;height:38px;background:var(--bg-main);color:var(--text-primary);border:1px solid var(--border-glass);border-radius:8px;font-size:1.25rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;">+</button>
                            <button id="mob-zoom-out"
                                style="width:38px;height:38px;background:var(--bg-main);color:var(--text-primary);border:1px solid var(--border-glass);border-radius:8px;font-size:1.25rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;">−</button>
                        </div>
                        <!-- Input (top) + Show Time button (bottom) on the right -->
                        <div style="flex:1;display:flex;flex-direction:column;gap:0.35rem;">
                            <div style="position:relative;">
                                <input type="text" id="dist-origin" placeholder=""
                                    style="width:100%;box-sizing:border-box;padding:0.55rem 0.75rem;border-radius:8px;border:1px solid var(--border-glass);background:var(--bg-main);color:var(--text-primary);font-size:0.85rem;outline:none;font-family:inherit;">
                                <span id="dist-origin-ph"
                                    style="position:absolute;left:0.75rem;top:0;bottom:0;display:flex;align-items:center;pointer-events:none;font-size:0.85rem;color:var(--text-secondary);opacity:0;transition:opacity 0.25s ease;white-space:nowrap;overflow:hidden;max-width:calc(100% - 1rem);"></span>
                            </div>
                            <button id="dist-calc-btn"
                                style="background:var(--primary);color:#fff;border:none;padding:0.5rem 1rem;border-radius:8px;font-weight:600;cursor:pointer;font-size:0.83rem;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:0.4rem;"><i
                                    class="fa-solid fa-clock"></i> Show Time</button>
                        </div>
                    </div>
                    <div id="dist-result-box"
                        style="display:none;flex-direction:column;align-items:center;background:rgba(14,165,233,0.08);padding:0.8rem;border-radius:8px;border:1px solid rgba(14,165,233,0.2);cursor:auto;margin-top:0.4rem;">
                        <div
                            style="font-size:0.75rem;color:var(--text-secondary);margin-bottom:0.2rem;text-align:center;">
                            From: <span id="dist-found-address"
                                style="font-weight:600;color:var(--text-primary);"></span></div>
                        <div style="display:flex;gap:1.5rem;margin-top:0.3rem;">
                            <div style="display:flex;flex-direction:column;align-items:center;"><i
                                    class="fa-solid fa-car"
                                    style="color:var(--primary);font-size:1.1rem;margin-bottom:0.2rem;"></i><span
                                    id="dist-val"
                                    style="font-weight:700;font-size:0.9rem;color:var(--text-primary);"></span></div>
                            <div style="display:flex;flex-direction:column;align-items:center;"><i
                                    class="fa-solid fa-clock"
                                    style="color:#10b981;font-size:1.1rem;margin-bottom:0.2rem;"></i><span id="dur-val"
                                    style="font-weight:700;font-size:0.9rem;color:var(--text-primary);"></span></div>
                        </div>
                    </div>
                    <div id="dist-error" style="display:none;color:#ef4444;font-size:0.8rem;text-align:center;"></div>
                </div>

                <div id="detail-map-mobile" class="dp-map"></div>
            </div>
        </div>

        <div class="dp-grid">
            <div class="dp-left">
                <!-- Header -->
                <div class="dp-head" id="dp-head-anchor">
                    <h1><?= htmlspecialchars($apt['title']) ?></h1>
                    <div class="dp-addr"><i class="fa-solid fa-location-dot"></i>
                        <?= htmlspecialchars($apt['address']) ?></div>
                    <div class="dp-price-row">
                        <span class="dp-price">Rs. <?= formatPriceShort($apt['price']) ?></span>
                        <span
                            class="dp-price-suf"><?= $apt['listing_mode'] === 'Buy' ? 'Total Price' : '/ month' ?></span>
                        <?php if ($pricePerSqft > 0): ?>
                            <span class="dp-price-sqft"><i class="fa-solid fa-ruler-combined"></i> Rs.
                                <?= number_format($pricePerSqft) ?>/sqft</span>
                        <?php endif; ?>
                    </div>
                    <div class="dp-tags">
                        <span class="dp-tag mode"><i class="fa-solid fa-tag"></i> For
                            <?= htmlspecialchars($apt['listing_mode'] ?? 'Rent') ?></span>
                        <span class="dp-tag"><i class="fa-solid fa-building"></i>
                            <?= htmlspecialchars($apt['type']) ?></span>
                        <span class="dp-tag"><i class="fa-solid fa-eye"></i>
                            <?= number_format($apt['view_count'] ?? 0) ?> views</span>
                        <span class="dp-tag"><i class="fa-solid fa-calendar"></i>
                            <?= date('M d, Y', strtotime($apt['created_at'])) ?></span>
                    </div>
                </div>

                <!-- Key Specs with Icons -->
                <?php if ($apt['type'] !== 'Land'): ?>
                    <div class="key-specs">
                        <div class="ks-item">
                            <div class="ks-icon"><i class="fa-solid fa-bed"></i></div>
                            <div class="ks-text"><span class="ks-val"><?= htmlspecialchars($apt['bedrooms']) ?></span><span
                                    class="ks-lbl">Beds</span></div>
                        </div>
                        <div class="ks-item">
                            <div class="ks-icon"><i class="fa-solid fa-bath"></i></div>
                            <div class="ks-text"><span class="ks-val"><?= (int) $apt['baths'] ?></span><span
                                    class="ks-lbl">Baths</span></div>
                        </div>
                        <?php if ($apt['size_sqft'] > 0): ?>
                            <div class="ks-item">
                                <div class="ks-icon"><i class="fa-solid fa-vector-square"></i></div>
                                <div class="ks-text"><span class="ks-val"><?= number_format($apt['size_sqft']) ?></span><span
                                        class="ks-lbl">Sqft</span></div>
                            </div>
                        <?php endif; ?>
                        <div class="ks-item">
                            <div class="ks-icon"><i class="fa-solid fa-couch"></i></div>
                            <div class="ks-text"><span
                                    class="ks-val"><?= htmlspecialchars($apt['furnished_status'] ?: 'N/A') ?></span><span
                                    class="ks-lbl">Furnished</span></div>
                        </div>
                        <div class="ks-item">
                            <div class="ks-icon"><i class="fa-solid fa-hammer"></i></div>
                            <div class="ks-text"><span
                                    class="ks-val"><?= htmlspecialchars($apt['completion_status'] ?: 'Ready') ?></span><span
                                    class="ks-lbl">Status</span></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="key-specs">
                        <div class="ks-item">
                            <div class="ks-icon"><i class="fa-solid fa-ruler-combined"></i></div>
                            <div class="ks-text"><span class="ks-val"><?= (float) $apt['size_perches'] ?></span><span
                                    class="ks-lbl">Perches</span></div>
                        </div>
                        <div class="ks-item">
                            <div class="ks-icon"><i class="fa-solid fa-seedling"></i></div>
                            <div class="ks-text"><span class="ks-val">Land</span><span class="ks-lbl">Type</span></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Amenities (ABOVE description) -->
                <?php if (count($features) > 0): ?>
                    <div class="amenities-card">

                        <div class="am-grid">
                            <?php foreach ($features as $f):
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
                        <div class="sp-item"><span class="sp-lbl">Type</span><span
                                class="sp-val"><?= htmlspecialchars($apt['type']) ?></span></div>
                        <?php if ($apt['type'] !== 'Land'): ?>
                            <div class="sp-item"><span class="sp-lbl">Bedrooms</span><span
                                    class="sp-val"><?= htmlspecialchars($apt['bedrooms']) ?></span></div>
                            <div class="sp-item"><span class="sp-lbl">Bathrooms</span><span
                                    class="sp-val"><?= (int) $apt['baths'] ?></span></div>
                            <div class="sp-item"><span class="sp-lbl">Size</span><span
                                    class="sp-val"><?= $apt['size_sqft'] > 0 ? number_format($apt['size_sqft']) . ' sqft' : 'N/A' ?></span>
                            </div>
                            <div class="sp-item"><span class="sp-lbl">Completion</span><span
                                    class="sp-val"><?= htmlspecialchars($apt['completion_status'] ?: 'Ready') ?></span>
                            </div>
                            <div class="sp-item"><span class="sp-lbl">Furnished</span><span
                                    class="sp-val"><?= htmlspecialchars($apt['furnished_status'] ?: 'Unfurnished') ?></span>
                            </div>
                            <div class="sp-item"><span class="sp-lbl">Complex</span><span
                                    class="sp-val"><?= htmlspecialchars($apt['apartment_complex'] ?: 'N/A') ?></span></div>
                        <?php else: ?>
                            <div class="sp-item"><span class="sp-lbl">Size</span><span
                                    class="sp-val"><?= (float) $apt['size_perches'] ?> Perches</span></div>
                        <?php endif; ?>
                        <div class="sp-item"><span class="sp-lbl">Address</span><span
                                class="sp-val"><?= htmlspecialchars($apt['address']) ?></span></div>
                    </div>
                </div>

                <!-- Contact — mobile -->
                <div class="dp-card mob-contact-card" style="display:none;">
                    <h3><i class="fa-solid fa-user"></i> Listed By</h3>
                    <div class="ct-row">
                        <div class="ct-avatar"><?= strtoupper(substr($apt['owner_name'], 0, 1)) ?></div>
                        <div class="ct-info">
                            <h4><?= htmlspecialchars($apt['owner_name']) ?></h4>
                            <p>Verified Lister</p>
                        </div>
                    </div>
                    <div class="ct-btns">
                        <a href="mailto:<?= htmlspecialchars($sEmail) ?>" class="ct-btn-fill"><i
                                class="fa-solid fa-envelope"></i> Email</a>
                        <?php if (!empty($sPhone)): ?>
                            <a href="tel:<?= htmlspecialchars($sPhone) ?>" class="ct-btn-outline"><i
                                    class="fa-solid fa-phone"></i> Call</a>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $sPhone) ?>" target="_blank"
                                class="ct-btn-fill"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ═══ RIGHT SIDEBAR ═══ -->
            <div class="dp-right">
                <div class="contact-card">
                    <div class="ct-row">
                        <div class="ct-avatar"><?= strtoupper(substr($apt['owner_name'], 0, 1)) ?></div>
                        <div class="ct-info">
                            <h4><?= htmlspecialchars($apt['owner_name']) ?></h4>
                            <p>Verified Lister</p>
                        </div>
                    </div>
                    <div class="ct-btns">
                        <a href="mailto:<?= htmlspecialchars($sEmail) ?>" class="ct-btn-fill"><i
                                class="fa-solid fa-envelope"></i> Email</a>
                        <?php if (!empty($sPhone)): ?>
                            <a href="tel:<?= htmlspecialchars($sPhone) ?>" class="ct-btn-outline"><i
                                    class="fa-solid fa-phone"></i> Call</a>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $sPhone) ?>" target="_blank"
                                class="ct-btn-fill"><i class="fa-brands fa-whatsapp"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="offer-card" id="offer-section">
                    <div class="oc-head">
                        <span class="oc-title"><i class="fa-solid fa-gavel"></i> Make an Offer</span>
                        <?php if ($isLoggedIn && !$isOwner): ?><span class="oc-live">Live</span><?php endif; ?>
                    </div>
                    <?php if ($isOwner): ?>
                        <p class="oc-own-msg">You cannot bid on your own listing.</p>
                    <?php else: ?>
                        <p class="oc-sub">Slide to set your offer amount.</p>
                        <div class="t-so-filter-group t-so-price-group">
                            <label><i class="fa-solid fa-tags" style="color:var(--primary); margin-right:4px;"></i> Set Your
                                Offer</label>
                            <div class="price-slider-container" style="margin-top:0.8rem;">
                                <div id="bid-slider"></div>
                                <div id="bid-slider-live" class="price-display">Rs. <?= formatPriceShort($maxBidValue) ?>
                                </div>
                            </div>
                            <div class="oc-slider-labels"
                                style="margin-top:0.6rem; font-size:0.7rem; color:var(--text-secondary); display:flex; justify-content:space-between; font-weight:500;">
                                <span>Min: Rs. <?= formatPriceShort($minBidValue) ?></span>
                                <span>Max: Rs. <?= formatPriceShort($maxBidValue) ?></span>
                            </div>
                        </div>
                        <div class="oc-display" style="display:none;"><small>Your Offer</small>
                            <div class="oc-amount">Rs. <span
                                    id="bid-amount-display"><?= number_format($maxBidValue) ?></span></div>
                        </div>
                        <input type="text" id="bid-message" class="oc-msg" placeholder="Message to seller (optional)...">
                        <button id="submit-bid-btn" class="oc-submit" data-apt="<?= $id ?>"><i
                                class="fa-solid fa-paper-plane"></i> Submit Offer</button>
                        <div id="bid-feedback" class="oc-feedback"></div>
                        <?php if ($isLoggedIn && count($my_bids) > 0): ?>
                            <div class="bh">
                                <h4><i class="fa-solid fa-clock-rotate-left"></i> Your Offers</h4>
                                <?php foreach ($my_bids as $bid): ?>
                                    <div class="bh-item st-<?= $bid['status'] ?>">
                                        <div class="bh-top"><span class="bh-amt">Rs.
                                                <?= number_format($bid['amount']) ?></span><span
                                                class="bh-st s-<?= $bid['status'] ?>"><?= $bid['status'] ?></span></div>
                                        <div class="bh-date"><?= date('M d, Y', strtotime($bid['created_at'])) ?></div>
                                        <?php if ($bid['status'] === 'accepted'): ?>
                                            <div class="bh-ok"><i class="fa-solid fa-check-circle"></i> Accepted! Contact seller above.
                                            </div><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Large Full-Width Map (Desktop & Mobile) -->
        <div class="dp-card" style="margin-top: 1.5rem;">
            <h3><i class="fa-solid fa-map-location-dot"></i> Location Map</h3>

            <!-- Distance Calculator for large map -->
            <div class="dist-calc-bar" id="dist-calc-bar">
                <div class="dist-calc-bar__row">
                    <div class="dist-calc-bar__from">
                        <i class="fa-solid fa-circle-dot" style="color:#10b981;font-size:0.75rem;"></i>
                        <span
                            style="font-size:0.78rem;color:var(--text-secondary);white-space:nowrap;"><?= htmlspecialchars($apt['address']) ?></span>
                    </div>

                    <div style="position:relative;flex:1;min-width:120px;">
                        <input type="text" id="dist-origin-large" placeholder="" class="dist-calc-bar__input"
                            style="flex:none;width:100%;box-sizing:border-box;">
                        <span id="dist-origin-large-ph"
                            style="position:absolute;left:0.75rem;top:0;bottom:0;display:flex;align-items:center;pointer-events:none;font-size:0.83rem;color:var(--text-secondary);opacity:0;transition:opacity 0.25s ease;white-space:nowrap;overflow:hidden;max-width:calc(100% - 1rem);"></span>
                    </div>
                    <button id="dist-calc-btn-large" class="dist-calc-bar__btn"><i class="fa-solid fa-clock"></i> Show
                        Time</button>
                </div>
                <div id="dist-result-large" style="display:none;" class="dist-calc-bar__result">
                    <span><i class="fa-solid fa-car" style="color:var(--primary);"></i> <strong
                            id="dist-val-large"></strong></span>
                    <span><i class="fa-solid fa-clock" style="color:#10b981;"></i> <strong
                            id="dur-val-large"></strong></span>
                    <span style="font-size:0.72rem;color:var(--text-secondary);">via road</span>
                    <button id="dist-clear-large"
                        style="margin-left:auto;background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:0.8rem;padding:0;"><i
                            class="fa-solid fa-xmark"></i></button>
                </div>
                <div id="dist-error-large" style="display:none;color:#ef4444;font-size:0.78rem;margin-top:0.25rem;">
                </div>
            </div>

            <div id="detail-map-large-mob" class="dp-map"
                style="height: 400px; border-radius: 14px;margin-top:0.75rem;"></div>
        </div>
    </div>

    <!-- MOBILE BAR -->
    <div class="mob-bar" id="mob-bar">
        <?php if (!$isOwner): ?>
            <div class="mb-slider-wrap t-so-price-group"
                style="padding:0 !important; background:transparent !important; border:none !important; box-shadow:none !important; margin:0 !important;">
                <div class="price-slider-container" style="padding:0 0.5rem !important;">
                    <div id="mb-bar-slider"></div>
                    <div id="mb-bar-slider-live" class="price-display"
                        style="margin-top:0.4rem !important; font-size:0.8rem !important; text-align:center;">Rs.
                        <?= formatPriceShort($maxBidValue) ?>
                    </div>
                </div>
            </div>
            <button class="mb-offer" id="mob-open"><i class="fa-solid fa-gavel"></i> Offer</button>
        <?php endif; ?>
        <?php if (!empty($sPhone)): ?>
            <a href="tel:<?= htmlspecialchars($sPhone) ?>" class="mb-call-btn"><i class="fa-solid fa-phone"></i></a>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $sPhone) ?>" target="_blank" class="mb-wa-btn"><i
                    class="fa-brands fa-whatsapp"></i></a>
        <?php else: ?>
            <span class="mb-call-btn mb-btn-disabled" title="No contact number"><i class="fa-solid fa-phone"></i></span>
            <span class="mb-wa-btn mb-btn-disabled" title="No contact number"><i class="fa-brands fa-whatsapp"></i></span>
        <?php endif; ?>
    </div>

    <!-- DRAWER -->
    <div class="drawer-bg" id="drw-bg"></div>
    <div class="drawer" id="drw">
        <div class="drawer-handle"></div>
        <h3><i class="fa-solid fa-gavel"></i> Make an Offer</h3>
        <?php if (!$isOwner): ?>
            <p style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:0.9rem;">Set your price and send directly
                to the seller.</p>
            <div class="t-so-filter-group t-so-price-group">
                <label><i class="fa-solid fa-tags" style="color:var(--primary); margin-right:4px;"></i> Set Your
                    Offer</label>
                <div class="price-slider-container" style="margin-top:0.8rem;">
                    <div id="mob-bid-slider"></div>
                    <div id="mob-bid-slider-live" class="price-display">Rs. <?= formatPriceShort($maxBidValue) ?></div>
                </div>
                <div class="oc-slider-labels"
                    style="margin-top:0.6rem; font-size:0.7rem; color:var(--text-secondary); display:flex; justify-content:space-between; font-weight:500;">
                    <span>Min: Rs. <?= formatPriceShort($minBidValue) ?></span>
                    <span>Max: Rs. <?= formatPriceShort($maxBidValue) ?></span>
                </div>
            </div>
            <div class="oc-display" style="display:none;"><small>Your Offer</small>
                <div class="oc-amount">Rs. <span id="mob-bid-display"><?= number_format($maxBidValue) ?></span></div>
            </div>
            <input type="text" id="mob-bid-message" class="oc-msg" placeholder="Message to seller (optional)...">
            <button id="mob-submit-btn" class="oc-submit" data-apt="<?= $id ?>"><i class="fa-solid fa-paper-plane"></i>
                Submit Offer</button>
            <div id="mob-bid-feedback" class="oc-feedback"></div>
            <?php if ($isLoggedIn && count($my_bids) > 0): ?>
                <div class="bh">
                    <h4><i class="fa-solid fa-clock-rotate-left"></i> Your Offers</h4>
                    <?php foreach ($my_bids as $bid): ?>
                        <div class="bh-item st-<?= $bid['status'] ?>">
                            <div class="bh-top"><span class="bh-amt">Rs. <?= number_format($bid['amount']) ?></span><span
                                    class="bh-st s-<?= $bid['status'] ?>"><?= $bid['status'] ?></span></div>
                            <div class="bh-date"><?= date('M d, Y', strtotime($bid['created_at'])) ?></div>
                            <?php if ($bid['status'] === 'accepted'): ?>
                                <div class="bh-ok"><i class="fa-solid fa-check-circle"></i> Accepted!</div><?php endif; ?>
                        </div>
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

    <!-- LOGIN PROMPT POPUP -->
    <div id="login-prompt-overlay"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
        <div id="login-prompt-box"
            style="background:var(--bg-card); border-radius:18px; padding:2rem 1.8rem; max-width:340px; width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.25); transform:scale(0.9); opacity:0; transition:transform 0.22s ease, opacity 0.22s ease;">
            <div
                style="width:52px;height:52px;background:rgba(14,165,233,0.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="fa-solid fa-lock" style="font-size:1.3rem;color:#0ea5e9;"></i>
            </div>
            <h3 style="margin:0 0 0.4rem;font-size:1.1rem;font-weight:700;color:var(--text-primary);">Login Required
            </h3>
            <p style="margin:0 0 1.4rem;font-size:0.88rem;color:var(--text-secondary);line-height:1.5;">You need to be
                logged in to place an offer. <b>It only takes a moment.</b></p>
            <div style="display:flex;gap:0.6rem;justify-content:center;">
                <button id="login-prompt-cancel"
                    style="flex:1;padding:0.65rem 1rem;border-radius:10px;border:1px solid var(--border-glass);background:transparent;color:var(--text-secondary);font-size:0.88rem;cursor:pointer;font-family:inherit;">Cancel</button>
                <a id="login-prompt-ok" href="login.php"
                    style="flex:1;padding:0.65rem 1rem;border-radius:10px;border:none;background:#0ea5e9;color:#fff;font-size:0.88rem;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;font-family:inherit;">Login</a>
            </div>
        </div>
    </div>

    <!-- MAP LIGHTBOX -->
    <div id="map-lb"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:10000; align-items:center; justify-content:center; opacity:0; transition:opacity 0.3s ease; backdrop-filter:blur(6px);">
        <div
            style="position:relative; width:90%; max-width:900px; height:80vh; max-height:650px; background:var(--bg-card); border-radius:16px; overflow:hidden; box-shadow:0 15px 50px rgba(0,0,0,0.4); display:flex; flex-direction:column;">
            <button id="map-lb-close"
                style="position:absolute; top:12px; right:12px; z-index:11000; background:var(--bg-main); color:var(--text-primary); border:2px solid var(--bg-main); width:40px; height:40px; border-radius:50%; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.2);"><i
                    class="fa-solid fa-xmark"></i></button>
            <!-- Distance Calculator bar at top of expanded map -->
            <div class="dist-calc-bar" id="dist-calc-bar-exp"
                style="position:absolute;top:0;left:0;right:0;z-index:1001;background:transparent;border:none;border-radius:0;padding:0.6rem 0.75rem;padding-right:60px;">
                <div style="display:flex;gap:0.5rem;align-items:stretch;">
                    <!-- Zoom +/- and extra action buttons left column -->
                    <div style="display:flex;flex-direction:column;gap:0.4rem;flex-shrink:0;align-items:center;">
                        <!-- Zoom joined group (no gap) -->
                        <div style="display:flex;flex-direction:column;gap:0;">
                            <button id="exp-zoom-in"
                                style="width:34px;height:34px;background:#fff;color:#333;border:2px solid rgba(0,0,0,0.25);border-bottom:1px solid rgba(0,0,0,0.15);border-radius:4px 4px 0 0;font-size:1.4rem;font-weight:bold;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 5px rgba(0,0,0,0.2);">+</button>
                            <button id="exp-zoom-out"
                                style="width:34px;height:34px;background:#fff;color:#333;border:2px solid rgba(0,0,0,0.25);border-top:none;border-radius:0 0 4px 4px;font-size:1.4rem;font-weight:bold;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 5px rgba(0,0,0,0.2);">−</button>
                        </div>
                        <button id="exp-action-1"
                            style="width:38px;height:38px;background:rgba(255,255,255,0.85);backdrop-filter:blur(6px);color:#0ea5e9;border:2px solid #0ea5e9;border-radius:50%;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.15);"><i
                                class="fa-solid fa-pen"></i></button>
                        <button id="exp-action-2"
                            style="width:38px;height:38px;background:rgba(255,255,255,0.85);backdrop-filter:blur(6px);color:#0ea5e9;border:2px solid #0ea5e9;border-radius:50%;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.15);"><i
                                class="fa-solid fa-train-subway"></i></button>
                    </div>
                    <!-- Input (top) + Show Time (bottom) right column -->
                    <div style="flex:1;display:flex;flex-direction:column;gap:0.35rem;">
                        <div style="position:relative;">
                            <input type="text" id="dist-origin-exp" placeholder="" class="dist-calc-bar__input"
                                style="flex:none;width:100%;box-sizing:border-box;background:rgba(255,255,255,0.85);backdrop-filter:blur(6px);border:1px solid rgba(0,0,0,0.12);box-shadow:0 2px 6px rgba(0,0,0,0.15);color:#111;">
                            <span id="dist-origin-exp-ph"
                                style="position:absolute;left:0.75rem;top:0;bottom:0;display:flex;align-items:center;pointer-events:none;font-size:0.83rem;color:#555;opacity:0;transition:opacity 0.25s ease;white-space:nowrap;overflow:hidden;max-width:calc(100% - 1rem);"></span>
                        </div>
                        <button id="dist-calc-btn-exp" class="dist-calc-bar__btn"
                            style="width:100%;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.2);"><i
                                class="fa-solid fa-clock"></i> Show Time</button>
                        <div id="dist-result-exp"
                            style="display:none;background:rgba(255,255,255,0.01);backdrop-filter:blur(1px);border-radius:8px;padding:0.4rem 0.6rem;box-shadow:0 2px 8px rgba(0,0,0,0.75);">
                            <div style="display:flex;justify-content:flex-end;margin-bottom:0.1rem;">
                                <button id="dist-clear-exp"
                                    style="background:none;border:none;color:#888;cursor:pointer;font-size:0.8rem;padding:0;"><i
                                        class="fa-solid fa-xmark"></i></button>
                            </div>
                            <!-- Grid: icon | dist | time | label -->
                            <div style="display:grid;grid-template-columns:16px auto auto 1fr;align-items:center;row-gap:0.22rem;column-gap:0.4rem;"
                                class="exp-result-grid">
                                <!-- Via Road -->
                                <i class="fa-solid fa-car"
                                    style="color:#0ea5e9;font-size:0.78rem;justify-self:center;"></i>
                                <strong id="dist-val-exp"
                                    style="font-size:0.78rem;color:#111;white-space:nowrap;"></strong>
                                <strong id="dur-val-exp"
                                    style="font-size:0.78rem;color:#0ea5e9;white-space:nowrap;"></strong>
                                <span style="font-size:0.68rem;color:#888;white-space:nowrap;text-align:right;">by
                                    car</span>
                                <!-- By Walk -->
                                <i class="fa-solid fa-person-walking"
                                    style="color:#0ea5e9;font-size:0.78rem;justify-self:center;"></i>
                                <strong id="dist-val-exp-walk"
                                    style="font-size:0.78rem;color:#111;white-space:nowrap;"></strong>
                                <strong id="dur-val-exp-walk"
                                    style="font-size:0.78rem;color:#0ea5e9;white-space:nowrap;"></strong>
                                <span style="font-size:0.68rem;color:#888;white-space:nowrap;text-align:right;">by
                                    walk</span>
                                <!-- By Bus -->
                                <i class="fa-solid fa-bus"
                                    style="color:#0ea5e9;font-size:0.78rem;justify-self:center;"></i>
                                <strong id="dist-val-exp-transit"
                                    style="font-size:0.78rem;color:#111;white-space:nowrap;"></strong>
                                <strong id="dur-val-exp-transit"
                                    style="font-size:0.78rem;color:#0ea5e9;white-space:nowrap;"></strong>
                                <span style="font-size:0.68rem;color:#888;white-space:nowrap;text-align:right;">by
                                    bus</span>
                            </div>
                        </div>
                        <div id="dist-error-exp" style="display:none;color:#ef4444;font-size:0.78rem;"></div>
                    </div>
                </div>
            </div>
            <div id="expanded-map" style="position:absolute;inset:0;"></div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; <?= date('Y') ?> MyHomeMyLand.LK. All rights reserved.</p>
            <div class="footer-links"><a href="#">Privacy Policy</a><a href="#">Terms of Service</a><a href="#">Contact
                    Us</a></div>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.js"></script>
    <script src="script.js"></script>
    <script src="terminal.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            /* ═══ MAP — desktop (in hero) + mobile (below slider) ═══ */
            const lat = <?= (float) $apt['lat'] ?>, lng = <?= (float) $apt['lng'] ?>;
            const popupHtml = '<b><?= addslashes(htmlspecialchars($apt['title'])) ?></b><br><?= addslashes(htmlspecialchars($apt['address'])) ?>';
            const tileUrl = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
            const tileOpts = { attribution: '&copy; OpenStreetMap' };

            /* Custom coloured pin */
            const pinIcon = L.divIcon({
                className: '',
                html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 36" width="28" height="42"><path d="M12 0C5.373 0 0 5.373 0 12c0 9 12 24 12 24s12-15 12-24C24 5.373 18.627 0 12 0z" fill="#0ea5e9"/><circle cx="12" cy="12" r="5" fill="#fff"/></svg>`,
                iconSize: [28, 42],
                iconAnchor: [14, 42],
                popupAnchor: [0, -44]
            });

            /* Desktop hero map */
            const map = L.map('detail-map', { zoomControl: false }).setView([lat, lng], 11);
            L.tileLayer(tileUrl, tileOpts).addTo(map);
            L.marker([lat, lng], { icon: pinIcon }).addTo(map).bindPopup(popupHtml);
            map.once('load', () => map.setView([lat, lng], 11, { animate: false }));
            setTimeout(() => { map.invalidateSize(); map.setView([lat, lng], 11, { animate: false }); }, 300);

            /* Mobile map (below slider) */
            const mobMapEl = document.getElementById('detail-map-mobile');
            let mobMap = null;
            if (mobMapEl) {
                // Ensure zoomControl is explicitly false here
                mobMap = L.map('detail-map-mobile', { zoomControl: false }).setView([lat, lng], 6);
                L.tileLayer(tileUrl, tileOpts).addTo(mobMap);
                L.marker([lat, lng], { icon: pinIcon }).addTo(mobMap).bindPopup(popupHtml);
                setTimeout(() => { mobMap.invalidateSize(); mobMap.setView([lat, lng], 6, { animate: false }); }, 300);
            }

            /* ═══ SHARED DISTANCE / ROUTE HELPER ═══ */
            const routeStates = {};
            async function calcRoute(mapObj, mapKey, originText, ui) {
                const { btn, origBtnHtml, resultBox, distEl, durEl, addrEl, errorEl } = ui;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                btn.disabled = true;
                if (errorEl) errorEl.style.display = 'none';
                resultBox.style.display = 'none';
                try {
                    // Google Places pre-resolved? else Nominatim
                    let oLat, oLng, oLabel;
                    const pre = window._distPlaceCoords && window._distPlaceCoords[mapKey];
                    if (pre) {
                        oLat = pre.lat; oLng = pre.lng; oLabel = pre.label;
                    } else {
                        const gj = await (await fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(originText) + '&format=json&limit=1', { headers: { 'Accept-Language': 'en' } })).json();
                        if (!gj.length) throw new Error('Location not found. Try a more specific address.');
                        oLat = parseFloat(gj[0].lat); oLng = parseFloat(gj[0].lon);
                        oLabel = gj[0].display_name.split(',').slice(0, 2).join(',');
                    }
                    // OSRM route
                    const rd = await (await fetch(`https://router.project-osrm.org/route/v1/driving/${oLng},${oLat};${lng},${lat}?overview=full&geometries=geojson`)).json();
                    if (rd.code !== 'Ok' || !rd.routes.length) throw new Error('No driving route found to this property.');
                    const r = rd.routes[0];
                    const distKm = (r.distance / 1000).toFixed(1) + ' km';
                    const durMin = Math.round(r.duration / 60);
                    const durText = durMin >= 60 ? Math.floor(durMin / 60) + 'h ' + (durMin % 60) + ' mins' : durMin + ' mins';
                    // Clear old layers
                    if (routeStates[mapKey]) routeStates[mapKey].forEach(l => mapObj.removeLayer(l));
                    // Draw route
                    const routeLine = L.geoJSON(r.geometry, { style: { color: '#0ea5e9', weight: 5, opacity: 0.85 } }).addTo(mapObj);
                    const oMarker = L.circleMarker([oLat, oLng], { radius: 9, fillColor: '#10b981', color: '#fff', weight: 3, fillOpacity: 1 }).addTo(mapObj).bindPopup('<b>Your location</b><br>' + oLabel);
                    routeStates[mapKey] = [routeLine, oMarker];
                    mapObj.fitBounds(routeLine.getBounds(), { padding: [40, 40], animate: true });
                    if (addrEl) addrEl.innerText = oLabel;
                    distEl.innerText = distKm;
                    durEl.innerText = durText;
                    resultBox.style.display = 'flex';
                } catch (err) {
                    if (errorEl) { errorEl.innerText = err.message || 'Error calculating route.'; errorEl.style.display = 'block'; }
                } finally {
                    btn.innerHTML = origBtnHtml;
                    btn.disabled = false;
                }
            }

            /* Large Mobile Map (under property details) */
            const largeMobMapEl = document.getElementById('detail-map-large-mob');
            let largeMobMap = null;
            if (largeMobMapEl) {
                largeMobMap = L.map('detail-map-large-mob', { zoomControl: true }).setView([lat, lng], 13);
                L.tileLayer(tileUrl, tileOpts).addTo(largeMobMap);
                L.marker([lat, lng], { icon: pinIcon }).addTo(largeMobMap).bindPopup(popupHtml);
                setTimeout(() => { largeMobMap.invalidateSize(); largeMobMap.setView([lat, lng], 13, { animate: false }); }, 300);

                // Wire large map distance calculator
                const distBtnLarge = document.getElementById('dist-calc-btn-large');
                const distInputLarge = document.getElementById('dist-origin-large');
                const distClearLarge = document.getElementById('dist-clear-large');
                if (distBtnLarge && distInputLarge) {
                    const doCalcLarge = () => {
                        const origin = distInputLarge.value.trim();
                        if (!origin) return;
                        calcRoute(largeMobMap, 'large', origin, {
                            btn: distBtnLarge,
                            origBtnHtml: '<i class="fa-solid fa-clock"></i> Show How Long',
                            resultBox: document.getElementById('dist-result-large'),
                            distEl: document.getElementById('dist-val-large'),
                            durEl: document.getElementById('dur-val-large'),
                            addrEl: null,
                            errorEl: document.getElementById('dist-error-large')
                        });
                    };
                    distBtnLarge.addEventListener('click', doCalcLarge);
                    distInputLarge.addEventListener('keydown', e => { if (e.key === 'Enter') doCalcLarge(); });
                    if (distClearLarge) {
                        distClearLarge.addEventListener('click', () => {
                            document.getElementById('dist-result-large').style.display = 'none';
                            document.getElementById('dist-error-large').style.display = 'none';
                            distInputLarge.value = '';
                            if (routeStates['large']) { routeStates['large'].forEach(l => largeMobMap.removeLayer(l)); delete routeStates['large']; }
                            largeMobMap.setView([lat, lng], 13, { animate: true });
                        });
                    }
                }
            }

            /* Mobile Map Fullscreen Toggle */
            /* Mobile Map Fullscreen Toggle */
            /* Mobile Map Fullscreen Toggle */
            const mobMapWrap = document.getElementById('mob-map-wrap');
            const mapCloseBtn = document.getElementById('map-close-btn');
            const expandBtn = document.getElementById('map-expand-text'); // The new button

            if (mobMapWrap && mapCloseBtn && mobMapEl) {

                // Helper function to open fullscreen
                const openFullscreenMap = () => {
                    if (window.innerWidth <= 768 && !mobMapWrap.classList.contains('map-fullscreen')) {
                        mobMapWrap.classList.add('map-fullscreen');
                        document.body.style.overflow = 'hidden';

                        // ADD THIS LINE HERE:
                        _bindAC('dist-origin', 'mob');

                        setTimeout(() => {
                            if (mobMap) {
                                mobMap.invalidateSize();
                                mobMap.setView([lat, lng], 13);
                            }
                        }, 300);
                    }
                };
                // 1. Open Fullscreen when the wrapper is tapped
                mobMapWrap.addEventListener('click', (e) => {
                    // Don't trigger if clicking the close button
                    if (e.target === mapCloseBtn || mapCloseBtn.contains(e.target)) return;
                    openFullscreenMap();
                });

                // 2. Explicitly handle the Expand Button click (extra safety)
                if (expandBtn) {
                    expandBtn.addEventListener('click', (e) => {
                        e.stopPropagation(); // Prevents double-triggering the wrapper click
                        openFullscreenMap();
                    });
                }

                // 2b. Mobile mini-map Expand button
                const mobExpandBtn = document.getElementById('mob-map-expand-btn');
                if (mobExpandBtn) {
                    mobExpandBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        openFullscreenMap();
                    });
                }

                // 3. Close Fullscreen when the X button is tapped
                mapCloseBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    mobMapWrap.classList.remove('map-fullscreen');
                    document.body.style.overflow = '';

                    setTimeout(() => {
                        if (mobMap) {
                            mobMap.invalidateSize();
                            mobMap.setView([lat, lng], 6); // Reset zoom for mini-map
                        }
                    }, 300);
                });

                // 4. Zoom buttons inside dist-calc card
                const mobZoomIn = document.getElementById('mob-zoom-in');
                const mobZoomOut = document.getElementById('mob-zoom-out');
                if (mobZoomIn) mobZoomIn.addEventListener('click', e => { e.stopPropagation(); if (mobMap) mobMap.zoomIn(); });
                if (mobZoomOut) mobZoomOut.addEventListener('click', e => { e.stopPropagation(); if (mobMap) mobMap.zoomOut(); });

                // 5. Distance Calculator — popup mobile map
                const distCalcCard = document.getElementById('dist-calc-card');
                if (distCalcCard) distCalcCard.addEventListener('click', e => e.stopPropagation());

                const distCalcBtn = document.getElementById('dist-calc-btn');
                const distOriginInput = document.getElementById('dist-origin');
                if (distCalcBtn && distOriginInput) {
                    const doCalcMob = (e) => {
                        if (e) { e.stopPropagation(); e.preventDefault(); }
                        const origin = distOriginInput.value.trim();
                        if (!origin || !mobMap) return;
                        calcRoute(mobMap, 'mob', origin, {
                            btn: distCalcBtn,
                            origBtnHtml: '<i class="fa-solid fa-clock"></i> Show Time',
                            resultBox: document.getElementById('dist-result-box'),
                            distEl: document.getElementById('dist-val'),
                            durEl: document.getElementById('dur-val'),
                            addrEl: document.getElementById('dist-found-address'),
                            errorEl: document.getElementById('dist-error')
                        });
                    };
                    distCalcBtn.addEventListener('click', doCalcMob);
                    distOriginInput.addEventListener('keydown', e => { if (e.key === 'Enter') doCalcMob(e); });
                }
            }

            /* ═══ MOBILE GALLERY AUTO-SLIDER ═══ */
            const imgs = <?= json_encode($images) ?>;
            const total = imgs.length;
            let mIdx = 0, mTimer = null;
            const mSlides = document.querySelectorAll('#gal-mobile > img');
            const mDots = document.querySelectorAll('.gm-dot');
            const mCounter = document.getElementById('gm-counter');

            function mGo(i) {
                mIdx = ((i % total) + total) % total;
                mSlides.forEach((s, j) => s.classList.toggle('active', j === mIdx));
                mDots.forEach((d, j) => d.classList.toggle('active', j === mIdx));
                if (mCounter) mCounter.textContent = (mIdx + 1) + '/' + total;
            }
            function mStart() { mStop(); if (total > 1) mTimer = setInterval(() => mGo(mIdx + 1), 3500); }
            function mStop() { if (mTimer) { clearInterval(mTimer); mTimer = null; } }

            mDots.forEach(d => d.addEventListener('click', () => { mGo(parseInt(d.dataset.i)); mStop(); mStart(); }));

            // swipe
            let tx = 0;
            const gm = document.getElementById('gal-mobile');
            if (gm) {
                gm.addEventListener('touchstart', e => { tx = e.changedTouches[0].screenX; }, { passive: true });
                gm.addEventListener('touchend', e => { const d = tx - e.changedTouches[0].screenX; if (Math.abs(d) > 35) { mGo(d > 0 ? mIdx + 1 : mIdx - 1); mStop(); mStart(); } }, { passive: true });
            }
            mStart();

            /* ═══ LIGHTBOX ═══ */
            let ci = 0;
            const lb = document.getElementById('lb'), lbImg = document.getElementById('lb-img'), lbCt = document.getElementById('lb-count');

            function lbOpen(i) { ci = i; lbImg.src = imgs[ci]; lbCt.textContent = (ci + 1) + ' / ' + total; lb.classList.add('on'); document.body.style.overflow = 'hidden'; mStop(); }
            function lbClose() { lb.classList.remove('on'); document.body.style.overflow = ''; mStart(); }
            function lbNav(d) { ci = ((ci + d) % total + total) % total; lbImg.src = imgs[ci]; lbCt.textContent = (ci + 1) + ' / ' + total; }

            document.querySelectorAll('.gal-item').forEach(item => item.addEventListener('click', () => lbOpen(parseInt(item.dataset.i) || 0)));
            mSlides.forEach(img => img.addEventListener('click', () => lbOpen(parseInt(img.dataset.i) || 0)));

            document.getElementById('lb-close').addEventListener('click', lbClose);
            lb.addEventListener('click', e => { if (e.target === lb) lbClose(); });
            document.getElementById('lb-prev').addEventListener('click', e => { e.stopPropagation(); lbNav(-1); });
            document.getElementById('lb-next').addEventListener('click', e => { e.stopPropagation(); lbNav(1); });
            document.addEventListener('keydown', e => { if (!lb.classList.contains('on')) return; if (e.key === 'Escape') lbClose(); if (e.key === 'ArrowLeft') lbNav(-1); if (e.key === 'ArrowRight') lbNav(1); });

            /* ═══ STICKY BAR (desktop) ═══ */
            const stickyBar = document.getElementById('sticky-bar');
            const headAnchor = document.getElementById('dp-head-anchor');
            if (stickyBar && headAnchor && window.innerWidth > 920) {
                const obs = new IntersectionObserver(([e]) => { stickyBar.classList.toggle('show', !e.isIntersecting); }, { threshold: 0, rootMargin: '-80px 0px 0px 0px' });
                obs.observe(headAnchor);
            }

            /* ═══ DRAWER (kept for fallback) ═══ */
            const drwBg = document.getElementById('drw-bg'), drw = document.getElementById('drw');
            function openDrw() { drwBg.classList.add('on'); drw.classList.add('on'); document.body.style.overflow = 'hidden'; }
            function closeDrw() { drwBg.classList.remove('on'); drw.classList.remove('on'); document.body.style.overflow = ''; }
            if (drwBg) drwBg.addEventListener('click', closeDrw);

            /* ═══ LOGIN PROMPT POPUP ═══ */
            const _isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
            const _loginRedirect = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            (function () {
                const overlay = document.getElementById('login-prompt-overlay');
                const box = document.getElementById('login-prompt-box');
                const cancelBtn = document.getElementById('login-prompt-cancel');
                const okBtn = document.getElementById('login-prompt-ok');
                const msgEl = box ? box.querySelector('p') : null;
                if (!overlay || !box) return;
                okBtn.href = _loginRedirect;
                window._showLoginPrompt = function (amount) {
                    if (amount && msgEl) {
                        msgEl.innerHTML = 'Login to place your offer of <strong style="color:#0ea5e9;">Rs.\u00a0' + Number(amount).toLocaleString() + '</strong>. <b>It only takes a moment.</b>';
                    } else if (msgEl) {
                        msgEl.innerHTML = 'You need to be logged in to place an offer. <b>It only takes a moment.</b>';
                    }
                    overlay.style.display = 'flex';
                    requestAnimationFrame(() => { box.style.transform = 'scale(1)'; box.style.opacity = '1'; });
                };
                function hidePrompt() {
                    box.style.transform = 'scale(0.9)'; box.style.opacity = '0';
                    setTimeout(() => { overlay.style.display = 'none'; }, 220);
                }
                cancelBtn.addEventListener('click', hidePrompt);
                overlay.addEventListener('click', (e) => { if (e.target === overlay) hidePrompt(); });
            })();

            /* ═══ NOUI SLIDER INITIALIZATION & SYNC ═══ */
            (function () {
                const minBid = <?= (int) $minBidValue ?>;
                const maxBid = <?= (int) $maxBidValue ?>;
                const currentBid = maxBid;

                function fmtLive(n) {
                    n = Number(n);
                    if (n >= 1000000) return 'Rs. ' + (n / 1000000).toFixed(2).replace(/\.?0+$/, '') + 'M';
                    if (n >= 1000) return 'Rs. ' + (n / 1000).toFixed(1).replace(/\.?0+$/, '') + 'K';
                    return 'Rs. ' + n.toLocaleString();
                }

                const sIds = ['bid-slider', 'mb-bar-slider', 'mob-bid-slider'];
                const sliders = [];

                sIds.forEach(id => {
                    const el = document.getElementById(id);
                    if (el && typeof noUiSlider !== 'undefined') {
                        noUiSlider.create(el, {
                            start: [currentBid],
                            connect: [true, false],
                            step: 5000,
                            range: { 'min': minBid, 'max': maxBid },
                            format: { to: v => Math.round(v), from: v => Number(v) }
                        });
                        sliders.push(el);
                    }
                });

                // Sync Function
                function syncSliders(value, callerEl) {
                    sliders.forEach(s => {
                        if (s !== callerEl) {
                            s.noUiSlider.set(value);
                        }
                    });
                    // Update Displays
                    const fmt = Number(value).toLocaleString();
                    const liveFmt = fmtLive(value);

                    ['bid-slider-live', 'mb-bar-slider-live', 'mob-bid-slider-live'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = liveFmt;
                    });

                    ['bid-amount-display', 'mob-bid-display'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = fmt;
                    });
                }

                sliders.forEach(s => {
                    s.noUiSlider.on('slide', (values) => {
                        syncSliders(values[0], s);
                    });
                });

                // --- MOBILE BAR SUBMIT ---
                const mobOpen = document.getElementById('mob-open');
                if (mobOpen) {
                    mobOpen.addEventListener('click', async () => {
                        const slider = document.getElementById('mb-bar-slider');
                        const amount = (slider && slider.noUiSlider) ? slider.noUiSlider.get() : <?= $maxBidValue ?>;
                        if (!_isLoggedIn) { window._showLoginPrompt(amount); return; }
                        mobOpen.disabled = true;
                        mobOpen.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                        try {
                            const fd = new FormData();
                            fd.append('apartment_id', '<?= $id ?>');
                            fd.append('amount', amount);
                            fd.append('message', '');
                            const res = await fetch('api/place_bid.php', { method: 'POST', body: fd });
                            const data = await res.json();
                            if (data.success) {
                                mobOpen.innerHTML = '<i class="fa-solid fa-check"></i> Sent';
                                mobOpen.style.background = '#10b981';
                            } else {
                                mobOpen.disabled = false;
                                mobOpen.innerHTML = '<i class="fa-solid fa-gavel"></i> Offer';
                                alert(data.error || 'Failed to submit offer.');
                            }
                        } catch (e) {
                            mobOpen.disabled = false;
                            mobOpen.innerHTML = '<i class="fa-solid fa-gavel"></i> Offer';
                        }
                    });
                }

                // --- GENERAL BID SUBMIT ---
                function initBidSubmission(bId, sId, mId, fId) {
                    const b = document.getElementById(bId), s = document.getElementById(sId), m = document.getElementById(mId), f = document.getElementById(fId);
                    if (!b || !s) return;
                    b.addEventListener('click', async () => {
                        const amount = s.noUiSlider ? s.noUiSlider.get() : <?= $maxBidValue ?>;
                        if (!_isLoggedIn) { window._showLoginPrompt(amount); return; }
                        b.disabled = true; b.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
                        try {
                            const fd = new FormData(); fd.append('apartment_id', b.dataset.apt); fd.append('amount', amount); fd.append('message', m ? m.value : '');
                            const res = await fetch('api/place_bid.php', { method: 'POST', body: fd }); const data = await res.json();
                            if (f) f.style.display = 'block';
                            if (data.success) { if (f) { f.style.color = '#10b981'; f.innerText = 'Offer submitted!'; } b.innerHTML = '<i class="fa-solid fa-check"></i> Sent'; b.style.background = '#10b981'; }
                            else { if (f) { f.style.color = '#ef4444'; f.innerText = data.error || 'Failed.'; } b.disabled = false; b.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Offer'; }
                        } catch (e) { if (f) { f.style.color = '#ef4444'; f.innerText = 'Network error.'; } b.disabled = false; b.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Offer'; }
                    });
                }
                initBidSubmission('submit-bid-btn', document.getElementById('bid-slider'), 'bid-message', 'bid-feedback');
                initBidSubmission('mob-submit-btn', document.getElementById('mob-bid-slider'), 'mob-bid-message', 'mob-bid-feedback');
            })();

            /* ═══ DESKTOP SLIDER LOGIC ═══ */
            const dtHero = document.getElementById('dt-hero');
            const dtSlides = document.querySelectorAll('.dt-slide');
            const dtThumbs = document.querySelectorAll('.dt-thumb');
            const dtPrev = document.querySelector('.dt-prev');
            const dtNext = document.querySelector('.dt-next');
            let dtIdx = 0, dtTimer = null;

            function updateDtSlider(idx) {
                if (dtSlides.length === 0) return;
                dtIdx = ((idx % total) + total) % total;
                dtSlides.forEach((s, j) => {
                    s.style.opacity = j === dtIdx ? '1' : '0';
                    s.style.zIndex = j === dtIdx ? '1' : '0';
                });
                dtThumbs.forEach((t, j) => {
                    t.style.borderColor = j === dtIdx ? '#fff' : 'transparent';
                    if (j === dtIdx) {
                        const strip = document.getElementById('dt-thumb-strip');
                        if (strip) {
                            const thumbLeft = t.offsetLeft;
                            const thumbWidth = t.offsetWidth;
                            const stripWidth = strip.offsetWidth;
                            strip.scrollTo({ left: thumbLeft - stripWidth / 2 + thumbWidth / 2, behavior: 'smooth' });
                        }
                    }
                });
            }

            function dtStart() {
                dtStop();
                if (total > 1) dtTimer = setInterval(() => updateDtSlider(dtIdx + 1), 3500);
            }
            function dtStop() {
                if (dtTimer) { clearInterval(dtTimer); dtTimer = null; }
            }

            if (dtPrev && dtNext) {
                dtPrev.addEventListener('click', (e) => { e.stopPropagation(); updateDtSlider(dtIdx - 1); dtStop(); dtStart(); });
                dtNext.addEventListener('click', (e) => { e.stopPropagation(); updateDtSlider(dtIdx + 1); dtStop(); dtStart(); });
            }

            dtThumbs.forEach(t => {
                t.addEventListener('click', (e) => {
                    e.stopPropagation();
                    updateDtSlider(parseInt(t.dataset.i));
                    dtStop(); dtStart();
                });
            });

            dtSlides.forEach(img => {
                img.addEventListener('click', () => {
                    if (typeof lbOpen === 'function') lbOpen(parseInt(img.dataset.i) || 0);
                });
            });

            // Desktop swipe (useful for touch laptops/tablets)
            let dtTx = 0;
            if (dtHero) {
                dtHero.addEventListener('touchstart', e => { dtTx = e.changedTouches[0].screenX; dtStop(); }, { passive: true });
                dtHero.addEventListener('touchend', e => {
                    const d = dtTx - e.changedTouches[0].screenX;
                    if (Math.abs(d) > 35) { updateDtSlider(d > 0 ? dtIdx + 1 : dtIdx - 1); }
                    dtStart();
                }, { passive: true });
                dtHero.addEventListener('mouseenter', dtStop);
                dtHero.addEventListener('mouseleave', dtStart);
            }
            dtStart();

            // Lightbox swipe
            let lbTx = 0;
            if (lb) {
                lb.addEventListener('touchstart', e => { lbTx = e.changedTouches[0].screenX; }, { passive: true });
                lb.addEventListener('touchend', e => {
                    const d = lbTx - e.changedTouches[0].screenX;
                    if (Math.abs(d) > 35) { lbNav(d > 0 ? 1 : -1); }
                }, { passive: true });
            }

            /* ═══ MAP LIGHTBOX (EXPAND) ═══ */
            const mapLb = document.getElementById('map-lb');
            const mapExpandText = document.getElementById('map-expand-text');
            const mapLbClose = document.getElementById('map-lb-close');
            let expandedMapObj = null;

            if (mapExpandText && mapLb) {
                mapExpandText.addEventListener('click', () => {
                    mapLb.style.display = 'flex';
                    void mapLb.offsetWidth; // Force reflow
                    mapLb.style.opacity = '1';
                    document.body.style.overflow = 'hidden';

                    if (!expandedMapObj) {
                        expandedMapObj = L.map('expanded-map', { zoomControl: true }).setView([lat, lng], 13);
                        L.tileLayer(tileUrl, tileOpts).addTo(expandedMapObj);
                        L.marker([lat, lng], { icon: pinIcon }).addTo(expandedMapObj).bindPopup(popupHtml);
                    }
                    setTimeout(() => {
                        expandedMapObj.invalidateSize();
                        expandedMapObj.setView([lat, lng], 14, { animate: false });
                        // Init autocomplete lazily — element must be visible first
                        if (window._bindAC) _bindAC('dist-origin-exp', 'exp');
                    }, 300);
                });

                mapLbClose.addEventListener('click', () => {
                    mapLb.style.opacity = '0';
                    setTimeout(() => {
                        mapLb.style.display = 'none';
                        document.body.style.overflow = '';
                    }, 300);
                });
            }

            /* Wire expanded map zoom buttons */
            const expZoomIn = document.getElementById('exp-zoom-in');
            const expZoomOut = document.getElementById('exp-zoom-out');
            if (expZoomIn) expZoomIn.addEventListener('click', () => { if (expandedMapObj) expandedMapObj.zoomIn(); });
            if (expZoomOut) expZoomOut.addEventListener('click', () => { if (expandedMapObj) expandedMapObj.zoomOut(); });

            /* Wire expanded map distance calculator — Google Distance Matrix (real data) */
            const distBtnExp = document.getElementById('dist-calc-btn-exp');
            const distInputExp = document.getElementById('dist-origin-exp');
            const distClearExp = document.getElementById('dist-clear-exp');
            if (distBtnExp && distInputExp) {
                const fmtDist = m => m >= 1000 ? (m / 1000).toFixed(1) + ' km' : m + ' m';
                const fmtSec = s => {
                    const totalMins = Math.round(s / 60);
                    const days = Math.floor(totalMins / 1440);
                    const hours = Math.floor((totalMins % 1440) / 60);
                    const mins = totalMins % 60;
                    if (days > 0 && hours > 0) return days + 'd ' + hours + 'h';
                    if (days > 0) return days + 'd';
                    if (hours > 0 && mins > 0) return hours + 'h ' + mins + ' mins';
                    if (hours > 0) return hours + 'h';
                    return mins + ' mins';
                };

                // Haversine straight-line distance in km (free, no API call)
                const haversineKm = (lat1, lng1, lat2, lng2) => {
                    const R = 6371, toRad = d => d * Math.PI / 180;
                    const dLat = toRad(lat2 - lat1), dLng = toRad(lng2 - lng1);
                    const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
                    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                };

                // Helper: call Google Distance Matrix for one travel mode, returns {dist, dur} or null
                const gmDist = (origin, dest, mode) => new Promise(resolve => {
                    const svc = new google.maps.DistanceMatrixService();
                    svc.getDistanceMatrix({
                        origins: [origin],
                        destinations: [dest],
                        travelMode: google.maps.TravelMode[mode],
                        unitSystem: google.maps.UnitSystem.METRIC,
                    }, (res, status) => {
                        if (status !== 'OK') return resolve(null);
                        const el = res.rows[0]?.elements[0];
                        if (!el || el.status !== 'OK') return resolve(null);
                        resolve({ dist: el.distance.value, dur: el.duration.value, distTxt: el.distance.text, durTxt: el.duration.text });
                    });
                });

                const doCalcExp = async () => {
                    if (!expandedMapObj) return;
                    const origin = distInputExp.value.trim();
                    if (!origin) return;
                    document.getElementById('dist-result-exp').style.display = 'none';
                    document.getElementById('dist-error-exp').style.display = 'none';
                    distBtnExp.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                    distBtnExp.disabled = true;
                    try {
                        // Resolve origin coords first (free — Nominatim or cached Places)
                        let oLat, oLng;
                        const pre = window._distPlaceCoords && window._distPlaceCoords['exp'];
                        if (pre) { oLat = pre.lat; oLng = pre.lng; }
                        else {
                            const gj = await (await fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(origin) + '&format=json&limit=1', { headers: { 'Accept-Language': 'en' } })).json();
                            if (!gj.length) throw new Error('Location not found. Try a more specific address.');
                            oLat = parseFloat(gj[0].lat); oLng = parseFloat(gj[0].lon);
                        }

                        // Check straight-line distance — bail before any paid API call
                        const straightKm = haversineKm(oLat, oLng, lat, lng);
                        if (straightKm > 100) throw new Error('Distance too far — location is over 100 km away.');

                        const dest = { lat, lng };
                        // Fire all 3 modes in parallel via Google
                        const [drive, walk, transit] = await Promise.all([
                            gmDist(origin, dest, 'DRIVING'),
                            gmDist(origin, dest, 'WALKING'),
                            gmDist(origin, dest, 'TRANSIT'),
                        ]);

                        if (!drive && !walk && !transit) throw new Error('Location not found or no route available.');

                        // Drive
                        if (drive) {
                            document.getElementById('dist-val-exp').textContent = drive.distTxt;
                            document.getElementById('dur-val-exp').textContent = fmtSec(drive.dur);
                        }
                        // Walk
                        if (walk) {
                            document.getElementById('dist-val-exp-walk').textContent = walk.distTxt;
                            document.getElementById('dur-val-exp-walk').textContent = fmtSec(walk.dur);
                        } else {
                            document.getElementById('dist-val-exp-walk').textContent = '—';
                            document.getElementById('dur-val-exp-walk').textContent = 'N/A';
                        }
                        // Transit
                        if (transit) {
                            document.getElementById('dist-val-exp-transit').textContent = transit.distTxt;
                            document.getElementById('dur-val-exp-transit').textContent = fmtSec(transit.dur);
                        } else {
                            document.getElementById('dist-val-exp-transit').textContent = drive ? drive.distTxt : '—';
                            document.getElementById('dur-val-exp-transit').textContent = 'N/A';
                        }

                        document.getElementById('dist-result-exp').style.display = 'block';

                        // Draw route on Leaflet map via OSRM (oLat/oLng already resolved above)
                        if (drive && oLat && oLng) {
                            const rd = await fetch(`https://router.project-osrm.org/route/v1/driving/${oLng},${oLat};${lng},${lat}?overview=full&geometries=geojson`).then(r => r.json());
                            if (rd.code === 'Ok' && rd.routes.length) {
                                if (routeStates['exp']) routeStates['exp'].forEach(l => expandedMapObj.removeLayer(l));
                                const routeLine = L.geoJSON(rd.routes[0].geometry, { style: { color: '#0ea5e9', weight: 5, opacity: 0.85 } }).addTo(expandedMapObj);
                                routeStates['exp'] = [routeLine];
                            }
                        }
                    } catch (e) {
                        const errEl = document.getElementById('dist-error-exp');
                        errEl.textContent = e.message; errEl.style.display = 'block';
                    } finally {
                        distBtnExp.innerHTML = '<i class="fa-solid fa-clock"></i> Show Time';
                        distBtnExp.disabled = false;
                    }
                };
                distBtnExp.addEventListener('click', doCalcExp);
                distInputExp.addEventListener('keydown', e => { if (e.key === 'Enter') doCalcExp(); });
                if (distClearExp) {
                    distClearExp.addEventListener('click', () => {
                        document.getElementById('dist-result-exp').style.display = 'none';
                        document.getElementById('dist-error-exp').style.display = 'none';
                        distInputExp.value = '';
                        delete (window._distPlaceCoords || {})['exp'];
                        if (expandedMapObj && routeStates['exp']) { routeStates['exp'].forEach(l => expandedMapObj.removeLayer(l)); delete routeStates['exp']; }
                        if (expandedMapObj) expandedMapObj.setView([lat, lng], 14, { animate: true });
                    });
                }
            }

        });
    </script>

    <!-- Animated fade+rotate placeholder for distance inputs -->
    <script>
        (function () {
            var phrases = [

                'Time: to Work ⏰',
                'Time: to School 🎒',
                'Time: to Mom / Sister\'s 🏡',

                'TYPE: any 📍 Google Location',
            ];

            function initFadePlaceholder(input, overlay) {
                var idx = 0;
                var stopped = false;
                var timer;

                function showNext() {
                    if (stopped || input === document.activeElement || input.value !== '') return;
                    // Set text and fade in
                    overlay.textContent = phrases[idx];
                    overlay.style.opacity = '0.5';
                    // Hold for 1s then fade out
                    timer = setTimeout(function () {
                        if (stopped) return;
                        overlay.style.opacity = '0';
                        // After fade out completes, advance to next phrase
                        timer = setTimeout(function () {
                            idx = (idx + 1) % phrases.length;
                            showNext();
                        }, 300);
                    }, 1000);
                }

                input.addEventListener('focus', function () {
                    stopped = true;
                    clearTimeout(timer);
                    overlay.style.opacity = '0';
                });

                input.addEventListener('input', function () {
                    if (input.value) overlay.style.opacity = '0';
                });

                input.addEventListener('blur', function () {
                    if (!input.value) {
                        stopped = false;
                        // Small delay so focus-out animation settles
                        timer = setTimeout(showNext, 200);
                    }
                });

                // Staggered start
                timer = setTimeout(showNext, input.id === 'dist-origin' ? 500 : 1100);
            }

            document.addEventListener('DOMContentLoaded', function () {
                var configs = [
                    { inputId: 'dist-origin', overlayId: 'dist-origin-ph' },
                    { inputId: 'dist-origin-large', overlayId: 'dist-origin-large-ph' },
                    { inputId: 'dist-origin-exp', overlayId: 'dist-origin-exp-ph' }
                ];
                configs.forEach(function (c) {
                    var el = document.getElementById(c.inputId);
                    var ov = document.getElementById(c.overlayId);
                    if (el && ov) initFadePlaceholder(el, ov);
                });
            });
        })();
    </script>

    <!-- Google Places autocomplete for distance calculator inputs -->
    <script>
        window._distPlaceCoords = {};
        window._acInited = {};
        function _bindAC(id, key) {
            // Remove the window._acInited[key] check to allow re-binding if needed
            var el = document.getElementById(id);
            if (!el) return;

            var ac = new google.maps.places.Autocomplete(el, {
                componentRestrictions: { country: 'lk' },
                fields: ['geometry', 'formatted_address', 'name']
            });

            ac.addListener('place_changed', function () {
                var place = ac.getPlace();
                if (!place.geometry) return;
                window._distPlaceCoords[key] = {
                    lat: place.geometry.location.lat(),
                    lng: place.geometry.location.lng(),
                    label: place.formatted_address || place.name || el.value
                };
            });
        }
        function initDistAutocomplete() {
            _bindAC('dist-origin', 'mob');
            _bindAC('dist-origin-large', 'large');
        }
    </script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBwXIu1h80V9Xapeyre_obRvjElwsi7dFg&libraries=places&callback=initDistAutocomplete"
        async defer></script>
</body>


</html>