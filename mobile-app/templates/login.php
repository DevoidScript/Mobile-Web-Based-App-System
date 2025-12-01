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
    <!-- Resource hints for faster loading on slow connections -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="//fonts.gstatic.com" crossorigin>
    <!-- Preload critical resources -->
    <link rel="preload" href="../assets/icons/redcrosslogo.jpg" as="image">
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
        
        /* Error Modal Styling */
        .error-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            padding: 20px;
            box-sizing: border-box;
            animation: fadeIn 0.3s ease;
        }
        
        .error-modal {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
            position: relative;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-modal-header {
            background-color: #d32f2f;
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .error-modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-modal-header .error-icon {
            font-size: 24px;
        }
        
        .error-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        
        .error-modal-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .error-modal-body {
            padding: 24px;
        }
        
        .error-modal-body .error-type {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .error-modal-body .error-message {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        
        .error-modal-body .error-details {
            background-color: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
            color: #666;
            margin-bottom: 16px;
            font-family: monospace;
            word-break: break-word;
        }
        
        .error-modal-body .error-help {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
            padding: 12px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .error-modal-footer {
            padding: 0 24px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .error-modal-button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 100px;
        }
        
        .error-modal-button-primary {
            background-color: #d32f2f;
            color: white;
        }
        
        .error-modal-button-primary:hover {
            background-color: #b71c1c;
        }
        
        .error-modal-button-secondary {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .error-modal-button-secondary:hover {
            background-color: #e0e0e0;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            -webkit-backdrop-filter: blur(3px);
            backdrop-filter: blur(3px);
        }
        
        .loading-content {
            background-color: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            text-align: center;
            width: 85%;
            max-width: 300px;
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #d32f2f;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            margin: 0 auto 16px;
            animation: spin 1.2s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            
            .error-modal {
                max-width: 100%;
                margin: 10px;
            }
            
            .error-modal-header {
                padding: 16px;
            }
            
            .error-modal-header h3 {
                font-size: 18px;
            }
            
            .error-modal-body {
                padding: 20px;
            }
            
            .error-modal-footer {
                flex-direction: column;
                padding: 0 20px 20px;
            }
            
            .error-modal-button {
                width: 100%;
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
            
            <form id="loginForm" action="../api/auth.php?login" method="POST" novalidate>
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
                
                <button type="submit" class="btn-login" id="loginButton">Login</button>
            </form>
            
            <a href="../index.php" class="btn-register">Back</a>
            
            <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
            
            <div class="footer-logo">
                <img src="../assets/icons/redcrosslogo.jpg" alt="Philippine Red Cross Logo" width="70" height="70" loading="lazy">
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
            
            // Show error modal if there's an error in URL
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            if (error) {
                const errorData = categorizeError(
                    new Error(decodeURIComponent(error)),
                    null
                );
                showErrorModal(errorData);
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
        
        /**
         * Enhanced Error Modal System for Login
         * Displays user-friendly error messages in a modal dialog
         */
        function showErrorModal(errorData) {
            // Remove any existing error modals
            const existingModal = document.querySelector('.error-modal-overlay');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Determine error type and message
            const errorType = errorData.type || 'Login Error';
            const errorMessage = errorData.message || 'An unknown error occurred';
            const errorDetails = errorData.details || null;
            const errorHelp = errorData.help || 'Please check your credentials and try again. If the problem persists, contact technical support.';
            
            // Create modal overlay
            const overlay = document.createElement('div');
            overlay.className = 'error-modal-overlay';
            
            // Create modal
            const modal = document.createElement('div');
            modal.className = 'error-modal';
            
            // Modal header
            const header = document.createElement('div');
            header.className = 'error-modal-header';
            header.innerHTML = `
                <h3>
                    <span class="error-icon">⚠️</span>
                    <span>${errorType}</span>
                </h3>
                <button class="error-modal-close" aria-label="Close">&times;</button>
            `;
            
            // Modal body
            const body = document.createElement('div');
            body.className = 'error-modal-body';
            
            const typeLabel = document.createElement('div');
            typeLabel.className = 'error-type';
            typeLabel.textContent = errorData.category || 'Error';
            
            const message = document.createElement('div');
            message.className = 'error-message';
            message.textContent = errorMessage;
            
            body.appendChild(typeLabel);
            body.appendChild(message);
            
            // Add error details if available
            if (errorDetails) {
                const details = document.createElement('div');
                details.className = 'error-details';
                details.textContent = errorDetails;
                body.appendChild(details);
            }
            
            // Add help text
            const help = document.createElement('div');
            help.className = 'error-help';
            help.textContent = errorHelp;
            body.appendChild(help);
            
            // Modal footer
            const footer = document.createElement('div');
            footer.className = 'error-modal-footer';
            footer.innerHTML = `
                <button class="error-modal-button error-modal-button-primary" onclick="this.closest('.error-modal-overlay').remove()">
                    OK, I Understand
                </button>
            `;
            
            // Assemble modal
            modal.appendChild(header);
            modal.appendChild(body);
            modal.appendChild(footer);
            overlay.appendChild(modal);
            
            // Add to document
            document.body.appendChild(overlay);
            
            // Close on overlay click (outside modal)
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    overlay.remove();
                }
            });
            
            // Close on close button click
            header.querySelector('.error-modal-close').addEventListener('click', function() {
                overlay.remove();
            });
            
            // Close on Escape key
            const escapeHandler = function(e) {
                if (e.key === 'Escape') {
                    overlay.remove();
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);
        }
        
        /**
         * Categorize and format errors for better user experience
         */
        function categorizeError(error, responseStatus = null) {
            let category = 'Error';
            let type = 'Login Error';
            let message = error.message || 'An unexpected error occurred';
            let details = null;
            let help = 'Please check your credentials and try again. If the problem persists, contact technical support.';
            
            // Network errors
            if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError') || error.message.includes('network')) {
                category = 'Network Error';
                type = 'Connection Problem';
                message = 'Unable to connect to the server. Please check your internet connection and try again.';
                help = 'Make sure you have a stable internet connection. If you\'re using mobile data, try switching to Wi-Fi.';
            }
            // Timeout errors
            else if (error.message.includes('timeout') || error.message.includes('taking too long')) {
                category = 'Timeout Error';
                type = 'Request Timeout';
                message = 'The server is taking too long to respond. Please try again in a moment.';
                help = 'The server might be experiencing high traffic. Please wait a few moments and try again.';
            }
            // Invalid credentials
            else if (error.message.includes('Invalid email or password') || error.message.includes('Invalid credentials') || error.message.includes('incorrect password') || error.message.includes('wrong password')) {
                category = 'Authentication Error';
                type = 'Invalid Credentials';
                message = 'The email or password you entered is incorrect. Please check your credentials and try again.';
                help = 'Double-check that you\'ve entered the correct email address and password. If you\'ve forgotten your password, use the "Forgot Password?" link below.';
            }
            // Email not verified
            else if (error.message.includes('verify your email') || error.message.includes('email not verified') || error.message.includes('verification')) {
                category = 'Verification Error';
                type = 'Email Not Verified';
                message = 'Please verify your email address before logging in. Check your inbox for the verification code.';
                help = 'If you haven\'t received the verification email, check your spam folder or request a new verification code from the registration page.';
            }
            // Missing fields
            else if (error.message.includes('required') || error.message.includes('Email and password')) {
                category = 'Validation Error';
                type = 'Missing Information';
                message = 'Please enter both your email address and password.';
                help = 'Make sure both the email and password fields are filled in before attempting to login.';
            }
            // Server errors (5xx)
            else if (responseStatus >= 500) {
                category = 'Server Error';
                type = 'Server Problem';
                message = 'The server encountered an error processing your request. Please try again later.';
                help = 'This is a temporary server issue. Please try again in a few minutes. If the problem continues, contact technical support.';
                details = error.message;
            }
            // Client errors (4xx)
            else if (responseStatus >= 400 && responseStatus < 500) {
                category = 'Request Error';
                type = 'Invalid Request';
                message = error.message || 'Your request could not be processed. Please check your information.';
                help = 'Please review your login credentials and make sure they are correct.';
            }
            // Generic errors
            else {
                details = error.message;
            }
            
            return {
                category: category,
                type: type,
                message: message,
                details: details,
                help: help
            };
        }
        
        // Handle login form submission with AJAX
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const loginButton = document.getElementById('loginButton');
            
            // Basic validation
            if (!email || !password) {
                const validationError = categorizeError(
                    new Error('Please enter both your email address and password.'),
                    null
                );
                validationError.type = 'Missing Information';
                validationError.category = 'Validation Error';
                validationError.help = 'Make sure both the email and password fields are filled in before attempting to login.';
                showErrorModal(validationError);
                return false;
            }
            
            // Show loading indicator
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.innerHTML = `
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <p>Logging in...</p>
                </div>
            `;
            document.body.appendChild(loadingOverlay);
            
            // Disable login button
            loginButton.disabled = true;
            loginButton.textContent = 'Logging in...';
            
            // Set timeout for loading overlay
            const loadingTimeout = setTimeout(() => {
                if (document.body.contains(loadingOverlay)) {
                    loadingOverlay.remove();
                    const timeoutError = categorizeError(
                        new Error('The server is taking too long to respond'),
                        null
                    );
                    showErrorModal(timeoutError);
                    loginButton.disabled = false;
                    loginButton.textContent = 'Login';
                }
            }, 15000);
            
            // Prepare form data
            const formData = new FormData(this);
            
            // Submit via AJAX
            fetch('../api/auth.php?login', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                redirect: 'follow' // Follow redirects
            })
            .then(response => {
                // Get the final URL after redirects
                const finalUrl = response.url;
                
                // Check if the final URL contains an error parameter
                if (finalUrl.includes('error=')) {
                    const urlObj = new URL(finalUrl);
                    const errorMessage = urlObj.searchParams.get('error');
                    throw new Error(errorMessage || 'Login failed');
                }
                
                // Check if redirecting to dashboard (success)
                if (finalUrl.includes('dashboard.php') || finalUrl.includes('email-verification.php')) {
                    clearTimeout(loadingTimeout);
                    loadingOverlay.remove();
                    loginButton.disabled = false;
                    loginButton.textContent = 'Login';
                    window.location.href = finalUrl;
                    return;
                }
                
                // Check if redirecting to index.php (likely an error)
                if (finalUrl.includes('index.php')) {
                    const urlObj = new URL(finalUrl);
                    const errorMessage = urlObj.searchParams.get('error');
                    if (errorMessage) {
                        throw new Error(errorMessage);
                    }
                }
                
                // If response is not ok, try to get error message
                if (!response.ok) {
                    return response.text().then(text => {
                        let errorMessage = 'Login failed. Please try again.';
                        try {
                            const jsonData = JSON.parse(text);
                            errorMessage = jsonData.message || errorMessage;
                        } catch (e) {
                            if (text) {
                                errorMessage = text;
                            }
                        }
                        throw new Error(errorMessage);
                    });
                }
                
                return response.text();
            })
            .then(data => {
                // If we get here and have data, try to parse it
                if (data) {
                    clearTimeout(loadingTimeout);
                    loadingOverlay.remove();
                    loginButton.disabled = false;
                    loginButton.textContent = 'Login';
                    
                    try {
                        const jsonData = JSON.parse(data);
                        if (jsonData.success && jsonData.redirect) {
                            window.location.href = jsonData.redirect;
                        } else if (jsonData.success) {
                            window.location.href = '../templates/dashboard.php';
                        } else {
                            throw new Error(jsonData.message || 'Login failed');
                        }
                    } catch (e) {
                        // If parsing fails, check if it's an error message
                        if (data.includes('error') || data.includes('Error')) {
                            throw new Error(data);
                        }
                        // Otherwise assume success
                        window.location.href = '../templates/dashboard.php';
                    }
                }
            })
            .catch(error => {
                // Remove loading timeout and overlay
                clearTimeout(loadingTimeout);
                if (document.body.contains(loadingOverlay)) {
                    loadingOverlay.remove();
                }
                
                // Re-enable login button
                loginButton.disabled = false;
                loginButton.textContent = 'Login';
                
                // Log error for debugging
                console.error('Login error:', error);
                
                // Categorize and show error in modal
                const errorData = categorizeError(error, null);
                showErrorModal(errorData);
            });
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