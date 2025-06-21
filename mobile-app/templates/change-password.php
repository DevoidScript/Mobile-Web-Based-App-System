<?php
/**
 * Change Password Page for the Red Cross Mobile App
 *
 * This page allows users to change their account password.
 *
 * Path: templates/change-password.php
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
    header('Location: ../index.php?error=Please login to change your password');
    exit;
}

// Handle form submission (placeholder)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Placeholder for password update logic
    $message = "Password updated successfully! (Simulation)";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .header {
            display: flex;
            align-items: center;
            padding: 15px;
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
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .password-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
            padding-right: 40px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
        }

        .update-btn {
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
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="back-arrow">
            <a href="profile.php">&#8249;</a>
        </div>
        <div class="header-title">Change Password</div>
    </div>

    <div class="container">
        <?php if (isset($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="change-password.php" method="POST">
            <div class="form-group">
                <label for="current-password">Current Password</label>
                <div class="password-wrapper">
                    <input type="password" id="current-password" name="current-password" placeholder="Enter password here">
                    <span class="toggle-password">👁️</span>
                </div>
            </div>
            <div class="form-group">
                <label for="new-password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="new-password" name="new-password" placeholder="Enter new password here">
                    <span class="toggle-password">👁️</span>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm new password">
                    <span class="toggle-password">👁️</span>
                </div>
            </div>

            <button type="submit" class="update-btn">Update Password</button>
        </form>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(item => {
            item.addEventListener('click', function (e) {
                const passwordInput = this.previousElementSibling;
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.textContent = '🙈';
                } else {
                    passwordInput.type = 'password';
                    this.textContent = '👁️';
                }
            });
        });
    </script>

</body>
</html> 