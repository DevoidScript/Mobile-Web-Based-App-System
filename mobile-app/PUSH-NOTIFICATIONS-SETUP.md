# Web Push Notifications Setup Guide

This guide covers the complete setup and testing of Web Push notifications for the Blood Donation PWA.

## Overview

Web Push notifications allow you to send timely updates to donors even when they're not actively using your app. The system includes:

- **Client-side**: Permission request, subscription management, and notification display
- **Server-side**: Subscription storage, targeting, and push sending via VAPID
- **Database**: Supabase tables for subscriptions and notification logs

---

## Prerequisites

### 1. HTTPS Required
- **Development**: `http://localhost` works fine
- **Production** or **LAN testing**: Requires HTTPS
  - Use `mkcert` for local SSL certificates
  - Or use ngrok/Cloudflare Tunnel for testing

### 2. Install Composer Dependencies
```bash
cd mobile-app
composer install
```

This installs `minishlink/web-push` library for sending push notifications.

### 3. Create Supabase Tables

Run this SQL in your Supabase SQL Editor:

```sql
-- Run the entire contents of mobile-app/sql/push_notifications_schema.sql
```

Or manually run:
```bash
# In Supabase dashboard → SQL Editor → New Query
# Copy and paste the contents of sql/push_notifications_schema.sql
```

---

## Configuration

### 1. Generate VAPID Keys

VAPID keys authenticate your server to push services. You need to generate a unique key pair.

**Option A: Using web-push library (after composer install)**
```bash
cd mobile-app
./vendor/bin/web-push generate:vapid --output json
```

**Option B: Using OpenSSL**
```bash
# Generate private key
openssl ecparam -name prime256v1 -genkey -noout -out vapid_private.pem

# Extract public key
openssl ec -in vapid_private.pem -pubout -out vapid_public.pem

# Convert to base64url format (manual step required)
```

**Option C: Online Generator**
Visit: https://web-push-codelab.glitch.me/ or https://vapidkeys.com/

### 2. Update config/push.php

Replace the placeholder keys in `mobile-app/config/push.php`:

```php
define('VAPID_PUBLIC_KEY', 'YOUR_PUBLIC_KEY_HERE');
define('VAPID_PRIVATE_KEY', 'YOUR_PRIVATE_KEY_HERE');
```

Also update the subject (email or URL):
```php
'subject' => 'mailto:your-email@example.com',
```

### 3. Environment Variables (Production)

For production, use environment variables instead of hardcoding keys:

```bash
export VAPID_PUBLIC_KEY="your_public_key"
export VAPID_PRIVATE_KEY="your_private_key"
export VAPID_SUBJECT="mailto:admin@example.com"
```

---

## Files Created

### Backend (PHP)
- `config/push.php` - VAPID configuration and Web Push sender
- `api/save-subscription.php` - Saves donor push subscriptions
- `api/broadcast-blood-drive.php` - Sends push to target donors
- `api/get-vapid-key.php` - Returns public VAPID key to client
- `sql/push_notifications_schema.sql` - Database schema

### Frontend (JavaScript)
- `assets/js/push-notifications.js` - Client push subscription logic
- `templates/push-notification-prompt.php` - UI prompt for notifications
- `service-worker.js` - Updated with enhanced push handlers

### Database Tables
- `push_subscriptions` - Stores donor push subscriptions (endpoint, keys)
- `donor_notifications` - Logs all sent notifications

---

## How It Works

### 1. User Flow (Donor Side)

1. **Login**: Donor logs into the dashboard
2. **Prompt**: After 3 seconds, a notification prompt appears
3. **Allow**: Donor clicks "Enable" → browser permission dialog
4. **Subscribe**: On approval, client subscribes to push via service worker
5. **Save**: Subscription JSON sent to `api/save-subscription.php`
6. **Stored**: Backend saves `endpoint`, `p256dh`, `auth` to Supabase

### 2. Admin Flow (Broadcast)

1. **Create notification**: Admin prepares a blood drive notification
2. **Target**: Optionally filter by blood type, location, specific donors
3. **Send**: POST to `api/broadcast-blood-drive.php` with payload
4. **Fetch subscriptions**: Backend queries `push_subscriptions` table
5. **Send push**: For each subscription, use Web Push library + VAPID keys
6. **Log**: Record delivery status in `donor_notifications` table
7. **Cleanup**: Delete stale subscriptions (404/410 errors)

### 3. Notification Display

1. **Push arrives**: Browser wakes the service worker
2. **`push` event**: Service worker receives payload
3. **Show notification**: `showNotification()` displays OS-level alert
4. **Click**: User clicks → `notificationclick` → opens app URL

---

## Testing the Integration

### Step 1: Install Dependencies
```bash
cd mobile-app
composer install
```

### Step 2: Run Supabase Schema
- Open Supabase dashboard → SQL Editor
- Paste contents of `sql/push_notifications_schema.sql`
- Run the query

### Step 3: Update VAPID Keys
- Generate keys (see Configuration section)
- Update `config/push.php` with your keys

### Step 4: Test Client Subscription

1. **Start your server**:
   ```bash
   # XAMPP should be running
   # Open http://localhost/mobile-app/
   ```

2. **Login** to the dashboard

3. **Check browser console**:
   - Should see: "ServiceWorker registration successful"
   - After 3 seconds: Notification prompt appears

4. **Click "Enable"**:
   - Browser permission dialog appears
   - Click "Allow"
   - Console should show: "Push subscription saved to backend"

5. **Verify in Supabase**:
   - Go to Supabase → Table Editor → `push_subscriptions`
   - Should see a new row with your `donor_id`, `endpoint`, `p256dh`, `auth`

### Step 5: Test Sending a Push

**Option A: Using curl/Postman**

