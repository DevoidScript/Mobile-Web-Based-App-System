<!-- Push Notification Prompt Component -->
<!-- Include this in dashboard.php or other logged-in pages -->

<style>
.push-prompt {
    position: fixed;
    bottom: 20px;
    right: 20px;
    max-width: 350px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 20px;
    z-index: 1000;
    display: none;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.push-prompt.show {
    display: block;
}

.push-prompt-header {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}

.push-prompt-icon {
    width: 40px;
    height: 40px;
    margin-right: 12px;
    font-size: 24px;
}

.push-prompt-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    color: #333;
}

.push-prompt-body {
    font-size: 14px;
    color: #666;
    margin-bottom: 16px;
    line-height: 1.5;
}

.push-prompt-actions {
    display: flex;
    gap: 10px;
}

.push-prompt-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.push-prompt-btn-primary {
    background: #dc3545;
    color: white;
}

.push-prompt-btn-primary:hover {
    background: #c82333;
}

.push-prompt-btn-secondary {
    background: #f1f1f1;
    color: #666;
}

.push-prompt-btn-secondary:hover {
    background: #e1e1e1;
}

.push-status-indicator {
    position: fixed;
    top: 70px;
    right: 20px;
    padding: 12px 20px;
    background: #28a745;
    color: white;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    display: none;
    z-index: 1001;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.push-status-indicator.show {
    display: block;
}

.push-status-indicator.error {
    background: #dc3545;
}
</style>

<!-- Push Notification Prompt -->
<div id="pushNotificationPrompt" class="push-prompt">
    <div class="push-prompt-header">
        <div class="push-prompt-icon">🔔</div>
        <h3 class="push-prompt-title">Stay Updated</h3>
    </div>
    <div class="push-prompt-body">
        Get notified about blood drives, donation reminders, and important updates.
    </div>
    <div class="push-prompt-actions">
        <button class="push-prompt-btn push-prompt-btn-secondary" onclick="dismissPushPrompt()">
            Not Now
        </button>
        <button class="push-prompt-btn push-prompt-btn-primary" onclick="enablePushNotifications()">
            Enable
        </button>
    </div>
</div>

<!-- Status Indicator -->
<div id="pushStatusIndicator" class="push-status-indicator">
    <span id="pushStatusMessage"></span>
</div>

<script src="../assets/js/push-notifications.js"></script>
<script>
// Push notification initialization
// fetchVapidKey function is now defined in push-notifications.js

// Show push notification prompt
function showPushPrompt() {
    const prompt = document.getElementById('pushNotificationPrompt');
    if (prompt) {
        prompt.classList.add('show');
    }
}

// Dismiss push notification prompt
function dismissPushPrompt() {
    const prompt = document.getElementById('pushNotificationPrompt');
    if (prompt) {
        prompt.classList.remove('show');
    }
    // Save dismissal to localStorage
    localStorage.setItem('pushPromptDismissed', Date.now());
}

// Enable push notifications
async function enablePushNotifications() {
    dismissPushPrompt();
    
    if (!VAPID_PUBLIC_KEY) {
        await fetchVapidKey();
    }
    
    if (!VAPID_PUBLIC_KEY) {
        showPushStatus('Failed to load configuration', true);
        return;
    }
    
    showPushStatus('Requesting permission...', false);
    
    const result = await promptForPushNotifications(VAPID_PUBLIC_KEY);
    
    if (result.success) {
        showPushStatus('✓ Notifications enabled successfully!', false);
        localStorage.setItem('pushNotificationsEnabled', 'true');
    } else {
        if (result.error === 'permission_denied') {
            showPushStatus('Notifications blocked. Enable in browser settings.', true);
        } else if (result.error === 'not_supported') {
            showPushStatus('Push notifications not supported on this browser', true);
        } else {
            showPushStatus('Failed to enable notifications', true);
        }
    }
}

// Show status message
function showPushStatus(message, isError) {
    const indicator = document.getElementById('pushStatusIndicator');
    const messageEl = document.getElementById('pushStatusMessage');
    
    if (indicator && messageEl) {
        messageEl.textContent = message;
        indicator.classList.toggle('error', isError);
        indicator.classList.add('show');
        
        setTimeout(() => {
            indicator.classList.remove('show');
        }, 4000);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', async function() {
    // Fetch VAPID key
    await fetchVapidKey();
    
    // Check if push is supported
    if (!isPushNotificationSupported()) {
        console.log('Push notifications not supported');
        return;
    }
    
    // Check if already enabled
    const alreadyEnabled = localStorage.getItem('pushNotificationsEnabled') === 'true';
    if (alreadyEnabled) {
        // Try to subscribe silently
        const result = await initializePushNotifications(VAPID_PUBLIC_KEY);
        if (result.success) {
            console.log('Push notifications already enabled');
        }
        return;
    }
    
    // Check if prompt was recently dismissed
    const dismissedTime = localStorage.getItem('pushPromptDismissed');
    if (dismissedTime) {
        const daysSinceDismissed = (Date.now() - parseInt(dismissedTime)) / (1000 * 60 * 60 * 24);
        if (daysSinceDismissed < 7) {
            // Don't show prompt again for 7 days
            return;
        }
    }
    
    // Show prompt after a short delay
    setTimeout(() => {
        const permission = getNotificationPermission();
        if (permission === 'default') {
            showPushPrompt();
        }
    }, 3000); // Show after 3 seconds
});
</script>

