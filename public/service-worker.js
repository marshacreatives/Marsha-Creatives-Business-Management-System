const CACHE_NAME = 'marsha-creatives-v1';

const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/images/logo.png',
    '/images/header_logo.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((cached) => {
            return cached || fetch(event.request).then((response) => {
                return caches.open(CACHE_NAME).then((cache) => {
                    if (event.request.method === 'GET') {
                        cache.put(event.request, response.clone());
                    }
                    return response;
                });
            });
        })
    );
});
