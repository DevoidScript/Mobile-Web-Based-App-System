<?php
/**
 * Dashboard page for the Red Cross Mobile App
 * This page is shown after successful login
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
    // Set a session flag to indicate they were redirected from dashboard
    $_SESSION['redirected_from'] = 'dashboard';
    
    // Redirect to login page
    header('Location: ../index.php?error=Please login to access the dashboard');
    exit;
}

// Set a session variable to show we're on the dashboard page - useful for back button detection
$_SESSION['on_dashboard'] = true;

// Get user data
$user = $_SESSION['user'] ?? null;
$donor_details = $_SESSION['donor_details'] ?? null;

// If donor details are not in session but user is logged in, try to fetch them
if (!$donor_details && $user) {
    // Get donor details from donors_detail table to match the React Native implementation
    $donor_data = get_record('donors_detail', $user['id']);
    if ($donor_data['success'] && !empty($donor_data['data'])) {
        $_SESSION['donor_details'] = $donor_data['data'][0];
        $donor_details = $_SESSION['donor_details'];
    }
}
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
    <title>Red Cross Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="Red Cross Mobile Application - Dashboard">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <style>
        /* 
         * Mobile-optimized styles for the Red Cross Dashboard
         * Designed specifically for mobile phone displays with touch interactions
         * Includes responsive design elements for various screen sizes
         */
        
        /* Additional styles specific to the dashboard page */
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa; /* Lighter gray to match design */
            font-family: Arial, sans-serif;
            font-size: 16px; /* Base font size for better readability on mobile */
            -webkit-tap-highlight-color: transparent; /* Remove tap highlight on mobile */
        }
        
        /* New header styles from explore.php */
        .header {
            background-color: #FF0000;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            box-sizing: border-box;
            z-index: 100;
        }

        .user-info-header {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #FF0000;
            font-weight: bold;
        }

        .user-name {
            font-weight: bold;
            font-size: 18px;
            color: white;
        }

        .notification-icon a {
            font-size: 24px;
            text-decoration: none;
            color: white;
        }
        
        .dashboard-container {
            padding: 15px;
            margin-bottom: 70px; /* Increased space for bottom navigation */
            max-width: 600px; /* Limit width for larger phones */
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Countdown Section */
        .countdown-section {
            text-align: center;
            padding: 20px 0;
        }

        .red-cross-logo-large {
            max-width: 80px; /* Set a maximum size */
            width: 25%;      /* Use a percentage for responsive scaling */
            height: auto;    /* Maintain aspect ratio */
            object-fit: contain; /* Ensure the image scales nicely */
            margin-bottom: 15px;
        }

        .countdown-section h3 {
            color: #FF0000;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .countdown-timer {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .timer-box {
            display: flex;
            flex-direction: column;
        }

        .timer-box .time {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }

        .timer-box .label {
            font-size: 14px;
            color: #6c757d;
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #eee;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .card-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            transition: background-color 0.2s ease;
        }

        .card-link:hover {
            background-color: #f9f9f9;
        }

        .card-link::after {
            content: '>';
            font-family: 'monospace';
            font-weight: bold;
            color: #ccc;
            font-size: 24px;
        }
        
        .card-content-wrapper {
            display: flex;
            align-items: center;
        }
        
        .card-icon {
            background-color: #ffebee;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
            color: #FF0000;
            flex-shrink: 0;
        }

        .card-text-container h3 {
            color: #FF0000;
            margin: 0 0 5px;
            font-size: 16px;
        }

        .card-text-container p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        /* Blood Tracker Styles */
        .blood-tracker-card .card-text-container {
             flex-grow: 1;
        }
        .tracker-timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding: 0 10px;
            position: relative;
        }
        .tracker-line {
            position: absolute;
            height: 2px;
            background-color: #e0e0e0;
            width: calc(100% - 60px);
            top: 19px; /* Vertically center with icon */
            left: 30px;
            z-index: 1;
        }
        .tracker-progress-line {
            position: absolute;
            height: 2px;
            background-color: #4CAF50; /* Green progress */
            width: 66.66%; /* 2/3 of the way for "Allocated" */
            top: 19px;
            left: 30px;
            z-index: 2;
            transition: width 0.3s ease;
        }
        .tracker-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 3;
            position: relative;
        }
        .tracker-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            background-color: #ccc; /* Default inactive */
            border: 2px solid white;
        }
        .tracker-icon.completed, .tracker-icon.active {
            background-color: #4CAF50; /* Green */
        }
        .tracker-icon.last {
            background-color: #FF0000; /* Red for 'Used' */
        }
        .tracker-label {
            font-size: 12px;
            margin-top: 5px;
            color: #666;
        }
        .tracker-status-text {
            font-size: 14px;
            color: #333;
            margin-top: 10px;
        }
        
        .logout-btn {
            width: 100%;
            padding: 15px;
            background-color: #FF0000;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 20px;
            -webkit-appearance: none; /* Remove default styling on iOS */
            min-height: 50px; /* Ensure good touch target size */
        }
        
        .logout-btn:active {
            background-color: #D50000; /* Darker red on touch */
            transform: translateY(1px); /* Slight visual feedback */
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
            height: 60px; /* Fixed height for consistency */
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
            touch-action: manipulation; /* Optimize for touch */
            text-decoration: none; /* Remove underline from links */
        }
        
        .nav-button:active {
            opacity: 0.7; /* Visual feedback on touch */
        }

        .nav-button.active {
            color: #FF0000;
        }
        
        .nav-icon {
            font-size: 24px;
            margin-bottom: 2px;
        }
        
        .nav-label {
            font-size: 10px;
            text-align: center;
        }
        
        /* Add responsive adjustments */
        @media (max-width: 360px) {
            /* For very small screens */
            .tracker-label {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="user-info-header">
            <div class="user-avatar">
                <?php
                    if (!empty($donor_details['first_name'])) {
                        echo htmlspecialchars(strtoupper(substr($donor_details['first_name'], 0, 1)));
                    } else {
                        echo '👤';
                    }
                ?>
            </div>
            <span class="user-name">
                <?php 
                    if (!empty($donor_details['first_name'])) {
                        echo htmlspecialchars($donor_details['first_name']);
                    } elseif (!empty($user['email'])) {
                        $email_parts = explode('@', $user['email']);
                        echo htmlspecialchars($email_parts[0]);
                    } else {
                        echo 'User';
                    }
                ?>
            </span>
        </div>
        <div class="notification-icon">
            <a href="#">🔔</a>
        </div>
    </div>
    
    <div class="dashboard-container">
        <div class="countdown-section">
            <img src="../assets/icons/redcrosslogo.jpg" alt="Philippine Red Cross Logo" class="red-cross-logo-large">
            <h3>You can donate again in</h3>
            <div class="countdown-timer">
                <div class="timer-box">
                    <span class="time">02</span>
                    <span class="label">months</span>
                </div>
                <div class="timer-box">
                    <span class="time">28</span>
                    <span class="label">days</span>
                </div>
            </div>
        </div>
        
        <div class="card">
            <a href="blood_donation.php" class="card-link">
                <div class="card-content-wrapper">
                    <div class="card-icon">❤️</div>
                    <div class="card-text-container">
                        <h3>Donate Blood</h3>
                        <p>Schedule your next blood donation appointment</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="card">
            <a href="donation_history.php" class="card-link">
                <div class="card-content-wrapper">
                    <div class="card-icon">📋</div>
                    <div class="card-text-container">
                        <h3>Donation History</h3>
                        <p>Keep track of your previous donations and their outcomes</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="card blood-tracker-card">
            <a href="#" class="card-link">
                <div class="card-text-container">
                    <h3>Blood Tracker</h3>
                    <div class="tracker-timeline">
                        <div class="tracker-line"></div>
                        <div class="tracker-progress-line"></div>
                        <div class="tracker-step">
                            <div class="tracker-icon completed">✓</div>
                            <div class="tracker-label">Processed</div>
                        </div>
                        <div class="tracker-step">
                            <div class="tracker-icon completed">✓</div>
                            <div class="tracker-label">Stored</div>
                        </div>
                        <div class="tracker-step">
                            <div class="tracker-icon active">✓</div>
                            <div class="tracker-label">Allocated</div>
                        </div>
                        <div class="tracker-step">
                            <div class="tracker-icon last">➕</div>
                            <div class="tracker-label">Used</div>
                        </div>
                    </div>
                    <div class="tracker-status-text">
                        Your blood is allocated for a hospital request.
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Mobile-optimized bottom navigation bar -->
    <div class="navigation-bar">
        <a href="dashboard.php" class="nav-button active">
            <div class="nav-icon">🏠</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="explore.php" class="nav-button">
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
            // Push the current state to history
            window.history.pushState({page: 'dashboard'}, 'Dashboard', window.location.href);
            
            // When the user presses back, push another state to prevent navigation
            window.addEventListener('popstate', function(e) {
                // Push state again to prevent going back
                window.history.pushState({page: 'dashboard'}, 'Dashboard', window.location.href);
                
                // Show a message if needed
                // console.log('Back navigation prevented');
            });
            
            // Additional approaches:
            
            // 1. Attempt to disable browser cache for this page using JavaScript
            // This works on some browsers to prevent back-forward cache
            window.onpageshow = function(event) {
                if (event.persisted) {
                    // Page was loaded from cache (back/forward navigation)
                    window.location.reload();
                }
            };
            
            // 2. Handle beforeunload when appropriate to prevent unwanted navigation
            // Uncomment this if you need to handle unsaved form data
            /* 
            window.addEventListener('beforeunload', function(e) {
                // Only add this if there's unsaved form data
                const unsavedChanges = false; // Set this based on your form state
                
                if (unsavedChanges) {
                    const confirmationMessage = 'You have unsaved changes. Are you sure you want to leave?';
                    e.returnValue = confirmationMessage;
                    return confirmationMessage;
                }
            });
            */
            
            // 3. Focus on the dashboard window to ensure it's active
            window.focus();
        })();
    </script>
</body>
</html> 