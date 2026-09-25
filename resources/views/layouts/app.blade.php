<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'Marsha Creatives') - Business Management</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#1e3a5f">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Marsha BMS">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-180.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); }
        .card-hover { transition: transform 0.2s, box-shadow 0.2s; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .chevron { transition: transform 0.2s; }
        details[open] > summary .chevron { transform: rotate(180deg); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Sidebar overlay (mobile) -->
    <div id="sidebarOverlay" class="fixed inset-0 z-30 bg-black/40 hidden lg:hidden" aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="gradient-bg fixed inset-y-0 left-0 w-64 z-40 flex flex-col overflow-y-auto transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="flex items-center justify-between px-4 h-16 shrink-0">
            <img src="{{ asset('images/header_logo.png') }}" alt="Marsha Creatives" class="h-12">
            <button type="button" id="sidebarCloseBtn" class="lg:hidden text-gray-300 hover:text-white focus:outline-none" aria-label="Close menu">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="flex items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-white/20 text-white' : '' }}">Dashboard</a>
                <a href="{{ route('security.dashboard') }}" class="flex items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('security.*') ? 'bg-white/20 text-white' : '' }}">Security</a>
                <a href="{{ route('admin.balance') }}" class="flex items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.balance*') ? 'bg-white/20 text-white' : '' }}">Balance</a>
                <a href="{{ route('admin.jobs.index') }}" class="flex items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.jobs*') ? 'bg-white/20 text-white' : '' }}">Jobs</a>

                <details class="group">
                    <summary class="flex items-center justify-between cursor-pointer text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.documents*') || request()->routeIs('admin.items*') ? 'bg-white/20 text-white' : '' }}">
                        <span>Documents</span>
                        <svg class="chevron w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </summary>
                    <div class="pl-6 flex flex-col mt-1">
                        <a href="{{ route('admin.documents.index', ['type' => 'invoice']) }}" class="text-gray-400 hover:text-white px-3 py-1.5 rounded-md text-sm font-medium {{ request()->fullUrlIs(route('admin.documents.index', ['type' => 'invoice'])) ? 'bg-white/10 text-white' : '' }}">Invoices</a>
                        <a href="{{ route('admin.documents.index', ['type' => 'quote']) }}" class="text-gray-400 hover:text-white px-3 py-1.5 rounded-md text-sm font-medium {{ request()->fullUrlIs(route('admin.documents.index', ['type' => 'quote'])) ? 'bg-white/10 text-white' : '' }}">Quotes</a>
                        <a href="{{ route('admin.documents.index', ['type' => 'receipt']) }}" class="text-gray-400 hover:text-white px-3 py-1.5 rounded-md text-sm font-medium {{ request()->fullUrlIs(route('admin.documents.index', ['type' => 'receipt'])) ? 'bg-white/10 text-white' : '' }}">Receipts</a>
                        <a href="{{ route('admin.items.index') }}" class="text-gray-400 hover:text-white px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('admin.items*') ? 'bg-white/10 text-white' : '' }}">Items</a>
                    </div>
                </details>

                <a href="{{ route('admin.users.index') }}" class="flex items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.users*') ? 'bg-white/20 text-white' : '' }}">Employees</a>
                <a href="{{ route('admin.financials') }}" class="flex items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.financials') ? 'bg-white/20 text-white' : '' }}">Financials</a>
            @else
                <a href="{{ route('employee.dashboard') }}" class="flex items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.dashboard') ? 'bg-white/20 text-white' : '' }}">Dashboard</a>
                <a href="{{ route('employee.jobs.index') }}" class="flex items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs*') ? 'bg-white/20 text-white' : '' }}">My Jobs</a>
                <a href="{{ route('employee.jobs.create') }}" class="flex items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs.create') ? 'bg-white/20 text-white' : '' }}">Log Job</a>
                <a href="{{ route('employee.jobs.history') }}" class="flex items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs.history') ? 'bg-white/20 text-white' : '' }}">History</a>

                <details class="group">
                    <summary class="flex items-center justify-between cursor-pointer text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.documents*') || request()->routeIs('employee.items*') ? 'bg-white/20 text-white' : '' }}">
                        <span>Documents</span>
                        <svg class="chevron w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </summary>
                    <div class="pl-6 flex flex-col mt-1">
                        <a href="{{ route('employee.documents.index', ['type' => 'invoice']) }}" class="text-gray-400 hover:text-white px-3 py-1.5 rounded-md text-sm font-medium {{ request()->fullUrlIs(route('employee.documents.index', ['type' => 'invoice'])) ? 'bg-white/10 text-white' : '' }}">Invoices</a>
                        <a href="{{ route('employee.documents.index', ['type' => 'quote']) }}" class="text-gray-400 hover:text-white px-3 py-1.5 rounded-md text-sm font-medium {{ request()->fullUrlIs(route('employee.documents.index', ['type' => 'quote'])) ? 'bg-white/10 text-white' : '' }}">Quotes</a>
                        <a href="{{ route('employee.documents.index', ['type' => 'receipt']) }}" class="text-gray-400 hover:text-white px-3 py-1.5 rounded-md text-sm font-medium {{ request()->fullUrlIs(route('employee.documents.index', ['type' => 'receipt'])) ? 'bg-white/10 text-white' : '' }}">Receipts</a>
                        <a href="{{ route('employee.items.index') }}" class="text-gray-400 hover:text-white px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('employee.items*') ? 'bg-white/10 text-white' : '' }}">Items</a>
                    </div>
                </details>
            @endif

            <button type="button" id="installPwaBtn"
                class="hidden items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium" aria-label="Install App">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-3-3m3 3l3-3"/></svg>
                Install App
            </button>
        </nav>

        <div class="p-3 border-t border-white/10 shrink-0">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium">Logout</button>
            </form>
        </div>
    </aside>

    <!-- Main column -->
    <div class="lg:pl-64 flex flex-col min-h-screen">
        <header class="gradient-bg shadow-lg sticky top-0 z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center">
                    <div class="flex items-center">
                        <button type="button" id="mobileMenuBtn" class="lg:hidden text-gray-300 hover:text-white focus:outline-none" aria-label="Toggle menu">
                            <svg id="mobileMenuIcon" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                    </div>
                    <div class="flex items-center space-x-4 ml-auto">
                        <span id="offlineBadge" class="hidden px-2 py-1 text-xs rounded-full bg-red-500 text-white">Offline</span>
                        <span class="text-gray-300 text-sm">{{ auth()->user()->name }}</span>
                        <span class="px-2 py-1 text-xs rounded-full {{ auth()->user()->isAdmin() ? 'bg-yellow-500 text-white' : 'bg-green-500 text-white' }}">{{ ucfirst(auth()->user()->role) }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                            @csrf
                            <button type="submit" class="text-gray-300 hover:text-white text-sm">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('scripts')

    <script>
        (function () {
            var btn = document.getElementById('mobileMenuBtn');
            var closeBtn = document.getElementById('sidebarCloseBtn');
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');
            var icon = document.getElementById('mobileMenuIcon');

            if (!btn || !sidebar) return;

            function setOpen(open) {
                sidebar.classList.toggle('-translate-x-full', !open);
                if (overlay) overlay.classList.toggle('hidden', !open);
                document.body.style.overflow = open ? 'hidden' : '';
                if (icon) {
                    icon.innerHTML = open
                        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>'
                        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>';
                }
            }

            btn.addEventListener('click', function () {
                setOpen(sidebar.classList.contains('-translate-x-full'));
            });
            if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
            if (overlay) overlay.addEventListener('click', function () { setOpen(false); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') setOpen(false);
            });
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 1024) setOpen(false);
            });
        })();

        // ---------- PWA ----------
        (function () {
            var deferredPrompt = null;
            var installBtn = document.getElementById('installPwaBtn');
            var offlineBadge = document.getElementById('offlineBadge');

            function showInstallButtons() {
                if (!installBtn) return;
                installBtn.classList.remove('hidden');
                installBtn.classList.add('inline-flex');
            }

            window.addEventListener('beforeinstallprompt', function (e) {
                e.preventDefault();
                deferredPrompt = e;
                showInstallButtons();
            });

            function doInstall() {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function (choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        if (installBtn) installBtn.classList.add('hidden');
                    }
                    deferredPrompt = null;
                });
            }

            if (installBtn) installBtn.addEventListener('click', doInstall);

            // Reflect app-installed state (hide install buttons once installed)
            window.addEventListener('appinstalled', function () {
                if (installBtn) installBtn.classList.add('hidden');
            });

            // Hide install button if already running as an installed PWA
            if (window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true) {
                if (installBtn) installBtn.classList.add('hidden');
            }

            // Online/offline indicator
            function updateOnline() {
                if (!offlineBadge) return;
                if (navigator.onLine) {
                    offlineBadge.classList.add('hidden');
                } else {
                    offlineBadge.classList.remove('hidden');
                }
            }
            window.addEventListener('online', updateOnline);
            window.addEventListener('offline', updateOnline);
            updateOnline();

            // Register service worker
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js').catch(function (err) {
                        console.error('Service worker registration failed:', err);
                    });
                });
            }
        })();
    </script>
</body>
</html>