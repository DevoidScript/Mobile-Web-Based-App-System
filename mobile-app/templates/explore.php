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
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #D50000;
            margin-bottom: 15px;
        }

        .blood-center-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 10px;
            overflow: hidden;
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
        }

        .dot.active {
            background-color: #D50000;
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
        <img src="../assets/icons/redcrosslogo.jpg" alt="Philippine Red Cross Logo" class="logo-small">
        <h1>Explore</h1>
    </div>
    
    <div class="explore-container">
        <div class="location-bar">
            <span>📍</span> Villa Arevalo, Iloilo City
        </div>

        <h2 class="section-title">Find Blood Centers</h2>

        <div class="blood-center-card">
            <img src="../assets/images/donate.png" alt="Blood Donation Drive" class="center-image">
            <div class="center-info">
                <h3>Philippine Red Cross Iloilo Chapter</h3>
                <p>Bonifacio Dr, Iloilo City Proper, Iloilo City, Iloilo</p>
            </div>
        </div>
        <div class="pagination-dots">
            <span class="dot active"></span>
            <span class="dot"></span>
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
            <div class="nav-label">Explore</div>
        </a>
        <a href="profile.php" class="nav-button">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/app.js"></script>
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
    </script>
</body>
</html> 