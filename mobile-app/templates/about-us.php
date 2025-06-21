<?php
/**
 * About Us Page for the Red Cross Mobile App
 *
 * This page provides information about the organization, the app, and credits.
 *
 * Path: templates/about-us.php
 */

// Basic setup, no complex logic needed for this static page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
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
            width: 100%;
            box-sizing: border-box;
            z-index: 100;
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

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #D50000;
            margin-bottom: 10px;
        }

        .section p, .section ul {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .section strong {
            font-weight: bold;
        }
        
        .section ul {
            list-style-type: disc;
            padding-left: 20px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="back-arrow">
            <a href="profile.php">&#8249;</a>
        </div>
        <div class="header-title">About Us</div>
    </div>

    <div class="container">
        <div class="card">
            <h2 class="section-title">Organization Overview</h2>
            <p>
                The Philippine Red Cross (PRC) is a humanitarian and non-government organization committed to providing life-saving services, reducing human suffering, and promoting health and safety for the people. It operates under the mandate of Republic Act No. 10072 (The Philippine Red Cross Act of 2009), which affirms its role as an auxiliary to public authorities in humanitarian services.
            </p>
            <p>
                <strong>Mission:</strong> To provide timely and effective humanitarian assistance to the most vulnerable communities through efficient service delivery.
            </p>
            <p>
                <strong>Vision:</strong> A well-equipped, technology-driven organization that ensures the well-being of Iloilo communities through proactive disaster response, healthcare, and volunteer engagement.
            </p>
        </div>

        <div class="card">
            <h2 class="section-title">About the App</h2>
            <p>
                <strong>Smart Blood Donation App</strong><br>
                This mobile app was developed in partnership with the Philippine Red Cross to empower donors with a smarter way to manage their blood donation journey. It allows users to track eligibility, view donation history, monitor blood usage, and stay informed about donation tips and nearby centers – all in one place.
            </p>
        </div>

        <div class="card">
            <h2 class="section-title">App Version and Credits</h2>
            <ul>
                <li><strong>App Version:</strong> 1.0.0</li>
                <li><strong>Developed by:</strong> CodeRed PH</li>
                <li><strong>In cooperation with:</strong> Philippine Red Cross Blood Services</li>
                <li><strong>Contact Us:</strong> bloodapp-support@redcross.org.ph</li>
            </ul>
        </div>
    </div>

</body>
</html> 