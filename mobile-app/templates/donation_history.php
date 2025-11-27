<?php
/**
 * Donation History Page for the Red Cross Mobile App
 * 
 * Updated to match wireframe design and use donations table
 * Created as part of the templates organization structure.
 *
 * Path: templates/donation_history.php
 */

// Set error reporting in development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../index.php?error=Please login to access your donation history');
    exit;
}

// Get user data
$user = $_SESSION['user'] ?? null;

// Initialize variables
$donation_history = [];
$latest_completed_donation = null;
$countdown_months = 0;
$countdown_days = 0;
$can_donate_now = false;
$next_donation_date = null;
$eligibility = null;
$medical_history_record = null;
$has_medical_history_record = false;
$donation_processing = false;
$latest_donation_status = null;

// Get donor ID the same way blood_tracker.php, medical-history-modal.php, and profile.php do
if ($user && isset($user['email'])) {
    $email = trim(strtolower($user['email']));
    
    // Fetch donor_form record by email
    $donorFormResp = get_records('donor_form', ['email' => 'eq.' . $email]);
    if ($donorFormResp['success'] && !empty($donorFormResp['data'])) {
        $donorForm = $donorFormResp['data'][0];
        $donor_id = $donorForm['donor_id'];
        
        // Debug logging
        error_log("Donation History - Found donor record for email: $email, donor_id: $donor_id");
        
        // Get donation history from donations table
        $donation_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'created_at.desc'
        ];
        
        $donation_result = get_records('donations', $donation_params);
        if ($donation_result['success'] && !empty($donation_result['data'])) {
            $donation_history = $donation_result['data'];
            
            // Get the latest donation (first in the array since we ordered by desc)
            $latest_donation = $donation_history[0];
            $latest_donation_status = strtolower(trim($latest_donation['current_status'] ?? ''));
            
            // Compute unified eligibility with 7-day grace
            $eligibility = compute_donation_eligibility($donor_id);
            if ($eligibility['success']) {
                $latest_completed_donation = $eligibility['latest_completed_donation'];
                $next_donation_date = $eligibility['next_donation_date'];
                $can_donate_now = $eligibility['can_donate_now'];
                $countdown_months = $eligibility['remaining_months'] ?? 0;
                $countdown_days = $eligibility['remaining_days'] ?? 0;
                
                // If countdown reaches zero, allow donation
                if ($countdown_months == 0 && $countdown_days == 0 && !$can_donate_now) {
                    $can_donate_now = true;
                }
                
                // Show days even when months>0, keep remainder approximation
                if ($countdown_months > 0) {
                    // If months were calculated, show remainder days
                    $countdown_days = $countdown_days % 30;
                }
            }
            
            // If no completed donation found in eligibility, use the latest donation
            if (!$latest_completed_donation && !empty($latest_donation)) {
                $latest_completed_donation = $latest_donation;
            }
            
            error_log("Donation History - Found " . count($donation_history) . " donations for donor_id: $donor_id");
            error_log("Donation History - Latest donation: " . json_encode($latest_donation));
        } else {
            error_log("Donation History - No donations found for donor_id: $donor_id");
        }
    } else {
        error_log("Donation History - No donor record found for email: $email");
    }

    // Fetch latest medical history record
    $medical_history_result = get_records('medical_history', [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'updated_at.desc',
        'limit' => 1
    ]);

    if ($medical_history_result['success'] && !empty($medical_history_result['data'])) {
        $medical_history_record = $medical_history_result['data'][0];
        $has_medical_history_record = true;
    }
}

$active_processing_statuses = [
    'registered',
    'sample collected',
    'sample_collected',
    'testing',
    'testing complete',
    'processed',
    'allocated',
    'stored'
];

