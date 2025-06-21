<?php
/**
 * Tips & Guide Page for the Red Cross Mobile App
 *
 * This page provides donation tips and eligibility guidelines.
 *
 * Path: templates/tips-guide.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tips & Guide</title>
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

        .checklist-box {
            background-color: #ffebee;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .checklist-item .icon {
            margin-right: 15px;
            color: #4CAF50;
            font-weight: bold;
        }
        
        .tip-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .tip-card h3 {
            margin: 0 0 5px;
            font-size: 16px;
        }

        .tip-card p {
            margin: 0 0 10px;
            color: #666;
            font-size: 14px;
        }

        .read-more {
            text-align: right;
            font-size: 14px;
            color: #007bff;
            text-decoration: none;
        }

        .deferral-cards-container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .deferral-card {
            background: #ffebee;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            width: 48%;
            box-sizing: border-box;
            text-align: center;
        }

        .deferral-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .deferral-card h4 {
            color: #D50000;
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }

        .deferral-card a {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="back-arrow">
            <a href="explore.php">&#8249;</a>
        </div>
        <div class="header-title">Tips & Guide</div>
    </div>

    <div class="container">
        <h2 class="section-title">Basic Eligibility Checklist</h2>
        <div class="checklist-box">
            <div class="checklist-item"><span class="icon">✓</span> Age: 18-60 years old (up to 65 if a regular donor)</div>
            <div class="checklist-item"><span class="icon">✓</span> Weight: At least 50kg</div>
            <div class="checklist-item"><span class="icon">✓</span> Hemoglobin: Within normal range</div>
            <div class="checklist-item"><span class="icon">✓</span> No major illness in the past 12 months</div>
            <div class="checklist-item"><span class="icon">✓</span> Has not donated blood in the last 3 months</div>
            <div class="checklist-item"><span class="icon">✓</span> Has enough sleep and proper hydration</div>
        </div>

        <h2 class="section-title">Donation Tips</h2>
        <div class="tip-card">
            <h3>Before Donation Tips</h3>
            <p>Prepare your body for a safe and smooth donation.</p>
            <a href="before-donation.php" class="read-more">Read More</a>
        </div>
        <div class="tip-card">
            <h3>During Donation Tips</h3>
            <p>Know what to expect while donating blood.</p>
            <a href="during-donation.php" class="read-more">Read More</a>
        </div>
        <div class="tip-card">
            <h3>After Donation Care</h3>
            <p>Follow these steps to recover and feel your best.</p>
            <a href="after-donation.php" class="read-more">Read More</a>
        </div>

        <h2 class="section-title" style="margin-top: 30px;">Deferrals</h2>
        <div class="deferral-cards-container">
            <div class="deferral-card">
                <a href="temporary-deferrals.php">
                    <div class="deferral-icon">⏱️</div>
                    <h4>Temporary Deferrals</h4>
                </a>
            </div>
            <div class="deferral-card">
                <a href="permanent-deferrals.php">
                    <div class="deferral-icon">❌</div>
                    <h4>Permanent Deferrals</h4>
                </a>
            </div>
        </div>
    </div>

</body>
</html> 