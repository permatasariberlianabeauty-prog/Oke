/**
 * NOXARA - Service Worker
 * Cache Strategy: cache-first for static assets, network-first for pages
 */

const CACHE_NAME    = 'noxara-v1';
const CACHE_STATIC  = 'noxara-static-v1';

// ============================================================
// ASSETS TO CACHE ON INSTALL
// ============================================================
const STATIC_ASSETS = [
  '/assets/css/style.css',
  '/assets/css/animations.css',
  '/assets/css/mobile.css',
  '/assets/js/main.js',
  '/assets/js/animations.js',
  '/assets/img/icons/icons.svg',
  '/manifest.json',
  // Google Fonts are cached dynamically via fetch handler
];

// ============================================================
// PATHS THAT SHOULD NEVER BE CACHED
// ============================================================
const NEVER_CACHE = [
  '/pages/',
  '/admin/',
  '/api/',
  '/auth/',
  '/deposit',
  '/withdraw',
  '/dashboard',
  '/install/',
  '/config/',
  '/logs/',
  '/backups/',
  '/database/',
];

// ============================================================
// INSTALL EVENT
// ============================================================
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_STATIC).then((cache) => {
      console.log('[SW] Caching static assets');
      return cache.addAll(STATIC_ASSETS.map(url => new Request(url, { cache: 'reload' })));
    }).then(() => self.skipWaiting())
  );
});

// ============================================================
// ACTIVATE EVENT — cleanup old caches
// ============================================================
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter(name => name !== CACHE_NAME && name !== CACHE_STATIC)
          .map(name => {
            console.log('[SW] Deleting old cache:', name);
            return caches.delete(name);
          })
      );
    }).then(() => self.clients.claim())
  );
});

// ============================================================
// FETCH EVENT
// ============================================================
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Only handle same-origin requests
  if (url.origin !== location.origin) {
    // Allow Google Fonts through with cache
    if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
      event.respondWith(cacheFirst(request, 'noxara-fonts-v1'));
    }
    return;
  }

  // Never cache these paths — always network
  const shouldSkip = NEVER_CACHE.some(path => url.pathname.startsWith(path));
  if (shouldSkip || request.method !== 'GET') {
    event.respondWith(networkOnly(request));
    return;
  }

  // Static assets (CSS, JS, images, fonts) — cache first
  const isStaticAsset = /\.(css|js|svg|png|jpg|jpeg|webp|gif|woff2?|ttf|otf|ico)$/i.test(url.pathname);
  if (isStaticAsset) {
    event.respondWith(cacheFirst(request, CACHE_STATIC));
    return;
  }

  // PHP pages — network first with cache fallback
  if (url.pathname.endsWith('.php') || url.pathname === '/') {
    event.respondWith(networkFirst(request));
    return;
  }

  // Default — network first
  event.respondWith(networkFirst(request));
});

// ============================================================
// STRATEGIES
// ============================================================

/**
 * Cache First — serve from cache, update cache in background
 */
async function cacheFirst(request, cacheName = CACHE_STATIC) {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch (err) {
    return new Response('Offline — resource not cached', { status: 503 });
  }
}

/**
 * Network First — try network, fall back to cache
 */
async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone());
    }
    return response;
  } catch (err) {
    const cached = await caches.match(request);
    if (cached) return cached;
    // Return offline page if available
    const offlinePage = await caches.match('/errors/offline.html');
    return offlinePage || new Response('<h1>Offline</h1>', {
      status: 503,
      headers: { 'Content-Type': 'text/html' },
    });
  }
}

/**
 * Network Only — no caching
 */
async function networkOnly(request) {
  try {
    return await fetch(request);
  } catch (err) {
    return new Response('Network error', { status: 503 });
  }
}

// ============================================================
// MESSAGE EVENT — force cache clear from app
// ============================================================
self.addEventListener('message', (event) => {
  if (event.data?.action === 'clearCache') {
    caches.keys().then(keys => Promise.all(keys.map(k => caches.delete(k)))).then(() => {
      event.ports[0]?.postMessage({ cleared: true });
    });
  }
  if (event.data?.action === 'skipWaiting') {
    self.skipWaiting();
  }
});
