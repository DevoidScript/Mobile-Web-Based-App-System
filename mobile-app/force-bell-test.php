<?php
/**
 * Force bell panel test - this will definitely work
 */

echo "=== FORCE BELL PANEL TEST ===\n\n";

echo "This will add notifications directly to localStorage so you can see them immediately.\n\n";

echo "FOLLOW THESE STEPS EXACTLY:\n\n";

echo "1. Open your dashboard: http://localhost/mobile-app/templates/dashboard.php\n\n";

echo "2. Press F12 to open developer tools\n\n";

echo "3. Click on the 'Console' tab\n\n";

echo "4. Copy and paste this EXACT code:\n\n";

$js_code = '
// FORCE ADD NOTIFICATIONS TO BELL PANEL
console.log("Starting bell panel test...");

// Clear any existing notifications
localStorage.removeItem("appNotifications");

// Create test notifications
const notifications = [
    {
        id: "test-1",
        title: "Blood Drive Alert!",
        body: "New blood drive at Community Center this Saturday",
        url: "/mobile-app/templates/dashboard.php",
        timestamp: Date.now() - 3600000
    },
    {
        id: "test-2", 
        title: "Donation Reminder",
        body: "You are eligible to donate again. Book now!",
        url: "/mobile-app/templates/dashboard.php",
        timestamp: Date.now() - 1800000
    },
    {
        id: "test-3",
        title: "Urgent: O+ Blood Needed",
        body: "Emergency shortage. Please donate if you can.",
        url: "/mobile-app/templates/dashboard.php", 
        timestamp: Date.now() - 300000
    }
];

// Add to localStorage
localStorage.setItem("appNotifications", JSON.stringify(notifications));
console.log("✅ Notifications added to localStorage");

// Update bell badge
const badge = document.getElementById("notificationBadge");
if (badge) {
    badge.textContent = notifications.length;
    badge.style.display = "flex";
    console.log("✅ Bell badge updated with", notifications.length, "notifications");
} else {
    console.log("❌ Bell badge element not found - check if you are on dashboard page");
}

// Try to refresh notification panel
if (typeof loadNotifications === "function") {
    loadNotifications();
    console.log("✅ Notification panel refreshed");
}

// Show success message
alert("🎉 Test notifications added! Check the bell icon (🔔) in the top right corner.");

console.log("🎉 Bell panel test complete!");
console.log("📊 Total notifications:", notifications.length);
console.log("👆 Click the bell icon to see the notifications");
';

echo $js_code . "\n\n";

echo "5. Press Enter to run the code\n\n";

echo "6. You should IMMEDIATELY see:\n";
echo "   ✅ Bell icon shows red badge with '3'\n";
echo "   ✅ Click bell → panel opens with 3 notifications\n";
echo "   ✅ Each notification shows title, body, and time\n\n";

echo "7. If you still don't see the bell badge:\n";
echo "   - Make sure you are on the dashboard page\n";
echo "   - Check if the bell icon is visible in the top right\n";
echo "   - Look for any errors in the console\n";
echo "   - Try refreshing the page and running the code again\n\n";

echo "=== TROUBLESHOOTING ===\n\n";

echo "If the bell icon is not visible:\n";
echo "1. Make sure you are on: http://localhost/mobile-app/templates/dashboard.php\n";
echo "2. Look for the bell icon (🔔) in the top right corner of the page\n";
echo "3. If you don't see it, the bell icon might not be rendered properly\n\n";

echo "If the bell icon is visible but no badge appears:\n";
echo "1. Check browser console for errors\n";
echo "2. Make sure the JavaScript code ran without errors\n";
echo "3. Try refreshing the page and running the code again\n\n";

echo "If you see the badge but clicking doesn't work:\n";
echo "1. Check if the notification panel HTML is present\n";
echo "2. Look for JavaScript errors in console\n";
echo "3. Make sure the click handler is attached\n\n";

echo "This test bypasses the database and push notification system entirely.\n";
echo "It directly adds notifications to localStorage where the bell panel reads them.\n";
echo "If this doesn't work, there's an issue with the bell panel JavaScript itself.\n";
?>

