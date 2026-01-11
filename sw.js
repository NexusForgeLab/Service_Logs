const CACHE_NAME = 'service-log-v1';
const ASSETS = [
  '/',
  '/index.php',
  '/assets/style.css',
  '/assets/app.js',
  '/assets/icon-192.png',
  '/assets/icon-512.png'
];

// Install Service Worker
self.addEventListener('install', (evt) => {
  evt.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
    })
  );
});

// Fetch resources
self.addEventListener('fetch', (evt) => {
  evt.respondWith(
    caches.match(evt.request).then((cacheRes) => {
      return cacheRes || fetch(evt.request);
    })
  );
});
