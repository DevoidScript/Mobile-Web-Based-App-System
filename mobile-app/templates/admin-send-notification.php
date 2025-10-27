<?php
/**
 * Simple admin panel to send test notifications
 * This allows you to test the notification system without setting up VAPID keys
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Simple authentication check (you can enhance this)
if (!is_logged_in()) {
    header('Location: ../templates/login.php');
    exit;
}

$message = '';
$error = '';

if ($_POST) {
    $title = $_POST['title'] ?? 'Test Notification';
    $body = $_POST['body'] ?? 'This is a test notification';
    $url = $_POST['url'] ?? '/mobile-app/templates/dashboard.php';
    $target_donor_id = $_POST['target_donor_id'] ?? null;
    
    // Create notification payload
    $payload = [
        'title' => $title,
        'body' => $body,
        'url' => $url,
        'timestamp' => time()
    ];
    
    if ($target_donor_id) {
        // Send to specific donor
        $payload['target_donor_id'] = $target_donor_id;
    }
    
    // Use the broadcast API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/mobile-app/api/broadcast-blood-drive.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            $message = "Notification sent successfully!";
        } else {
            $error = "API Error: " . ($result['message'] ?? 'Unknown error');
        }
    } else {
        $error = "HTTP Error: " . $http_code . " - " . $response;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Test Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        button {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #c82333;
        }
        .message {
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .help {
            background: #e2e3e5;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔔 Send Test Notification</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="title">Notification Title:</label>
                <input type="text" id="title" name="title" value="Test Notification" required>
            </div>
            
            <div class="form-group">
                <label for="body">Message:</label>
                <textarea id="body" name="body" placeholder="Enter your notification message here..." required>This is a test notification to verify the system is working.</textarea>
            </div>
            
            <div class="form-group">
                <label for="url">Click URL (optional):</label>
                <input type="text" id="url" name="url" value="/mobile-app/templates/dashboard.php" placeholder="Where should the notification link to?">
            </div>
            
            <div class="form-group">
                <label for="target_donor_id">Target Donor ID (optional):</label>
                <input type="number" id="target_donor_id" name="target_donor_id" placeholder="Leave empty to send to all donors">
                <small>Enter 211 to test with donor 211 specifically</small>
            </div>
            
            <button type="submit">Send Test Notification</button>
        </form>
        
        <div class="help">
            <h3>How to Test:</h3>
            <ol>
                <li><strong>Send to All:</strong> Leave "Target Donor ID" empty to send to all subscribers</li>
                <li><strong>Send to Donor 211:</strong> Enter "211" in the Target Donor ID field</li>
                <li><strong>Check Results:</strong> Look for the notification in the bell icon panel</li>
            </ol>
            
            <h3>Expected Results:</h3>
            <ul>
                <li>✅ <strong>If VAPID keys are real:</strong> Notification appears in bell panel + system notification</li>
                <li>⚠️ <strong>If VAPID keys are placeholder:</strong> Only database logging works, no actual notification</li>
            </ul>
            
            <h3>Current Status:</h3>
            <p>VAPID keys are currently placeholder values. To send real notifications:</p>
            <ol>
                <li>Go to <a href="https://vapidkeys.com/" target="_blank">vapidkeys.com</a></li>
                <li>Generate new VAPID keys</li>
                <li>Update <code>mobile-app/config/push.php</code> with real keys</li>
            </ol>
        </div>
    </div>
</body>
</html>

