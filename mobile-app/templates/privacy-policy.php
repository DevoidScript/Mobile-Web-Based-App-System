<?php
/**
 * Privacy Policy Page for the Red Cross Mobile App
 *
 * This page displays the application's privacy policy.
 *
 * Path: templates/privacy-policy.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .header {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #FF0000;
            color: white;
            position: relative;
        }

        .back-arrow {
            position: absolute;
            left: 15px;
        }

        .back-arrow a {
            font-size: 24px;
            color: white;
            text-decoration: none;
        }

        .header-title {
            flex-grow: 1;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
        }

        .container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: left;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #D50000;
            margin-bottom: 10px;
        }

        .section p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="back-arrow">
            <a href="profile.php">&#8249;</a>
        </div>
        <div class="header-title">Privacy Policy</div>
    </div>

    <div class="container">
        <div class="card">
            <h2 class="section-title">Introduction</h2>
            <p>
                Welcome to the Philippine Red Cross Mobile App. We are committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our mobile application.
            </p>
        </div>
        <div class="card">
            <h2 class="section-title">Information We Collect</h2>
            <p>
                We may collect information about you in a variety of ways. The information we may collect via the Application includes:
                <br><br>
                <strong>Personal Data:</strong> Personally identifiable information, such as your name, shipping address, email address, and telephone number, and demographic information, such as your age, gender, hometown, and interests, that you voluntarily give to us when you register with the Application.
                <br><br>
                <strong>Health Information:</strong> To determine your eligibility for blood donation, we may collect health-related information as required by donation standards.
            </p>
        </div>
        <div class="card">
            <h2 class="section-title">How We Use Your Information</h2>
            <p>
                Having accurate information permits us to provide you with a smooth, efficient, and customized experience. Specifically, we may use information collected about you via the Application to:
                <br><br>
                - Manage your account and profile.<br>
                - Determine your eligibility for blood donation.<br>
                - Send you notifications about your donation schedule and status.<br>
                - Fulfill and manage services related to the app.
            </p>
        </div>
        <div class="card">
            <h2 class="section-title">Data Security</h2>
            <p>
                We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable, and no method of data transmission can be guaranteed against any interception or other type of misuse.
            </p>
        </div>
    </div>

</body>
</html> 