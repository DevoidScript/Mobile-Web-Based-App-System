/**
 * Push Notifications Client-Side Handler
 * 
 * Manages push notification subscriptions for the PWA.
 * Requests permission, subscribes to push, and saves subscription to backend.
 */

// VAPID public key - this should match the server-side config
// This will be injected from PHP in the template
var VAPID_PUBLIC_KEY = null;
var pushVapidKey = null; // Global variable for VAPID key

/**
 * Get the app base path using the registered Service Worker scope when available.
 * Falls back to pathname/script inference.
 */
async function getAppBasePath() {
    try {
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.ready;
            if (registration && registration.scope) {
                const scopeUrl = new URL(registration.scope);
                // Ensure trailing slash
                return scopeUrl.pathname.replace(/\/$/, '/')
            }
        }
    } catch (e) {
        // ignore and fallback
    }
    // Fallback to static resolver
    return apiBaseFromLocation();
}

function apiBaseFromLocation() {
    var marker = '/mobile-app/';
    var pathname = window.location.pathname || '';
    var idx = pathname.indexOf(marker);
    var base = marker; // default
    if (idx !== -1) {
        base = pathname.substring(0, idx + marker.length);
    } else {
        // Fallback: try to infer from script tag src
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].src || '';
            try {
                var url = new URL(src, window.location.origin);
                var pidx = url.pathname.indexOf(marker);
                if (pidx !== -1) {
                    base = url.pathname.substring(0, pidx + marker.length);
                    break;
                }
            } catch (e) {}
        }
    }
    return base.replace(/\/$/, '/');
}

/**
 * Resolve API URL under any base path that contains /mobile-app/
 */
function apiUrl(path) {
	try {
		var base = apiBaseFromLocation();
		return base + 'api/' + path.replace(/^\//, '');
	} catch (e) {
		return '/mobile-app/api/' + path.replace(/^\//, '');
	}
}

/**
 * Convert base64 string to Uint8Array
 * Required for VAPID key format
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

/**
 * Check if push notifications are supported
 */
function isPushNotificationSupported() {
    return 'serviceWorker' in navigator && 
           'PushManager' in window && 
           'Notification' in window;
}

/**
 * Get current notification permission status
 */
function getNotificationPermission() {
    if (!('Notification' in window)) {
        return 'unsupported';
    }
    return Notification.permission;
}

/**
 * Request notification permission from user
 */
async function requestNotificationPermission() {
    if (!('Notification' in window)) {
        console.warn('Notifications not supported');
        return 'unsupported';
    }

    try {
        const permission = await Notification.requestPermission();
        console.log('Notification permission:', permission);
        return permission;
    } catch (error) {
        console.error('Error requesting notification permission:', error);
        return 'denied';
    }
}

/**
 * Subscribe to push notifications
 */
async function subscribeToPush() {
    if (!isPushNotificationSupported()) {
        console.warn('Push notifications not supported');
        return { success: false, error: 'Push notifications not supported' };
    }

    if (!VAPID_PUBLIC_KEY) {
        console.error('VAPID public key not set');
        return { success: false, error: 'VAPID public key not configured' };
    }

    try {
        // Get service worker registration
        const registration = await navigator.serviceWorker.ready;
        
        // Check if already subscribed
        let subscription = await registration.pushManager.getSubscription();
        
        if (!subscription) {
            // Subscribe to push
            console.log('Subscribing to push notifications...');
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
            });
            console.log('Push subscription created:', subscription);
        } else {
            console.log('Already subscribed to push:', subscription);
        }

        // Save subscription to backend
        const saveResult = await saveSubscriptionToBackend(subscription);
        
        if (saveResult.success) {
            console.log('Push subscription saved to backend');
            return { success: true, subscription: subscription };
        } else {
            console.error('Failed to save subscription to backend:', saveResult);
            return { success: false, error: 'Failed to save subscription' };
        }
    } catch (error) {
        console.error('Error subscribing to push:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Save push subscription to backend
 */
async function saveSubscriptionToBackend(subscription) {
    try {
        const base = await getAppBasePath();
        const response = await fetch(base + 'api/save-subscription.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                subscription: subscription.toJSON()
            })
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error saving subscription to backend:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Unsubscribe from push notifications
 */
async function unsubscribeFromPush() {
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        
        if (subscription) {
            await subscription.unsubscribe();
            console.log('Unsubscribed from push notifications');
            return { success: true };
        } else {
            console.log('No active subscription found');
            return { success: true };
        }
    } catch (error) {
        console.error('Error unsubscribing from push:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Initialize push notifications
 * Call this after user login or on dashboard load
 */
async function initializePushNotifications(vapidPublicKey) {
    VAPID_PUBLIC_KEY = vapidPublicKey;
    
    if (!isPushNotificationSupported()) {
        console.warn('Push notifications not supported on this browser');
        return { success: false, error: 'not_supported' };
    }

    // Check current permission
    const permission = getNotificationPermission();
    
    if (permission === 'granted') {
        // Already granted, subscribe immediately
        return await subscribeToPush();
    } else if (permission === 'default') {
        // Not asked yet, will need to prompt user
        console.log('Notification permission not requested yet');
        return { success: false, error: 'permission_required' };
    } else {
        // Denied
        console.warn('Notification permission denied');
        return { success: false, error: 'permission_denied' };
    }
}

/**
 * Prompt user to enable push notifications
 * Shows a UI prompt and requests permission
 */
async function promptForPushNotifications(vapidPublicKey) {
    VAPID_PUBLIC_KEY = vapidPublicKey;
    
    if (!isPushNotificationSupported()) {
        return { success: false, error: 'not_supported' };
    }

    // Request permission
    const permission = await requestNotificationPermission();
    
    if (permission === 'granted') {
        // Subscribe to push
        return await subscribeToPush();
    } else {
        return { success: false, error: 'permission_denied' };
    }
}

/**
 * Fetch VAPID public key from server
 */
async function fetchVapidKey() {
    try {
        const base = await getAppBasePath();
        const response = await fetch(base + 'api/get-vapid-key.php');
        const data = await response.json();
        if (data.success) {
            VAPID_PUBLIC_KEY = data.publicKey;
            pushVapidKey = data.publicKey; // Also set the global variable
            return true;
        }
        return false;
    } catch (error) {
        console.error('Error fetching VAPID key:', error);
        return false;
    }
}

// Export functions for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        isPushNotificationSupported,
        getNotificationPermission,
        requestNotificationPermission,
        subscribeToPush,
        unsubscribeFromPush,
        initializePushNotifications,
        promptForPushNotifications,
        fetchVapidKey
    };
}

