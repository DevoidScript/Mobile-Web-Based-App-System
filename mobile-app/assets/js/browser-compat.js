/**
 * Browser Compatibility Utility
 * Ensures compatibility with Edge, Chrome, and Opera GX (all Chromium-based)
 * Provides feature detection and graceful degradation
 */

(function() {
    'use strict';

    /**
     * Browser Compatibility Checker
     */
    const BrowserCompat = {
        /**
         * Detect if browser is Chromium-based (Chrome, Edge, Opera GX)
         */
        isChromium: function() {
            return /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor) ||
                   /Edg/.test(navigator.userAgent) ||
                   /OPR/.test(navigator.userAgent) ||
                   /Chromium/.test(navigator.userAgent);
        },

        /**
         * Check if Service Worker is supported
         */
        supportsServiceWorker: function() {
            return 'serviceWorker' in navigator;
        },

        /**
         * Check if Push Notifications are supported
         */
        supportsPushNotifications: function() {
            return 'serviceWorker' in navigator && 
                   'PushManager' in window && 
                   'Notification' in window;
        },

        /**
         * Check if IndexedDB is supported
         */
        supportsIndexedDB: function() {
            return 'indexedDB' in window;
        },

        /**
         * Check if Fetch API is supported
         */
        supportsFetch: function() {
            return 'fetch' in window;
        },

        /**
         * Check if Promise is supported
         */
        supportsPromise: function() {
            return typeof Promise !== 'undefined';
        },

        /**
         * Check if localStorage is supported
         */
        supportsLocalStorage: function() {
            try {
                const test = '__localStorage_test__';
                localStorage.setItem(test, test);
                localStorage.removeItem(test);
                return true;
            } catch (e) {
                return false;
            }
        },

        /**
         * Check if CSS Grid is supported
         */
        supportsCSSGrid: function() {
            return CSS.supports('display', 'grid');
        },

        /**
         * Check if Flexbox is supported
         */
        supportsFlexbox: function() {
            return CSS.supports('display', 'flex');
        },

        /**
         * Check if CSS Variables are supported
         */
        supportsCSSVariables: function() {
            return CSS.supports('color', 'var(--fake-var)');
        },

        /**
         * Get browser name
         */
        getBrowserName: function() {
            const ua = navigator.userAgent;
            if (/Edg/.test(ua)) return 'Edge';
            if (/OPR/.test(ua)) return 'Opera';
            if (/Chrome/.test(ua)) return 'Chrome';
            if (/Firefox/.test(ua)) return 'Firefox';
            if (/Safari/.test(ua)) return 'Safari';
            return 'Unknown';
        },

        /**
         * Get browser version
         */
        getBrowserVersion: function() {
            const ua = navigator.userAgent;
            const match = ua.match(/(?:Chrome|Edg|OPR)\/(\d+)/);
            return match ? parseInt(match[1], 10) : null;
        },

        /**
         * Check if browser meets minimum requirements
         */
        meetsMinimumRequirements: function() {
            const version = this.getBrowserVersion();
            // Chromium-based browsers need at least version 80 for full PWA support
            if (this.isChromium() && version && version < 80) {
                console.warn('Browser version may not fully support all PWA features. Please update your browser.');
                return false;
            }
            return this.supportsPromise() && 
                   this.supportsFetch() && 
                   this.supportsLocalStorage() &&
                   this.supportsFlexbox();
        },

        /**
         * Initialize compatibility checks and show warnings if needed
         */
        init: function() {
            // Check minimum requirements
            if (!this.meetsMinimumRequirements()) {
                console.warn('Browser Compatibility: Some features may not work properly. Please update your browser.');
            }

            // Log browser info in development
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.log('Browser:', this.getBrowserName(), this.getBrowserVersion());
                console.log('Features:', {
                    serviceWorker: this.supportsServiceWorker(),
                    pushNotifications: this.supportsPushNotifications(),
                    indexedDB: this.supportsIndexedDB(),
                    fetch: this.supportsFetch(),
                    cssGrid: this.supportsCSSGrid(),
                    flexbox: this.supportsFlexbox(),
                    cssVariables: this.supportsCSSVariables()
                });
            }

            // Add browser class to body for CSS targeting if needed
            document.body.classList.add('browser-' + this.getBrowserName().toLowerCase());
        }
    };

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            BrowserCompat.init();
        });
    } else {
        BrowserCompat.init();
    }

    // Export to window for global access
    window.BrowserCompat = BrowserCompat;
})();

