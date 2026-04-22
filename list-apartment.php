<?php
require_once 'auth_check.php';

// Authentication Check: Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=list-apartment.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and validate inputs
        $title = trim($_POST['title'] ?? '');
        $type = $_POST['type'] ?? 'Apartment';
        $listing_mode = $_POST['listing_mode'] ?? 'Rent';
        $price = (float)($_POST['price'] ?? 0);
        $beds = $_POST['beds'] ?? '';
        $baths = (int)($_POST['baths'] ?? 1);
        $size_sqft = (int)($_POST['size_sqft'] ?? 0);
        $size_perches = (float)($_POST['size_perches'] ?? 0);
        $apartment_complex = trim($_POST['apartment_complex'] ?? '');
        $completion_status = $_POST['completion_status'] ?? 'Ready';
        $furnished_status = $_POST['furnished_status'] ?? 'Unfurnished';
        $seller_email = trim($_POST['seller_email'] ?? '');
        $seller_phone = trim($_POST['seller_phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $lat = (float)($_POST['lat'] ?? 6.9271);
        $lng = (float)($_POST['lng'] ?? 79.8612);
        $features = isset($_POST['features']) ? implode(',', $_POST['features']) : '';

        // Basic Validation
        if (empty($title)) throw new Exception("Title is required.");
        if ($price <= 0) throw new Exception("Please enter a valid price.");
        if (empty($address)) throw new Exception("Full address is required.");
        if (empty($description)) throw new Exception("Description is required.");

        // Image Handling
        $uploaded_images = [];
        if (!empty($_FILES['images']['name'][0])) {
            $total_images = count($_FILES['images']['name']);
            if ($total_images > 5) throw new Exception("You can only upload a maximum of 5 images.");

            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            for ($i = 0; $i < $total_images; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['images']['name'][$i];
                    $file_tmp = $_FILES['images']['tmp_name'][$i];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                    if (in_array($file_ext, $allowed)) {
                        $new_name = uniqid() . "_" . $i . "." . $file_ext;
                        $target_file = $target_dir . $new_name;
                        if (move_uploaded_file($file_tmp, $target_file)) {
                            $uploaded_images[] = $target_file;
                        }
                    }
                }
            }
        }
        
        // If no images uploaded, use a default placeholder (as seen in existing code)
        if (empty($uploaded_images)) {
            $uploaded_images = ['https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=800'];
        }
        
        $images_json = json_encode($uploaded_images);

        // Database Insertion
        $stmt = $pdo->prepare("INSERT INTO apartments 
            (user_id, title, type, listing_mode, price, bedrooms, baths, size_sqft, size_perches, apartment_complex, 
             completion_status, furnished_status, address, description, features, lat, lng, images, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        $stmt->execute([
            $user_id, $title, $type, $listing_mode, $price, $beds, $baths, $size_sqft, $size_perches, $apartment_complex,
            $completion_status, $furnished_status, $address, $description, $features, $lat, $lng, $images_json
        ]);

        $success_msg = "Listing submitted successfully! It will appear on the site once approved by an admin.";
        header("Location: dashboard.php?msg=" . urlencode($success_msg));
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Your Property - MyHomeMyLand.LK</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="terminal.css">
    <script>try{if(localStorage.getItem('theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}</script>
    <style>
        /* ── Type Selector ── */
        .type-selector { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .type-selector .type-option {
            flex: 1; min-width: 90px; text-align: center; border: 2px solid var(--border-glass);
            border-radius: var(--radius-md); padding: 0.8rem 0.5rem; cursor: pointer;
            transition: all 0.2s ease; background: var(--bg-main);
            display: flex; flex-direction: column; align-items: center; gap: 0.3rem;
        }
        .type-selector .type-option:hover { border-color: var(--primary); }
        .type-selector input[type="radio"] { display: none; }
        .type-selector .type-option-inner { font-size: 0.85rem; color: var(--text-secondary); line-height: 1.3; pointer-events: none; }
        .type-selector .type-option-inner .type-icon { font-size: 1.4rem; display: block; margin-bottom: 0.2rem; }
        .type-selector input[type="radio"]:checked + .type-option-inner { color: var(--primary); font-weight: 700; }
        .type-selector label:has(input[type="radio"]:checked) .type-option { border-color: var(--primary); background: rgba(79, 70, 229, 0.06); box-shadow: 0 0 0 1px var(--primary); }

        /* ── Mode Selector ── */
        .mode-selector { display: flex; gap: 0.8rem; }
        .mode-selector .mode-option {
            flex: 1; text-align: center; padding: 0.8rem; border: 2px solid var(--border-glass);
            border-radius: var(--radius-md); cursor: pointer; font-weight: 600; font-size: 0.95rem;
            background: var(--bg-main); color: var(--text-primary); transition: all 0.2s ease; display: block;
        }
        .mode-selector .mode-option:hover { border-color: var(--primary); }
        .mode-selector input[type="radio"] { display: none; }
        .mode-selector label:has(input[type="radio"]:checked) .mode-option { background: var(--primary); color: white; border-color: var(--primary); }

        /* ── Features Grid ── */
        .features-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.6rem; margin-top: 0.5rem; }
        .feature-item {
            display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.75rem;
            border: 1px solid var(--border-glass); border-radius: var(--radius-sm); cursor: pointer;
            transition: all 0.2s ease; background: var(--bg-main); user-select: none;
        }
        .feature-item:hover { border-color: var(--primary); }
        .feature-item input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer; flex-shrink: 0; margin: 0; }
        .feature-item .feature-label { font-size: 0.85rem; color: var(--text-primary); font-weight: 500; pointer-events: none; }
        .feature-item:has(input:checked) { border-color: var(--primary); background: rgba(79, 70, 229, 0.06); }

        /* ── Image Upload ── */
        .image-preview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 10px; }
        .image-preview-grid img { width: 100%; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-glass); }

        /* ── Map ── */
        #selection-map { height: 350px; border-radius: 12px; border: 1px solid var(--border-glass); width: 100%; }
        .map-type-toggle { display: flex; background: var(--bg-card); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 1px solid var(--border-glass); }
        .map-type-toggle button { padding: 6px 14px; border: none; background: transparent; font-family: 'Outfit', sans-serif; font-size: 0.8rem; font-weight: 500; color: var(--text-secondary); cursor: pointer; transition: all 0.2s; }
        .map-type-toggle button.active { background: var(--primary); color: #fff; font-weight: 600; }

        .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 0.7rem; }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--primary), #7c3aed); color: white; border: none;
            padding: 0.8rem 1.5rem; border-radius: 10px; font-weight: 700; cursor: pointer;
            transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4); opacity: 0.95; }

        input, select, textarea { 
            width: 100%; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border-glass);
            background: var(--bg-main); color: var(--text-primary); font-family: inherit; font-size: 0.92rem;
            transition: border-color 0.2s; box-sizing: border-box;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        label { display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-primary); }
        .input-group { margin-bottom: 1.5rem; }
        .input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }

        @media (max-width: 768px) {
            .input-row { grid-template-columns: 1fr; gap: 0; }
            #selection-map { height: 280px; }
            .features-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body class="form-page">
    <header class="site-header">
        <nav class="navbar">
            <a href="index.php" class="logo" style="text-decoration: none;">
                <i class="fa-solid fa-house-chimney-window"></i> MyHomeMyLand.LK
            </a>
            <div class="nav-links">
                <button id="theme-toggle" class="theme-toggle" title="Toggle Dark/Light Mode">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <a href="index.php">Explore</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main class="page-container form-page" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
        <div class="form-wrapper" style="background: var(--bg-card); padding: 2rem; border-radius: 20px; border: 1px solid var(--border-glass); box-shadow: var(--shadow-card);">
            <h2 style="margin-bottom: 0.3rem; font-size: 1.8rem; font-weight: 800; letter-spacing: -0.02em;">List Your Property</h2>
            <p style="color: var(--text-secondary); margin-bottom: 2rem; font-weight: 500;">Fill in all the details below accurately to reach thousands of potential buyers/renters.</p>

            <?php if($error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
            <?php endif; ?>

            <form method="POST" class="listing-form" enctype="multipart/form-data">
                <!-- Listing Mode -->
                <div class="input-group">
                    <label>Listing Mode *</label>
                    <div class="mode-selector">
                        <label>
                            <input type="radio" name="listing_mode" value="Rent" checked>
                            <div class="mode-option">Rent</div>
                        </label>
                        <label>
                            <input type="radio" name="listing_mode" value="Buy">
                            <div class="mode-option">Buy</div>
                        </label>
                    </div>
                </div>

                <!-- Property Type -->
                <div class="input-group">
                    <label>Property Type *</label>
                    <div class="type-selector">
                        <label>
                            <input type="radio" name="type" value="Apartment" checked>
                            <div class="type-option">
                                <div class="type-option-inner"><span class="type-icon">🏢</span>Apartment</div>
                            </div>
                        </label>
                        <label>
                            <input type="radio" name="type" value="House">
                            <div class="type-option">
                                <div class="type-option-inner"><span class="type-icon">🏠</span>House</div>
                            </div>
                        </label>
                        <label>
                            <input type="radio" name="type" value="Villa">
                            <div class="type-option">
                                <div class="type-option-inner"><span class="type-icon">🏡</span>Villa</div>
                            </div>
                        </label>
                        <label>
                            <input type="radio" name="type" value="Commercial">
                            <div class="type-option">
                                <div class="type-option-inner"><span class="type-icon">🏪</span>Commercial</div>
                            </div>
                        </label>
                        <label>
                            <input type="radio" name="type" value="Land">
                            <div class="type-option">
                                <div class="type-option-inner"><span class="type-icon">🌿</span>Land</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Title & Price -->
                <div class="input-row">
                    <div class="input-group">
                        <label>Listing Title *</label>
                        <input type="text" name="title" placeholder="e.g. Premium 2BR Fully Furnished Apartment" required>
                    </div>
                    <div class="input-group">
                        <label>Price (Rs.) *</label>
                        <input type="number" name="price" placeholder="75000" required>
                    </div>
                </div>

                <!-- Beds & Baths -->
                <div class="input-row" id="row-beds-baths">
                    <div class="input-group">
                        <label>Bedrooms *</label>
                        <select name="beds" id="val-beds">
                            <option value="studio">Studio</option>
                            <option value="1">1 Bedroom</option>
                            <option value="2" selected>2 Bedrooms</option>
                            <option value="3">3 Bedrooms</option>
                            <option value="4+">4+ Bedrooms</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Bathrooms *</label>
                        <select name="baths" id="val-baths">
                            <option value="1">1 Bathroom</option>
                            <option value="2" selected>2 Bathrooms</option>
                            <option value="3">3 Bathrooms</option>
                            <option value="4+">4+ Bathrooms</option>
                        </select>
                    </div>
                </div>

                <!-- Size & Complex -->
                <div class="input-row">
                    <div class="input-group" id="grp-sqft">
                        <label>Size (sqft)</label>
                        <input type="number" name="size_sqft" placeholder="e.g. 842">
                    </div>
                    <div class="input-group" id="grp-perches" style="display: none;">
                        <label>Size (perches) *</label>
                        <input type="number" step="0.1" name="size_perches" placeholder="e.g. 10.5" id="val-perches">
                    </div>
                    <div class="input-group" id="grp-complex">
                        <label>Apartment Complex</label>
                        <input type="text" name="apartment_complex" placeholder="e.g. Luna Tower, Colombo 02">
                    </div>
                </div>

                <!-- Completion & Furnished Status -->
                <div class="input-row" id="row-status">
                    <div class="input-group">
                        <label>Completion Status *</label>
                        <select name="completion_status" required>
                            <option value="Ready">Ready</option>
                            <option value="Under Construction">Under Construction</option>
                            <option value="Off-Plan">Off-Plan</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Furnished Status *</label>
                        <select name="furnished_status" required>
                            <option value="Fully Furnished">Fully Furnished</option>
                            <option value="Semi Furnished">Semi Furnished</option>
                            <option value="Unfurnished">Unfurnished</option>
                        </select>
                    </div>
                </div>

                <!-- Seller Contact -->
                <div class="input-row">
                    <div class="input-group">
                        <label>Seller Email *</label>
                        <input type="email" name="seller_email" placeholder="email@example.com" required>
                    </div>
                    <div class="input-group">
                        <label>Seller Phone *</label>
                        <input type="tel" name="seller_phone" placeholder="e.g. 077 123 4567" required>
                    </div>
                </div>

                <!-- Full Address -->
                <div class="input-group">
                    <label>Full Address *</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="address" id="address-input" placeholder="e.g. Union Place, Colombo 02" required style="flex: 1;">
                        <button type="button" id="geocode-btn" class="btn-primary" style="white-space: nowrap; padding: 0.5rem 1rem; box-shadow: none;">
                            <i class="fa-solid fa-location-crosshairs"></i> Locate
                        </button>
                    </div>
                </div>

                <!-- Description -->
                <div class="input-group">
                    <label>Description *</label>
                    <textarea name="description" rows="5" placeholder="Describe the property in detail... What makes it special?" required></textarea>
                </div>

                <!-- Image Upload -->
                <div class="input-group">
                    <label>Upload Images (Max 5)</label>
                    <div style="background: var(--bg-main); border: 2px dashed var(--border-glass); border-radius: 12px; padding: 1.5rem; text-align: center;">
                        <input type="file" name="images[]" id="image-upload" multiple accept="image/*" style="opacity: 0; position: absolute; width: 0; height: 0;">
                        <label for="image-upload" style="cursor: pointer; margin: 0;">
                            <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2rem; color: var(--primary); margin-bottom: 0.8rem; display: block;"></i>
                            <span style="font-size: 0.9rem; font-weight: 600;">Click to upload photos</span>
                            <span style="display: block; font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.3rem;">JPG, PNG or WebP allowed</span>
                        </label>
                    </div>
                    <div class="image-preview-grid" id="image-preview"></div>
                </div>

                <!-- Features & Amenities -->
                <div class="input-group" id="row-features">
                    <label>Features & Amenities</label>
                    <div class="features-grid">
                        <label class="feature-item"><input type="checkbox" name="features[]" value="A/C"><span class="feature-label">A/C</span></label>
                        <label class="feature-item"><input type="checkbox" name="features[]" value="Pool"><span class="feature-label">Pool</span></label>
                        <label class="feature-item"><input type="checkbox" name="features[]" value="Gym"><span class="feature-label">Gym</span></label>
                        <label class="feature-item"><input type="checkbox" name="features[]" value="Parking"><span class="feature-label">Parking</span></label>
                        <label class="feature-item"><input type="checkbox" name="features[]" value="Furnished"><span class="feature-label">Furnished</span></label>
                        <label class="feature-item"><input type="checkbox" name="features[]" value="Balcony"><span class="feature-label">Balcony</span></label>
                        <label class="feature-item"><input type="checkbox" name="features[]" value="Security"><span class="feature-label">24/7 Security</span></label>
                        <label class="feature-item"><input type="checkbox" name="features[]" value="Generator"><span class="feature-label">Generator</span></label>
                    </div>
                </div>

                <!-- Google Map -->
                <div class="input-group">
                    <label>Pin Exact Location on Map *</label>
                    <div id="selection-map"></div>
                    <input type="hidden" name="lat" id="lat" value="6.9271">
                    <input type="hidden" name="lng" id="lng" value="79.8612">
                    <small style="color: var(--text-secondary); margin-top: 0.6rem; display: block;">Click on the map or drag the marker to the exact property spot.</small>
                </div>

                <button type="submit" class="btn-primary" style="width:100%; padding: 1rem; font-size: 1.1rem; margin-top: 1rem;">
                    Submit Property for Approval
                </button>
            </form>
        </div>
    </main>

    <footer class="site-footer" style="margin-top: 4rem;">
        <div class="footer-container">
            <p>&copy; <?= date('Y') ?> MyHomeMyLand.LK. All rights reserved.</p>
        </div>
    </footer>

    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBwXIu1h80V9Xapeyre_obRvjElwsi7dFg&libraries=places&callback=initMap" async defer></script>

    <script>
        let map, marker, autocomplete;

        function initMap() {
            const defaultPos = { lat: 6.9271, lng: 79.8612 };
            map = new google.maps.Map(document.getElementById('selection-map'), {
                center: defaultPos,
                zoom: 13,
                mapTypeControl: false,
                streetViewControl: false,
                styles: [{ featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] }]
            });

            // Satellite/Map Toggle
            const toggleDiv = document.createElement('div');
            toggleDiv.className = 'map-type-toggle';
            toggleDiv.style.margin = '10px';
            const btnMap = document.createElement('button'); btnMap.textContent = 'Map'; btnMap.className = 'active'; btnMap.type = 'button';
            const btnSat = document.createElement('button'); btnSat.textContent = 'Satellite'; btnSat.type = 'button';
            toggleDiv.appendChild(btnMap); toggleDiv.appendChild(btnSat);
            btnMap.onclick = () => { map.setMapTypeId('roadmap'); btnMap.classList.add('active'); btnSat.classList.remove('active'); };
            btnSat.onclick = () => { map.setMapTypeId('hybrid'); btnSat.classList.add('active'); btnMap.classList.remove('active'); };
            map.controls[google.maps.ControlPosition.TOP_RIGHT].push(toggleDiv);

            marker = new google.maps.Marker({ position: defaultPos, map: map, draggable: true, animation: google.maps.Animation.DROP });
            marker.addListener('dragend', () => {
                const pos = marker.getPosition();
                document.getElementById('lat').value = pos.lat().toFixed(8);
                document.getElementById('lng').value = pos.lng().toFixed(8);
            });
            map.addListener('click', (e) => {
                marker.setPosition(e.latLng);
                document.getElementById('lat').value = e.latLng.lat().toFixed(8);
                document.getElementById('lng').value = e.latLng.lng().toFixed(8);
            });

            const addressInput = document.getElementById('address-input');
            autocomplete = new google.maps.places.Autocomplete(addressInput, {
                componentRestrictions: { country: 'lk' },
                fields: ['geometry', 'formatted_address']
            });
            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry) return;
                map.setCenter(place.geometry.location);
                map.setZoom(16);
                marker.setPosition(place.geometry.location);
                document.getElementById('lat').value = place.geometry.location.lat().toFixed(8);
                document.getElementById('lng').value = place.geometry.location.lng().toFixed(8);
            });

            document.getElementById('geocode-btn').onclick = () => {
                const query = addressInput.value.trim();
                if(!query) return;
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address: query + ', Sri Lanka' }, (res, status) => {
                    if (status === 'OK') {
                        map.setCenter(res[0].geometry.location);
                        map.setZoom(16);
                        marker.setPosition(res[0].geometry.location);
                        document.getElementById('lat').value = res[0].geometry.location.lat().toFixed(8);
                        document.getElementById('lng').value = res[0].geometry.location.lng().toFixed(8);
                    }
                });
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Theme Toggle
            const themeBtn = document.getElementById('theme-toggle');
            if (themeBtn) {
                themeBtn.onclick = () => {
                    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                    if (isDark) { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); }
                    else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); }
                };
            }

            // Type Switching Logic
            const typeRadios = document.querySelectorAll('input[name="type"]');
            function applyTypeRules(type) {
                const isLand = type === 'Land';
                document.getElementById('row-beds-baths').style.display = isLand ? 'none' : 'grid';
                document.getElementById('grp-sqft').style.display = isLand ? 'none' : 'block';
                document.getElementById('grp-complex').style.display = isLand ? 'none' : 'block';
                document.getElementById('row-status').style.display = isLand ? 'none' : 'grid';
                document.getElementById('row-features').style.display = isLand ? 'none' : 'block';
                document.getElementById('grp-perches').style.display = isLand ? 'block' : 'none';
            }
            typeRadios.forEach(r => r.onchange = (e) => applyTypeRules(e.target.value));

            // Image Preview
            const imgInput = document.getElementById('image-upload');
            const imgPreview = document.getElementById('image-preview');
            imgInput.onchange = () => {
                imgPreview.innerHTML = '';
                if(imgInput.files.length > 5) { alert('Max 5 images allowed'); imgInput.value = ''; return; }
                Array.from(imgInput.files).forEach(f => {
                    const reader = new FileReader();
                    reader.onload = e => {
                        const img = document.createElement('img'); img.src = e.target.result;
                        imgPreview.appendChild(img);
                    };
                    reader.readAsDataURL(f);
                });
            };
        });
    </script>
</body>
</html>