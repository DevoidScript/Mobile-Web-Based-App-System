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

> **Important:** There is no longer an `MD files` folder; all markdown documentation is grouped under `mobile-app/docs/`.

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

# 🩸 Red Cross Donor Mobile Web App (PWA)

This repository contains the **donor-facing Progressive Web App** that works alongside your separate **Red Cross Blood Donation & Inventory Management System** (admin / staff / hospital side).

- The **main thesis “big” system** (inventory, forecasting, hospital requests, staff roles) lives in its own GitHub repo you showed in your description.
- This repo is **only the donor side**: a mobile-first PWA where donors register, track donations, and receive notifications.

---

## Repository layout (high level)

```text
Mobile-Web-Based-App-System/
├── mobile-app/        # Donor PWA (all PHP, JS, CSS, docs for this app)
└── .git / README.md   # Git metadata + this top-level description
```

All of the actual donor application code and thesis-relevant implementation details are inside **`mobile-app/`**.

For a **detailed file-by-file structure** of the donor app (matching the real folders and files), see:

- `mobile-app/README.md`

That README documents:

- `api/`, `config/`, `includes/`, `assets/`, `templates/`, `sql/`, `storage/`, `docs/`, `vendor/`
- How the donor registration, email verification, blood tracking, push notifications, and PWA behavior are implemented.

---

## Thesis notes

When the technical editor reviews this repo:

- Treat the **other GitHub repo** you pasted as the **Admin / Inventory / Hospital system**.
- Treat this repo’s **`mobile-app/`** as the **Donor PWA** that connects to the same Supabase backend.
- For a **clean donor-side bundle** in your thesis appendices, you can:
  - Include from `mobile-app/`: `index.php`, `api/`, `config/`, `includes/`, `assets/`, `templates/`, `sql/`, `docs/`, `service-worker.js`, `manifest.json`, `README.md`
  - Exclude from `mobile-app/`: `vendor/` and `storage/logs/` (they can be regenerated or created at runtime).

No runtime code or directory layout inside `mobile-app/` has been changed; only documentation has been updated to help the technical editor understand how this donor PWA fits into your overall thesis system.

# 🩸 **Red Cross Donor Mobile Web App (PWA)**

---

## 📖 **System Overview**

This repository contains the **donor-facing Progressive Web App (PWA)** that complements the main *Red Cross Blood Donation & Inventory Management System*.

Where the main system focuses on **admin, staff, and hospital workflows** (inventory, forecasting, hospital requests), this project focuses on the **individual blood donor**:

- Mobile-first donor dashboard  
- Donation journey and eligibility tracking  
- Parcel-style blood tracking  
- Push notifications for blood drives and urgent blood-type needs  

The donor app is implemented as a standalone PHP + Supabase PWA inside the `mobile-app/` directory.

---

## 🧱 **Architecture**

- **Application type**: Mobile-first PWA, installable on Android/desktop (Chrome/Edge/Opera).
- **Backend**: PHP 7.4+ talking to **Supabase** (PostgreSQL + Auth + RLS).
- **Frontend**: PHP templates rendered server-side with responsive HTML/CSS/JS.
- **State & data**:
  - Donor data, eligibility, and donation history retrieved via Supabase REST.
  - Real-time donation status synchronized from the staff system via shared tables and cron/API jobs (documented in `mobile-app/docs/`).
- **PWA features**:
  - `service-worker.js` for offline cache, background sync, and push handling.
  - `manifest.json` for install metadata (name, icons, theme color, scope).

There is **one primary role** for this app: the **donor user**, authenticated via Supabase and PHP sessions.

---

## 🗂️ **Directory Structure (Donor App)**

The donor PWA lives entirely under `mobile-app/`:

