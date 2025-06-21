<?php
/**
 * During Donation Tips Page
 * Path: templates/during-donation.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>During Donation Tips</title>
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
        <h2 class="section-title">During Donation Tips</h2>
        <div class="tips-list-box">
            <ul class="tips-list">
                <li>Stay calm and relaxed</li>
                <li>Follow staff instructions</li>
                <li>Squeeze the stress ball gently</li>
                <li>Let them know if you feel dizzy or unwell</li>
            </ul>
        </div>
    </div>
</body>
</html> 