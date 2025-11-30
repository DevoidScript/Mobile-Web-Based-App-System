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

// Handle form submission (functional with Supabase Auth)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current-password'] ?? '';
    $new_password = $_POST['new-password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';
    $email = $_POST['email'] ?? '';
    $error_message = '';
    $success_message = '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password) || empty($email)) {
        $error_message = 'All fields are required.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'New password must be at least 8 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New password and confirmation do not match.';
    } else {
        // Step 1: Sign in to verify current password and get access token
        $supabase_url = SUPABASE_URL;
        $supabase_key = SUPABASE_API_KEY;
        $sign_in_url = rtrim($supabase_url, '/') . '/auth/v1/token?grant_type=password';
        $sign_in_data = json_encode([
            'email' => $email,
            'password' => $current_password
        ]);
        $ch = curl_init($sign_in_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sign_in_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $supabase_key
        ]);
        $sign_in_response = curl_exec($ch);
        $sign_in_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $sign_in_result = json_decode($sign_in_response, true);

        if ($sign_in_status !== 200 || empty($sign_in_result['access_token'])) {
            $error_message = 'Current password is incorrect.';
        } else {
            // Step 2: Update password using the access token
            $access_token = $sign_in_result['access_token'];
            $update_url = rtrim($supabase_url, '/') . '/auth/v1/user';
            $update_data = json_encode(['password' => $new_password]);
            $ch = curl_init($update_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $update_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'apikey: ' . $supabase_key,
                'Authorization: Bearer ' . $access_token
            ]);
            $update_response = curl_exec($ch);
            $update_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($update_status === 200) {
                $success_message = 'Password updated successfully!';
            } else {
                $error_message = 'Failed to update password. Please try again.';
            }
        }
    }
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
            background-color: #FF0000;
            color: white;
        }

        .back-arrow a {
            font-size: 24px;
            color: white;
            text-decoration: none;
        }

        .header-title {
            flex-grow: 1;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-right: 30px; /* Offset for back arrow */
            color: white;
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
        
        .password-hint {
            font-size: 13px;
            color: #666;
            margin-top: 6px;
            margin-bottom: 0;
            font-style: italic;
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
        <?php if (!empty($success_message)): ?>
            <div class="message" style="color:green;"><?php echo htmlspecialchars($success_message); ?></div>
        <?php elseif (!empty($error_message)): ?>
            <div class="message" style="color:red;"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="change-password.php" method="POST">
            <?php $user = $_SESSION['user'] ?? null; $user_email = $user['email'] ?? ''; ?>
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($user_email); ?>">
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
                    <input type="password" id="new-password" name="new-password" placeholder="Enter new password here" required minlength="8">
                    <span class="toggle-password">👁️</span>
                </div>
                <p class="password-hint">Password must be at least 8 characters long</p>
            </div>
            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm new password" required minlength="8">
                    <span class="toggle-password">👁️</span>
                </div>
                <p class="password-hint">Password must be at least 8 characters long</p>
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