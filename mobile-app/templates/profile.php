<?php
/**
 * User Profile Page for the Red Cross Mobile App
 * 
 * This page displays user profile information and allows users to view and edit their details.
 * Created as part of the templates organization structure to maintain consistent navigation.
 *
 * Path: templates/profile.php
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

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../index.php?error=Please login to access your profile');
    exit;
}

// Fetch donor data for profile
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
        } else {
            error_log("No donor_form found for user (params: " . json_encode($params) . ")");
        }
    }
}
if (!$donorForm) {
    error_log("User not logged in or missing donor_id/email in session");
    header('Location: ../index.php?error=Please login to access your profile');
    exit;
}

// Calculate age from birthdate
$age = 'N/A';
if (!empty($donorForm['birthdate'])) {
    $birthDate = new DateTime($donorForm['birthdate']);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
}

// Get blood type and donation stats from donations table using the same email-based lookup pattern
$blood_type = 'N/A';
$last_donation_date = 'N/A';
$total_donations = 0;

// Get donor ID the same way blood_tracker.php and medical-history-modal.php do
if ($user && isset($user['email'])) {
    $email = trim(strtolower($user['email']));
    
    // Fetch donor_form record by email
    $donorFormResp = get_records('donor_form', ['email' => 'eq.' . $email]);
    if ($donorFormResp['success'] && !empty($donorFormResp['data'])) {
        $donorForm = $donorFormResp['data'][0];
        $donor_id = $donorForm['donor_id'];
        
        // Debug logging
        error_log("Profile - Found donor record for email: $email, donor_id: $donor_id");
        
        // Get blood type and donation count from donations table
        $donation_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'created_at.desc'
        ];
        
        $donation_result = get_records('donations', $donation_params);
        if ($donation_result['success'] && !empty($donation_result['data'])) {
            $donations = $donation_result['data'];
            $total_donations = count($donations);
            
            // Get blood type from the most recent donation
            if ($total_donations > 0) {
                $latest_donation = $donations[0];
                $blood_type = htmlspecialchars($latest_donation['blood_type'] ?? 'N/A');
                
                // Get last donation date if available
                if (isset($latest_donation['created_at'])) {
                    $last_donation_date = date('F j, Y', strtotime($latest_donation['created_at']));
                }
            }
            
            error_log("Profile - Found $total_donations donations, blood type: $blood_type");
        } else {
            error_log("Profile - No donations found for donor_id: $donor_id");
        }
    } else {
        error_log("Profile - No donor record found for email: $email");
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
    <title>Red Cross - Profile</title>
    <!-- Resource hints for faster loading on slow connections -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="//fonts.gstatic.com" crossorigin>
    <!-- Preload critical resources -->
    <link rel="preload" href="../assets/css/styles.css" as="style">
    <link rel="preload" href="../assets/js/app.js" as="script">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="Red Cross Mobile Application - User Profile">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <style>
        /* 
         * Mobile-optimized styles for the Red Cross Profile Page
         * Designed specifically for mobile phone displays with touch interactions
         */
        
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa; /* Lighter background */
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
        
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        
        .profile-container {
            padding: 20px 15px 80px; /* Add more bottom padding */
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }
        
        .profile-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 10px;
            background-color: #007bff; /* Example color */
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM2MzY2RjEiLz4KPC9zdmc+'); /* Placeholder image */
            background-size: cover;
        }

        .profile-name {
            font-size: 22px;
            font-weight: bold;
            color: #FF0000;
            margin-bottom: 5px;
        }

        .last-donation {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }

        .stat-box {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            width: 30%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .stat-box .label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
        }
        
        .settings-group {
            background-color: white;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: left;
            border: 1px solid #ddd;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .settings-group h3 {
            padding: 15px 15px 10px;
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .setting-item a {
            text-decoration: none;
            color: #333;
            flex-grow: 1;
        }

        .setting-item .arrow {
            color: #ccc;
            font-weight: bold;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #4CAF50; /* Green */
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .logout-link {
            display: inline-block;
            margin-top: 20px;
            background: #d50000;
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            padding: 14px 32px;
            font-size: 18px;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(213,0,0,0.08);
            transition: background 0.2s;
        }
        .logout-link:active {
            background: #b71c1c;
        }
        .logout-confirm-btn {
            background: #d50000;
            color: #fff;
            font-weight: bold;
            padding: 14px 0;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            box-shadow: 0 2px 8px rgba(213,0,0,0.08);
            transition: background 0.2s;
        }
        .logout-confirm-btn:active {
            background: #b71c1c;
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
        
        /* Responsive adjustments */
        @media (max-width: 360px) {
            .profile-detail {
                flex-direction: column;
            }
            
            .detail-label {
                margin-bottom: 5px;
            }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            max-width: 300px;
            width: 90%;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .modal-title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }

        .modal-text {
            margin-bottom: 25px;
            color: #555;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .modal-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 48%;
        }

        .cancel-btn {
            background: white;
            border: 1px solid #ddd;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Profile</h1>
    </div>
    
    <div class="profile-container">
        <div class="profile-avatar" style="background-image: url('<?php echo !empty($donorForm['profile_picture']) ? htmlspecialchars($donorForm['profile_picture']) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM2MzY2RjEiLz4KPC9zdmc+'; ?>');"></div>
        <div class="profile-name">
            <?php 
                $firstName = htmlspecialchars($donorForm['first_name'] ?? '');
                $lastName = htmlspecialchars($donorForm['surname'] ?? '');
                echo trim("$firstName $lastName");
            ?>
        </div>
        <div class="last-donation">Last Donation: <?php echo $last_donation_date; ?></div>

        <?php
        // Get current donation status for profile
        $current_donation_status = null;
        $donor_id = $donorForm['id'] ?? $user['donor_id'] ?? $user['id'];
        
        if ($donor_id) {
            $params = [
                'donor_id' => 'eq.' . $donor_id,
                'order' => 'created_at.desc',
                'limit' => 1
            ];
            
            $result = get_records('donations', $params);
            if ($result['success'] && !empty($result['data'])) {
                $donation = $result['data'][0];
                $tracker_data = build_tracker_data($donation);
                $current_donation_status = $tracker_data['current_status'];
            }
        }
        ?>
        
        <div class="stats-container">
            <div class="stat-box">
                <div class="label">Age</div>
                <div class="value"><?php echo isset($donorForm['age']) ? htmlspecialchars($donorForm['age']) : 'N/A'; ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Blood Type</div>
                <div class="value"><?php echo $blood_type; ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Donations</div>
                <div class="value"><?php echo str_pad((int)$total_donations, 2, '0', STR_PAD_LEFT); ?></div>
            </div>
        </div>
        
        <?php if ($current_donation_status): ?>
        <div class="stats-container" style="margin-top: 15px;">
            <div class="stat-box" style="width: 100%; background: #e3f2fd; border-color: #2196F3;">
                <div class="label">Current Donation Status</div>
                <div class="value" style="color: #1976d2;">
                    <a href="blood_tracker.php" style="color: #1976d2; text-decoration: none;">
                        <?php echo ucfirst($current_donation_status); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="settings-group">
            <h3>Account Settings</h3>
            <div class="setting-item">
                <a href="edit-profile.php">Edit profile</a>
                <span class="arrow">></span>
            </div>
            <div class="setting-item">
                <a href="change-password.php">Change password</a>
                <span class="arrow">></span>
            </div>
            <div class="setting-item">
                <span>Push notifications</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="pushNotificationToggle" checked>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="settings-group">
            <h3>Support</h3>
            <div class="setting-item">
                <a href="about-us.php">About us</a>
                <span class="arrow">></span>
            </div>
            <div class="setting-item">
                <a href="privacy-policy.php">Privacy policy</a>
                <span class="arrow">></span>
            </div>
        </div>

        <a class="logout-link" id="logout-link">Logout</a>
    </div>
    
    <!-- Logout Confirmation Modal -->
    <div id="logout-modal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" id="close-modal">&times;</span>
            <h3 class="modal-title">Logout</h3>
            <p class="modal-text">Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button id="cancel-logout" class="modal-btn cancel-btn">Cancel</button>
                <form id="logout-form" action="../api/auth.php?logout" method="POST" style="width: 48%;">
                    <button type="submit" class="modal-btn logout-confirm-btn" style="width: 100%;">Logout</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Mobile-optimized bottom navigation bar -->
    <div class="navigation-bar">
        <a href="dashboard.php" class="nav-button">
            <div class="nav-icon">🏠</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="explore.php" class="nav-button">
            <div class="nav-icon">🔍</div>
            <div class="nav-label">Discover</div>
        </a>
        <a href="profile.php" class="nav-button active">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
    
    <!-- Push Notification Prompt -->
    <?php include 'push-notification-prompt.php'; ?>
    
    <!-- Scripts -->
    <script src="../assets/js/app.js" defer></script>
    <script src="../assets/js/push-notifications.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutLink = document.getElementById('logout-link');
            const logoutModal = document.getElementById('logout-modal');
            const closeModal = document.getElementById('close-modal');
            const cancelLogout = document.getElementById('cancel-logout');
            const logoutForm = document.getElementById('logout-form');

            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                logoutModal.style.display = 'flex';
            });

            const hideModal = () => {
                logoutModal.style.display = 'none';
            };

            closeModal.addEventListener('click', hideModal);
            cancelLogout.addEventListener('click', hideModal);

            // Optional: Close modal if clicking on the overlay
            logoutModal.addEventListener('click', function(e) {
                if (e.target === logoutModal) {
                    hideModal();
                }
            });

            // Push notification toggle functionality
            const pushToggle = document.getElementById('pushNotificationToggle');
            if (pushToggle) {
                // Flag to prevent updateToggleState from overwriting during enable/disable
                let isUpdatingPushState = false;
                
                // Wait for push-notifications.js to load, then check status
                // Increased delay for mobile devices and ngrok/median.co
                setTimeout(() => {
                    if (!isUpdatingPushState) {
                        updateToggleState();
                    }
                }, 1000);
                
                pushToggle.addEventListener('change', function() {
                    // Prevent default state change until operation completes
                    const desiredState = this.checked;
                    isUpdatingPushState = true;
                    
                    if (desiredState) {
                        enablePushNotifications().finally(() => {
                            // Re-check state after enabling
                            setTimeout(() => {
                                isUpdatingPushState = false;
                                updateToggleState();
                            }, 1500);
                        });
                    } else {
                        disablePushNotifications().finally(() => {
                            // Re-check state after disabling
                            setTimeout(() => {
                                isUpdatingPushState = false;
                                updateToggleState();
                            }, 500);
                        });
                    }
                });
                
                // Store the flag globally so updateToggleState can check it
                window.isUpdatingPushState = () => isUpdatingPushState;
                window.setUpdatingPushState = (value) => { isUpdatingPushState = value; };
            }
        });

        // Show status message for push notifications
        function showPushStatus(message, isError) {
            // Try to use the status indicator from push-notification-prompt.php if available
            const indicator = document.getElementById('pushStatusIndicator');
            const messageEl = document.getElementById('pushStatusMessage');
            
            if (indicator && messageEl) {
                messageEl.textContent = message;
                indicator.classList.toggle('error', isError);
                indicator.classList.add('show');
                
                setTimeout(() => {
                    indicator.classList.remove('show');
                }, 4000);
            } else {
                // Fallback: use console and show a simple alert for critical errors
                console.log(isError ? 'Push notification error:' : 'Push notification:', message);
                if (isError) {
                    // For mobile, a brief console message is better than blocking alerts
                    // You could also create a simple toast notification here
                }
            }
        }

        // Helper function to convert VAPID key (from push-notifications.js)
        function urlBase64ToUint8Array(base64String) {
            if (typeof window.urlBase64ToUint8Array === 'function') {
                return window.urlBase64ToUint8Array(base64String);
            }
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

        // Check push notification support with detailed diagnostics
        function checkPushSupport() {
            const checks = {
                https: window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1',
                serviceWorker: 'serviceWorker' in navigator,
                notification: 'Notification' in window,
                pushManager: 'PushManager' in window,
                userAgent: navigator.userAgent
            };
            
            // PushManager might only be available after service worker registration
            // Try to get it from service worker registration if available
            if (!checks.pushManager && 'serviceWorker' in navigator) {
                navigator.serviceWorker.ready.then(registration => {
                    if (registration && registration.pushManager) {
                        checks.pushManager = true;
                        console.log('PushManager found in service worker registration');
                    }
                }).catch(() => {});
            }
            
            console.log('Push notification support check:', checks);
            
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                         (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            
            if (isIOS && !window.navigator.standalone && !window.matchMedia('(display-mode: standalone)').matches) {
                return {
                    supported: false,
                    error: 'iOS requires PWA installation',
                    message: 'On iOS, push notifications only work when the app is installed via "Add to Home Screen". Please install the PWA first.',
                    checks: checks
                };
            }
            
            if (!checks.https) {
                return {
                    supported: false,
                    error: 'HTTPS required',
                    message: 'Push notifications require HTTPS (or localhost). Make sure you\'re accessing via ngrok or median.co.',
                    checks: checks
                };
            }
            
            if (!checks.serviceWorker) {
                return {
                    supported: false,
                    error: 'Service Worker not supported',
                    message: 'Your browser doesn\'t support Service Workers. Please use Chrome, Edge, Firefox, or Safari 16.4+.',
                    checks: checks
                };
            }
            
            if (!checks.notification) {
                return {
                    supported: false,
                    error: 'Notifications not supported',
                    message: 'Your browser doesn\'t support the Notification API.',
                    checks: checks
                };
            }
            
            // PushManager might not be available until service worker is registered
            // This is OK - we'll check again after service worker registration
            return {
                supported: true,
                checks: checks
            };
        }

        // Update toggle state based on actual subscription status (not just permission)
        async function updateToggleState() {
            const toggle = document.getElementById('pushNotificationToggle');
            if (!toggle) return;
            
            // Don't update if we're in the middle of enabling/disabling
            if (typeof window.isUpdatingPushState === 'function' && window.isUpdatingPushState()) {
                console.log('Skipping toggle state update - operation in progress');
                return;
            }

            try {
                // First check basic support
                const supportCheck = checkPushSupport();
                if (!supportCheck.supported) {
                    toggle.disabled = true;
                    toggle.title = supportCheck.message || 'Push notifications not supported';
                    return;
                } else {
                    toggle.disabled = false;
                    toggle.title = '';
                }
                
                // Check if push notification functions are available
                if (typeof getNotificationPermission !== 'function') {
                    console.warn('Push notification functions not loaded yet, retrying...');
                    // Retry after a longer delay for mobile devices
                    setTimeout(() => {
                        if (!window.isUpdatingPushState || !window.isUpdatingPushState()) {
                            updateToggleState();
                        }
                    }, 1000);
                    // Fallback: check permission directly
                    if ('Notification' in window) {
                        toggle.checked = Notification.permission === 'granted';
                    }
                    return;
                }

                // Check if service worker is ready (important for mobile/ngrok)
                if ('serviceWorker' in navigator) {
                    try {
                        await navigator.serviceWorker.ready;
                    } catch (e) {
                        console.warn('Service worker not ready yet:', e);
                        return; // Wait for service worker
                    }
                }

                const permission = getNotificationPermission();
                
                // For mobile devices via ngrok/median.co, check actual subscription status
                if (permission === 'granted' && 'serviceWorker' in navigator) {
                    try {
                        const registration = await navigator.serviceWorker.ready;
                        const subscription = await registration.pushManager.getSubscription();
                        
                        // Only update if not in the middle of an operation
                        if (!window.isUpdatingPushState || !window.isUpdatingPushState()) {
                            toggle.checked = subscription !== null;
                            console.log('Toggle state updated - subscription exists:', subscription !== null);
                        }
                        
                        // If permission is granted but no subscription, only auto-subscribe on initial load
                        // Don't auto-subscribe if user just disabled it
                        if (subscription === null && localStorage.getItem('pushNotificationsEnabled') === 'true') {
                            console.log('Permission granted but no subscription found, attempting to subscribe...');
                            if (typeof window.setUpdatingPushState === 'function') {
                                window.setUpdatingPushState(true);
                            }
                            await fetchVapidKey();
                            if (VAPID_PUBLIC_KEY) {
                                const result = await initializePushNotifications(VAPID_PUBLIC_KEY);
                                if (result.success) {
                                    toggle.checked = true;
                                    console.log('Push notifications enabled successfully');
                                } else if (result.error !== 'permission_denied') {
                                    console.log('Push subscription attempt:', result);
                                    toggle.checked = false;
                                }
                            }
                            if (typeof window.setUpdatingPushState === 'function') {
                                window.setUpdatingPushState(false);
                            }
                        }
                    } catch (error) {
                        console.error('Error checking subscription:', error);
                        // Fallback to permission check (only if not updating)
                        if (!window.isUpdatingPushState || !window.isUpdatingPushState()) {
                            toggle.checked = permission === 'granted';
                        }
                    }
                } else {
                    // Permission not granted, set toggle accordingly (only if not updating)
                    if (!window.isUpdatingPushState || !window.isUpdatingPushState()) {
                        toggle.checked = permission === 'granted';
                    }
                }
            } catch (error) {
                console.error('Error updating toggle state:', error);
                // Fallback: check permission directly (only if not updating)
                if ((!window.isUpdatingPushState || !window.isUpdatingPushState()) && 'Notification' in window) {
                    toggle.checked = Notification.permission === 'granted';
                }
            }
        }

        // Enable push notifications (optimized for mobile/ngrok/median.co)
        async function enablePushNotifications() {
            const toggle = document.getElementById('pushNotificationToggle');
            
            try {
                // First, check support with diagnostics
                const supportCheck = checkPushSupport();
                if (!supportCheck.supported) {
                    showPushStatus(supportCheck.message || 'Push notifications not supported', true);
                    if (toggle) {
                        toggle.checked = false;
                    }
                    console.error('Push notification support check failed:', supportCheck);
                    return;
                }
                
                // Set flag to prevent state updates during operation
                if (typeof window.setUpdatingPushState === 'function') {
                    window.setUpdatingPushState(true);
                }
                
                // Keep toggle on while processing
                if (toggle) {
                    toggle.checked = true;
                }
                
                // Ensure service worker is ready (critical for mobile)
                // PushManager is often only available after service worker registration
                if ('serviceWorker' in navigator) {
                    try {
                        let registration = null;
                        try {
                            registration = await navigator.serviceWorker.ready;
                        } catch (e) {
                            console.warn('Service worker not ready, waiting...', e);
                            showPushStatus('Registering service worker...', false);
                            // Try to register if not already registered
                            const basePath = (() => {
                                const pathname = window.location.pathname;
                                const marker = '/mobile-app/';
                                const idx = pathname.indexOf(marker);
                                return idx !== -1 ? pathname.substring(0, idx + marker.length) : '/mobile-app/';
                            })();
                            const swPath = basePath + 'service-worker.js';
                            registration = await navigator.serviceWorker.register(swPath, { scope: basePath });
                            await registration.ready;
                        }
                        
                        // Now check if PushManager is available in the registration
                        if (!registration.pushManager && 'PushManager' in window) {
                            console.log('PushManager available globally');
                        } else if (registration.pushManager) {
                            console.log('PushManager available in service worker registration');
                        } else {
                            // Wait a bit and check again
                            await new Promise(resolve => setTimeout(resolve, 500));
                        }
                    } catch (e) {
                        console.error('Service worker registration failed:', e);
                        showPushStatus('Failed to register service worker. Please refresh the page.', true);
                        if (toggle) {
                            toggle.checked = false;
                        }
                        if (typeof window.setUpdatingPushState === 'function') {
                            window.setUpdatingPushState(false);
                        }
                        return;
                    }
                } else {
                    showPushStatus('Service Workers not supported in this browser', true);
                    if (toggle) {
                        toggle.checked = false;
                    }
                    if (typeof window.setUpdatingPushState === 'function') {
                        window.setUpdatingPushState(false);
                    }
                    return;
                }

                // Re-check support now that service worker is ready
                // PushManager should be available now
                const registration = await navigator.serviceWorker.ready;
                if (!registration.pushManager && typeof PushManager === 'undefined') {
                    console.error('PushManager not available even after service worker registration');
                    showPushStatus('Push notifications not supported. Please use Chrome, Edge, or Firefox on Android.', true);
                    if (toggle) {
                        toggle.checked = false;
                    }
                    if (typeof window.setUpdatingPushState === 'function') {
                        window.setUpdatingPushState(false);
                    }
                    return;
                }
                
                // Check if functions are available
                if (typeof fetchVapidKey !== 'function' || typeof promptForPushNotifications !== 'function') {
                    console.warn('Push notification functions not available, waiting...');
                    // Wait a bit longer for mobile devices
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    
                    if (typeof fetchVapidKey !== 'function' || typeof promptForPushNotifications !== 'function') {
                        // Fallback: simple permission request and manual subscription
                        if ('Notification' in window) {
                            showPushStatus('Requesting permission...', false);
                            const permission = await Notification.requestPermission();
                            if (permission === 'granted') {
                                // Try to subscribe manually
                                try {
                                    const reg = await navigator.serviceWorker.ready;
                                    const pushManager = reg.pushManager || (typeof PushManager !== 'undefined' ? new PushManager() : null);
                                    
                                    if (!pushManager) {
                                        throw new Error('PushManager not available');
                                    }
                                    
                                    // Fetch VAPID key manually
                                    const base = (() => {
                                        const pathname = window.location.pathname;
                                        const marker = '/mobile-app/';
                                        const idx = pathname.indexOf(marker);
                                        return idx !== -1 ? pathname.substring(0, idx + marker.length) : '/mobile-app/';
                                    })();
                                    const url = new URL(base + 'api/get-vapid-key.php', window.location.origin);
                                    const keyResponse = await fetch(url.toString(), { credentials: 'same-origin' });
                                    const keyData = await keyResponse.json();
                                    
                                    if (keyData.success && keyData.publicKey) {
                                        // Subscribe manually
                                        const subscription = await pushManager.subscribe({
                                            userVisibleOnly: true,
                                            applicationServerKey: urlBase64ToUint8Array(keyData.publicKey)
                                        });
                                        
                                        // Save to backend
                                        const saveUrl = new URL(base + 'api/save-subscription.php', window.location.origin);
                                        await fetch(saveUrl.toString(), {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            credentials: 'same-origin',
                                            body: JSON.stringify({ subscription: subscription.toJSON() })
                                        });
                                        
                                        showPushStatus('✓ Notifications enabled!', false);
                                        localStorage.setItem('pushNotificationsEnabled', 'true');
                                        if (toggle) {
                                            toggle.checked = true;
                                        }
                                    } else {
                                        throw new Error('Failed to fetch VAPID key');
                                    }
                                } catch (e) {
                                    console.error('Fallback subscription failed:', e);
                                    showPushStatus('Failed to enable: ' + e.message, true);
                                    if (toggle) {
                                        toggle.checked = false;
                                    }
                                }
                            } else {
                                showPushStatus('Notifications blocked. Enable in browser settings.', true);
                                if (toggle) {
                                    toggle.checked = false;
                                }
                            }
                        } else {
                            showPushStatus('Push notifications not supported', true);
                            if (toggle) {
                                toggle.checked = false;
                            }
                        }
                        if (typeof window.setUpdatingPushState === 'function') {
                            window.setUpdatingPushState(false);
                        }
                        return;
                    }
                }

                await fetchVapidKey();
                if (!VAPID_PUBLIC_KEY) {
                    showPushStatus('Failed to load configuration. Retrying...', false);
                    // Retry fetching VAPID key (may need more time on mobile)
                    await new Promise(resolve => setTimeout(resolve, 500));
                    await fetchVapidKey();
                    
                    if (!VAPID_PUBLIC_KEY) {
                        showPushStatus('Failed to load configuration', true);
                        if (toggle) {
                            toggle.checked = false;
                        }
                        if (typeof window.setUpdatingPushState === 'function') {
                            window.setUpdatingPushState(false);
                        }
                        return;
                    }
                }
                
                showPushStatus('Requesting permission...', false);
                
                const result = await promptForPushNotifications(VAPID_PUBLIC_KEY);
                
                if (result.success) {
                    showPushStatus('✓ Notifications enabled!', false);
                    localStorage.setItem('pushNotificationsEnabled', 'true');
                    
                    // Verify subscription exists
                    try {
                        const registration = await navigator.serviceWorker.ready;
                        const subscription = await registration.pushManager.getSubscription();
                        if (toggle) {
                            toggle.checked = subscription !== null;
                        }
                        console.log('Push notifications enabled - subscription verified:', subscription !== null);
                    } catch (e) {
                        console.error('Error verifying subscription:', e);
                        if (toggle) {
                            toggle.checked = true; // Assume success if we got here
                        }
                    }
                } else {
                    const fallbackMessage = () => {
                        if (result.error === 'permission_denied') {
                            return 'Notifications blocked. Enable in browser settings.';
                        }
                        if (result.error === 'not_supported') {
                            return 'Push notifications not supported';
                        }
                        if (result.error === 'permission_required') {
                            return 'Permission is required to enable notifications';
                        }
                        return 'Failed to enable notifications: ' + (result.error || 'Unknown error');
                    };
                    const errorMessage = (typeof describePushError === 'function')
                        ? describePushError(result)
                        : fallbackMessage();
                    showPushStatus(errorMessage, true);
                    if (toggle) {
                        toggle.checked = false;
                    }
                }
            } catch (error) {
                console.error('Error enabling push notifications:', error);
                showPushStatus('Error: ' + error.message, true);
                if (toggle) {
                    toggle.checked = false;
                }
            } finally {
                // Always clear the flag
                if (typeof window.setUpdatingPushState === 'function') {
                    window.setUpdatingPushState(false);
                }
            }
        }

        // Disable push notifications
        async function disablePushNotifications() {
            const toggle = document.getElementById('pushNotificationToggle');
            
            try {
                // Set flag to prevent state updates during operation
                if (typeof window.setUpdatingPushState === 'function') {
                    window.setUpdatingPushState(true);
                }
                
                // Keep toggle off while processing
                if (toggle) {
                    toggle.checked = false;
                }
                
                // Ensure service worker is ready
                if ('serviceWorker' in navigator) {
                    try {
                        await navigator.serviceWorker.ready;
                    } catch (e) {
                        console.warn('Service worker not ready:', e);
                    }
                }

                // Check if function is available
                if (typeof unsubscribeFromPush !== 'function') {
                    // Fallback: manually unsubscribe and remove from localStorage
                    if ('serviceWorker' in navigator) {
                        try {
                            const registration = await navigator.serviceWorker.ready;
                            const subscription = await registration.pushManager.getSubscription();
                            if (subscription) {
                                await subscription.unsubscribe();
                            }
                        } catch (e) {
                            console.warn('Manual unsubscribe failed:', e);
                        }
                    }
                    showPushStatus('Notifications disabled', false);
                    localStorage.removeItem('pushNotificationsEnabled');
                    
                    // Verify unsubscription
                    if (toggle && 'serviceWorker' in navigator) {
                        try {
                            const registration = await navigator.serviceWorker.ready;
                            const subscription = await registration.pushManager.getSubscription();
                            toggle.checked = subscription !== null; // Should be false
                        } catch (e) {
                            toggle.checked = false;
                        }
                    }
                    return;
                }

                const result = await unsubscribeFromPush();
                if (result.success) {
                    showPushStatus('Notifications disabled', false);
                    localStorage.removeItem('pushNotificationsEnabled');
                    
                    // Verify unsubscription
                    if (toggle && 'serviceWorker' in navigator) {
                        try {
                            const registration = await navigator.serviceWorker.ready;
                            const subscription = await registration.pushManager.getSubscription();
                            toggle.checked = subscription !== null; // Should be false
                            console.log('Push notifications disabled - subscription verified:', subscription === null);
                        } catch (e) {
                            console.error('Error verifying unsubscription:', e);
                            toggle.checked = false;
                        }
                    }
                } else {
                    showPushStatus('Failed to disable notifications', true);
                    if (toggle) {
                        toggle.checked = true;
                    }
                }
            } catch (error) {
                console.error('Error disabling push notifications:', error);
                showPushStatus('Error: ' + error.message, true);
                if (toggle) {
                    toggle.checked = true;
                }
            } finally {
                // Always clear the flag
                if (typeof window.setUpdatingPushState === 'function') {
                    window.setUpdatingPushState(false);
                }
            }
        }
    </script>
    <!-- Register Service Worker for PWA (optimized for ngrok/median.co) -->
    <script>
        if ('serviceWorker' in navigator) {
            // Register immediately and also on load (better for mobile)
            (function registerServiceWorker() {
                // Determine correct path based on current location (works with ngrok/median.co)
                const getBasePath = function() {
                    const pathname = window.location.pathname;
                    const marker = '/mobile-app/';
                    const idx = pathname.indexOf(marker);
                    if (idx !== -1) {
                        return pathname.substring(0, idx + marker.length);
                    }
                    // Fallback: try to determine from origin and pathname
                    const origin = window.location.origin;
                    // For ngrok/median.co, use the full path
                    return '/mobile-app/';
                };
                
                const basePath = getBasePath();
                const swPath = basePath + 'service-worker.js';
                
                navigator.serviceWorker.register(swPath, {
                    scope: basePath
                })
                .then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    
                    // For mobile devices, ensure service worker is ready before initializing push
                    if ('PushManager' in window && 'Notification' in window) {
                        registration.update(); // Check for updates
                        
                        // Once ready, try to initialize push notifications if permission is already granted
                        navigator.serviceWorker.ready.then(function() {
                            if (Notification.permission === 'granted') {
                                // Permission already granted, try to subscribe (only if not already updating)
                                setTimeout(() => {
                                    if (typeof updateToggleState === 'function' && 
                                        (!window.isUpdatingPushState || !window.isUpdatingPushState())) {
                                        updateToggleState();
                                    }
                                }, 1500); // Increased delay to avoid race conditions
                            }
                        });
                    }
                })
                .catch(function(error) {
                    // Only log if it's not a 404 (file might not exist in some environments)
                    if (error.message && !error.message.includes('404') && !error.message.includes('bad HTTP response code')) {
                        console.warn('ServiceWorker registration warning: ', error.message);
                    }
                });
            })();
            
            // Also register on load as backup
            window.addEventListener('load', function() {
                // Already registered above, but this ensures it's done on load as well
                if (!navigator.serviceWorker.controller) {
                    // If no controller, try registering again
                    setTimeout(function() {
                        const pathname = window.location.pathname;
                        const marker = '/mobile-app/';
                        const idx = pathname.indexOf(marker);
                        const basePath = idx !== -1 ? pathname.substring(0, idx + marker.length) : '/mobile-app/';
                        const swPath = basePath + 'service-worker.js';
                        
                        navigator.serviceWorker.register(swPath, {
                            scope: basePath
                        }).catch(function(error) {
                            if (error.message && !error.message.includes('404')) {
                                console.warn('ServiceWorker backup registration warning: ', error.message);
                            }
                        });
                    }, 500);
                }
            });
        }
    </script>
</body>
</html> 