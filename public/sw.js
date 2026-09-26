/* Marsha Creatives BMS - Service Worker */
const CACHE_NAME = 'marsha-bms-v2';
const APP_SHELL = [
    '/login',
    '/manifest.json',
    '/images/favicon.png',
    '/images/header_logo.png',
    '/images/logo.png',
    '/images/icons/icon-192.png',
    '/images/icons/icon-512.png'
];

/*
 * Never cache these: they are user specific, and a stale answer would show
 * the wrong notifications or the wrong balance. Entries have no trailing
 * slash so that "/notifications" also covers "/notifications/{id}/read" and
 * "/push" covers "/push/key".
 */
const NEVER_CACHE = ['/notifications', '/push'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(APP_SHELL))
            .catch(() => {})
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Only handle same-origin GET requests
    if (event.request.method !== 'GET' || url.origin !== location.origin) {
        return;
    }

    if (NEVER_CACHE.some((prefix) => url.pathname === prefix || url.pathname.startsWith(prefix + '/'))) {
        return;
    }

    // Network-first for HTML navigations (fresh data when online, offline fallback)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                    return response;
                })
                .catch(() => caches.match(event.request).then((cached) =>
                    cached || caches.match('/login')
                ))
        );
        return;
    }

    // Cache-first for static assets (images, css, js, manifest, icons)
    event.respondWith(
        caches.match(event.request).then((cached) => {
            const fetchPromise = fetch(event.request)
                .then((response) => {
                    if (response && response.status === 200 && response.type === 'basic') {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                    }
                    return response;
                })
                .catch(() => cached);
            return cached || fetchPromise;
        })
    );
});

/* ---------- Web Push ---------- */
self.addEventListener('push', (event) => {
    let payload = {};

    if (event.data) {
        try {
            payload = event.data.json();
        } catch (err) {
            payload = { title: 'Marsha Creatives', body: event.data.text() };
        }
    }

    const title = payload.title || 'Marsha Creatives';
    const options = {
        body: payload.body || '',
        icon: '/images/icons/icon-192.png',
        badge: '/images/icons/icon-192.png',
        tag: payload.category ? 'marsha-' + payload.category : 'marsha',
        renotify: true,
        data: { url: payload.url || null },
        actions: [{ action: 'open', title: 'Open' }]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
            .then(() => notifyOpenClients())
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = safeTarget((event.notification.data && event.notification.data.url) || '/');

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Reuse a tab that is already open rather than piling up new ones.
                for (const client of clientList) {
                    if ('focus' in client) {
                        if ('navigate' in client) {
                            return client.navigate(target).then((navigated) => {
                                return navigated ? navigated.focus() : client.focus();
                            });
                        }
                        return client.focus();
                    }
                }
                return self.clients.openWindow(target);
            })
    );
});

/*
 * Notification links are stored with route(), so they are absolute URLs. If
 * the app is ever served from a different domain the old links would try to
 * navigate a tab off site, so anything off-origin is sent to the home page.
 */
function safeTarget(url) {
    try {
        const parsed = new URL(url, self.location.origin);

        if (parsed.origin !== self.location.origin) {
            return '/';
        }

        return parsed.pathname + parsed.search + parsed.hash;
    } catch (err) {
        return '/';
    }
}

/*
 * Let any open tab know a push arrived so it can refresh its badge
 * immediately instead of waiting for the next poll tick.
 */
function notifyOpenClients() {
    return self.clients.matchAll({ type: 'window', includeUncontrolled: true })
        .then((clientList) => {
            clientList.forEach((client) => {
                client.postMessage({ type: 'NOTIFICATION_RECEIVED' });
            });
        });
}

