self.addEventListener('install', (e) => {
  console.log('[Service Worker] Installed');
});

self.addEventListener('fetch', (e) => {
  // تفعيل الجلب العادي للملفات والبيانات
  e.respondWith(fetch(e.request).catch(() => caches.match(e.request)));
});