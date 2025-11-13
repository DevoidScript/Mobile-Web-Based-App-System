<?php
/**
 * Blood Donation Page for the Red Cross Mobile App
 * 
 * This is a placeholder page for the blood donation functionality.
 * It will be implemented in future updates with appointment scheduling features.
 * Created as part of the templates organization structure.
 *
 * Path: templates/blood_donation.php
 * 
 * WORKFLOW REDIRECTION UPDATE:
 * The "Start Donation Process" button now points to blood-session.php which acts as 
 * an entry point for the donor registration process. The complete workflow is:
 * 
 * 1. blood_donation.php (user clicks "Start Donation Process")
 * 2. blood-session.php (initializes session and workflow)
 * 3. donor-form-modal.php (collects basic donor information)
 * 4. medical-history-modal.php (medical history questionnaire)
 * 5. declaration-form-modal.php (final declaration and consent)
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
    header('Location: ../index.php?error=Please login to access the blood donation page');
    exit;
}

// Get user data
$user = $_SESSION['user'] ?? null;

// Get donor_id from session first (needed for POST handler)
$donor_id = $_SESSION['donor_id'] ?? null;

// Handle start of donation process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_donation'])) {
    // CRITICAL FIX: Ensure donor_id is in session before redirecting
    if (!$donor_id && $user && isset($user['email'])) {
        $email = trim(strtolower($user['email']));
        $donorFormResp = get_records('donor_form', ['email' => 'eq.' . $email]);
        if ($donorFormResp['success'] && !empty($donorFormResp['data'])) {
            $donor_id = $donorFormResp['data'][0]['donor_id'];
            if ($donor_id) {
                $_SESSION['donor_id'] = $donor_id;
                error_log("Set donor_id in session before redirecting to medical history: " . $donor_id);
            }
        }
    }
    
    // Validate that we have donor_id before redirecting
    if (!$donor_id) {
        error_log("ERROR: Cannot start donation - donor_id not found for user: " . ($user['email'] ?? 'N/A'));
        $_SESSION['error_message'] = "Unable to find your donor record. Please complete your donor registration first.";
        header('Location: blood_donation.php?error=Missing donor record');
        exit;
    }
    
    // Redirect to medical history form with donor_id in session
    header('Location: forms/medical-history-modal.php');
    exit;
}

// Get donor_id from session (if not already set above)
if (!isset($donor_id)) {
    $donor_id = $_SESSION['donor_id'] ?? null;
}
$eligibility = null;
if ($user && !$donor_id && isset($user['email'])) {
    // Derive donor_id if not in session
    $email = trim(strtolower($user['email']));
    $donorFormResp = get_records('donor_form', ['email' => 'eq.' . $email]);
    if ($donorFormResp['success'] && !empty($donorFormResp['data'])) {
        $donor_id = $donorFormResp['data'][0]['donor_id'];
        // CRITICAL FIX: Store donor_id in session for use in medical history form
        if ($donor_id) {
            $_SESSION['donor_id'] = $donor_id;
            error_log("Stored donor_id in session from blood_donation.php: " . $donor_id);
        }
    }
}
if ($donor_id) {
    $eligibility = compute_donation_eligibility($donor_id);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF0000">
    <title>Red Cross - Blood Donation</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="manifest" href="../manifest.json">
    <link rel="apple-touch-icon" href="../assets/icons/icon-192x192.png">
    <!-- PWA meta tags -->
    <meta name="description" content="Red Cross Mobile Application - Blood Donation">
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
        
        .donation-container {
            padding: 15px;
            margin-bottom: 70px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .message-box {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .message-box h2 {
            color: #FF0000;
            margin-top: 0;
        }
        
        .coming-soon-img {
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
            display: block;
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
            width: 33.33%;
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
    </style>
</head>
<body>
    <div class="header">
        <img src="../assets/icons/redcrosslogo.jpg" alt="Philippine Red Cross Logo" class="logo-small">
        <h1>Blood Donation</h1>
    </div>
    
    <div class="donation-container">
        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message-box" style="border:2px solid #28a745; background:#d4edda; color:#155724; padding:15px; margin-bottom:20px; border-radius:8px; max-width:400px; margin-left:auto; margin-right:auto;">
                <strong>✓ Success:</strong> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message-box" style="border:2px solid #dc3545; background:#f8d7da; color:#721c24; padding:15px; margin-bottom:20px; border-radius:8px; max-width:400px; margin-left:auto; margin-right:auto;">
                <strong>✗ Error:</strong> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['warning_message'])): ?>
            <div class="message-box" style="border:2px solid #ffc107; background:#fff3cd; color:#856404; padding:15px; margin-bottom:20px; border-radius:8px; max-width:400px; margin-left:auto; margin-right:auto;">
                <strong>⚠ Warning:</strong> <?php echo htmlspecialchars($_SESSION['warning_message']); unset($_SESSION['warning_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="message-box" style="border:2px solid #dc3545; background:#f8d7da; color:#721c24; padding:15px; margin-bottom:20px; border-radius:8px; max-width:400px; margin-left:auto; margin-right:auto;">
                <strong>✗ Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="message-box" style="border:1.5px solid #ccc; max-width:400px; margin:30px auto 0 auto; padding:32px 16px 20px 16px; border-radius:12px; background:#fff;">
            <img src="../assets/icons/redcrosslogo.jpg" alt="Red Cross Logo" class="coming-soon-img" style="display:block;margin:0 auto 18px auto;max-width:120px;">
            <div style="font-size:1.2rem; font-weight:500; color:#444; text-align:center; margin-bottom:18px;">To donate blood, start by answering the medical history questionnaire.</div>
            <div style="font-size:1rem; color:#666; text-align:center; font-style:italic; margin-bottom:18px;">Click the button below to fill out your Medical History Form.</div>
            <?php 
                $disabled = false; 
                $tooltip = '';
                if ($eligibility && $eligibility['latest_completed_donation']) {
                    // Disable if still waiting until next eligible date
                    $disabled = !$eligibility['can_donate_now'];
                    if ($disabled && !empty($eligibility['next_donation_date'])) {
                        $tooltip = 'Next eligible: ' . date('F j, Y', strtotime($eligibility['next_donation_date']));
                    }
                }
            ?>
            <a href="<?php echo $disabled ? '#' : 'forms/medical-history-modal.php'; ?>" 
               class="btn btn-primary" 
               <?php echo $disabled ? 'aria-disabled="true"' : ''; ?>
               style="display:inline-block;margin-top:12px;padding:0;font-size:18px;font-weight:bold;border-radius:10px;overflow:hidden;"
               onclick="<?php echo $disabled ? 'return false;' : 'return true;'; ?>">
                <span style="background:#d50000;color:#fff;border:none;display:inline-block;padding:14px 28px;box-shadow:0 2px 8px rgba(213,0,0,0.08);transition:background 0.2s;cursor:pointer;text-decoration:none;">
                    <?php echo $disabled ? 'Donation Not Yet Available' : 'Start Donation Process'; ?>
                </span>
            </a>
            <?php if ($disabled && $tooltip): ?>
                <div style="margin-top:8px;color:#666;font-size:0.9rem;"><?php echo htmlspecialchars($tooltip); ?></div>
            <?php endif; ?>
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
            <div class="nav-label">Explore</div>
        </a>
        <a href="profile.php" class="nav-button">
            <div class="nav-icon">👤</div>
            <div class="nav-label">Profile</div>
        </a>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/app.js"></script>
    <!-- Register Service Worker for PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../service-worker.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('ServiceWorker registration failed: ', error);
                    });
            });
        }
    </script>
</body>
</html> 