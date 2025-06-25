<?php
// donation-success.php
// Confirmation page after successful medical history submission
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate Blood - Success</title>
    <style>
        body {
            background: #f7f7f7;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 400px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 32px 24px 24px 24px;
            text-align: center;
        }
        .success-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 24px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-icon img {
            width: 100%;
            height: 100%;
        }
        .success-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 12px;
            color: #222;
        }
        .success-message {
            font-size: 1rem;
            color: #444;
            margin-bottom: 32px;
        }
        .confirm-btn {
            background: #d50000;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 14px 0;
            width: 100%;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 16px;
        }
        .confirm-btn:active {
            background: #b71c1c;
        }
        .back-arrow {
            position: absolute;
            left: 24px;
            top: 24px;
            font-size: 1.5rem;
            color: #888;
            text-decoration: none;
        }
        @media (max-width: 500px) {
            .container {
                margin: 0;
                min-height: 100vh;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-arrow" title="Back">&lt;</a>
        <div class="success-icon">
            <!-- Inline SVG for green badge with checkmark -->
            <svg width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="48" fill="#E6F9EC" stroke="#2ECC71" stroke-width="4"/>
                <path d="M30 53L45 68L70 38" stroke="#2ECC71" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="success-title">You have successfully submitted your Medical History Form.</div>
        <div class="success-message">Please proceed to a Red Cross staff for the next steps in your donation process.</div>
        <form action="dashboard.php" method="get">
            <button type="submit" class="confirm-btn">Confirm</button>
        </form>
    </div>
</body>
</html> 