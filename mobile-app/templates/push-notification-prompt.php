<!-- Push Notification Prompt Component -->
<!-- Include this in dashboard.php or other logged-in pages -->

<style>
.push-prompt {
    position: fixed;
    top: 110px;
    right: 32px;
    width: 360px;
    max-width: 90vw;
    z-index: 1100;
    background: #ffffff;
    border-radius: 14px;
    border-left: 6px solid #dc3545;
    box-shadow: 0 12px 30px rgba(148,16,34,0.15), 0 2px 10px rgba(0,0,0,0.08);
    padding: 22px 24px 20px 22px;
    display: none;
    animation: slideInRight 0.35s ease forwards;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(60px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.push-prompt.show {
    display: block;
}

.push-prompt-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.push-prompt-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(220,53,69,0.1);
    color: #dc3545;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 2px 6px rgba(220,53,69,0.2);
}

.push-prompt-title {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0;
    color: #941022;
}

.push-prompt-body {
    font-size: 0.95rem;
    color: #4a4a4a;
    line-height: 1.5;
    margin-bottom: 18px;
}

.push-prompt-actions {
    display: flex;
    gap: 12px;
}

.push-prompt-btn {
    flex: 1;
    border: none;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.push-prompt-btn:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(220,53,69,0.25);
}

.push-prompt-btn-primary {
    background: linear-gradient(135deg, #dc3545, #b31828);
    color: #fff;
    box-shadow: 0 6px 16px rgba(220,53,69,0.35);
}

.push-prompt-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(220,53,69,0.35);
}

.push-prompt-btn-secondary {
    background: #fdf2f4;
    color: #b31828;
    border: 1px solid rgba(220,53,69,0.2);
}

.push-prompt-btn-secondary:hover {
    background: #ffeaea;
}

.push-status-indicator {
    position: fixed;
    top: 60px;
    right: 32px;
    padding: 12px 18px;
    background: #28a745;
    color: #fff;
    border-radius: 10px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.18);
    display: none;
    z-index: 1110;
    animation: slideDown 0.25s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.push-status-indicator.show {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.push-status-indicator.error {
    background: #dc3545;
}

#notificationBell.push-bell-attention {
    position: relative;
    animation: pushBellPulse 1.4s infinite;
    color: #ffe9a6 !important;
}

#notificationBell.push-bell-attention::after {
    content: '';
    position: absolute;
    top: -4px;
    right: -6px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #dc3545;
    box-shadow: 0 0 0 2px rgba(255,255,255,0.8);
}

#notificationBell.push-bell-active {
    text-shadow: 0 0 12px rgba(220,53,69,0.7);
}

@keyframes pushBellPulse {
    0% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-2px);
    }
    100% {
        transform: translateY(0);
    }
}
</style>

<!-- Push Notification Prompt -->
<div id="pushNotificationPrompt" class="push-prompt">
    <div class="push-prompt-header">
        <div class="push-prompt-icon">🔔</div>
        <h3 id="pushPromptTitle" class="push-prompt-title">Stay Updated</h3>
    </div>
    <div id="pushPromptBody" class="push-prompt-body">
        Get notified about blood drives, donation reminders, and important updates.
    </div>
    <div class="push-prompt-actions">
        <button class="push-prompt-btn push-prompt-btn-secondary" onclick="dismissPushPrompt()">
            Not Now
        </button>
        <button id="pushPromptPrimaryBtn" class="push-prompt-btn push-prompt-btn-primary" onclick="handlePushPromptPrimaryAction()">
            Enable
        </button>
    </div>
</div>

<!-- Status Indicator -->
<div id="pushStatusIndicator" class="push-status-indicator">
    <span id="pushStatusMessage"></span>
</div>

<script src="../assets/js/push-notifications.js" defer></script>
<script>
// Push notification initialization
// fetchVapidKey function is now defined in push-notifications.js

let latestPromptData = null;
let pushPromptMode = 'push'; // 'push' | 'notification'

function updatePushPromptContent({ title, body, cta }) {
    const titleEl = document.getElementById('pushPromptTitle');
    const bodyEl = document.getElementById('pushPromptBody');
    const primaryBtn = document.getElementById('pushPromptPrimaryBtn');
    if (titleEl) titleEl.textContent = title || 'Stay Updated';
    if (bodyEl) bodyEl.textContent = body || 'Get notified about blood drives, donation reminders, and important updates.';
    if (primaryBtn) primaryBtn.textContent = cta || 'Enable';
}

function resetPushPromptContent() {
    updatePushPromptContent({
        title: 'Stay Updated',
        body: 'Get notified about blood drives, donation reminders, and important updates.',
        cta: 'Enable'
    });
    latestPromptData = null;
    pushPromptMode = 'push';
}

function getNotificationBellElements() {
    return {
        bell: document.getElementById('notificationBell'),
        badge: document.getElementById('notificationBadge')
    };
}

function activateBellPromptState() {
    const { bell } = getNotificationBellElements();
    if (bell) {
        bell.classList.add('push-bell-active');
        bell.classList.remove('push-bell-attention');
    }
}

function markBellAttention() {
    const { bell } = getNotificationBellElements();
    if (bell) {
        bell.classList.add('push-bell-attention');
    }
}

function clearBellPromptState() {
    const { bell } = getNotificationBellElements();
    if (bell) {
        bell.classList.remove('push-bell-active');
        bell.classList.remove('push-bell-attention');
    }
    if (pushPromptMode === 'notification') {
        resetPushPromptContent();
    }
}

// Show push notification prompt
function showPushPrompt() {
    const prompt = document.getElementById('pushNotificationPrompt');
    if (prompt) {
        prompt.classList.add('show');
    }
    activateBellPromptState();
}

// Dismiss push notification prompt
function dismissPushPrompt() {
    const prompt = document.getElementById('pushNotificationPrompt');
    if (prompt) {
        prompt.classList.remove('show');
    }
    clearBellPromptState();
    // Save dismissal to localStorage
    localStorage.setItem('pushPromptDismissed', Date.now());
}

async function handlePushPromptPrimaryAction() {
    if (pushPromptMode === 'notification' && latestPromptData) {
        dismissPushPrompt();
        if (typeof window.stopNotificationPromptCycle === 'function') {
            window.stopNotificationPromptCycle();
        }
        if (typeof window.showNotificationModal === 'function') {
            window.showNotificationModal(latestPromptData);
        }
        return;
    }
    await enablePushNotifications();
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
        const fallbackMessage = () => {
            if (result.error === 'permission_denied') {
                return 'Notifications blocked. Enable in browser settings.';
            }
            if (result.error === 'not_supported') {
                return 'Push notifications not supported on this browser';
            }
            return 'Failed to enable notifications';
        };
        const errorMessage = (typeof describePushError === 'function')
            ? describePushError(result)
            : fallbackMessage();
        showPushStatus(errorMessage, true);
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
    getNotificationBellElements();

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
            markBellAttention();
            showPushPrompt();
        }
    }, 3000); // Show after 3 seconds
});

window.showLatestNotificationPrompt = function(notification) {
    if (!notification) return;
    latestPromptData = notification;
    pushPromptMode = 'notification';
    updatePushPromptContent({
        title: notification.title || 'New Update',
        body: notification.message_template || notification.body || 'Tap to see the latest announcement.',
        cta: notification.url ? 'View' : 'Close'
    });
    showPushPrompt();
};
</script>

