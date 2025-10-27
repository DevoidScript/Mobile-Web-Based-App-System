<?php
/**
 * Test Push Notification Page
 * 
 * Simple testing interface for sending push notifications.
 * Use this to test the broadcast functionality.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

session_start();

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}

$result = null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_push'])) {
    $payload = [
        'title' => $_POST['title'] ?? 'Test Notification',
        'body' => $_POST['body'] ?? 'This is a test notification',
        'url' => $_POST['url'] ?? '/mobile-app/templates/dashboard.php',
        'icon' => $_POST['icon'] ?? '/mobile-app/assets/icons/icon-192x192.png',
        'blood_drive_id' => !empty($_POST['blood_drive_id']) ? intval($_POST['blood_drive_id']) : null
    ];
    
    // If targeting specific donors
    if (!empty($_POST['donor_ids'])) {
        $donor_ids = array_map('trim', explode(',', $_POST['donor_ids']));
        $donor_ids = array_map('intval', $donor_ids);
        $payload['donor_ids'] = $donor_ids;
    }
    
    // Send request to broadcast endpoint
    $ch = curl_init('http://localhost/mobile-app/api/broadcast-blood-drive.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Cookie: ' . $_SERVER['HTTP_COOKIE']
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response) {
        $result = json_decode($response, true);
    } else {
        $error = 'Failed to send request to broadcast API';
    }
}

// Get current subscriptions count
$subscriptions = get_records('push_subscriptions', []);
$total_subscriptions = $subscriptions['success'] ? count($subscriptions['data']) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Push Notifications - Blood Donation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .result-box {
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .result-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .result-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .stats {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔔 Test Push Notifications</h1>
        <p class="text-muted">Send test push notifications to donors</p>
        
        <div class="stats">
            <h5>Current Statistics</h5>
            <p><strong>Total Active Subscriptions:</strong> <?php echo $total_subscriptions; ?></p>
            <?php if ($total_subscriptions == 0): ?>
                <div class="alert alert-warning">
                    No active subscriptions found. Make sure donors have enabled notifications on their devices.
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($result): ?>
            <div class="result-box <?php echo $result['success'] ? 'result-success' : 'result-error'; ?>">
                <h5><?php echo $result['success'] ? '✓ Success' : '✗ Error'; ?></h5>
                <p><strong>Message:</strong> <?php echo htmlspecialchars($result['message']); ?></p>
                <?php if (isset($result['data'])): ?>
                    <p><strong>Sent:</strong> <?php echo $result['data']['sent']; ?></p>
                    <p><strong>Failed:</strong> <?php echo $result['data']['failed']; ?></p>
                    <p><strong>Total:</strong> <?php echo $result['data']['total']; ?></p>
                <?php endif; ?>
                <details>
                    <summary>Full Response</summary>
                    <pre><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)); ?></pre>
                </details>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="result-box result-error">
                <h5>✗ Error</h5>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label for="title" class="form-label">Notification Title</label>
                <input type="text" class="form-control" id="title" name="title" value="Blood Drive Alert" required>
            </div>
            
            <div class="mb-3">
                <label for="body" class="form-label">Notification Body</label>
                <textarea class="form-control" id="body" name="body" rows="3" required>Join us at City Hospital tomorrow, 9 AM - 5 PM. Your donation can save lives!</textarea>
            </div>
            
            <div class="mb-3">
                <label for="url" class="form-label">Click URL (Deep Link)</label>
                <input type="text" class="form-control" id="url" name="url" value="/mobile-app/templates/dashboard.php">
                <small class="form-text text-muted">URL to open when notification is clicked</small>
            </div>
            
            <div class="mb-3">
                <label for="icon" class="form-label">Icon URL</label>
                <input type="text" class="form-control" id="icon" name="icon" value="/mobile-app/assets/icons/icon-192x192.png">
            </div>
            
            <div class="mb-3">
                <label for="blood_drive_id" class="form-label">Blood Drive ID (Optional)</label>
                <input type="number" class="form-control" id="blood_drive_id" name="blood_drive_id">
                <small class="form-text text-muted">Leave empty if not related to a specific blood drive</small>
            </div>
            
            <div class="mb-3">
                <label for="donor_ids" class="form-label">Target Donor IDs (Optional)</label>
                <input type="text" class="form-control" id="donor_ids" name="donor_ids" placeholder="1,2,3">
                <small class="form-text text-muted">Comma-separated donor IDs. Leave empty to send to all subscribed donors.</small>
            </div>
            
            <button type="submit" name="send_push" class="btn btn-primary">Send Test Push Notification</button>
            <a href="templates/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </form>
        
        <hr class="my-4">
        
        <h5>Testing Checklist</h5>
        <ul>
            <li>✓ Composer installed and dependencies loaded</li>
            <li>✓ VAPID keys configured in <code>config/push.php</code></li>
            <li>✓ Supabase tables created (<code>push_subscriptions</code>, <code>donor_notifications</code>)</li>
            <li>✓ Service worker registered and active</li>
            <li>✓ At least one donor has enabled notifications</li>
            <li>✓ Testing on HTTPS or localhost</li>
        </ul>
        
        <div class="alert alert-info mt-3">
            <strong>Note:</strong> If you don't receive the notification:
            <ul>
                <li>Check browser console for errors</li>
                <li>Verify service worker is active (DevTools → Application → Service Workers)</li>
                <li>Ensure notification permission is granted</li>
                <li>Check that you have an active subscription in the database</li>
            </ul>
        </div>
    </div>
</body>
</html>

