const CACHE_NAME = "siapp-{{APP_SLUG}}-cache-v1";
const STATIC_CACHE = "static-v1";
const RUNTIME_CACHE = "runtime-v1";

// Cache untuk file static aplikasi
const STATIC_ASSETS = ["/", "/index.html", "/manifest.json", "/icons/icon.svg"];

// Install event - cache static assets
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches
      .open(STATIC_CACHE)
      .then((cache) => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting()),
  );
});

// Activate event - cleanup old caches
self.addEventListener("activate", (event) => {
  const currentCaches = [STATIC_CACHE, RUNTIME_CACHE];
  event.waitUntil(
    caches
      .keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (!currentCaches.includes(cacheName)) {
              return caches.delete(cacheName);
            }
          }),
        );
      })
      .then(() => self.clients.claim()),
  );
});

// Fetch event - handle all requests
self.addEventListener("fetch", (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Handle static assets (cache first)
  if (STATIC_ASSETS.includes(url.pathname)) {
    event.respondWith(
      caches
        .open(STATIC_CACHE)
        .then((cache) => cache.match(request))
        .then((response) => response || fetch(request)),
    );
    return;
  }

  // Handle Google Apps Script (network first with cache fallback)
  if (url.hostname === "script.google.com") {
    event.respondWith(
      caches.open(RUNTIME_CACHE).then((cache) => {
        return fetch(request)
          .then((response) => {
            // Clone response karena stream hanya bisa dibaca sekali
            const responseClone = response.clone();

            // Cache response jika berhasil
            if (response.status === 200) {
              cache.put(request, responseClone);
            }

            return response;
          })
          .catch(() => {
            // Jika network gagal, coba dari cache
            return cache.match(request);
          });
      }),
    );
    return;
  }

  // Handle resources lain dari Google (fonts, cdn, etc) - cache first
  if (url.hostname.includes("google") || url.hostname.includes("gstatic")) {
    event.respondWith(
      caches.open(RUNTIME_CACHE).then((cache) => {
        return cache.match(request).then((response) => {
          if (response) {
            // Ada di cache, tapi tetap update di background
            fetch(request)
              .then((fetchResponse) => {
                if (fetchResponse.status === 200) {
                  cache.put(request, fetchResponse.clone());
                }
              })
              .catch(() => {});
            return response;
          }

          // Tidak ada di cache, fetch dan cache
          return fetch(request).then((fetchResponse) => {
            if (fetchResponse.status === 200) {
              cache.put(request, fetchResponse.clone());
            }
            return fetchResponse;
          });
        });
      }),
    );
    return;
  }

  // Default: network first
  event.respondWith(fetch(request));
});
