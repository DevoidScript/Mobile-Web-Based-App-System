# Web Push Notifications - Quick Start Guide

## 🚀 Get Started in 5 Minutes

Follow these steps to enable push notifications in your Blood Donation PWA:

---

## Step 1: Install Composer (if not installed)

**Windows (XAMPP users):**
1. Download from: https://getcomposer.org/Composer-Setup.exe
2. Run installer (it will find your PHP automatically)
3. Restart terminal/command prompt

**Mac:**
```bash
brew install composer
```

**Linux:**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## Step 2: Install PHP Dependencies

```bash
cd D:\Xampp\htdocs\Mobile-Web-Based-App-System\mobile-app
composer install
```

This installs the `minishlink/web-push` library needed for sending notifications.

---

## Step 3: Generate VAPID Keys

**Method A (Recommended):**
```bash
cd D:\Xampp\htdocs\Mobile-Web-Based-App-System\mobile-app
vendor/bin/web-push generate:vapid
```

**Method B (Online):**
Visit https://vapidkeys.com/ and generate keys there.

**You'll get output like:**
```
Public Key: BEl62iUYgUivxIkv69yViEuiBIa40HI8U7n7WtqswLwFwalz34a1fYFHvzktJj0p3Xz14pbS_IFVxS0xpaSgwfE
Private Key: p6PQZxW3qP3Z8rL9mN2kJ4hG6fD5sA1zX9cV8bN7mK4
```

---

## Step 4: Update VAPID Keys in Config

Edit `mobile-app/config/push.php`:

Find these lines (around line 47-48):
```php
define('VAPID_PUBLIC_KEY', 'BEl62iUYgUivxIkv69yViEuiBIa40HI8U7n7WtqswLwFwalz34a1fYFHvzktJj0p3Xz14pbS_IFVxS0xpaSgwfE');
define('VAPID_PRIVATE_KEY', 'p256dhkey');
```

Replace with your generated keys:
```php
define('VAPID_PUBLIC_KEY', 'YOUR_GENERATED_PUBLIC_KEY_HERE');
define('VAPID_PRIVATE_KEY', 'YOUR_GENERATED_PRIVATE_KEY_HERE');
```

**Also update the subject (around line 89):**
```php
'subject' => 'mailto:your-email@example.com', // Change to your email
```

---

## Step 5: Create Supabase Tables

1. Open your Supabase dashboard: https://supabase.com/dashboard
2. Select your project: `nwakbxwglhxcpunrzstf`
3. Click **SQL Editor** in the left menu
4. Click **New Query**
5. Open `mobile-app/sql/push_notifications_schema.sql` in your editor
6. Copy ALL the contents
7. Paste into Supabase SQL Editor
8. Click **Run** or press `Ctrl+Enter`

You should see: "Success. No rows returned"

This creates:
- `push_subscriptions` table
- `donor_notifications` table
- Indexes
- RLS policies

---

## Step 6: Test the Implementation

### A. Test Client Subscription

1. **Start XAMPP** (Apache and MySQL running)

2. **Open your app:**
   ```
   http://localhost/Mobile-Web-Based-App-System/mobile-app/
   ```

3. **Login** with your donor account

4. **Wait 3 seconds** - a notification prompt should appear

5. **Click "Enable"** - browser will ask for permission

6. **Click "Allow"**

7. **Check browser console** (F12):
   ```
   ServiceWorker registration successful
   Push subscription saved to backend
   ```

8. **Verify in Supabase:**
   - Go to Table Editor → `push_subscriptions`
   - You should see a new row with your donor_id and subscription details

### B. Send a Test Notification

1. **Open the test page:**
   ```
   http://localhost/Mobile-Web-Based-App-System/mobile-app/test-push.php
   ```

2. **Fill out the form:**
   - Title: "Test Blood Drive"
   - Body: "This is a test notification!"
   - Leave other fields as default

3. **Click "Send Test Push Notification"**

4. **You should see:**
   - A notification pop up on your screen (OS-level notification)
   - Success message on the test page showing "Sent: 1"

5. **Click the notification:**
   - It should open/focus your PWA dashboard

---

## ✅ Success Checklist

After completing all steps, verify:

- [ ] Composer installed and working
- [ ] `vendor/` folder exists in `mobile-app/`
- [ ] VAPID keys updated in `config/push.php`
- [ ] Supabase tables created (`push_subscriptions`, `donor_notifications`)
- [ ] Service worker registered (check browser console)
- [ ] Notification permission granted
- [ ] Subscription saved in Supabase
- [ ] Test notification received and displayed
- [ ] Clicking notification opens the app

---

## 🐛 Common Issues

### "composer: command not found"
**Fix:** Install Composer (see Step 1)

### "Class 'Minishlink\WebPush\WebPush' not found"
**Fix:** Run `composer install` in the mobile-app directory

### "Push notifications not supported"
**Fix:** 
- Use Chrome, Edge, Firefox, or Safari 16.4+
- Must be on `http://localhost` or HTTPS
- For iOS: Install PWA via "Add to Home Screen"

### "No subscriptions found"
**Fix:**
- Make sure you enabled notifications in the dashboard
- Check browser console for errors
- Verify service worker is active

### "Notification permission blocked"
**Fix:**
- Click the lock icon in address bar
- Reset permissions for notifications
- Reload the page and try again

---

## 🎯 What's Next?

Once testing is successful:

1. **Production Setup:**
   - Move VAPID keys to environment variables
   - Set up HTTPS for your domain
   - Test on real devices (especially iOS)

2. **Integration:**
   - Add broadcast button to admin panel
   - Create blood drive notification system
   - Add donor notification preferences

3. **Enhancements:**
   - Filter by blood type
   - Location-based targeting (PostGIS)
   - In-app notification inbox
   - Scheduled notifications

---

## 📚 Full Documentation

For detailed information, see:
- **`PUSH-NOTIFICATIONS-SETUP.md`** - Complete setup guide
- **`PUSH-NOTIFICATIONS-SUMMARY.md`** - Implementation overview

---

## 🎉 You're Ready!

If you've completed all steps successfully, your PWA now supports push notifications!

**Test it out:**
1. Close the browser tab completely
2. Send a test notification from `test-push.php`
3. You should still receive the notification!

This works because the service worker runs independently of open tabs.

---

**Need Help?**
- Check browser console (F12) for errors
- Review the full setup guide: `PUSH-NOTIFICATIONS-SETUP.md`
- Verify each step in the checklist above

