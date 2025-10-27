<?php
/**
 * Broadcast Blood Drive Notification API
 * 
 * Sends push notifications to selected donors about blood drives.
 * Can filter by location (PostGIS), blood type, and other criteria.
 * 
 * Requires composer autoload and minishlink/web-push library.
 */

require_once '../config/database.php';
require_once '../config/push.php';
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

// Check if user is logged in and has admin/staff privileges
// TODO: Implement proper role-based access control
if (!is_logged_in()) {
    send_response(false, 'Unauthorized. Please log in.', null, 401);
}

// For now, allow any authenticated user to send broadcasts
// In production, restrict to admin/staff roles only

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

// Extract notification details
$title = $data['title'] ?? 'Blood Drive Notification';
$body = $data['body'] ?? '';
$url = $data['url'] ?? '/mobile-app/';
$icon = $data['icon'] ?? '/mobile-app/assets/icons/icon-192x192.png';
$blood_drive_id = $data['blood_drive_id'] ?? null;

// Extract targeting criteria (optional)
$blood_type = $data['blood_type'] ?? null;
$location_lat = $data['location_lat'] ?? null;
$location_lng = $data['location_lng'] ?? null;
$radius_km = $data['radius_km'] ?? null;
$donor_ids = $data['donor_ids'] ?? null; // Array of specific donor IDs

if (empty($body)) {
    send_response(false, 'Notification body is required.', null, 400);
}

// Build payload for push notification
$payload = json_encode([
    'title' => $title,
    'body' => $body,
    'icon' => $icon,
    'url' => $url,
    'blood_drive_id' => $blood_drive_id,
    'timestamp' => time()
]);

// Build query to select target donors
$query_params = [];

if ($donor_ids && is_array($donor_ids)) {
    // Target specific donors
    $donor_ids_str = implode(',', array_map('intval', $donor_ids));
    $query_params['donor_id'] = 'in.(' . $donor_ids_str . ')';
} else {
    // Target all donors (or apply filters)
    // TODO: Implement blood type filtering
    // TODO: Implement PostGIS radius filtering for location-based targeting
}

// Fetch push subscriptions for target donors
$subscriptions_result = get_records('push_subscriptions', $query_params);

if (!$subscriptions_result['success']) {
    send_response(false, 'Failed to fetch push subscriptions.', null, 500);
}

$subscriptions = $subscriptions_result['data'] ?? [];

if (empty($subscriptions)) {
    send_response(true, 'No subscriptions found for the selected criteria.', [
        'sent' => 0,
        'failed' => 0,
        'total' => 0
    ]);
}

// Initialize Web Push sender
$webPush = build_webpush_sender();

if (!$webPush) {
    send_response(false, 'Web Push library not available. Please run: composer install', null, 500);
}

// Send push notifications
$sent_count = 0;
$failed_count = 0;
$notifications_to_log = [];

foreach ($subscriptions as $sub) {
    try {
        // Create subscription object for web-push library
        $subscription = \Minishlink\WebPush\Subscription::create([
            'endpoint' => $sub['endpoint'],
            'keys' => [
                'p256dh' => $sub['p256dh'],
                'auth' => $sub['auth']
            ]
        ]);

        // Queue notification
        $report = $webPush->sendOneNotification($subscription, $payload);

        // Check if successful
        if ($report->isSuccess()) {
            $sent_count++;
            $notifications_to_log[] = [
                'donor_id' => $sub['donor_id'],
                'payload_json' => json_decode($payload, true),
                'status' => 'sent',
                'blood_drive_id' => $blood_drive_id,
                'sent_at' => date('Y-m-d\TH:i:s\Z')
            ];
        } else {
            $failed_count++;
            $error_message = $report->getReason();
            
            // Log failure
            $notifications_to_log[] = [
                'donor_id' => $sub['donor_id'],
                'payload_json' => json_decode($payload, true),
                'status' => 'failed',
                'blood_drive_id' => $blood_drive_id,
                'error_message' => $error_message,
                'sent_at' => date('Y-m-d\TH:i:s\Z')
            ];

            // If subscription is expired/invalid (410/404), delete it
            $status_code = $report->getResponse() ? $report->getResponse()->getStatusCode() : null;
            if ($status_code == 404 || $status_code == 410) {
                error_log("Deleting stale subscription for donor {$sub['donor_id']}: {$sub['endpoint']}");
                delete_record('push_subscriptions', $sub['id']);
            }
        }
    } catch (Exception $e) {
        $failed_count++;
        error_log('Push notification error for donor ' . $sub['donor_id'] . ': ' . $e->getMessage());
        
        $notifications_to_log[] = [
            'donor_id' => $sub['donor_id'],
            'payload_json' => json_decode($payload, true),
            'status' => 'failed',
            'blood_drive_id' => $blood_drive_id,
            'error_message' => $e->getMessage(),
            'sent_at' => date('Y-m-d\TH:i:s\Z')
        ];
    }
}

// Log all notifications to donor_notifications table
foreach ($notifications_to_log as $notification) {
    create_record('donor_notifications', $notification);
}

// Return summary
send_response(true, 'Broadcast completed.', [
    'sent' => $sent_count,
    'failed' => $failed_count,
    'total' => count($subscriptions),
    'payload' => json_decode($payload, true)
]);
?>