```text
/mobile-app/
├── index.php                  # Main entry point (PWA shell / router)
├── api/                       # JSON endpoints (Supabase + app logic)
│   ├── auth.php               # Login, logout, session validation
│   ├── donor_register.php     # Donor registration + account creation
│   ├── email_verification.php # Handle 6‑digit email verification workflow
│   ├── password-reset.php     # Password reset (code + update)
│   ├── get-notifications.php  # In‑app notification feed
│   ├── save-subscription.php  # Store Web Push subscriptions
│   ├── broadcast-blood-drive.php
│   ├── get-vapid-key.php      # Expose public VAPID key for JS
│   ├── geocode.php            # Geocoding helper for addresses/locations
│   └── ...                    # Other feature‑specific endpoints
├── config/                    # Application configuration
│   ├── database.php           # Supabase URL, API key, helper wrappers
│   ├── email.php              # SMTP / PHPMailer config and templates
│   └── push.php               # VAPID keys + Web Push settings
├── includes/
│   └── functions.php          # Shared helpers (auth, responses, trackers, utilities)
├── assets/                    # Static assets
│   ├── css/
│   │   └── styles.css         # Main stylesheet (mobile-first UI)
│   ├── js/
│   │   ├── app.js             # Core UI, dashboard widgets, tracker logic
│   │   ├── push-notifications.js
│   │   ├── service-worker-register.js
│   │   └── browser-compat.js  # Feature detection & SW registration
│   └── icons/                 # PWA icons / branding
├── templates/                 # Mobile‑optimized views (PHP)
│   ├── home.php               # Public landing / marketing page
│   ├── login.php              # Donor login
│   ├── register.php           # Multi-step donor registration
│   ├── dashboard.php          # Main donor dashboard (KPIs + shortcuts)
│   ├── profile.php            # Donor profile, stats, and settings
│   ├── blood_donation.php     # Donation initiation + forms entry point
│   ├── donation_history.php   # Full donation history + eligibility countdown
│   ├── blood_tracker.php      # Parcel‑style real‑time donation tracker
│   ├── explore.php            # Explore / locations / informational resources
│   ├── email-verification.php # 6‑digit email verification page
│   ├── change-password.php
│   ├── reset-password.php
│   ├── admin-send-notification.php  # Internal testing/admin UI for push
│   ├── push-notification-prompt.php # Reusable notification opt‑in component
│   ├── 404.php
│   └── forms/                 # Reusable modals (donor form, medical history, etc.)
├── sql/
│   ├── push_notifications_schema.sql  # Tables + RLS for Web Push
│   └── reset_sequences.sql            # Utility SQL for sequence repair
├── storage/
│   └── logs/                        # Runtime logs (not needed in thesis bundle)
├── docs/                            # Internal / thesis documentation
│   ├── dashboard-documentation.html # Full donor dashboard/documentation export
│   ├── MOBILE-APP-SUMMARY.md
│   ├── PERFORMANCE-OPTIMIZATIONS.md
│   ├── BROWSER-COMPATIBILITY.md
│   ├── README-TRACKER.md            # Blood tracking feature doc
│   ├── QUICK-START-PUSH.md
│   ├── README-PUSH-NOTIFICATIONS.md
│   ├── PUSH-NOTIFICATIONS-SETUP.md
│   ├── IMPLEMENTATION-CHECKLIST.md
│   ├── PUSH-NOTIFICATIONS-SUMMARY.md
│   ├── EMAIL-VERIFICATION-SETUP.md
│   └── REAL-TIME-SETUP.md           # Real-time sync with staff system
├── vendor/                          # Composer dependencies (minishlink/web-push, PHPMailer, etc.)
├── service-worker.js                # Offline cache, background sync, push handler
├── manifest.json                    # Web App Manifest (name, icons, theme, scope)
├── composer.json                    # PHP dependencies
├── composer.lock
└── README.md                        # Donor PWA documentation (this file)
```

---

## 🧬 **Core Donor-Side Functions**

### 1. Donor Registration & Email Verification
- Multi-step registration form (`templates/register.php`) writes into donor-related tables via `api/donor_register.php`.
- On successful registration:
  - A **6-digit verification code** is generated and stored (see `EMAIL-VERIFICATION-SETUP.md`).
  - An email is sent using **Gmail SMTP + PHPMailer** (`config/email.php`).
  - The donor is redirected to `templates/email-verification.php` to enter the code.
- `api/email_verification.php` validates the code, updates email verification flags, and unlocks normal login.

### 2. Donor Dashboard & Journey
- `templates/dashboard.php` shows:
  - Welcome message and quick stats.
  - **Eligibility countdown** (days until next donation allowed).
  - Shortcuts to **Donate Blood**, **Donation History**, and **Blood Tracker**.
