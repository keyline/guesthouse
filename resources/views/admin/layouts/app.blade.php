@php
    $adminTheme = \Illuminate\Support\Facades\Schema::hasTable('settings')
        ? \App\Models\Setting::query()->first()
        : null;
    $safeThemeColor = static fn ($value, $fallback) => is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value)
        ? $value
        : $fallback;
    $sidebarColor = $safeThemeColor($adminTheme?->admin_sidebar_color, '#53647f');
    $primaryColor = $safeThemeColor($adminTheme?->admin_primary_color, '#2563eb');
    $accentColor = $safeThemeColor($adminTheme?->admin_accent_color, '#7dd3fc');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') | {{ config('app.name', 'Property Manager') }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --admin-sidebar-width: 248px;
            --admin-sidebar-collapsed-width: 68px;
            --admin-bg: #f4f7fb;
            --admin-card: #ffffff;
            --admin-border: #dfe7f1;
            --admin-text: #0f172a;
            --admin-muted: #64748b;
            --admin-primary: {{ $primaryColor }};
            --admin-primary-dark: color-mix(in srgb, {{ $primaryColor }} 78%, #000);
            --admin-accent: {{ $accentColor }};
            --admin-sidebar: {{ $sidebarColor }};
            --admin-sidebar-soft: color-mix(in srgb, {{ $sidebarColor }} 88%, #000);
            --admin-sidebar-text: #cbd5e1;
            --admin-ease: cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        body {
            background: var(--admin-bg);
        }

        .admin-shell {
            min-height: 100vh;
            color: var(--admin-text);
        }

        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 40;
            display: flex;
            width: var(--admin-sidebar-width);
            flex-direction: column;
            border-right: 1px solid rgba(148, 163, 184, 0.18);
            background: linear-gradient(180deg, color-mix(in srgb, var(--admin-sidebar) 96%, #fff) 0%, var(--admin-sidebar) 52%, color-mix(in srgb, var(--admin-sidebar) 82%, #000) 100%);
            color: var(--admin-sidebar-text);
            transition: width 220ms var(--admin-ease), transform 220ms var(--admin-ease);
        }

        .admin-main {
            min-height: 100vh;
            margin-left: var(--admin-sidebar-width);
            transition: margin-left 220ms var(--admin-ease);
        }

        .admin-shell.is-collapsed .admin-sidebar {
            width: var(--admin-sidebar-collapsed-width);
        }

        .admin-shell.is-collapsed .admin-main {
            margin-left: var(--admin-sidebar-collapsed-width);
        }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            border-bottom: 1px solid var(--admin-border);
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .admin-card {
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            background: var(--admin-card);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.035);
        }

        .admin-card-header {
            display: flex;
            min-height: 44px;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid var(--admin-border);
            padding: 10px 14px;
        }

        .admin-nav-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.35) transparent;
        }

        .admin-nav-group-label,
        .admin-nav-label,
        .admin-nav-child,
        .admin-brand-text,
        .admin-sidebar-footer-text,
        .admin-sidebar-logout-text,
        .admin-dropdown-arrow {
            transition: opacity 150ms ease;
        }

        .admin-shell.is-collapsed .admin-nav-group-label,
        .admin-shell.is-collapsed .admin-nav-label,
        .admin-shell.is-collapsed .admin-nav-child,
        .admin-shell.is-collapsed .admin-brand-text,
        .admin-shell.is-collapsed .admin-sidebar-footer-text,
        .admin-shell.is-collapsed .admin-sidebar-logout-text,
        .admin-shell.is-collapsed .admin-dropdown-arrow {
            pointer-events: none;
            opacity: 0;
        }

        .admin-brand-mark {
            display: grid;
            height: 36px;
            width: 36px;
            min-width: 36px;
            place-items: center;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-primary));
            color: #fff;
            font-size: 14px;
            font-weight: 900;
            box-shadow: 0 12px 22px rgba(37, 99, 235, 0.24);
        }

        .admin-sidebar-toggle,
        .admin-icon-button {
            display: inline-flex;
            height: 36px;
            width: 36px;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            border: 1px solid var(--admin-border);
            color: #475569;
            transition: background-color 160ms ease, color 160ms ease, transform 160ms var(--admin-ease);
        }

        .admin-sidebar-toggle {
            border-color: rgba(148, 163, 184, 0.24);
            color: #cbd5e1;
        }

        .admin-sidebar-toggle:hover,
        .admin-icon-button:hover {
            background: #f1f5f9;
            color: #0f172a;
            transform: translateY(-1px);
        }

        .admin-sidebar .admin-sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .admin-shell.is-collapsed .admin-sidebar-toggle svg {
            transform: rotate(180deg);
        }

        .admin-nav-parent,
        .admin-nav-link {
            display: flex;
            min-height: 38px;
            width: 100%;
            align-items: center;
            gap: 10px;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 650;
            line-height: 1;
            padding: 0 10px;
            text-decoration: none;
            transition: background-color 150ms ease, color 150ms ease, transform 150ms var(--admin-ease);
        }

        .admin-nav-parent:hover,
        .admin-nav-link:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .admin-nav-parent.is-active,
        .admin-nav-link.is-active {
            background: color-mix(in srgb, var(--admin-primary) 50%, transparent);
            color: #fff;
            box-shadow: inset 3px 0 0 var(--admin-accent), 0 5px 14px rgba(15, 23, 42, 0.12);
        }

        .admin-nav-icon {
            display: grid;
            width: 20px;
            min-width: 20px;
            place-items: center;
        }

        .admin-nav-icon svg {
            height: 18px;
            width: 18px;
        }

        .admin-nav-children {
            display: grid;
            max-height: 0;
            gap: 2px;
            overflow: hidden;
            padding-left: 30px;
            opacity: 0;
            transition: max-height 180ms var(--admin-ease), opacity 140ms ease, padding-top 180ms var(--admin-ease);
        }

        .admin-nav-group.is-open .admin-nav-children {
            max-height: 260px;
            padding-top: 4px;
            opacity: 1;
        }

        .admin-nav-child {
            display: flex;
            min-height: 30px;
            align-items: center;
            border-radius: 7px;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
            padding: 0 9px;
            text-decoration: none;
        }

        .admin-nav-child:hover,
        .admin-nav-child.is-active {
            background: rgba(255, 255, 255, 0.07);
            color: #fff;
        }

        .admin-nav-child.is-active::before {
            width: 5px;
            height: 5px;
            margin-right: 8px;
            border-radius: 999px;
            background: var(--admin-accent);
            content: '';
        }

        .admin-shell.is-collapsed .admin-nav-parent,
        .admin-shell.is-collapsed .admin-nav-link {
            justify-content: center;
            padding-inline: 0;
        }

        .admin-shell.is-collapsed [data-tooltip] {
            position: relative;
        }

        .admin-shell.is-collapsed [data-tooltip]:hover::after {
            position: absolute;
            left: calc(100% + 13px);
            z-index: 60;
            width: max-content;
            max-width: 180px;
            border-radius: 7px;
            background: #0f172a;
            color: #fff;
            content: attr(data-tooltip);
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            padding: 9px 10px;
            pointer-events: none;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .24);
        }

        .admin-shell.is-collapsed .admin-nav-children {
            max-height: 0;
            padding-top: 0;
            opacity: 0;
        }

        .admin-dropdown-arrow {
            margin-left: auto;
            transition: transform 160ms var(--admin-ease), opacity 140ms ease;
        }

        .admin-nav-group.is-open .admin-dropdown-arrow {
            transform: rotate(90deg);
        }

        .admin-sidebar-logout {
            display: flex;
            min-height: 38px;
            width: 100%;
            align-items: center;
            gap: 10px;
            border-radius: 9px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: rgba(255, 255, 255, 0.055);
            color: #cbd5e1;
            padding: 0 10px;
            font-size: 13px;
            font-weight: 800;
        }

        .admin-sidebar-logout:hover {
            background: rgba(239, 68, 68, 0.16);
            color: #fff;
        }

        .admin-shell.is-collapsed .admin-sidebar-logout {
            justify-content: center;
        }

        .admin-page-actions > a,
        .admin-page-actions > button {
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid var(--admin-primary-dark);
            background: var(--admin-primary) !important;
            color: #fff !important;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 850;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(15, 94, 170, 0.14);
        }

        .admin-page-actions > a:hover,
        .admin-page-actions > button:hover {
            background: var(--admin-primary-dark) !important;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .admin-table th {
            border-bottom: 1px solid var(--admin-border);
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.06em;
            padding: 10px 12px;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .admin-table td {
            border-bottom: 1px solid #edf2f7;
            padding: 10px 12px;
            vertical-align: middle;
            white-space: nowrap;
        }

        .admin-table tr:hover td {
            background: #f8fafc;
        }

        .admin-status-dot {
            animation: adminPulse 1800ms ease-in-out infinite;
        }

        .admin-mobile-overlay {
            display: none;
        }

        @keyframes adminPulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.45); }
            70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @media (max-width: 1023px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: var(--admin-sidebar-width);
            }

            .admin-main,
            .admin-shell.is-collapsed .admin-main {
                margin-left: 0;
            }

            .admin-shell.is-mobile-open .admin-sidebar {
                transform: translateX(0);
            }

            .admin-shell.is-mobile-open .admin-mobile-overlay {
                position: fixed;
                inset: 0;
                z-index: 35;
                display: block;
                background: rgba(15, 23, 42, 0.54);
            }

            .admin-shell.is-collapsed .admin-sidebar {
                width: var(--admin-sidebar-width);
            }

            .admin-shell.is-collapsed .admin-nav-group-label,
            .admin-shell.is-collapsed .admin-nav-label,
            .admin-shell.is-collapsed .admin-nav-child,
            .admin-shell.is-collapsed .admin-brand-text,
            .admin-shell.is-collapsed .admin-sidebar-footer-text,
            .admin-shell.is-collapsed .admin-sidebar-logout-text,
            .admin-shell.is-collapsed .admin-dropdown-arrow {
                pointer-events: auto;
                opacity: 1;
            }

            .admin-shell.is-collapsed .admin-sidebar-logout {
                justify-content: flex-start;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body class="text-slate-900 antialiased">
    <div id="adminShell" class="admin-shell">
        <div id="adminMobileOverlay" class="admin-mobile-overlay"></div>

        @include('admin.partials.sidebar')

        <main class="admin-main">
            @include('admin.partials.topbar')

            <section class="w-full px-4 py-4 lg:px-5">
                @hasSection('header-actions')
                    <div class="mb-4 flex justify-end">
                        <div class="admin-page-actions flex flex-wrap items-center justify-end gap-2">
                            @yield('header-actions')
                        </div>
                    </div>
                @endif

                @yield('content')
            </section>
        </main>
    </div>

    <script>
        const adminShell = document.getElementById('adminShell');
        const sidebarToggle = document.getElementById('adminSidebarToggle');
        const mobileToggle = document.getElementById('adminMobileToggle');
        const mobileOverlay = document.getElementById('adminMobileOverlay');
        const navGroups = [...document.querySelectorAll('[data-nav-group]')];

        function setCollapsed(collapsed) {
            adminShell.classList.toggle('is-collapsed', collapsed);
            localStorage.setItem('adminSidebarCollapsed', collapsed ? 'true' : 'false');
        }

        function closeMobileSidebar() {
            adminShell.classList.remove('is-mobile-open');
        }

        document.addEventListener('DOMContentLoaded', () => {
            setCollapsed(localStorage.getItem('adminSidebarCollapsed') === 'true');

            navGroups.forEach((group) => {
                const storedState = localStorage.getItem('adminNavGroup:' + group.dataset.navGroup);

                if (storedState === 'open') {
                    group.classList.add('is-open');
                }

                if (storedState === 'closed' && ! group.querySelector('.is-active')) {
                    group.classList.remove('is-open');
                }
            });
        });

        sidebarToggle?.addEventListener('click', () => {
            setCollapsed(!adminShell.classList.contains('is-collapsed'));
        });

        mobileToggle?.addEventListener('click', () => {
            adminShell.classList.add('is-mobile-open');
        });

        mobileOverlay?.addEventListener('click', closeMobileSidebar);

        navGroups.forEach((group) => {
            const button = group.querySelector('.admin-nav-parent');

            button?.addEventListener('click', () => {
                if (adminShell.classList.contains('is-collapsed') && window.innerWidth >= 1024) {
                    setCollapsed(false);
                }

                group.classList.toggle('is-open');
                localStorage.setItem('adminNavGroup:' + group.dataset.navGroup, group.classList.contains('is-open') ? 'open' : 'closed');
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMobileSidebar();
            }
        });
    </script>
</body>
</html>
