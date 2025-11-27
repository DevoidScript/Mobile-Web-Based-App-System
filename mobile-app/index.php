<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#db2323">
    <title>Smart Blood Management - Welcome</title>
    <!-- Resource hints for faster loading on slow connections -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="//fonts.gstatic.com" crossorigin>
    <!-- Preload critical resources for LCP -->
    <link rel="preload" href="assets/icons/redcrosslogo.jpg" as="image" fetchpriority="high">
    <link rel="preload" href="assets/icons/icon-192x192.png" as="image">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
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
        .container {
            width: 100%;
            max-width: 420px;
            background-color: var(--card-bg-color);
            border-radius: 16px;
            box-shadow: 0 8px 24px var(--shadow-color);
            padding: 36px 28px 20px 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .app-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.2em;
            line-height: 1.2;
        }
        .subtitle {
            color: var(--secondary-color);
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 2em;
            font-weight: 500;
        }
        .welcome {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 2em;
            text-align: center;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            margin-bottom: 18px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            text-align: center;
        }
        .btn-login {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
        }
        .btn-login:hover {
            background-color: #c61d1d;
        }
        .btn-register {
            background-color: #fff;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        .btn-register:hover {
            background-color: #fbeaea;
        }
        .logo {
            margin: 32px 0 8px 0;
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        .footer {
            color: var(--secondary-color);
            font-size: 1rem;
            text-align: center;
            margin-bottom: 8px;
        }
        @media (max-width: 500px) {
            .container {
                padding: 18px 4vw 12px 4vw;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="app-title">Smart Blood<br>Management</div>
        <div class="subtitle">Philippine Red Cross</div>
        <div class="welcome">Welcome!</div>
        <a href="templates/login.php" style="width:100%;text-decoration:none;"><button class="btn btn-login">Login</button></a>
        <a href="templates/register.php" style="width:100%;text-decoration:none;"><button class="btn btn-register">Register</button></a>
        <img src="assets/icons/redcrosslogo.jpg" alt="Red Cross Logo" class="logo" fetchpriority="high" width="70" height="70" loading="eager">
        <div class="footer">Blood Services Information System</div>
    </div>
</body>
</html> 