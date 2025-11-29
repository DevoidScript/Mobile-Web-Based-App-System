<?php
/**
 * Email Configuration for Gmail SMTP
 * 
 * This file contains email configuration settings for sending verification emails
 * through Gmail SMTP using PHPMailer.
 */

// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noca.kellyjohn@gmail.com'); // Replace with your Gmail address
define('SMTP_PASSWORD', 'ckpbcnjmejngegpx'); // Replace with your Gmail App Password
define('SMTP_FROM_EMAIL', 'noca.kellyjohn@gmail.com'); // Replace with your Gmail address
define('SMTP_FROM_NAME', 'Red Cross Blood Donation System');

// Email settings
define('EMAIL_VERIFICATION_SUBJECT', 'Email Verification - Red Cross Blood Donation');
define('EMAIL_VERIFICATION_EXPIRY_MINUTES', 15); // Verification code expires in 15 minutes

/**
 * Get email configuration array
 * 
 * @return array Email configuration
 */
function get_email_config() {
    return [
        'host' => SMTP_HOST,
        'port' => SMTP_PORT,
        'username' => SMTP_USERNAME,
        'password' => SMTP_PASSWORD,
        'from_email' => SMTP_FROM_EMAIL,
        'from_name' => SMTP_FROM_NAME,
        'encryption' => 'tls'
    ];
}

/**
 * Generate a 6-digit verification code
 * 
 * @return string 6-digit verification code
 */
function generate_verification_code() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send verification email using PHPMailer
 * 
 * @param string $to_email Recipient email address
 * @param string $verification_code 6-digit verification code
 * @param string $user_name User's name for personalization
 * @param string $context Context: 'registration' or 'password_reset' (default: 'registration')
 * @return array Result array with success status and message
 */
function send_verification_email($to_email, $verification_code, $user_name = '', $context = 'registration') {
    try {
        // Include PHPMailer files explicitly
        $phpmailer_base = __DIR__ . '/../vendor/phpmailer/phpmailer/src/';
        
        // Load required PHPMailer files in correct order
        $required_files = [
            'Exception.php',
            'SMTP.php', 
            'PHPMailer.php'
        ];
        
        foreach ($required_files as $file) {
            $file_path = $phpmailer_base . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                throw new Exception("PHPMailer file not found: $file");
            }
        }
        
        // Check if classes exist after loading
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            throw new Exception('PHPMailer class not found after loading.');
        }
        
        if (!class_exists('PHPMailer\\PHPMailer\\SMTP')) {
            throw new Exception('SMTP class not found after loading.');
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $user_name);
        
        // Content
        $mail->isHTML(true);
        
        // Set subject based on context
        if ($context === 'password_reset') {
            $mail->Subject = 'Password Reset Code - Red Cross Blood Donation';
            $mail->Body = get_password_reset_verification_email_template($verification_code, $user_name);
            $mail->AltBody = get_password_reset_verification_email_text($verification_code, $user_name);
        } else {
        $mail->Subject = EMAIL_VERIFICATION_SUBJECT;
        $mail->Body = get_verification_email_template($verification_code, $user_name);
        $mail->AltBody = get_verification_email_text($verification_code, $user_name);
        }
        
        $mail->send();
        
        error_log("Verification email sent successfully to: $to_email");
        
        return [
            'success' => true,
            'message' => 'Verification email sent successfully'
        ];
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Email sending failed to $to_email: " . $error_message);
        error_log("SMTP Config - Host: " . SMTP_HOST . ", Port: " . SMTP_PORT . ", Username: " . SMTP_USERNAME);
        
        // Try fallback method
        try {
            $fallback_result = send_verification_email_fallback($to_email, $verification_code, $user_name, $context);
            if ($fallback_result['success']) {
                return $fallback_result;
            }
        } catch (Exception $fallback_e) {
            error_log("Fallback email sending also failed: " . $fallback_e->getMessage());
        }
        
        // Return the actual PHPMailer error
        return [
            'success' => false,
            'message' => 'Failed to send verification email: ' . $error_message
        ];
    }
}

