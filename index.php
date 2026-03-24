<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyHomeMyLand.LK - Sri Lanka Real Estate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.css" />
<?php include 'get-theme.php'; ?>
</head>

<body>
    <header class="site-header">
        <nav class="navbar">
<a href="index.php" class="logo" style="color: #000000; text-decoration: none;">
    <i class="fa-solid fa-house-chimney-window" style="color: #4f46e5;"></i> MyHomeMyLand.LK
</a>
            <div class="nav-links">
                <button id="theme-toggle" class="theme-toggle" title="Toggle Dark/Light Mode">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <a href="index.php" class="active">Explore</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="admin.php">Admin</a>
                    <?php endif; ?>
                    <a href="list-apartment.php" class="btn-primary">List Property</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- ═══ DESKTOP: Single clean filter row ═══ -->
        <div class="filter-bar-desktop">
            <div class="filter-bar-inner">
                <div class="mode-toggle">
                    <button class="mode-btn mode-active" data-mode="Rent">Rent</button>
                    <button class="mode-btn" data-mode="Buy">Buy</button>
                </div>
                <span class="filter-bar-divider"></span>
                <input type="text" id="search-text" class="search-input" placeholder="Search by title or keyword...">
                <select id="filter-type" class="filter-select">
                    <option value="All">Type</option>
                    <option value="Apartment">Apartment</option>
                    <option value="House">House</option>
                    <option value="Villa">Villa</option>
                    <option value="Commercial">Commercial</option>
                    <option value="Land">Land</option>
                </select>
                <select id="filter-location" class="filter-select">
                    <option value="All">Location</option>
                    <option value="Colombo">Colombo</option>
                    <option value="Kotte">Kotte</option>
                    <option value="Dehiwala">Dehiwala</option>
                    <option value="Kandy">Kandy</option>
                    <option value="Galle">Galle</option>
                </select>
                <select id="filter-beds" class="filter-select">
                    <option value="All">Beds</option>
                    <option value="1">1 Bed</option>
                    <option value="2">2 Beds</option>
                    <option value="3+">3+ Beds</option>
                    <option value="studio">Studio</option>
                </select>
                <select id="filter-baths" class="filter-select">
                    <option value="All">Baths</option>
                    <option value="1">1 Bath</option>
                    <option value="2">2 Baths</option>
                    <option value="3+">3+ Baths</option>
                </select>
                <select id="filter-min-price-select" class="filter-select">
                    <option value="">Min Price</option>
                    <option value="10000">Rs. 10k</option>
                    <option value="25000">Rs. 25k</option>
                    <option value="50000">Rs. 50k</option>
                    <option value="100000">Rs. 100k</option>
                    <option value="250000">Rs. 250k</option>
                    <option value="500000">Rs. 500k</option>
                </select>
                <select id="filter-max-price-select" class="filter-select">
                    <option value="">Max Price</option>
                    <option value="50000">Rs. 50k</option>
                    <option value="100000">Rs. 100k</option>
                    <option value="250000">Rs. 250k</option>
                    <option value="500000">Rs. 500k</option>
                    <option value="750000">Rs. 750k</option>
                    <option value="1000000">Rs. 1M+</option>
                </select>
                <select id="filter-sort" class="filter-select">
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="price_low">Price ↑</option>
                    <option value="price_high">Price ↓</option>
                    <option value="trending">Trending</option>
                </select>
                <button id="apply-filters" class="filter-search-btn" title="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
                <button id="clear-filters" class="filter-clear-btn" title="Clear"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div><!-- /.filter-bar-desktop -->

        <!-- ═══ MOBILE: Compact search + filter trigger ═══ -->
        <div class="filter-bar-mobile">
            <div class="filter-bar-mobile-inner">
                <input type="text" id="search-text-mobile" class="search-input" placeholder="Search...">
                <button id="apply-filters-mobile" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
                <button id="mobile-filter-btn" class="btn-secondary"><i class="fa-solid fa-sliders"></i></button>
            </div>
        </div>

        <!-- ═══ MOBILE: Full-screen filters modal ═══ -->
        <div class="filters-modal" id="filters-modal">
            <div class="filters-modal-header">
                <h3>Filters</h3>
                <button class="close-filters-btn" id="close-filters-btn"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="filters-modal-body">
                <div class="filters-grid">
                    <div class="filter-group" style="display:flex; justify-content:center;">
                        <div class="mode-toggle mode-toggle-mobile">
                            <button class="mode-btn mode-active" data-mode="Rent">Rent</button>
                            <button class="mode-btn" data-mode="Buy">Buy</button>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Property Type</label>
                        <select id="filter-type-mobile" class="filter-select"><option value="All">Type</option><option value="Apartment">Apartment</option><option value="House">House</option><option value="Villa">Villa</option><option value="Commercial">Commercial</option><option value="Land">Land</option></select>
                    </div>
                    <div class="filter-group">
                        <label>Location</label>
                        <select id="filter-location-mobile" class="filter-select"><option value="All">Location</option><option value="Colombo">Colombo</option><option value="Kotte">Kotte</option><option value="Dehiwala">Dehiwala</option><option value="Kandy">Kandy</option><option value="Galle">Galle</option></select>
                    </div>
                    <div class="filter-group">
                        <label>Beds</label>
                        <select id="filter-beds-mobile" class="filter-select"><option value="All">Beds</option><option value="1">1 Bed</option><option value="2">2 Beds</option><option value="3+">3+ Beds</option><option value="studio">Studio</option></select>
                    </div>
                    <div class="filter-group">
                        <label>Baths</label>
                        <select id="filter-baths-mobile" class="filter-select"><option value="All">Baths</option><option value="1">1 Bath</option><option value="2">2 Baths</option><option value="3+">3+ Baths</option></select>
                    </div>
                    <div class="filter-group price-group">
                        <label>Price Range</label>
                        <div class="price-slider-container">
                            <div id="price-slider"></div>
                            <div id="price-range-display" class="price-display">Rs. 0 - Rs. 500k</div>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Sort By</label>
                        <select id="filter-sort-mobile" class="filter-select"><option value="newest">Newest</option><option value="oldest">Oldest</option><option value="price_low">Price ↑</option><option value="price_high">Price ↓</option><option value="trending">Trending</option></select>
                    </div>
                    <div class="filter-group">
                        <button id="clear-filters-mobile" class="filter-select clear-btn">Clear All</button>
                    </div>
                </div>
            </div>
        </div><!-- /.filters-modal -->

    </header>

    <main class="main-container">
        <section class="listings-section" id="listings-section">
            <div id="initial-mode-overlay" class="mode-overlay">
                <div class="mode-overlay-content">
                    <div class="mode-overlay-btns">
                        <button class="btn-primary overlay-mode-btn" data-mode="Buy">Buy</button>
                        <button class="btn-primary overlay-mode-btn" data-mode="Rent">Rent</button>
                    </div>
                </div>
            </div>
            <div class="listings-grid" id="listings-grid">
                <p>Loading properties...</p>
            </div>
        </section>
        <section class="map-section" id="map-section">
            <div id="map"></div>
        </section>
    </main>

    <!-- ═══ Professional Footer ═══ -->
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <a href="index.php" class="footer-logo">
                    <i class="fa-solid fa-house-chimney-window"></i>
                    <span>MyHomeMyLand<small>.LK</small></span>
                </a>
                <p class="footer-tagline">Sri Lanka's trusted property marketplace.<br>Find your dream home today.</p>
                <div class="footer-social">
                    <a href="#" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="#" title="Twitter / X"><i class="fa-brands fa-x-twitter"></i></a>
                </div>
            </div>
            <div class="footer-links-grid">
                <div class="footer-col">
                    <h4>Explore</h4>
                    <a href="index.php">Buy Property</a>
                    <a href="index.php">Rent Property</a>
                    <a href="list-apartment.php">List Your Property</a>
                </div>
                <div class="footer-col">
                    <h4>Company</h4>
                    <a href="#">About Us</a>
                    <a href="#">Contact</a>
                    <a href="#">Careers</a>
                </div>
                <div class="footer-col">
                    <h4>Legal</h4>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> MyHomeMyLand.LK &mdash; All rights reserved.</p>
        </div>
    </footer>

    <!-- Mobile View Toggle Pill -->
    <div class="mobile-view-toggle" id="mobile-view-toggle">
        <button class="mvt-btn mvt-active" id="mvt-list" data-mode="list"><i class="fa-solid fa-list"></i><span>List</span></button>
        <button class="mvt-btn" id="mvt-split" data-mode="split"><i class="fa-solid fa-table-columns"></i><span>Split</span></button>
        <button class="mvt-btn" id="mvt-map" data-mode="map"><i class="fa-solid fa-map-location-dot"></i><span>Map</span></button>
    </div>

    <!-- Hidden inputs for JS price sync -->
    <input type="hidden" id="filter-min-price">
    <input type="hidden" id="filter-max-price">

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.js"></script>
    <script src="script.js?v=<?php echo time(); ?>"></script>
    <script>
    /* ── Desktop ↔ Mobile sync layer ── */
    document.addEventListener('DOMContentLoaded', () => {
        const minSel = document.getElementById('filter-min-price-select');
        const maxSel = document.getElementById('filter-max-price-select');
        const minHid = document.getElementById('filter-min-price');
        const maxHid = document.getElementById('filter-max-price');

        if (minSel) minSel.addEventListener('change', () => { minHid.value = minSel.value; });
        if (maxSel) maxSel.addEventListener('change', () => { maxHid.value = maxSel.value; });

        // Mobile search → desktop search sync
        const sMob = document.getElementById('search-text-mobile');
        const sDesk = document.getElementById('search-text');
        if (sMob && sDesk) sMob.addEventListener('input', () => { sDesk.value = sMob.value; });

        // Mobile apply → desktop apply
        const aMob = document.getElementById('apply-filters-mobile');
        const aDesk = document.getElementById('apply-filters');
        if (aMob && aDesk) aMob.addEventListener('click', () => aDesk.click());

        // Sync mobile selects → desktop selects on change
        [['filter-type-mobile','filter-type'],['filter-location-mobile','filter-location'],['filter-beds-mobile','filter-beds'],['filter-baths-mobile','filter-baths'],['filter-sort-mobile','filter-sort']].forEach(([m,d]) => {
            const mob = document.getElementById(m), desk = document.getElementById(d);
            if (mob && desk) mob.addEventListener('change', () => { desk.value = mob.value; desk.dispatchEvent(new Event('change')); });
        });

        // Sync mobile mode → desktop mode
        document.querySelectorAll('.mode-toggle-mobile .mode-btn').forEach(mBtn => {
            mBtn.addEventListener('click', () => {
                document.querySelectorAll('.filter-bar-desktop .mode-btn').forEach(dBtn => {
                    dBtn.classList.toggle('mode-active', dBtn.dataset.mode === mBtn.dataset.mode);
                });
            });
        });

        // Mobile clear → desktop clear
        const cMob = document.getElementById('clear-filters-mobile');
        const cDesk = document.getElementById('clear-filters');
        if (cMob && cDesk) cMob.addEventListener('click', () => cDesk.click());
    });
    </script>
</body>
</html>