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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        /* Additional styles specific to the dashboard page */
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        
        .header {
            background-color: #FF0000;
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
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
        }
        
        .dashboard-container {
            padding: 20px;
            margin-bottom: 60px; /* Space for bottom navigation */
        }
        
        .user-info {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .user-info h2 {
            color: #FF0000;
            margin-top: 0;
            font-size: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .user-detail {
            margin-bottom: 10px;
            display: flex;
        }
        
        .detail-label {
            font-weight: bold;
            min-width: 120px;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            color: #FF0000;
            margin-top: 0;
            font-size: 16px;
        }
        
        .card-content {
            display: flex;
            align-items: center;
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
        }
        
        .nav-button {
            color: white;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
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
            <div class="card-content">
                <div class="card-icon">❤️</div>
                <div class="card-text">Schedule your next blood donation appointment</div>
            </div>
        </div>
        
        <div class="card">
            <h3>Donation History</h3>
            <div class="card-content">
                <div class="card-icon">📋</div>
                <div class="card-text">View your past donations and upcoming appointments</div>
            </div>
        </div>
        
        <div class="card">
            <h3>Find Blood Centers</h3>
            <div class="card-content">
                <div class="card-icon">🏥</div>
                <div class="card-text">Locate the nearest Red Cross blood center</div>
            </div>
        </div>
        
        <form action="api/auth.php?logout" method="POST">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
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