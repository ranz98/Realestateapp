<?php
//require_once 'auth_check.php';
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.css" />
    <!-- Instant theme: prevent flash of wrong theme -->
    <script>try { if (localStorage.getItem('theme') === 'dark') document.documentElement.setAttribute('data-theme', 'dark'); } catch (e) { }</script>
    <?php include 'get-theme.php'; ?>

    <style>
        /* ============================================================
           CSS VARIABLES & RESET
           ============================================================ */
        :root {
            --primary: #0ea5e9;
            --primary-hover: #0284c7;
            --accent: #38bdf8;
            --bg-main: #f8fafc;
            --bg-panel: #ffffff;
            --bg-card: #ffffff;
            --border-glass: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --radius-lg: 16px;
            --radius-md: 8px;
            --radius-sm: 6px;
            --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --transition: all 0.3s ease;
        }

        .leaflet-control-attribution {
            display: none !important;
        }

        [data-theme="dark"] {
            --bg-main: #0f172a;
            --bg-panel: #1e293b;
            --bg-card: #1e293b;
            --border-glass: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --shadow-card: 0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.3);
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        h1,
        h2,
        h3,
        h4,
        .logo {
            font-family: 'Outfit', sans-serif;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-main);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--primary), var(--primary-hover)) !important;
            border-radius: 10px !important;
        }

        [data-theme="dark"] ::-webkit-scrollbar-thumb {
            background: #334155;
        }

        @media (max-width: 992px) {
            ::-webkit-scrollbar {
                width: 3px;
                height: 3px;
            }

            ::-webkit-scrollbar-track {
                background: transparent;
            }

            ::-webkit-scrollbar-thumb {
                background: #0ea5e9;
                border-radius: 99px;
            }

            * {
                scrollbar-width: thin;
                scrollbar-color: #0ea5e9 transparent;
            }
        }

        /* Hide mobile-only on desktop */
        .mode-overlay,
        .mobile-view-toggle,
        .mobile-only-flex {
            display: none;
        }

        /* ============================================================
           HEADER — STICKY GLASS (terminal.css)
           ============================================================ */
        .site-header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            transform: none !important;
            width: 100% !important;
            max-width: none !important;
            border-radius: 0 !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            background: rgba(248, 250, 252, 0.82) !important;
            backdrop-filter: blur(28px) saturate(160%) !important;
            -webkit-backdrop-filter: blur(28px) saturate(160%) !important;
            border-bottom: 1px solid rgba(226, 232, 240, 0.7) !important;
            box-shadow: 0 1px 12px rgba(0, 0, 0, 0.06) !important;
            transition: box-shadow 0.35s ease, background 0.35s ease !important;
            overflow: visible !important;
            z-index: 1000 !important;
        }

        [data-theme="dark"] .site-header {
            background: rgba(15, 23, 42, 0.82) !important;
            border-bottom: 1px solid rgba(51, 65, 85, 0.55) !important;
            box-shadow: 0 1px 12px rgba(0, 0, 0, 0.28) !important;
        }

        .site-header.t-scrolled {
            background: rgba(248, 250, 252, 0.96) !important;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.09) !important;
        }

        [data-theme="dark"] .site-header.t-scrolled {
            background: rgba(15, 23, 42, 0.97) !important;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.4) !important;
        }

        /* ── Navbar ── */
        .navbar {
            display: flex !important;
            align-items: center !important;
            gap: 0.6rem !important;
            padding: 0.55rem 1rem !important;
            position: relative;
        }

        /* Hide old filter bars */
        .filter-bar-desktop {
            display: none !important;
        }

        .filter-bar-mobile {
            display: none !important;
        }

        /* ── Logo ── */
        .t-logo {
            display: flex !important;
            align-items: center !important;
            gap: 0.45rem !important;
            flex-shrink: 0 !important;
            text-decoration: none !important;
            transition: opacity 0.2s ease !important;
        }

        .t-logo:hover {
            opacity: 0.72 !important;
        }

        .t-logo-icon {
            font-size: 1.35rem !important;
            color: var(--primary) !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
        }

        .t-logo-words {
            display: flex !important;
            flex-direction: row !important;
            align-items: baseline !important;
            gap: 0 !important;
            line-height: 1 !important;
        }

        .t-logo-top,
        .t-logo-bot {
            font-family: 'Outfit', sans-serif !important;
            font-size: 1rem !important;
            font-weight: 800 !important;
            letter-spacing: -0.02em !important;
            text-transform: uppercase !important;
            white-space: nowrap !important;
        }

        .t-logo-top {
            color: var(--text-primary) !important;
        }

        .t-logo-bot {
            color: var(--primary) !important;
        }

        /* ── Nav Pill Search Bar ── */
        .t-nav-left {
            display: flex !important;
            align-items: center !important;
            gap: 0.55rem !important;
            background: var(--bg-main) !important;
            border: 1.5px solid var(--border-glass) !important;
            border-radius: 50px !important;
            padding: 0.42rem 0.8rem 0.42rem 1rem !important;
            cursor: pointer !important;
            min-width: 200px !important;
            max-width: 270px !important;
            transition: border-color 0.22s, box-shadow 0.22s !important;
            user-select: none !important;
            flex-shrink: 0 !important;
        }

        .t-nav-left:hover {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.12) !important;
        }

        .t-nav-pill-icon {
            color: var(--primary) !important;
            font-size: 0.95rem !important;
            flex-shrink: 0 !important;
        }

        .t-nav-pill-tw {
            display: flex !important;
            align-items: center !important;
            font-size: 0.82rem !important;
            color: var(--text-secondary) !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            flex: 1 !important;
            min-width: 0 !important;
            gap: 1px !important;
        }

        .t-tw-cursor {
            display: inline-block;
            color: var(--primary);
            font-weight: 300;
            animation: t-blink 0.85s step-end infinite;
            opacity: 0.9;
        }

        @keyframes t-blink {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: 0
            }
        }

        @media (min-width: 993px) {
            .t-search-trigger {
                display: none !important;
            }
        }

        /* ── Right nav links ── */
        .nav-links {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            flex-shrink: 0 !important;
        }

        .nav-links a {
            position: relative !important;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem !important;
            transition: color 0.3s ease;
            white-space: nowrap;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a.active {
            color: var(--primary);
        }

        .nav-links a::after {
            content: '' !important;
            position: absolute !important;
            bottom: -1px !important;
            left: 0 !important;
            width: 100% !important;
            height: 1.5px !important;
            background: var(--primary) !important;
            transform: scaleX(0) !important;
            transform-origin: right !important;
            transition: transform 0.4s cubic-bezier(0.76, 0, 0.24, 1) !important;
            border-radius: 2px !important;
        }

        .nav-links a:hover::after,
        .nav-links a.active::after {
            transform: scaleX(1) !important;
            transform-origin: left !important;
        }

        /* Theme toggle */
        .theme-toggle {
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            color: var(--text-secondary);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1.1rem;
            overflow: hidden;
            position: relative;
        }

        .theme-toggle i {
            transition: transform 0.4s ease, opacity 0.4s ease;
        }

        .theme-toggle:hover {
            color: var(--primary);
            border-color: var(--primary);
            transform: scale(1.1);
        }

        .theme-toggle:active {
            transform: rotate(180deg) scale(1.05);
        }

        [data-theme="dark"] .theme-toggle i.fa-moon {
            display: none;
        }

        html:not([data-theme="dark"]) .theme-toggle i.fa-sun {
            display: none;
        }

        /* ── Hamburger ── */
        .t-hamburger {
            display: none !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 4px !important;
            width: 32px !important;
            height: 32px !important;
            background: #fff !important;
            border: 1.5px solid var(--border-glass) !important;
            border-radius: 50% !important;
            cursor: pointer !important;
            padding: 0 !important;
            transition: all 0.25s ease !important;
            flex-shrink: 0 !important;
        }

        .t-hamburger:hover {
            border-color: #0ea5e9 !important;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.12) !important;
        }

        .t-hamburger i {
            font-size: 1rem;
            color: #000;
            transition: color 0.2s ease;
        }

        /* ── Active filters bar (mobile only) ── */
        .t-active-bar {
            display: none !important;
            align-items: center;
            gap: 0.3rem;
            padding: 0.1rem 0.8rem;
            border-top: 1px solid var(--border-glass);
            min-height: 24px;
            max-height: 24px;
            overflow: hidden;
            background: var(--bg-main);
        }

        .t-active-bar.has-filters {
            display: flex !important;
        }

        .t-active-pills {
            display: flex;
            gap: 0.32rem;
            align-items: center;
            overflow-x: auto;
            flex: 1;
            min-width: 0;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .t-active-pills::-webkit-scrollbar {
            display: none;
        }

        .t-active-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.22rem;
            padding: 0.08rem 0.25rem 0.08rem 0.45rem;
            background: var(--primary);
            color: #fff;
            border-radius: 50px;
            font-size: 0.62rem;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
            line-height: 1;
            font-family: 'Outfit', sans-serif;
            animation: t-apill-in 0.2s cubic-bezier(.34, 1.56, .64, 1) both;
        }

        @keyframes t-apill-in {
            from {
                opacity: 0;
                transform: scale(0.75)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        .t-apill-remove {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.28);
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 0;
            flex-shrink: 0;
            font-size: 0.5rem;
            line-height: 1;
            transition: background 0.15s;
        }

        .t-apill-remove:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .t-active-clear {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--text-secondary);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.58rem;
            opacity: 0.55;
            transition: opacity 0.18s, background 0.18s;
        }

        .t-active-clear:hover {
            opacity: 1;
            background: #ef4444;
        }

        @media (min-width: 993px) {
            .t-active-bar {
                display: none !important;
            }
        }

        /* ── Mobile Nav Overlay + Drawer ── */
        .t-overlay {
            display: block;
            position: fixed;
            inset: 0;
            z-index: 8900;
            background: rgba(0, 0, 0, 0.3);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s ease;
        }

        .t-overlay.is-visible {
            opacity: 1;
            pointer-events: all;
        }

        .t-drawer {
            position: fixed;
            top: 0;
            right: 0;
            width: min(300px, 82vw);
            height: 100svh;
            height: 100vh;
            z-index: 8901;
            background: var(--bg-panel);
            border-left: 1px solid var(--border-glass);
            transform: translateX(100%);
            transition: transform 0.42s cubic-bezier(0.76, 0, 0.24, 1);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            box-shadow: -8px 0 48px rgba(0, 0, 0, 0.14);
        }

        .t-drawer.is-open {
            transform: translateX(0);
        }

        .t-drawer-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid var(--border-glass);
            flex-shrink: 0;
        }

        .t-close-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid var(--border-glass);
            background: none;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .t-close-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(14, 165, 233, 0.07);
        }

        .t-drawer-body {
            flex: 1;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .t-nav-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.8rem 0.9rem;
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.22s ease;
            border: 1px solid transparent;
        }

        .t-nav-link:hover {
            background: rgba(14, 165, 233, 0.07);
            border-color: rgba(14, 165, 233, 0.14);
            color: var(--primary);
            transform: translateX(3px);
        }

        .t-nav-link .t-nav-icon {
            width: 18px;
            text-align: center;
            color: var(--primary);
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .t-nav-link.is-cta {
            background: var(--primary);
            color: white !important;
            border-color: transparent !important;
            justify-content: center;
            margin-top: 0.4rem;
        }

        .t-nav-link.is-cta:hover {
            background: var(--primary-hover) !important;
            transform: none;
        }

        .t-drawer-sep {
            height: 1px;
            background: var(--border-glass);
            margin: 0.6rem 0;
        }

        .t-drawer-foot {
            padding: 0.9rem 1.25rem;
            border-top: 1px solid var(--border-glass);
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-align: center;
            flex-shrink: 0;
            letter-spacing: 0.02em;
        }

        /* ============================================================
           BUTTONS
           ============================================================ */
        .btn-primary {
            background: var(--primary);
            color: white !important;
            padding: 0.6rem 1.4rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.01em;
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.18) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            border-radius: inherit;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(14, 165, 233, 0.4);
        }

        .btn-primary:hover::after {
            opacity: 1;
        }

        .btn-secondary {
            background: var(--bg-main);
            color: var(--text-primary);
            border: 1px solid var(--border-glass);
            padding: 0.6rem 1.4rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--border-glass);
        }

        /* Buy/Rent Toggle */
        .mode-toggle,
        .t-mode-pill {
            display: flex;
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            border-radius: 50px;
            padding: 3px;
            flex-shrink: 0;
        }

        .mode-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            padding: 0.45rem 1rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .mode-btn:hover {
            color: var(--primary);
        }

        .mode-btn.mode-active {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }

        /* Filter selects */
        .filter-select {
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
            padding: 0.5rem 0.85rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 500;
            outline: none;
            min-width: 0;
            transition: border-color 0.2s ease;
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 8px;
            padding-right: 1.8rem;
        }

        .filter-select:hover,
        .filter-select:focus {
            border-color: var(--primary);
        }

        /* Search input */
        .search-input {
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
            padding: 0.5rem 0.85rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .search-input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .search-input:focus {
            border-color: var(--primary);
        }

        /* ============================================================
           DESKTOP FILTER BAR (t-desktop-filter-bar)
           ============================================================ */
        .t-desktop-filter-bar {
            display: none;
        }

        @media (min-width: 993px) {
            .t-desktop-filter-bar {
                display: block;
                background: var(--bg-panel);
                border-bottom: 1px solid var(--border-glass);
                padding: 0.65rem 2rem;
                position: sticky;
                top: 60px;
                z-index: 990;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            }

            .t-dfb-inner {
                max-width: 1400px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                gap: 0.8rem;
            }

            .t-dfb-divider {
                width: 1px;
                height: 24px;
                background: var(--border-glass);
                margin: 0 0.2rem;
            }

            .t-dfb-search {
                flex: 1;
                min-width: 0;
                display: flex;
                align-items: center;
                gap: 0.4rem;
                background: var(--bg-main);
                border: 1px solid var(--border-glass);
                border-radius: 50px;
                padding: 0 0.75rem;
                transition: border-color 0.2s;
            }

            .t-dfb-search:focus-within {
                border-color: var(--primary);
            }

            .t-dfb-search i {
                color: var(--text-secondary);
                font-size: 0.75rem;
                flex-shrink: 0;
            }

            .t-dfb-search .search-input {
                background: transparent !important;
                border: none !important;
                padding: 0.38rem 0 !important;
                font-size: 0.82rem !important;
                flex: 1;
            }
        }

        /* ── Mobile Filter Trigger ── */
        .t-nav-filter-btn {
            display: none !important;
        }

        @media (max-width: 992px) {
            .t-nav-filter-btn {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 34px !important;
                height: 34px !important;
                border-radius: 50% !important;
                background: #fff !important;
                color: #000 !important;
                border: 1.5px solid var(--border-glass) !important;
                cursor: pointer !important;
                flex-shrink: 0 !important;
                font-size: 0.85rem !important;
                transition: all 0.22s ease !important;
                order: 3 !important;
            }

            .t-nav-filter-btn:hover {
                border-color: #0ea5e9 !important;
                box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.12) !important;
            }
        }

        /* ============================================================
           MAIN LAYOUT
           ============================================================ */
        .main-container {
            display: flex;
            height: calc(100vh - 145px);
            overflow: hidden;
            margin-top: var(--t-header-h, 130px) !important;
            height: calc(100svh - var(--t-header-h, 130px)) !important;
        }

        /* Listings Section */
        .listings-section {
            flex: 1;
            max-width: 55%;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            overflow-y: auto;
        }

        .listings-scroll-container {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-y;
        }

        .listings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.2rem;
            align-items: stretch !important;
        }

        /* Map Section */
        .map-section {
            flex: 1;
            position: relative;
            border-left: 1px solid var(--border-glass);
        }

        [data-theme="dark"] .map-section {
            border-left-color: var(--border-glass);
        }

        #map {
            width: 100%;
            height: 100%;
            background-color: var(--bg-main);
            z-index: 1;
        }

        /* Map price markers */
        .custom-price-marker-wrapper {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        .price-marker-label {
            background: var(--bg-card);
            border: 2px solid var(--primary);
            color: var(--text-primary);
            font-weight: bold;
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-card);
            text-align: center;
            white-space: nowrap;
            opacity: 0.65;
            transition: opacity 0.2s ease;
        }

        .price-marker-label:hover {
            opacity: 1;
        }

        .price-marker-label::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px 6px 0;
            border-style: solid;
            border-color: var(--primary) transparent transparent transparent;
        }

        .leaflet-popup-content-wrapper,
        .leaflet-popup-tip {
            background: var(--bg-card) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-glass);
            box-shadow: var(--shadow-card) !important;
        }

        .leaflet-container a.leaflet-popup-close-button {
            color: var(--text-secondary) !important;
        }

        /* ============================================================
           PROPERTY CARDS — terminal redesign
           ============================================================ */
        .property-card {
            display: flex !important;
            flex-direction: column !important;
            background: var(--bg-card) !important;
            border: 1px solid var(--border-glass) !important;
            border-radius: 16px !important;
            overflow: hidden !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04) !important;
            transition: transform 0.4s cubic-bezier(0.34, 1.3, 0.64, 1), box-shadow 0.4s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.3s ease !important;
            will-change: transform;
            cursor: pointer;
        }

        .property-card:hover {
            transform: translateY(-6px) !important;
            border-color: rgba(14, 165, 233, 0.3) !important;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.1), 0 4px 12px rgba(0, 0, 0, 0.06), 0 0 0 1px rgba(14, 165, 233, 0.12) !important;
        }

        [data-theme="dark"] .property-card {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.22), 0 1px 2px rgba(0, 0, 0, 0.15) !important;
        }

        [data-theme="dark"] .property-card:hover {
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45), 0 4px 12px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(14, 165, 233, 0.2) !important;
        }

        /* Image container */
        .img-container {
            position: relative !important;
            width: 100% !important;
            aspect-ratio: 16/9 !important;
            overflow: hidden !important;
            flex-shrink: 0 !important;
        }

        @media (min-width: 993px) {
            .img-container {
                aspect-ratio: unset !important;
                height: 185px !important;
            }
        }

        /* Scale image on hover desktop only */
        @media (min-width: 993px) {
            .property-card:hover .card-slider-wrapper {
                transform: scale(1.04) !important;
                transition: transform 0.55s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            .card-slider-wrapper {
                transition: transform 0.55s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
        }

        /* Gradient overlay */
        .pc-img-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 2;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.28) 0%, transparent 28%, transparent 40%, rgba(0, 0, 0, 0.72) 100%);
        }

        /* Top badge row */
        .pc-top-row {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 6px;
            z-index: 5;
            pointer-events: none;
        }

        .pc-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.28);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 0.28rem 0.6rem;
            border-radius: 50px;
            white-space: nowrap;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        }

        .pc-type-badge i {
            font-size: 0.65rem;
            opacity: 0.9;
        }

        .pc-mode-badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            padding: 0.32rem 0.75rem;
            border-radius: 50px;
            white-space: nowrap;
            border: 1.5px solid rgba(255, 255, 255, 0.4);
        }

        .pc-mode-rent {
            background: #0ea5e9;
            color: #fff;
            box-shadow: 0 2px 10px rgba(14, 165, 233, 0.6);
        }

        .pc-mode-buy {
            background: #0284c7;
            color: #fff;
            box-shadow: 0 2px 10px rgba(2, 132, 199, 0.6);
        }

        /* Bottom row */
        .pc-bottom-row {
            position: absolute;
            bottom: 7px;
            left: 0;
            right: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 8px;
            z-index: 3;
            pointer-events: none;
        }

        .pc-price {
            display: inline-flex;
            align-items: baseline;
            gap: 3px;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 0.95rem;
            color: #0ea5e9;
            background: #fff;
            padding: 0.22rem 0.6rem;
            border-radius: 50px;
            white-space: nowrap;
            line-height: 1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .pc-price-per {
            font-size: 0.65rem;
            font-weight: 500;
            opacity: 0.75;
            margin-left: 1px;
            color: var(--text-secondary);
        }

        /* Dots */
        .pc-bottom-row .card-slider-dots {
            position: static !important;
            transform: none !important;
            display: flex;
            gap: 4px;
            pointer-events: all;
        }

        .card-slider-dots {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 4px;
            z-index: 10;
        }

        .card-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transition: background 0.3s;
        }

        .card-dot.active {
            background: white;
            transform: scale(1.2);
        }

        /* Stats overlay on image */
        .pc-stats-overlay {
            position: absolute;
            bottom: 27px;
            left: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 4;
            pointer-events: none;
        }

        .pc-stats-overlay span {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.6rem;
            font-weight: 600;
            white-space: nowrap;
            line-height: 1;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.8);
        }

        .pc-stats-overlay span i {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.56rem;
            filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.7));
        }

        /* Hidden old overlapping elements */
        .type-tag {
            display: none !important;
        }

        .mode-badge {
            display: none !important;
        }

        .price-tag {
            display: none !important;
        }

        .property-metrics {
            display: none !important;
        }

        .pc-stats-row {
            display: none !important;
        }

        /* Card slider */
        .card-slider-wrapper {
            display: flex;
            height: 100%;
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-x;
            cursor: grab;
            overscroll-behavior-x: contain;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .card-slider-wrapper:active {
            cursor: grabbing;
        }

        .card-slider-wrapper::-webkit-scrollbar {
            display: none;
        }

        .card-slider-wrapper img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            flex: 0 0 100%;
            scroll-snap-align: start;
            pointer-events: none;
            -webkit-user-drag: none;
            user-select: none;
            min-width: 100%;
        }

        .card-img-slide {
            min-width: 100%;
            height: 100%;
            object-fit: cover;
            flex-shrink: 0;
            scroll-snap-align: start;
            user-select: none;
            pointer-events: none;
        }

        /* Slider nav arrows */
        .card-slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(4px) !important;
            border: none !important;
            width: 26px !important;
            height: 26px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            transition: opacity 0.2s ease, transform 0.2s ease !important;
            z-index: 4 !important;
            color: #0f172a !important;
            font-size: 0.7rem !important;
            opacity: 0 !important;
        }

        .img-container:hover .card-slider-nav {
            opacity: 1 !important;
        }

        .card-slider-prev {
            left: 8px !important;
        }

        .card-slider-next {
            right: 8px !important;
        }

        /* Property info body */
        .property-info {
            display: flex !important;
            flex-direction: column !important;
            padding: 0.85rem 0.9rem 0.8rem !important;
            flex: 1 !important;
            min-height: 112px !important;
            gap: 0 !important;
        }

        .property-title {
            font-family: 'Outfit', sans-serif !important;
            font-size: 0.9rem !important;
            font-weight: 700 !important;
            line-height: 1.35 !important;
            color: var(--text-primary) !important;
            margin: 0 0 0.4rem 0 !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            overflow: hidden !important;
            min-height: calc(0.9rem * 1.35 * 2) !important;
        }

        .property-location {
            display: flex !important;
            align-items: center !important;
            gap: 5px !important;
            color: var(--text-secondary) !important;
            font-size: 0.73rem !important;
            font-weight: 500 !important;
            margin: 0 !important;
            overflow: hidden !important;
            white-space: nowrap !important;
            margin-bottom: 0 !important;
            flex-shrink: 0 !important;
        }

        .property-location i {
            color: var(--primary) !important;
            font-size: 0.7rem !important;
            flex-shrink: 0 !important;
        }

        .property-location span {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        /* noUiSlider overrides */
        .price-slider-container {
            padding: 0 10px;
            margin-top: 8px;
            margin-bottom: 5px;
        }

        .price-display {
            font-size: 0.85rem;
            color: var(--primary);
            font-weight: 600;
            text-align: center;
            margin-top: 0.8rem;
        }

        .noUi-target {
            background: var(--border-glass);
            border: none;
            box-shadow: none;
            height: 6px;
        }

        .noUi-connect {
            background: var(--primary);
        }

        .noUi-handle {
            border: 2px solid var(--primary);
            background: var(--bg-card);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            cursor: pointer;
        }

        .noUi-horizontal .noUi-handle {
            width: 20px;
            height: 20px;
            right: -10px;
            top: -7px;
        }

        .noUi-handle::before,
        .noUi-handle::after {
            display: none;
        }

        /* ============================================================
           MOBILE VIEW TOGGLE PILL
           ============================================================ */
        .mobile-view-toggle {
            display: none;
            position: fixed;
            bottom: 1.2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1100;
            background: var(--bg-panel);
            border: 2px solid var(--border-glass);
            border-radius: 50px;
            padding: 5px;
            gap: 4px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
            align-items: center;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .mvt-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0.55rem 1.1rem;
            border: none;
            border-radius: 50px;
            background: transparent;
            color: var(--text-secondary);
            font-size: 0.82rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mvt-btn:hover {
            color: var(--primary);
            background: rgba(14, 165, 233, 0.08);
        }

        .mvt-btn.mvt-active {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: #fff;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.35);
        }

        .mvt-btn i {
            font-size: 0.9rem;
        }

        /* ============================================================
           SPLIT MODE
           ============================================================ */
        .main-container.split-mode {
            flex-direction: column !important;
            height: calc(100dvh - var(--t-header-h, 130px)) !important;
            overflow: hidden !important;
            margin-top: var(--t-header-h, 130px) !important;
        }

        .split-mode .listings-section {
            display: flex !important;
            flex: 0 0 50% !important;
            max-width: 100% !important;
            width: 100% !important;
            height: 50% !important;
            overflow-y: auto !important;
            border-bottom: 2px solid var(--primary) !important;
        }

        .split-mode .map-section {
            display: block !important;
            flex: 0 0 50% !important;
            width: 100% !important;
            height: 50% !important;
            border-left: none !important;
        }

        /* ============================================================
           SEARCH OVERLAY
           ============================================================ */
        .t-search-overlay {
            position: fixed;
            inset: 0;
            z-index: 9500;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: clamp(60px, 10vh, 110px);
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.28s ease, visibility 0.28s ease;
        }

        .t-search-overlay.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: all;
        }

        .t-so-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.3);
            cursor: pointer;
        }

        .t-so-panel {
            position: relative;
            z-index: 1;
            width: min(680px, 94vw);
            background: var(--bg-panel);
            border: 1px solid var(--border-glass);
            border-radius: 22px;
            padding: 1.6rem 1.75rem 1.5rem;
            box-shadow: 0 40px 90px rgba(0, 0, 0, 0.38), 0 0 0 1px rgba(255, 255, 255, 0.05) inset;
            transform: translateY(-32px) scale(0.96);
            transition: transform 0.42s cubic-bezier(0.34, 1.5, 0.64, 1), opacity 0.3s ease;
            opacity: 0;
        }

        .t-search-overlay.is-open .t-so-panel {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        [data-theme="dark"] .t-so-panel {
            box-shadow: 0 40px 90px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.04) inset;
        }

        .t-so-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.3rem;
        }

        .t-so-close {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid var(--border-glass);
            background: none;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .t-so-close:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Big search input */
        .t-so-input-wrap {
            position: relative;
            margin-bottom: 1rem;
        }

        .t-so-search-icon {
            position: absolute;
            left: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1rem;
            z-index: 2;
            pointer-events: none;
            animation: t-so-pulse 2.2s ease-in-out infinite;
        }

        @keyframes t-so-pulse {

            0%,
            100% {
                opacity: 1;
                transform: translateY(-50%) scale(1)
            }

            50% {
                opacity: .55;
                transform: translateY(-50%) scale(.88)
            }
        }

        .t-so-input {
            width: 100% !important;
            background: var(--bg-main) !important;
            border: 2px solid var(--border-glass) !important;
            border-radius: 50px !important;
            padding: 0.95rem 1.25rem 0.95rem 3.1rem !important;
            font-size: 1.08rem !important;
            font-family: 'Outfit', sans-serif !important;
            font-weight: 500 !important;
            color: var(--text-primary) !important;
            outline: none !important;
            caret-color: var(--primary);
            transition: border-color 0.25s ease, box-shadow 0.25s ease !important;
            box-sizing: border-box !important;
            position: relative;
            z-index: 1;
        }

        .t-so-input:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15), 0 6px 28px rgba(14, 165, 233, 0.14) !important;
            animation: t-so-glow 2.6s ease-in-out infinite !important;
        }

        @keyframes t-so-glow {

            0%,
            100% {
                box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15), 0 6px 28px rgba(14, 165, 233, 0.14)
            }

            50% {
                box-shadow: 0 0 0 6px rgba(14, 165, 233, 0.25), 0 10px 40px rgba(14, 165, 233, 0.22)
            }
        }

        .t-so-typewriter {
            position: absolute;
            left: 3.1rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            z-index: 3;
            display: flex;
            align-items: center;
            font-size: 1.08rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 500;
            color: var(--text-secondary);
            opacity: 0.5;
            transition: opacity 0.15s ease;
            white-space: nowrap;
            overflow: hidden;
            max-width: calc(100% - 3.5rem);
        }

        .t-so-typewriter.is-hidden {
            opacity: 0 !important;
        }

        /* Chips */
        .t-so-chips {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: wrap;
            margin-bottom: 1.3rem;
        }

        .t-so-chips-label {
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--text-secondary);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            flex-shrink: 0;
            margin-right: 0.15rem;
        }

        .t-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            border: 1px solid var(--border-glass);
            background: var(--bg-main);
            color: var(--text-secondary);
            font-size: 0.76rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .t-chip:hover,
        .t-chip.is-active {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(14, 165, 233, 0.09);
            transform: translateY(-1px);
        }

        .t-chip i {
            font-size: 0.65rem;
        }

        /* Filters grid inside overlay */
        .t-so-filters {
            display: flex;
            gap: 0.45rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            margin-top: 0.5rem !important;
            padding-top: 0.75rem !important;
            border-top: 1px solid var(--border-glass) !important;
        }

        .t-so-filters-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr 1fr !important;
            gap: 0.45rem 0.5rem !important;
            width: 100%;
        }

        .t-so-filter-group .t-pill-select-wrapper {
            display: flex !important;
            align-items: center !important;
            background-color: var(--bg-main) !important;
            border: 1px solid var(--border-glass) !important;
            border-radius: 50px !important;
            padding: 0 0.55rem !important;
            height: 32px !important;
            transition: all 0.2s ease !important;
        }

        .t-so-filter-group .t-pill-select-wrapper:focus-within {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15) !important;
        }

        .t-so-filter-group .t-pill-select-wrapper i:first-child {
            color: #0ea5e9 !important;
            font-size: 0.75rem !important;
            margin-right: 0.35rem !important;
            flex-shrink: 0 !important;
        }

        .t-so-filter-group .filter-select {
            width: 100% !important;
            font-size: 0.72rem !important;
            padding: 0 !important;
            height: 100% !important;
            border: none !important;
            background: transparent !important;
            color: var(--text-primary) !important;
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 0 center !important;
            background-size: 0.7rem !important;
            padding-right: 1.1rem !important;
        }

        .t-so-price-group {
            margin-top: 1rem !important;
            padding: 0 !important;
            width: calc(100% + 1.2rem) !important;
            margin-left: -0.6rem !important;
        }

        .t-so-price-group label {
            font-size: 0.72rem !important;
            color: var(--text-secondary) !important;
            margin-bottom: 1rem !important;
            display: block !important;
            padding-left: 0.5rem !important;
        }

        .t-so-price-group .price-slider-container {
            padding: 0 0.5rem !important;
        }

        .t-so-price-group .noUi-target {
            background: var(--border-glass) !important;
            height: 4px !important;
            border: none !important;
            box-shadow: none !important;
        }

        .t-so-price-group .noUi-connect {
            background: #0ea5e9 !important;
        }

        .t-so-price-group .noUi-handle {
            width: 18px !important;
            height: 18px !important;
            border-radius: 50% !important;
            border: 3px solid #fff !important;
            background: #0ea5e9 !important;
            box-shadow: 0 3px 8px rgba(14, 165, 233, 0.45) !important;
            cursor: pointer !important;
            top: -8px !important;
            right: -9px !important;
            transition: transform 0.15s ease !important;
        }

        .t-so-price-group .noUi-handle:active {
            transform: scale(1.2);
        }

        .t-so-price-group .noUi-handle::before,
        .t-so-price-group .noUi-handle::after {
            display: none !important;
        }

        .t-so-price-group .price-display {
            color: #0ea5e9 !important;
            font-family: 'Outfit', sans-serif !important;
            font-size: 0.88rem !important;
            margin-top: 1rem !important;
        }

        /* Actions */
        .t-so-actions {
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }

        .t-so-clear-btn {
            padding: 0.6rem 1rem;
            border-radius: 50px;
            border: 1px solid var(--border-glass);
            background: none;
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
            margin-left: auto;
            white-space: nowrap;
        }

        .t-so-clear-btn:hover {
            border-color: rgba(239, 68, 68, 0.5);
            color: #ef4444;
        }

        .t-so-apply-btn {
            padding: 0.65rem 1.6rem;
            border-radius: 50px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.22s ease;
            letter-spacing: 0.01em;
            box-shadow: 0 4px 18px rgba(14, 165, 233, 0.38);
            white-space: nowrap;
        }

        .t-so-apply-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 7px 26px rgba(14, 165, 233, 0.5);
        }

        /* Listing count badge */
        .listings-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 0.3rem 0.75rem;
            background: var(--bg-main);
            border: 1px solid var(--border-glass);
            border-radius: 50px;
        }

        /* Focus visible */
        :focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* ============================================================
           RESPONSIVE: TABLET ≤992px
           ============================================================ */
        @media (max-width: 992px) {
            body.page-listings {
                overflow: hidden !important;
            }

            .site-footer {
                display: none !important;
            }

            .navbar {
                justify-content: flex-start !important;
                gap: 0.4rem !important;
            }

            .nav-links>a,
            .nav-links>.theme-toggle,
            .nav-links>.btn-primary {
                display: none !important;
            }

            .nav-links {
                display: flex !important;
                width: auto !important;
                gap: 0.45rem !important;
                margin-left: auto !important;
                align-items: center !important;
                justify-content: flex-end !important;
                overflow: visible !important;
            }

            .t-logo {
                order: 1 !important;
                flex-shrink: 0 !important;
            }

            .t-nav-left {
                order: 2 !important;
                flex: 1 !important;
                min-width: 0 !important;
                max-width: none !important;
                height: 34px !important;
                padding: 0 0 0 0.85rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                background: var(--bg-main) !important;
                border-radius: 50px !important;
                overflow: hidden !important;
            }

            .nav-links {
                order: 5 !important;
                flex-shrink: 0 !important;
                margin-left: 0 !important;
            }

            .t-nav-pill-icon {
                width: 34px !important;
                height: 34px !important;
                background: #0ea5e9 !important;
                color: white !important;
                border-radius: 50% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 0.98rem !important;
                box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3) !important;
                border: 1.5px solid var(--border-glass) !important;
            }

            .t-logo-words {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0 !important;
            }

            .t-logo-top,
            .t-logo-bot {
                font-size: 0.82rem !important;
                line-height: 1 !important;
            }

            .t-logo-bot {
                margin-top: -1px !important;
            }

            .t-hamburger {
                display: flex !important;
                order: 4 !important;
            }

            .main-container {
                display: flex !important;
                flex-direction: column;
                height: calc(100vh - 120px);
                margin-top: var(--t-header-h, 95px) !important;
                height: calc(100svh - var(--t-header-h, 95px)) !important;
                overflow: hidden;
                position: relative;
            }

            .main-container.split-mode {
                height: calc(100dvh - var(--t-header-h, 95px)) !important;
                margin-top: var(--t-header-h, 95px) !important;
            }

            .listings-section {
                max-width: 100%;
                width: 100%;
                height: 100%;
                padding: 0 !important;
                padding-bottom: 0.01rem !important;
                max-width: 100% !important;
                width: 100% !important;
                position: relative;
                overflow: hidden;
            }

            .listings-scroll-container {
                padding: 0.5rem !important;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                touch-action: pan-y;
            }

            .listings-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem !important;
            }

            .map-section {
                display: none;
                width: 100%;
                border-left: none;
            }

            .map-section.mobile-map-active {
                display: block !important;
                height: 100% !important;
                flex: 1 !important;
            }

            .listings-section.mobile-hidden {
                display: none !important;
            }

            .main-container.split-mode .map-section {
                display: block !important;
                order: -1;
                height: 40vh !important;
                flex: none;
                border-bottom: 2px solid var(--border-glass);
            }

            .main-container.split-mode .listings-section {
                display: block !important;
                flex: 1;
                overflow-y: auto;
            }

            body.mobile-map-only .site-footer {
                display: none !important;
            }

            .mobile-view-toggle {
                display: flex;
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: var(--bg-card);
                padding: 6px;
                border-radius: 40px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
                border: 1px solid var(--border-glass);
                z-index: 2500;
                gap: 5px;
            }

            .property-card {
                border-radius: 10px !important;
            }

            .pc-top-row {
                top: 8px !important;
                left: 8px !important;
                right: 8px !important;
            }

            .pc-type-badge {
                font-size: 0.6rem !important;
                padding: 0.22rem 0.5rem !important;
                gap: 3px !important;
            }

            .pc-mode-badge {
                font-size: 0.6rem !important;
                padding: 0.22rem 0.5rem !important;
            }

            .pc-price {
                font-size: 0.78rem !important;
                padding: 0.2rem 0.55rem !important;
            }

            .pc-stats-overlay {
                bottom: 30px !important;
                left: 16px !important;
                gap: 5px !important;
            }

            .pc-stats-overlay span {
                font-size: 0.66rem !important;
                gap: 3px !important;
            }

            .pc-stats-overlay span i {
                font-size: 0.6rem !important;
            }

            .property-info {
                padding: 0.5rem 0.5rem 0.45rem !important;
                min-height: unset !important;
                gap: 0.2rem !important;
            }

            .property-title {
                font-size: 0.76rem !important;
                line-height: 1.3 !important;
                min-height: unset !important;
                margin-bottom: 0.18rem !important;
            }

            .property-location {
                font-size: 0.64rem !important;
                gap: 4px !important;
                margin-bottom: 0 !important;
            }

            .property-location i {
                font-size: 0.6rem !important;
            }

            .t-search-overlay {
                padding-top: clamp(50px, 8vh, 80px);
            }

            .t-so-panel {
                padding: 1.1rem;
                border-radius: 18px;
            }

            .t-so-input {
                font-size: 0.92rem !important;
                padding: 0.82rem 1rem 0.82rem 2.8rem !important;
            }

            .t-so-typewriter {
                font-size: 0.92rem;
                left: 2.8rem;
            }

            .t-so-search-icon {
                left: 0.9rem;
                font-size: 0.88rem;
            }

            .t-so-chips-label {
                display: none;
            }

            .t-so-filters-grid {
                grid-template-columns: 1fr 1fr 1fr !important;
            }

            .t-so-actions {
                flex-wrap: wrap;
            }

            .t-so-clear-btn {
                margin-left: 0;
                order: -1;
            }

            .card-slider-wrapper {
                touch-action: pan-x;
            }
        }

        /* ============================================================
           RESPONSIVE: MOBILE ≤768px
           ============================================================ */
        @media (max-width: 768px) {
            .listings-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.6rem;
            }
        }

        /* ============================================================
           RESPONSIVE: SMALL MOBILE ≤480px
           ============================================================ */
        @media (max-width: 480px) {
            .navbar {
                padding: 0.45rem 0.55rem !important;
                gap: 0.3rem !important;
            }

            .t-logo-top,
            .t-logo-bot {
                font-size: 0.75rem !important;
                line-height: 1.05 !important;
            }

            .t-logo-icon {
                font-size: 1.1rem !important;
            }

            .listings-scroll-container {
                padding: 0.4rem !important;
            }

            .listings-grid {
                gap: 0.4rem !important;
            }

            .t-nav-pill-tw {
                font-size: 0.74rem !important;
            }

            .t-nav-pill-icon {
                font-size: 0.7rem !important;
            }

            .property-info {
                padding: 0.42rem 0.45rem 0.4rem !important;
            }

            .property-title {
                font-size: 0.72rem !important;
            }

            .property-location {
                font-size: 0.6rem !important;
            }

            .pc-price {
                font-size: 0.72rem !important;
                padding: 0.18rem 0.45rem !important;
            }

            .pc-stats-overlay {
                bottom: 28px !important;
                left: 15px !important;
            }

            .pc-bottom-row {
                padding: 0 7px !important;
                bottom: 6px !important;
            }

            .pc-top-row {
                top: 6px !important;
                left: 6px !important;
                right: 6px !important;
            }

            .pc-type-badge {
                font-size: 0.56rem !important;
                padding: 0.18rem 0.4rem !important;
            }

            .pc-mode-badge {
                font-size: 0.56rem !important;
                padding: 0.18rem 0.4rem !important;
            }

            .mvt-btn span {
                display: none;
            }

            .mvt-btn i {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body class="page-listings">
    <header class="site-header">
        <nav class="navbar">
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

            <button class="t-nav-filter-btn" id="t-nav-filter-btn" aria-label="Open filters" title="Search & Filters">
                <i class="fa-solid fa-sliders"></i>
            </button>

            <div class="nav-links">
                <button id="theme-toggle" class="theme-toggle" title="Toggle Dark/Light Mode">
                    <i class="fa-solid fa-moon"></i><i class="fa-solid fa-sun" style="display:none"></i>
                </button>
                <a href="index.php" class="active">Explore</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?><a href="admin.php">Admin</a>
                    <?php endif; ?>
                    <a href="list-apartment.php" class="btn-primary">List Property</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="t-active-bar" id="t-active-bar" aria-label="Active filters">
            <div class="t-active-pills" id="t-active-pills"></div>
            <button class="t-active-clear" id="t-active-clear" aria-label="Clear all filters" title="Clear all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </header>

    <!-- Mobile Nav Overlay + Drawer -->
    <div class="t-overlay" id="t-overlay"></div>
    <aside class="t-drawer" id="t-drawer" aria-label="Navigation menu">
        <div class="t-drawer-head">
            <a href="index.php" class="t-logo" style="text-decoration:none;">
                <i class="fa-solid fa-house-chimney-window t-logo-icon"></i>
                <span class="t-logo-words">
                    <span class="t-logo-top">MyHome</span>
                    <span class="t-logo-bot">MyLand</span>
                </span>
            </a>
            <button class="t-close-btn" id="t-drawer-close" aria-label="Close menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="t-drawer-body">
            <a href="index.php" class="t-nav-link"><span class="t-nav-icon"><i
                        class="fa-solid fa-compass"></i></span>Explore</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="t-nav-link"><span class="t-nav-icon"><i
                            class="fa-solid fa-gauge"></i></span>Dashboard</a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin.php" class="t-nav-link"><span class="t-nav-icon"><i
                                class="fa-solid fa-shield"></i></span>Admin</a>
                <?php endif; ?>
                <div class="t-drawer-sep"></div>
                <a href="list-apartment.php" class="t-nav-link is-cta"><i class="fa-solid fa-plus"></i> List Property</a>
                <a href="logout.php" class="t-nav-link"><span class="t-nav-icon"><i
                            class="fa-solid fa-right-from-bracket"></i></span>Logout</a>
            <?php else: ?>
                <div class="t-drawer-sep"></div>
                <a href="login.php" class="t-nav-link"><span class="t-nav-icon"><i
                            class="fa-solid fa-right-to-bracket"></i></span>Login</a>
                <a href="register.php" class="t-nav-link is-cta"><i class="fa-solid fa-user-plus"></i> Sign Up</a>
            <?php endif; ?>
        </div>
        <div class="t-drawer-foot">©
            <?= date('Y') ?> MyHomeMyLand.LK
        </div>
    </aside>

    <!-- Desktop Visible Filter Bar -->
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

    <!-- Mobile View Toggle Pill -->
    <div class="mobile-view-toggle" id="mobile-view-toggle">
        <button class="mvt-btn" id="mvt-list" data-mode="list"><i
                class="fa-solid fa-list"></i><span>List</span></button>
        <button class="mvt-btn" id="mvt-split" data-mode="split"><i
                class="fa-solid fa-table-columns"></i><span>Split</span></button>
        <button class="mvt-btn mvt-active" id="mvt-map" data-mode="map"><i
                class="fa-solid fa-map-location-dot"></i><span>Map</span></button>
    </div>

    <!-- Hidden inputs for JS price sync -->
    <input type="hidden" id="filter-min-price">
    <input type="hidden" id="filter-max-price">

    <!-- Search Overlay -->
    <div class="t-search-overlay" id="t-search-overlay" aria-hidden="true">
        <div class="t-so-backdrop" id="t-so-backdrop"></div>
        <div class="t-so-panel">
            <div class="t-so-head">
                <div class="t-mode-pill">
                    <button class="mode-btn mode-active" data-mode="Rent">Rent</button>
                    <button class="mode-btn" data-mode="Buy">Buy</button>
                </div>
                <button class="t-so-close" id="t-search-close" aria-label="Close search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="t-so-input-wrap">
                <i class="fa-solid fa-magnifying-glass t-so-search-icon"></i>
                <input type="text" id="search-text" class="t-so-input" autocomplete="off" spellcheck="false"
                    aria-label="Search properties">
                <div class="t-so-typewriter" id="t-so-typewriter" aria-hidden="true">
                    <span id="t-tw-text"></span><span class="t-tw-cursor">|</span>
                </div>
            </div>

            <select id="filter-min-price-select" style="display:none;">
                <option value="">Min</option>
                <option value="10000">Rs.10k</option>
                <option value="25000">Rs.25k</option>
                <option value="50000">Rs.50k</option>
                <option value="100000">Rs.100k</option>
                <option value="250000">Rs.250k</option>
                <option value="500000">Rs.500k</option>
            </select>
            <select id="filter-max-price-select" style="display:none;">
                <option value="">Max</option>
                <option value="50000">Rs.50k</option>
                <option value="100000">Rs.100k</option>
                <option value="250000">Rs.250k</option>
                <option value="500000">Rs.500k</option>
                <option value="750000">Rs.750k</option>
                <option value="1000000">Rs.1M+</option>
            </select>

            <div class="t-so-chips">
                <span class="t-so-chips-label">Quick Search:</span>
                <button class="t-chip" data-q="Colombo"><i class="fa-solid fa-location-dot"></i> Colombo</button>
                <button class="t-chip" data-q="Apartment"><i class="fa-solid fa-building"></i> Apartment</button>
                <button class="t-chip" data-q="House"><i class="fa-solid fa-house"></i> House</button>
                <button class="t-chip" data-q="Villa"><i class="fa-solid fa-house-chimney-window"></i> Villa</button>
                <button class="t-chip" data-q="Galle"><i class="fa-solid fa-location-dot"></i> Galle</button>
                <button class="t-chip" data-q="Kandy"><i class="fa-solid fa-location-dot"></i> Kandy</button>
            </div>

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
                <div class="t-so-filter-group t-so-price-group">
                    <label>Price Range</label>
                    <div class="price-slider-container">
                        <div id="price-slider"></div>
                        <div id="price-range-display" class="price-display">Rs. 0 - Rs. 500k</div>
                    </div>
                </div>
            </div>

            <div class="t-so-actions">
                <button id="clear-filters" class="t-so-clear-btn">
                    <i class="fa-solid fa-rotate-left"></i> Clear
                </button>
                <button id="apply-filters" class="t-so-apply-btn">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.js"></script>

    <script>
        /* ================================================================
           INLINE SCRIPT — index.php (isolated from script.js + terminal.js)
           ================================================================ */
        document.addEventListener('DOMContentLoaded', () => {
            let currentMode = 'Rent';

            /* ── Theme Toggle ── */
            const themeBtn = document.getElementById('theme-toggle');
            const currentTheme = localStorage.getItem('theme') || 'light';
            if (currentTheme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
            if (themeBtn) {
                themeBtn.addEventListener('click', () => {
                    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                    if (isDark) {
                        document.documentElement.removeAttribute('data-theme');
                        localStorage.setItem('theme', 'light');
                    } else {
                        document.documentElement.setAttribute('data-theme', 'dark');
                        localStorage.setItem('theme', 'dark');
                    }
                });
            }

            /* ── Header scroll class ── */
            const siteHeader = document.querySelector('.site-header');
            window.addEventListener('scroll', () => {
                if (siteHeader) siteHeader.classList.toggle('t-scrolled', window.scrollY > 10);
            }, { passive: true });

            /* ── Header height tracking ── */
            const mainContainer = document.querySelector('.main-container');
            function updateHeaderHeight() {
                if (siteHeader && mainContainer) {
                    mainContainer.style.setProperty('--t-header-h', siteHeader.offsetHeight + 'px');
                }
            }
            updateHeaderHeight();
            window.addEventListener('resize', updateHeaderHeight);
            if (typeof ResizeObserver !== 'undefined' && siteHeader) {
                new ResizeObserver(updateHeaderHeight).observe(siteHeader);
            }

            /* ── Hamburger / Drawer ── */
            const hamburger = document.querySelector('.t-hamburger');
            const drawer = document.getElementById('t-drawer');
            const overlay = document.getElementById('t-overlay');
            const drawerClose = document.getElementById('t-drawer-close');

            function openDrawer() { drawer?.classList.add('is-open'); overlay?.classList.add('is-visible'); document.body.style.overflow = 'hidden'; }
            function closeDrawer() { drawer?.classList.remove('is-open'); overlay?.classList.remove('is-visible'); document.body.style.overflow = ''; }

            hamburger?.addEventListener('click', openDrawer);
            drawerClose?.addEventListener('click', closeDrawer);
            overlay?.addEventListener('click', closeDrawer);

            /* ── Typewriter animation ── */
            const phrases = ['Search Colombo apartments…', 'Find a villa in Galle…', 'Houses near Kandy…', 'Land for sale in Dehiwala…', 'Studio in Kotte…'];
            let tIdx = 0, tChar = 0, tDeleting = false;

            const twNavEl = document.getElementById('t-nav-tw-text');
            const twSoEl = document.getElementById('t-tw-text');
            const twSoWrap = document.getElementById('t-so-typewriter');

            function typewriterTick() {
                const phrase = phrases[tIdx];
                if (!tDeleting) {
                    tChar++;
                    if (tChar > phrase.length) { tDeleting = true; setTimeout(typewriterTick, 1800); return; }
                } else {
                    tChar--;
                    if (tChar < 0) { tChar = 0; tDeleting = false; tIdx = (tIdx + 1) % phrases.length; setTimeout(typewriterTick, 400); return; }
                }
                const text = phrase.slice(0, tChar);
                if (twNavEl) twNavEl.textContent = text;
                if (twSoEl) twSoEl.textContent = text;
                setTimeout(typewriterTick, tDeleting ? 45 : 80);
            }
            setTimeout(typewriterTick, 600);

            /* Hide typewriter overlay when input has value */
            const soInput = document.getElementById('search-text');
            soInput?.addEventListener('input', () => {
                if (twSoWrap) twSoWrap.classList.toggle('is-hidden', soInput.value.length > 0);
            });

            /* ── Search Overlay open/close ── */
            const searchOverlay = document.getElementById('t-search-overlay');
            const searchClose = document.getElementById('t-search-close');
            const soBackdrop = document.getElementById('t-so-backdrop');
            const navPill = document.getElementById('t-nav-pill');
            const navFilterBtn = document.getElementById('t-nav-filter-btn');

            function openSearchOverlay() {
                searchOverlay?.classList.add('is-open');
                searchOverlay?.removeAttribute('aria-hidden');
                setTimeout(() => soInput?.focus(), 300);
            }
            function closeSearchOverlay() {
                searchOverlay?.classList.remove('is-open');
                searchOverlay?.setAttribute('aria-hidden', 'true');
            }

            navPill?.addEventListener('click', openSearchOverlay);
            navPill?.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openSearchOverlay(); } });
            navFilterBtn?.addEventListener('click', openSearchOverlay);
            searchClose?.addEventListener('click', closeSearchOverlay);
            soBackdrop?.addEventListener('click', closeSearchOverlay);
            document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSearchOverlay(); });

            /* Quick chips */
            document.querySelectorAll('.t-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    document.querySelectorAll('.t-chip').forEach(c => c.classList.remove('is-active'));
                    chip.classList.add('is-active');
                    const q = chip.dataset.q || '';
                    if (soInput) { soInput.value = q; if (twSoWrap) twSoWrap.classList.add('is-hidden'); }
                    closeSearchOverlay();
                    fetchListings();
                });
            });

            /* ── Active filters pills (mobile) ── */
            const activeBar = document.getElementById('t-active-bar');
            const activePills = document.getElementById('t-active-pills');
            const activeClear = document.getElementById('t-active-clear');

            function renderActivePills() {
                if (!activePills) return;
                activePills.innerHTML = '';
                const filters = [
                    { id: 'search-text', label: () => { const v = soInput?.value; return v ? `"${v}"` : null; } },
                    { id: 'filter-type-mobile', label: v => v !== 'All' ? v : null },
                    { id: 'filter-location-mobile', label: v => v !== 'All' ? v : null },
                    { id: 'filter-beds-mobile', label: v => v !== 'All' ? `${v} Bed` : null },
                    { id: 'filter-baths-mobile', label: v => v !== 'All' ? `${v} Bath` : null },
                    { id: 'dfb-search-text', label: v => v ? `"${v}"` : null },
                    { id: 'dfb-filter-type', label: v => v !== 'All' ? v : null },
                    { id: 'dfb-filter-location', label: v => v !== 'All' ? v : null },
                    { id: 'dfb-filter-beds', label: v => v !== 'All' ? `${v} Bed` : null },
                ];
                const seen = new Set();
                filters.forEach(f => {
                    const el = document.getElementById(f.id);
                    if (!el) return;
                    const raw = el.value;
                    const label = typeof f.label === 'function' ? f.label(raw) : null;
                    if (!label || seen.has(label)) return;
                    seen.add(label);
                    const pill = document.createElement('div');
                    pill.className = 't-active-pill';
                    pill.innerHTML = `${label}<button class="t-apill-remove" data-filter-id="${f.id}" aria-label="Remove ${label}"><i class="fa-solid fa-xmark"></i></button>`;
                    activePills.appendChild(pill);
                });
                if (activeBar) activeBar.classList.toggle('has-filters', activePills.children.length > 0);
            }

            activePills?.addEventListener('click', e => {
                const btn = e.target.closest('.t-apill-remove');
                if (!btn) return;
                const el = document.getElementById(btn.dataset.filterId);
                if (el) { el.value = el.tagName === 'INPUT' ? '' : 'All'; }
                renderActivePills(); fetchListings();
            });
            activeClear?.addEventListener('click', () => { clearAll(); renderActivePills(); });

            /* ── Helper: read filter value ── */
            function getVal(desktopId, mobileId, dfbId) {
                const desk = document.getElementById(desktopId);
                const mob = mobileId ? document.getElementById(mobileId) : null;
                const dfb = dfbId ? document.getElementById(dfbId) : null;
                if (dfb && dfb.offsetParent !== null) return dfb.value || '';
                if (desk && desk.offsetParent !== null) return desk.value || '';
                if (mob && mob.offsetParent !== null) return mob.value || '';
                if (mob && mob.value) return mob.value;
                if (desk && desk.value) return desk.value;
                if (dfb && dfb.value) return dfb.value;
                return '';
            }

            /* ── Map Initialization ── */
            let map, markersLayer, flyInCompleted = false, flyInRunning = false;
            const mapElement = document.getElementById('map');

            const formatPriceShort = (price) => {
                const p = Number(price);
                if (isNaN(p)) return '0';
                if (p >= 1000000) return (p / 1000000).toFixed(2).replace(/\.?0+$/, '') + 'M';
                return p.toLocaleString();
            };

            if (mapElement && typeof L !== 'undefined') {
                map = L.map('map', { center: [20, 0], zoom: 2, minZoom: 2, attributionControl: false, zoomControl: true, scrollWheelZoom: true });
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 18, minZoom: 2 }).addTo(map);
                markersLayer = L.layerGroup().addTo(map);

                /* Brand overlay */
                const overlayStyles = document.createElement('style');
                overlayStyles.textContent = `
                .map-brand-overlay{position:absolute;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none}
                .map-brand-overlay .brand-text{font-family:'Outfit',sans-serif;font-size:clamp(1.6rem,3.5vw,2.8rem);font-weight:800;letter-spacing:4px;color:var(--primary,#0ea5e9);opacity:0;animation:brandIn 1.2s ease 0.1s both}
                .map-brand-overlay.brand-exit .brand-text{animation:brandOut 0.8s ease forwards}
                .map-brand-overlay.brand-exit{animation:overlayOut 0.8s ease 0.5s forwards}
                @keyframes brandIn{from{opacity:0}to{opacity:1}}
                @keyframes brandOut{from{opacity:1}to{opacity:0}}
                @keyframes overlayOut{from{opacity:1}to{opacity:0}}
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
                            if (isMobile()) setTimeout(() => applyMobileMode('split'), 500);
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
                        for (const e of entries)
                            if (e.contentRect.width > 0 && e.contentRect.height > 0 && !flyInCompleted && !flyInRunning) {
                                map.invalidateSize(); setTimeout(attemptFlyIn, 300);
                            }
                    }).observe(mapElement);
                }
            }

            /* ── Mobile View Toggle ── */
            const mapSection = document.getElementById('map-section');
            const listingsSection = document.getElementById('listings-section');
            const mvtButtons = document.querySelectorAll('.mvt-btn');
            const isMobile = () => window.innerWidth <= 992;

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
            if (isMobile()) applyMobileMode(localStorage.getItem('mobileViewMode') || 'map');

            window.addEventListener('resize', () => {
                updateHeaderHeight();
                if (!isMobile()) {
                    mainContainer?.classList.remove('split-mode');
                    mapSection?.classList.remove('mobile-map-active');
                    listingsSection?.classList.remove('mobile-hidden');
                    if (map) setTimeout(() => map.invalidateSize(), 200);
                }
            });

            /* ── Price Slider ── */
            const priceSlider = document.getElementById('price-slider');
            const minPriceInput = document.getElementById('filter-min-price');
            const maxPriceInput = document.getElementById('filter-max-price');
            const priceDisplay = document.getElementById('price-range-display');
            const RENT_MAX = 1000000, BUY_MAX = 200000000;

            function updatePriceUI(mode) {
                if (!priceSlider || !priceSlider.noUiSlider) return;
                const isBuy = mode === 'Buy';
                const newMax = isBuy ? BUY_MAX : RENT_MAX;
                priceSlider.noUiSlider.updateOptions({ range: { 'min': 0, 'max': newMax }, step: isBuy ? 100000 : 5000 });
                priceSlider.noUiSlider.set([0, newMax]);

                const minSel = document.getElementById('filter-min-price-select');
                const maxSel = document.getElementById('filter-max-price-select');
                if (minSel && maxSel) {
                    const optsMin = isBuy
                        ? [['', 'Min Price'], ['1000000', 'Rs. 1M'], ['5000000', 'Rs. 5M'], ['10000000', 'Rs. 10M'], ['25000000', 'Rs. 25M'], ['50000000', 'Rs. 50M'], ['100000000', 'Rs. 100M']]
                        : [['', 'Min Price'], ['10000', 'Rs. 10k'], ['25000', 'Rs. 25k'], ['50000', 'Rs. 50k'], ['100000', 'Rs. 100k'], ['250000', 'Rs. 250k'], ['500000', 'Rs. 500k']];
                    const optsMax = isBuy
                        ? [['', 'Max Price'], ['5000000', 'Rs. 5M'], ['10000000', 'Rs. 10M'], ['25000000', 'Rs. 25M'], ['50000000', 'Rs. 50M'], ['100000000', 'Rs. 100M'], [BUY_MAX, 'Rs. 200M+']]
                        : [['', 'Max Price'], ['50000', 'Rs. 50k'], ['100000', 'Rs. 100k'], ['250000', 'Rs. 250k'], ['500000', 'Rs. 500k'], ['750000', 'Rs. 750k'], [RENT_MAX, 'Rs. 1M+']];
                    minSel.innerHTML = optsMin.map(([v, t]) => `<option value="${v}">${t}</option>`).join('');
                    maxSel.innerHTML = optsMax.map(([v, t]) => `<option value="${v}">${t}</option>`).join('');
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
                    const maxD = (maxVal >= currentMax) ? (currentMax >= 1000000 ? (currentMax / 1000000) + 'M+' : '1M+') : maxVal.toLocaleString();
                    if (priceDisplay) priceDisplay.innerText = 'Rs. ' + minD + ' - Rs. ' + maxD;
                });
                priceSlider.noUiSlider.on('change', () => fetchListings());
            }

            /* ── Buy/Rent Toggle ── */
            document.querySelectorAll('.mode-btn').forEach(btn => {
                btn.addEventListener('click', e => {
                    currentMode = e.target.dataset.mode;
                    document.querySelectorAll('.mode-btn').forEach(b => b.classList.toggle('mode-active', b.dataset.mode === currentMode));
                    updatePriceUI(currentMode);
                    fetchListings();
                });
            });

            /* ── Card slider helpers ── */
            const grid = document.getElementById('listings-grid');
            let _moved = false;

            function _updateCardSlider(wrapper) {
                const s = parseInt(wrapper.dataset.slide) || 0;
                const w = wrapper.offsetWidth;
                if (w > 0) wrapper.scrollTo({ left: s * w, behavior: 'smooth' });
                const card = wrapper.closest('.property-card');
                if (card) card.querySelectorAll('.card-dot').forEach((d, i) => d.classList.toggle('active', i === s));
            }

            if (grid) {
                /* Native scroll → update dots */
                grid.addEventListener('scroll', e => {
                    const wrapper = e.target.closest('.card-slider-wrapper');
                    if (!wrapper) return;
                    const index = Math.round(wrapper.scrollLeft / wrapper.offsetWidth);
                    if (parseInt(wrapper.dataset.slide) !== index) {
                        wrapper.dataset.slide = index;
                        const card = wrapper.closest('.property-card');
                        if (card) card.querySelectorAll('.card-dot').forEach((d, i) => d.classList.toggle('active', i === index));
                    }
                }, true);

                /* Touch detection */
                let _startX = 0;
                grid.addEventListener('touchstart', e => { _startX = e.touches[0].clientX; _moved = false; }, { passive: true });
                grid.addEventListener('touchmove', e => { if (Math.abs(e.touches[0].clientX - _startX) > 10) _moved = true; }, { passive: true });
                grid.addEventListener('touchend', () => { setTimeout(() => { _moved = false; }, 50); });

                /* Mouse drag */
                let _isMouseDown = false, _scrollStartX, _scrollLeftInitial, _startY = 0;
                grid.addEventListener('mousedown', e => {
                    const wrapper = e.target.closest('.card-slider-wrapper');
                    if (!wrapper) return;
                    _isMouseDown = true; _startX = e.clientX; _startY = e.clientY;
                    _scrollStartX = e.pageX - wrapper.offsetLeft;
                    _scrollLeftInitial = wrapper.scrollLeft;
                    wrapper.style.scrollSnapType = 'none';
                    wrapper.style.scrollBehavior = 'auto';
                });
                window.addEventListener('mousemove', e => {
                    if (!_isMouseDown) return;
                    const wrapper = Array.from(document.querySelectorAll('.card-slider-wrapper')).find(w => w.contains(e.target)) || document.querySelector('.card-slider-wrapper:hover');
                    if (!wrapper) return;
                    const x = e.pageX - wrapper.offsetLeft;
                    wrapper.scrollLeft = _scrollLeftInitial - (x - _scrollStartX) * 1.5;
                });
                window.addEventListener('mouseup', () => {
                    if (!_isMouseDown) return;
                    _isMouseDown = false;
                    document.querySelectorAll('.card-slider-wrapper').forEach(w => {
                        w.style.scrollSnapType = 'x mandatory';
                        w.style.scrollBehavior = 'smooth';
                    });
                });

                /* Arrow buttons (delegated) */
                grid.addEventListener('click', e => {
                    const nextBtn = e.target.closest('.card-slider-next');
                    const prevBtn = e.target.closest('.card-slider-prev');
                    if (nextBtn || prevBtn) {
                        e.stopPropagation(); e.preventDefault();
                        const wrapper = e.target.closest('.property-card')?.querySelector('.card-slider-wrapper');
                        if (wrapper) {
                            const count = parseInt(wrapper.dataset.count) || 1;
                            if (count > 1) {
                                let s = parseInt(wrapper.dataset.slide) || 0;
                                s = nextBtn ? (s + 1) % count : (s - 1 + count) % count;
                                wrapper.dataset.slide = s;
                                _updateCardSlider(wrapper);
                            }
                        }
                    }
                });
            }

            /* ── escapeHTML ── */
            function escapeHTML(str) {
                if (str === null || str === undefined) return '';
                return new Option(str).innerHTML;
            }

            /* ── fetchListings ── */
            let _fetchRetried = false;
            const fetchListings = async () => {
                try {
                    const search = getVal('search-text', null, 'dfb-search-text');
                    const type = getVal('filter-type', 'filter-type-mobile', 'dfb-filter-type');
                    const location = getVal('filter-location', 'filter-location-mobile', 'dfb-filter-location');
                    const beds = getVal('filter-beds', 'filter-beds-mobile', 'dfb-filter-beds');
                    const baths = getVal('filter-baths', 'filter-baths-mobile');
                    const min_price = minPriceInput?.value || '';
                    const max_price = maxPriceInput?.value || '';
                    const sort = getVal('filter-sort', 'filter-sort-mobile');
                    const listing_mode = currentMode;

                    const params = new URLSearchParams({ search, type, location, beds, baths, min_price, max_price, sort, listing_mode });

                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 20000);
                    const res = await fetch('api/get_apartments.php?' + params.toString(), { signal: controller.signal });
                    clearTimeout(timeoutId);

                    if (!res.ok) {
                        const errText = await res.text();
                        throw new Error(`Server error: ${res.status}. ${errText.substring(0, 100)}`);
                    }

                    const data = await res.json();
                    if (!Array.isArray(data)) throw new Error(data.error || 'Unexpected API response format');
                    _fetchRetried = false;

                    if (grid) {
                        grid.innerHTML = '';
                        if (data.length === 0) {
                            grid.innerHTML = '<p style="grid-column:span 2;padding:2rem;">No properties match your search.</p>';
                        } else {
                            data.forEach(prop => {
                                const card = document.createElement('div');
                                card.className = 'property-card';

                                const typeIcons = { Apartment: 'fa-building', House: 'fa-house', Villa: 'fa-house-chimney-window', Commercial: 'fa-store', Land: 'fa-tree' };
                                const typeIconClass = typeIcons[prop.type] || 'fa-building';

                                const defaults = { Land: 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&q=80&w=800', House: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&q=80&w=800', Villa: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&q=80&w=800' };
                                const defaultImg = defaults[prop.type] || 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=800';

                                let imgs = [defaultImg];
                                try { const p = JSON.parse(prop.images); if (p && p.length > 0) imgs = p; } catch (e) { }

                                const imagesHtml = imgs.map(img => `<img src="${img}" alt="${escapeHTML(prop.title)}" class="property-img card-img-slide" loading="lazy">`).join('');
                                const dotsHtml = imgs.length > 1 ? imgs.map((_, i) => `<div class="card-dot${i === 0 ? ' active' : ''}"></div>`).join('') : '';
                                const dotsBlock = imgs.length > 1 ? `<div class="card-slider-dots">${dotsHtml}</div>` : '';
                                const arrowsHtml = imgs.length > 1 ? '<button class="card-slider-nav card-slider-prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button><button class="card-slider-nav card-slider-next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>' : '';

                                const modeText = prop.listing_mode || currentMode;
                                const modeClass = modeText === 'Rent' ? 'pc-mode-rent' : 'pc-mode-buy';
                                const period = modeText === 'Rent' ? '<small class="pc-price-per">/mo</small>' : '';
                                const priceFormatted = 'Rs.\u00a0' + formatPriceShort(prop.price);
                                const views = prop.view_count || 0;

                                let statsOverlayHtml;
                                if (prop.type === 'Land') {
                                    statsOverlayHtml = `<span><i class="fa-solid fa-ruler-combined"></i> ${prop.size_perches || '—'} P</span><span><i class="fa-solid fa-eye"></i> ${views}</span>`;
                                } else {
                                    statsOverlayHtml = `<span><i class="fa-solid fa-bed"></i> ${prop.bedrooms || '—'}</span><span><i class="fa-solid fa-bath"></i> ${prop.baths || '—'}</span><span><i class="fa-solid fa-eye"></i> ${views}</span>`;
                                }

                                card.innerHTML =
                                    `<div class="img-container">` +
                                    `<div class="card-slider-wrapper">${imagesHtml}</div>` +
                                    `<div class="pc-img-overlay"></div>` +
                                    `<div class="pc-top-row"><span class="pc-type-badge"><i class="fa-solid ${typeIconClass}"></i>&nbsp;${escapeHTML(prop.type)}</span><span class="pc-mode-badge ${modeClass}">${modeText}</span></div>` +
                                    `<div class="pc-stats-overlay">${statsOverlayHtml}</div>` +
                                    `<div class="pc-bottom-row"><div class="pc-price">${priceFormatted}${period}</div>${dotsBlock}</div>` +
                                    arrowsHtml +
                                    `</div>` +
                                    `<div class="property-info">` +
                                    `<h3 class="property-title">${escapeHTML(prop.title)}</h3>` +
                                    `<div class="property-location"><i class="fa-solid fa-location-dot"></i><span>${escapeHTML(prop.address)}</span></div>` +
                                    `</div>`;

                                if (imgs.length > 1) {
                                    const wrapper = card.querySelector('.card-slider-wrapper');
                                    wrapper.dataset.slide = '0';
                                    wrapper.dataset.count = imgs.length;
                                }

                                card.dataset.propId = prop.id;
                                card.style.cursor = 'pointer';
                                card.addEventListener('click', e => {
                                    if (_moved) { e.preventDefault(); return; }
                                    window.location.href = 'apartment.php?id=' + prop.id;
                                });
                                grid.appendChild(card);
                            });
                        }
                    }

                    /* Map markers */
                    if (mapElement && markersLayer) {
                        markersLayer.clearLayers();
                        data.forEach(prop => {
                            const priceIcon = L.divIcon({ className: 'custom-price-marker-wrapper', html: `<div class="price-marker-label">Rs. ${escapeHTML(formatPriceShort(prop.price))}</div>`, iconSize: [80, 24], iconAnchor: [40, 24] });
                            const marker = L.marker([prop.lat, prop.lng], { icon: priceIcon }).addTo(markersLayer);
                            let popupImg = 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&q=80&w=800';
                            try { const pi = JSON.parse(prop.images); if (pi && pi.length > 0) popupImg = pi[0]; } catch (e) { }
                            marker.bindPopup(`<div style="cursor:pointer;" onclick="window.location.href='apartment.php?id=${prop.id}'"><img src="${popupImg}" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:0.5rem;"><h4 style="margin:0;font-size:1rem;">${prop.title}</h4><p style="margin:0;color:var(--primary);font-weight:bold;">Rs. ${formatPriceShort(prop.price)}</p><span style="font-size:0.8rem;color:var(--text-secondary);">${prop.type} | ${prop.bedrooms} Bed | ${prop.baths} Bath</span><div style="margin-top:0.5rem;"><span style="color:var(--primary);font-size:0.85rem;font-weight:500;">View Details →</span></div></div>`, { closeButton: true, minWidth: 220 });
                        });
                    }

                    renderActivePills();

                } catch (e) {
                    console.error('Fetch error:', e);
                    if (!_fetchRetried) {
                        _fetchRetried = true;
                        if (grid) grid.innerHTML = '<p>Loading properties...</p>';
                        setTimeout(fetchListings, 1200);
                    } else {
                        _fetchRetried = false;
                        if (grid) grid.innerHTML = '<p style="color:red;grid-column:span 2;">Failed to load properties. Please check your connection.</p>';
                    }
                }
            };

            if (grid) fetchListings();

            /* ── clearAll ── */
            function clearAll() {
                ['search-text', 'dfb-search-text'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                ['filter-type-mobile', 'dfb-filter-type', 'filter-location-mobile', 'dfb-filter-location', 'filter-beds-mobile', 'dfb-filter-beds', 'filter-baths-mobile'].forEach(id => { const el = document.getElementById(id); if (el) el.value = 'All'; });
                ['filter-sort-mobile'].forEach(id => { const el = document.getElementById(id); if (el) el.value = 'newest'; });
                const minSel = document.getElementById('filter-min-price-select');
                const maxSel = document.getElementById('filter-max-price-select');
                if (minSel) minSel.value = '';
                if (maxSel) maxSel.value = '';
                if (minPriceInput) minPriceInput.value = '';
                if (maxPriceInput) maxPriceInput.value = '';
                if (priceSlider && priceSlider.noUiSlider) priceSlider.noUiSlider.set([0, currentMode === 'Buy' ? BUY_MAX : RENT_MAX]);
                if (twSoWrap) twSoWrap.classList.remove('is-hidden');
                renderActivePills();
                fetchListings();
            }

            /* ── Wire up remaining buttons ── */
            document.getElementById('apply-filters')?.addEventListener('click', () => { closeSearchOverlay(); fetchListings(); });
            document.getElementById('clear-filters')?.addEventListener('click', clearAll);
            document.getElementById('dfb-apply')?.addEventListener('click', fetchListings);
            document.getElementById('dfb-clear')?.addEventListener('click', clearAll);

            document.getElementById('dfb-search-text')?.addEventListener('keypress', e => { if (e.key === 'Enter') fetchListings(); });
            document.querySelectorAll('.t-desktop-filter-bar .filter-select').forEach(s => s.addEventListener('change', fetchListings));

            const minSel = document.getElementById('filter-min-price-select');
            const maxSel = document.getElementById('filter-max-price-select');
            if (minSel) minSel.addEventListener('change', () => { if (minPriceInput) minPriceInput.value = minSel.value; fetchListings(); });
            if (maxSel) maxSel.addEventListener('change', () => { if (maxPriceInput) maxPriceInput.value = maxSel.value; fetchListings(); });

            /* Filter sync layer */
            if (minSel) minSel.addEventListener('change', () => { if (document.getElementById('filter-min-price')) document.getElementById('filter-min-price').value = minSel.value; });
            if (maxSel) maxSel.addEventListener('change', () => { if (document.getElementById('filter-max-price')) document.getElementById('filter-max-price').value = maxSel.value; });
        });
    </script>
</body>

</html>