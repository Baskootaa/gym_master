// Service Worker بسيط بدون اعتراض للـ Fetch لتجنب أي مشاكل في العرض
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.clients.claim();
});
