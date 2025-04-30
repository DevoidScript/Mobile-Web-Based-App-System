<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF0000">
    <title>Red Cross Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="Red Cross Mobile Application">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <style>
        /* Additional styles specific to the login page */
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        
        .login-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Login card container to create visual separation from background */
        .login-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .login-title {
            color: #FF0000;
            font-size: 22px;
            margin-top: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .logo-container {
            margin-bottom: 40px;
            text-align: center;
        }
        
        .logo {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border-radius: 50%;
            background-color: white;
        }
        
        .login-form {
            width: 100%;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background-color: #FF0000;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .btn-register {
            width: 100%;
            padding: 15px;
            background-color: #ffffff;
            color: #FF0000;
            border: 1px solid #FF0000;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .navigation-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #000000;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
        }
        
        .nav-button {
            color: white;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }
        
        .flash-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            width: 100%;
            text-align: center;
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
        
        /* Animation for fading out */
        .fade-out {
            opacity: 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <img src="assets/icons/redcrosslogo.jpg" alt="Philippine Red Cross Logo" class="logo">
            </div>
            
            <h2 class="login-title">Philippine Red Cross</h2>
            
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
            
            <div class="login-form">
                <form action="api/auth.php?login" method="POST">
                    <div class="form-group">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn-login">Login</button>
                </form>
                <a href="templates/register.php"><button type="button" class="btn-register">Register</button></a>
            </div>
        </div>
    </div>

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
        
        // Auto-hide success message after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
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
    </script>
</body>
</html> 