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

// Fetch donation history from eligibility table to calculate stats
$donation_history = [];
$total_donations = 0;
$last_donation_date = 'N/A';

if ($user && isset($user['id'])) {
    $params = [
        'donor_id' => 'eq.' . $user['id'],
        'order' => 'collection_start_time.desc'
    ];
    $result = get_records('eligibility', $params);
    if ($result['success'] && !empty($result['data'])) {
        $donation_history = $result['data'];
        $total_donations = count($donation_history);
        if ($total_donations > 0 && isset($donation_history[0]['collection_start_time'])) {
            $last_donation_date = date('F j, Y', strtotime($donation_history[0]['collection_start_time']));
        }
    }
}

// Calculate age from birthdate
$age = 'N/A';
if (!empty($donor_details['birthdate'])) {
    $birthDate = new DateTime($donor_details['birthdate']);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
}

// Get blood type
$blood_type = htmlspecialchars($donor_details['blood_type'] ?? 'N/A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Enhanced viewport settings for better mobile rendering -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF0000">
    <title>Red Cross - Profile</title>
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
            background-image: url('../assets/icons/user-avatar-placeholder.png'); /* Placeholder image */
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
            color: #FF0000;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
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

        .logout-confirm-btn {
            background: #D50000;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Profile</h1>
    </div>
    
    <div class="profile-container">
        <div class="profile-avatar"></div>
        <div class="profile-name">
            <?php 
                $firstName = htmlspecialchars($donor_details['first_name'] ?? '');
                $lastName = htmlspecialchars($donor_details['surname'] ?? '');
                echo trim("$firstName $lastName");
            ?>
        </div>
        <div class="last-donation">Last Donation: <?php echo $last_donation_date; ?></div>

        <div class="stats-container">
            <div class="stat-box">
                <div class="label">Age</div>
                <div class="value"><?php echo $age; ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Blood Type</div>
                <div class="value"><?php echo $blood_type; ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Donations</div>
                <div class="value"><?php echo str_pad($total_donations, 2, '0', STR_PAD_LEFT); ?></div>
            </div>
        </div>

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
                    <input type="checkbox" checked>
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
            <div class="nav-label">Explore</div>
        </a>
        <a href="profile.php" class="nav-button active">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/app.js"></script>
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
        });
    </script>
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
    </script>
</body>
</html> 