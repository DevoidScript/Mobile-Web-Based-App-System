<?php
/**
 * Password Reset API Handler
 * 
 * This file provides API endpoints for password reset functionality:
 * - Request password reset (sends email with reset link)
 * - Reset password with token
 */

// Include required files
require_once '../config/database.php';
require_once '../config/email.php';
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
    case 'request_reset':
        handle_request_reset($post_data);
        break;
        
    case 'verify_code':
        handle_verify_code($post_data);
        break;
        
    case 'reset_password':
        handle_reset_password($post_data);
        break;
        
    default:
        send_response(false, 'Invalid action specified.', null, 400);
}

/**
 * Handle password reset request
 */
function handle_request_reset($post_data) {
    $email = sanitize_input($post_data['email'] ?? '');
    
    // Validate input
    if (empty($email)) {
        send_response(false, 'Email is required', null, 400);
    }
    
    if (!is_valid_email($email)) {
        send_response(false, 'Invalid email format', null, 400);
    }
    
    // Create password reset code
    $result = create_password_reset_code($email);
    if ($result['success']) {
        send_response(true, 'Verification code has been sent to your email.');
    } else {
        // Log the actual error for debugging and return clear failure
        error_log("Password reset failed for email: $email, error: " . ($result['message'] ?? 'Unknown error'));
        send_response(false, $result['message'] ?? 'Failed to send verification code. Please try again later.', null, 500);
    }
}

/**
 * Handle code verification
 */
function handle_verify_code($post_data) {
    $email = sanitize_input($post_data['email'] ?? '');
    $code = sanitize_input($post_data['code'] ?? '');
    
    // Validate input
    if (empty($email)) {
        send_response(false, 'Email is required', null, 400);
    }
    
    if (empty($code)) {
        send_response(false, 'Verification code is required', null, 400);
    }
    
    if (!preg_match('/^\d{6}$/', $code)) {
        send_response(false, 'Verification code must be 6 digits', null, 400);
    }
    
    // Verify the code
    $verify_result = verify_password_reset_code($email, $code);
    
    if ($verify_result['success']) {
        // Return token for password reset
        send_response(true, 'Code verified successfully', [
            'token' => $verify_result['token']
        ]);
    } else {
        // Prefer detailed backend message when available
        $error_message = isset($verify_result['message']) && $verify_result['message'] !== ''
            ? $verify_result['message']
            : 'Invalid or expired verification code';
        send_response(false, $error_message, null, 400);
    }
}

/**
 * Handle password reset with token
 */
