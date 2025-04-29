/**
 * Service Worker for the Progressive Web App
 * Enables offline functionality and caching
 */

// Cache version
const CACHE_VERSION = 'v1';
const CACHE_NAME = `app-cache-${CACHE_VERSION}`;

// Resources to cache initially
const INITIAL_CACHED_RESOURCES = [
    '/',
    '/index.php',
    '/assets/css/styles.css',
    '/assets/js/app.js',
    '/assets/icons/icon-192x192.png',
    '/assets/icons/icon-512x512.png',
    '/manifest.json',
    '/templates/404.php'
];

// Install event - cache initial resources
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Service Worker: Caching initial resources');
                return cache.addAll(INITIAL_CACHED_RESOURCES);
            })
            .then(() => {
                // Skip waiting to activate service worker immediately
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Service Worker: Deleting old cache', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // Claim clients to control all tabs open in the browser
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event) => {
    // Only cache GET requests
    if (event.request.method !== 'GET') {
        return;
    }
    
    // Skip some URLs that shouldn't be cached
    const url = new URL(event.request.url);
    if (url.pathname.startsWith('/api/')) {
        // For API requests, try network first, then fall back to cached response
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    // Don't cache API responses by default
                    return response;
                })
                .catch(() => {
                    // If network request fails, try to get from cache
                    return caches.match(event.request);
                })
        );
        return;
    }
    
    // For other requests, use "Cache, falling back to network" strategy
    event.respondWith(
        caches.match(event.request)
            .then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                
                // Not in cache, fetch from network
                return fetch(event.request)
                    .then((fetchResponse) => {
                        // Return without caching if response is not valid
                        if (!fetchResponse || fetchResponse.status !== 200 || fetchResponse.type !== 'basic') {
                            return fetchResponse;
                        }
                        
                        // Clone the response as it's a stream and can only be consumed once
                        const responseToCache = fetchResponse.clone();
                        
                        // Cache the fetched resource
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(event.request, responseToCache);
                            });
                        
                        return fetchResponse;
                    })
                    .catch((error) => {
                        console.error('Service Worker: Fetch failed', error);
                        
                        // Return a custom offline page if available and the request is for a document
                        if (event.request.mode === 'navigate') {
                            return caches.match('/templates/404.php');
                        }
                        
                        // Otherwise just return the error
                        throw error;
                    });
            })
    );
});

// Background sync for offline form submissions
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-forms') {
        event.waitUntil(syncFormData());
    }
});

// Function to sync cached form submissions
async function syncFormData() {
    try {
        // Open IndexedDB
        const db = await openDB();
        const tx = db.transaction('formData', 'readwrite');
        const store = tx.objectStore('formData');
        
        // Get all form submissions
        const submissions = await store.getAll();
        
        for (const submission of submissions) {
            try {
                // Attempt to send the data
                const response = await fetch(submission.url, {
                    method: submission.method,
                    headers: submission.headers,
                    body: submission.body
                });
                
                if (response.ok) {
                    // If successful, delete from IndexedDB
                    await store.delete(submission.id);
                }
            } catch (error) {
                console.error('Failed to sync submission', error);
                // Keep in IndexedDB for next sync attempt
            }
        }
        
        await tx.complete;
        db.close();
    } catch (error) {
        console.error('Error during form sync', error);
    }
}

// Helper function to open IndexedDB
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('offlineFormData', 1);
        
        request.onerror = (event) => {
            reject('Error opening IndexedDB');
        };
        
        request.onsuccess = (event) => {
            resolve(event.target.result);
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            // Create object store for offline form submissions
            if (!db.objectStoreNames.contains('formData')) {
                db.createObjectStore('formData', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

// Push notification event handler
self.addEventListener('push', (event) => {
    if (!event.data) return;
    
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: '/assets/icons/icon-192x192.png',
        badge: '/assets/icons/badge.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/'
        }
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click event handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    event.waitUntil(
        clients.matchAll({ type: 'window' })
            .then((clientList) => {
                // Check if a window is already open and navigate to the URL
                for (const client of clientList) {
                    if (client.url === event.notification.data.url && 'focus' in client) {
                        return client.focus();
                    }
                }
                // If no window is open, open a new one
                if (clients.openWindow) {
                    return clients.openWindow(event.notification.data.url);
                }
            })
    );
}); 