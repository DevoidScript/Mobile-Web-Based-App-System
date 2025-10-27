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
 * @return array Result array with success status and message
 */
function send_verification_email($to_email, $verification_code, $user_name = '') {
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
        $mail->Subject = EMAIL_VERIFICATION_SUBJECT;
        
        // Email template
        $mail->Body = get_verification_email_template($verification_code, $user_name);
        $mail->AltBody = get_verification_email_text($verification_code, $user_name);
        
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Verification email sent successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        
        // Return the actual PHPMailer error instead of falling back
        return [
            'success' => false,
            'message' => 'Failed to send verification email: ' . $e->getMessage()
        ];
    }
}

/**
 * Fallback email sending using PHP's built-in mail function
 * 
 * @param string $to_email Recipient email address
 * @param string $verification_code 6-digit verification code
 * @param string $user_name User's name for personalization
 * @return array Result array with success status and message
 */
function send_verification_email_fallback($to_email, $verification_code, $user_name = '') {
    try {
        $subject = EMAIL_VERIFICATION_SUBJECT;
        $message = get_verification_email_text($verification_code, $user_name);
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
?>
