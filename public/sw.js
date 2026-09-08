/**
 * Food Point POS - Service Worker
 * Caches application shell assets for offline booting and rapid local loading.
 */
const CACHE_NAME = 'foodpoint-pos-shell-v1';
const ASSETS_TO_CACHE = [
    '/assets/vendor/bootstrap/css/bootstrap.min.css',
    '/assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
    '/assets/vendor/bootstrap-icons/bootstrap-icons.css',
    '/assets/vendor/phosphor-icons/phosphor-icons.css',
    '/assets/css/main.css',
    '/assets/js/theme.js',
    '/assets/js/pos-offline-db.js',
    '/assets/js/pos-offline-printer.js',
    '/assets/js/pos-offline-engine.js',
    '/assets/img/favicon.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(ASSETS_TO_CACHE).catch(err => {
                console.warn('Some assets could not be pre-cached during SW install:', err);
            });
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(name => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    // Only cache GET requests
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Static assets: Cache-First strategy
    if (url.pathname.startsWith('/assets/') || url.hostname.includes('fonts.')) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    if (response.status === 200) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Dynamic POS Page: Network-First with Cache fallback
    if (url.pathname === '/pos' || url.pathname.startsWith('/pos/')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    if (response.status === 200) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(event.request).then(cached => {
                        if (cached) return cached;
                        return caches.match('/pos');
                    });
                })
        );
    }
});