- `templates/blood_donation.php` orchestrates the donation journey by linking to:
  - Donor forms (personal information, medical history).
  - Declarations/consent and post-donation information.
- `templates/donation_history.php` displays:
  - All past donations, their final status (stored/used/expired/etc.).
  - The calculation and countdown for the **next eligible donation date**.

### 3. Parcel-Style Blood Tracking
- `templates/blood_tracker.php` and helpers in `includes/functions.php` present a **parcel-tracking style view** of a donor’s current donation:
  - Stages such as *Registered → Sample Collected → Screening → Testing → Processed → Ready for Use*.
  - Progress percentages and descriptive labels.
- The donor app reads from shared tables updated by the staff/administrative system; more details are in `docs/README-TRACKER.md` and `docs/REAL-TIME-SETUP.md`.

### 4. Web Push Notifications (Donor Engagement)
- Client-side logic in `assets/js/push-notifications.js` + `service-worker.js`:
  - Prompts donors to enable notifications via `templates/push-notification-prompt.php`.
  - Subscribes browsers using the VAPID public key from `api/get-vapid-key.php`.
  - Saves subscriptions to Supabase via `api/save-subscription.php` and `sql/push_notifications_schema.sql`.
- Server-side broadcast:
  - `api/broadcast-blood-drive.php` uses `config/push.php` and **minishlink/web-push** to send Web Push messages.
  - `service-worker.js` displays OS-level notifications and deep-links into `/mobile-app/templates/dashboard.php` or other relevant pages.
  - The behavior and schema are documented in `docs/README-PUSH-NOTIFICATIONS.md` and related files.

### 5. Offline Support & Background Sync
- `service-worker.js` implements:
  - **Install / activate**: caches key assets (CSS, JS, icons, shell pages) with versioned cache keys.
  - **Network-first for HTML** to avoid stale sessions; **cache-first for static assets** for performance.
  - Background sync for queued form submissions (offline-friendly behavior, described in docs).

---

## 🔐 **Security & Data Protection (Donor App)**

- **Authentication**:
  - PHP sessions plus Supabase Auth tokens verified in `api/auth.php` and helper functions.
  - Donor-only access to dashboard, profile, history, tracker, and notifications.
- **Row Level Security (RLS)**:
  - Enforced at Supabase for donor tables so donors can only see their own records.
- **Input/Output Handling**:
  - Centralized helper functions (e.g., safe JSON responses, validation) in `includes/functions.php`.
  - Output escaping in templates to prevent XSS.

For deeper security and workflow details, see the markdown files in `mobile-app/docs/`, which are written to support the thesis’ technical documentation requirements.

---

## 🧪 **Local Setup (XAMPP)**

1. Place this project under XAMPP `htdocs`, for example:  
   `D:\Xampp\htdocs\Mobile-Web-Based-App-System\mobile-app`
2. In `mobile-app/config/database.php`, set:
   - `SUPABASE_URL`
   - `SUPABASE_API_KEY`
   - Any other project-specific constants (JWT secret, schema, etc.).
3. In `mobile-app/config/email.php`, configure:
   - Gmail address and **app password** (not the main password).
   - Sender name and from address.
4. (Optional, for Web Push) In `mobile-app/`:
   - Run `composer install` to restore `vendor/`.
   - Generate VAPID keys and update `config/push.php` and Supabase schema as described in `docs/QUICK-START-PUSH.md` and `docs/PUSH-NOTIFICATIONS-SETUP.md`.
5. Access the donor app at:  
   `http://localhost/Mobile-Web-Based-App-System/mobile-app/`

---

## 📦 **Thesis Submission: Clean Donor App Bundle**

For a **clean thesis code bundle** focused on the donor PWA, you can include from `mobile-app/`:

- **Include (core logic):**
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

- **Optionally exclude (regenerable or runtime data):**
  - `vendor/` (can be regenerated via `composer install`)
  - `storage/logs/` contents

This keeps the thesis folder professional and focused on the actual donor-side implementation while preserving all behavior described above.

# Mobile Web-Based App System

A mobile-first **Progressive Web App (PWA)** for blood donation management, built with a PHP backend and **Supabase** (PostgreSQL + Auth) on the data layer.

## Tech stack

