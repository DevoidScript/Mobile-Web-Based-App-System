# 🩸 Red Cross Donor Mobile Web App (PWA)

---

## 📖 System Overview

This repository contains the **donor-facing Progressive Web App (PWA)** that complements your separate **Red Cross Blood Donation & Inventory Management System** (admin / staff / hospital side).

- The **main thesis system** (inventory, hospital requests, staff dashboards, forecasting) lives in a different project.
- This project focuses **only on the donor**:
  - Mobile-first dashboard
  - Donation journey and eligibility tracking
  - Parcel-style blood tracking
  - Push notifications for blood drives and urgent blood-type needs

All donor app code lives inside the `mobile-app` folder.

---

## 🧰 Technology Stack

- **Backend:** PHP 7.4+  
- **Database:** Supabase (PostgreSQL + Auth + RLS)  
- **Frontend:** PHP templates, HTML5, CSS3, vanilla JavaScript  
- **PWA:** Service Worker (`service-worker.js`), Web App Manifest (`manifest.json`)  
- **Notifications:** Web Push using VAPID (`config/push.php`, `api/save-subscription.php`)  

---

## 📂 Project Structure (Donor App)

The donor application is self-contained under `mobile-app/`:

```text
d:\Xampp\htdocs\Mobile-Web-Based-App-System
└── mobile-app/
    ├── index.php              # Main entry point (PWA shell / router)
    ├── api/                   # JSON endpoints (Supabase + app logic)
    │   ├── auth.php           # Login / logout / session validation
    │   ├── donor_register.php # Donor registration + account creation
    │   ├── email_verification.php
    │   ├── password-reset.php
    │   ├── get-notifications.php
    │   ├── save-subscription.php
    │   ├── broadcast-blood-drive.php
    │   ├── get-vapid-key.php
    │   └── geocode.php
    ├── config/                # Configuration
    │   ├── database.php       # Supabase URL, keys, helpers
    │   ├── email.php          # SMTP / PHPMailer
    │   └── push.php           # VAPID + Web Push settings
    ├── includes/
    │   └── functions.php      # Shared helpers (auth, responses, trackers, etc.)
    ├── assets/
    │   ├── css/
    │   │   └── styles.css     # Main stylesheet
    │   ├── js/
    │   │   ├── app.js
    │   │   ├── push-notifications.js
    │   │   ├── service-worker-register.js
    │   │   └── browser-compat.js
    │   └── icons/             # PWA icons / branding
    ├── templates/             # Donor-facing pages (PHP)
    │   ├── home.php
    │   ├── login.php
    │   ├── register.php
    │   ├── dashboard.php
    │   ├── profile.php
    │   ├── blood_donation.php
    │   ├── donation_history.php
    │   ├── blood_tracker.php
    │   ├── explore.php
    │   ├── email-verification.php
    │   ├── change-password.php
    │   ├── reset-password.php
    │   ├── admin-send-notification.php
    │   ├── push-notification-prompt.php
    │   ├── 404.php
    │   └── forms/             # Reusable modals (donor form, medical history, etc.)
    ├── sql/
    │   ├── push_notifications_schema.sql
    │   └── reset_sequences.sql
    ├── storage/
    │   └── logs/              # Runtime logs (can be excluded in thesis bundle)
    ├── docs/                  # Donor app docs used for thesis
    ├── vendor/                # Composer dependencies
    ├── service-worker.js
    ├── manifest.json
    ├── composer.json
    ├── composer.lock
    └── README.md              # Donor app–specific README
```

---

## ⚙️ Core Donor Flows

- **Registration & Email Verification**
  - Donors register via `templates/register.php` → `api/donor_register.php`.
  - A 6-digit code is emailed (SMTP + PHPMailer from `config/email.php`).
  - Donors verify using `templates/email-verification.php` + `api/email_verification.php`.

- **Donor Dashboard & History**
  - `templates/dashboard.php` shows a summary (next eligible date, quick actions).
  - `templates/donation_history.php` lists all past donations and the **eligibility countdown**.

- **Parcel-Style Blood Tracking**
  - `templates/blood_tracker.php` and helpers in `includes/functions.php` show the donation journey as stages (Registered → Sample Collected → Testing → Ready for Use).
  - This view reads shared data updated by the staff/admin system (described in your main thesis repo).

- **Push Notifications**
  - Donors can opt-in to browser notifications via `templates/push-notification-prompt.php`.
  - Subscriptions are stored with `api/save-subscription.php` and `sql/push_notifications_schema.sql`.
  - Staff/admin tools (in the main system) can call `api/broadcast-blood-drive.php` to send notifications that open this PWA.

- **PWA / Offline**
  - `service-worker.js` caches key assets, supports offline viewing of core pages, and handles push events and notification clicks.
  - `manifest.json` defines install behavior and app branding.

---

## 🔐 Security (Donor App)

- **Authentication**: PHP sessions + Supabase Auth; donors must be logged in to access dashboard, profile, history, and tracker.
- **Row Level Security**: Enforced in Supabase so donors only see their own records.
- **Validation & Escaping**: Centralized helpers in `includes/functions.php` and output escaping in templates.

---

## 🧪 Local Setup (XAMPP)

1. Place this folder under XAMPP `htdocs`, e.g.  
   `D:\Xampp\htdocs\Mobile-Web-Based-App-System\mobile-app`
2. In `mobile-app/config/database.php`, set:
   - `SUPABASE_URL`
   - `SUPABASE_API_KEY`
   - Any other project-specific constants (JWT secret, schema, etc.).
3. In `mobile-app/config/email.php`, configure:
   - Gmail address and **app password** (not your main password).
   - Sender name and from address.
4. (Optional, for Web Push) In `mobile-app/`:
   - Run `composer install` to restore `vendor/`.
   - Generate VAPID keys and update `config/push.php` and Supabase using `sql/push_notifications_schema.sql`.
5. Access the donor app at:  
   `http://localhost/Mobile-Web-Based-App-System/mobile-app/`

---

## 📦 Thesis Bundle (Donor App Only)

For a **clean thesis code bundle** focused on this donor PWA, you can include from `mobile-app/`:

- **Include:**
  - `index.php`
  - `api/`
  - `config/`
  - `includes/`
  - `assets/`
  - `templates/`
  - `sql/`
  - `docs/`
  - `service-worker.js`
  - `manifest.json`
  - `README.md`

- **Optionally exclude:**
  - `vendor/` (regenerated via `composer install`)
  - Contents of `storage/logs/`

This keeps the README structure close to your main thesis project, while accurately reflecting the actual `mobile-app` layout and keeping everything working as-is.
