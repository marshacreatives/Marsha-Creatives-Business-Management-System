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
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="{{ asset('images/header_logo.png') }}" alt="Marsha Creatives" class="h-12">
                    <div class="hidden md:flex ml-10 space-x-1">
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-white/20 text-white' : '' }}">Dashboard</a>
                            <a href="{{ route('security.dashboard') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('security.*') ? 'bg-white/20 text-white' : '' }}">Security</a>
                            <a href="{{ route('admin.balance') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.balance*') ? 'bg-white/20 text-white' : '' }}">Balance</a>
                            <a href="{{ route('admin.jobs.index') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.jobs*') ? 'bg-white/20 text-white' : '' }}">Jobs</a>
                            <a href="{{ route('admin.users.index') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.users*') ? 'bg-white/20 text-white' : '' }}">Employees</a>
                            <a href="{{ route('admin.financials') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.financials') ? 'bg-white/20 text-white' : '' }}">Financials</a>
                            <button type="button" id="installPwaBtn" class="hidden items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium" aria-label="Install App">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-3-3m3 3l3-3"/></svg>
                                Install App
                            </button>
                        @else
                            <a href="{{ route('employee.dashboard') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.dashboard') ? 'bg-white/20 text-white' : '' }}">Dashboard</a>
                            <a href="{{ route('employee.jobs.index') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs*') ? 'bg-white/20 text-white' : '' }}">My Jobs</a>
                            <a href="{{ route('employee.jobs.create') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs.create') ? 'bg-white/20 text-white' : '' }}">Log Job</a>
                            <a href="{{ route('employee.jobs.history') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs.history') ? 'bg-white/20 text-white' : '' }}">History</a>
                            <button type="button" id="installPwaBtn" class="hidden items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium" aria-label="Install App">
                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-3-3m3 3l3-3"/></svg>
                                Install App
                            </button>
                        @endif
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span id="offlineBadge" class="hidden px-2 py-1 text-xs rounded-full bg-red-500 text-white">Offline</span>
                    <span class="text-gray-300 text-sm">{{ auth()->user()->name }}</span>
                    <span class="px-2 py-1 text-xs rounded-full {{ auth()->user()->isAdmin() ? 'bg-yellow-500 text-white' : 'bg-green-500 text-white' }}">{{ ucfirst(auth()->user()->role) }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-300 hover:text-white text-sm">Logout</button>
                    </form>
                    <button type="button" id="mobileMenuBtn" class="md:hidden text-gray-300 hover:text-white focus:outline-none" aria-label="Toggle menu">
                        <svg id="mobileMenuIcon" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>
            </div>

            <div id="mobileMenu" class="md:hidden hidden pb-4">
                <div class="flex flex-col space-y-1">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-white/20 text-white' : '' }}">Dashboard</a>
                        <a href="{{ route('security.dashboard') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('security.*') ? 'bg-white/20 text-white' : '' }}">Security</a>
                        <a href="{{ route('admin.balance') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.balance*') ? 'bg-white/20 text-white' : '' }}">Balance</a>
                        <a href="{{ route('admin.jobs.index') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.jobs*') ? 'bg-white/20 text-white' : '' }}">Jobs</a>
                        <a href="{{ route('admin.users.index') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.users*') ? 'bg-white/20 text-white' : '' }}">Employees</a>
                        <a href="{{ route('admin.financials') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.financials') ? 'bg-white/20 text-white' : '' }}">Financials</a>
                    @else
                        <a href="{{ route('employee.dashboard') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.dashboard') ? 'bg-white/20 text-white' : '' }}">Dashboard</a>
                        <a href="{{ route('employee.jobs.index') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs*') ? 'bg-white/20 text-white' : '' }}">My Jobs</a>
                        <a href="{{ route('employee.jobs.create') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs.create') ? 'bg-white/20 text-white' : '' }}">Log Job</a>
                        <a href="{{ route('employee.jobs.history') }}" class="text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('employee.jobs.history') ? 'bg-white/20 text-white' : '' }}">History</a>
                    @endif
                    <button type="button" id="installPwaBtnMobile" class="hidden items-center text-gray-300 hover:text-white hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-3-3m3 3l3-3"/></svg>
                        Install App
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

    <script>
        (function () {
            var btn = document.getElementById('mobileMenuBtn');
            var menu = document.getElementById('mobileMenu');
            if (btn && menu) {
                btn.addEventListener('click', function () {
                    var hidden = menu.classList.toggle('hidden');
                    var icon = document.getElementById('mobileMenuIcon');
                    if (icon) {
                        icon.innerHTML = hidden
                            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>'
                            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
                    }
                });
            }
        })();

        // ---------- PWA ----------
        (function () {
            var deferredPrompt = null;
            var installBtn = document.getElementById('installPwaBtn');
            var installBtnMobile = document.getElementById('installPwaBtnMobile');
            var offlineBadge = document.getElementById('offlineBadge');

            function showInstallButtons() {
                if (!installBtn || !installBtnMobile) return;
                installBtn.classList.remove('hidden');
                installBtn.classList.add('inline-flex');
                installBtnMobile.classList.remove('hidden');
                installBtnMobile.classList.add('flex');
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
                        if (installBtnMobile) installBtnMobile.classList.add('hidden');
                    }
                    deferredPrompt = null;
                });
            }

            if (installBtn) installBtn.addEventListener('click', doInstall);
            if (installBtnMobile) installBtnMobile.addEventListener('click', doInstall);

            // Reflect app-installed state (hide install buttons once installed)
            window.addEventListener('appinstalled', function () {
                if (installBtn) installBtn.classList.add('hidden');
                if (installBtnMobile) installBtnMobile.classList.add('hidden');
            });

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
