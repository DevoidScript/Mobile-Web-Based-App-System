<?php
/**
 * Authentication endpoints for the Red Cross Mobile App
 * Handles user login, registration, and session management with Supabase integration
 * 
 * Note: ID field requirements have been removed since they don't exist in
 * the donors_detail table structure. This includes fields like:
 * - School ID, Company ID, PRC License, Driver's License, SSS/GSIS/BIR ID, etc.
 * The validation for these fields has been removed to match the actual database structure.
 */

// Include necessary files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle the different authentication endpoints
$endpoint = isset($_GET['login']) ? 'login' : (isset($_GET['register']) ? 'register' : (isset($_GET['logout']) ? 'logout' : 'unknown'));

switch ($endpoint) {
    case 'login':
        handleLogin();
        break;
        
    case 'register':
        handleRegistration();
        break;
        
    case 'logout':
        handleLogout();
        break;
        
    default:
        // Redirect to home page if endpoint is not recognized
        header('Location: ../index.php?error=Invalid endpoint');
        exit;
}

/**
 * Handle user login
 */
function handleLogin() {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?error=Invalid request method');
        exit;
    }
    
    // Get form data
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        header('Location: ../index.php?error=Email and password are required');
        exit;
    }
    
    // Perform login with Supabase
    $data = [
        'email' => $email,
        'password' => $password
    ];
    
    // Call Supabase signIn endpoint
    $response = supabase_request('auth/v1/token?grant_type=password', 'POST', $data);
    
    if (!$response['success']) {
        // Login failed
        header('Location: ../index.php?error=Invalid email or password');
        exit;
    }
    
    // Login successful, store user data in session
    $userData = $response['data'];
    
    if (isset($userData['access_token']) && isset($userData['user'])) {
        $_SESSION['user'] = $userData['user'];
        $_SESSION['token'] = $userData['access_token'];
        
        // Get additional user data from donors_detail table
        $user_id = $userData['user']['id']; 
        $donor_data = get_record('donors_detail', $user_id);
        
        if ($donor_data['success'] && !empty($donor_data['data'])) {
            $_SESSION['donor_details'] = $donor_data['data'][0];
        }
        
        // Redirect to dashboard or home page
        header('Location: ../templates/dashboard.php');
        exit;
    } else {
        // Something went wrong
        header('Location: ../index.php?error=Login failed. Please try again.');
        exit;
    }
}

/**
 * Handle user registration
 * 
 * This function processes the registration form data, creates a new user in Supabase Auth,
 * and stores the donor details in the public.donors_detail table in Supabase.
 * 
 * @param array $postData Optional POST data to use instead of $_POST
 * @return bool|string True on success, error message on failure
 */
