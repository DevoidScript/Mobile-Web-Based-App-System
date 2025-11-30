<?php
/**
 * Test script for password reset email functionality
 * 
 * This script helps debug email sending issues for password reset codes.
 * Usage: Access this file via browser or run via command line
 */

// Include required files
require_once 'config/database.php';
require_once 'config/email.php';
require_once 'includes/functions.php';

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get test email from GET parameter or use default
$test_email = $_GET['email'] ?? 'test@example.com';

echo "<h1>Password Reset Email Test</h1>";
echo "<p>Testing email sending to: <strong>$test_email</strong></p>";
echo "<hr>";

// Test 1: Check email configuration
echo "<h2>1. Email Configuration Check</h2>";
echo "<pre>";
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP Port: " . SMTP_PORT . "\n";
echo "SMTP Username: " . SMTP_USERNAME . "\n";
echo "SMTP Password: " . (strlen(SMTP_PASSWORD) > 0 ? "***" . substr(SMTP_PASSWORD, -4) : "NOT SET") . "\n";
echo "From Email: " . SMTP_FROM_EMAIL . "\n";
echo "From Name: " . SMTP_FROM_NAME . "\n";
echo "</pre>";

// Test 2: Generate test code
echo "<h2>2. Generate Test Code</h2>";
$test_code = generate_verification_code();
echo "<p>Generated Code: <strong>$test_code</strong></p>";

// Test 3: Check PHPMailer files
echo "<h2>3. PHPMailer Files Check</h2>";
$phpmailer_base = __DIR__ . '/vendor/phpmailer/phpmailer/src/';
$required_files = ['Exception.php', 'SMTP.php', 'PHPMailer.php'];
echo "<pre>";
foreach ($required_files as $file) {
    $file_path = $phpmailer_base . $file;
    $exists = file_exists($file_path);
    echo "$file: " . ($exists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
}
echo "</pre>";

// Test 4: Try sending email
echo "<h2>4. Send Test Email</h2>";
try {
    $result = send_password_reset_code_email($test_email, $test_code, 'Test User');
    
    if ($result['success']) {
        echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> " . $result['message'] . "</p>";
        echo "<p>Check your inbox (and spam folder) at: <strong>$test_email</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>✗ FAILED:</strong> " . $result['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ EXCEPTION:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 5: Check error logs
echo "<h2>5. Recent Error Logs</h2>";
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path)) {
    $log_lines = file($error_log_path);
    $recent_logs = array_slice($log_lines, -10);
    echo "<pre>";
    foreach ($recent_logs as $line) {
        if (stripos($line, 'password') !== false || stripos($line, 'email') !== false || stripos($line, 'smtp') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>Error log not found or not configured.</p>";
}

// Test 6: Test database connection
echo "<h2>6. Database Connection Test</h2>";
try {
    $test_params = ['limit' => 1];
    $test_result = get_records('email_verifications', $test_params);
    if ($test_result['success']) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Database connection issue: " . json_encode($test_result) . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='?email=$test_email'>Refresh Test</a> | <a href='templates/forgot-password.php'>Go to Forgot Password</a></p>";
echo "<p><small>To test with a different email, add ?email=your@email.com to the URL</small></p>";
?>



