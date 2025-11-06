<?php
/**
 * API endpoint to fetch notifications from database for the bell panel
 * GET /api/get-notifications.php?donor_id=211
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Require authentication
    if (!is_logged_in()) {
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ]);
        exit;
    }

    // Resolve the logged-in donor's canonical donor_id (from donor_form)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $session_user = $_SESSION['user'] ?? null;
    $session_donor_id = null;

    if ($session_user) {
        if (isset($session_user['donor_form_id']) && is_numeric($session_user['donor_form_id'])) {
            $session_donor_id = (int) $session_user['donor_form_id'];
        } elseif (isset($session_user['donor_id']) && is_numeric($session_user['donor_id'])) {
            $session_donor_id = (int) $session_user['donor_id'];
        } elseif (isset($session_user['id']) && is_numeric($session_user['id'])) {
            $session_donor_id = (int) $session_user['id'];
        } elseif (!empty($session_user['email'])) {
            $email = trim(strtolower($session_user['email']));
            $lookup = get_records('donor_form', ['email' => 'eq.' . $email, 'limit' => 1]);
            if ($lookup['success'] && !empty($lookup['data'])) {
                $row = $lookup['data'][0];
                $session_donor_id = isset($row['id']) ? (int)$row['id'] : (isset($row['donor_id']) ? (int)$row['donor_id'] : null);
            }
        }
    }

    if (!$session_donor_id) {
        echo json_encode([
            'success' => false,
            'error' => 'Could not resolve donor ID for current session'
        ]);
        exit;
    }

    // Optional donor_id query param is ignored if it doesn't match the session donor
    $requested_donor_id = isset($_GET['donor_id']) ? (int)$_GET['donor_id'] : null;
    $donor_id = $session_donor_id;

    // Fetch notifications from database for this donor (latest 20)
    $notifications_result = get_records(
        'donor_notifications', 
        [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'sent_at.desc',
            'limit' => 20
        ]
    );

    if (!$notifications_result['success']) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch notifications: ' . ($notifications_result['error'] ?? 'Unknown error')
        ]);
        exit;
    }

    // Transform database notifications to bell panel format
    $bell_notifications = [];
    foreach ($notifications_result['data'] as $notification) {
        $payload = $notification['payload_json'];
        
        // Convert timestamp to milliseconds for JavaScript
        $timestamp = strtotime($notification['sent_at']);
        if ($timestamp === false) {
            $timestamp = time(); // Fallback to current time
        }
        $timestamp_ms = $timestamp * 1000; // Convert to milliseconds for JavaScript
        
        // Convert absolute URLs to relative URLs for better session handling
        $url = $payload['url'] ?? '/mobile-app/templates/dashboard.php';
        if (strpos($url, '/Mobile-Web-Based-App-System/mobile-app/') === 0) {
            $url = str_replace('/Mobile-Web-Based-App-System/mobile-app/', '../', $url);
        } elseif (strpos($url, '/mobile-app/') === 0) {
            $url = str_replace('/mobile-app/', '../', $url);
        } elseif (strpos($url, '/') === 0 && !strpos($url, 'http')) {
            $url = '..' . $url;
        }
        
        $bell_notifications[] = [
            'id' => $notification['id'],
            'title' => $payload['title'] ?? 'Notification',
            'body' => $payload['body'] ?? 'You have a new notification',
            'url' => $url,
            'timestamp' => $timestamp_ms,
            'status' => $notification['status'],
            'blood_drive_id' => $notification['blood_drive_id']
        ];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'notifications' => $bell_notifications,
            'count' => count($bell_notifications),
            'donor_id' => $donor_id
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
