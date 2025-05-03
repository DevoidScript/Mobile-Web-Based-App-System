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
            padding: 0 40px; /* Make space for the logo */
        }
        
        .profile-container {
            padding: 15px;
            margin-bottom: 70px; /* Space for bottom navigation */
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .profile-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .profile-section h2 {
            color: #FF0000;
            margin-top: 0;
            font-size: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .profile-detail {
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap;
        }
        
        .detail-label {
            font-weight: bold;
            min-width: 140px;
            padding-right: 10px;
        }
        
        .detail-value {
            flex: 1;
            word-break: break-word;
        }
        
        .edit-btn {
            width: 100%;
            padding: 15px;
            background-color:rgb(48, 138, 85);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        
        /* 
         * Logout button style moved from dashboard
         * Maintains consistent styling with the edit button
         */
        .logout-btn {
            width: 100%;
            padding: 15px;
            background-color: #FF0000;
            color: #FFFFFF; /* Ensuring high contrast white color for better visibility */
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            -webkit-appearance: none; /* Remove default styling on iOS */
            min-height: 50px; /* Ensure good touch target size */
            text-shadow: 0 1px 1px rgba(0,0,0,0.2); /* Adding text shadow for better readability on red background */
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
        
        /* Responsive adjustments */
        @media (max-width: 360px) {
            .profile-detail {
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
        <img src="../assets/icons/redcrosslogo.jpg" alt="Philippine Red Cross Logo" class="logo-small">
        <h1>User Profile</h1>
    </div>
    
    <div class="profile-container">
        <?php if ($donor_details): ?>
            <div class="profile-section">
                <h2>Personal Information</h2>
                
                <div class="profile-detail">
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
                
                <div class="profile-detail">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($donor_details['email'] ?? $user['email'] ?? ''); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="detail-label">Mobile:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($donor_details['mobile'] ?? ''); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="detail-label">Birthdate:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($donor_details['birthdate'] ?? ''); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="detail-label">Sex:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($donor_details['sex'] ?? ''); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="detail-label">Civil Status:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($donor_details['civil_status'] ?? ''); ?></div>
                </div>
            </div>
            
            <div class="profile-section">
                <h2>Additional Information</h2>
                
                <div class="profile-detail">
                    <div class="detail-label">Address:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($donor_details['permanent_address'] ?? ''); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="detail-label">Nationality:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($donor_details['nationality'] ?? ''); ?></div>
                </div>
                
                <div class="profile-detail">
                    <div class="detail-label">Occupation:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($donor_details['occupation'] ?? ''); ?></div>
                </div>
                
                <?php if (!empty($donor_details['religion'])): ?>
                <div class="profile-detail">
                    <div class="detail-label">Religion:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($donor_details['religion']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($donor_details['education'])): ?>
                <div class="profile-detail">
                    <div class="detail-label">Education:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($donor_details['education']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Edit button for future functionality -->
            <button type="button" class="edit-btn" onclick="alert('Profile editing will be available in a future update.')">Edit Profile</button>
            
            <!-- 
             * Logout button moved from dashboard to profile page
             * Placing it here makes the dashboard cleaner while keeping the logout functionality easily accessible
             -->
            <form action="../api/auth.php?logout" method="POST" style="margin-top: 20px;">
                <button type="submit" class="logout-btn" style="color: #FFFFFF !important; background-color: #FF0000 !important;">Logout</button>
            </form>
            
        <?php else: ?>
            <div class="profile-section">
                <h2>Profile Information</h2>
                <p>Your profile information is not complete. Please update your details.</p>
                
                <?php if ($user): ?>
                <div class="profile-detail">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                </div>
                <?php endif; ?>
                
                <button type="button" class="edit-btn" onclick="alert('Profile editing will be available in a future update.')">Complete Profile</button>
                
                <!-- 
                 * Logout button moved from dashboard to profile page
                 * Placing it here makes the dashboard cleaner while keeping the logout functionality easily accessible
                 -->
                <form action="../api/auth.php?logout" method="POST" style="margin-top: 20px;">
                    <button type="submit" class="logout-btn" style="color: #FFFFFF !important; background-color: #FF0000 !important;">Logout</button>
                </form>
            </div>
        <?php endif; ?>
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
        <a href="profile.php" class="nav-button active">
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