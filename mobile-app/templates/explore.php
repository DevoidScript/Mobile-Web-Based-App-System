<?php
/**
 * Explore page for the Red Cross Mobile App
 * This page will contain various exploration features
 * 
 * MOVED TO TEMPLATES:
 * This file has been moved to the templates directory for better organization.
 * Paths have been adjusted to maintain functionality.
 */

// Set error reporting in development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration files - adjusted paths for templates directory
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Extra security - regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Check if user is logged in and redirect if not
if (!is_logged_in()) {
    // Set a session flag to indicate they were redirected from explore
    $_SESSION['redirected_from'] = 'explore';
    
    // Redirect to login page
    header('Location: ../index.php?error=Please login to access the explore page');
    exit;
}

// Get user data
$user = $_SESSION['user'] ?? null;
$donor_details = $_SESSION['donor_details'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Enhanced viewport settings for better mobile rendering -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF0000">
    <!-- Cache control to prevent back button access -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Red Cross Explore</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="Red Cross Mobile Application - Explore">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <!-- Resource hints for faster loading on slow connections -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="//fonts.gstatic.com" crossorigin>
    <!-- Preload critical resources -->
    <link rel="preload" href="../assets/icons/redcrosslogo.jpg" as="image">
    <link rel="preload" href="../assets/css/styles.css" as="style">
    <link rel="preload" href="../assets/js/app.js" as="script">
    <style>
        /* 
         * Mobile-optimized styles for the Red Cross Explore page
         * Designed specifically for mobile phone displays with touch interactions
         * Includes responsive design elements for various screen sizes
         */
        
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
            font-size: 16px;
            -webkit-tap-highlight-color: transparent;
        }
        
        .header {
            background-color: #FF0000;
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
            width: 100%;
            box-sizing: border-box;
            z-index: 100;
        }
        
        .logo-small {
            width: 40px;
            height: 40px;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            object-fit: contain;
            border-radius: 50%;
            background-color: white;
        }
        
        .header h1 {
            margin: 0;
            font-size: 20px;
            padding: 0 40px;
        }
        
        .explore-container {
            padding: 15px;
            margin-bottom: 80px; /* More space for nav bar */
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            text-align: left;
        }
        
        .location-bar {
            margin-bottom: 20px;
            font-size: 16px;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: background-color 0.2s;
            user-select: none;
        }
        
        .location-bar:active {
            background-color: #f0f0f0;
        }
        
        .location-bar:hover {
            background-color: #fafafa;
        }
        
        .location-bar.loading {
            opacity: 0.7;
            cursor: wait;
        }
        
        .location-bar.loading::after {
            content: '...';
            animation: dots 1.5s steps(4, end) infinite;
        }
        
        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #D50000;
            margin-bottom: 15px;
        }

        .cards-container {
            position: relative;
            margin-bottom: 10px;
            overflow: hidden;
            touch-action: pan-y pinch-zoom;
        }

        .blood-center-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        
        .blood-center-card.active {
            display: block;
            opacity: 1;
        }
        
        .blood-center-card a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .center-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .center-info {
            padding: 15px;
        }

        .center-info h3 {
            margin: 0 0 5px;
            font-size: 16px;
            font-weight: bold;
        }

        .center-info p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }

        .pagination-dots {
            text-align: center;
            margin-bottom: 25px;
        }

        .dot {
            height: 8px;
            width: 8px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            margin: 0 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .dot.active {
            background-color: #D50000;
        }
        
        .dot:hover {
            background-color: #999;
        }
        
        .dot.active:hover {
            background-color: #B00000;
        }

        .info-cards-container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .info-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            width: 48%;
            box-sizing: border-box;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            text-decoration: none;
            color: inherit;
        }

        .info-card-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .info-card h4 {
            color: #D50000;
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            line-height: 1.3;
        }
        
        .navigation-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #000000;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
            height: 60px;
            box-sizing: border-box;
        }
        
        .nav-button {
            color: white;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 33.33%;
            padding: 5px 0;
            touch-action: manipulation;
            text-decoration: none;
        }
        
        .nav-button:active {
            opacity: 0.7;
        }
        
        .nav-icon {
            font-size: 24px;
            margin-bottom: 2px;
        }
        
        .nav-label {
            font-size: 10px;
            text-align: center;
        }
        
        .nav-button.active {
            color: #FF0000;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../assets/icons/redcrosslogo.jpg" alt="Philippine Red Cross Logo" class="logo-small" width="40" height="40" loading="eager" fetchpriority="high">
        <h1>Discover</h1>
    </div>
    
    <div class="explore-container">
        <div class="location-bar">
            <span>📍</span> Bonifacio Dr, Iloilo City Proper, Iloilo City, Iloilo
        </div>

        <h2 class="section-title">Find Blood Centers</h2>

        <div class="cards-container" id="cardsContainer">
            <div class="blood-center-card active" data-index="0">
                <img src="../assets/images/donate.png" alt="Blood Donation Drive" class="center-image" width="600" height="180" loading="lazy" fetchpriority="high">
                <div class="center-info">
                    <h3>Philippine Red Cross Iloilo Chapter</h3>
                    <p>Bonifacio Dr, Iloilo City Proper, Iloilo City, Iloilo</p>
                </div>
            </div>
            <div class="blood-center-card" data-index="1">
                <a href="https://redcross.org.ph" target="_blank" rel="noopener noreferrer">
                    <img src="../assets/images/prc-site.jpg" alt="Philippine Red Cross Website" class="center-image" width="600" height="180" loading="lazy">
                    <div class="center-info">
                        <h3>Philippine Red Cross Official Website</h3>
                        <p>Visit our official website for more information and services</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="pagination-dots">
            <span class="dot active" data-index="0"></span>
            <span class="dot" data-index="1"></span>
        </div>

        <div class="info-cards-container">
            <a href="tips-guide.php" class="info-card">
                <div class="info-card-icon">💡</div>
                <h4>Donation Tips &amp; Eligibility Guide</h4>
            </a>
            <a href="faq.php" class="info-card">
                <div class="info-card-icon">❓</div>
                <h4>Frequently Asked Questions</h4>
            </a>
        </div>
    </div>
    
    <!-- Mobile-optimized bottom navigation bar -->
    <div class="navigation-bar">
        <a href="dashboard.php" class="nav-button">
            <div class="nav-icon">🏠</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="explore.php" class="nav-button active">
            <div class="nav-icon">🔍</div>
            <div class="nav-label">Discover</div>
        </a>
        <a href="profile.php" class="nav-button">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
    
    <!-- Scripts - Defer for non-blocking loading -->
    <script src="../assets/js/app.js" defer></script>
    <!-- Register Service Worker for PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../service-worker.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('ServiceWorker registration failed: ', error);
                    });
            });
        }
        
        // Prevent back button navigation
        (function preventBackNavigation() {
            window.history.pushState({page: 'explore'}, 'Explore', window.location.href);
            
            window.addEventListener('popstate', function(e) {
                window.history.pushState({page: 'explore'}, 'Explore', window.location.href);
            });
            
            window.onpageshow = function(event) {
                if (event.persisted) {
                    window.location.reload();
                }
            };
            
            window.focus();
        })();
        
        // Pagination dots functionality with swipe support
        (function initPagination() {
            const dots = document.querySelectorAll('.dot');
            const cards = document.querySelectorAll('.blood-center-card');
            const cardsContainer = document.getElementById('cardsContainer');
            
            let currentIndex = 0;
            let touchStartX = 0;
            let touchStartY = 0;
            let touchEndX = 0;
            let touchEndY = 0;
            const minSwipeDistance = 50; // Minimum distance in pixels for a swipe
            let isSwiping = false;
            
            // Function to switch to a specific card
            function switchToCard(index) {
                if (index < 0 || index >= cards.length) return;
                
                currentIndex = index;
                
                // Remove active class from all dots and cards
                dots.forEach(function(d) {
                    d.classList.remove('active');
                });
                cards.forEach(function(c) {
                    c.classList.remove('active');
                });
                
                // Add active class to corresponding dot and card
                if (dots[index]) {
                    dots[index].classList.add('active');
                }
                if (cards[index]) {
                    cards[index].classList.add('active');
                }
            }
            
            // Dot click handlers
            dots.forEach(function(dot, index) {
                dot.addEventListener('click', function() {
                    switchToCard(index);
                });
            });
            
            // Touch event handlers for swipe
            cardsContainer.addEventListener('touchstart', function(e) {
                // Don't interfere if user is clicking on a link
                if (e.target.closest('a')) {
                    return;
                }
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
                isSwiping = false;
            }, { passive: true });
            
            cardsContainer.addEventListener('touchmove', function(e) {
                // Detect if this is a horizontal swipe
                const currentX = e.changedTouches[0].screenX;
                const currentY = e.changedTouches[0].screenY;
                const deltaX = Math.abs(currentX - touchStartX);
                const deltaY = Math.abs(currentY - touchStartY);
                
                // If horizontal movement is greater than vertical, it's a swipe
                if (deltaX > deltaY && deltaX > 10) {
                    isSwiping = true;
                }
            }, { passive: true });
            
            cardsContainer.addEventListener('touchend', function(e) {
                // Don't process swipe if user was clicking on a link
                if (e.target.closest('a')) {
                    return;
                }
                
                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;
                
                if (isSwiping) {
                    handleSwipe();
                }
            }, { passive: true });
            
            // Handle swipe gesture
            function handleSwipe() {
                const swipeDistance = touchEndX - touchStartX;
                
                // Check if swipe distance is significant enough
                if (Math.abs(swipeDistance) < minSwipeDistance) {
                    return; // Not a significant swipe
                }
                
                if (swipeDistance > 0) {
                    // Swipe right - go to previous card
                    switchToCard(currentIndex - 1);
                } else {
                    // Swipe left - go to next card
                    switchToCard(currentIndex + 1);
                }
            }
            
            // Initialize with first card active
            switchToCard(0);
        })();
        
        // Location bar functionality
        (function initLocationBar() {
            const locationBar = document.querySelector('.location-bar');
            
            // Check if geolocation is supported
            if (!navigator.geolocation) {
                locationBar.style.cursor = 'default';
                locationBar.title = 'Geolocation is not supported by your browser';
                return;
            }
            
            // Function to get readable address from coordinates (reverse geocoding)
            function getAddressFromCoordinates(lat, lng) {
                // Use server-side proxy to avoid CORS issues
                const proxyUrl = `../api/geocode.php?lat=${lat}&lon=${lng}&zoom=18`;
                
                return fetch(proxyUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-cache'
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Geocoding service unavailable');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.address) {
                            const addr = data.address;
                            let addressParts = [];
                            
                            // Try different address components for Philippine locations
                            // Check for barangay/suburb first
                            if (addr.barangay || addr.suburb || addr.neighbourhood) {
                                addressParts.push(addr.barangay || addr.suburb || addr.neighbourhood);
                            }
                            
                            // Check for city/municipality
                            if (addr.city || addr.town || addr.municipality || addr.village) {
                                addressParts.push(addr.city || addr.town || addr.municipality || addr.village);
                            }
                            
                            // Check for province/state
                            if (addr.province || addr.state || addr.region) {
                                addressParts.push(addr.province || addr.state || addr.region);
                            }
                            
                            // If we have address parts, return them
                            if (addressParts.length > 0) {
                                return addressParts.join(', ');
                            }
                            
                            // Try to extract from display_name with better parsing
                            if (data.display_name) {
                                const parts = data.display_name.split(',').map(p => p.trim()).filter(p => p.length > 0);
                                // For Philippine addresses, typically format is: Street/Area, City/Municipality, Province, Country
                                // We want to skip very generic parts and get meaningful location names
                                const skipWords = ['Philippines', 'Philippine', 'PH', 'Road', 'Street', 'Avenue'];
                                const meaningfulParts = parts.filter(part => {
                                    const lowerPart = part.toLowerCase();
                                    return !skipWords.some(word => lowerPart.includes(word.toLowerCase()));
                                });
                                
                                if (meaningfulParts.length >= 2) {
                                    // Take first 2 meaningful parts (usually area/city and province)
                                    return meaningfulParts.slice(0, 2).join(', ');
                                } else if (meaningfulParts.length === 1) {
                                    return meaningfulParts[0];
                                } else if (parts.length >= 2) {
                                    // Fallback: take first 2 parts regardless
                                    return parts.slice(0, 2).join(', ');
                                }
                            }
                        }
                        
                        // If we still don't have a good address, try with different zoom level
                        throw new Error('No address found from primary service');
                    })
                    .catch(error => {
                        console.error('Reverse geocoding error:', error);
                        // Retry with a lower zoom level for broader area name
                        const retryUrl = `../api/geocode.php?lat=${lat}&lon=${lng}&zoom=10`;
                        return fetch(retryUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json'
                            },
                            cache: 'no-cache'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.display_name) {
                                const parts = data.display_name.split(',').map(p => p.trim()).filter(p => p.length > 0);
                                // Get city/municipality and province
                                if (parts.length >= 2) {
                                    return parts.slice(0, 2).join(', ');
                                }
                                return parts[0] || 'Current Location';
                            }
                            return 'Current Location';
                        })
                        .catch(retryError => {
                            console.error('Retry geocoding also failed:', retryError);
                            return 'Current Location';
                        });
                    });
            }
            
            // Function to update location bar with new location
            function updateLocationBar(locationText) {
                locationBar.innerHTML = '<span>📍</span> ' + locationText;
                locationBar.classList.remove('loading');
            }
            
            // Function to handle geolocation success
            function handleLocationSuccess(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Get readable address
                getAddressFromCoordinates(lat, lng)
                    .then(address => {
                        updateLocationBar(address);
                    });
            }
            
            // Function to handle geolocation errors
            function handleLocationError(error) {
                locationBar.classList.remove('loading');
                let errorMessage = 'Unable to get location';
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Location permission denied. Please enable GPS in your browser settings.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Location information unavailable.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Location request timed out.';
                        break;
                    default:
                        errorMessage = 'An unknown error occurred.';
                        break;
                }
                
                alert(errorMessage);
            }
            
            // Click handler to request location
            locationBar.addEventListener('click', function() {
                // Add loading state
                locationBar.classList.add('loading');
                const originalContent = locationBar.innerHTML;
                locationBar.innerHTML = '<span>📍</span> Getting your location';
                
                // Request location with options
                const options = {
                    enableHighAccuracy: true, // Use GPS if available
                    timeout: 10000, // 10 seconds timeout
                    maximumAge: 0 // Don't use cached location
                };
                
                navigator.geolocation.getCurrentPosition(
                    handleLocationSuccess,
                    handleLocationError,
                    options
                );
            });
            
            // Try to get location automatically on page load (optional)
            // Uncomment the following if you want automatic location on page load
            /*
            window.addEventListener('load', function() {
                const options = {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000 // Accept cached location up to 1 minute old
                };
                
                navigator.geolocation.getCurrentPosition(
                    handleLocationSuccess,
                    function(error) {
                        // Silently fail on page load - user can click to retry
                        console.log('Location not available on page load:', error);
                    },
                    options
                );
            });
            */
        })();
    </script>
</body>
</html> 