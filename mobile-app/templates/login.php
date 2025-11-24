<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#db2323">
    <title>Smart Blood Management</title>
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="Smart Blood Management System">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <style>
        :root {
            --primary-color: #db2323;
            --secondary-color: #6a6f63;
            --background-color: #f9f4f4;
            --card-bg-color: #ffffff;
            --text-color: #333333;
            --input-border: #e0e0e0;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        body {
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            background-image: linear-gradient(135deg, #fbe9e7 0%, #f9f4f4 100%);
            background-attachment: fixed;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: radial-gradient(circle at 10% 20%, rgba(255, 200, 200, 0.15) 10%, transparent 60%);
            z-index: -1;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        
        .login-card {
            background-color: var(--card-bg-color);
            border-radius: 16px;
            box-shadow: 0 8px 24px var(--shadow-color);
            padding: 30px 25px;
            width: 100%;
            position: relative;
        }
        
        .brand-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .app-title {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 6px;
            line-height: 1.2;
        }
        
        .app-subtitle {
            color: var(--secondary-color);
            font-size: 20px;
            font-weight: 500;
            margin: 0;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
            color: #555;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-wrapper, .password-input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 14px 12px 45px;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
            background-color: #fff;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(219, 35, 35, 0.1);
            outline: none;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
            font-size: 14px;
            padding: 5px;
        }
        
        .remember-me-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .remember-me-checkbox {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            accent-color: var(--primary-color);
        }
        
        .remember-me-label {
            font-size: 14px;
            color: #555;
        }
        
        .btn-login {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-bottom: 16px;
        }
        
        .btn-login:hover {
            background-color: #c61d1d;
        }
        
        .btn-register {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #ffffff;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s;
            margin-bottom: 16px;
            text-align: center;
            text-decoration: none;
        }
        
        .btn-register:hover {
            background-color: #f9f0f0;
        }
        
        .forgot-password {
            display: block;
            text-align: center;
            color: #555;
            font-size: 14px;
            text-decoration: none;
            margin-top: 16px;
            transition: color 0.2s;
        }
        
        .forgot-password:hover {
            color: var(--primary-color);
        }
        
        .footer-logo {
            text-align: center;
            margin-top: 30px;
        }
        
        .footer-logo img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            object-position: center;
            border: 1px solid #eeeeee;
        }
        
        .system-name {
            text-align: center;
            color: #777;
            font-size: 12px;
            margin-top: 8px;
        }
        
        .flash-message {
            padding: 12px;
            margin-bottom: 24px;
            border-radius: 8px;
            width: 100%;
            text-align: center;
            font-size: 14px;
        }
        
        .flash-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .flash-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
            transition: opacity 1s ease-in-out;
        }
        
        .fade-out {
            opacity: 0;
        }
        
        /* PWA enhancements for mobile */
        @media (max-width: 768px) {
            .login-container {
                padding: 15px;
            }
            
            .login-card {
                padding: 25px 20px;
            }
            
            body.pwa-standalone {
                padding-top: env(safe-area-inset-top, 0);
                padding-bottom: env(safe-area-inset-bottom, 0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="brand-header">
                <h1 class="app-title">Smart Blood Management</h1>
                <h2 class="app-subtitle">Philippine Red Cross</h2>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="flash-message flash-error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="flash-message flash-success" id="successMessage">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <form action="../api/auth.php?login" method="POST">
                <div class="input-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">✉️</span>
                        <input type="email" id="email" class="form-control" name="email" placeholder="Enter your email" autocomplete="email" required>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" class="form-control" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="toggle-password" id="togglePassword">Show</button>
                    </div>
                </div>
                
                <div class="remember-me-wrapper">
                    <input type="checkbox" id="remember-me" name="remember" class="remember-me-checkbox">
                    <label for="remember-me" class="remember-me-label">Remember me</label>
                </div>
                
                <button type="submit" class="btn-login">Login</button>
            </form>
            
            <a href="../index.php" class="btn-register">Back</a>
            
            <a href="#" class="forgot-password">Forgot Password?</a>
            
            <div class="footer-logo">
                <img src="../assets/icons/redcrosslogo.jpg" alt="Philippine Red Cross Logo">
                <div class="system-name">Blood Services Information System</div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.textContent = type === 'password' ? 'Show' : 'Hide';
            });
            
            // Auto-hide success message after 5 seconds
            const successMessage = document.getElementById('successMessage');
            
            if (successMessage) {
                // Check if it contains "logged out" message (case insensitive)
                const messageText = successMessage.textContent.toLowerCase();
                if (messageText.includes('logged out')) {
                    // Start the timeout to fade out after 5 seconds
                    setTimeout(function() {
                        successMessage.classList.add('fade-out');
                        
                        // Remove the element after fade completes
                        setTimeout(function() {
                            successMessage.style.display = 'none';
                        }, 1000); // Match the transition duration (1s)
                    }, 1500);
                }
            }
        });
        
        // Check if this is a PWA
        const isInStandaloneMode = () => 
            (window.matchMedia('(display-mode: standalone)').matches) || 
            (window.navigator.standalone) || 
            document.referrer.includes('android-app://');
            
        if (isInStandaloneMode()) {
            document.body.classList.add('pwa-standalone');
        }
        
        // Register Service Worker for PWA with improved error handling
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                // Determine correct path based on current location
                const getBasePath = function() {
                    const pathname = window.location.pathname;
                    const marker = '/mobile-app/';
                    const idx = pathname.indexOf(marker);
                    if (idx !== -1) {
                        return pathname.substring(0, idx + marker.length);
                    }
                    return '/mobile-app/';
                };
                
                const basePath = getBasePath();
                const swPath = basePath + 'service-worker.js';
                
                navigator.serviceWorker.register(swPath, {
                    scope: basePath
                })
                .then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                })
                .catch(function(error) {
                    // Only log if it's not a 404 (file might not exist in some environments)
                    if (error.message && !error.message.includes('404') && !error.message.includes('bad HTTP response code')) {
                        console.warn('ServiceWorker registration warning: ', error.message);
                    }
                    // Silently fail for 404 errors to avoid console spam
                });
            });
        }
    </script>
</body>
</html> 