- **Frontend**: PHP templates (`templates/*`), HTML5, CSS3, vanilla JavaScript
- **PWA**: `service-worker.js`, `manifest.json`, responsive layout, install prompt
- **Backend**: PHP (`api/*`, `includes/functions.php`, `config/*`)
- **Data**: Supabase REST + PostgreSQL
- **Notifications**: Web Push (VAPID, `config/push.php`, `api/save-subscription.php`)

## Directory structure (high level)

```text
/mobile-app/
├── index.php                  # Main entry point (PWA shell / router)
├── api/                       # JSON endpoints (Supabase + app logic)
│   ├── auth.php               # Login, register, session handling
│   ├── donor_register.php     # Donor registration + email verification
│   ├── email_verification.php # Verify 6‑digit email codes
│   ├── get-notifications.php  # In‑app notification feed
│   ├── save-subscription.php  # Store Web Push subscriptions
│   ├── broadcast-blood-drive.php
│   ├── geocode.php            # Geocoding helper
│   └── ...                    # Other feature‑specific endpoints
├── config/                    # Application configuration
│   ├── database.php           # Supabase URL/API key, helpers
│   ├── email.php              # SMTP / PHPMailer config
│   └── push.php               # VAPID keys + Web Push settings
├── includes/
│   └── functions.php          # Shared helpers (auth, responses, trackers, etc.)
├── assets/                    # Static assets
│   ├── css/
│   │   └── styles.css         # Main stylesheet
│   ├── js/
│   │   ├── app.js             # Core UI and dashboard logic
│   │   ├── push-notifications.js
│   │   ├── service-worker-register.js
│   │   └── browser-compat.js  # Feature detection & SW registration
│   └── icons/                 # PWA icons / branding
├── templates/                 # Mobile‑optimized views
│   ├── home.php               # Landing / home
│   ├── login.php              # Authentication
│   ├── register.php
│   ├── dashboard.php          # Main donor dashboard
│   ├── profile.php            # Donor profile & stats
│   ├── blood_donation.php     # Donation flow
│   ├── donation_history.php   # Past donations + eligibility
│   ├── blood_tracker.php      # Parcel‑style tracker
│   ├── explore.php            # Explore / locations
│   ├── email-verification.php # 6‑digit code input
│   ├── admin-send-notification.php
│   ├── 404.php
│   └── forms/                 # Reusable modals (donor form, medical history, …)
├── sql/
│   ├── push_notifications_schema.sql  # Tables + RLS for Web Push
│   └── reset_sequences.sql            # Utility SQL
├── storage/
│   └── logs/                 # Runtime logs (kept out of thesis bundles)
├── docs/                     # Internal / thesis documentation (markdown + HTML)
├── vendor/                   # Composer dependencies (regenerate via composer)
├── service-worker.js         # Offline cache, background sync, push handler
├── manifest.json             # Web App Manifest (name, icons, theme, scope)
├── composer.json             # PHP dependencies (minishlink/web-push, etc.)
├── composer.lock
└── README.md                 # This file
```

## Key features

- **PWA experience**: installable, offline support, background sync, push notifications
- **Donor journey**: registration, medical history, eligibility countdown, donation history
- **Real‑time tracking**: parcel‑style donation tracker synchronized with external system
- **Notifications**: Web Push for blood drives and urgent blood‑type requests
- **Email verification**: 6‑digit code via Gmail SMTP / PHPMailer

## Local setup (XAMPP)

1. Place the project under your XAMPP `htdocs` (for example `D:\Xampp\htdocs\Mobile-Web-Based-App-System\mobile-app`).
2. In `config/database.php`, set **`SUPABASE_URL`**, **`SUPABASE_API_KEY`**, and related constants to your project values.
3. In `config/email.php`, configure your Gmail SMTP **app password** and sender details.
4. (Optional, for push) Run `composer install` in `mobile-app/`, generate VAPID keys, and update `config/push.php` and `sql/push_notifications_schema.sql` in Supabase.
5. Visit `http://localhost/Mobile-Web-Based-App-System/mobile-app/` in a Chromium‑based browser to use the app and install the PWA.

For thesis review, a “clean” code bundle can include `index.php`, `api/`, `config/`, `includes/`, `assets/`, `templates/`, `sql/`, and `docs/`, while excluding `vendor/` and `storage/logs/` (they can be regenerated or created at runtime). 