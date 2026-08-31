const CACHE_NAME = 'gym-master-v2';
const assetsToCache = [
  '/',
  '/index.php',
  '/css/adminlte.rtl.css',
  '/js/adminlte.js',
  '/manifest.json'
];

// تثبيت الـ Service Worker وحفظ الملفات الثابتة فقط
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

// جلب الملفات: استثناء تام لصفحات الـ PHP وروابط الـ Base64 (data:)
self.addEventListener('fetch', (event) => {
  const requestUrl = event.request.url;
  
  // تجاهل أي طلب لا يبدأ بـ http أو https (مثل روابط data:image للصور المخزنة كـ Base64)
  if (!requestUrl.startsWith('http')) {
    return;
  }

  const url = new URL(requestUrl);
  
  // إذا كانت الصفحة PHP أو الطلب ليس GET، وجه الطلب للسيرفر مباشرة لتحديث البيانات
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