if ($has_medical_history_record && $latest_donation_status && in_array($latest_donation_status, $active_processing_statuses)) {
    $donation_processing = true;
} elseif ($has_medical_history_record && !$latest_donation_status) {
    // Default to processing if a medical history exists but no donation record yet
    $donation_processing = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF0000">
    <title>Red Cross - Donation History</title>
    <!-- Resource hints for faster loading on slow connections -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="//fonts.gstatic.com" crossorigin>
    <!-- Preload critical resources -->
    <link rel="preload" href="../assets/css/styles.css" as="style">
    <link rel="preload" href="../assets/js/app.js" as="script">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="Red Cross Mobile Application - Donation History">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
            font-size: 16px;
            -webkit-tap-highlight-color: transparent;
        }
        
        .header {
            background-color: #FF0000;
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
            width: 100%;
            box-sizing: border-box;
            z-index: 100;
        }
        
        .logo-small {
            width: 40px;
            height: 40px;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            object-fit: contain;
            border-radius: 50%;
            background-color: white;
        }
        
        .header h1 {
            margin: 0;
            font-size: 20px;
            padding: 0 40px;
        }
        
        .history-container {
            padding: 15px;
            margin-bottom: 70px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* No Donations Card */
        .no-donations-card {
            background-color: white;
            border-radius: 15px;
            padding: 30px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .no-donations-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .no-donations-card h2 {
            color: #FF0000;
            margin: 0 0 15px 0;
            font-size: 24px;
        }
        
        .no-donations-card p {
            color: #666;
            margin: 0 0 25px 0;
            font-size: 16px;
        }
        
        .start-donation-btn {
            background-color: #FF0000;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .start-donation-btn:hover {
            background-color: #cc0000;
        }
        
        /* Last Donation Card */
        .last-donation-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .last-donation-card h3 {
            color: #FF0000;
            margin: 0 0 20px 0;
            font-size: 20px;
            text-align: center;
        }
        
        .donation-details {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row .label {
            color: #666;
            font-weight: 500;
        }
        
        .detail-row .value {
            color: #333;
            font-weight: bold;
        }
        
        .status-completed {
            color: #28a745 !important;
        }
        
        /* Countdown Card */
        .countdown-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .countdown-card h3 {
            color: #FF0000;
            margin: 0 0 20px 0;
            font-size: 20px;
        }
        
        .can-donate-now {
            padding: 20px 0;
        }
        
        .checkmark {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .can-donate-now p {
            color: #28a745;
            font-weight: bold;
            font-size: 18px;
            margin: 0 0 20px 0;
        }
        
        .donate-now-btn {
            background-color: #28a745;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .donate-now-btn:hover {
            background-color: #218838;
        }
        
        .countdown-timer {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }
        
        .timer-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 60px;
        }
        
        .timer-box .time {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            line-height: 1;
        }
        
        .timer-box .label {
            font-size: 14px;
            color: #6c757d;
            text-align: center;
            margin-top: 5px;
        }
        
        .next-donation-date {
            color: #6c757d;
            font-size: 14px;
            margin: 15px 0 0 0;
        }
        
        /* History List Card */
        .history-list-card {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .history-list-card h3 {
            color: #FF0000;
            margin: 0 0 20px 0;
            font-size: 20px;
            text-align: center;
        }
        
        .donation-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .donation-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #FF0000;
        }
        
        .donation-date {
            color: #333;
            font-weight: bold;
            font-size: 16px;
        }
        
        .donation-info {
            text-align: right;
        }
        
        .donation-status {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .donation-status.completed {
            color: #28a745;
        }
        
        .donation-status.pending {
            color: #ffc107;
        }
        
        .donation-site {
            color: #666;
            font-size: 14px;
        }
        
        .no-history {
            color: #666;
            text-align: center;
            font-style: italic;
            margin: 20px 0;
        }
        
        .navigation-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #000000;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
            height: 60px;
            box-sizing: border-box;
        }
        
        .nav-button {
            color: white;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 25%;
            padding: 5px 0;
            touch-action: manipulation;
            text-decoration: none;
        }
        
        .nav-button:active {
            opacity: 0.7;
        }
        
        .nav-icon {
            font-size: 24px;
            margin-bottom: 2px;
        }
        
        .nav-label {
            font-size: 10px;
            text-align: center;
        }
        
        .nav-button.active {
            color: #FF0000;
        }
        
        /* Modal Styles */
        .donation-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .donation-modal-overlay.active {
            display: flex;
        }
        
        .donation-modal {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            max-width: 400px;
            width: 100%;
            position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .donation-modal-header {
            position: relative;
            margin-bottom: 20px;
        }
        
        .donation-modal-title {
            color: #FF0000;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin: 0;
            padding: 0 30px;
        }
        
        .donation-modal-close {
            position: absolute;
            top: 0;
            right: 0;
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            transition: color 0.2s;
        }
        
        .donation-modal-close:hover {
            color: #FF0000;
        }
        
        .donation-modal-close:active {
            opacity: 0.7;
        }
        
        .donation-modal-details {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .donation-modal-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .donation-modal-detail-row:last-child {
            border-bottom: none;
        }
        
        .donation-modal-label {
            color: #666;
            font-weight: 500;
            margin: 0;
        }
        
        .donation-modal-value {
            color: #333;
            font-weight: bold;
            text-align: right;
            margin: 0;
        }
        
        .donation-modal-value.status-value {
            color:#ffc107 !important;
        }
        
        .donation-item {
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }
        
        .donation-item:active {
            transform: scale(0.98);
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../assets/icons/redcrosslogo.jpg" alt="Philippine Red Cross Logo" class="logo-small" width="40" height="40" loading="eager" fetchpriority="high">
        <h1>Donation History</h1>
    </div>
    
    <div class="history-container">
        <?php if (empty($donation_history)): ?>
            <!-- No donations yet -->
            <div class="no-donations-card">
                <div class="no-donations-icon">🩸</div>
                <h2>No Donations Yet</h2>
                <p>Start your blood donation journey today and save lives!</p>
                <?php if ($donation_processing): ?>
                    <p style="color:#FF0000;font-weight:bold;margin-top:15px;">Your current donation is being processed. Please wait for it to finish before starting a new one.</p>
                <?php else: ?>
                    <a href="blood_donation.php" class="start-donation-btn">Start Donating</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Last Donation Card -->
            <div class="last-donation-card">
                <h3>Last Donation</h3>
                <div class="donation-details">
                    <div class="detail-row">
                        <span class="label">Date:</span>
                        <span class="value"><?php echo $latest_completed_donation ? date('M j, Y', strtotime($latest_completed_donation['created_at'])) : 'N/A'; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Blood Type:</span>
                        <span class="value"><?php echo htmlspecialchars($latest_completed_donation['blood_type'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Units Collected:</span>
                        <span class="value"><?php echo htmlspecialchars($latest_completed_donation['units_collected'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Donation Site:</span>
                        <span class="value">PRC Iloilo Chapter</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status:</span>
                        <span class="value status-completed"><?php 
                            if ($latest_completed_donation && $latest_completed_donation['current_status'] === 'Processed') {
                                echo 'Ready for Use';
                            } elseif ($latest_completed_donation && $latest_completed_donation['current_status'] === 'Ready for Use') {
                                echo 'Used';
                            } else {
                                echo htmlspecialchars(ucfirst($latest_completed_donation['current_status'] ?? 'Pending'));
                            }
                        ?></span>
                    </div>
                </div>
            </div>

            <!-- Next Donation Countdown Card -->
            <div class="countdown-card">
                <h3>When You Can Donate Next</h3>
                <?php if ($donation_processing): ?>
                    <div class="can-donate-now">
                        <div class="checkmark">⌛</div>
                        <p style="color:#FF0000;">Your donation is currently being processed.</p>
                        <p style="color:#666;font-size:14px;">Please wait until the ongoing donation is finished before donating again.</p>
                    </div>
                <?php elseif ($can_donate_now || ($countdown_months == 0 && $countdown_days == 0)): ?>
                    <div class="can-donate-now">
                        <div class="checkmark">✓</div>
                        <p>You can donate blood now!</p>
                        <a href="blood_donation.php" class="donate-now-btn">Donate Now</a>
                    </div>
                <?php else: ?>
                    <div class="countdown-timer">
                        <?php if ($countdown_months > 0): ?>
                            <div class="timer-box">
                                <span class="time"><?php echo $countdown_months; ?></span>
                                <span class="label"><?php echo $countdown_months == 1 ? 'Month' : 'Months'; ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($countdown_days > 0 || $countdown_months == 0): ?>
                            <div class="timer-box">
                                <span class="time"><?php echo $countdown_days; ?></span>
                                <span class="label"><?php echo $countdown_days == 1 ? 'Day' : 'Days'; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($next_donation_date): ?>
                        <p class="next_donation-date">Next eligible date: <?php echo date('F j, Y', strtotime($next_donation_date)); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Donation History List -->
            <div class="history-list-card">
                <h3>Donation History</h3>
                <?php if (count($donation_history) > 0): ?>
                    <div class="donation-list">
                        <?php foreach ($donation_history as $index => $donation): ?>
                            <div class="donation-item" 
                                 data-date="<?php echo htmlspecialchars(date('M j, Y', strtotime($donation['created_at']))); ?>"
                                 data-blood-type="<?php echo htmlspecialchars($donation['blood_type'] ?? 'N/A'); ?>"
                                 data-units="<?php echo htmlspecialchars($donation['units_collected'] ?? 'N/A'); ?>"
                                 data-status="<?php echo htmlspecialchars($donation['current_status'] ?? 'Pending'); ?>">
                                <div class="donation-date">
                                    <?php echo date('M j, Y', strtotime($donation['created_at'])); ?>
                                </div>
                                <div class="donation-info">
                                    <div class="donation-status <?php echo $donation['current_status'] === 'Processed' ? 'completed' : 'pending'; ?>">
                                        <?php 
                                            if ($donation['current_status'] === 'Processed') {
                                                echo 'Ready for Use';
                                            } elseif ($donation['current_status'] === 'Ready for Use') {
                                                echo 'Used';
                                            } else {
                                                echo ucfirst($donation['current_status']);
                                            }
                                        ?>
                                    </div>
                                    <div class="donation-site">
                                        <?php echo htmlspecialchars($donation['blood_type'] ?? 'N/A'); ?> • 
                                        <?php echo htmlspecialchars($donation['units_collected'] ?? 'N/A'); ?> units
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-history">No donation history available.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Donation Details Modal -->
    <div class="donation-modal-overlay" id="donationModal">
        <div class="donation-modal">
            <div class="donation-modal-header">
                <h2 class="donation-modal-title">Donation History</h2>
                <button class="donation-modal-close" id="closeModal" aria-label="Close">&times;</button>
            </div>
            <div class="donation-modal-details" id="modalDetails">
                <div class="donation-modal-detail-row">
                    <span class="donation-modal-label">Date:</span>
                    <span class="donation-modal-value" id="modalDate"></span>
                </div>
                <div class="donation-modal-detail-row">
                    <span class="donation-modal-label">Blood Type:</span>
                    <span class="donation-modal-value" id="modalBloodType"></span>
                </div>
                <div class="donation-modal-detail-row">
                    <span class="donation-modal-label">Units Collected:</span>
                    <span class="donation-modal-value" id="modalUnits"></span>
                </div>
                <div class="donation-modal-detail-row">
                    <span class="donation-modal-label">Donation Site:</span>
                    <span class="donation-modal-value" id="modalSite">PRC Iloilo Chapter</span>
                </div>
                <div class="donation-modal-detail-row">
                    <span class="donation-modal-label">Status:</span>
                    <span class="donation-modal-value status-value" id="modalStatus"></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile-optimized bottom navigation bar -->
    <div class="navigation-bar">
        <a href="dashboard.php" class="nav-button">
            <div class="nav-icon">🏠</div>
            <div class="nav-label">Home</div>
        </a>
        <a href="explore.php" class="nav-button">
            <div class="nav-icon">🔍</div>
            <div class="nav-label">Discover</div>
        </a>
        <a href="profile.php" class="nav-button">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/app.js" defer></script>
    <!-- Register Service Worker for PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                // Determine correct path based on current location
                const getBasePath = function() {
                    const pathname = window.location.pathname;
                    const marker = '/mobile-app/';
                    const idx = pathname.indexOf(marker);
                    if (idx !== -1) {
                        return pathname.substring(0, idx + marker.length);
                    }
                    return '/mobile-app/';
                };
                
                const basePath = getBasePath();
                const swPath = basePath + 'service-worker.js';
                
                navigator.serviceWorker.register(swPath, {
                    scope: basePath
                })
                .then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                })
                .catch(function(error) {
                    // Only log if it's not a 404 (file might not exist in some environments)
                    if (error.message && !error.message.includes('404') && !error.message.includes('bad HTTP response code')) {
                        console.warn('ServiceWorker registration warning: ', error.message);
                    }
                });
            });
        }
    </script>
    <!-- Donation Modal Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('donationModal');
            const closeModalBtn = document.getElementById('closeModal');
            const donationItems = document.querySelectorAll('.donation-item');
            
            // Function to format status
            function formatStatus(status) {
                if (status === 'Processed') {
                    return 'Ready for Use';
                } else if (status === 'Ready for Use') {
                    return 'Used';
                } else {
                    return status.charAt(0).toUpperCase() + status.slice(1);
                }
            }
            
            // Function to open modal with donation data
            function openModal(donationItem) {
                const date = donationItem.getAttribute('data-date');
                const bloodType = donationItem.getAttribute('data-blood-type');
                const units = donationItem.getAttribute('data-units');
                const status = donationItem.getAttribute('data-status');
                
                // Set modal content - date is already formatted in PHP, just use it directly
                document.getElementById('modalDate').textContent = date;
                document.getElementById('modalBloodType').textContent = bloodType;
                document.getElementById('modalUnits').textContent = units;
                document.getElementById('modalStatus').textContent = formatStatus(status);
                
                // Show modal
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
            
            // Function to close modal
            function closeModal() {
                modal.classList.remove('active');
                document.body.style.overflow = ''; // Restore scrolling
            }
            
            // Add click event listeners to donation items
            donationItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    openModal(item);
                });
            });
            
            // Close modal when clicking close button
            closeModalBtn.addEventListener('click', function() {
                closeModal();
            });
            
            // Close modal when clicking outside the modal content
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
            
            // Close modal when pressing Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html> 