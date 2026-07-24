/* Dyno service worker — installable PWA + offline app shell.
 * - /admin/* and Livewire endpoints are never intercepted (Filament stays live).
 * - Navigations: network-first, fall back to cached page, then /offline.html.
 * - Build assets & icons: cache-first.
 * Offline set-logging is handled in the page (queued in localStorage, flushed
 * on reconnect via /api/set-log) — not here, so it survives SW updates.
 */
const VERSION = 'dyno-v1';
const SHELL = `${VERSION}-shell`;
const PAGES = `${VERSION}-pages`;
const ASSETS = `${VERSION}-assets`;

const PRECACHE = ['/offline.html', '/manifest.json', '/icons/192.png', '/icons/512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(SHELL).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

function bypass(url) {
    return (
        url.pathname.startsWith('/admin') ||
        url.pathname.startsWith('/livewire') ||
        url.pathname.startsWith('/api')
    );
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Only same-origin GET; never touch admin / livewire / api.
    if (request.method !== 'GET' || url.origin !== self.location.origin || bypass(url)) {
        return;
    }

    // Static assets → cache-first.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname === '/manifest.json') {
        event.respondWith(
            caches.match(request).then((hit) =>
                hit || fetch(request).then((res) => {
                    const copy = res.clone();
                    caches.open(ASSETS).then((c) => c.put(request, copy));
                    return res;
                })
            )
        );
        return;
    }

    // Navigations / pages → network-first, fall back to cache, then offline page.
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then((res) => {
                    // Landing on a guest page (welcome, login, …) means no one is
                    // signed in here any more — drop cached authenticated pages so
                    // the next user of this device can't read them offline.
                    const landed = new URL(res.url || request.url);
                    const guestPaths = ['/', '/login', '/register', '/forgot-password', '/reset-password'];
                    if (guestPaths.some((p) => landed.pathname === p || landed.pathname.startsWith(p + '/'))) {
                        caches.delete(PAGES);
                        return res;
                    }
                    if (res.ok) {
                        const copy = res.clone();
                        caches.open(PAGES).then((c) => c.put(request, copy));
                    }
                    return res;
                })
                .catch(() => caches.match(request).then((hit) => hit || caches.match('/offline.html')))
        );
    }
});

// ---- Web Push: session reminders ----
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { /* non-JSON */ }
    const title = data.title || 'Dyno';
    const options = {
        body: data.body || '',
        icon: '/icons/192.png',
        badge: '/icons/192.png',
        data: { url: data.url || '/' },
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = event.notification.data?.url || '/';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const client of list) {
                if (client.url.includes(target) && 'focus' in client) return client.focus();
            }
            return self.clients.openWindow(target);
        })
    );
});
