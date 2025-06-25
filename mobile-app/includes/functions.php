<?php
/**
 * Common helper functions for the PWA
 */

/**
 * Sanitize input to prevent XSS
 * 
 * @param string $data Input to sanitize
 * @return string Sanitized input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool Whether the email is valid
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a secure random token
 * 
 * @param int $length Length of the token
 * @return string Random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if the request is AJAX
 * 
 * @return bool Whether the request is AJAX
 */
function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get the base URL of the application
 * 
 * @return string Base URL
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    
    return "$protocol://$host$script";
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Get current user from session
 * Renamed from get_current_user() to get_logged_in_user() to avoid conflicts with PHP's built-in function
 * 
 * @return array|null User data or null if not logged in
 */
function get_logged_in_user() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

/**
 * Check if user is logged in
 * 
 * @return bool Whether the user is logged in
 */
function is_logged_in() {
    return get_logged_in_user() !== null;
}

/**
 * Set flash message
 * 
 * @param string $type Message type (success, error, info, warning)
 * @param string $message Message content
 * @return void
 */
function set_flash_message($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_messages'][$type] = $message;
}

/**
 * Get flash messages and clear them
 * 
 * @return array Flash messages
 */
function get_flash_messages() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
    
    // Clear flash messages
    $_SESSION['flash_messages'] = [];
    
    return $messages;
}

/**
 * Check if a donor has a successful donation in the eligibility table
 * Returns an array: [bool has_donated, array|null latest_donation]
 *
 * @param int $donor_id
 * @return array [bool, array|null]
 */
function has_successful_donation($donor_id) {
    $params = [
        'donor_id' => 'eq.' . $donor_id,
        'collection_successful' => 'eq.true',
        'order' => 'collection_start_time.desc'
    ];
    $result = get_records('eligibility', $params);
    if ($result['success'] && !empty($result['data'])) {
        return [true, $result['data'][0]];
    }
    return [false, null];
}

/**
 * Get the latest donor_form record for a donor by donor_id
 * @param string $donor_id Donor UUID
 * @return array Supabase response
 */
function get_donor_form_by_donor_id($donor_id) {
    $params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'id.desc',
        'limit' => 1
    ];
    return get_records('donor_form', $params);
} 