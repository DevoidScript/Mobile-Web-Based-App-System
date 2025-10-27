<?php
/**
 * Save Push Subscription API
 * 
 * Saves or updates a donor's push subscription in the database.
 * Called from the client when a user subscribes to push notifications.
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
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

// Check if user is logged in
if (!is_logged_in()) {
    send_response(false, 'Unauthorized. Please log in.', null, 401);
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(false, 'Invalid request method. Only POST is allowed.', null, 405);
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    send_response(false, 'Invalid JSON payload.', null, 400);
}

// Validate required fields
if (empty($data['subscription'])) {
    send_response(false, 'Missing subscription data.', null, 400);
}

$subscription = $data['subscription'];

// Extract subscription details
$endpoint = $subscription['endpoint'] ?? '';
$p256dh = $subscription['keys']['p256dh'] ?? '';
$auth = $subscription['keys']['auth'] ?? '';

if (empty($endpoint) || empty($p256dh) || empty($auth)) {
    send_response(false, 'Invalid subscription format. Missing endpoint or keys.', null, 400);
}

// Get donor_id from session
$user = $_SESSION['user'];
$donor_id = null;

// Try to get donor_id from various sources
if (isset($user['donor_id'])) {
    $donor_id = $user['donor_id'];
} elseif (isset($user['id'])) {
    $donor_id = $user['id'];
} elseif (isset($user['email'])) {
    // Fetch donor_id from donor_form table by email
    $email = trim(strtolower($user['email']));
    $donorFormResp = get_records('donor_form', ['email' => 'eq.' . $email]);
    if ($donorFormResp['success'] && !empty($donorFormResp['data'])) {
        $donor_id = $donorFormResp['data'][0]['donor_id'];
    }
}

if (!$donor_id) {
    send_response(false, 'Could not determine donor ID.', null, 400);
}

// Check if subscription already exists for this donor and endpoint
$existing = get_records('push_subscriptions', [
    'donor_id' => 'eq.' . $donor_id,
    'endpoint' => 'eq.' . $endpoint
]);

// Prepare subscription data
$subscription_data = [
    'donor_id' => intval($donor_id),
    'endpoint' => $endpoint,
    'p256dh' => $p256dh,
    'auth' => $auth,
    'updated_at' => date('Y-m-d\TH:i:s\Z')
];

// Set expiration if provided
if (isset($subscription['expirationTime']) && $subscription['expirationTime']) {
    $subscription_data['expires_at'] = date('Y-m-d\TH:i:s\Z', $subscription['expirationTime'] / 1000);
}

try {
    if ($existing['success'] && !empty($existing['data'])) {
        // Update existing subscription
        $subscription_id = $existing['data'][0]['id'];
        $result = update_record('push_subscriptions', $subscription_id, $subscription_data, 'id');
        
        if ($result['success']) {
            send_response(true, 'Push subscription updated successfully.', [
                'subscription_id' => $subscription_id
            ]);
        } else {
            error_log('Failed to update push subscription: ' . json_encode($result));
            send_response(false, 'Failed to update push subscription.', null, 500);
        }
    } else {
        // Insert new subscription
        $result = create_record('push_subscriptions', $subscription_data);
        
        if ($result['success'] && !empty($result['data'])) {
            send_response(true, 'Push subscription saved successfully.', [
                'subscription_id' => $result['data'][0]['id']
            ], 201);
        } else {
            error_log('Failed to save push subscription: ' . json_encode($result));
            send_response(false, 'Failed to save push subscription.', null, 500);
        }
    }
} catch (Exception $e) {
    error_log('Exception saving push subscription: ' . $e->getMessage());
    send_response(false, 'An error occurred while saving the subscription.', null, 500);
}
?>




