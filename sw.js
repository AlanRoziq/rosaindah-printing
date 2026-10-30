/**
 * Service Worker — Rosa Indah Printing PWA
 * Menggunakan strategi Network-First (Real-Time Sync):
 * Setiap perubahan di website (produk, harga, portofolio, promo, status pesanan, maupun desain)
 * otomatis langsung tersinkronisasi di aplikasi yang diinstal pengguna.
 * Cache digunakan sebagai percepatan aset dan cadangan saat koneksi terputus.
 */

const CACHE_NAME = 'rosaindah-pwa-v1';
const STATIC_PRECACHE = [
  './assets/images/logo/icon-192x192.png',
  './assets/images/logo/icon-512x512.png',
  './assets/images/logo/apple-touch-icon.png'
];

self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_PRECACHE).catch(() => {});
    })
  );
});

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
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Jangan intercept request selain GET (seperti POST form login, keranjang, checkout, admin)
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);

  // Abaikan skema non-http/https (misal chrome-extension://)
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // Jangan cache halaman admin agar selalu langsung dari server
  if (url.pathname.includes('/admin')) {
    return;
  }

  // Strategi Network-First: selalu ambil data terbaru dari website/server terlebih dahulu.
  // Jika berhasil, simpan salinan terbaru ke cache. Jika offline, baru gunakan cache.
  event.respondWith(
    fetch(req)
      .then((networkResponse) => {
        if (
          networkResponse &&
          networkResponse.status === 200 &&
          url.origin === self.location.origin
        ) {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(req, responseClone).catch(() => {});
          });
        }
        return networkResponse;
      })
      .catch(() => {
        return caches.match(req);
      })
  );
});
