<?php
/**
 * Test notification interface
 * Allows you to send notifications directly to the bell panel for visual testing
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Simple authentication check
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_POST) {
    $title = $_POST['title'] ?? 'Test Notification';
    $body = $_POST['body'] ?? 'This is a test notification';
    $url = $_POST['url'] ?? '/mobile-app/templates/dashboard.php';
    
    // Create notification
    $notification = [
        'id' => 'test-' . time(),
        'title' => $title,
        'body' => $body,
        'url' => $url,
        'timestamp' => time()
    ];
    
    $message = "Notification created! Use the JavaScript code below to add it to the bell panel.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Bell Notifications</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
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
            color: #dc3545;
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
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        textarea {
            height: 80px;
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
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .instructions {
            background: #e2e3e5;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .step {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔔 Test Bell Notifications</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="title">Notification Title:</label>
                <input type="text" id="title" name="title" value="New Blood Drive Alert!" required>
            </div>
            
            <div class="form-group">
                <label for="body">Message:</label>
                <textarea id="body" name="body" required>Join us this Saturday at the Community Center for our monthly blood drive. Your donation saves lives!</textarea>
            </div>
            
            <div class="form-group">
                <label for="url">Click URL:</label>
                <input type="text" id="url" name="url" value="/mobile-app/templates/dashboard.php">
            </div>
            
            <button type="submit">Create Test Notification</button>
        </form>
        
        <?php if ($_POST): ?>
        <div class="instructions">
            <h3>📋 How to Add This Notification to the Bell Panel:</h3>
            
            <div class="step">
                <strong>Step 1:</strong> Open the dashboard in another tab:<br>
                <a href="dashboard.php" target="_blank">http://localhost/mobile-app/templates/dashboard.php</a>
            </div>
            
            <div class="step">
                <strong>Step 2:</strong> Open browser developer tools (Press F12)
            </div>
            
            <div class="step">
                <strong>Step 3:</strong> Go to the Console tab
            </div>
            
            <div class="step">
                <strong>Step 4:</strong> Copy and paste this JavaScript code:
            </div>
            
            <div class="code-block">
// Add this notification to the bell panel
const notification = <?php echo json_encode($notification); ?>;
const existing = JSON.parse(localStorage.getItem('appNotifications') || '[]');
existing.unshift(notification);
localStorage.setItem('appNotifications', JSON.stringify(existing));

// Update the bell badge
const badge = document.getElementById('notificationBadge');
if (badge) {
    badge.textContent = existing.length;
    badge.style.display = 'flex';
}

// Refresh notification panel if open
if (typeof loadNotifications === 'function') {
    loadNotifications();
}

console.log('✅ Notification added to bell panel!');
alert('Notification added! Check the bell icon (🔔) in the top right corner.');
            </div>
            
            <div class="step">
                <strong>Step 5:</strong> Press Enter to run the code
            </div>
            
            <div class="step">
                <strong>Step 6:</strong> You should see:
                <ul>
                    <li>✅ Bell icon shows red badge with notification count</li>
                    <li>✅ Click bell icon → panel opens with your notification</li>
                    <li>✅ Click notification → navigates to the specified URL</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="instructions">
            <h3>🎯 What This Tests:</h3>
            <ul>
                <li>✅ Bell panel UI functionality</li>
                <li>✅ Notification display and formatting</li>
                <li>✅ Badge counter updates</li>
                <li>✅ Click handling and navigation</li>
                <li>✅ Timestamp display</li>
            </ul>
            
            <h3>🔧 If It Doesn't Work:</h3>
            <ul>
                <li>Make sure you're on the dashboard page</li>
                <li>Check browser console for errors</li>
                <li>Verify the bell icon is visible in the top right</li>
                <li>Try refreshing the page and running the code again</li>
            </ul>
        </div>
    </div>
</body>
</html>

