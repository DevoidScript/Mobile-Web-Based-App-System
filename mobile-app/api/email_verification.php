<?php
/**
 * Email Verification API Handler
 * 
 * This file provides API endpoints for email verification functionality:
 * - Verify email code
 * - Resend verification email
 * - Check verification status
 */

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON for API responses
header('Content-Type: application/json');

// Function to send JSON response
function send_response($success, $message, $data = null, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(false, 'Invalid request method. Only POST is allowed.', null, 405);
}

// Get POST data
$post_data = $_POST;
$action = $post_data['action'] ?? '';

switch ($action) {
    case 'verify':
        handle_verify_email($post_data);
        break;
        
    case 'resend':
        handle_resend_email($post_data);
        break;
        
    case 'check_status':
        handle_check_status($post_data);
        break;
        
    default:
        send_response(false, 'Invalid action specified.', null, 400);
}

/**
 * Handle email verification
 */
function handle_verify_email($post_data) {
    $email = sanitize_input($post_data['email'] ?? '');
    $verification_code = sanitize_input($post_data['verification_code'] ?? '');
    
    // Validate input
    if (empty($email)) {
        send_response(false, 'Email is required', null, 400);
    }
    
    if (empty($verification_code)) {
        send_response(false, 'Verification code is required', null, 400);
    }
    
    if (!preg_match('/^\d{6}$/', $verification_code)) {
        send_response(false, 'Verification code must be 6 digits', null, 400);
    }
    
    // Verify the code
    $verify_result = verify_email_code($email, $verification_code);
    
    if ($verify_result['success']) {
        // Clear pending verification session if it exists
        if (isset($_SESSION['pending_verification'])) {
            unset($_SESSION['pending_verification']);
        }
        
        send_response(true, 'Email verified successfully', [
            'redirect' => '/Mobile-Web-Based-App-System/mobile-app/templates/login.php'
        ]);
    } else {
        send_response(false, $verify_result['message'], null, 400);
    }
}

/**
 * Handle resend verification email
 */
function handle_resend_email($post_data) {
    $email = sanitize_input($post_data['email'] ?? '');
    $user_id = $post_data['user_id'] ?? '';
    $user_name = sanitize_input($post_data['user_name'] ?? '');
    
    // Validate input
    if (empty($email)) {
        send_response(false, 'Email is required', null, 400);
    }
    
    if (empty($user_id)) {
        send_response(false, 'User ID is required', null, 400);
    }
    
    // Resend verification email
    $resend_result = resend_verification_email($email, $user_id, $user_name);
    
    if ($resend_result['success']) {
        send_response(true, 'Verification email sent successfully');
    } else {
        send_response(false, $resend_result['message'], null, 500);
    }
}

/**
 * Handle check verification status
 */
function handle_check_status($post_data) {
    $user_id = $post_data['user_id'] ?? '';
    
    // Validate input
    if (empty($user_id)) {
        send_response(false, 'User ID is required', null, 400);
    }
    
    // Check if email is verified
    $is_verified = is_email_verified($user_id);
    
    send_response(true, 'Status checked successfully', [
        'verified' => $is_verified
    ]);
}
?>

