const CACHE_NAME = 'gym-master-v3';
const assetsToCache = [
  '/',
  '/index.php',
  '/css/adminlte.rtl.css',
  '/js/adminlte.js',
  '/manifest.json'
];

// تثبيت الـ Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(assetsToCache);
    })
  );
  self.skipWaiting();
});

// تفعيل وتطهير الكاش القديم
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// معالجة الـ Fetch مع تجاهل تام لأي روابط Base64 أو بيانات غير HTTP
self.addEventListener('fetch', (event) => {
  const requestUrl = event.request.url;

  // تجاهل أي رابط لا يبدأ بـ http أو https (مثل روابط Base64 للصور data:image) لتجنب أخطاء الـ Fetch
  if (!requestUrl.startsWith('http://') && !requestUrl.startsWith('https://')) {
    return;
  }

  const url = new URL(requestUrl);

  // صفحات الـ PHP توجه للسيرفر مباشرة
  if (url.pathname.endsWith('.php') || event.request.method !== 'GET') {
    event.respondWith(fetch(event.request));
    return;
  }

  // للملفات الثابتة فقط
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      return cachedResponse || fetch(event.request);
    })
  );
});
