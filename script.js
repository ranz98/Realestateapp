document.addEventListener('DOMContentLoaded', () => {
    let currentMode = 'Rent';
    
    // --- Theme Toggle ---
    const themeBtn = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            let theme = document.documentElement.getAttribute('data-theme');
            if (theme === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    }

    // --- Helper: read desktop or mobile filter value ---
    function getVal(desktopId, mobileId, dfbId) {
        const desk = document.getElementById(desktopId);
        const mob = mobileId ? document.getElementById(mobileId) : null;
        const dfb = dfbId ? document.getElementById(dfbId) : null;
        
        if (dfb && dfb.offsetParent !== null) return dfb.value || '';
        if (desk && desk.offsetParent !== null) return desk.value || '';
        if (mob && mob.value) return mob.value || '';
        if (dfb) return dfb.value || '';
        if (desk) return desk.value || '';
        return '';
    }

    // --- Map Initialization ---
    let map, markersLayer, flyInCompleted = false, flyInRunning = false;
    const mapElement = document.getElementById('map');

    if (mapElement && typeof L !== 'undefined') {
        map = L.map('map', { center: [20, 0], zoom: 2, minZoom: 2, attributionControl: false, zoomControl: true, scrollWheelZoom: true });
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 18, minZoom: 2 }).addTo(map);
        markersLayer = L.layerGroup().addTo(map);

        // Brand overlay
        const overlayStyles = document.createElement('style');
        overlayStyles.textContent = `
            .map-brand-overlay { position:absolute; inset:0; z-index:9999; display:flex; align-items:center; justify-content:center; pointer-events:none; }
            .map-brand-overlay .brand-text { font-family:'Outfit',sans-serif; font-size:clamp(1.6rem,3.5vw,2.8rem); font-weight:800; letter-spacing:4px; color:var(--primary,#4f46e5); opacity:0; animation:brandIn 1.2s ease 0.1s both; }
            .map-brand-overlay.brand-exit .brand-text { animation:brandOut 0.8s ease forwards; }
            .map-brand-overlay.brand-exit { animation:overlayOut 0.8s ease 0.5s forwards; }
            @keyframes brandIn { from{opacity:0} to{opacity:1} }
            @keyframes brandOut { from{opacity:1} to{opacity:0} }
            @keyframes overlayOut { from{opacity:1} to{opacity:0} }
        `;
        document.head.appendChild(overlayStyles);
        const brandOverlay = document.createElement('div');
        brandOverlay.className = 'map-brand-overlay';
        brandOverlay.innerHTML = '<div class="brand-text">MyHomeMyLand</div>';
        mapElement.parentElement.style.position = 'relative';
        mapElement.parentElement.appendChild(brandOverlay);

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const isWeakDevice = (navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 2) || (navigator.deviceMemory && navigator.deviceMemory <= 2);
        const skipAnimation = prefersReducedMotion || isWeakDevice;

        function runFlyIn() {
            if (flyInCompleted || flyInRunning) return;
            const rect = mapElement.getBoundingClientRect();
            if (rect.width === 0 || rect.height === 0) return;
            flyInRunning = true;
            map.invalidateSize();
            if (skipAnimation) {
                brandOverlay.remove();
                map.setView([6.90, 79.96], 12, { animate: false });
                map.setMaxBounds(L.latLngBounds(L.latLng(5.8, 79.5), L.latLng(9.9, 82.0)));
                map.options.minZoom = 7;
                flyInCompleted = true; flyInRunning = false;
                return;
            }
            map.flyTo([6.90, 79.96], 10, { duration: 1.1, easeLinearity: 0.25 });
            map.once('moveend', () => {
                setTimeout(() => {
                    brandOverlay.classList.add('brand-exit');
                    setTimeout(() => { brandOverlay.remove(); }, 1300);

                    map.setMaxBounds(L.latLngBounds(L.latLng(5.8, 79.5), L.latLng(9.9, 82.0)));
                    map.options.minZoom = 7;
                    flyInCompleted = true; flyInRunning = false;

                    // AUTO-SPLIT AFTER FLY-IN (only on mobile)
                    if (isMobile()) {
                        setTimeout(() => applyMobileMode('split'), 500);
                    }
                }, 800);
            });
        }

        function attemptFlyIn() {
            if (flyInCompleted) return;
            const rect = mapElement.getBoundingClientRect();
            if (rect.width > 0 && rect.height > 0) runFlyIn();
        }
        setTimeout(attemptFlyIn, 400);

        const mapSect = document.getElementById('map-section');
        if (mapSect && typeof MutationObserver !== 'undefined') {
            const obs = new MutationObserver(() => { if (!flyInCompleted) setTimeout(() => { map.invalidateSize(); attemptFlyIn(); }, 250); });
            obs.observe(mapSect, { attributes: true, attributeFilter: ['class', 'style'] });
            const mc = document.querySelector('.main-container');
            if (mc) obs.observe(mc, { attributes: true, attributeFilter: ['class'] });
        }
        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(entries => {
                for (const e of entries) if (e.contentRect.width > 0 && e.contentRect.height > 0 && !flyInCompleted && !flyInRunning) { map.invalidateSize(); setTimeout(attemptFlyIn, 300); }
            }).observe(mapElement);
        }
    }

    // --- Mobile View Toggle Components ---
    const mainContainer = document.querySelector('.main-container');
    const mapSection = document.getElementById('map-section');
    const listingsSection = document.getElementById('listings-section');
    const mvtButtons = document.querySelectorAll('.mvt-btn');

    const isMobile = () => window.innerWidth <= 992;

    function updateHeaderHeight() {
        const header = document.querySelector('.site-header');
        if (header && mainContainer) mainContainer.style.setProperty('--header-h', header.offsetHeight + 'px');
    }
    updateHeaderHeight();
    window.addEventListener('resize', updateHeaderHeight);

    function applyMobileMode(mode) {
        mainContainer?.classList.remove('split-mode');
        mapSection?.classList.remove('mobile-map-active');
        listingsSection?.classList.remove('mobile-hidden');
        mvtButtons.forEach(b => b.classList.remove('mvt-active'));

        if (mode === 'list') {
            document.getElementById('mvt-list')?.classList.add('mvt-active');
            document.body.classList.remove('mobile-map-only');
        } else if (mode === 'split') {
            document.getElementById('mvt-split')?.classList.add('mvt-active');
            mainContainer?.classList.add('split-mode');
            document.body.classList.remove('mobile-map-only');
            if (map) setTimeout(() => map.invalidateSize(), 150);
        } else if (mode === 'map') {
            document.getElementById('mvt-map')?.classList.add('mvt-active');
            mapSection?.classList.add('mobile-map-active');
            listingsSection?.classList.add('mobile-hidden');
            document.body.classList.add('mobile-map-only');
            if (map) setTimeout(() => map.invalidateSize(), 150);
        }
        localStorage.setItem('mobileViewMode', mode);
    }

    mvtButtons.forEach(btn => btn.addEventListener('click', () => applyMobileMode(btn.dataset.mode)));

    if (isMobile()) {
        applyMobileMode(localStorage.getItem('mobileViewMode') || 'map');
        const initialOverlay = document.getElementById('initial-mode-overlay');
        if (initialOverlay) {
            initialOverlay.style.display = 'flex';
            document.querySelectorAll('.overlay-mode-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    currentMode = e.target.dataset.mode;
                    document.querySelectorAll('.mode-btn').forEach(b => b.classList.toggle('mode-active', b.dataset.mode === currentMode));
                    initialOverlay.style.display = 'none';
                    fetchListings();
                });
            });
        }
    } else {
        const ov = document.getElementById('initial-mode-overlay');
        if (ov) ov.style.display = 'none';
    }

    window.addEventListener('resize', () => {
        if (!isMobile()) {
            mainContainer?.classList.remove('split-mode');
            mapSection?.classList.remove('mobile-map-active');
            listingsSection?.classList.remove('mobile-hidden');
            if (map) setTimeout(() => map.invalidateSize(), 200);
        }
    });

    // --- Price Slider (mobile) ---
    const priceSlider = document.getElementById('price-slider');
    const minPriceInput = document.getElementById('filter-min-price');
    const maxPriceInput = document.getElementById('filter-max-price');
    const priceDisplay = document.getElementById('price-range-display');

    const RENT_MAX = 1000000;
    const BUY_MAX = 200000000; // 200M

    function updatePriceUI(mode) {
        if (!priceSlider || !priceSlider.noUiSlider) return;
        const isBuy = mode === 'Buy';
        const newMax = isBuy ? BUY_MAX : RENT_MAX;
        const newStep = isBuy ? 100000 : 5000;

        priceSlider.noUiSlider.updateOptions({
            range: { 'min': 0, 'max': newMax },
            step: newStep
        });
        // Reset to full range on mode change
        priceSlider.noUiSlider.set([0, newMax]);

        // Update Desktop Selects if they exist
        const minSel = document.getElementById('filter-min-price-select');
        const maxSel = document.getElementById('filter-max-price-select');
        if (minSel && maxSel) {
            const buyOptionsMin = [
                {v:'', t:'Min Price'}, {v:'1000000', t:'Rs. 1M'}, {v:'5000000', t:'Rs. 5M'}, {v:'10000000', t:'Rs. 10M'}, 
                {v:'25000000', t:'Rs. 25M'}, {v:'50000000', t:'Rs. 50M'}, {v:'100000000', t:'Rs. 100M'}
            ];
            const rentOptionsMin = [
                {v:'', t:'Min Price'}, {v:'10000', t:'Rs. 10k'}, {v:'25000', t:'Rs. 25k'}, {v:'50000', t:'Rs. 50k'}, 
                {v:'100000', t:'Rs. 100k'}, {v:'250000', t:'Rs. 250k'}, {v:'500000', t:'Rs. 500k'}
            ];
            const buyOptionsMax = [
                {v:'', t:'Max Price'}, {v:'5000000', t:'Rs. 5M'}, {v:'10000000', t:'Rs. 10M'}, {v:'25000000', t:'Rs. 25M'}, 
                {v:'50000000', t:'Rs. 50M'}, {v:'100000000', t:'Rs. 100M'}, {v:BUY_MAX, t:'Rs. 200M+'}
            ];
            const rentOptionsMax = [
                {v:'', t:'Max Price'}, {v:'50000', t:'Rs. 50k'}, {v:'100000', t:'Rs. 100k'}, {v:'250000', t:'Rs. 250k'}, 
                {v:'500000', t:'Rs. 500k'}, {v:'750000', t:'Rs. 750k'}, {v:RENT_MAX, t:'Rs. 1M+'}
            ];

            const optsMin = isBuy ? buyOptionsMin : rentOptionsMin;
            const optsMax = isBuy ? buyOptionsMax : rentOptionsMax;

            minSel.innerHTML = optsMin.map(o => `<option value="${o.v}">${o.t}</option>`).join('');
            maxSel.innerHTML = optsMax.map(o => `<option value="${o.v}">${o.t}</option>`).join('');
        }
    }

    if (priceSlider && typeof noUiSlider !== 'undefined') {
        noUiSlider.create(priceSlider, {
            start: [0, RENT_MAX], connect: true, step: 5000,
            range: { 'min': 0, 'max': RENT_MAX },
            format: { to: v => Math.round(v), from: v => Number(v) }
        });
        priceSlider.noUiSlider.on('update', function (values, handle) {
            const currentMax = currentMode === 'Buy' ? BUY_MAX : RENT_MAX;
            if (handle === 0) { if (minPriceInput) minPriceInput.value = values[0]; }
            else { if (maxPriceInput) maxPriceInput.value = (Number(values[1]) >= currentMax) ? '' : values[1]; }
            
            const minD = Number(values[0]).toLocaleString();
            const maxVal = Number(values[1]);
            const maxD = (maxVal >= currentMax) ? (currentMax >= 1000000 ? (currentMax/1000000) + 'M+' : '1M+') : maxVal.toLocaleString();
            if (priceDisplay) priceDisplay.innerText = 'Rs. ' + minD + ' - Rs. ' + maxD;
        });
        priceSlider.noUiSlider.on('change', () => fetchListings());
    }

    // --- Mobile Filter Modal ---
    const mobileFilterBtn = document.getElementById('mobile-filter-btn');
    const filtersModal = document.getElementById('filters-modal');
    const closeFiltersBtn = document.getElementById('close-filters-btn');

    if (mobileFilterBtn && filtersModal) {
        mobileFilterBtn.addEventListener('click', () => { filtersModal.classList.add('active'); document.body.style.overflow = 'hidden'; });
    }
    if (closeFiltersBtn && filtersModal) {
        closeFiltersBtn.addEventListener('click', () => { filtersModal.classList.remove('active'); document.body.style.overflow = ''; fetchListings(); });
    }

    // --- Buy/Rent Toggle (ALL mode buttons synced) ---
    document.querySelectorAll('.mode-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            currentMode = e.target.dataset.mode;
            document.querySelectorAll('.mode-btn').forEach(b => b.classList.toggle('mode-active', b.dataset.mode === currentMode));
            updatePriceUI(currentMode); // Update slider and selects
            fetchListings();
        });
    });

    // --- Fetch & Render ---
    const grid = document.getElementById('listings-grid');

    const fetchListings = async () => {
        try {
            const search = getVal('search-text', 'search-text-mobile', 'dfb-search-text');
            const type = getVal('filter-type', 'filter-type-mobile', 'dfb-filter-type');
            const location = getVal('filter-location', 'filter-location-mobile', 'dfb-filter-location');
            const beds = getVal('filter-beds', 'filter-beds-mobile', 'dfb-filter-beds');
            const baths = getVal('filter-baths', 'filter-baths-mobile');
            const min_price = minPriceInput?.value || '';
            const max_price = maxPriceInput?.value || '';
            const sort = getVal('filter-sort', 'filter-sort-mobile');
            const listing_mode = currentMode;

            const params = new URLSearchParams({ search, type, location, beds, baths, min_price, max_price, sort, listing_mode });
            const res = await fetch('api/get_apartments.php?' + params.toString());
            const data = await res.json();

            if (grid) {
                grid.innerHTML = '';
                if (data.length === 0) {
                    grid.innerHTML = '<p style="grid-column:span 2;padding:2rem;">No properties match your search.</p>';
                } else {
                    data.forEach(prop => {
                        const card = document.createElement('div');
                        card.className = 'property-card';

                        // FA icon class per type
                        const typeIcons = { Apartment:'fa-building', House:'fa-house', Villa:'fa-house-chimney-window', Commercial:'fa-store', Land:'fa-tree' };
                        const typeIconClass = typeIcons[prop.type] || 'fa-building';

                        // Default fallback images
                        const defaults = { Land:'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&q=80&w=800', House:'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&q=80&w=800', Villa:'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&q=80&w=800' };
                        const defaultImg = defaults[prop.type] || 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=800';

                        let imgs = [defaultImg];
                        try { const p = JSON.parse(prop.images); if (p && p.length > 0) imgs = p; } catch (e) { }

                        // Images & dots
                        const imagesHtml = imgs.map(img => '<img src="' + img + '" alt="' + escapeHTML(prop.title) + '" class="property-img card-img-slide" loading="lazy">').join('');
                        const dotsHtml   = imgs.length > 1 ? imgs.map((_, i) => '<div class="card-dot' + (i===0?' active':'') + '"></div>').join('') : '';
                        const dotsBlock  = imgs.length > 1 ? '<div class="card-slider-dots">' + dotsHtml + '</div>' : '';
                        const arrowsHtml = imgs.length > 1 ? '<button class="card-slider-nav card-slider-prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button><button class="card-slider-nav card-slider-next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>' : '';

                        // Mode badge
                        const modeText  = prop.listing_mode || currentMode;
                        const modeClass = modeText === 'Rent' ? 'pc-mode-rent' : 'pc-mode-buy';
                        const period    = modeText === 'Rent' ? '<small class="pc-price-per">/mo</small>' : '';

                        // Price
                        const priceFormatted = 'Rs.\u00a0' + Number(prop.price).toLocaleString();

                        // Stats row
                        const views = prop.view_count || 0;
                        let statsHtml;
                        if (prop.type === 'Land') {
                            statsHtml = '<div class="pc-stat"><i class="fa-solid fa-ruler-combined"></i><span>' + (prop.size_perches||'—') + '</span><small>Perches</small></div><div class="pc-stat-sep"></div><div class="pc-stat"><i class="fa-solid fa-eye"></i><span>' + views + '</span><small>Views</small></div>';
                        } else {
                            statsHtml = '<div class="pc-stat"><i class="fa-solid fa-bed"></i><span>' + (prop.bedrooms||'—') + '</span><small>Bed' + (prop.bedrooms!=1?'s':'') + '</small></div><div class="pc-stat-sep"></div><div class="pc-stat"><i class="fa-solid fa-bath"></i><span>' + (prop.baths||'—') + '</span><small>Bath' + (prop.baths!=1?'s':'') + '</small></div><div class="pc-stat-sep"></div><div class="pc-stat"><i class="fa-solid fa-eye"></i><span>' + views + '</span><small>Views</small></div>';
                        }

                        card.innerHTML =
                            '<div class="img-container">' +
                                '<div class="card-slider-wrapper">' + imagesHtml + '</div>' +
                                '<div class="pc-img-overlay"></div>' +
                                '<div class="pc-top-row">' +
                                    '<span class="pc-type-badge"><i class="fa-solid ' + typeIconClass + '"></i>&nbsp;' + escapeHTML(prop.type) + '</span>' +
                                    '<span class="pc-mode-badge ' + modeClass + '">' + modeText + '</span>' +
                                '</div>' +
                                '<div class="pc-bottom-row">' +
                                    '<div class="pc-price">' + priceFormatted + period + '</div>' +
                                    dotsBlock +
                                '</div>' +
                                arrowsHtml +
                            '</div>' +
                            '<div class="property-info">' +
                                '<h3 class="property-title">' + escapeHTML(prop.title) + '</h3>' +
                                '<div class="property-location"><i class="fa-solid fa-location-dot"></i><span>' + escapeHTML(prop.address) + '</span></div>' +
                                '<div class="pc-stats-row">' + statsHtml + '</div>' +
                            '</div>';

                        let preventCardClick = false;
                        if (imgs.length > 1) {
                            let currSlide = 0;
                            const wrapper = card.querySelector('.card-slider-wrapper');
                            const dots = card.querySelectorAll('.card-dot');
                            const updateSlider = () => { wrapper.style.transform = 'translateX(-' + (currSlide * 100) + '%)'; dots.forEach(d => d.classList.remove('active')); if (dots[currSlide]) dots[currSlide].classList.add('active'); };
                            card.querySelector('.card-slider-next').addEventListener('click', (e) => { e.stopPropagation(); currSlide = (currSlide + 1) % imgs.length; updateSlider(); });
                            card.querySelector('.card-slider-prev').addEventListener('click', (e) => { e.stopPropagation(); currSlide = (currSlide - 1 + imgs.length) % imgs.length; updateSlider(); });
                            let startX = 0, currentX = 0, isDragging = false;
                            wrapper.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; isDragging = true; wrapper.style.transition = 'none'; }, { passive: true });
                            wrapper.addEventListener('touchmove', (e) => { if (!isDragging) return; currentX = e.touches[0].clientX; wrapper.style.transform = 'translateX(' + (-(currSlide * 100) + ((currentX - startX) / wrapper.offsetWidth * 100)) + '%)'; }, { passive: true });
                            wrapper.addEventListener('touchend', () => { if (!isDragging) return; isDragging = false; wrapper.style.transition = 'transform 0.3s cubic-bezier(0.25,1,0.5,1)'; const diff = currentX - startX; if (Math.abs(diff) > 30) { preventCardClick = true; setTimeout(() => preventCardClick = false, 100); if (diff < 0) currSlide = Math.min(currSlide + 1, imgs.length - 1); else currSlide = Math.max(currSlide - 1, 0); } updateSlider(); });
                        }
                        card.style.cursor = 'pointer';
                        card.addEventListener('click', () => { if (!preventCardClick) window.location.href = 'apartment.php?id=' + prop.id; });
                        grid.appendChild(card);
                    });
                }
            }

            // Map markers
            if (mapElement && markersLayer) {
                markersLayer.clearLayers();
                data.forEach(prop => {
                    const priceIcon = L.divIcon({ className: 'custom-price-marker-wrapper', html: '<div class="price-marker-label">Rs. ' + escapeHTML(Number(prop.price).toLocaleString()) + '</div>', iconSize: [80, 24], iconAnchor: [40, 24] });
                    const marker = L.marker([prop.lat, prop.lng], { icon: priceIcon }).addTo(markersLayer);
                    let popupImage = 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=800';
                    try { const pi = JSON.parse(prop.images); if (pi && pi.length > 0) popupImage = pi[0]; } catch (e) { }
                    marker.bindPopup('<div style="cursor:pointer;" onclick="window.location.href=\'apartment.php?id=' + prop.id + '\'"><img src="' + popupImage + '" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:0.5rem;"><h4 style="margin:0;font-size:1rem;">' + prop.title + '</h4><p style="margin:0;color:var(--primary);font-weight:bold;">Rs. ' + Number(prop.price).toLocaleString() + '</p><span style="font-size:0.8rem;color:var(--text-secondary);">' + prop.type + ' | ' + prop.bedrooms + ' Bed | ' + prop.baths + ' Bath</span><div style="margin-top:0.5rem;"><span style="color:var(--primary);font-size:0.85rem;font-weight:500;">View Details →</span></div></div>', { closeButton: true, minWidth: 220 });
                });
            }
        } catch (e) {
            console.error('Fetch error:', e);
            if (grid) grid.innerHTML = '<p style="color:red;grid-column:span 2;">Failed to load properties.</p>';
        }
    };

    function escapeHTML(str) { return new Option(str).innerHTML; }
    if (grid) fetchListings();

    // Desktop DFB controls
    const dfbApply = document.getElementById('dfb-apply');
    if (dfbApply) dfbApply.addEventListener('click', fetchListings);
    document.querySelectorAll('.t-desktop-filter-bar .filter-select').forEach(s => s.addEventListener('change', fetchListings));
    const dfbSearch = document.getElementById('dfb-search-text');
    if (dfbSearch) dfbSearch.addEventListener('keypress', (e) => { if(e.key === 'Enter') fetchListings(); });

    // Desktop controls
    const applyBtn = document.getElementById('apply-filters');
    if (applyBtn) applyBtn.addEventListener('click', fetchListings);
    document.querySelectorAll('.filter-bar-desktop .filter-select').forEach(s => s.addEventListener('change', fetchListings));
    const minSel = document.getElementById('filter-min-price-select');
    const maxSel = document.getElementById('filter-max-price-select');
    if (minSel) minSel.addEventListener('change', () => { if (minPriceInput) minPriceInput.value = minSel.value; fetchListings(); });
    if (maxSel) maxSel.addEventListener('change', () => { if (maxPriceInput) maxPriceInput.value = maxSel.value; fetchListings(); });

    // Mobile apply
    const applyMob = document.getElementById('apply-filters-mobile');
    if (applyMob) applyMob.addEventListener('click', fetchListings);

    // Clear all
    function clearAll() {
        ['search-text', 'search-text-mobile', 'dfb-search-text'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        ['filter-type', 'filter-type-mobile', 'dfb-filter-type', 'filter-location', 'filter-location-mobile', 'dfb-filter-location', 'filter-beds', 'filter-beds-mobile', 'dfb-filter-beds', 'filter-baths', 'filter-baths-mobile'].forEach(id => { const el = document.getElementById(id); if (el) el.value = 'All'; });
        ['filter-sort', 'filter-sort-mobile'].forEach(id => { const el = document.getElementById(id); if (el) el.value = 'newest'; });
        if (minSel) minSel.value = ''; if (maxSel) maxSel.value = '';
        if (minPriceInput) minPriceInput.value = ''; if (maxPriceInput) maxPriceInput.value = '';
        if (priceSlider && priceSlider.noUiSlider) priceSlider.noUiSlider.set([0, 500000]);
        fetchListings();
    }
    const clearBtn = document.getElementById('clear-filters');
    const clearMob = document.getElementById('clear-filters-mobile');
    const clearDfb = document.getElementById('dfb-clear');
    if (clearBtn) clearBtn.addEventListener('click', clearAll);
    if (clearMob) clearMob.addEventListener('click', clearAll);
    if (clearDfb) clearDfb.addEventListener('click', clearAll);
});