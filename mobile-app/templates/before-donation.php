<?php
/**
 * Before Donation Tips Page
 * Path: templates/before-donation.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Before Donation Tips</title>
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
            margin-right: 30px;
        }
        .container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #D50000;
            margin-bottom: 15px;
        }
        .tips-list-box {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
        }
        .tips-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .tips-list li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .tips-list li::before {
            content: '•';
            color: #D50000;
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="back-arrow">
            <a href="tips-guide.php">&#8249;</a>
        </div>
        <div class="header-title">Donation Tips</div>
    </div>
    <div class="container">
        <h2 class="section-title">Before Donation Tips</h2>
        <div class="tips-list-box">
            <ul class="tips-list">
                <li>Get 6-8 hours of sleep the night before</li>
                <li>Eat a light meal (avoid fatty foods)</li>
                <li>Drink plenty of water</li>
                <li>Avoid alcohol 24 hours before</li>
                <li>Bring a valid ID</li>
                <li>Wear comfortable clothing (short sleeves)</li>
            </ul>
        </div>
    </div>
</body>
</html> 