/**
 * Service Worker Registration Utility
 * Handles service worker registration with proper error handling
 * Compatible with Edge, Chrome, and Opera GX
 */

(function() {
    'use strict';

    /**
     * Register Service Worker with error handling
     * @param {string} swPath - Path to service worker file
     * @param {object} options - Registration options
     */
    function registerServiceWorker(swPath, options) {
        options = options || {};

        // Check if service worker is supported
        if (!('serviceWorker' in navigator)) {
            if (options.onError) {
                options.onError(new Error('Service Worker not supported'));
            }
            return Promise.reject(new Error('Service Worker not supported'));
        }

        // Determine service worker path
        const basePath = getBasePath();
        const serviceWorkerPath = swPath || (basePath + 'service-worker.js');

        return navigator.serviceWorker.register(serviceWorkerPath, {
            scope: basePath
        })
        .then(function(registration) {
            console.log('Service Worker registered successfully:', registration.scope);

            // Handle updates
            registration.addEventListener('updatefound', function() {
                const newWorker = registration.installing;
                if (newWorker) {
                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New service worker available
                            if (options.onUpdate) {
                                options.onUpdate(registration);
                            }
                        }
                    });
                }
            });

            // Success callback
            if (options.onSuccess) {
                options.onSuccess(registration);
            }

            return registration;
        })
        .catch(function(error) {
            console.error('Service Worker registration failed:', error);
            
            // Only log error if it's not a 404 (file not found)
            if (error.message && !error.message.includes('404')) {
                console.warn('Service Worker registration error:', error.message);
            }

            if (options.onError) {
                options.onError(error);
            }

            return Promise.reject(error);
        });
    }

    /**
     * Get base path for the application
     */
    function getBasePath() {
        const pathname = window.location.pathname;
        const marker = '/mobile-app/';
        const idx = pathname.indexOf(marker);
        
        if (idx !== -1) {
            return pathname.substring(0, idx + marker.length);
        }
        
        // Fallback: try to infer from current location
        const pathParts = pathname.split('/');
        const mobileAppIdx = pathParts.indexOf('mobile-app');
        
        if (mobileAppIdx !== -1) {
            return '/' + pathParts.slice(1, mobileAppIdx + 2).join('/') + '/';
        }
        
        // Default fallback
        return '/mobile-app/';
    }

    /**
     * Auto-register service worker on page load
     */
    function autoRegister() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                registerServiceWorker();
            });
        } else {
            registerServiceWorker();
        }
    }

    // Export functions
    window.ServiceWorkerRegister = {
        register: registerServiceWorker,
        getBasePath: getBasePath,
        autoRegister: autoRegister
    };

    // Auto-register if in a supported environment
    if (window.location.protocol === 'https:' || 
        window.location.hostname === 'localhost' || 
        window.location.hostname === '127.0.0.1') {
        autoRegister();
    }
})();

