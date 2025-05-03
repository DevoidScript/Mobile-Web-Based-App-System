// Blood Donation PWA Service Worker
const CACHE_NAME = 'blood-donation-app-v1';

// Files to cache for offline use
const urlsToCache = [
  '/',
  '/index.php',
  '/mobile-app/templates/forms/donor-form-modal.php',
  '/mobile-app/templates/forms/medical-history-modal.php',
  '/mobile-app/templates/forms/declaration-form-modal.php',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  '/manifest.json',
  '/images/icons/icon-192x192.png',
  '/images/icons/icon-512x512.png'
];

// Install event - cache essential files
self.addEventListener('install', event => {
  console.log('[Service Worker] Installing Service Worker');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[Service Worker] Caching app shell');
        return cache.addAll(urlsToCache);
      })
      .then(() => {
        console.log('[Service Worker] Install completed');
        return self.skipWaiting();
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('[Service Worker] Activating Service Worker');
  
  const cacheWhitelist = [CACHE_NAME];
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            console.log('[Service Worker] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('[Service Worker] Claiming clients');
      return self.clients.claim();
    })
  );
});

// Fetch event - serve from cache if available, otherwise fetch from network
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') return;
  
  // Skip cross-origin requests
  if (!event.request.url.startsWith(self.location.origin) && 
      !event.request.url.startsWith('https://cdn.jsdelivr.net')) {
    return;
  }
  
  // For API calls (to Supabase)
  if (event.request.url.includes('/rest/v1/')) {
    // For API requests, use Network First strategy
    event.respondWith(
      fetch(event.request)
        .then(response => {
          return response;
        })
        .catch(() => {
          // If network fails, return offline response
          return new Response(
            JSON.stringify({ 
              offline: true, 
              message: 'You are currently offline. Please try again when you have internet connection.' 
            }),
            { 
              headers: { 'Content-Type': 'application/json' } 
            }
          );
        })
    );
    return;
  }
  
  // For page requests use Cache First strategy
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Cache hit - return the response from the cached version
        if (response) {
          console.log('[Service Worker] Serving from cache:', event.request.url);
          return response;
        }
        
        // Not in cache - fetch from network
        return fetch(event.request)
          .then(networkResponse => {
            // Don't cache if response is not valid
            if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
              return networkResponse;
            }
            
            // Clone the response since it can only be consumed once
            const responseToCache = networkResponse.clone();
            
            // Add response to cache for future use
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
                console.log('[Service Worker] Caching new resource:', event.request.url);
              });
            
            return networkResponse;
          })
          .catch(error => {
            console.log('[Service Worker] Fetch failed:', error);
            
            // For HTML pages, serve the offline page
            if (event.request.headers.get('accept').includes('text/html')) {
              return caches.match('/offline.html');
            }
            
            return new Response('Network error');
          });
      })
  );
});

// Handle background sync for offline form submissions
self.addEventListener('sync', event => {
  if (event.tag === 'medical-history-sync' || event.tag === 'donor-form-sync' || event.tag === 'declaration-form-sync') {
    event.waitUntil(
      // Process pending form submissions here when online
      console.log('[Service Worker] Background sync triggered:', event.tag)
    );
  }
});

// Handle push notifications
self.addEventListener('push', event => {
  const data = event.data.json();
  
  const options = {
    body: data.body || 'New notification',
    icon: '/images/icons/icon-192x192.png',
    badge: '/images/icons/icon-192x192.png',
    vibrate: [100, 50, 100],
    data: {
      url: data.url || '/'
    }
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'Blood Donation App', options)
  );
});

// Handle notification click
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  event.waitUntil(
    clients.openWindow(event.notification.data.url)
  );
}); 