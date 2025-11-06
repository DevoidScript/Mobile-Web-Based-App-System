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

// Resolve donor_form primary key for the logged-in user
$user = $_SESSION['user'];
$donor_id = null;

// 1) Explicit session mappings if present
if (isset($_SESSION['donor_form_id']) && is_numeric($_SESSION['donor_form_id'])) {
    $donor_id = (int) $_SESSION['donor_form_id'];
} elseif (isset($_SESSION['donor_id']) && is_numeric($_SESSION['donor_id'])) {
    $donor_id = (int) $_SESSION['donor_id'];
}

// 2) If still unknown, look up donor_form by user_id (Supabase Auth user id)
if (!$donor_id && isset($user['id'])) {
    $byUserId = get_records('donor_form', ['user_id' => 'eq.' . $user['id'], 'limit' => 1]);
    if ($byUserId['success'] && !empty($byUserId['data'])) {
        $row = $byUserId['data'][0];
        $donor_id = isset($row['id']) ? (int)$row['id'] : (isset($row['donor_id']) ? (int)$row['donor_id'] : null);
    }
}

// 3) Fallback: look up donor_form by email
if (!$donor_id && isset($user['email'])) {
    $email = trim(strtolower($user['email']));
    $byEmail = get_records('donor_form', ['email' => 'eq.' . $email, 'limit' => 1]);
    if ($byEmail['success'] && !empty($byEmail['data'])) {
        $row = $byEmail['data'][0];
        $donor_id = isset($row['id']) ? (int)$row['id'] : (isset($row['donor_id']) ? (int)$row['donor_id'] : null);
    }
}

if (!$donor_id) {
    send_response(false, 'Could not determine donor ID.', null, 400);
}

// Verify donor exists in donor_form to satisfy FK
$verified_donor_row = null;
if ($donor_id) {
    $check = get_records('donor_form', ['id' => 'eq.' . intval($donor_id), 'limit' => 1]);
    if ($check['success'] && !empty($check['data'])) {
        $verified_donor_row = $check['data'][0];
    } else {
        $check2 = get_records('donor_form', ['donor_id' => 'eq.' . intval($donor_id), 'limit' => 1]);
        if ($check2['success'] && !empty($check2['data'])) {
            $verified_donor_row = $check2['data'][0];
        }
    }
}

if (!$verified_donor_row) {
    send_response(false, 'No donor profile found for this account. Please complete your donor profile before enabling notifications.', null, 400);
}

// Ensure only ONE subscription per donor: fetch latest by donor_id
$existing = get_records('push_subscriptions', [
    'donor_id' => 'eq.' . $donor_id,
    'order' => 'created_at.desc',
    'limit' => 1
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
        // Update existing subscription for this donor
        $subscription_id = $existing['data'][0]['id'];
        $result = update_record('push_subscriptions', $subscription_id, $subscription_data, 'id');
        
        if (!$result['success']) {
            error_log('Failed to update push subscription: ' . json_encode($result));
            send_response(false, 'Failed to update push subscription.', null, 500);
        }

        // Clean up any duplicates for the same donor (keep the updated id)
        $dupes = get_records('push_subscriptions', [
            'donor_id' => 'eq.' . $donor_id
        ]);
        if ($dupes['success'] && !empty($dupes['data'])) {
            foreach ($dupes['data'] as $row) {
                if ($row['id'] !== $subscription_id) {
                    delete_record('push_subscriptions', $row['id']);
                }
            }
        }

        send_response(true, 'Push subscription updated successfully.', [
            'subscription_id' => $subscription_id
        ]);
    } else {
        // Insert new subscription; enforce one row per donor
        $result = create_record('push_subscriptions', $subscription_data);
        
        if (!$result['success'] || empty($result['data'])) {
            error_log('Failed to save push subscription: ' . json_encode($result));
            send_response(false, 'Failed to save push subscription.', null, 500);
        }

        $subscription_id = $result['data'][0]['id'];

        // Clean up any older rows for same donor if they exist (defensive)
        $dupes = get_records('push_subscriptions', [
            'donor_id' => 'eq.' . $donor_id
        ]);
        if ($dupes['success'] && !empty($dupes['data'])) {
            foreach ($dupes['data'] as $row) {
                if ($row['id'] !== $subscription_id) {
                    delete_record('push_subscriptions', $row['id']);
                }
            }
        }

        send_response(true, 'Push subscription saved successfully.', [
            'subscription_id' => $subscription_id
        ], 201);
    }
} catch (Exception $e) {
    error_log('Exception saving push subscription: ' . $e->getMessage());
    send_response(false, 'An error occurred while saving the subscription.', null, 500);
}
?>




