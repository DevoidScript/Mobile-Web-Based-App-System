<?php
/**
 * Donor Registration API Handler
 * 
 * This file provides a dedicated API endpoint for handling donor registration data
 * and inserting it into the Supabase donors_detail table.
 * 
 * The file handles:
 * 1. Direct API calls from the registration form
 * 2. Authentication with Supabase
 * 3. Insertion of donor details into the donors_detail table
 * 4. Proper error handling
 * 5. Returning JSON responses for programmatic handling
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

// Validate required fields
$required_fields = [
    'surname', 
    'first_name', 
    'sex', 
    'civil_status', 
    'birthdate', 
    'nationality', 
    'occupation', 
    'mobile', 
    'permanent_address',
    'email',
    'password'
];

foreach ($required_fields as $field) {
    if (empty($post_data[$field])) {
        send_response(false, "Missing required field: $field", null, 400);
    }
}

// Create user in Supabase Auth
try {
    $auth_data = [
        'email' => sanitize_input($post_data['email']),
        'password' => $post_data['password']
    ];
    
    $auth_response = supabase_request('auth/v1/signup', 'POST', $auth_data);
    
    if (!$auth_response['success']) {
        // Check if user already exists
        if (isset($auth_response['status_code']) && $auth_response['status_code'] === 400) {
            send_response(false, 'Registration failed: Email already registered', null, 400);
        } else {
            send_response(false, 'Auth signup failed: ' . json_encode($auth_response['data'] ?? []), null, 500);
        }
    }
    
    // Get user data from response
    $user_data = $auth_response['data']['user'] ?? false;
    
    if (!$user_data) {
        send_response(false, 'Failed to create user account: No user data returned', null, 500);
    }
    
    // Get user ID
    $user_id = $user_data['id'];
    
    // Format birthdate
    $birthdate = $post_data['birthdate'] ?? null;
    if ($birthdate) {
        $birthdate = date('Y-m-d', strtotime($birthdate));
    }
    
    // Prepare donor details
    $donor_data = [
        'id' => $user_id,  // This is the primary key matching auth.users.id
        'surname' => sanitize_input($post_data['surname'] ?? ''),
        'first_name' => sanitize_input($post_data['first_name'] ?? ''),
        'middle_name' => sanitize_input($post_data['middle_name'] ?? null),
        'birthdate' => $birthdate,
        'age' => intval($post_data['age'] ?? 0),
        'sex' => sanitize_input($post_data['sex'] ?? ''),
        'civil_status' => sanitize_input($post_data['civil_status'] ?? ''),
        'permanent_address' => sanitize_input($post_data['permanent_address'] ?? ''),
        'nationality' => sanitize_input($post_data['nationality'] ?? ''),
        'religion' => sanitize_input($post_data['religion'] ?? null),
        'education' => sanitize_input($post_data['education'] ?? null),
        'occupation' => sanitize_input($post_data['occupation'] ?? ''),
        'mobile' => sanitize_input($post_data['mobile'] ?? ''),
        'email' => sanitize_input($post_data['email'])
    ];
    
    // Remove null values
    foreach ($donor_data as $key => $value) {
        if ($value === null) {
            unset($donor_data[$key]);
        }
    }
    
    // Method 1: Try using standard supabase_request with service role
    $headers = [
        'Prefer: return=representation',
        'Content-Profile: public'
    ];
    
    $standard_result = supabase_request('rest/v1/donors_detail', 'POST', $donor_data, $headers, true);
    
    if ($standard_result['success']) {
        $_SESSION['success'] = "Registration successful! Please login with your new account.";
        send_response(true, "Registration successful", ['redirect' => '../login.php'], 201);
        exit;
    }
    
    // Method 2: Try UPSERT approach with on_conflict parameter
    $upsert_headers = [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_SERVICE_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
        'Prefer: return=representation',
        'Content-Profile: public'
    ];
    
    // The Supabase REST API endpoint for upsert
    $upsert_url = SUPABASE_URL . '/rest/v1/donors_detail?on_conflict=id';
    
    $ch = curl_init($upsert_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($donor_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $upsert_headers);
    
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if (!$error && $status_code >= 200 && $status_code < 300) {
        $_SESSION['success'] = "Registration successful! Please login with your new account.";
        send_response(true, "Registration successful", ['redirect' => '../login.php'], 201);
        exit;
    }
    
    // Let the user know something went wrong in a friendly way
    send_response(false, "Registration was successful, but we couldn't save your donor details. Please log in and update your profile information.", ['redirect' => '../login.php?update_profile=true'], 201);
    
} catch (Exception $e) {
    send_response(false, 'An unexpected error occurred: ' . $e->getMessage(), null, 500);
} 