const CACHE_NAME = 'rk-property-pwa-v1';
const STATIC_ASSETS = [
  '/favicon.ico',
  '/manifest.json'
];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn('PWA: Failed to cache some static assets during install', err);
      });
    })
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// Network-first strategy for dynamic web apps
self.addEventListener('fetch', (event) => {
  // Only handle GET requests
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Skip API or non-http requests
  if (!url.protocol.startsWith('http')) return;

  // Static assets (images, css, js, fonts) can be stale-while-revalidate or cache-first
  const isStatic = url.pathname.match(/\.(css|js|png|jpg|jpeg|svg|ico|woff2?|ttf|eot)$/i);

  if (isStatic) {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        const fetchPromise = fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseToCache));
          }
          return networkResponse;
        }).catch(() => cachedResponse);

        return cachedResponse || fetchPromise;
      })
    );
  } else {
    // HTML pages: Network first, fall back to offline/cache
    event.respondWith(
      fetch(event.request)
        .then((networkResponse) => {
          return networkResponse;
        })
        .catch(() => {
          return caches.match(event.request).then((cached) => {
            if (cached) return cached;
            // Fallback offline message if completely offline
            return new Response(
              '<div style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Anda sedang offline</h2><p>Silakan periksa koneksi internet Anda lalu tekan Refresh.</p><button onclick="window.location.reload()" style="padding:10px 20px;font-size:16px;cursor:pointer;border-radius:8px;background:#4f46e5;color:white;border:none;">Refresh Halaman</button></div>',
              { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
            );
          });
        })
    );
  }
});

// Message listener for skipWaiting / manual refresh from UI
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
