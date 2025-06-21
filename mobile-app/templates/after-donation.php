<?php
/**
 * After Donation Care Page
 * Path: templates/after-donation.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>After Donation Care</title>
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
        <h2 class="section-title">After Donation Care</h2>
        <div class="tips-list-box">
            <ul class="tips-list">
                <li>Rest for 10-15 minutes</li>
                <li>Eat the snacks provided</li>
                <li>Avoid heavy lifting or intense activity for 24 hours</li>
                <li>Drink extra fluids</li>
                <li>If bruising occurs, apply a cold compress</li>
            </ul>
        </div>
    </div>
</body>
</html> 