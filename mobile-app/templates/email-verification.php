<?php
/**
 * Email Verification Page
 * 
 * This page handles email verification for newly registered users.
 * Users must enter the 6-digit verification code sent to their email
 * to complete their registration process.
 */

// Include configuration files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$errors = [];
$success_message = '';
$email = '';
$user_name = '';

// Check if user has pending verification
if (!isset($_SESSION['pending_verification'])) {
    header('Location: ../index.php?error=No pending verification found. Please register first.');
    exit;
}

$pending_verification = $_SESSION['pending_verification'];
$email = $pending_verification['email'];
$user_name = $pending_verification['user_name'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_code = sanitize_input($_POST['verification_code'] ?? '');
    
    // Validate verification code
    if (empty($verification_code)) {
        $errors[] = "Verification code is required";
    } elseif (!preg_match('/^\d{6}$/', $verification_code)) {
        $errors[] = "Verification code must be 6 digits";
    } else {
        // Verify the code
        $verify_result = verify_email_code($email, $verification_code);
        
        if ($verify_result['success']) {
            // Clear pending verification session
            unset($_SESSION['pending_verification']);
            
            // Set success message and redirect to login
            $_SESSION['success'] = "Email verified successfully! You can now login to your account.";
            header('Location: ../templates/login.php?success=Email verified successfully! You can now login to your account.');
            exit;
        } else {
            $errors[] = $verify_result['message'];
        }
    }
}

// Handle resend verification email
if (isset($_POST['resend_code'])) {
    $resend_result = resend_verification_email($email, $pending_verification['user_id'], $user_name);
    
    if ($resend_result['success']) {
        $success_message = "New verification code sent to your email";
    } else {
        $errors[] = $resend_result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#d32f2f">
    <title>Email Verification - Red Cross</title>
    <style>
        /* Base styles optimized for mobile */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .verification-container {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .title {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 20px;
            color: #d32f2f;
        }
        
        .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }
        
        .email-display {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-weight: 600;
            color: #333;
        }
        
        .verification-form {
            margin-bottom: 30px;
        }
        
        .verification-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 5px;
            margin-bottom: 20px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        .verification-input:focus {
            outline: none;
            border-color: #d32f2f;
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1);
        }
        
        .verify-button {
            background-color: #d32f2f;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .verify-button:hover {
            background-color: #b71c1c;
        }
        
        .verify-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .resend-section {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .resend-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .resend-button {
            background-color: transparent;
            color: #d32f2f;
            border: 1px solid #d32f2f;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .resend-button:hover {
            background-color: #d32f2f;
            color: white;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ef9a9a;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #a5d6a7;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #d32f2f;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .timer {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        
        .timer.warning {
            color: #d32f2f;
            font-weight: 600;
        }
        
        /* Mobile-specific adjustments */
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            
            .verification-container {
                padding: 20px;
            }
            
            .title {
                font-size: 20px;
            }
            
            .verification-input {
                font-size: 20px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-container">
            <h1 class="title">📧 Email Verification</h1>
            <p class="subtitle">Please enter the 6-digit verification code sent to your email address</p>
            
            <div class="email-display">
                <?php echo htmlspecialchars($email); ?>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <strong>Verification Error:</strong>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="verification-form">
                <input type="text" 
                       name="verification_code" 
                       class="verification-input" 
                       placeholder="000000" 
                       maxlength="6" 
                       pattern="[0-9]{6}"
                       required
                       autocomplete="off">
                
                <button type="submit" class="verify-button">
                    Verify Email
                </button>
            </form>
            
            <div class="resend-section">
                <p class="resend-text">
                    Didn't receive the code? Check your spam folder or request a new one.
                </p>
                
                <form method="POST" style="display: inline;">
                    <button type="submit" name="resend_code" class="resend-button">
                        Resend Verification Code
                    </button>
                </form>
                
                <div class="timer" id="timer">
                    Code expires in <span id="countdown">15:00</span>
                </div>
            </div>
        </div>
        
        <div class="back-link">
            <a href="../index.php">← Back to Home</a>
        </div>
    </div>
    
    <script>
        // Countdown timer for verification code expiry
        let timeLeft = 15 * 60; // 15 minutes in seconds
        const timerElement = document.getElementById('countdown');
        const timerContainer = document.getElementById('timer');
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerElement.textContent = 
                minutes.toString().padStart(2, '0') + ':' + 
                seconds.toString().padStart(2, '0');
            
            if (timeLeft <= 300) { // 5 minutes or less
                timerContainer.classList.add('warning');
            }
            
            if (timeLeft <= 0) {
                timerElement.textContent = '00:00';
                timerContainer.innerHTML = 'Verification code has expired. Please request a new one.';
                timerContainer.style.color = '#d32f2f';
                timerContainer.style.fontWeight = '600';
            } else {
                timeLeft--;
            }
        }
        
        // Update timer every second
        setInterval(updateTimer, 1000);
        
        // Auto-format verification code input
        const verificationInput = document.querySelector('.verification-input');
        
        verificationInput.addEventListener('input', function(e) {
            // Remove any non-digit characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 6 digits
            if (this.value.length > 6) {
                this.value = this.value.slice(0, 6);
            }
        });
        
        // Auto-submit when 6 digits are entered
        verificationInput.addEventListener('input', function(e) {
            if (this.value.length === 6) {
                // Small delay to show the complete code
                setTimeout(() => {
                    this.form.submit();
                }, 500);
            }
        });
        
        // Focus on input when page loads
        window.addEventListener('load', function() {
            verificationInput.focus();
        });
        
        // Prevent form submission if timer has expired
        document.querySelector('.verification-form').addEventListener('submit', function(e) {
            if (timeLeft <= 0) {
                e.preventDefault();
                alert('Verification code has expired. Please request a new one.');
                return false;
            }
        });
    </script>
</body>
</html>