function handle_reset_password($post_data) {
    $token = sanitize_input($post_data['token'] ?? '');
    $password = $post_data['password'] ?? '';
    $confirm_password = $post_data['confirm_password'] ?? '';
    
    // Validate input
    if (empty($token)) {
        send_response(false, 'Reset token is required', null, 400);
    }
    
    if (empty($password)) {
        send_response(false, 'Password is required', null, 400);
    }
    
    if (strlen($password) < 8) {
        send_response(false, 'Password must be at least 8 characters long', null, 400);
    }
    
    if ($password !== $confirm_password) {
        send_response(false, 'Passwords do not match', null, 400);
    }
    
    // Verify token and get user info
    // Token can be from code verification or direct token
    $token_result = verify_password_reset_token($token);
    
    if (!$token_result['success']) {
        // Try alternative: check if token is stored in email_verifications
        $params = [
            'verification_code' => 'eq.' . $token,
            'verified' => 'eq.false',
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $token_check = get_records('email_verifications', $params);
        
        if ($token_check['success'] && !empty($token_check['data'])) {
            $token_record = $token_check['data'][0];
            $now = date('Y-m-d H:i:s');
            
            if (strtotime($now) <= strtotime($token_record['expires_at'])) {
                $token_result = [
                    'success' => true,
                    'user_id' => $token_record['user_id'],
                    'email' => $token_record['email']
                ];
            } else {
                send_response(false, 'Reset token has expired. Please request a new password reset.', null, 400);
            }
        } else {
            $error_message = isset($token_result['message']) && $token_result['message'] !== ''
                ? $token_result['message']
                : 'Invalid or expired reset token';
            send_response(false, $error_message, null, 400);
        }
    }
    
    $user_id = $token_result['user_id'];
    $email = $token_result['email'];
    
    // Update password using Supabase Auth (admin endpoint)
    // Similar approach to change-password.php but using admin endpoint since we don't have current password
    $supabase_url = SUPABASE_URL;
    $supabase_key = SUPABASE_API_KEY;
    $supabase_service_key = SUPABASE_SERVICE_KEY;
    
    // Step 1: Update password using admin endpoint (similar to change-password.php Step 2)
    $update_url = rtrim($supabase_url, '/') . '/auth/v1/admin/users/' . $user_id;
    $update_data = json_encode([
        'password' => $password
    ]);
    
    $ch = curl_init($update_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $update_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabase_service_key,
        'Authorization: Bearer ' . $supabase_service_key
    ]);
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status_code === 200) {
        // Step 2: Verify password by signing in (same as change-password.php verification)
        // This ensures the password was actually updated correctly
        $sign_in_url = rtrim($supabase_url, '/') . '/auth/v1/token?grant_type=password';
        $sign_in_data = json_encode([
            'email' => $email,
            'password' => $password
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

        if ($sign_in_status === 200 && !empty($sign_in_result['access_token'])) {
            // Password verified successfully - same approach as change-password.php
            mark_password_reset_token_used($token);
            send_response(true, 'Password has been reset successfully. You can now login with your new password.');
        } else {
            // Admin update returned 200 but login with new password failed – treat as failure
            error_log("Password reset: admin update returned 200 but login with new password failed for email: $email, status: $sign_in_status, response: $sign_in_response");
            send_response(false, 'Failed to verify new password. Please try again.', null, 500);
        }
    } else {
        // Try alternative method: use password reset endpoint
        $result = reset_password_with_token($token, $password);
        if ($result['success']) {
            send_response(true, 'Password has been reset successfully. You can now login with your new password.');
        } else {
            send_response(false, 'Failed to reset password. Please try again.', null, 500);
        }
    }
}

/**
 * Create password reset code
 */
function create_password_reset_code($email) {
    try {
        // Resolve the Supabase Auth user_id for this email.
        // We MUST have a real UUID here because email_verifications.user_id is UUID NOT NULL.
        $user_id = get_user_id_for_password_reset($email);

        if (!$user_id) {
            // Hard‑fail if we can't map this email to a real auth user.
            // This avoids inserting invalid data and later "invalid code" confusion.
            return [
                'success' => false,
                'message' => 'User account not found for this email. Please make sure you entered the email you registered with.'
            ];
        }

        $user_name = '';

        // Try to get a friendly name from donor tables for the email content
        $donor_params = ['email' => 'eq.' . $email];
        $donor_result = get_records('donor_form', $donor_params);
        if ($donor_result['success'] && !empty($donor_result['data'])) {
            $user_name = trim(
                ($donor_result['data'][0]['first_name'] ?? '') . ' ' .
                ($donor_result['data'][0]['surname'] ?? '')
            );
        } else {
            $donor_detail_params = ['email' => 'eq.' . $email];
            $donor_detail_result = get_records('public.donors_detail', $donor_detail_params);
            if ($donor_detail_result['success'] && !empty($donor_detail_result['data'])) {
                $user_name = trim(
                    ($donor_detail_result['data'][0]['first_name'] ?? '') . ' ' .
                    ($donor_detail_result['data'][0]['surname'] ?? '')
                );
            }
        }

        // Generate 6-digit verification code
        $code = generate_verification_code();
        
        // Calculate expiry time (15 minutes from now)
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Log for debugging
        error_log("Password reset request for email: $email, resolved user_id: " . $user_id);

        // Check if there's an existing email_verifications record for this email
        $existing_params = [
            'email' => 'eq.' . $email,
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        $existing_result = get_records('email_verifications', $existing_params);
        
        $reset_data = [
            'email' => $email,
            // Keep verification_code populated (NOT NULL column) and also
            // use the dedicated reset_code column for clarity.
            'verification_code' => $code,
            'reset_code' => $code,
            'user_id' => $user_id,
            'expires_at' => $expires_at,
            'verified' => false,
            'reset_requested_at' => date('Y-m-d H:i:s') // when reset was requested
        ];
        
        // If an existing record exists, update it instead of creating a new one
        if ($existing_result['success'] && !empty($existing_result['data'])) {
            $existing_record = $existing_result['data'][0];
            $existing_id = $existing_record['id'];
            
            error_log("Found existing email_verifications record (ID: $existing_id) for email: $email, updating with new reset code");
            
            // Update the existing record with new reset code
            $update_result = update_record('email_verifications', $existing_id, $reset_data);
            
            if (!$update_result['success']) {
                error_log("Failed to update password reset code record: " . json_encode($update_result));
                return [
                    'success' => false,
                    'message' => 'Failed to update password reset record. ' . json_encode($update_result['data'] ?? $update_result)
                ];
            }
            
            error_log("Password reset code record updated successfully in database (ID: $existing_id)");
        } else {
            // No existing record, create a new one
            error_log("No existing email_verifications record found for email: $email, creating new record");
            error_log("Attempting to create password reset record with data: " . json_encode($reset_data));
            
            $result = create_record('email_verifications', $reset_data);
            
            if (!$result['success']) {
                error_log("Failed to create password reset code record: " . json_encode($result));
                return [
                    'success' => false,
                    'message' => 'Failed to create password reset record. ' . json_encode($result['data'] ?? $result)
                ];
            }
            
            error_log("Password reset code record created successfully in database");
        }
        
        // Log code creation for debugging
        error_log("Password reset code generated for email: $email, code: $code");
        error_log("Attempting to send email to: $email with code: $code");
        
        // Send reset code email using the same function as registration
        // This ensures we use the same working email configuration
        // Pass 'password_reset' context to customize the email message
        $email_result = send_verification_email($email, $code, trim($user_name), 'password_reset');
        
        error_log("Email send result: " . json_encode($email_result));
        
        if ($email_result['success']) {
            error_log("Password reset code email sent successfully to: $email");
            return [
                'success' => true,
                'message' => 'Verification code has been sent to your email.'
            ];
        } else {
            error_log("Failed to send password reset code email to: $email, error: " . $email_result['message']);
            // Try fallback method
            error_log("Attempting fallback email method");
            $fallback_result = send_verification_email_fallback($email, $code, trim($user_name), 'password_reset');
            error_log("Fallback email result: " . json_encode($fallback_result));
            
            if ($fallback_result['success']) {
                error_log("Password reset code email sent via fallback method to: $email");
                return [
                    'success' => true,
                    'message' => 'Verification code has been sent to your email.'
                ];
            }
            
            // For security, still return success message even if email fails
            // But log the actual error for debugging
            error_log("CRITICAL: Both email methods failed for password reset. Email: $email, Error: " . $email_result['message']);
            return [
                'success' => false,
                'message' => 'Failed to send email. Please check your email configuration or try again later. Error: ' . $email_result['message']
            ];
        }
        
    } catch (Exception $e) {
        error_log("Create password reset code error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error creating reset code: ' . $e->getMessage()
        ];
    }
}

/**
 * Resolve the Supabase Auth user_id for a given email so we can safely
 * insert into email_verifications (user_id is UUID NOT NULL).
 *
 * This function deliberately fails (returns null) when no real auth user
 * exists for the email, instead of inventing placeholder IDs.
 */
function get_user_id_for_password_reset($email) {
    $email = trim(strtolower($email));

    // 1) Try donor_form – it should have a user_id that points to Supabase Auth
    $donor_params = ['email' => 'eq.' . $email];
    $donor_result = get_records('donor_form', $donor_params);
    if ($donor_result['success'] && !empty($donor_result['data'])) {
        $user_id = $donor_result['data'][0]['user_id'] ?? null;
        if ($user_id) {
            return $user_id;
        }
    }

    // 2) Try donors_detail as a secondary source
    $donor_detail_params = ['email' => 'eq.' . $email];
    $donor_detail_result = get_records('public.donors_detail', $donor_detail_params);
    if ($donor_detail_result['success'] && !empty($donor_detail_result['data'])) {
        $user_id = $donor_detail_result['data'][0]['user_id'] ?? null;
        if ($user_id) {
            return $user_id;
        }
    }

    // 3) Fallback: query Supabase Auth admin API by email using the service role key
    try {
        $supabase_url = SUPABASE_URL;
        $endpoint = rtrim($supabase_url, '/') . '/auth/v1/admin/users?email=eq.' . urlencode($email);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . SUPABASE_SERVICE_KEY,
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY
        ]);

        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            error_log("Supabase admin user lookup error for $email: $curl_error");
            return null;
        }

        $data = json_decode($response, true);

        // Supabase Admin list users can return an array of users or an object with 'users'
        if ($status_code >= 200 && $status_code < 300 && $data) {
            if (isset($data['users']) && is_array($data['users']) && !empty($data['users'])) {
                return $data['users'][0]['id'] ?? null;
            }

            if (isset($data[0]) && is_array($data[0])) {
                return $data[0]['id'] ?? null;
            }
        } else {
            error_log("Supabase admin user lookup failed for $email. Status: $status_code, Response: $response");
        }
    } catch (Exception $e) {
        error_log("Exception during Supabase admin user lookup for $email: " . $e->getMessage());
    }

    // No valid UUID found for this email
    return null;
}

