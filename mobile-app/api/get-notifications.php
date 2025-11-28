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

    // Preload blood drive message templates when available
    $blood_drive_cache = [];
    // Transform database notifications to bell panel format
    $bell_notifications = [];
    foreach ($notifications_result['data'] as $notification) {
        // payload_json may come back as a JSON string (from DB) or already decoded (from API)
        $rawPayload = $notification['payload_json'] ?? null;
        if (is_array($rawPayload)) {
            $payload = $rawPayload;
        } else {
            $decoded = json_decode((string)$rawPayload, true);
            $payload = is_array($decoded) ? $decoded : [];
        }
        
        // Convert timestamp to milliseconds for JavaScript
        $timestamp = strtotime($notification['sent_at']);
        if ($timestamp === false) {
            $timestamp = time(); // Fallback to current time
        }
        $timestamp_ms = $timestamp * 1000; // Convert to milliseconds for JavaScript
        $blood_drive_id = $notification['blood_drive_id'] ?? ($payload['blood_drive_id'] ?? ($payload['data']['blood_drive_id'] ?? null));
        $blood_drive_meta = null;
        if ($blood_drive_id) {
            if (!array_key_exists($blood_drive_id, $blood_drive_cache)) {
                $drive_lookup = get_records('blood_drive_notifications', [
                    'id' => 'eq.' . $blood_drive_id,
                    'limit' => 1
                ]);
                if ($drive_lookup['success'] && !empty($drive_lookup['data'])) {
                    $blood_drive_cache[$blood_drive_id] = $drive_lookup['data'][0];
                } else {
                    $blood_drive_cache[$blood_drive_id] = null;
                }
            }
            $blood_drive_meta = $blood_drive_cache[$blood_drive_id];
        }
        $message_template = $blood_drive_meta['message_template'] ?? ($payload['message_template'] ?? null);

        // Convert absolute URLs to relative URLs for better session handling
        $urlFromPayload = $payload['url'] ?? ($payload['data']['url'] ?? null);
        $url = $urlFromPayload ?: '/mobile-app/templates/dashboard.php';
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
            'message_template' => $message_template,
            'url' => $url,
            'timestamp' => $timestamp_ms,
            'status' => $notification['status'],
            'blood_drive_id' => $blood_drive_id,
            'location' => $blood_drive_meta['location'] ?? null,
            'drive_date' => $blood_drive_meta['drive_date'] ?? null,
            'drive_time' => $blood_drive_meta['drive_time'] ?? null,
            'radius_km' => $blood_drive_meta['radius_km'] ?? null
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
