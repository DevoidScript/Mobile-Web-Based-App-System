<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#db2323">
    <title>Forgot Password - Smart Blood Management</title>
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
        
        .forgot-password-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        
        .forgot-password-card {
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
        
        .input-wrapper {
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
        
        .code-input {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 8px;
            padding: 12px 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(219, 35, 35, 0.1);
            outline: none;
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
        
        .resend-link {
            text-align: center;
            margin-top: 16px;
        }
        
        .resend-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
        
        .resend-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .forgot-password-container {
                padding: 15px;
            }
            
            .forgot-password-card {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="forgot-password-card">
            <div class="brand-header">
                <h1 class="app-title" id="pageTitle">Forgot Password?</h1>
                <h2 class="app-subtitle" id="pageSubtitle">No worries, we'll help you reset it</h2>
            </div>
            
            <div id="messageContainer"></div>
            
            <!-- Step 1: Email Input -->
            <div id="emailStep">
                <p class="info-text">Enter your email address and we'll send you a verification code to reset your password.</p>
                
                <form id="forgotPasswordForm">
                    <div class="input-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <span class="input-icon">✉️</span>
                            <input type="email" id="email" class="form-control" name="email" placeholder="Enter your email" autocomplete="email" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="submitBtn">Send Verification Code</button>
                </form>
            </div>
            
            <!-- Step 2: Code Verification -->
            <div id="codeStep" style="display: none;">
                <p class="info-text">We've sent a 6-digit verification code to your email. Please enter it below.</p>
                
                <form id="verifyCodeForm">
                    <input type="hidden" id="userEmail" name="email" value="">
                    
                    <div class="input-group">
                        <label for="verificationCode" class="form-label">Verification Code</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔐</span>
                            <input type="text" id="verificationCode" class="form-control code-input" name="code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="verifyBtn">Verify Code</button>
                    
                    <div class="resend-link">
                        <a href="#" id="resendLink">Didn't receive the code? Resend</a>
                    </div>
                </form>
            </div>
            
            <a href="login.php" class="btn-back">Back to Login</a>
        </div>
    </div>

    <script>
        let userEmail = '';
        
        // Step 1: Request reset code
        document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const submitBtn = document.getElementById('submitBtn');
            const messageContainer = document.getElementById('messageContainer');
            
            // Validate email
            if (!email || !email.includes('@')) {
                showMessage('Please enter a valid email address', 'error');
                return;
            }
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'request_reset');
                formData.append('email', email);
                
                const response = await fetch('../api/password-reset.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    userEmail = email;
                    document.getElementById('userEmail').value = email;
                    
                    // Hide email step, show code step
                    document.getElementById('emailStep').style.display = 'none';
                    document.getElementById('codeStep').style.display = 'block';
                    document.getElementById('pageTitle').textContent = 'Enter Verification Code';
                    document.getElementById('pageSubtitle').textContent = 'Check your email for the code';
                    
                    showMessage('Verification code has been sent to your email. Please check your inbox (and spam folder).', 'success');
                    
                    // Focus on code input
                    setTimeout(() => {
                        document.getElementById('verificationCode').focus();
                    }, 100);
                } else {
                    // Show detailed error message
                    let errorMsg = result.message || 'Failed to send verification code. Please try again.';
                    
                    // Add helpful suggestions for common errors
                    if (errorMsg.includes('email') || errorMsg.includes('SMTP') || errorMsg.includes('send')) {
                        errorMsg += ' Please check your email configuration or contact support.';
                    }
                    
                    showMessage(errorMsg, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Send Verification Code';
                    
                    // Log error to console for debugging
                    console.error('Password reset error:', result);
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again later.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Verification Code';
            }
        });
        
        // Step 2: Verify code
        document.getElementById('verifyCodeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const code = document.getElementById('verificationCode').value;
            const verifyBtn = document.getElementById('verifyBtn');
            const messageContainer = document.getElementById('messageContainer');
            
            // Validate code
            if (!code || code.length !== 6 || !/^\d{6}$/.test(code)) {
                showMessage('Please enter a valid 6-digit code', 'error');
                return;
            }
            
            // Disable button and show loading
            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'verify_code');
                formData.append('email', userEmail);
                formData.append('code', code);
                
                const response = await fetch('../api/password-reset.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Redirect to reset password page with token
                    const token = result.data?.token || code;
                    window.location.href = `reset-password.php?token=${encodeURIComponent(token)}&email=${encodeURIComponent(userEmail)}`;
                } else {
                    showMessage(result.message || 'Invalid verification code. Please try again.', 'error');
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify Code';
                    document.getElementById('verificationCode').value = '';
                    document.getElementById('verificationCode').focus();
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again later.', 'error');
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Verify Code';
            }
        });
        
        // Resend code
        document.getElementById('resendLink').addEventListener('click', async function(e) {
            e.preventDefault();
            
            if (!userEmail) {
                showMessage('Please request a code first', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'request_reset');
            formData.append('email', userEmail);
            
            try {
                const response = await fetch('../api/password-reset.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Verification code has been resent to your email.', 'success');
                } else {
                    showMessage(result.message || 'Failed to resend code. Please try again.', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again later.', 'error');
            }
        });
        
        // Auto-format code input (numbers only)
        document.getElementById('verificationCode').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) {
                // Auto-submit when 6 digits are entered
                // document.getElementById('verifyCodeForm').dispatchEvent(new Event('submit'));
            }
        });
        
        function showMessage(message, type) {
            const messageContainer = document.getElementById('messageContainer');
            messageContainer.innerHTML = `<div class="flash-message flash-${type}">${message}</div>`;
            
            // Scroll to top to show message
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Check for URL parameters
        const urlParams = new URLSearchParams(window.location.search);
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
