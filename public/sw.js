// 仅缓存静态资源，避免将包含余额/登录状态的动态页面缓存成旧快照
const CACHE_NAME = 'ssjizhang-cache-v2';
const ASSETS_TO_CACHE = [
  '/assets/css/app.css'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS_TO_CACHE))
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
          return null;
        })
      )
    )
  );
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') {
    return;
  }

  const url = new URL(event.request.url);

  // 所有导航请求（HTML 页面）一律走网络，避免缓存首页/记账页 HTML
  if (event.request.mode === 'navigate') {
    event.respondWith(fetch(event.request));
    return;
  }

  // 对 PHP 等动态接口也始终走网络，确保数据实时
  if (url.pathname.endsWith('.php')) {
    event.respondWith(fetch(event.request));
    return;
  }

  // 对静态资源采用 cache-first 策略
  event.respondWith(
    caches.match(event.request).then(cachedResponse => {
      if (cachedResponse) {
        return cachedResponse;
      }
      return fetch(event.request).catch(() => cachedResponse || Response.error());
    })
  );
});
