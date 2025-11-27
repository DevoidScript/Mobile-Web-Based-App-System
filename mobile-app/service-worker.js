/**
 * Service Worker for the Progressive Web App
 * Enables offline functionality and caching
 */

// Cache version - increment to force service worker update
const CACHE_VERSION = 'v4';
const CACHE_NAME = `app-cache-${CACHE_VERSION}`;

// Resources to cache initially - optimized for LCP and fast navigation
const INITIAL_CACHED_RESOURCES = [
    '/mobile-app/',
    '/mobile-app/index.php',
    '/mobile-app/assets/css/styles.css',
    '/mobile-app/assets/js/app.js',
    '/mobile-app/assets/icons/icon-192x192.png',
    '/mobile-app/assets/icons/icon-512x512.png',
    '/mobile-app/assets/icons/redcrosslogo.jpg', // LCP image
    '/mobile-app/assets/images/donate.png', // Common image
    '/mobile-app/manifest.json',
    '/mobile-app/templates/404.php'
];

// Install event - cache initial resources
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                // Caching initial resources
                // Cache resources individually to handle missing files gracefully
                return Promise.allSettled(
                    INITIAL_CACHED_RESOURCES.map(resource => 
                        cache.add(resource).catch(error => {
                            console.warn(`Service Worker: Failed to cache ${resource}:`, error);
                            return null; // Continue with other resources
                        })
                    )
                );
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
    // Only handle GET
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);
    const accept = event.request.headers.get('accept') || '';
    
    // Only cache http/https URLs, not chrome-extension://, file://, or other schemes
    const isValidScheme = url.protocol === 'http:' || url.protocol === 'https:';
    if (!isValidScheme) {
        // For unsupported schemes (like chrome-extension://), just fetch without caching
        event.respondWith(fetch(event.request));
        return;
    }

    // Identify API endpoints under app path; never cache API
    const isApi = (
        url.pathname.indexOf('/mobile-app/api/') !== -1 ||
        url.pathname.indexOf('/Mobile-Web-Based-App-System/mobile-app/api/') !== -1 ||
        url.pathname.startsWith('/api/')
    );

    if (isApi) {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(event.request))
        );
        return;
    }

    // For HTML navigations and pages, use network-first and do NOT cache to avoid serving other users' sessions
    const isHtml = event.request.mode === 'navigate' || accept.includes('text/html');
    if (isHtml) {
        event.respondWith(
            fetch(event.request).catch(() => caches.match('/templates/404.php'))
        );
        return;
    }

    // Static assets: Cache-first with network fallback (optimized for slow connections)
    // For images, CSS, and JS - serve from cache immediately, update in background
    event.respondWith(
        caches.match(event.request).then((cached) => {
            // If cached, return immediately and update in background (stale-while-revalidate)
            if (cached) {
                // Update cache in background for next visit
                fetch(event.request).then((resp) => {
                    if (resp && resp.status === 200 && resp.type === 'basic') {
                        const respClone = resp.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            try {
                                const finalCheckUrl = new URL(event.request.url);
                                if (finalCheckUrl.protocol === 'http:' || finalCheckUrl.protocol === 'https:') {
                                    cache.put(event.request, respClone).catch(() => {});
                                }
                            } catch (e) {}
                        });
                    }
                }).catch(() => {}); // Silently fail background update
                return cached;
            }
            // Not cached - fetch from network
            return fetch(event.request).then((resp) => {
                if (!resp || resp.status !== 200 || resp.type !== 'basic') return resp;
                
                // Multiple safety checks before caching
                const requestUrl = event.request.url;
                const responseUrl = resp.url;
                
                // Check request URL scheme
                try {
                    const reqUrlObj = new URL(requestUrl);
                    if (reqUrlObj.protocol !== 'http:' && reqUrlObj.protocol !== 'https:') {
                        return resp; // Don't cache unsupported schemes
                    }
                } catch (e) {
                    // Invalid URL, don't cache
                    return resp;
                }
                
                // Check response URL scheme
                try {
                    const respUrlObj = new URL(responseUrl);
                    if (respUrlObj.protocol !== 'http:' && respUrlObj.protocol !== 'https:') {
                        return resp; // Don't cache unsupported schemes
                    }
                } catch (e) {
                    // Invalid URL, don't cache
                    return resp;
                }
                
                // Additional check: verify the request URL doesn't contain unsupported schemes
                if (requestUrl.startsWith('chrome-extension:') || 
                    requestUrl.startsWith('chrome:') ||
                    requestUrl.startsWith('file:') ||
                    requestUrl.startsWith('moz-extension:') ||
                    requestUrl.startsWith('safari-extension:')) {
                    return resp; // Don't cache extension URLs
                }
                
                const respClone = resp.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    // Final check before attempting to cache
                    const finalCheckUrl = new URL(event.request.url);
                    if (finalCheckUrl.protocol === 'http:' || finalCheckUrl.protocol === 'https:') {
                        cache.put(event.request, respClone).catch((error) => {
                            // Silently fail if caching is not possible
                            // This prevents console errors for unsupported schemes
                            if (!error.message.includes('chrome-extension') && 
                                !error.message.includes('unsupported')) {
                                console.warn('Service Worker: Could not cache resource:', error.message);
                            }
                        });
                    }
                });
                return resp;
            }).catch((error) => {
                // Handle fetch errors
                return caches.match(event.request).catch(() => {
                    throw error;
                });
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
    console.log('[Service Worker] Push notification received');
    
    if (!event.data) {
        console.log('[Service Worker] Push event has no data');
        return;
    }
    
    try {
        const data = event.data.json();
        console.log('[Service Worker] Push data:', data);
        
        const options = {
            body: data.body || 'You have a new notification',
            icon: data.icon || '/mobile-app/assets/icons/icon-192x192.png',
            badge: data.badge || '/mobile-app/assets/icons/icon-192x192.png',
            vibrate: data.vibrate || [100, 50, 100],
            tag: data.tag || 'default',
            requireInteraction: data.requireInteraction || false,
            actions: data.actions || [],
            data: {
                url: data.url || '/mobile-app/',
                blood_drive_id: data.blood_drive_id || null,
                timestamp: data.timestamp || Date.now()
            }
        };
        
        event.waitUntil(
            (async () => {
                // Check if app is open and send in-app notification
                const allClients = await clients.matchAll({ type: 'window', includeUncontrolled: true });
                let deliveredToOpenApp = false;
                
                for (const client of allClients) {
                    // Send message to open app tabs
                    client.postMessage({ 
                        type: 'PUSH_IN_APP', 
                        payload: data 
                    });
                    deliveredToOpenApp = true;
                }
                
                // Still show system notification (even if app is open)
                await self.registration.showNotification(data.title || 'Blood Donation App', options);
                
                console.log('[Service Worker] Notification delivered to', deliveredToOpenApp ? 'open app and system' : 'system only');
            })()
        );
    } catch (error) {
        console.error('[Service Worker] Error parsing push data:', error);
    }
});

