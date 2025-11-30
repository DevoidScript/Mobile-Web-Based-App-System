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
        
        // ROOT CAUSE FIX: Use eligibility table as primary source (matching admin dashboard logic)
        // Get latest eligibility record to determine actual donation status
        $eligibility_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'collection_start_time.desc,created_at.desc',
            'limit' => 1
        ];
        
        $eligibility_result = get_records('eligibility', $eligibility_params);
        $eligibility_record = null;
        $eligibility_status_map = [];
        
        if ($eligibility_result['success'] && !empty($eligibility_result['data'])) {
            $eligibility_record = $eligibility_result['data'][0];
            $elig_status = strtolower(trim($eligibility_record['status'] ?? ''));
            $collection_successful = isset($eligibility_record['collection_successful']) && 
                                     ($eligibility_record['collection_successful'] === true || 
                                      $eligibility_record['collection_successful'] === 'true' || 
                                      $eligibility_record['collection_successful'] === 1);
            $has_blood_collection_id = !empty($eligibility_record['blood_collection_id'] ?? null);
            
            // Map eligibility to donation status (matching admin dashboard logic)
            if (($elig_status === 'approved' || $elig_status === 'eligible') && $has_blood_collection_id) {
                $eligibility_status_map[$donor_id] = 'Processed';
            } elseif ($collection_successful && $has_blood_collection_id) {
                $eligibility_status_map[$donor_id] = 'Processed';
            } elseif ($has_blood_collection_id) {
                $eligibility_status_map[$donor_id] = 'Testing';
            }
            
            error_log("Donation History - Eligibility record found: status={$elig_status}, collection_successful=" . var_export($collection_successful, true) . ", has_blood_collection_id=" . var_export($has_blood_collection_id, true));
        }
        
        // Get donation history from donations table, ordered by last_updated to get most recent
        $donation_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'last_updated.desc'
        ];
        
        $donation_result = get_records('donations', $donation_params);
        if ($donation_result['success'] && !empty($donation_result['data'])) {
            $donation_history = $donation_result['data'];
            
            // Get the latest donation (first in the array since we ordered by desc)
            $latest_donation = $donation_history[0];
            $latest_donation_id = $latest_donation['donation_id'];
            
            // PRIORITY 1: Check blood_bank_units for handed_over_at/disposed_at (highest priority - indicates Used status)
            $blood_bank_check_params = [
                'donor_id' => 'eq.' . $donor_id,
                'order' => 'created_at.desc',
                'limit' => 1
            ];
            $blood_bank_check_result = get_records('blood_bank_units', $blood_bank_check_params);
            $status_from_blood_bank = null;
            
            if ($blood_bank_check_result['success'] && !empty($blood_bank_check_result['data'])) {
                $bb_check_unit = $blood_bank_check_result['data'][0];
                $handed_over_at = $bb_check_unit['handed_over_at'] ?? null;
                $disposed_at = $bb_check_unit['disposed_at'] ?? null;
                $bb_status = strtolower(trim($bb_check_unit['status'] ?? ''));
                
                if (!empty($disposed_at)) {
                    $status_from_blood_bank = 'Used'; // Will show as Expired in history
                    error_log("Donation History - Unit disposed at {$disposed_at}, setting status to Used");
                } elseif (!empty($handed_over_at)) {
                    $status_from_blood_bank = 'Used';
                    error_log("Donation History - Unit handed over at {$handed_over_at}, setting status to Used");
                } elseif ($bb_status === 'used' || $bb_status === 'transfused' || $bb_status === 'buffer') {
                    // Buffer is used by admin system as a way to update the blood bank, so it should be treated as Used
                    $status_from_blood_bank = 'Used';
                    error_log("Donation History - Unit status is {$bb_status}, setting status to Used");
                } elseif ($bb_status === 'stored') {
                    $status_from_blood_bank = 'Stored';
                } elseif ($bb_status === 'allocated') {
                    $status_from_blood_bank = 'Allocated';
                }
            }
            
            // Use status from blood_bank_units if available, otherwise use eligibility status, then fallback to history
            if ($status_from_blood_bank) {
                $latest_donation['current_status'] = $status_from_blood_bank;
                error_log("Donation History - Using status from blood_bank_units: {$status_from_blood_bank}");
            } elseif (isset($eligibility_status_map[$donor_id])) {
                $latest_donation['current_status'] = $eligibility_status_map[$donor_id];
                error_log("Donation History - Using status from eligibility table: {$eligibility_status_map[$donor_id]}");
            } else {
                // Fallback: Verify current_status against donation_status_history
                $history_params = [
                    'donation_id' => 'eq.' . $latest_donation_id,
                    'order' => 'changed_at.desc',
                    'limit' => 1
                ];
                
                $history_result = get_records('donation_status_history', $history_params);
                if ($history_result['success'] && !empty($history_result['data'])) {
                    $latest_history = $history_result['data'][0];
                    $history_status = $latest_history['status'] ?? null;
                    
                    if ($history_status && $history_status !== $latest_donation['current_status']) {
                        error_log("Donation History - Status mismatch for donation_id={$latest_donation_id}: donations.current_status={$latest_donation['current_status']}, history.status={$history_status}. Using history status.");
                        $latest_donation['current_status'] = $history_status;
                    }
                }
            }
            
            // Sync blood_type - Priority: 1) Eligibility table, 2) Screening form, 3) Donation record
            if (empty($latest_donation['blood_type'])) {
                if ($eligibility_record && !empty($eligibility_record['blood_type'])) {
                    $latest_donation['blood_type'] = $eligibility_record['blood_type'];
                    error_log("Donation History - Synced blood_type from eligibility: " . $eligibility_record['blood_type']);
                } else {
                    // Check screening form for blood_type
                    $donor_form_params = ['donor_id' => 'eq.' . $donor_id];
                    $donor_form_result = get_records('donor_form', $donor_form_params);
                    if ($donor_form_result['success'] && !empty($donor_form_result['data'])) {
                        $donor_form = $donor_form_result['data'][0];
                        $donor_form_id = $donor_form['donor_id'];
                        
                        $screening_params = [
                            'donor_form_id' => 'eq.' . $donor_form_id,
                            'order' => 'created_at.desc',
                            'limit' => 1
                        ];
                        
                        $screening_result = get_records('screening_form', $screening_params);
                        if ($screening_result['success'] && !empty($screening_result['data'])) {
                            $screening = $screening_result['data'][0];
                            if (!empty($screening['blood_type'])) {
                                $latest_donation['blood_type'] = $screening['blood_type'];
                                error_log("Donation History - Synced blood_type from screening: " . $screening['blood_type']);
                            }
                        }
                    }
                }
            }
            
            // Get blood_bank_units data for all donations to determine actual status
            $blood_bank_params = [
                'donor_id' => 'eq.' . $donor_id,
                'order' => 'created_at.desc'
            ];
            
            $blood_bank_result = get_records('blood_bank_units', $blood_bank_params);
            $blood_bank_lookup = [];
            
            if ($blood_bank_result['success'] && !empty($blood_bank_result['data'])) {
                // Create lookup by donor_id (since we can't directly link to donation_id)
                // Use the most recent blood_bank_unit for this donor
                $blood_bank_lookup[$donor_id] = $blood_bank_result['data'][0];
                error_log("Donation History - Found " . count($blood_bank_result['data']) . " blood_bank_units records for donor_id: $donor_id");
            }
            
            // Update all donations in history with verified statuses from blood_bank_units, eligibility, or history
            foreach ($donation_history as $idx => $donation) {
                $don_id = $donation['donation_id'];
                $final_status = $donation['current_status'];
                $status_notes = '';
                
                // Priority 1: Check blood_bank_units (most accurate for current status)
                if (isset($blood_bank_lookup[$donor_id])) {
                    $bb_unit = $blood_bank_lookup[$donor_id];
                    $bb_status = strtolower(trim($bb_unit['status'] ?? ''));
                    $handed_over_at = $bb_unit['handed_over_at'] ?? null;
                    $disposed_at = $bb_unit['disposed_at'] ?? null;
                    $hospital_from = $bb_unit['hospital_from'] ?? null;
                    
                    // Check disposed first (highest priority)
                    if (!empty($disposed_at)) {
                        $final_status = 'Expired';
                        $status_notes = ' (Disposed)';
                        error_log("Donation History - Donation {$don_id}: Unit disposed at {$disposed_at}");
                    }
                    // Check handed over
                    elseif (!empty($handed_over_at)) {
                        $final_status = 'Used';
                        if (!empty($hospital_from)) {
                            $status_notes = ' - Sent to ' . htmlspecialchars($hospital_from);
                        }
                        error_log("Donation History - Donation {$don_id}: Unit handed over at {$handed_over_at} to " . ($hospital_from ?? 'N/A'));
                    }
                    // Check status field
                    elseif ($bb_status === 'stored') {
                        $final_status = 'Stored';
                    } elseif ($bb_status === 'allocated') {
                        $final_status = 'Allocated';
                    } elseif ($bb_status === 'used' || $bb_status === 'transfused') {
                        $final_status = 'Used';
                        if (!empty($hospital_from)) {
                            $status_notes = ' - Sent to ' . htmlspecialchars($hospital_from);
                        }
                    } elseif ($bb_status === 'buffer') {
                        // Buffer is used by admin system as a way to update the blood bank, so it should be treated as Used
                        $final_status = 'Used';
                        error_log("Donation History - Donation {$don_id}: Unit status is Buffer - mapped to Used");
                    } elseif ($bb_status === 'processed' || $bb_status === 'valid' || empty($bb_status)) {
                        $final_status = 'Processed';
                    }
                }
                // Priority 2: Use eligibility status if available
                elseif (isset($eligibility_status_map[$donor_id])) {
                    $final_status = $eligibility_status_map[$donor_id];
                }
                // Priority 3: Fallback to donation_status_history
                else {
                    $hist_params = [
                        'donation_id' => 'eq.' . $don_id,
                        'order' => 'changed_at.desc',
                        'limit' => 1
                    ];
                    
                    $hist_result = get_records('donation_status_history', $hist_params);
                    if ($hist_result['success'] && !empty($hist_result['data'])) {
                        $hist = $hist_result['data'][0];
                        $hist_status = $hist['status'] ?? null;
                        if ($hist_status && $hist_status !== $donation['current_status']) {
                            $final_status = $hist_status;
                        }
                    }
                }
                
                $donation_history[$idx]['current_status'] = $final_status;
                $donation_history[$idx]['status_notes'] = $status_notes;
                
                // Update latest_donation if this is the latest one (first in array)
                if ($idx === 0) {
                    $latest_donation['current_status'] = $final_status;
                }
                
                // Sync blood_type from eligibility if missing
                if (empty($donation_history[$idx]['blood_type']) && $eligibility_record && !empty($eligibility_record['blood_type'])) {
                    $donation_history[$idx]['blood_type'] = $eligibility_record['blood_type'];
                }
            }
            
            // Update latest_donation_status to reflect the actual status from blood_bank_units
            // This ensures "Used", "Expired", etc. are properly recognized for donation_processing check
            if (!empty($latest_donation)) {
                $latest_donation_status = strtolower(trim($latest_donation['current_status'] ?? ''));
                error_log("Donation History - Latest donation status after blood_bank_units update: " . $latest_donation_status);
            }
            
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
                
                error_log("Donation History - Eligibility computed: can_donate_now=" . var_export($can_donate_now, true) . ", months={$countdown_months}, days={$countdown_days}, next_date=" . ($next_donation_date ?? 'NULL'));
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

// Completed statuses - these should show countdown (blood has been collected)
// These include: Stored, Allocated (blood is ready/allocated), Used, Expired, etc.
$completed_statuses = [
    'stored',
    'allocated',
    'used',
    'expired',
    'ready for use',
    'transfused',
    'processed'  // Processed also means blood collection is complete
];

// Active processing statuses - these should show as "processing" (blood not yet collected/stored)
$active_processing_statuses = [
    'registered',
    'sample collected',
    'sample_collected',
    'testing',
    'testing complete'
];

// Only show as processing if:
// 1. Has medical history AND
// 2. Latest donation status is in active_processing_statuses AND
// 3. Latest donation status is NOT in completed_statuses (Stored, Allocated, Used, Expired, etc.)
if ($has_medical_history_record && $latest_donation_status) {
    $is_completed = in_array($latest_donation_status, $completed_statuses);
    $is_processing = in_array($latest_donation_status, $active_processing_statuses);
    
    // Show as processing only if it's actively processing AND not completed
    if ($is_processing && !$is_completed) {
        $donation_processing = true;
    } elseif ($is_completed) {
        // If completed (Stored, Allocated, Used, Expired, etc.), don't show as processing - show countdown instead
        $donation_processing = false;
        error_log("Donation History - Status '{$latest_donation_status}' is completed, showing countdown instead of processing message");
    }
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
                            $display_status = $latest_completed_donation['current_status'] ?? 'Pending';
                            $status_notes = $latest_completed_donation['status_notes'] ?? '';
                            
                            // Format status display
                            if ($display_status === 'Expired') {
                                echo 'Expired';
                            } elseif ($display_status === 'Used') {
                                echo 'Used' . $status_notes;
                            } elseif ($display_status === 'Processed') {
                                echo 'Ready for Use';
                            } elseif ($display_status === 'Ready for Use') {
                                echo 'Used';
                            } else {
                                echo htmlspecialchars(ucfirst($display_status));
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
                                 data-status="<?php echo htmlspecialchars($donation['current_status'] ?? 'Pending'); ?>"
                                 data-status-notes="<?php echo htmlspecialchars($donation['status_notes'] ?? ''); ?>">
                                <div class="donation-date">
                                    <?php echo date('M j, Y', strtotime($donation['created_at'])); ?>
                                </div>
                                <div class="donation-info">
                                    <div class="donation-status <?php 
                                        $donation_status = $donation['current_status'] ?? 'Pending';
                                        echo (in_array($donation_status, ['Processed', 'Stored', 'Allocated', 'Used', 'Expired']) ? 'completed' : 'pending'); 
                                    ?>">
                                        <?php 
                                            $status_notes = $donation['status_notes'] ?? '';
                                            if ($donation_status === 'Expired') {
                                                echo 'Expired';
                                            } elseif ($donation_status === 'Used') {
                                                echo 'Used' . $status_notes;
                                            } elseif ($donation_status === 'Processed') {
                                                echo 'Ready for Use';
                                            } elseif ($donation_status === 'Ready for Use') {
                                                echo 'Used';
                                            } elseif ($donation_status === 'Stored') {
                                                echo 'Stored';
                                            } elseif ($donation_status === 'Allocated') {
                                                echo 'Allocated';
                                            } else {
                                                echo ucfirst($donation_status);
                                            }
                                        ?>
                                    </div>
                                    <div class="donation-site">
                                        <?php echo htmlspecialchars($donation['blood_type'] ?? 'N/A'); ?> • 
                                        <?php 
                                            $units = $donation['units_collected'] ?? 'N/A';
                                            $unit_text = ($units == 1) ? 'unit' : 'units';
                                            echo htmlspecialchars($units) . ' ' . $unit_text;
                                        ?>
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
            function formatStatus(status, statusNotes) {
                if (status === 'Expired') {
                    return 'Expired';
                } else if (status === 'Used') {
                    return 'Used' + (statusNotes || '');
                } else if (status === 'Processed') {
                    return 'Ready for Use';
                } else if (status === 'Ready for Use') {
                    return 'Used';
                } else if (status === 'Stored' || status === 'Allocated') {
                    return status;
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
                const statusNotes = donationItem.getAttribute('data-status-notes') || '';
                
                // Set modal content - date is already formatted in PHP, just use it directly
                document.getElementById('modalDate').textContent = date;
                document.getElementById('modalBloodType').textContent = bloodType;
                document.getElementById('modalUnits').textContent = units;
                document.getElementById('modalStatus').textContent = formatStatus(status, statusNotes);
                
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