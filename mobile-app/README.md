# Mobile Web-Based App System

A Progressive Web App (PWA) built with PHP backend and Supabase database integration. This application demonstrates modern web application techniques and PWA features.

## File Structure

```
/mobile-app/
├── index.php                # Main entry point
├── api/                     # API endpoints
│   ├── auth.php             # Authentication endpoints
│   └── data.php             # Data endpoints
├── config/                  # Configuration
│   └── database.php         # Database configuration with Supabase
├── includes/                # Common PHP classes/functions
│   └── functions.php        # Helper functions
├── assets/                  # Static assets
│   ├── css/
│   │   └── styles.css       # Main stylesheet
│   ├── js/
│   │   └── app.js           # Main JavaScript file
│   └── icons/               # App icons for PWA
├── templates/               # Mobile-optimized views
│   ├── home.php             # Home page
│   └── 404.php              # 404 error page
├── service-worker.js        # Service Worker for PWA features
├── manifest.json            # Web App Manifest for PWA
└── README.md                # Project documentation
```

## Features

- **Progressive Web App (PWA)** capabilities:
  - Installable on home screen
  - Offline functionality
  - App-like experience
  - Push notifications support
  - Background sync
  
- **PHP Backend API**:
  - Authentication endpoints (login, register, logout)
  - Data management endpoints (CRUD operations)
  
- **Supabase Integration**:
  - Database management
  - Authentication services
  
- **Responsive Design**:
  - Mobile-first approach
  - Works on all screen sizes
  
## Installation

1. Clone the repository to your web server directory
2. Configure your Supabase credentials in `config/database.php`
3. Ensure your web server is configured to serve the application from the root

## Supabase Configuration

You need to update the following values in `config/database.php`:

```php
define('SUPABASE_URL', 'YOUR_SUPABASE_URL');
define('SUPABASE_KEY', 'YOUR_SUPABASE_API_KEY');
define('SUPABASE_JWT_SECRET', 'YOUR_SUPABASE_JWT_SECRET');
```

## PWA Assets

To complete the PWA setup, you need to provide:

1. App icons in the `assets/icons/` directory:
   - icon-192x192.png
   - icon-512x512.png
   - badge.png (for notifications)
  
2. Screenshots in the `assets/screenshots/` directory:
   - mobile.png (750x1334)
   - desktop.png (1280x800)

## License

[MIT License](LICENSE)

## Credits

Created by [Your Name] 