/**
 * Verify password reset code
 */
function verify_password_reset_code($email, $code) {
    try {
        error_log("Verifying password reset code for email: $email, code: $code");
        
        // First try to match against reset_code (preferred for password resets)
        $params_reset = [
            'email' => 'eq.' . $email,
            'reset_code' => 'eq.' . $code,
            'verified' => 'eq.false',
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        error_log("Looking up reset verification record with params: " . json_encode($params_reset));
        $result = get_records('email_verifications', $params_reset);

        // If nothing matched reset_code, fall back to verification_code
        if (!$result['success'] || empty($result['data'])) {
            $params_verif = [
                'email' => 'eq.' . $email,
                'verification_code' => 'eq.' . $code,
                'verified' => 'eq.false',
                'order' => 'created_at.desc',
                'limit' => 1
            ];
            error_log("No reset_code match, falling back to verification_code with params: " . json_encode($params_verif));
            $result = get_records('email_verifications', $params_verif);
        }
        
        error_log("Verification lookup result: " . json_encode($result));
        error_log("Verification lookup result: " . json_encode($result));
        
        if (!$result['success'] || empty($result['data'])) {
            error_log("No verification record found for email: $email, code: $code");
            return [
                'success' => false,
                'message' => 'Invalid verification code'
            ];
        }
        
        $verification_record = $result['data'][0];
        error_log("Found verification record: " . json_encode($verification_record));
        
        // Check if code has expired
        $now = date('Y-m-d H:i:s');
        $expires_at = $verification_record['expires_at'] ?? null;
        
        if ($expires_at && strtotime($now) > strtotime($expires_at)) {
            error_log("Verification code expired. Now: $now, Expires: $expires_at");
            return [
                'success' => false,
                'message' => 'Verification code has expired. Please request a new one.'
            ];
        }
        
        // We always store a real auth user_id in email_verifications now
        $actual_user_id = $verification_record['user_id'] ?? null;
        if (!$actual_user_id) {
            error_log("WARNING: email_verifications record missing user_id for email: $email");
            return [
                'success' => false,
                'message' => 'User account not found. Please contact support.'
            ];
        }
        
        // Mark as verified
        // Mark code as verified for auditing
        $update_data = [
            'verified' => true,
            'verified_at' => $now
        ];
        
        $update_result = update_record('email_verifications', $verification_record['id'], $update_data);
        
        if (!$update_result['success']) {
            error_log("Failed to mark verification as verified");
            return [
                'success' => false,
                'message' => 'Failed to verify code'
            ];
        }
        
        // Use the 6‑digit code itself as the reset token to avoid
        // storing oversized random tokens in VARCHAR(10) columns.
        $reset_token = $code;
        
        return [
            'success' => true,
            'token' => $reset_token,
            'user_id' => $actual_user_id,
            'email' => $email
        ];
        
    } catch (Exception $e) {
        error_log("Verify password reset code error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return [
            'success' => false,
            'message' => 'Error verifying code: ' . $e->getMessage()
        ];
    }
}

/**
 * Store password reset token after code verification
 */
function store_password_reset_token($user_id, $email, $token) {
    try {
        // Store token in a way we can retrieve it later
        // We'll use email_verifications table with a special format
        $token_data = [
            'email' => $email,
            'verification_code' => $token, // Store token as code
            'user_id' => $user_id,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'verified' => false
        ];
        
        $result = create_record('email_verifications', $token_data);
        return $result['success'];
        
    } catch (Exception $e) {
        error_log("Store password reset token error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify password reset token
 */
function verify_password_reset_token($token) {
    try {
        // We now use the 6‑digit code itself as the reset token and store it
        // directly in email_verifications (verification_code/reset_code).
        // Look up the most recent matching record.
        $params = [
            'verification_code' => 'eq.' . $token,
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        $result = get_records('email_verifications', $params);
        
        if (!$result['success'] || empty($result['data'])) {
            return [
                'success' => false,
                'message' => 'Invalid reset token'
            ];
        }
        
        $token_record = $result['data'][0];
        
        // Check if token has expired
        $now = date('Y-m-d H:i:s');
        $expires_at = $token_record['expires_at'] ?? null;
        
        if ($expires_at && strtotime($now) > strtotime($expires_at)) {
            return [
                'success' => false,
                'message' => 'Reset token has expired. Please request a new one.'
            ];
        }
        
        return [
            'success' => true,
            'user_id' => $token_record['user_id'] ?? null,
            'email' => $token_record['email'] ?? null,
            'token_id' => $token_record['id'] ?? null,
            'table' => 'email_verifications'
        ];
        
    } catch (Exception $e) {
        error_log("Verify password reset token error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error verifying token: ' . $e->getMessage()
        ];
    }
}

/**
 * Mark password reset token as used
 */
function mark_password_reset_token_used($token) {
    try {
        // Find token in email_verifications table
        $params = [
            'verification_code' => 'eq.' . $token,
            'verified' => 'eq.false',
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $result = get_records('email_verifications', $params);
        
        if ($result['success'] && !empty($result['data'])) {
            $token_record = $result['data'][0];
            $update_data = [
                'verified' => true,
                'verified_at' => date('Y-m-d H:i:s')
            ];
            
            $update_result = update_record('email_verifications', $token_record['id'], $update_data);
            return $update_result['success'];
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Mark token used error: " . $e->getMessage());
        return false;
    }
}

/**
 * Reset password with token (alternative method)
 */
function reset_password_with_token($token, $password) {
    $token_result = verify_password_reset_token($token);
    
    if (!$token_result['success']) {
        return [
            'success' => false,
            'message' => $token_result['message']
        ];
    }
    
    $user_id = $token_result['user_id'];
    $email = $token_result['email'] ?? null;
    
    // Update password using Supabase (same approach as handle_reset_password)
    $supabase_url = SUPABASE_URL;
    $supabase_key = SUPABASE_API_KEY;
    $supabase_service_key = SUPABASE_SERVICE_KEY;
    
    $update_url = rtrim($supabase_url, '/') . '/auth/v1/admin/users/' . $user_id;
    $update_data = json_encode(['password' => $password]);
    
    $ch = curl_init($update_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $update_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabase_service_key,
        'Authorization: Bearer ' . $supabase_service_key
    ]);
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status_code === 200 && $email) {
        // Verify password by signing in (same as change-password.php)
        $sign_in_url = rtrim($supabase_url, '/') . '/auth/v1/token?grant_type=password';
        $sign_in_data = json_encode([
            'email' => $email,
            'password' => $password
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

        if ($sign_in_status === 200 && !empty($sign_in_result['access_token'])) {
            mark_password_reset_token_used($token);
            return ['success' => true];
        } else {
            error_log("reset_password_with_token: admin update returned 200 but login with new password failed for email: $email, status: $sign_in_status");
            return [
                'success' => false,
                'message' => 'Failed to verify new password'
            ];
        }
    } else if ($status_code === 200) {
        // If we don't have email, just mark as used (fallback)
        mark_password_reset_token_used($token);
        return ['success' => true];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update password'
        ];
    }
}
?>

