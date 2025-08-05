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
    
    // Calculate age from birthdate
    $birthdate = $post_data['birthdate'] ?? null;
    $age = null;
    if ($birthdate) {
        $birthdate_dt = new DateTime($birthdate);
        $today = new DateTime('today');
        $age = $birthdate_dt->diff($today)->y;
    }
    
    // Helper functions for PRC donor number and DOH NNBNETS barcode
    define('PRC_DONOR_PREFIX', 'PRC');
    define('DOH_BARCODE_PREFIX', 'DOH');
    function generateDonorNumber() {
        $year = date('Y');
        $randomNumber = mt_rand(10000, 99999); // 5-digit random number
        return PRC_DONOR_PREFIX . "-$year-$randomNumber";
    }
    function generateNNBNetBarcode() {
        $year = date('Y');
        $randomNumber = mt_rand(1000, 9999); // 4-digit random number
        return DOH_BARCODE_PREFIX . "-$year$randomNumber";
    }
    
    // Prepare donor_form data (instead of donors_detail)
    $donor_form_data = [
        'surname' => sanitize_input($post_data['surname'] ?? ''),
        'first_name' => sanitize_input($post_data['first_name'] ?? ''),
        'middle_name' => sanitize_input($post_data['middle_name'] ?? null),
        'birthdate' => $birthdate,
        'age' => $age,
        'sex' => sanitize_input($post_data['sex'] ?? ''),
        'civil_status' => sanitize_input($post_data['civil_status'] ?? ''),
        'permanent_address' => sanitize_input($post_data['permanent_address'] ?? ''),
        'nationality' => sanitize_input($post_data['nationality'] ?? ''),
        'religion' => sanitize_input($post_data['religion'] ?? null),
        'education' => sanitize_input($post_data['education'] ?? null),
        'occupation' => sanitize_input($post_data['occupation'] ?? ''),
        'mobile' => sanitize_input($post_data['mobile'] ?? ''),
        'email' => sanitize_input($post_data['email']),
        'prc_donor_number' => generateDonorNumber(),
        'doh_nnbnets_barcode' => generateNNBNetBarcode(),
        'registration_channel' => 'Mobile',
        // Add more fields as needed for donor_form schema
    ];
    // Remove null values
    foreach ($donor_form_data as $key => $value) {
        if ($value === null) {
            unset($donor_form_data[$key]);
        }
    }
    // Insert into donor_form table
    $headers = [
        'Prefer: return=representation',
        'Content-Profile: public'
    ];
    $insert_result = supabase_request('rest/v1/donor_form', 'POST', $donor_form_data, $headers, true);
    if ($insert_result['success']) {
        // Check if data exists and has the expected structure
        if (isset($insert_result['data']) && is_array($insert_result['data']) && !empty($insert_result['data']) && isset($insert_result['data'][0]['id'])) {
            $donor_form_id = $insert_result['data'][0]['id'];
            $_SESSION['donor_form_id'] = $donor_form_id;
        } else {
            // Data structure is not as expected, but registration was successful
            // We'll continue without the donor_form_id
            $donor_form_id = null;
        }
        
        // Set success message and redirect to login
        $_SESSION['success'] = "Registration successful! Please login with your new account.";
        send_response(true, "Registration successful", ['redirect' => '/Mobile-Web-Based-App-System/mobile-app/templates/login.php'], 201);
        exit;
    }
    send_response(false, "Registration failed: " . json_encode($insert_result['data'] ?? []), null, 500);
    
} catch (Exception $e) {
    send_response(false, 'An unexpected error occurred: ' . $e->getMessage(), null, 500);
} 