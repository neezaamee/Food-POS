/**
 * Food Point POS - Service Worker Self-Cleanup & Cache Eviction
 * Clears old cached scripts and unregisters itself to prevent stale offline locks.
 */
self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(name => caches.delete(name))
            );
        }).then(() => {
            return self.registration.unregister();
        }).then(() => {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', event => {
    // Pass-through all requests directly to the network
    return;
});