function handleRegistration($postData = null) {
    // Get POST data from parameter or global $_POST
    $post = $postData ?: $_POST;
    
    // Check if it's a direct call or from form submission
    $isDirectCall = $postData !== null;
    
    // If not a direct call, check if it's a POST request
    if (!$isDirectCall && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        if (!$isDirectCall) {
            header('Location: ../templates/register.php?error=Invalid request method');
            exit;
        }
        return "Invalid request method";
    }
    
    // Get form data
    $email = isset($post['email']) ? sanitize_input($post['email']) : '';
    $password = isset($post['password']) ? $post['password'] : '';
    $confirm_password = isset($post['confirm_password']) ? $post['confirm_password'] : '';
    
    // Check if password match
    if ($password !== $confirm_password) {
        if (!$isDirectCall) {
            header('Location: ../templates/register.php?error=Passwords do not match');
            exit;
        }
        return "Passwords do not match";
    }
    
    // Register user if this is a register request or a direct call
    if ($isDirectCall || ($_GET['register'] ?? false)) {
        // Create user with Supabase auth
        $email = sanitize_input($post['email']);
        $password = $post['password'];
        
        $user_data = create_user($email, $password);
        
        if (!$user_data) {
            if (!$isDirectCall) {
                $_SESSION['error'] = "Failed to create user account.";
                header('Location: ../templates/register.php');
                exit;
            }
            return "Failed to create user account";
        }
        
        $user_id = $user_data['id'];
        
        // Prepare donor details
        $donor_data = [
            'user_id' => $user_id, // This should match the foreign key type in the donors_detail table
            'surname' => sanitize_input($post['surname'] ?? ''),
            'first_name' => sanitize_input($post['first_name'] ?? ''),
            'middle_name' => sanitize_input($post['middle_name'] ?? null),
            'birthdate' => $post['birthdate'] ?? null,
            'age' => intval($post['age'] ?? 0),
            'sex' => sanitize_input($post['sex'] ?? ''),
            'civil_status' => sanitize_input($post['civil_status'] ?? ''),
            'nationality' => sanitize_input($post['nationality'] ?? ''),
            'religion' => sanitize_input($post['religion'] ?? null),
            'education' => sanitize_input($post['education'] ?? null),
            'occupation' => sanitize_input($post['occupation'] ?? ''),
            'mobile' => sanitize_input($post['mobile'] ?? ''),
            'telephone' => sanitize_input($post['telephone'] ?? null),
            'email' => $email
        ];
        
        // Handle address fields - use permanent_address if provided, otherwise combine the components
        if (!empty($post['permanent_address'])) {
            $donor_data['permanent_address'] = sanitize_input($post['permanent_address']);
        } elseif (!empty($post['barangay']) && !empty($post['municipality']) && !empty($post['province'])) {
            // Safely get address components
            $house_no = isset($post['house_no']) ? $post['house_no'] . ' ' : '';
            $street = isset($post['street']) ? $post['street'] . ', ' : '';
            $barangay = isset($post['barangay']) ? $post['barangay'] . ', ' : '';
            $municipality = isset($post['municipality']) ? $post['municipality'] . ', ' : '';
            $province = isset($post['province']) ? $post['province'] : '';
            $postal_code = isset($post['postal_code']) ? ' ' . $post['postal_code'] : '';
            
            // Combine address parts
            $donor_data['permanent_address'] = sanitize_input(
                $house_no . 
                $street . 
                $barangay . 
                $municipality . 
                $province . 
                $postal_code
            );
        }
        
        // Handle office address if provided
        if (!empty($post['office_address'])) {
            $donor_data['office_address'] = sanitize_input($post['office_address']);
        }
        
        // Remove null values for cleaner insert
        foreach ($donor_data as $key => $value) {
            if ($value === null) {
                unset($donor_data[$key]);
            }
        }
        
        // Insert donor details into the public schema's donors_detail table
        $result = db_insert('public.donors_detail', $donor_data);
        
        if (!$result) {
            if (!$isDirectCall) {
                $_SESSION['error'] = "Registration failed. Please try again.";
                header('Location: ../templates/register.php');
                exit;
            }
            return "Failed to insert donor details";
        }
        
        // Check if the insert was actually successful
        if (!$result['success'] || empty($result['data'])) {
            if (!$isDirectCall) {
                $_SESSION['error'] = "Registration failed. Database error occurred.";
                header('Location: ../templates/register.php');
                exit;
            }
            return "Database error occurred during registration";
        }
        
        // If direct call, return success
        if ($isDirectCall) {
            return true;
        }
        
        // Complete the registration and redirect to login page
        $_SESSION['success'] = "Registration successful! Please login with your new account.";
        
        // Redirect to index.php (which includes the login.php template)
        header('Location: ../index.php?success=Registration successful! Please login with your new account.');
        exit;
    }
    
    // Default return for direct calls
    if ($isDirectCall) {
        return "No registration request detected";
    }
    
    // Default redirect for form submissions
    header('Location: ../templates/register.php?error=Invalid registration request');
    exit;
}

/**
 * Handle user logout
 */
function handleLogout() {
    // Check if user is logged in
    if (!isset($_SESSION['user'])) {
        header('Location: ../index.php?error=You are not logged in');
        exit;
    }
    
    // Clear user session data
    unset($_SESSION['user']);
    unset($_SESSION['token']);
    unset($_SESSION['donor_details']);
    
    // Destroy session
    session_destroy();
    
    // Restart session to set success message
    session_start();
    $_SESSION['success'] = "You have been successfully logged out.";
    
    // Redirect to login page
    header('Location: ../index.php?success=You have been successfully logged out');
    exit;
}

/**
 * Create a new user in Supabase Auth
 * 
 * @param string $email User's email
 * @param string $password User's password
 * @return array|bool The created user data or false on failure
 */
function create_user($email, $password) {
    $data = [
        'email' => $email,
        'password' => $password
    ];
    
    $response = supabase_request('auth/v1/signup', 'POST', $data);
    
    if (!$response['success']) {
        return false;
    }
    
    return $response['data']['user'] ?? false;
}
?> 