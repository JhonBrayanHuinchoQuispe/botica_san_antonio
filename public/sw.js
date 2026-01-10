// Service Worker for Sistema de Botica PWA
const CACHE_NAME = 'botica-sistema-v1.0.5';
const STATIC_CACHE = 'botica-static-v1.0.5';
const DYNAMIC_CACHE = 'botica-dynamic-v1.0.5';

// Files to cache immediately
const STATIC_FILES = [
    '/',
    '/assets/css/style.css',
    '/assets/css/custom-sidebar.css',
    '/assets/css/navbar-search.css',
    '/assets/css/inventario/productos_botica.css',
    '/assets/js/app.js',
    '/assets/js/navbar-search.js',
    '/assets/js/pwa-install.js',
    '/assets/js/inventario/productos_botica.js',
    '/images/icons/icon-192x192.svg',
    '/images/icons/icon-512x512.svg',
    '/manifest.json'
];

// Install event - cache static files
self.addEventListener('install', (event) => {
    console.log('SW: Installing service worker');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('SW: Caching static files');
                return cache.addAll(STATIC_FILES);
            })
            .catch((error) => {
                console.error('SW: Error caching static files:', error);
            })
    );
    
    // Force the waiting service worker to become the active service worker
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('SW: Activating service worker');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                            console.log('SW: Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                // Take control of all pages immediately
                return self.clients.claim();
            })
    );
});

// Fetch event - serve cached files or fetch from network
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET') {
        return;
    }

    if (url.origin !== location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstWithTimeout(request, 8000));
        return;
    }

    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(request));
        return;
    }

    if (url.pathname.includes('csrf-token')) {
        event.respondWith(networkFirst(request));
        return;
    }

    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    event.respondWith(networkFirst(request));
});

// Cache first strategy (for static assets)
async function cacheFirst(request) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse.status === 200) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.error('SW: Cache first strategy failed:', error);
        
        // Return offline fallback if available
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline page for navigation requests
        if (request.mode === 'navigate') {
            return caches.match('/offline.html') || new Response('Offline', { status: 503 });
        }
        
        throw error;
    }
}

// Network first strategy (for dynamic content)
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse.status === 200) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.warn('SW: Network first strategy failed:', error && (error.message || error));

        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        if (request.mode === 'navigate') {
            return caches.match('/offline.html') || new Response('Offline', { status: 503 });
        }

        try {
            const url = new URL(request.url);
            if (url.pathname.startsWith('/api/')) {
                return new Response(JSON.stringify({ success: false, message: 'Offline o sin conexión' }), {
                    status: 503,
                    headers: { 'Content-Type': 'application/json' }
                });
            }
        } catch (_) {}

        return new Response('', { status: 503 });
    }
}

async function networkFirstWithTimeout(request, ms) {
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), ms);
    try {
        const res = await fetch(request, { signal: controller.signal });
        clearTimeout(id);
        if (res && res.status === 200) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, res.clone());
        }
        return res;
    } catch (e) {
        clearTimeout(id);
        const cached = await caches.match(request);
        if (cached) return cached;
        if (request.mode === 'navigate') {
            return caches.match('/') || new Response('Offline', { status: 503 });
        }
        try {
            const url = new URL(request.url);
            if (url.pathname.startsWith('/api/')) {
                return new Response(JSON.stringify({ success: false, message: 'Timeout de red' }), {
                    status: 503,
                    headers: { 'Content-Type': 'application/json' }
                });
            }
        } catch (_) {}
        return new Response('', { status: 503 });
    }
}

// Check if request is for a static asset
function isStaticAsset(pathname) {
    const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot'];
    return staticExtensions.some(ext => pathname.endsWith(ext));
}

// Handle background sync (for offline actions)
self.addEventListener('sync', (event) => {
    console.log('SW: Background sync triggered:', event.tag);
    
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

async function doBackgroundSync() {
    try {
        // Handle any pending offline actions here
        console.log('SW: Performing background sync');
        
        // Example: Send queued data when back online
        // This would be implemented based on your specific needs
        
    } catch (error) {
        console.error('SW: Background sync failed:', error);
    }
}

// Handle push notifications (if needed in the future)
self.addEventListener('push', (event) => {
    console.log('SW: Push notification received');

    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (e) {
        payload = { body: event.data ? event.data.text() : 'Nueva notificación del Sistema de Botica' };
    }

    const type = payload.type || (payload.data && payload.data.type) || 'general';
    const title = payload.title || 'Sistema de Botica';
    const body = payload.body || 'Nueva notificación';
    const link = (payload.data && payload.data.url) ? payload.data.url : '/inventario/productos';

    // Iconos por tipo (puedes personalizar rutas si agregas iconos específicos)
    const typeIcons = {
        low_stock: '/images/icons/icon-192x192.svg',
        expiring_soon: '/images/icons/icon-192x192.svg',
        expired: '/images/icons/icon-192x192.svg',
        out_of_stock: '/images/icons/icon-192x192.svg',
        general: '/images/icons/icon-192x192.svg',
    };

    const icon = payload.icon || typeIcons[type] || typeIcons.general;
    const badge = payload.badge || '/images/icons/icon-192x192.svg';

    const options = {
        body,
        icon,
        badge,
        vibrate: [100, 50, 100],
        data: { url: link, arrivedAt: Date.now(), type },
        tag: payload.tag || `botica-${type}`,
        renotify: true,
        requireInteraction: true,
        actions: [
            { action: 'open', title: 'Abrir', icon },
            { action: 'close', title: 'Cerrar', icon }
        ]
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    console.log('SW: Notification clicked');
    event.notification.close();

    const url = event.notification?.data?.url || '/inventario/productos';
    if (event.action === 'close') return;

    event.waitUntil(clients.openWindow(url));
});

console.log('SW: Service Worker loaded successfully');
