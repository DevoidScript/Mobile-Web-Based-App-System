<?php
/**
 * Debug script for password reset email issues
 * 
 * This script helps identify why password reset emails aren't being sent.
 * Access via: http://your-domain/mobile-app/debug-password-reset.php?email=your@email.com
 */

// Include required files
require_once 'config/database.php';
require_once 'config/email.php';
require_once 'includes/functions.php';

// Get test email from GET parameter
$test_email = $_GET['email'] ?? '';

if (empty($test_email)) {
    die("Please provide an email address: ?email=your@email.com");
}

echo "<h1>Password Reset Debug Tool</h1>";
echo "<p>Testing password reset for: <strong>$test_email</strong></p>";
echo "<hr>";

// Step 1: Check if user exists
echo "<h2>Step 1: User Lookup</h2>";
$user_id = null;
$user_name = '';

// Check donor_form
$donor_params = ['email' => 'eq.' . $test_email];
$donor_result = get_records('donor_form', $donor_params);

echo "<h3>Donor Form Lookup:</h3>";
echo "<pre>";
echo "Success: " . ($donor_result['success'] ? 'YES' : 'NO') . "\n";
if ($donor_result['success'] && !empty($donor_result['data'])) {
    echo "Found: YES\n";
    echo "Data: " . json_encode($donor_result['data'][0], JSON_PRETTY_PRINT) . "\n";
    $user_id = $donor_result['data'][0]['user_id'] ?? null;
    $user_name = ($donor_result['data'][0]['first_name'] ?? '') . ' ' . ($donor_result['data'][0]['surname'] ?? '');
} else {
    echo "Found: NO\n";
    echo "Error: " . json_encode($donor_result) . "\n";
}
echo "</pre>";

// Check donors_detail
if (!$user_id) {
    echo "<h3>Donors Detail Lookup:</h3>";
    $donor_detail_params = ['email' => 'eq.' . $test_email];
    $donor_detail_result = get_records('public.donors_detail', $donor_detail_params);
    
    echo "<pre>";
    echo "Success: " . ($donor_detail_result['success'] ? 'YES' : 'NO') . "\n";
    if ($donor_detail_result['success'] && !empty($donor_detail_result['data'])) {
        echo "Found: YES\n";
        echo "Data: " . json_encode($donor_detail_result['data'][0], JSON_PRETTY_PRINT) . "\n";
        $user_id = $donor_detail_result['data'][0]['user_id'] ?? null;
        $user_name = ($donor_detail_result['data'][0]['first_name'] ?? '') . ' ' . ($donor_detail_result['data'][0]['surname'] ?? '');
    } else {
        echo "Found: NO\n";
    }
    echo "</pre>";
}

echo "<p><strong>Result:</strong> User ID = " . ($user_id ?? 'NOT FOUND') . "</p>";

// Step 2: Generate code
echo "<hr><h2>Step 2: Generate Code</h2>";
$code = generate_verification_code();
echo "<p>Generated Code: <strong>$code</strong></p>";

// Step 3: Test database insert
echo "<hr><h2>Step 3: Database Insert Test</h2>";
$expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
$test_user_id = $user_id ?? 'temp_' . bin2hex(random_bytes(16));

$reset_data = [
    'email' => $test_email,
    'verification_code' => $code,
    'user_id' => $test_user_id,
    'expires_at' => $expires_at,
    'verified' => false
];

echo "<pre>";
echo "Data to insert: " . json_encode($reset_data, JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

$result = create_record('email_verifications', $reset_data);

echo "<pre>";
echo "Insert Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

// Step 4: Test email sending
echo "<hr><h2>Step 4: Email Sending Test</h2>";
echo "<p>Attempting to send email...</p>";

$email_result = send_verification_email($test_email, $code, trim($user_name), 'password_reset');

echo "<pre>";
echo "Email Send Success: " . ($email_result['success'] ? 'YES' : 'NO') . "\n";
echo "Message: " . ($email_result['message'] ?? 'N/A') . "\n";
echo "Full Result: " . json_encode($email_result, JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

if (!$email_result['success']) {
    echo "<h3>Fallback Email Test:</h3>";
    $fallback_result = send_verification_email_fallback($test_email, $code, trim($user_name), 'password_reset');
    echo "<pre>";
    echo "Fallback Success: " . ($fallback_result['success'] ? 'YES' : 'NO') . "\n";
    echo "Message: " . ($fallback_result['message'] ?? 'N/A') . "\n";
    echo "</pre>";
}

// Step 5: Check error logs
echo "<hr><h2>Step 5: Recent Error Logs</h2>";
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path)) {
    $log_lines = file($error_log_path);
    $recent_logs = array_slice($log_lines, -20);
    echo "<pre>";
    foreach ($recent_logs as $line) {
        if (stripos($line, 'password') !== false || 
            stripos($line, 'email') !== false || 
            stripos($line, 'smtp') !== false ||
            stripos($line, $test_email) !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p>Error log not found at: " . ($error_log_path ?: 'not configured') . "</p>";
}

echo "<hr>";
echo "<p><a href='?email=$test_email'>Refresh Test</a> | <a href='templates/forgot-password.php'>Go to Forgot Password</a></p>";
?>