// Notification click event handler
self.addEventListener('notificationclick', (event) => {
    console.log('[Service Worker] Notification clicked');
    event.notification.close();
    
    const urlToOpen = event.notification.data.url || '/mobile-app/';
    
    event.waitUntil(
        clients.matchAll({ 
            type: 'window',
            includeUncontrolled: true
        })
        .then((clientList) => {
            console.log('[Service Worker] Found', clientList.length, 'open windows');
            
            // Check if there's already a window open with this URL
            for (const client of clientList) {
                const clientUrl = new URL(client.url);
                const targetUrl = new URL(urlToOpen, self.location.origin);
                
                if (clientUrl.pathname === targetUrl.pathname && 'focus' in client) {
                    console.log('[Service Worker] Focusing existing window');
                    return client.focus();
                }
            }
            
            // If no matching window, check if any app window is open and navigate it
            if (clientList.length > 0 && 'navigate' in clientList[0]) {
                console.log('[Service Worker] Navigating existing window to:', urlToOpen);
                return clientList[0].navigate(urlToOpen).then(client => client.focus());
            }
            
            // Otherwise, open a new window
            if (clients.openWindow) {
                console.log('[Service Worker] Opening new window:', urlToOpen);
                return clients.openWindow(urlToOpen);
            }
        })
        .catch((error) => {
            console.error('[Service Worker] Error handling notification click:', error);
        })
    );
}); 
                    // Send message to open app tabs
                    client.postMessage({ 
                        type: 'PUSH_IN_APP', 
                        payload: data 
                    });
                    deliveredToOpenApp = true;
                }
                
                // Still show system notification (even if app is open)
                await self.registration.showNotification(data.title || 'Blood Donation App', options);
                
                console.log('[Service Worker] Notification delivered to', deliveredToOpenApp ? 'open app and system' : 'system only');
            })()
        );
    } catch (error) {
        console.error('[Service Worker] Error parsing push data:', error);
    }
});

// Notification click event handler
self.addEventListener('notificationclick', (event) => {
    console.log('[Service Worker] Notification clicked');
    event.notification.close();
    
    const urlToOpen = event.notification.data.url || '/mobile-app/';
    
    event.waitUntil(
        clients.matchAll({ 
            type: 'window',
            includeUncontrolled: true
        })
        .then((clientList) => {
            console.log('[Service Worker] Found', clientList.length, 'open windows');
            
            // Check if there's already a window open with this URL
            for (const client of clientList) {
                const clientUrl = new URL(client.url);
                const targetUrl = new URL(urlToOpen, self.location.origin);
                
                if (clientUrl.pathname === targetUrl.pathname && 'focus' in client) {
                    console.log('[Service Worker] Focusing existing window');
                    return client.focus();
                }
            }
            
            // If no matching window, check if any app window is open and navigate it
            if (clientList.length > 0 && 'navigate' in clientList[0]) {
                console.log('[Service Worker] Navigating existing window to:', urlToOpen);
                return clientList[0].navigate(urlToOpen).then(client => client.focus());
            }
            
            // Otherwise, open a new window
            if (clients.openWindow) {
                console.log('[Service Worker] Opening new window:', urlToOpen);
                return clients.openWindow(urlToOpen);
            }
        })
        .catch((error) => {
            console.error('[Service Worker] Error handling notification click:', error);
        })
    );
}); 