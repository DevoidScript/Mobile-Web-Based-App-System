<?php
/**
 * Donation History Page for the Red Cross Mobile App
 * 
 * This is a placeholder page for the donation history functionality.
 * It will be implemented in future updates to display user's donation records.
 * Created as part of the templates organization structure.
 *
 * Path: templates/donation_history.php
 */

// Set error reporting in development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../index.php?error=Please login to access your donation history');
    exit;
}

// Get user data
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF0000">
    <title>Red Cross - Donation History</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="Red Cross Mobile Application - Donation History">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <style>
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
        
        .history-container {
            padding: 15px;
            margin-bottom: 70px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .message-box {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .message-box h2 {
            color: #FF0000;
            margin-top: 0;
        }
        
        .placeholder-image {
            width: 100%;
            max-width: 250px;
            margin: 20px auto;
            display: block;
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
            width: 25%;
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
        <h1>Donation History</h1>
    </div>
    
    <div class="history-container">
        <div class="message-box">
            <h2>Coming Soon</h2>
            <p>The donation history feature is currently under development.</p>
            <p>Soon you'll be able to view your past donations and upcoming appointments here.</p>
            
            <!-- Placeholder image -->
            <img src="../assets/icons/redcrosslogo.jpg" alt="Coming Soon" class="placeholder-image">
            
            <p>Thank you for your patience as we work to improve your experience with the Red Cross mobile app.</p>
        </div>
    </div>
    
    <!-- Mobile-optimized bottom navigation bar -->
    <div class="navigation-bar">
        <a href="dashboard.php" class="nav-button">
            <div class="nav-icon">🏠</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="blood-session.php" class="nav-button">
            <div class="nav-icon">❤️</div>
            <div class="nav-label">Donate</div>
        </a>
        <a href="donation_history.php" class="nav-button active">
            <div class="nav-icon">📋</div>
            <div class="nav-label">History</div>
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
    </script>
</body>
</html> 