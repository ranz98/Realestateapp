/* Reels — TikTok-style mobile-only video viewer
 * Pulls reels from api/get_reels.php, renders vertical snap feed,
 * autoplays the visible slide, pauses others, tap to mute/unmute,
 * tap once to pause/resume.
 */
function reelsInit() {
    'use strict';

    const overlay = document.getElementById('reels-overlay');
    const feed = document.getElementById('reels-feed');
    const openBtn = document.getElementById('mvt-reels');
    const closeBtn = document.getElementById('reels-close');
    if (!overlay || !feed || !openBtn) {
        console.warn('[reels] missing element', { overlay: !!overlay, feed: !!feed, openBtn: !!openBtn });
        return;
    }

    let loaded = false;
    let reels = [];
    let observer = null;
    let muted = true; // Start muted so autoplay works on mobile

    const fmtPrice = (n) => {
        if (!n || isNaN(n)) return '';
        const v = Number(n);
        if (v >= 1_000_000) return 'Rs. ' + (Math.round(v / 100_000) / 10) + 'M';
        if (v >= 1_000) return 'Rs. ' + Math.round(v / 1000) + 'k';
        return 'Rs. ' + v;
    };

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function firstImage(imagesJson) {
        try {
            const arr = JSON.parse(imagesJson);
            if (Array.isArray(arr) && arr.length) return arr[0];
        } catch (_) {}
        return 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=400';
    }

    function buildListingPanel(r) {
        const listing = r.listing;
        // No linked listing — fall back to the old caption/meta block.
        if (!listing) {
            const price = fmtPrice(r.price);
            const meta = [r.location, r.listing_mode].filter(Boolean).map(escapeHtml).join(' · ');
            return `
            <div class="reel-info">
                <h3>${escapeHtml(r.title || '')}</h3>
                <div class="reel-meta">
                    ${meta ? `<span>${meta}</span>` : ''}
                    ${price ? `<span class="reel-price">${price}</span>` : ''}
                </div>
                ${r.caption ? `<div class="reel-caption">${escapeHtml(r.caption)}</div>` : ''}
            </div>`;
        }

        const img = firstImage(listing.images);
        const price = fmtPrice(listing.price);
        const mode = listing.listing_mode || '';
        const modeClass = mode === 'Rent' ? 'pc-mode-rent' : 'pc-mode-buy';
        const period = mode === 'Rent' ? '<small>/mo</small>' : '';
        const stats = (listing.type === 'Land')
            ? `<span><i class="fa-solid fa-ruler-combined"></i> ${escapeHtml(listing.size_perches || '—')}P</span>`
            : `<span><i class="fa-solid fa-bed"></i> ${escapeHtml(listing.bedrooms || '—')}</span>
               <span><i class="fa-solid fa-bath"></i> ${escapeHtml(listing.baths || '—')}</span>`;

        return `
        <div class="reel-listing-panel" data-apartment-id="${listing.id}">
            <a class="reel-mini-card" href="apartment.php?id=${listing.id}">
                <div class="reel-mini-card-img" style="background-image:url('${escapeHtml(img)}')">
                    ${mode ? `<span class="reel-mini-mode ${modeClass}">${escapeHtml(mode)}</span>` : ''}
                    <span class="reel-mini-price">Rs. ${price ? price.replace('Rs. ', '') : '—'}${period}</span>
                </div>
                <div class="reel-mini-body">
                    <h4>${escapeHtml(listing.title || '')}</h4>
                    <div class="reel-mini-addr"><i class="fa-solid fa-location-dot"></i> ${escapeHtml(listing.address || '')}</div>
                    <div class="reel-mini-stats">${stats}</div>
                </div>
            </a>
            <a class="reel-mini-map" href="apartment.php?id=${listing.id}"
               data-lat="${listing.lat || ''}" data-lng="${listing.lng || ''}">
                <div class="reel-mini-map-canvas"></div>
                <div class="reel-mini-map-cta"><i class="fa-solid fa-map-location-dot"></i> View on map</div>
            </a>
        </div>`;
    }

    function buildSlide(r) {
        return `
        <div class="reel-slide" data-id="${r.id}" data-apartment-id="${r.listing ? r.listing.id : ''}">
            <video
                src="${escapeHtml(r.video_url)}"
                ${r.poster_url ? `poster="${escapeHtml(r.poster_url)}"` : ''}
                playsinline
                webkit-playsinline
                loop
                muted
                preload="metadata"
                x5-playsinline
            ></video>
            <div class="reel-tap"></div>
            <div class="reel-play-icon"><i class="fa-solid fa-play"></i></div>
            <div class="reel-progress"><span></span></div>
            <div class="reel-actions">
                <button class="reel-mute" aria-label="Mute toggle"><i class="fa-solid fa-volume-xmark"></i></button>
                <button class="reel-like" aria-label="Like"><i class="fa-regular fa-heart"></i></button>
                <button class="reel-share" aria-label="Share"><i class="fa-solid fa-share"></i></button>
            </div>
            ${buildListingPanel(r)}
        </div>`;
    }

    function initMiniMaps() {
        if (typeof L === 'undefined') return;
        feed.querySelectorAll('.reel-mini-map').forEach((el) => {
            if (el.dataset.mapReady === '1') return;
            const lat = parseFloat(el.dataset.lat);
            const lng = parseFloat(el.dataset.lng);
            const canvas = el.querySelector('.reel-mini-map-canvas');
            if (!canvas || isNaN(lat) || isNaN(lng)) return;
            try {
                const map = L.map(canvas, {
                    zoomControl: false,
                    attributionControl: false,
                    dragging: false,
                    scrollWheelZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    keyboard: false,
                    tap: false,
                    touchZoom: false,
                }).setView([lat, lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                }).addTo(map);
                L.marker([lat, lng]).addTo(map);
                setTimeout(() => { try { map.invalidateSize(); } catch (_) {} }, 60);
                el.dataset.mapReady = '1';
            } catch (e) {
                console.warn('[reels] mini map failed', e);
            }
        });
    }

    function renderEmpty(msg) {
        feed.innerHTML = `<div class="reels-loading">${escapeHtml(msg)}</div>`;
    }

    async function loadReels() {
        try {
            const res = await fetch('api/get_reels.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to load');
            reels = data.reels || [];
            if (!reels.length) {
                renderEmpty('No reels yet. Admin can add some at admin-reels.php');
                return;
            }
            feed.innerHTML = reels.map(buildSlide).join('');
            attachInteractions();
            setupObserver();
            initMiniMaps();
        } catch (e) {
            renderEmpty('Could not load reels. ' + (e.message || ''));
        }
    }

    function setupObserver() {
        if (observer) observer.disconnect();
        observer = new IntersectionObserver((entries) => {
            entries.forEach((en) => {
                const slide = en.target;
                const video = slide.querySelector('video');
                if (!video) return;
                if (en.isIntersecting && en.intersectionRatio >= 0.7) {
                    video.muted = muted;
                    const p = video.play();
                    if (p && p.catch) p.catch(() => { /* autoplay blocked, will resume on tap */ });
                    slide.classList.remove('is-paused');
                } else {
                    video.pause();
                    video.currentTime = 0;
                }
            });
        }, { root: feed, threshold: [0, 0.7, 1] });

        feed.querySelectorAll('.reel-slide').forEach((s) => observer.observe(s));
    }

    function attachInteractions() {
        feed.querySelectorAll('.reel-slide').forEach((slide) => {
            const video = slide.querySelector('video');
            const tap = slide.querySelector('.reel-tap');
            const muteBtn = slide.querySelector('.reel-mute');
            const likeBtn = slide.querySelector('.reel-like');
            const shareBtn = slide.querySelector('.reel-share');
            const bar = slide.querySelector('.reel-progress > span');

            // single tap = pause/resume
            tap.addEventListener('click', () => {
                if (video.paused) {
                    video.play().catch(() => {});
                    slide.classList.remove('is-paused');
                } else {
                    video.pause();
                    slide.classList.add('is-paused');
                }
            });

            muteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                muted = !muted;
                document.querySelectorAll('.reel-slide video').forEach((v) => { v.muted = muted; });
                document.querySelectorAll('.reel-mute i').forEach((i) => {
                    i.className = muted ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high';
                });
            });

            likeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                likeBtn.classList.toggle('liked');
                const i = likeBtn.querySelector('i');
                if (i) i.className = likeBtn.classList.contains('liked') ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
            });

            shareBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const id = slide.dataset.id;
                const url = location.origin + location.pathname + '?reel=' + id;
                try {
                    if (navigator.share) {
                        await navigator.share({ title: 'Check this reel', url });
                    } else {
                        await navigator.clipboard.writeText(url);
                        shareBtn.querySelector('i').className = 'fa-solid fa-check';
                        setTimeout(() => { shareBtn.querySelector('i').className = 'fa-solid fa-share'; }, 1200);
                    }
                } catch (_) {}
            });

            video.addEventListener('timeupdate', () => {
                if (!video.duration) return;
                bar.style.width = (video.currentTime / video.duration * 100) + '%';
            });
        });
    }

    function open(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
        }
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('reels-open');
        if (!loaded) {
            loaded = true;
            loadReels();
        } else {
            setupObserver();
        }
    }

    function close() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('reels-open');
        // pause everything
        feed.querySelectorAll('video').forEach((v) => { try { v.pause(); } catch (_) {} });
        if (observer) { observer.disconnect(); observer = null; }
    }

    // Capture-phase listener so we run BEFORE the existing .mvt-btn handler
    openBtn.addEventListener('click', open, true);
    closeBtn.addEventListener('click', close);

    // Document-level delegation as a safety net
    document.addEventListener('click', (e) => {
        const t = e.target.closest && e.target.closest('#mvt-reels');
        if (t && !overlay.classList.contains('is-open')) open(e);
    }, true);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
    });

    const params = new URLSearchParams(location.search);
    if (params.get('reel')) {
        window.addEventListener('load', () => {
            open();
            const tryScroll = () => {
                const target = feed.querySelector(`.reel-slide[data-id="${params.get('reel')}"]`);
                if (target) target.scrollIntoView({ behavior: 'instant', block: 'start' });
                else if (loaded) setTimeout(tryScroll, 200);
            };
            setTimeout(tryScroll, 400);
        });
    }

    // Expose for debugging
    window.__reels = { open, close };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', reelsInit);
} else {
    reelsInit();
}
