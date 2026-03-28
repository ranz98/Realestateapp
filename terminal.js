/* ================================================================
   TERMINAL UI — Interactive Engine  v2
   MyHomeMyLand.LK
   ================================================================ */

(function () {
    'use strict';

    /* ── Apply saved theme INSTANTLY (before paint) ───────────── */
    /* Note: also done inline in <head> for zero-flash, this is a safety net */
    (function () {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    })();

    const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';

    /* Page loader removed — instantaneous page display */

    /* ================================================================
       HEADER HEIGHT SYNC  ← sets --t-header-h on <html>
       ================================================================ */
    function syncHeaderHeight() {
        const header = document.querySelector('.site-header');
        if (!header) return;

        const set = () => {
            const h = header.offsetHeight;
            document.documentElement.style.setProperty('--t-header-h', h + 'px');
            /* Also keep the legacy variable working for existing JS */
            const mc = document.querySelector('.main-container');
            if (mc) mc.style.setProperty('--header-h', h + 'px');
        };

        set();
        window.addEventListener('resize', set, { passive: true });
        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(set).observe(header);
        }
    }

    /* ================================================================
       FLOATING HEADER — Scroll effects
       ================================================================ */
    function initHeaderScroll() {
        const header = document.querySelector('.site-header');
        if (!header) return;

        let ticking = false;
        window.addEventListener('scroll', () => {
            if (ticking) return;
            window.requestAnimationFrame(() => {
                const y = window.scrollY;
                header.classList.toggle('t-scrolled', y > 30);
                ticking = false;
            });
            ticking = true;
        }, { passive: true });
    }

    /* ================================================================
       MOBILE HAMBURGER + DRAWER
       ================================================================ */
    function initMobileMenu() {
        const header = document.querySelector('.site-header');
        const navbar = document.querySelector('.navbar');
        const navLinks = document.querySelector('.nav-links');
        if (!navbar || !navLinks) return;

        /* ── 1. Build hamburger ── */
        const ham = document.createElement('button');
        ham.className = 't-hamburger';
        ham.setAttribute('aria-label', 'Toggle navigation');
        ham.innerHTML = `<i class="fa-solid fa-ellipsis-vertical"></i>`;
        navbar.appendChild(ham);

        /* ── 2. Build overlay ── */
        const overlay = document.createElement('div');
        overlay.className = 't-overlay';
        overlay.id = 't-overlay';
        document.body.appendChild(overlay);

        /* ── 3. Build drawer from existing nav links ── */
        const drawer = document.createElement('div');
        drawer.className = 't-drawer';
        drawer.id = 't-drawer';
        drawer.setAttribute('aria-hidden', 'true');

        /* Clone links from desktop nav */
        const linkDefs = [];
        navLinks.querySelectorAll('a').forEach(a => {
            const txt = a.textContent.trim();
            const href = a.getAttribute('href') || '#';
            const isCta = a.classList.contains('btn-primary');
            const isActive = a.classList.contains('active');
            let icon = 'circle';
            const tl = txt.toLowerCase();
            if (tl.includes('explore') || href.includes('index')) icon = 'magnifying-glass';
            else if (tl.includes('dashboard')) icon = 'table-columns';
            else if (tl.includes('admin')) icon = 'shield-halved';
            else if (tl.includes('list') || tl.includes('property')) icon = 'plus-circle';
            else if (tl.includes('login')) icon = 'right-to-bracket';
            else if (tl.includes('sign') || tl.includes('register')) icon = 'user-plus';
            else if (tl.includes('logout')) icon = 'right-from-bracket';
            else if (tl.includes('profile')) icon = 'user';
            linkDefs.push({ txt, href, isCta, isActive, icon });
        });

        /* Also look for theme toggle button */
        const themeBtn = navLinks.querySelector('.theme-toggle, #theme-toggle');

        let linksHTML = linkDefs.map(l => `
            <a href="${l.href}" class="t-nav-link${l.isCta ? ' is-cta' : ''}${l.isActive ? ' active' : ''}">
                ${!l.isCta ? `<span class="t-nav-icon"><i class="fa-solid fa-${l.icon}"></i></span>` : ''}
                ${l.txt}
            </a>`).join('');

        if (themeBtn) {
            linksHTML += `
            <div class="t-drawer-sep"></div>
            <button class="t-nav-link" id="t-drawer-theme" type="button" style="cursor:pointer; background:none; border:1px solid var(--border-glass); width:100%; text-align:left;">
                <span class="t-nav-icon" id="t-drawer-theme-icon">
                    <i class="fa-solid ${isDark() ? 'fa-sun' : 'fa-moon'}"></i>
                </span>
                <span id="t-drawer-theme-label">${isDark() ? 'Light Mode' : 'Dark Mode'}</span>
            </button>`;
        }

        /* Language switcher row */
        linksHTML += `
            <div class="t-drawer-sep"></div>
            <div class="t-drawer-lang">
                <span class="t-drawer-lang-label">
                    <span class="t-nav-icon"><i class="fa-solid fa-language"></i></span>
                    Language
                </span>
                <div class="t-drawer-lang-opts">
                    <button class="t-drawer-lang-btn t-drawer-lang-active" data-lang="si">සිංහල</button>
                    <button class="t-drawer-lang-btn" data-lang="en">English</button>
                    <button class="t-drawer-lang-btn" data-lang="ta">தமிழ்</button>
                </div>
            </div>`;

        const yr = new Date().getFullYear();
        drawer.innerHTML = `
            <div class="t-drawer-head">
                <a href="index.php" class="logo" style="text-decoration:none;color:var(--text-primary);">
                    <i class="fa-solid fa-house-chimney-window" style="color:var(--primary);"></i>
                    MyHomeMyLand
                </a>
                <button class="t-close-btn" id="t-drawer-close" aria-label="Close menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="t-drawer-body">${linksHTML}</div>
            <div class="t-drawer-foot">&copy; ${yr} MyHomeMyLand &mdash; All rights reserved.</div>`;

        document.body.appendChild(drawer);

        /* ── 4. Open / close logic ── */
        let isOpen = false;

        function openMenu() {
            isOpen = true;
            ham.classList.add('is-open');
            overlay.classList.add('is-visible');
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeMenu() {
            isOpen = false;
            ham.classList.remove('is-open');
            overlay.classList.remove('is-visible');
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        ham.addEventListener('click', () => isOpen ? closeMenu() : openMenu());
        overlay.addEventListener('click', closeMenu);
        document.getElementById('t-drawer-close')?.addEventListener('click', closeMenu);
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && isOpen) closeMenu(); });

        /* Close at desktop width */
        window.matchMedia('(min-width: 993px)').addEventListener('change', e => {
            if (e.matches && isOpen) closeMenu();
        });

        /* ── 5b. Language buttons ── */
        drawer.querySelectorAll('.t-drawer-lang-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                drawer.querySelectorAll('.t-drawer-lang-btn').forEach(b => b.classList.remove('t-drawer-lang-active'));
                btn.classList.add('t-drawer-lang-active');
            });
        });

        /* ── 5. Drawer theme toggle syncs with main toggle ── */
        const drawerTheme = document.getElementById('t-drawer-theme');
        const mainTheme = document.getElementById('theme-toggle') || document.querySelector('.theme-toggle');
        if (drawerTheme && mainTheme) {
            drawerTheme.addEventListener('click', () => {
                mainTheme.click();
                const d = isDark();
                const iconEl = document.getElementById('t-drawer-theme-icon');
                const labelEl = document.getElementById('t-drawer-theme-label');
                if (iconEl) iconEl.innerHTML = `<i class="fa-solid ${d ? 'fa-sun' : 'fa-moon'}"></i>`;
                if (labelEl) labelEl.textContent = d ? 'Light Mode' : 'Dark Mode';
            });
        }
    }

    /* ================================================================
       SCROLL REVEAL  — IntersectionObserver
       ================================================================ */
    function initScrollReveal() {
        if (!('IntersectionObserver' in window)) {
            document.querySelectorAll('[data-reveal]').forEach(el => el.classList.add('is-revealed'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.07, rootMargin: '0px 0px -36px 0px' });

        document.querySelectorAll('[data-reveal]').forEach(el => observer.observe(el));
    }

    /* Call observer on any [data-reveal] that's now in view */
    function kickReveal() {
        document.querySelectorAll('[data-reveal]:not(.is-revealed)').forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight - 30) el.classList.add('is-revealed');
        });
    }

    /* ================================================================
       DYNAMIC CARD REVEAL  — watches listings grid
       ================================================================ */
    function initCardReveal() {
        if (!('IntersectionObserver' in window)) return;

        const cardObs = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    cardObs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.04, rootMargin: '0px 0px -20px 0px' });

        function attachCards() {
            document.querySelectorAll('.property-card:not([data-t-observed])').forEach((card, i) => {
                card.setAttribute('data-t-observed', '1');
                card.setAttribute('data-reveal', 'scale');
                /* Stagger: cap at 320ms so late cards don't wait forever */
                card.style.transitionDelay = Math.min((i % 6) * 70, 320) + 'ms';
                cardObs.observe(card);
            });
        }

        attachCards();

        /* Watch for AJAX-injected cards */
        const grid = document.getElementById('listings-grid');
        if (grid && typeof MutationObserver !== 'undefined') {
            new MutationObserver(() => setTimeout(attachCards, 60))
                .observe(grid, { childList: true });
        }
    }

    /* ================================================================
       ACTIVE NAV LINK
       ================================================================ */
    function markActiveLink() {
        const current = window.location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('.nav-links a, .t-nav-link').forEach(a => {
            const href = (a.getAttribute('href') || '').split('/').pop();
            if (href === current) a.classList.add('active');
        });
    }

    /* ================================================================
       FOOTER REVEAL — applies data-reveal to footer elements
       ================================================================ */
    function initFooterReveal() {
        const footer = document.querySelector('.site-footer');
        if (!footer) return;
        footer.querySelectorAll('.footer-brand, .footer-col').forEach((el, i) => {
            if (el.hasAttribute('data-reveal')) return;
            el.setAttribute('data-reveal', 'up');
            el.setAttribute('data-delay', String(i * 100));
        });
    }

    /* ================================================================
       SEARCH OVERLAY — Open/close + chip interaction
       ================================================================ */
    function initSearchOverlay() {
        const overlay = document.getElementById('t-search-overlay');
        const backdrop = document.getElementById('t-so-backdrop');
        const closeBtn = document.getElementById('t-search-close');
        const trigger = document.getElementById('t-search-trigger');
        const input = document.getElementById('search-text');
        const applyBtn = document.getElementById('apply-filters');

        if (!overlay) return;

        function openOverlay() {
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            /* Focus input after panel springs in */
            setTimeout(() => input && input.focus(), 420);
        }

        function closeOverlay() {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        /* trigger may be absent on mobile (pill handles open instead) */
        trigger && trigger.addEventListener('click', openOverlay);
        /* Floating filter button — always opens the overlay */
        const fab = document.getElementById('t-fab-filters');
        fab && fab.addEventListener('click', openOverlay);
        backdrop && backdrop.addEventListener('click', closeOverlay);
        closeBtn && closeBtn.addEventListener('click', closeOverlay);
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeOverlay();
        });

        /* Apply → close overlay then let script.js run the search */
        applyBtn && applyBtn.addEventListener('click', closeOverlay);

        /* Hide/show typewriter when user types */
        if (input) {
            const tw = document.getElementById('t-so-typewriter');
            input.addEventListener('input', () => {
                if (tw) tw.classList.toggle('is-hidden', input.value.length > 0);
            });
        }

        /* Quick chips → fill search input */
        overlay.querySelectorAll('.t-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                if (!input) return;
                input.value = chip.dataset.q || '';
                input.focus();
                const tw = document.getElementById('t-so-typewriter');
                if (tw) tw.classList.add('is-hidden');
                /* Visual feedback */
                overlay.querySelectorAll('.t-chip').forEach(c => c.classList.remove('is-active'));
                chip.classList.add('is-active');
                setTimeout(() => chip.classList.remove('is-active'), 600);
            });
        });
    }

    /* ================================================================
       TYPEWRITER — cycles animated placeholder phrases
       ================================================================ */
    function initTypewriter() {
        const textEl = document.getElementById('t-tw-text');
        if (!textEl) return;

        const phrases = [
            'Apartment in Colombo',
            '30 mins drive from WTC in traffic',
            'Walking distance to Royal College with AC',
            'Apartment near SLIIT with AC',
            'Apartment 15 mins drive from SLIIT with AC',
            'Villa in galle with swimming pool',
            '7-storey commerical building in Kotte',
        ];

        const TYPE_MS = 55;
        const DEL_MS = 25;
        const PAUSE_END = 1800;
        const PAUSE_NEW = 400;

        let phraseIdx = 0;
        let charIdx = 0;
        let deleting = false;
        let timer = null;
        let paused = false;

        function tick() {
            if (paused) return;
            const phrase = phrases[phraseIdx];

            if (!deleting) {
                charIdx++;
                textEl.textContent = phrase.slice(0, charIdx);
                if (charIdx === phrase.length) {
                    deleting = true;
                    timer = setTimeout(tick, PAUSE_END);
                    return;
                }
                timer = setTimeout(tick, TYPE_MS);
            } else {
                charIdx--;
                textEl.textContent = phrase.slice(0, charIdx);
                if (charIdx === 0) {
                    deleting = false;
                    phraseIdx = (phraseIdx + 1) % phrases.length;
                    timer = setTimeout(tick, PAUSE_NEW);
                    return;
                }
                timer = setTimeout(tick, DEL_MS);
            }
        }

        /* Start after brief delay */
        timer = setTimeout(tick, 900);

        /* Pause only when the user has actually typed something */
        const input = document.getElementById('search-text');
        if (input) {
            /* Pause animation while there is text in the input */
            input.addEventListener('input', () => {
                if (input.value.length > 0) {
                    paused = true;
                    clearTimeout(timer);
                } else {
                    /* Input cleared — restart typewriter */
                    paused = false;
                    charIdx = 0;
                    textEl.textContent = '';
                    clearTimeout(timer);
                    timer = setTimeout(tick, 500);
                }
            });
        }
    }

    /* ================================================================
       NAV PILL — Animated pill search bar (desktop, left of logo)
       ================================================================ */
    function initNavPill() {
        const pill = document.getElementById('t-nav-pill');
        const textEl = document.getElementById('t-nav-tw-text');
        const overlay = document.getElementById('t-search-overlay');
        if (!pill || !textEl || !overlay) return;

        /* Clicking the pill opens the same search overlay */
        function openOverlay() {
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            const input = document.getElementById('search-text');
            setTimeout(() => input && input.focus(), 420);
        }
        pill.addEventListener('click', openOverlay);
        pill.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openOverlay(); } });

        /* Typewriter — same phrases, same timing as the overlay */
        const phrases = [
            'Apartment in Colombo',
            '30 mins drive from WTC in traffic',
            'Walking distance to Royal College with AC',
            'Flat near SLIIT with AC',
            'Apartment 15 mins drive from SLIIT with AC',
            'Villa in Galle with swimming pool',
            '7-storey commerical building in Kotte',
        ];
        const TYPE_MS = 55, DEL_MS = 25, PAUSE_END = 1800, PAUSE_NEW = 400;
        let phraseIdx = 0, charIdx = 0, deleting = false, timer = null;

        function tick() {
            const phrase = phrases[phraseIdx];
            if (!deleting) {
                charIdx++;
                textEl.textContent = phrase.slice(0, charIdx);
                if (charIdx === phrase.length) { deleting = true; timer = setTimeout(tick, PAUSE_END); return; }
                timer = setTimeout(tick, TYPE_MS);
            } else {
                charIdx--;
                textEl.textContent = phrase.slice(0, charIdx);
                if (charIdx === 0) { deleting = false; phraseIdx = (phraseIdx + 1) % phrases.length; timer = setTimeout(tick, PAUSE_NEW); return; }
                timer = setTimeout(tick, DEL_MS);
            }
        }
        timer = setTimeout(tick, 1400);

        /* Pause while overlay is open, restart when it closes */
        const mo = new MutationObserver(() => {
            if (overlay.classList.contains('is-open')) {
                clearTimeout(timer);
            } else {
                clearTimeout(timer);
                charIdx = 0; textEl.textContent = '';
                timer = setTimeout(tick, 600);
            }
        });
        mo.observe(overlay, { attributes: true, attributeFilter: ['class'] });
    }

    /* ================================================================
       ACTIVE FILTERS BAR  — shows selected filters as removable pills
       ================================================================ */
    function initActiveFiltersBar() {
        const bar      = document.getElementById('t-active-bar');
        const pillsCon = document.getElementById('t-active-pills');
        const clearAll = document.getElementById('t-active-clear');
        if (!bar || !pillsCon) return;

        /* Map of filter id → { label, default value } */
        const FILTERS = [
            { id: 'search-text',           label: 'Search',   def: '',        isInput: true },
            { id: 'filter-type-mobile',     label: 'Type',     def: 'All' },
            { id: 'filter-location-mobile', label: 'Location', def: 'All' },
            { id: 'filter-beds-mobile',     label: 'Beds',     def: 'All' },
            { id: 'filter-baths-mobile',    label: 'Baths',    def: 'All' },
            { id: 'filter-sort-mobile',     label: 'Sort',     def: 'newest' },
        ];

        function buildPills() {
            pillsCon.innerHTML = '';
            let active = 0;

            FILTERS.forEach(f => {
                const el = document.getElementById(f.id);
                if (!el) return;
                const val = el.value;
                if (f.isInput ? val.trim() === '' : val === f.def) return;
                active++;
                const pill = document.createElement('div');
                pill.className = 't-active-pill';
                /* Display value: for selects use selectedOptions text, for inputs use value */
                const display = f.isInput
                    ? '"' + val + '"'
                    : (el.selectedOptions && el.selectedOptions[0]
                        ? el.selectedOptions[0].text
                        : val);
                pill.innerHTML =
                    '<span>' + display + '</span>' +
                    '<button class="t-apill-remove" data-id="' + f.id + '" data-def="' + f.def + '" data-input="' + (f.isInput ? '1' : '') + '" aria-label="Remove">' +
                    '<i class="fa-solid fa-xmark"></i></button>';
                pillsCon.appendChild(pill);
            });

            /* Price range pill — show when non-zero min or non-max max */
            const minSel = document.getElementById('filter-min-price-select');
            const maxSel = document.getElementById('filter-max-price-select');
            const priceDisp = document.getElementById('price-range-display');
            if (minSel && maxSel && (minSel.value || maxSel.value)) {
                active++;
                const pill = document.createElement('div');
                pill.className = 't-active-pill';
                const txt = priceDisp ? priceDisp.innerText : 'Price';
                pill.innerHTML =
                    '<span>' + txt + '</span>' +
                    '<button class="t-apill-remove" data-id="price" data-def="" aria-label="Remove price">' +
                    '<i class="fa-solid fa-xmark"></i></button>';
                pillsCon.appendChild(pill);
            }

            bar.classList.toggle('has-filters', active > 0);
        }

        /* Remove individual filter when pill X is clicked */
        pillsCon.addEventListener('click', function (e) {
            const btn = e.target.closest('.t-apill-remove');
            if (!btn) return;
            const id = btn.dataset.id;

            if (id === 'price') {
                const minSel = document.getElementById('filter-min-price-select');
                const maxSel = document.getElementById('filter-max-price-select');
                if (minSel) minSel.value = '';
                if (maxSel) maxSel.value = '';
                const slider = document.getElementById('price-slider');
                if (slider && slider.noUiSlider) slider.noUiSlider.reset();
            } else {
                const el = document.getElementById(id);
                if (!el) return;
                el.value = btn.dataset.def || '';
            }

            /* Re-run the search (clicks apply-filters; overlay is closed so closeOverlay is a no-op) */
            const applyBtn = document.getElementById('apply-filters');
            if (applyBtn) applyBtn.click();
            buildPills();
        });

        /* Clear all → delegate to existing clear button */
        if (clearAll) {
            clearAll.addEventListener('click', function () {
                const clearBtn = document.getElementById('clear-filters');
                if (clearBtn) clearBtn.click();
                buildPills();
            });
        }

        /* Re-evaluate after every apply-filters click */
        const applyBtn = document.getElementById('apply-filters');
        if (applyBtn) applyBtn.addEventListener('click', function () {
            /* Small delay lets script.js finish fetching then we refresh pills */
            setTimeout(buildPills, 60);
        });

        /* Also watch select changes live (user adjusts inside open overlay) */
        FILTERS.forEach(function (f) {
            const el = document.getElementById(f.id);
            if (!el) return;
            el.addEventListener(f.isInput ? 'input' : 'change', buildPills);
        });

        buildPills(); /* initial state */
    }

    /* ================================================================
       INIT — DOMContentLoaded entry point
       ================================================================ */
    function init() {
        /* Theme is already set at top of file */

        /* Header features — only when a header exists */
        if (document.querySelector('.site-header')) {
            syncHeaderHeight();
            initHeaderScroll();
            initMobileMenu();
        }

        initScrollReveal();
        initCardReveal();
        initFooterReveal();
        markActiveLink();
        initSearchOverlay();
        initTypewriter();
        initNavPill();
        initActiveFiltersBar();
    }


    document.addEventListener('DOMContentLoaded', init);

})();
