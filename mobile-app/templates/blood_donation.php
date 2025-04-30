<?php
/**
 * Blood Donation Page for the Red Cross Mobile App
 * 
 * This is a placeholder page for the blood donation functionality.
 * It will be implemented in future updates with appointment scheduling features.
 * Created as part of the templates organization structure.
 *
 * Path: templates/blood_donation.php
 * 
 * WORKFLOW REDIRECTION UPDATE:
 * The "Start Donation Process" button now points to blood-session.php which acts as 
 * an entry point for the donor registration process. The complete workflow is:
 * 
 * 1. blood_donation.php (user clicks "Start Donation Process")
 * 2. blood-session.php (initializes session and workflow)
 * 3. donor-form-modal.php (collects basic donor information)
 * 4. medical-history-modal.php (medical history questionnaire)
 * 5. declaration-form-modal.php (final declaration and consent)
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
    header('Location: ../index.php?error=Please login to access the blood donation page');
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
    <title>Red Cross - Blood Donation</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="Red Cross Mobile Application - Blood Donation">
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
        
        .donation-container {
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
        
        .coming-soon-img {
            width: 100%;
            max-width: 300px;
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
        <h1>Blood Donation</h1>
    </div>
    
    <div class="donation-container">
        <div class="message-box">
            <h2>Blood Donation Registration</h2>
            <p>You can now start your blood donation process by clicking the button below.</p>
            <p>The process involves three main steps:</p>
            <ol style="text-align: left; padding-left: 30px;">
                <li>Filling out the donor information form</li>
                <li>Completing the medical history questionnaire</li>
                <li>Signing the donor declaration</li>
            </ol>
            
            <!-- Red Cross Logo -->
            <img src="../assets/icons/redcrosslogo.jpg" alt="Red Cross Logo" class="coming-soon-img">
            
            <p>Thank you for your interest in donating blood and saving lives!</p>
            
            <!-- 
             * Button to start the donation process flow
             * The flow sequence: donor-form-modal.php → medical-history-modal.php → declaration-form-modal.php
             * Clicking this button starts the donor registration workflow
             -->
            <a href="forms/donor-form-modal.php" class="btn primary full-width" style="background-color: #FF0000; color: white; padding: 15px; border-radius: 5px; text-decoration: none; display: block; text-align: center; margin-top: 20px; font-weight: bold;">
                Start Donation Process
            </a>
        </div>
    </div>
    
    <!-- Mobile-optimized bottom navigation bar -->
    <div class="navigation-bar">
        <a href="dashboard.php" class="nav-button">
            <div class="nav-icon">🏠</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="blood_donation.php" class="nav-button active">
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