/**
 * Fallback email sending using PHP's built-in mail function
 * 
 * @param string $to_email Recipient email address
 * @param string $verification_code 6-digit verification code
 * @param string $user_name User's name for personalization
 * @param string $context Context: 'registration' or 'password_reset' (default: 'registration')
 * @return array Result array with success status and message
 */
function send_verification_email_fallback($to_email, $verification_code, $user_name = '', $context = 'registration') {
    try {
        if ($context === 'password_reset') {
            $subject = 'Password Reset Code - Red Cross Blood Donation';
            $message = get_password_reset_verification_email_text($verification_code, $user_name);
        } else {
        $subject = EMAIL_VERIFICATION_SUBJECT;
        $message = get_verification_email_text($verification_code, $user_name);
        }
        $headers = [
            'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
            'Reply-To: ' . SMTP_FROM_EMAIL,
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $mail_sent = mail($to_email, $subject, $message, implode("\r\n", $headers));
        
        if ($mail_sent) {
            return [
                'success' => true,
                'message' => 'Verification email sent successfully (fallback method)'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send email using fallback method'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Fallback email sending failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Get HTML email template for verification
 * 
 * @param string $verification_code 6-digit verification code
 * @param string $user_name User's name
 * @return string HTML email content
 */
function get_verification_email_template($verification_code, $user_name = '') {
    $greeting = $user_name ? "Hello $user_name," : "Hello,";
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Email Verification</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #d32f2f;
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 8px 8px 0 0;
            }
            .content {
                background-color: #f9f9f9;
                padding: 30px;
                border-radius: 0 0 8px 8px;
            }
            .verification-code {
                background-color: #d32f2f;
                color: white;
                font-size: 32px;
                font-weight: bold;
                text-align: center;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
                letter-spacing: 5px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                font-size: 12px;
                color: #666;
            }
            .warning {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🔴 Red Cross Blood Donation System</h1>
            <h2>Email Verification Required</h2>
        </div>
        
        <div class='content'>
            <p>$greeting</p>
            
            <p>Thank you for registering with the Red Cross Blood Donation System. To complete your registration, please verify your email address using the verification code below:</p>
            
            <div class='verification-code'>
                $verification_code
            </div>
            
            <p>Please enter this code in the verification form to activate your account.</p>
            
            <div class='warning'>
                <strong>⚠️ Important:</strong> This verification code will expire in " . EMAIL_VERIFICATION_EXPIRY_MINUTES . " minutes. If you don't verify your email within this time, you'll need to request a new verification code.
            </div>
            
            <p>If you didn't register for this account, please ignore this email.</p>
            
            <p>Best regards,<br>
            Red Cross Blood Donation System Team</p>
        </div>
        
        <div class='footer'>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>© " . date('Y') . " Red Cross Blood Donation System</p>
        </div>
    </body>
    </html>";
}

/**
 * Get plain text email content for verification
 * 
 * @param string $verification_code 6-digit verification code
 * @param string $user_name User's name
 * @return string Plain text email content
 */
function get_verification_email_text($verification_code, $user_name = '') {
    $greeting = $user_name ? "Hello $user_name," : "Hello,";
    
    return "
$greeting

Thank you for registering with the Red Cross Blood Donation System. To complete your registration, please verify your email address using the verification code below:

VERIFICATION CODE: $verification_code

Please enter this code in the verification form to activate your account.

IMPORTANT: This verification code will expire in " . EMAIL_VERIFICATION_EXPIRY_MINUTES . " minutes. If you don't verify your email within this time, you'll need to request a new verification code.

If you didn't register for this account, please ignore this email.

Best regards,
Red Cross Blood Donation System Team

---
This is an automated message. Please do not reply to this email.
© " . date('Y') . " Red Cross Blood Donation System";
}

/**
 * Send password reset code email using PHPMailer
 * 
 * @param string $to_email Recipient email address
 * @param string $reset_code 6-digit verification code
 * @param string $user_name User's name for personalization
 * @return array Result array with success status and message
 */
function send_password_reset_code_email($to_email, $reset_code, $user_name = '') {
    try {
        // Include PHPMailer files explicitly
        $phpmailer_base = __DIR__ . '/../vendor/phpmailer/phpmailer/src/';
        
        // Load required PHPMailer files in correct order
        $required_files = [
            'Exception.php',
            'SMTP.php', 
            'PHPMailer.php'
        ];
        
        foreach ($required_files as $file) {
            $file_path = $phpmailer_base . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                throw new Exception("PHPMailer file not found: $file");
            }
        }
        
        // Check if classes exist after loading
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            throw new Exception('PHPMailer class not found after loading.');
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $user_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code - Red Cross Blood Donation';
        
        // Email template
        $mail->Body = get_password_reset_code_email_template($reset_code, $user_name);
        $mail->AltBody = get_password_reset_code_email_text($reset_code, $user_name);
        
        $mail->send();
        
        error_log("Password reset code email sent successfully to: $to_email");
        
        return [
            'success' => true,
            'message' => 'Password reset code email sent successfully'
        ];
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Password reset code email sending failed to $to_email: " . $error_message);
        error_log("SMTP Config - Host: " . SMTP_HOST . ", Port: " . SMTP_PORT . ", Username: " . SMTP_USERNAME);
        
        // Try fallback method
        try {
            $fallback_result = send_password_reset_code_email_fallback($to_email, $reset_code, $user_name);
            if ($fallback_result['success']) {
                return $fallback_result;
            }
        } catch (Exception $fallback_e) {
            error_log("Fallback email sending also failed: " . $fallback_e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to send password reset code email: ' . $error_message
        ];
    }
}

/**
 * Send password reset email using PHPMailer (legacy function for link-based reset)
 * 
 * @param string $to_email Recipient email address
 * @param string $reset_link Password reset link
 * @param string $reset_token Reset token (optional, for manual entry)
 * @param string $user_name User's name for personalization
 * @return array Result array with success status and message
 */
function send_password_reset_email($to_email, $reset_link, $reset_token = '', $user_name = '') {
    try {
        // Include PHPMailer files explicitly
        $phpmailer_base = __DIR__ . '/../vendor/phpmailer/phpmailer/src/';
        
        // Load required PHPMailer files in correct order
        $required_files = [
            'Exception.php',
            'SMTP.php', 
            'PHPMailer.php'
        ];
        
        foreach ($required_files as $file) {
            $file_path = $phpmailer_base . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                throw new Exception("PHPMailer file not found: $file");
            }
        }
        
        // Check if classes exist after loading
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            throw new Exception('PHPMailer class not found after loading.');
        }
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $user_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - Red Cross Blood Donation';
        
        // Email template
        $mail->Body = get_password_reset_email_template($reset_link, $reset_token, $user_name);
        $mail->AltBody = get_password_reset_email_text($reset_link, $reset_token, $user_name);
        
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Password reset email sent successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Password reset email sending failed: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Failed to send password reset email: ' . $e->getMessage()
        ];
    }
}

/**
 * Get HTML email template for password reset
 * 
 * @param string $reset_link Password reset link
 * @param string $reset_token Reset token (optional)
 * @param string $user_name User's name
 * @return string HTML email content
 */
function get_password_reset_email_template($reset_link, $reset_token = '', $user_name = '') {
    $greeting = $user_name ? "Hello $user_name," : "Hello,";
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Password Reset</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #d32f2f;
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 8px 8px 0 0;
            }
            .content {
                background-color: #f9f9f9;
                padding: 30px;
                border-radius: 0 0 8px 8px;
            }
            .reset-button {
                display: inline-block;
                background-color: #d32f2f;
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 8px;
                margin: 20px 0;
                font-weight: bold;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                font-size: 12px;
                color: #666;
            }
            .warning {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .token-box {
                background-color: #f0f0f0;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
                font-family: monospace;
                word-break: break-all;
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🔴 Red Cross Blood Donation System</h1>
            <h2>Password Reset Request</h2>
        </div>
        
        <div class='content'>
            <p>$greeting</p>
            
            <p>We received a request to reset your password for your Red Cross Blood Donation System account.</p>
            
            <p>Click the button below to reset your password:</p>
            
            <div style='text-align: center;'>
                <a href='$reset_link' class='reset-button'>Reset Password</a>
            </div>
            
            <p>Or copy and paste this link into your browser:</p>
            <div class='token-box'>$reset_link</div>
            
            <div class='warning'>
                <strong>⚠️ Important:</strong> This password reset link will expire in 1 hour. If you didn't request a password reset, please ignore this email and your password will remain unchanged.
            </div>
            
            <p>If you didn't request this password reset, please ignore this email or contact support if you have concerns.</p>
            
            <p>Best regards,<br>
            Red Cross Blood Donation System Team</p>
        </div>
        
        <div class='footer'>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>© " . date('Y') . " Red Cross Blood Donation System</p>
        </div>
    </body>
    </html>";
}

/**
 * Get plain text email content for password reset
 * 
 * @param string $reset_link Password reset link
 * @param string $reset_token Reset token (optional)
 * @param string $user_name User's name
 * @return string Plain text email content
 */
function get_password_reset_email_text($reset_link, $reset_token = '', $user_name = '') {
    $greeting = $user_name ? "Hello $user_name," : "Hello,";
    
    return "
$greeting

We received a request to reset your password for your Red Cross Blood Donation System account.

To reset your password, please click on the following link or copy and paste it into your browser:

$reset_link

IMPORTANT: This password reset link will expire in 1 hour. If you didn't request a password reset, please ignore this email and your password will remain unchanged.

If you didn't request this password reset, please ignore this email or contact support if you have concerns.

Best regards,
Red Cross Blood Donation System Team

---
This is an automated message. Please do not reply to this email.
© " . date('Y') . " Red Cross Blood Donation System";
}

/**
 * Get HTML email template for password reset code
 * 
 * @param string $reset_code 6-digit verification code
 * @param string $user_name User's name
 * @return string HTML email content
 */
function get_password_reset_code_email_template($reset_code, $user_name = '') {
    $greeting = $user_name ? "Hello $user_name," : "Hello,";
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Password Reset Code</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #d32f2f;
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 8px 8px 0 0;
            }
            .content {
                background-color: #f9f9f9;
                padding: 30px;
                border-radius: 0 0 8px 8px;
            }
            .verification-code {
                background-color: #d32f2f;
                color: white;
                font-size: 32px;
                font-weight: bold;
                text-align: center;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
                letter-spacing: 5px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                font-size: 12px;
                color: #666;
            }
            .warning {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🔴 Red Cross Blood Donation System</h1>
            <h2>Password Reset Verification Code</h2>
        </div>
        
        <div class='content'>
            <p>$greeting</p>
            
            <p>We received a request to reset your password for your Red Cross Blood Donation System account.</p>
            
            <p>Please use the following verification code to proceed with resetting your password:</p>
            
            <div class='verification-code'>
                $reset_code
            </div>
            
            <p>Enter this code in the password reset form to continue.</p>
            
            <div class='warning'>
                <strong>⚠️ Important:</strong> This verification code will expire in 15 minutes. If you didn't request a password reset, please ignore this email and your password will remain unchanged.
            </div>
            
            <p>If you didn't request this password reset, please ignore this email or contact support if you have concerns.</p>
            
            <p>Best regards,<br>
            Red Cross Blood Donation System Team</p>
        </div>
        
        <div class='footer'>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>© " . date('Y') . " Red Cross Blood Donation System</p>
        </div>
    </body>
    </html>";
}

/**
 * Get plain text email content for password reset code
 * 
 * @param string $reset_code 6-digit verification code
 * @param string $user_name User's name
 * @return string Plain text email content
 */
function get_password_reset_code_email_text($reset_code, $user_name = '') {
    $greeting = $user_name ? "Hello $user_name," : "Hello,";
    
    return "
$greeting

We received a request to reset your password for your Red Cross Blood Donation System account.

Please use the following verification code to proceed with resetting your password:

VERIFICATION CODE: $reset_code

Enter this code in the password reset form to continue.

IMPORTANT: This verification code will expire in 15 minutes. If you didn't request a password reset, please ignore this email and your password will remain unchanged.

If you didn't request this password reset, please ignore this email or contact support if you have concerns.

Best regards,
Red Cross Blood Donation System Team

---
This is an automated message. Please do not reply to this email.
© " . date('Y') . " Red Cross Blood Donation System";
}

/**
 * Fallback email sending using PHP's built-in mail function for password reset code
 * 
 * @param string $to_email Recipient email address
 * @param string $reset_code 6-digit verification code
 * @param string $user_name User's name for personalization
 * @return array Result array with success status and message
 */
function send_password_reset_code_email_fallback($to_email, $reset_code, $user_name = '') {
    try {
        $subject = 'Password Reset Code - Red Cross Blood Donation';
        $message = get_password_reset_code_email_text($reset_code, $user_name);
        $headers = [
            'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
            'Reply-To: ' . SMTP_FROM_EMAIL,
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $mail_sent = mail($to_email, $subject, $message, implode("\r\n", $headers));
        
        if ($mail_sent) {
            error_log("Password reset code email sent successfully (fallback method) to: $to_email");
            return [
                'success' => true,
                'message' => 'Password reset code email sent successfully (fallback method)'
            ];
        } else {
            error_log("Failed to send password reset code email using fallback method to: $to_email");
            return [
                'success' => false,
                'message' => 'Failed to send email using fallback method'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Fallback password reset code email sending failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Fallback email sending failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Get HTML email template for password reset verification code
 * 
 * @param string $verification_code 6-digit verification code
 * @param string $user_name User's name
 * @return string HTML email content
 */
function get_password_reset_verification_email_template($verification_code, $user_name = '') {
    $greeting = $user_name ? "Hello $user_name," : "Hello,";
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Password Reset Code</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #d32f2f;
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 8px 8px 0 0;
            }
            .content {
                background-color: #f9f9f9;
                padding: 30px;
                border-radius: 0 0 8px 8px;
            }
            .verification-code {
                background-color: #d32f2f;
                color: white;
                font-size: 32px;
                font-weight: bold;
                text-align: center;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
                letter-spacing: 5px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                font-size: 12px;
                color: #666;
            }
            .warning {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🔴 Red Cross Blood Donation System</h1>
            <h2>Password Reset Verification Code</h2>
        </div>
        
        <div class='content'>
            <p>$greeting</p>
            
            <p>We received a request to reset your password for your Red Cross Blood Donation System account.</p>
            
            <p>Please use the following verification code to proceed with resetting your password:</p>
            
            <div class='verification-code'>
                $verification_code
            </div>
            
            <p>Enter this code in the password reset form to continue.</p>
            
            <div class='warning'>
                <strong>⚠️ Important:</strong> This verification code will expire in " . EMAIL_VERIFICATION_EXPIRY_MINUTES . " minutes. If you didn't request a password reset, please ignore this email and your password will remain unchanged.
            </div>
            
            <p>If you didn't request this password reset, please ignore this email or contact support if you have concerns.</p>
            
            <p>Best regards,<br>
            Red Cross Blood Donation System Team</p>
        </div>
        
        <div class='footer'>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>© " . date('Y') . " Red Cross Blood Donation System</p>
        </div>
    </body>
    </html>";
}

/**
 * Get plain text email content for password reset verification code
 * 
 * @param string $verification_code 6-digit verification code
 * @param string $user_name User's name
 * @return string Plain text email content
 */
function get_password_reset_verification_email_text($verification_code, $user_name = '') {
    $greeting = $user_name ? "Hello $user_name," : "Hello,";
    
    return "
$greeting

We received a request to reset your password for your Red Cross Blood Donation System account.

Please use the following verification code to proceed with resetting your password:

VERIFICATION CODE: $verification_code

Enter this code in the password reset form to continue.

IMPORTANT: This verification code will expire in " . EMAIL_VERIFICATION_EXPIRY_MINUTES . " minutes. If you didn't request a password reset, please ignore this email and your password will remain unchanged.

If you didn't request this password reset, please ignore this email or contact support if you have concerns.

Best regards,
Red Cross Blood Donation System Team

---
This is an automated message. Please do not reply to this email.
© " . date('Y') . " Red Cross Blood Donation System";
}
?>
