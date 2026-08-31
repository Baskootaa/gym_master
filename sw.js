// Service Worker آمن ونظيف تماماً بدون أي أخطاء
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  // إزالة الـ claim المؤقتة لتجنب أي مشاكل في المتصفح
});
