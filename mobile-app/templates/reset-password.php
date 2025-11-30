<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#db2323">
    <title>Reset Password - Smart Blood Management</title>
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
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
        
        .reset-password-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        
        .reset-password-card {
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
        
        .btn-submit {
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
        
        .btn-submit:hover {
            background-color: #c61d1d;
        }
        
        .btn-submit:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .btn-back {
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
        
        .btn-back:hover {
            background-color: #f9f0f0;
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
        }
        
        .info-text {
            color: #666;
            font-size: 14px;
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        
        .password-hint {
            font-size: 13px;
            color: #666;
            margin-top: 6px;
            margin-bottom: 0;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .reset-password-container {
                padding: 15px;
            }
            
            .reset-password-card {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <div class="reset-password-card">
            <div class="brand-header">
                <h1 class="app-title">Reset Password</h1>
                <h2 class="app-subtitle">Enter your new password</h2>
            </div>
            
            <div id="messageContainer"></div>
            
            <div id="formContainer">
                <p class="info-text">Please enter your new password below. Make sure it's at least 8 characters long.</p>
                
                <form id="resetPasswordForm">
                    <input type="hidden" id="token" name="token" value="">
                    
                    <div class="input-group">
                        <label for="password" class="form-label">New Password</label>
                        <div class="password-input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="password" class="form-control" name="password" placeholder="Enter new password" autocomplete="new-password" required minlength="8">
                            <button type="button" class="toggle-password" id="togglePassword1">Show</button>
                        </div>
                        <p class="password-hint">Password must be at least 8 characters long</p>
                    </div>
                    
                    <div class="input-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="password-input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="confirm_password" class="form-control" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password" required minlength="8">
                            <button type="button" class="toggle-password" id="togglePassword2">Show</button>
                        </div>
                        <p class="password-hint">Password must be at least 8 characters long</p>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="submitBtn">Reset Password</button>
                </form>
            </div>
            
            <a href="login.php" class="btn-back">Back to Login</a>
        </div>
    </div>

    <script>
        // Get token and email from URL
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        const email = urlParams.get('email');
        
        if (!token) {
            document.getElementById('messageContainer').innerHTML = 
                '<div class="flash-message flash-error">Invalid reset token. Please request a new password reset.</div>';
            document.getElementById('formContainer').style.display = 'none';
        } else {
            document.getElementById('token').value = token;
            if (email) {
                // Show email info if available
                const infoText = document.querySelector('.info-text');
                if (infoText) {
                    infoText.textContent = `Please enter your new password for ${email}. Make sure it's at least 8 characters long.`;
                }
            }
        }
        
        // Toggle password visibility
        document.getElementById('togglePassword1').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
        });
        
        document.getElementById('togglePassword2').addEventListener('click', function() {
            const passwordField = document.getElementById('confirm_password');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
        });
        
        // Handle form submission
        document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submitBtn');
            const messageContainer = document.getElementById('messageContainer');
            
            // Validate passwords
            if (password.length < 8) {
                showMessage('Password must be at least 8 characters long', 'error');
                return;
            }
            
            if (password !== confirmPassword) {
                showMessage('Passwords do not match', 'error');
                return;
            }
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Resetting...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'reset_password');
                formData.append('token', token);
                formData.append('password', password);
                formData.append('confirm_password', confirmPassword);
                
                const response = await fetch('../api/password-reset.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage(result.message || 'Password has been reset successfully! You can now login with your new password.', 'success');
                    document.getElementById('formContainer').style.display = 'none';
                    
                    // Redirect to login after 3 seconds
                    setTimeout(() => {
                        window.location.href = 'login.php?success=' + encodeURIComponent('Password reset successful! Please login with your new password.');
                    }, 3000);
                } else {
                    showMessage(result.message || 'Failed to reset password. Please try again.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Reset Password';
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again later.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset Password';
            }
        });
        
        function showMessage(message, type) {
            const messageContainer = document.getElementById('messageContainer');
            messageContainer.innerHTML = `<div class="flash-message flash-${type}">${message}</div>`;
            
            // Scroll to top to show message
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Check for URL parameters
        const error = urlParams.get('error');
        const success = urlParams.get('success');
        
        if (error) {
            showMessage(decodeURIComponent(error), 'error');
        }
        if (success) {
            showMessage(decodeURIComponent(success), 'success');
        }
    </script>
</body>
</html>

