<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4285f4">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="Page not found - A Progressive Web App built with PHP and Supabase">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
</head>
<body>
    <header>
        <div class="container">
            <h1>Mobile App</h1>
            <nav>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/#features">Features</a></li>
                    <li><a href="/#about">About</a></li>
                    <?php if (!is_logged_in()): ?>
                        <li><a href="/#login" class="login-btn">Login</a></li>
                    <?php else: ?>
                        <li><a href="/#dashboard" class="dashboard-btn">Dashboard</a></li>
                        <li><a href="/#logout" class="logout-btn">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="error-page">
            <div class="container">
                <div class="error-content">
                    <h1>404</h1>
                    <h2>Page Not Found</h2>
                    <p>The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
                    <a href="/" class="btn primary">Go to Homepage</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Mobile App. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/app.js"></script>
</body>
</html> 