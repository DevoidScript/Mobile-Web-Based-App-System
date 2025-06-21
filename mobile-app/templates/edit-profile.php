<?php
/**
 * Edit Profile Page for the Red Cross Mobile App
 *
 * This page allows users to edit their personal information.
 *
 * Path: templates/edit-profile.php
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
    header('Location: ../index.php?error=Please login to edit your profile');
    exit;
}

// Get user data
$user = $_SESSION['user'] ?? null;
$donor_details = $_SESSION['donor_details'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // NOTE: This is a placeholder for the update logic.
    // In a real application, you would sanitize and validate the input,
    // then update the database via an API call.
    
    // For now, we'll just redirect back to the profile page.
    header('Location: profile.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF0000">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .edit-profile-header {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .back-arrow a {
            font-size: 24px;
            color: #333;
            text-decoration: none;
        }

        .header-title {
            flex-grow: 1;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-right: 30px; /* Offset for back arrow */
        }

        .container {
            padding: 20px;
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 10px;
            background-color: #007bff;
            background-image: url('../assets/icons/user-avatar-placeholder.png');
            background-size: cover;
        }

        .change-picture-link {
            display: block;
            margin-bottom: 30px;
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        .email-input-wrapper {
            position: relative;
        }

        .email-input-wrapper .email-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .save-btn {
            width: 100%;
            padding: 15px;
            background-color: #D50000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .message {
            margin-bottom: 20px;
            color: green;
            font-weight: bold;
        }

        /* Loader styles */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: none; /* Hidden by default */
            justify-content: center;
            align-items: center;
        }

        .loader {
            border: 8px solid #f3f3f3; /* Light grey */
            border-top: 8px solid #D50000; /* Red */
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="edit-profile-header">
        <div class="back-arrow">
            <a href="profile.php">&#8249;</a>
        </div>
        <div class="header-title">Edit Profile</div>
    </div>

    <div class="container">
        <div class="profile-avatar"></div>
        <a href="#" class="change-picture-link">Change Picture</a>

        <form action="edit-profile.php" method="POST">
            <div class="form-group">
                <label for="name">Name</label>
                <input disabled type="text" id="name" name="name" value="<?php echo htmlspecialchars(($donor_details['first_name'] ?? '') . ' ' . ($donor_details['surname'] ?? '')); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="email-input-wrapper">
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($donor_details['email'] ?? $user['email'] ?? ''); ?>">
                    <span class="email-icon">✉️</span>
                </div>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($donor_details['mobile'] ?? ''); ?>">
            </div>

            <button type="submit" class="save-btn">Save Changes</button>
        </form>
    </div>

    <div class="loader-overlay" id="loader-overlay">
        <div class="loader"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const loaderOverlay = document.getElementById('loader-overlay');

            form.addEventListener('submit', function(event) {
                // Prevent the form from submitting immediately
                event.preventDefault();

                // Show the loader
                if (loaderOverlay) {
                    loaderOverlay.style.display = 'flex';
                }

                // Simulate saving for 1.5 seconds, then submit
                setTimeout(function() {
                    form.submit();
                }, 1500);
            });
        });
    </script>

</body>
</html> 