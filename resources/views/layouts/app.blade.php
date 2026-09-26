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

                        <div class="relative" id="notifBell">
                            <button type="button" id="notifBellBtn" class="relative text-gray-300 hover:text-white focus:outline-none" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"></path>
                                </svg>
                                <span id="notifBadge" class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center text-[10px] font-bold rounded-full bg-red-500 text-white">0</span>
                            </button>

                            <div id="notifPanel" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                    <span class="font-semibold text-gray-800 text-sm">Notifications</span>
                                    <button type="button" id="notifReadAll" class="text-xs text-indigo-600 hover:text-indigo-800">Mark all read</button>
                                </div>

                                <div id="notifList" class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                                    <p class="px-4 py-6 text-center text-sm text-gray-400">Loading&hellip;</p>
                                </div>

                                <div class="px-4 py-2 border-t border-gray-100 text-center">
                                    <a href="{{ route('notifications.page') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View all notifications</a>
                                </div>

                                <div class="px-4 py-3 border-t border-gray-100">
                                    <button type="button" id="notifPushBtn" class="w-full text-xs px-3 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                                        Enable browser notifications
                                    </button>
                                    <p id="notifPushHint" class="hidden mt-2 text-xs text-gray-500"></p>
                                </div>
                            </div>
                        </div>

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

        // ---------- Notifications ----------
        (function () {
            var bellBtn = document.getElementById('notifBellBtn');
            var panel = document.getElementById('notifPanel');
            var list = document.getElementById('notifList');
            var badge = document.getElementById('notifBadge');
            var readAllBtn = document.getElementById('notifReadAll');
            var pushBtn = document.getElementById('notifPushBtn');
            var pushHint = document.getElementById('notifPushHint');
            var wrap = document.getElementById('notifBell');

            if (!bellBtn || !panel || !list || !badge || !wrap) return;

            var CSRF = '{{ csrf_token() }}';
            var INDEX_URL = '{{ route('notifications.index') }}';
            var READ_ALL_URL = '{{ route('notifications.read-all') }}';
            var PAGE_URL = '{{ route('notifications.page') }}';
            var PUSH_KEY_URL = '{{ route('push.key') }}';
            var SUBSCRIBE_URL = '{{ route('push.subscribe') }}';
            var UNSUBSCRIBE_URL = '{{ route('push.unsubscribe') }}';

            var POLL_MS = 20000;
            var lastUnread = null;
            var timer = null;

            var COLORS = {
                blue: 'bg-blue-100 text-blue-800',
                amber: 'bg-amber-100 text-amber-800',
                emerald: 'bg-emerald-100 text-emerald-800'
            };

            function csrfHeaders(extra) {
                var headers = {
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                };
                if (extra) {
                    Object.keys(extra).forEach(function (k) { headers[k] = extra[k]; });
                }
                return headers;
            }

            function escapeHtml(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            }

            function setBadge(count) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : String(count);
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            function render(data) {
                setBadge(data.unread_count);

                if (!data.notifications.length) {
                    list.innerHTML = '<p class="px-4 py-6 text-center text-sm text-gray-400">Nothing here yet.</p>';
                    return;
                }

                list.innerHTML = data.notifications.map(function (n) {
                    var color = COLORS[n.color] || COLORS.blue;
                    var dot = n.read_at
                        ? ''
                        : '<span class="mt-1.5 h-2 w-2 rounded-full bg-indigo-500 shrink-0"></span>';
                    var body = '<div class="flex gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer" data-id="' + escapeHtml(n.id) + '"' +
                        (n.url ? ' data-url="' + escapeHtml(n.url) + '"' : '') + '>' +
                        '<span class="px-2 py-0.5 h-fit text-[10px] font-semibold rounded ' + color + '">' + escapeHtml(n.category) + '</span>' +
                        '<div class="flex-1 min-w-0">' +
                        '<p class="text-sm font-medium text-gray-800 break-words">' + escapeHtml(n.title) + '</p>' +
                        '<p class="text-xs text-gray-500 break-words">' + escapeHtml(n.body) + '</p>' +
                        '<p class="text-[11px] text-gray-400 mt-0.5">' + escapeHtml(n.created_at ? new Date(n.created_at).toLocaleString() : '') + '</p>' +
                        '</div>' + dot +
                        '</div>';
                    return body;
                }).join('');
            }

            function poll() {
                fetch(INDEX_URL, { headers: csrfHeaders(), credentials: 'same-origin' })
                    .then(function (res) {
                        // An expired session redirects to the login page, so a
                        // non JSON body means we are no longer authenticated.
                        // Stop polling rather than hammering the server.
                        if (!res.headers.get('content-type') || res.headers.get('content-type').indexOf('json') === -1) {
                            if (timer) clearInterval(timer);
                            return null;
                        }
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(function (data) {
                        if (!data) return;
                        render(data);
                        // A rising unread count means something happened while the
                        // user was elsewhere in the app.
                        if (lastUnread !== null && data.unread_count > lastUnread) {
                            showToast('You have ' + data.unread_count + ' unread notification' + (data.unread_count === 1 ? '' : 's'));
                        }
                        lastUnread = data.unread_count;
                    })
                    .catch(function () { /* stay quiet; the next tick retries */ });
            }

            /**
             * A request that must genuinely have succeeded. Validation errors
             * on web routes redirect to the login page and come back as HTML,
             * so a 200 is not on its own proof that the write happened.
             */
            function requireJson(response) {
                var type = response.headers.get('content-type') || '';
                if (type.indexOf('json') === -1) {
                    throw new Error('Unexpected response (the request was rejected or the session expired)');
                }
                return response.json();
            }

            function schedule() {
                if (timer) clearInterval(timer);
                timer = setInterval(poll, POLL_MS);
            }

            function showToast(message) {
                var el = document.createElement('div');
                el.className = 'fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg bg-gray-800 text-white text-sm';
                el.textContent = message;
                document.body.appendChild(el);
                setTimeout(function () { el.remove(); }, 4000);
            }

            function markRead(id) {
                return fetch('{{ url('notifications') }}/' + encodeURIComponent(id) + '/read', {
                    method: 'POST',
                    headers: csrfHeaders(),
                    credentials: 'same-origin'
                }).then(requireJson).then(poll).catch(function () {});
            }

            // ---- dropdown ----
            bellBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = panel.classList.contains('hidden');
                panel.classList.toggle('hidden');
                bellBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) poll();
            });

            document.addEventListener('click', function (e) {
                if (!wrap.contains(e.target)) {
                    panel.classList.add('hidden');
                    bellBtn.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    panel.classList.add('hidden');
                    bellBtn.setAttribute('aria-expanded', 'false');
                }
            });

            readAllBtn.addEventListener('click', function () {
                fetch(READ_ALL_URL, { method: 'POST', headers: csrfHeaders(), credentials: 'same-origin' })
                    .then(requireJson)
                    .then(poll)
                    .catch(function () {});
            });

            list.addEventListener('click', function (e) {
                var row = e.target.closest('[data-id]');
                if (!row) return;
                markRead(row.getAttribute('data-id'));
                var url = row.getAttribute('data-url');
                if (url) window.location.href = url;
            });

            // ---- browser push opt-in ----
            function urlBase64ToUint8Array(base64) {
                var padding = '='.repeat((4 - (base64.length % 4)) % 4);
                var raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
                var output = new Uint8Array(raw.length);
                for (var i = 0; i < raw.length; i++) output[i] = raw.charCodeAt(i);
                return output;
            }

            function currentSubscription() {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) return Promise.resolve(null);
                return navigator.serviceWorker.ready
                    .then(function (reg) { return reg.pushManager.getSubscription(); })
                    .catch(function () { return null; });
            }

            function setPushButton(label, hint, hintClass) {
                if (!pushBtn) return;
                pushBtn.textContent = label;
                if (!pushHint) return;
                if (hint) {
                    pushHint.textContent = hint;
                    pushHint.className = 'mt-2 text-xs ' + (hintClass || 'text-gray-500');
                } else {
                    pushHint.textContent = '';
                    pushHint.className = 'hidden mt-2 text-xs text-gray-500';
                }
            }

            function syncPushButton() {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    setPushButton('Browser notifications unavailable', 'This browser does not support push.', 'text-red-500');
                    return;
                }
                if (!('Notification' in window)) {
                    setPushButton('Browser notifications unavailable', 'This browser does not support notifications.', 'text-red-500');
                    return;
                }
                if (Notification.permission === 'denied') {
                    setPushButton('Notifications blocked', 'Re-enable notifications for this site in your browser settings.', 'text-red-500');
                    return;
                }
                currentSubscription().then(function (sub) {
                    if (sub) {
                        setPushButton('Disable browser notifications');
                    } else if (Notification.permission === 'granted') {
                        setPushButton('Enable browser notifications');
                    } else {
                        setPushButton('Enable browser notifications', 'You will be asked for permission.');
                    }
                });
            }

            if (pushBtn) {
                pushBtn.addEventListener('click', function () {
                    if (!('Notification' in window)) return;
                    if (Notification.permission === 'denied') {
                        setPushButton('Notifications blocked', 'Re-enable notifications in your browser settings.', 'text-red-500');
                        return;
                    }

                    currentSubscription().then(function (existing) {
                        if (existing) {
                            existing.unsubscribe().then(function () {
                                fetch(UNSUBSCRIBE_URL, {
                                    method: 'POST',
                                    headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                                    credentials: 'same-origin',
                                    body: JSON.stringify({ endpoint: existing.endpoint })
                                })
                                    .then(requireJson)
                                    .then(function () {
                                        setPushButton('Enable browser notifications', 'Browser notifications turned off.', 'text-gray-500');
                                    })
                                    .catch(function (err) {
                                        setPushButton('Enable browser notifications', 'Could not unsubscribe: ' + err.message, 'text-amber-600');
                                    });
                            });
                            return;
                        }

                        Notification.requestPermission().then(function (permission) {
                            if (permission !== 'granted') {
                                setPushButton('Enable browser notifications', 'Permission was not granted.', 'text-amber-600');
                                return;
                            }

                            fetch(PUSH_KEY_URL, { headers: csrfHeaders(), credentials: 'same-origin' })
                                .then(requireJson)
                                .then(function (data) {
                                    if (!data.enabled) {
                                        setPushButton('Push not configured', 'The server has no VAPID keys yet. Run php artisan push:vapid.', 'text-amber-600');
                                        return null;
                                    }
                                    return navigator.serviceWorker.ready
                                        .then(function (reg) {
                                            return reg.pushManager.subscribe({
                                                userVisibleOnly: true,
                                                applicationServerKey: urlBase64ToUint8Array(data.public_key)
                                            });
                                        })
                                        .then(function (sub) {
                                            return fetch(SUBSCRIBE_URL, {
                                                method: 'POST',
                                                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                                                credentials: 'same-origin',
                                                body: JSON.stringify(sub.toJSON())
                                            }).then(requireJson);
                                        })
                                        .then(function () {
                                            setPushButton('Disable browser notifications', 'Browser notifications are on.', 'text-emerald-600');
                                        });
                                })
                                .catch(function (err) {
                                    setPushButton('Enable browser notifications', 'Could not subscribe: ' + err.message, 'text-amber-600');
                                });
                        });
                    });
                });
            }

            // Poll straight away so the badge is correct on first paint, then
            // pause while the tab is hidden to avoid pointless requests.
            poll();
            schedule();
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    if (timer) clearInterval(timer);
                } else {
                    poll();
                    schedule();
                }
            });

            // The service worker fires a message after a push arrives so the
            // open tab can refresh its badge without waiting for the next tick.
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.addEventListener('message', function (event) {
                    if (event.data && event.data.type === 'NOTIFICATION_RECEIVED') {
                        poll();
                    }
                });
            }

            syncPushButton();
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.ready.then(syncPushButton).catch(function () {});
            }
        })();
    </script>
</body>
</html>