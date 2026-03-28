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
    <link rel="stylesheet" href="terminal.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.css" />
    <!-- Instant theme: prevent flash of wrong theme -->
    <script>try{if(localStorage.getItem('theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}</script>
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

            <!-- ── Search pill (Animated typewriter) ── -->
            <div class="t-nav-left" id="t-nav-pill" role="button" tabindex="0" aria-label="Search properties">
                <span class="t-nav-pill-tw">
                    <span id="t-nav-tw-text"></span><span class="t-tw-cursor">|</span>
                </span>
                <i class="fa-solid fa-magnifying-glass t-nav-pill-icon"></i>
            </div>

            <!-- ── Right: Desktop links + Mobile action buttons ── -->
            <div class="nav-links">
                <!-- Desktop nav items (hidden on mobile via CSS) -->
                <button id="theme-toggle" class="theme-toggle" title="Toggle Dark/Light Mode"><i class="fa-solid fa-moon"></i></button>
                <a href="index.php" class="active">Explore</a>
                <?php if (isset($_SESSION['user_id'])): ?>
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

        <!-- Active filters bar — mobile only, shows selected filters as removable pills -->
        <div class="t-active-bar" id="t-active-bar" aria-label="Active filters">
            <div class="t-active-pills" id="t-active-pills"></div>
            <button class="t-active-clear" id="t-active-clear" aria-label="Clear all filters" title="Clear all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

    </header>

    <!-- ═══ DESKTOP VISIBLE FILTER BAR (Moved down from header) ═══ -->
    <div class="t-desktop-filter-bar">
        <div class="t-dfb-inner">
            <div class="t-mode-pill">
                <button class="mode-btn mode-active" data-mode="Rent">Rent</button>
                <button class="mode-btn" data-mode="Buy">Buy</button>
            </div>
            <div class="t-dfb-divider"></div>
            <div class="t-dfb-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="dfb-search-text" placeholder="Search areas..." class="search-input">
            </div>
            <select id="dfb-filter-type" class="filter-select">
                <option value="All">All Types</option>
                <option value="Apartment">Apartment</option>
                <option value="House">House</option>
                <option value="Villa">Villa</option>
                <option value="Commercial">Commercial</option>
                <option value="Land">Land</option>
            </select>
            <select id="dfb-filter-location" class="filter-select">
                <option value="All">All Areas</option>
                <option value="Colombo">Colombo</option>
                <option value="Kotte">Kotte</option>
                <option value="Dehiwala">Dehiwala</option>
                <option value="Kandy">Kandy</option>
                <option value="Galle">Galle</option>
            </select>
            <select id="dfb-filter-beds" class="filter-select">
                <option value="All">Any Beds</option>
                <option value="1">1 Bed</option>
                <option value="2">2 Beds</option>
                <option value="3+">3+ Beds</option>
                <option value="studio">Studio</option>
            </select>
            <div class="t-dfb-divider"></div>
            <button id="dfb-apply" class="btn-primary">Search</button>
            <button id="dfb-clear" class="btn-secondary">Clear</button>
        </div>
    </div>

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
            <div class="listings-scroll-container" id="listings-scroll-container">
                <div class="listings-grid" id="listings-grid">
                    <p>Loading properties...</p>
                </div>
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
                    <span>MyHomeMyLand</span>
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
                    <a href="#">About Usxx</a>
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
            <p>&copy; <?php echo date('Y'); ?> MyHomeMyLand &mdash; All rights reserved.</p>
        </div>
    </footer>

    <!-- Mobile View Toggle Pill -->
    <div class="mobile-view-toggle" id="mobile-view-toggle">
        <button class="mvt-btn" id="mvt-list" data-mode="list"><i class="fa-solid fa-list"></i><span>List</span></button>
        <button class="mvt-btn" id="mvt-split" data-mode="split"><i class="fa-solid fa-table-columns"></i><span>Split</span></button>
        <button class="mvt-btn mvt-active" id="mvt-map" data-mode="map"><i class="fa-solid fa-map-location-dot"></i><span>Map</span></button>
    </div>

    <!-- Hidden inputs for JS price sync -->
    <input type="hidden" id="filter-min-price">
    <input type="hidden" id="filter-max-price">

    <!-- ═══ ANIMATED SEARCH OVERLAY ═══ -->
    <div class="t-search-overlay" id="t-search-overlay" aria-hidden="true">
        <div class="t-so-backdrop" id="t-so-backdrop"></div>
        <div class="t-so-panel">

            <!-- Head: mode pill + close -->
            <div class="t-so-head">
                <div class="mode-toggle t-mode-pill">
                    <button class="mode-btn mode-active" data-mode="Rent">Rent</button>
                    <button class="mode-btn" data-mode="Buy">Buy</button>
                </div>
                <button class="t-so-close" id="t-search-close" aria-label="Close search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Big search input with typewriter placeholder -->
            <div class="t-so-input-wrap">
                <i class="fa-solid fa-magnifying-glass t-so-search-icon"></i>
                <input type="text" id="search-text" class="t-so-input" autocomplete="off" spellcheck="false" aria-label="Search properties">
                <div class="t-so-typewriter" id="t-so-typewriter" aria-hidden="true">
                    <span id="t-tw-text"></span><span class="t-tw-cursor">|</span>
                </div>
            </div>

            <!-- Hidden price selects for JS -->
            <select id="filter-min-price-select" style="display:none;"><option value="">Min</option><option value="10000">Rs.10k</option><option value="25000">Rs.25k</option><option value="50000">Rs.50k</option><option value="100000">Rs.100k</option><option value="250000">Rs.250k</option><option value="500000">Rs.500k</option></select>
            <select id="filter-max-price-select" style="display:none;"><option value="">Max</option><option value="50000">Rs.50k</option><option value="100000">Rs.100k</option><option value="250000">Rs.250k</option><option value="500000">Rs.500k</option><option value="750000">Rs.750k</option><option value="1000000">Rs.1M+</option></select>

            <!-- Quick suggestion chips -->
            <div class="t-so-chips">
                <span class="t-so-chips-label">Quick Search:</span>
                <button class="t-chip" data-q="Colombo"><i class="fa-solid fa-location-dot"></i> Colombo</button>
                <button class="t-chip" data-q="Apartment"><i class="fa-solid fa-building"></i> Apartment</button>
                <button class="t-chip" data-q="House"><i class="fa-solid fa-house"></i> House</button>
                <button class="t-chip" data-q="Villa"><i class="fa-solid fa-house-chimney-window"></i> Villa</button>
                <button class="t-chip" data-q="Galle"><i class="fa-solid fa-location-dot"></i> Galle</button>
                <button class="t-chip" data-q="Kandy"><i class="fa-solid fa-location-dot"></i> Kandy</button>
            </div>

            <!-- Filters grid -->
            <div class="t-so-filters">
                <div class="t-so-filters-grid">
                    <div class="t-so-filter-group">
                        <div class="t-pill-select-wrapper">
                            <i class="fa-solid fa-building"></i>
                            <select id="filter-type-mobile" class="filter-select t-pill-select">
                                <option value="All">Type: All</option>
                                <option value="Apartment">Apartment</option>
                                <option value="House">House</option>
                                <option value="Villa">Villa</option>
                                <option value="Commercial">Commercial</option>
                                <option value="Land">Land</option>
                            </select>
                        </div>
                    </div>
                    <div class="t-so-filter-group">
                        <div class="t-pill-select-wrapper">
                            <i class="fa-solid fa-location-dot"></i>
                            <select id="filter-location-mobile" class="filter-select t-pill-select">
                                <option value="All">Location: All</option>
                                <option value="Colombo">Colombo</option>
                                <option value="Kotte">Kotte</option>
                                <option value="Dehiwala">Dehiwala</option>
                                <option value="Kandy">Kandy</option>
                                <option value="Galle">Galle</option>
                            </select>
                        </div>
                    </div>
                    <div class="t-so-filter-group">
                        <div class="t-pill-select-wrapper">
                            <i class="fa-solid fa-bed"></i>
                            <select id="filter-beds-mobile" class="filter-select t-pill-select">
                                <option value="All">Beds: Any</option>
                                <option value="1">1 Bed</option>
                                <option value="2">2 Beds</option>
                                <option value="3+">3+ Beds</option>
                                <option value="studio">Studio</option>
                            </select>
                        </div>
                    </div>
                    <div class="t-so-filter-group">
                        <div class="t-pill-select-wrapper">
                            <i class="fa-solid fa-bath"></i>
                            <select id="filter-baths-mobile" class="filter-select t-pill-select">
                                <option value="All">Baths: Any</option>
                                <option value="1">1 Bath</option>
                                <option value="2">2 Baths</option>
                                <option value="3+">3+ Baths</option>
                            </select>
                        </div>
                    </div>
                    <div class="t-so-filter-group">
                        <div class="t-pill-select-wrapper">
                            <i class="fa-solid fa-sort"></i>
                            <select id="filter-sort-mobile" class="filter-select t-pill-select">
                                <option value="newest">Sort: Newest</option>
                                <option value="oldest">Oldest</option>
                                <option value="price_low">Price ↑</option>
                                <option value="price_high">Price ↓</option>
                                <option value="trending">Trending</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Price Range -->
                <div class="t-so-filter-group t-so-price-group">
                    <label>Price Range</label>
                    <div class="price-slider-container">
                        <div id="price-slider"></div>
                        <div id="price-range-display" class="price-display">Rs. 0 - Rs. 500k</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="t-so-actions">
                <button id="clear-filters" class="t-so-clear-btn">
                    <i class="fa-solid fa-rotate-left"></i> Clear
                </button>
                <button id="apply-filters" class="t-so-apply-btn">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
            </div>

        </div>
    </div><!-- /.t-search-overlay -->

    <!-- ── Fixed floating filter button ── -->
    <button class="t-fab-filters" id="t-fab-filters" aria-label="Open filters" title="Search & Filters">
        <i class="fa-solid fa-sliders"></i>
    </button>


    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.js"></script>
    <script src="script.js?v=<?php echo time(); ?>"></script>
    <script src="terminal.js?v=<?php echo time(); ?>"></script>
    <script>
    /* ── Filter sync layer ── */
    document.addEventListener('DOMContentLoaded', () => {
        const minSel = document.getElementById('filter-min-price-select');
        const maxSel = document.getElementById('filter-max-price-select');
        const minHid = document.getElementById('filter-min-price');
        const maxHid = document.getElementById('filter-max-price');

        if (minSel) minSel.addEventListener('change', () => { if(minHid) minHid.value = minSel.value; });
        if (maxSel) maxSel.addEventListener('change', () => { if(maxHid) maxHid.value = maxSel.value; });

        // Sync "More Filters" modal selects → overlay selects
        [['filter-type-mobile','filter-type'],['filter-location-mobile','filter-location'],
         ['filter-beds-mobile','filter-beds'],['filter-baths-mobile','filter-baths'],
   