```bash
# Test broadcast endpoint
curl -X POST http://localhost/mobile-app/api/broadcast-blood-drive.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{
    "title": "Test Blood Drive",
    "body": "This is a test notification!",
    "url": "/mobile-app/templates/dashboard.php",
    "icon": "/mobile-app/assets/icons/icon-192x192.png"
  }'
```

**Option B: Create a test page**

Create `mobile-app/test-push.php`:

```php
<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

session_start();

// Simple test form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ch = curl_init('http://localhost/mobile-app/api/broadcast-blood-drive.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($_POST));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Cookie: ' . $_SERVER['HTTP_COOKIE']
    ]);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    
    echo '<pre>' . print_r($result, true) . '</pre>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Push Notification</title>
</head>
<body>
    <h1>Test Push Notification</h1>
    <form method="POST">
        <label>Title: <input type="text" name="title" value="Blood Drive Alert" required></label><br>
        <label>Body: <textarea name="body" required>A blood drive is happening near you!</textarea></label><br>
        <label>URL: <input type="text" name="url" value="/mobile-app/templates/dashboard.php"></label><br>
        <button type="submit">Send Test Push</button>
    </form>
</body>
</html>
```

Visit `http://localhost/mobile-app/test-push.php` and click "Send Test Push".

### Step 6: Verify Notification

1. **Check browser**: You should see the OS notification pop up
2. **Click notification**: Should open/focus the app at the specified URL
3. **Check Supabase**: `donor_notifications` table should have a log entry

---

## Common Issues and Solutions

### Issue: "Composer not found"
**Solution**: Install Composer from https://getcomposer.org/download/
- Windows: Download installer
- Mac/Linux: `brew install composer`

### Issue: "Push notifications not supported"
**Solution**: 
- Check browser compatibility (Chrome, Edge, Firefox, Safari 16.4+)
- Ensure HTTPS (or localhost)
- iOS: App must be installed (Add to Home Screen)

### Issue: "Permission denied"
**Solution**:
- User blocked notifications
- Guide user to browser settings to re-enable
- Provide fallback (in-app inbox, email)

### Issue: "Failed to save subscription"
**Solution**:
- Check user is logged in (`is_logged_in()`)
- Verify `donor_id` in session
- Check Supabase RLS policies allow insert

### Issue: "Notification not received"
**Solution**:
- Check browser console for errors
- Verify VAPID keys match (public on client, private on server)
- Confirm subscription exists in database
- Check service worker is active (`chrome://serviceworker-internals`)

### Issue: "410 Gone" error when sending
**Solution**:
- Subscription expired or revoked
- Broadcast endpoint automatically deletes stale subscriptions
- User needs to re-subscribe

---

## Platform-Specific Notes

### Desktop Chrome/Edge/Firefox
- Works in browser and installed PWA
- Notifications show even when browser closed (if PWA installed)

### Android Chrome
- Works in browser and installed PWA
- Notifications wake device

### iOS Safari (16.4+)
- **Requires PWA installation** (Add to Home Screen)
- Won't work in regular Safari browser
- Test by installing PWA first

### Windows/Mac PWA
- Full support when installed
- Notifications integrated with OS

---

## Security Best Practices

1. **Protect VAPID private key**: Never expose to client, use env vars in production
2. **Validate sessions**: All API endpoints check `is_logged_in()`
3. **RLS policies**: Donors can only see/delete their own subscriptions
4. **Use service role**: Broadcast endpoint uses service_role key to bypass RLS
5. **Rate limiting**: Add rate limits to prevent abuse (future enhancement)
6. **Input validation**: Sanitize all notification content

---

## Future Enhancements

- [ ] **Location-based targeting**: Use PostGIS to target donors by radius
- [ ] **Blood type filtering**: Send only to compatible blood types
- [ ] **Notification preferences**: Let donors choose notification types
- [ ] **In-app inbox**: Fallback for browsers without push support
- [ ] **Scheduled notifications**: Cron job for automated campaigns
- [ ] **Rich notifications**: Action buttons (RSVP, Dismiss, etc.)
- [ ] **Analytics**: Track open rates, click-through, etc.

---

## API Reference

### POST `/mobile-app/api/save-subscription.php`

Saves or updates a donor's push subscription.

**Request:**
```json
{
  "subscription": {
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "keys": {
      "p256dh": "BNcRdrei...",
      "auth": "tBHItJI..."
    },
    "expirationTime": null
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Push subscription saved successfully.",
  "data": {
    "subscription_id": "uuid-here"
  }
}
```

### POST `/mobile-app/api/broadcast-blood-drive.php`

Sends push notifications to target donors.

**Request:**
```json
{
  "title": "Blood Drive Tomorrow",
  "body": "Join us at City Hospital, 9 AM - 5 PM",
  "url": "/mobile-app/templates/blood-drive-detail.php?id=123",
  "icon": "/mobile-app/assets/icons/icon-192x192.png",
  "blood_drive_id": 123,
  "donor_ids": [1, 2, 3]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Broadcast completed.",
  "data": {
    "sent": 15,
    "failed": 2,
    "total": 17,
    "payload": { ... }
  }
}
```

### GET `/mobile-app/api/get-vapid-key.php`

Returns the public VAPID key for client subscription.

**Response:**
```json
{
  "success": true,
  "publicKey": "BEl62iUYgUivxIkv69yViEuiBIa..."
}
```

---

## Support

If you encounter issues:

1. Check browser console for JavaScript errors
2. Check PHP error logs for server errors
3. Verify Supabase connection and tables exist
4. Ensure VAPID keys are correctly configured
5. Test with `test-push.php` page

For iOS testing, remember: PWA must be installed via "Add to Home Screen" first.

---

**Created:** $(date)
**Version:** 1.0
**Status:** Ready for testing

