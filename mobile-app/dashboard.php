<?php
/**
 * Dashboard page for the Red Cross Mobile App
 * This page is shown after successful login
 */

// Set error reporting in development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: index.php?error=Please login to access the dashboard');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Enhanced viewport settings for better mobile rendering -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF0000">
    <title>Red Cross Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
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
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
            font-size: 16px; /* Base font size for better readability on mobile */
            -webkit-tap-highlight-color: transparent; /* Remove tap highlight on mobile */
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
            padding: 0 40px; /* Make space for the logo */
        }
        
        .dashboard-container {
            padding: 15px;
            margin-bottom: 70px; /* Increased space for bottom navigation */
            max-width: 600px; /* Limit width for larger phones */
            margin-left: auto;
            margin-right: auto;
        }
        
        .user-info {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .user-info h2 {
            color: #FF0000;
            margin-top: 0;
            font-size: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .user-detail {
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap; /* Allow wrapping on very small screens */
        }
        
        .detail-label {
            font-weight: bold;
            min-width: 100px;
            padding-right: 10px;
        }
        
        .detail-value {
            flex: 1;
            word-break: break-word; /* Prevent overflow for long values */
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            color: #FF0000;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .card-content {
            display: flex;
            align-items: center;
            min-height: 60px; /* Ensure minimum height for better touch targets */
        }
        
        .card-icon {
            background-color: #ffebee;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
            color: #FF0000;
            flex-shrink: 0; /* Prevent icon from shrinking */
        }
        
        .card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            padding: 5px; /* Additional padding for touch area */
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }
        
        .card-link:hover {
            text-decoration: none;
        }
        
        .card-link:active {
            background-color: #f5f5f5; /* Visual feedback on touch */
        }
        
        .card-link:hover .card-icon,
        .card-link:active .card-icon {
            background-color: #ffcdd2;
            transform: scale(1.05);
            transition: all 0.2s ease;
        }
        
        .card-text {
            flex: 1;
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
            width: 25%;
            padding: 5px 0;
            touch-action: manipulation; /* Optimize for touch */
            text-decoration: none; /* Remove underline from links */
        }
        
        .nav-button:active {
            opacity: 0.7; /* Visual feedback on touch */
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
            .card-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-icon {
                margin-bottom: 10px;
                margin-right: 0;
            }
            
            .user-detail {
                flex-direction: column;
            }
            
            .detail-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="assets/icons/redcrosslogo.jpg" alt="Philippine Red Cross Logo" class="logo-small">
        <h1>Philippine Red Cross</h1>
    </div>
    
    <div class="dashboard-container">
        <div class="user-info">
            <h2>Welcome, <?php echo htmlspecialchars($donor_details['first_name'] ?? $user['email'] ?? 'User'); ?>!</h2>
            
            <?php if ($donor_details): ?>
            <div class="user-detail">
                <div class="detail-label">Name:</div>
                <div class="detail-value">
                    <?php 
                        echo htmlspecialchars($donor_details['first_name'] ?? '');
                        echo ' ';
                        echo htmlspecialchars($donor_details['middle_name'] ?? '');
                        echo ' ';
                        echo htmlspecialchars($donor_details['surname'] ?? '');
                    ?>
                </div>
            </div>
            
            <div class="user-detail">
                <div class="detail-label">Email:</div>
                <div class="detail-value"><?php echo htmlspecialchars($donor_details['email'] ?? $user['email'] ?? ''); ?></div>
            </div>
            
            <div class="user-detail">
                <div class="detail-label">Mobile:</div>
                <div class="detail-value"><?php echo htmlspecialchars($donor_details['mobile'] ?? ''); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>Donate Blood</h3>
            <!-- Make the entire card clickable for blood donation -->
            <a href="blood_donation.php" class="card-link">
                <div class="card-content">
                    <div class="card-icon">❤️</div>
                    <div class="card-text">Schedule your next blood donation appointment</div>
                </div>
            </a>
        </div>
        
        <div class="card">
            <h3>Donation History</h3>
            <!-- Card to view donation history -->
            <a href="donation_history.php" class="card-link">
                <div class="card-content">
                    <div class="card-icon">📋</div>
                    <div class="card-text">View your past donations and upcoming appointments</div>
                </div>
            </a>
        </div>
        
        <div class="card">
            <h3>Find Blood Centers</h3>
            <!-- Card to find nearest blood centers -->
            <a href="blood_centers.php" class="card-link">
                <div class="card-content">
                    <div class="card-icon">🏥</div>
                    <div class="card-text">Locate the nearest Red Cross blood center</div>
                </div>
            </a>
        </div>
        
        <form action="api/auth.php?logout" method="POST">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
    
    <!-- Mobile-optimized bottom navigation bar -->
    <div class="navigation-bar">
        <a href="dashboard.php" class="nav-button">
            <div class="nav-icon">🏠</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="blood_donation.php" class="nav-button">
            <div class="nav-icon">❤️</div>
            <div class="nav-label">Donate</div>
        </a>
        <a href="donation_history.php" class="nav-button">
            <div class="nav-icon">📋</div>
            <div class="nav-label">History</div>
        </a>
        <a href="profile.php" class="nav-button">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
    
    <!-- Scripts -->
    <script src="assets/js/app.js"></script>
    <!-- Register Service Worker for PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('service-worker.js')
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