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
$donorForm = null;
if ($user) {
    $params = [];
    if (!empty($user['donor_id'])) {
        $params = [ 'id' => 'eq.' . $user['donor_id'], 'limit' => 1 ];
    } elseif (!empty($user['email'])) {
        $params = [ 'email' => 'eq.' . strtolower(trim($user['email'])), 'limit' => 1 ];
    }
    if (!empty($params)) {
        $result = get_records('donor_form', $params);
        if ($result['success'] && !empty($result['data'])) {
            $donorForm = $result['data'][0];
        }
    }
}

// Check if user has donated
$has_donated = false;
$latest_donation = null;
if ($user && isset($user['id'])) {
    list($has_donated, $latest_donation) = has_successful_donation($user['id']);
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
        
        /* Notification Panel Styles */
        .notification-panel {
            position: fixed;
            top: 70px;
            right: 20px;
            width: 300px;
            max-height: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 1000;
            display: none;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        
        .notification-panel.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }
        
        .notification-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #666;
        }
        
        .notification-content {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .notification-item-body {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .notification-item-time {
            font-size: 12px;
            color: #999;
        }
        
        .notification-empty {
            padding: 40px 20px;
            text-align: center;
            color: #666;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .notification-icon {
            position: relative;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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
<?php if (!$has_donated): ?>
    <div class="header">
        <div class="user-info-header">
            <div class="user-avatar" style="background-image: url('<?php echo !empty($donorForm['profile_picture']) ? htmlspecialchars($donorForm['profile_picture']) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM2MzY2RjEiLz4KPC9zdmc+'; ?>'); background-size: cover; background-position: center;">
                <?php
                    if (empty($donorForm['profile_picture'])) {
                        if (!empty($donorForm['first_name'])) {
                            echo htmlspecialchars(strtoupper(substr($donorForm['first_name'], 0, 1)));
                        } else {
                            echo '👤';
                        }
                    }
                ?>
            </div>
            <span class="user-name">
                <?php 
                    if (!empty($donorForm['first_name'])) {
                        echo htmlspecialchars($donorForm['first_name']);
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
            <a href="#" id="notificationBell" onclick="toggleNotificationPanel(event)">🔔</a>
            <span id="notificationBadge" class="notification-badge" style="display: none;">0</span>
        </div>
    </div>
    <div class="dashboard-container">
        <div style="text-align:center; margin-bottom: 20px;">
            <img src="../assets/images/donate.png" alt="Donate Blood Illustration" style="max-width:160px;width:70%;height:auto;">
            <h2 style="color:#b80000;font-size:2rem;font-weight:800;margin:16px 0 0 0;line-height:1.1;">DONATE BLOOD<br>SAVE LIVES</h2>
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
                        <p>Keep track of your previous donations and their outcomes.</p>
                    </div>
                </div>
            </a>
        </div>

        
        <div class="card blood-tracker-card">
            <a href="blood_tracker.php" class="card-link">
                <div class="card-content-wrapper">
                    <div class="card-icon">📊</div>
                    <div class="card-text-container">
                        <h3>Blood Tracker</h3>
                        <p>Track your blood donation progress and status</p>
                        <p style="color: #007bff; font-size: 12px;">Tap to view tracker</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="navigation-bar">
        <a href="dashboard.php" class="nav-button active">
            <div class="nav-icon">🏠</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="explore.php" class="nav-button">
            <div class="nav-icon">🔍</div>
            <div class="nav-label">Discover</div>
        </a>
        <a href="profile.php" class="nav-button">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
<?php else: ?>
    <div class="header">
        <div class="user-info-header">
            <div class="user-avatar" style="background-image: url('<?php echo !empty($donorForm['profile_picture']) ? htmlspecialchars($donorForm['profile_picture']) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM2MzY2RjEiLz4KPC9zdmc+'; ?>'); background-size: cover; background-position: center;">
                <?php
                    if (empty($donorForm['profile_picture'])) {
                        if (!empty($donorForm['first_name'])) {
                            echo htmlspecialchars(strtoupper(substr($donorForm['first_name'], 0, 1)));
                        } else {
                            echo '👤';
                        }
                    }
                ?>
            </div>
            <span class="user-name">
                <?php 
                    if (!empty($donorForm['first_name'])) {
                        echo htmlspecialchars($donorForm['first_name']);
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
            <a href="#" id="notificationBell" onclick="toggleNotificationPanel(event)">🔔</a>
            <span id="notificationBadge" class="notification-badge" style="display: none;">0</span>
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
            <a href="blood_tracker.php" class="card-link">
                <div class="card-content-wrapper">
                    <div class="card-icon">📊</div>
                    <div class="card-text-container">
                        <h3>Blood Tracker</h3>
                        <p>Track your blood donation progress and status</p>
                        <p style="color: #007bff; font-size: 12px;">Tap to view tracker</p>
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
            <div class="nav-label">Discover</div>
        </a>
        <a href="profile.php" class="nav-button">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
<?php endif; ?>
    
    <!-- Push Notification Prompt -->
    <?php include 'push-notification-prompt.php'; ?>
    
    <!-- Notification Panel -->
    <div id="notificationPanel" class="notification-panel">
        <div class="notification-header">
            <h3 class="notification-title">Notifications</h3>
            <button class="notification-close" onclick="closeNotificationPanel()">&times;</button>
        </div>
        <div class="notification-content" id="notificationContent">
            <div class="notification-empty">
                <p>No notifications yet</p>
                <small>You'll see blood drive alerts and updates here</small>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/push-notifications.js"></script>
    <!-- Register Service Worker for PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../service-worker.js')
                    .then(function(registration) {
                        // ServiceWorker registered successfully
                    })
                    .catch(function(error) {
                        console.error('ServiceWorker registration failed: ', error);
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
            
            // This works on some browsers to prevent back-forward cache
            window.onpageshow = function(event) {
                if (event.persisted) {
                    // Page was loaded from cache (back/forward navigation)
                    window.location.reload();
                }
            };

            window.focus();
        })();

        // Notification panel functionality
        let notificationPanelOpen = false;
        let notifications = [];

        // Toggle notification panel
        function toggleNotificationPanel(event) {
            event.preventDefault();
            const panel = document.getElementById('notificationPanel');
            
            if (notificationPanelOpen) {
                closeNotificationPanel();
            } else {
                openNotificationPanel();
            }
        }

        // Open notification panel
        function openNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            panel.classList.add('show');
            notificationPanelOpen = true;
            
            // Load notifications
            loadNotifications();
            
            // Hide badge when panel is open
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.style.display = 'none';
            }
        }

        // Close notification panel
        function closeNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            panel.classList.remove('show');
            notificationPanelOpen = false;
        }

        // Load notifications from database API
        async function loadNotifications() {
            try {
                // Get donor_id from session or default to 211 for testing
                const donorId = <?php echo isset($_SESSION['donor_id']) ? $_SESSION['donor_id'] : 211; ?>;
                
                const response = await fetch(`../api/get-notifications.php?donor_id=${donorId}`);
                const result = await response.json();
                
                if (result.success) {
                    notifications = result.data.notifications;
                    // Notifications loaded successfully
                } else {
                    console.error('❌ Failed to load notifications:', result.error);
                    // Fallback to localStorage if API fails
                    const stored = localStorage.getItem('appNotifications');
                    if (stored) {
                        notifications = JSON.parse(stored);
                        // Using localStorage fallback
                    }
                }
            } catch (error) {
                console.error('❌ Error loading notifications:', error);
                // Fallback to localStorage if API fails
                const stored = localStorage.getItem('appNotifications');
                if (stored) {
                    notifications = JSON.parse(stored);
                    console.log('📱 Using localStorage fallback');
                }
            }
            
            renderNotifications();
        }

        // Render notifications in the panel
        function renderNotifications() {
            const content = document.getElementById('notificationContent');
            
            if (notifications.length === 0) {
                content.innerHTML = `
                    <div class="notification-empty">
                        <p>No notifications yet</p>
                        <small>You'll see blood drive alerts and updates here</small>
                    </div>
                `;
                return;
            }
            
            const html = notifications.map(notification => `
                <div class="notification-item" onclick="handleNotificationClick('${notification.id}')">
                    <div class="notification-item-title">${notification.title}</div>
                    <div class="notification-item-body">${notification.body}</div>
                    <div class="notification-item-time">${formatTime(notification.timestamp)}</div>
                </div>
            `).join('');
            
            content.innerHTML = html;
        }

        // Handle notification click
        function handleNotificationClick(notificationId) {
            const notification = notifications.find(n => n.id === notificationId);
            if (notification && notification.url) {
                // Convert absolute URL to relative URL to maintain session context
                let targetUrl = notification.url;
                
                // If it's an absolute path starting with /Mobile-Web-Based-App-System/, make it relative
                if (targetUrl.startsWith('/Mobile-Web-Based-App-System/mobile-app/')) {
                    targetUrl = targetUrl.replace('/Mobile-Web-Based-App-System/mobile-app/', '../');
                }
                // If it's an absolute path starting with /mobile-app/, make it relative
                else if (targetUrl.startsWith('/mobile-app/')) {
                    targetUrl = targetUrl.replace('/mobile-app/', '../');
                }
                // If it's already relative, use as is
                else if (!targetUrl.startsWith('http') && !targetUrl.startsWith('/')) {
                    // Already relative, use as is
                }
                // If it's an absolute path starting with /, make it relative to current directory
                else if (targetUrl.startsWith('/')) {
                    targetUrl = '..' + targetUrl;
                }
                
                // Navigating to target URL
                
                // Use relative navigation to maintain session context
                window.location.href = targetUrl;
            }
            
            // Mark as read
            markNotificationAsRead(notificationId);
        }

        // Mark notification as read
        function markNotificationAsRead(notificationId) {
            // Remove from current display
            notifications = notifications.filter(n => n.id !== notificationId);
            
            // For database notifications, we could mark as read in the database
            // For now, just update the display
            renderNotifications();
            updateNotificationBadge();
            
            // Notification marked as read
        }

        // Add notification to the list (for real-time updates)
        function addNotification(notification) {
            notifications.unshift(notification);
            
            // Update localStorage for fallback
            localStorage.setItem('appNotifications', JSON.stringify(notifications));
            
            if (!notificationPanelOpen) {
                updateNotificationBadge();
            }
            
            if (notificationPanelOpen) {
                renderNotifications();
            }
            
            // New notification added
        }

        // Update notification badge
        function updateNotificationBadge() {
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                if (notifications.length > 0) {
                    badge.textContent = notifications.length;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        }

        // Format timestamp
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) { // Less than 1 minute
                return 'Just now';
            } else if (diff < 3600000) { // Less than 1 hour
                return Math.floor(diff / 60000) + ' minutes ago';
            } else if (diff < 86400000) { // Less than 1 day
                return Math.floor(diff / 3600000) + ' hours ago';
            } else {
                return date.toLocaleDateString();
            }
        }

        // Listen for push notifications when app is open
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                const { type, payload } = event.data || {};
                if (type === 'PUSH_IN_APP') {
                    // Add to in-app notifications
                    addNotification({
                        id: Date.now().toString(),
                        title: payload.title || 'New notification',
                        body: payload.body || '',
                        url: payload.url || '/mobile-app/templates/dashboard.php',
                        timestamp: Date.now()
                    });
                }
            });
        }

        // Initialize notification badge on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            updateNotificationBadge();
        });
    </script>
</body>
</html> 