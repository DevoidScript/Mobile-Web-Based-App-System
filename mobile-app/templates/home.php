<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4285f4">
    <title>Mobile App</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="A Progressive Web App built with PHP and Supabase">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
</head>
<body>
    <header>
        <div class="container">
            <h1>Mobile App</h1>
            <nav>
                <ul>
                    <li><a href="#" class="active">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#about">About</a></li>
                    <?php if (!is_logged_in()): ?>
                        <li><a href="#login" class="login-btn">Login</a></li>
                    <?php else: ?>
                        <li><a href="#dashboard" class="dashboard-btn">Dashboard</a></li>
                        <li><a href="#logout" class="logout-btn">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section id="hero" class="hero">
            <div class="container">
                <h2>Welcome to Our Mobile App</h2>
                <p>A Progressive Web App with PHP backend and Supabase database</p>
                <div class="cta-buttons">
                    <a href="#features" class="btn primary">Learn More</a>
                    <a href="#login" class="btn secondary">Get Started</a>
                </div>
            </div>
        </section>

        <section id="features" class="features">
            <div class="container">
                <h2>Features</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="icon">📱</div>
                        <h3>Mobile-First Design</h3>
                        <p>Optimized for all screen sizes and devices</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🔄</div>
                        <h3>Offline Support</h3>
                        <p>Works even when you're not connected</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">⚡</div>
                        <h3>Fast &amp; Responsive</h3>
                        <p>Optimized for performance across devices</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🔒</div>
                        <h3>Secure Data</h3>
                        <p>Your data is encrypted and secure</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="about">
            <div class="container">
                <h2>About This App</h2>
                <p>This is a Progressive Web App built with PHP on the backend and Supabase as the database. It demonstrates how to create a modern web application that works like a native app on mobile devices.</p>
                <p>Key technologies used:</p>
                <ul>
                    <li>Progressive Web App (PWA) features</li>
                    <li>PHP Backend API</li>
                    <li>Supabase Database</li>
                    <li>Service Workers for offline support</li>
                    <li>Responsive Design</li>
                </ul>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Mobile App. All rights reserved.</p>
        </div>
    </footer>

    <!-- App Shell for the PWA -->
    <div id="app-shell" style="display: none;">
        <!-- This will be shown when offline -->
        <div class="offline-message">
            <h2>You are offline</h2>
            <p>Some features may not be available until you're back online.</p>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Login</h2>
            <form id="login-form" class="form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn primary full-width">Login</button>
                <p class="text-center">Don't have an account? <a href="#register" class="show-register">Register</a></p>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="register-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Register</h2>
            <form id="register-form" class="form">
                <div class="form-group">
                    <label for="reg-name">Full Name</label>
                    <input type="text" id="reg-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="reg-email">Email</label>
                    <input type="email" id="reg-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="reg-password">Password</label>
                    <input type="password" id="reg-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="reg-confirm-password">Confirm Password</label>
                    <input type="password" id="reg-confirm-password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn primary full-width">Register</button>
                <p class="text-center">Already have an account? <a href="#login" class="show-login">Login</a></p>
            </form>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php $flash_messages = get_flash_messages(); ?>
    <?php if (!empty($flash_messages)): ?>
        <div id="flash-messages">
            <?php foreach ($flash_messages as $type => $message): ?>
                <div class="flash-message <?php echo $type; ?>">
                    <?php echo $message; ?>
                    <span class="close-flash">&times;</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="assets/js/app.js"></script>
    <!-- Register Service Worker for PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('service-worker.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('ServiceWorker registration failed: ', error);
                    });
            });
        }
    </script>
</body>
</html> 