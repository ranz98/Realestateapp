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

$images = [];
try { $images = json_decode($apt['images'], true); } catch(Exception $e) {}
if (!$images || count($images) === 0) {
    $images = ['https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=800'];
}

$features = $apt['features'] ? explode(',', $apt['features']) : [];
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <style>
        .detail-page { max-width: 1100px; margin: 0 auto; padding: 2rem 1rem; }
        .detail-back { display: inline-flex; align-items: center; gap: 0.4rem; color: var(--text-secondary); text-decoration: none; font-size: 0.9rem; margin-bottom: 1.5rem; }
        .detail-back:hover { color: var(--primary); }
        .detail-header { margin-bottom: 1.5rem; }
        .detail-header h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 0.3rem; }
        .detail-header .detail-address { color: var(--text-secondary); font-size: 0.95rem; display: flex; align-items: center; gap: 0.4rem; }
        .detail-price { font-size: 1.6rem; font-weight: 700; color: var(--primary); margin-top: 0.5rem; }
        .detail-tags { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.8rem; }
        .detail-tag { background: var(--bg-main); border: 1px solid var(--border-glass); padding: 0.3rem 0.8rem; border-radius: var(--radius-sm); font-size: 0.85rem; color: var(--text-secondary); }
        
        /* Gallery */
        .gallery { display: grid; grid-template-columns: 2fr 1fr; gap: 0.5rem; border-radius: var(--radius-md); overflow: hidden; margin-bottom: 2rem; max-height: 400px; }
        .gallery-main img { width: 100%; height: 100%; object-fit: cover; display: block; cursor: pointer; }
        .gallery-side { display: flex; flex-direction: column; gap: 0.5rem; overflow: hidden; }
        .gallery-side img { flex: 1; width: 100%; object-fit: cover; display: block; cursor: pointer; min-height: 0; }
        
        /* Details Grid */
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .detail-section { background: var(--bg-card); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow-card); }
        .detail-section h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; padding-bottom: 0.6rem; border-bottom: 1px solid var(--border-glass); }
        
        .spec-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .spec-item { display: flex; flex-direction: column; gap: 0.2rem; }
        .spec-label { font-size: 0.8rem; color: var(--text-secondary); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .spec-value { font-size: 0.95rem; font-weight: 600; }
        
        .feature-list { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .feature-chip { background: rgba(79, 70, 229, 0.1); color: var(--primary); border: 1px solid rgba(79, 70, 229, 0.2); padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.85rem; font-weight: 500; }
        
        .description-text { line-height: 1.7; color: var(--text-secondary); font-size: 0.95rem; }
        
        .detail-map { height: 300px; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border-glass); margin-top: 1rem; }
        
        .owner-card { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--bg-main); border-radius: var(--radius-sm); margin-top: 1rem; }
        .owner-avatar { width: 48px; height: 48px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; flex-shrink: 0; }
        .owner-info h4 { font-size: 0.95rem; margin-bottom: 0.1rem; }
        .owner-info p { font-size: 0.85rem; color: var(--text-secondary); }

        .lightbox { position: fixed; top: 0; left: 0; width: 100vw; height: 100dvh; background: rgba(0,0,0,0.9); z-index: 3000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .lightbox.active { display: flex; animation: fadeIn 0.2s ease; }
        .lightbox img { max-width: 90%; max-height: 90vh; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .lightbox-close { position: absolute; top: 20px; right: 20px; color: white; font-size: 2.5rem; background: none; border: none; cursor: pointer; z-index: 3010; }
        @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }

        @media (max-width: 768px) {
            .gallery { grid-template-columns: 1fr; max-height: 300px; }
            .gallery-side { flex-direction: row; }
            .gallery-side img { max-height: 80px; }
            .detail-grid { grid-template-columns: 1fr; gap: 1rem; }
            .spec-grid { grid-template-columns: 1fr; }
            .detail-header h1 { font-size: 1.4rem; }
            .detail-price { font-size: 1.3rem; }
            .detail-page { padding: 1rem 0.8rem; }
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

        <div class="detail-header">
            <h1><?= htmlspecialchars($apt['title']) ?></h1>
            <div class="detail-address"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($apt['address']) ?></div>
            <div class="detail-price">Rs. <?= number_format($apt['price']) ?> /month</div>
            <div class="detail-tags">
                <span class="detail-tag" style="background: var(--primary); color: white; border: none; font-weight: 600;"><i class="fa-solid fa-tag"></i> For <?= htmlspecialchars($apt['listing_mode'] ?? 'Rent') ?></span>
                <span class="detail-tag"><i class="fa-solid fa-building"></i> <?= htmlspecialchars($apt['type']) ?></span>
                
                <?php if($apt['type'] !== 'Land'): ?>
                    <span class="detail-tag"><i class="fa-solid fa-bed"></i> <?= htmlspecialchars($apt['bedrooms']) ?> Bed</span>
                    <span class="detail-tag"><i class="fa-solid fa-bath"></i> <?= (int)$apt['baths'] ?> Bath</span>
                    <?php if($apt['size_sqft'] > 0): ?>
                        <span class="detail-tag"><i class="fa-solid fa-ruler-combined"></i> <?= number_format($apt['size_sqft']) ?> sqft</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="detail-tag"><i class="fa-solid fa-ruler-combined"></i> <?= (float)$apt['size_perches'] ?> Perches</span>
                <?php endif; ?>
                <span class="detail-tag"><i class="fa-solid fa-clock"></i> <?= date('M d, Y', strtotime($apt['created_at'])) ?></span>
            </div>
        </div>

        <!-- Image Gallery -->
        <div class="gallery">
            <div class="gallery-main">
                <img src="<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($apt['title']) ?>">
            </div>
            <?php if(count($images) > 1): ?>
            <div class="gallery-side">
                <?php for($i = 1; $i < min(count($images), 5); $i++): ?>
                    <img src="<?= htmlspecialchars($images[$i]) ?>" alt="Photo <?= $i+1 ?>">
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Two Column Detail Grid -->
        <div class="detail-grid">
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
                    <?php else: ?>
                        <div class="spec-item">
                            <span class="spec-label">Size</span>
                            <span class="spec-value"><?= (float)$apt['size_perches'] ?> Perches</span>
                        </div>
                    <?php endif; ?>
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
                    <div class="spec-item">
                        <span class="spec-label">Address</span>
                        <span class="spec-value"><?= htmlspecialchars($apt['address']) ?></span>
                    </div>
                </div>

                <?php if(count($features) > 0): ?>
                <h3 style="margin-top: 1.5rem;"><i class="fa-solid fa-star"></i> Features & Amenities</h3>
                <div class="feature-list">
                    <?php foreach($features as $f): ?>
                        <span class="feature-chip"><?= htmlspecialchars(trim($f)) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="detail-section">
                    <h3><i class="fa-solid fa-file-lines"></i> Description</h3>
                    <div class="description-text"><?= nl2br(htmlspecialchars($apt['description'])) ?></div>
                </div>

                <div class="detail-section" style="margin-top: 1rem;">
                    <h3><i class="fa-solid fa-map-pin"></i> Location</h3>
                    <div id="detail-map" class="detail-map"></div>
                </div>

                <div class="detail-section" style="margin-top: 1rem;">
                    <h3><i class="fa-solid fa-user"></i> Listed By & Contact</h3>
                    <div class="owner-card" style="flex-direction: column; align-items: stretch;">
                        <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 0.5rem;">
                            <div class="owner-avatar"><?= strtoupper(substr($apt['owner_name'], 0, 1)) ?></div>
                            <div class="owner-info">
                                <h4><?= htmlspecialchars($apt['owner_name']) ?></h4>
                                <p style="font-size: 0.8rem; color: var(--text-secondary);">Verified Lister</p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php $sEmail = $apt['seller_email'] ?: $apt['owner_email']; ?>
                            <a href="mailto:<?= htmlspecialchars($sEmail) ?>" class="btn-primary" style="flex: 1; text-align: center; text-decoration: none; font-size: 0.85rem;"><i class="fa-solid fa-envelope"></i> Email</a>
                            <?php if(!empty($apt['seller_phone'])): ?>
                                <a href="tel:<?= htmlspecialchars($apt['seller_phone']) ?>" class="btn-secondary" style="flex: 1; text-align: center; text-decoration: none; font-size: 0.85rem;"><i class="fa-solid fa-phone"></i> Phone</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="detail-section" style="margin-top: 1rem; border: 2px solid var(--primary); box-shadow: 0 4px 15px rgba(79, 70, 229, 0.1);">
                    <h3><i class="fa-solid fa-gavel"></i> Quick Offer / Bid</h3>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['user_id'] == $apt['user_id']): ?>
                            <p style="color: var(--text-secondary); text-align: center; padding: 1rem 0;">You cannot bid on your own listing.</p>
                        <?php else: ?>
                            <div style="padding: 0.5rem 0;">
                                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem;">Your Offer Amount</label>
                                <input type="range" id="bid-slider" style="width: 100%; margin-bottom: 1rem; accent-color: var(--primary);" 
                                       min="<?= (int)$apt['price'] * 0.8 ?>" max="<?= (int)$apt['price'] * 2 ?>" step="5000" value="<?= (int)$apt['price'] ?>">
                                <div style="text-align: center; font-size: 1.5rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem;">
                                    Rs. <span id="bid-amount-display"><?= number_format($apt['price']) ?></span>
                                </div>
                                <input type="text" id="bid-message" style="width: 100%; margin-bottom: 1rem; padding: 0.8rem; border-radius: 8px; border: 1px solid var(--border-glass);" placeholder="Message to seller (Optional)...">
                                <button id="submit-bid-btn" class="btn-primary" style="width: 100%; justify-content: center; font-size: 1.05rem;" data-apt="<?= $id ?>">Submit Offer</button>
                                <div id="bid-feedback" style="margin-top: 0.8rem; text-align: center; font-size: 0.9rem; font-weight: 600; display: none;"></div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 1.5rem 0;">
                            <i class="fa-solid fa-lock" style="font-size: 2rem; color: var(--border-glass); margin-bottom: 1rem; display: block;"></i>
                            <p style="margin-bottom: 1rem; font-size:0.9rem;">Please log in to place an offer.</p>
                            <a href="login.php" class="btn-primary" style="display: inline-block; text-decoration: none;">Login to Bid</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="lightbox" id="lightbox">
        <button class="lightbox-close" id="lightbox-close"><i class="fa-solid fa-xmark"></i></button>
        <img src="" alt="Fullscreen image" id="lightbox-img">
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Map
            const lat = <?= (float)$apt['lat'] ?>;
            const lng = <?= (float)$apt['lng'] ?>;
            const map = L.map('detail-map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            L.marker([lat, lng]).addTo(map)
                .bindPopup('<b><?= addslashes(htmlspecialchars($apt['title'])) ?></b><br><?= addslashes(htmlspecialchars($apt['address'])) ?>')
                .openPopup();

            // Lightbox
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightbox-img');
            const closeBtn = document.getElementById('lightbox-close');
            
            document.querySelectorAll('.gallery img').forEach(img => {
                img.addEventListener('click', () => {
                    lightboxImg.src = img.src;
                    lightbox.classList.add('active');
                });
            });
            
            closeBtn.addEventListener('click', () => lightbox.classList.remove('active'));
            lightbox.addEventListener('click', (e) => {
                if (e.target === lightbox) lightbox.classList.remove('active');
            });

            // Bid logic
            const bidSlider = document.getElementById('bid-slider');
            const bidDisplay = document.getElementById('bid-amount-display');
            const submitBtn = document.getElementById('submit-bid-btn');
            
            if (bidSlider && bidDisplay && submitBtn) {
                bidSlider.addEventListener('input', (e) => {
                    bidDisplay.innerText = Number(e.target.value).toLocaleString();
                });

                submitBtn.addEventListener('click', async () => {
                    const amount = bidSlider.value;
                    const message = document.getElementById('bid-message').value;
                    const aptId = submitBtn.dataset.apt;
                    const feedback = document.getElementById('bid-feedback');

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
                            feedback.style.color = '#10b981'; // Green
                            feedback.innerText = 'Offer submitted successfully!';
                            submitBtn.style.display = 'none';
                        } else {
                            feedback.style.color = '#ef4444'; // Red
                            feedback.innerText = data.error || 'Failed to submit offer.';
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = 'Submit Offer';
                        }
                    } catch (e) {
                        feedback.style.display = 'block';
                        feedback.style.color = '#ef4444';
                        feedback.innerText = 'Network error. Please try again.';
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Submit Offer';
                    }
                });
            }
        });
    </script>
</body>
</html>
