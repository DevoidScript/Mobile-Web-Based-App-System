<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$user = $_SESSION['user'];

// Get donor ID the same way medical-history-modal.php does
$donor_id = null;
if (isset($_SESSION['user']['email'])) {
    $email = trim(strtolower($_SESSION['user']['email']));
    
    // Fetch donor_form record by email
    $donorFormResp = get_records('donor_form', ['email' => 'eq.' . $email]);
    if ($donorFormResp['success'] && !empty($donorFormResp['data'])) {
        $donorForm = $donorFormResp['data'][0];
        $donor_id = $donorForm['donor_id'];
        
        // Debug logging
        error_log("Blood Tracker - Found donor record for email: $email, donor_id: $donor_id");
    } else {
        error_log("Blood Tracker - No donor record found for email: $email");
    }
}

// If we still don't have a donor_id, fall back to user id
if (!$donor_id) {
    $donor_id = $user['donor_id'] ?? $user['id'];
    error_log("Blood Tracker - Using fallback donor_id: $donor_id");
}

// Get current donation from your donations table
$tracker_data = null;
$eligibility = null;
$blood_collection_data = null;
if ($donor_id) {
    // ROOT CAUSE FIX: Use eligibility table as primary source (matching admin dashboard logic)
    // Admin dashboard checks eligibility table with collection_successful=true and blood_collection_id not null
    $eligibility_params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'collection_start_time.desc,created_at.desc',
        'limit' => 1
    ];
    
    $eligibility_result = get_records('eligibility', $eligibility_params);
    $eligibility_record = null;
    $donation_status_from_eligibility = null;
    
    if ($eligibility_result['success'] && !empty($eligibility_result['data'])) {
        $eligibility_record = $eligibility_result['data'][0];
        $elig_status = strtolower(trim($eligibility_record['status'] ?? ''));
        $collection_successful = isset($eligibility_record['collection_successful']) && 
                                 ($eligibility_record['collection_successful'] === true || 
                                  $eligibility_record['collection_successful'] === 'true' || 
                                  $eligibility_record['collection_successful'] === 1);
        $has_blood_collection_id = !empty($eligibility_record['blood_collection_id'] ?? null);
        
        // Determine status from eligibility (matching admin dashboard logic)
        if (($elig_status === 'approved' || $elig_status === 'eligible') && $has_blood_collection_id) {
            $donation_status_from_eligibility = 'Processed';
        } elseif ($collection_successful && $has_blood_collection_id) {
            $donation_status_from_eligibility = 'Processed';
        } elseif ($has_blood_collection_id) {
            // Has blood collection but not yet approved - could be in testing
            $donation_status_from_eligibility = 'Testing';
        }
        
        error_log("Blood Tracker - Eligibility record found: status={$elig_status}, collection_successful=" . var_export($collection_successful, true) . ", has_blood_collection_id=" . var_export($has_blood_collection_id, true) . ", derived_status={$donation_status_from_eligibility}");
    }
    
    // Get donation record (secondary source)
    $params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'last_updated.desc',
        'limit' => 1
    ];

    $result = get_records('donations', $params);
    if ($result['success'] && !empty($result['data'])) {
        $donation = $result['data'][0];
        $donation_id = $donation['donation_id'];
        
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
                error_log("Blood Tracker - Unit disposed at {$disposed_at}, setting status to Used");
            } elseif (!empty($handed_over_at)) {
                $status_from_blood_bank = 'Used';
                error_log("Blood Tracker - Unit handed over at {$handed_over_at}, setting status to Used");
                } elseif ($bb_status === 'used' || $bb_status === 'transfused' || $bb_status === 'buffer') {
                    // Buffer is used by admin system as a way to update the blood bank, so it should be treated as Used
                    $status_from_blood_bank = 'Used';
                    error_log("Blood Tracker - Unit status is {$bb_status}, setting status to Used");
                } elseif ($bb_status === 'stored') {
                    $status_from_blood_bank = 'Stored';
                } elseif ($bb_status === 'allocated') {
                    $status_from_blood_bank = 'Allocated';
                }
        }
        
        // Use status from blood_bank_units if available, otherwise use eligibility status, then fallback to history
        if ($status_from_blood_bank) {
            $donation['current_status'] = $status_from_blood_bank;
            error_log("Blood Tracker - Using status from blood_bank_units: {$status_from_blood_bank}");
        } elseif ($donation_status_from_eligibility) {
            $donation['current_status'] = $donation_status_from_eligibility;
            error_log("Blood Tracker - Using status from eligibility table: {$donation_status_from_eligibility}");
        } else {
            // Fallback: Get the latest status from donation_status_history
            $history_params = [
                'donation_id' => 'eq.' . $donation_id,
                'order' => 'changed_at.desc',
                'limit' => 1
            ];
            
            $history_result = get_records('donation_status_history', $history_params);
            if ($history_result['success'] && !empty($history_result['data'])) {
                $latest_history = $history_result['data'][0];
                $history_status = $latest_history['status'] ?? null;
                
                if ($history_status && $history_status !== $donation['current_status']) {
                    error_log("Blood Tracker - Status mismatch: donations.current_status={$donation['current_status']}, history.status={$history_status}. Using history status.");
                    $donation['current_status'] = $history_status;
                }
            }
        }
        
        // Get blood type - Priority: 1) Eligibility table, 2) Current donation, 3) Previous donations
        $donor_blood_type = null;
        
        if ($eligibility_record && !empty($eligibility_record['blood_type'])) {
            $donor_blood_type = $eligibility_record['blood_type'];
            error_log("Blood Tracker - Using blood type from eligibility table: " . $donor_blood_type);
        } elseif (!empty($donation['blood_type'])) {
            $donor_blood_type = $donation['blood_type'];
            error_log("Blood Tracker - Using blood type from current donation: " . $donor_blood_type);
        } else {
            // Get all donations for this donor to find the most recent one with blood_type
            $all_donations_params = [
                'donor_id' => 'eq.' . $donor_id,
                'order' => 'last_updated.desc'
            ];
            
            $all_donations_result = get_records('donations', $all_donations_params);
            if ($all_donations_result['success'] && !empty($all_donations_result['data'])) {
                foreach ($all_donations_result['data'] as $past_donation) {
                    if (!empty($past_donation['blood_type'])) {
                        $donor_blood_type = $past_donation['blood_type'];
                        error_log("Blood Tracker - Using blood type from previous donation: " . $donor_blood_type);
                        break;
                    }
                }
            }
        }
        
        // Override the blood_type in the donation data
        if ($donor_blood_type) {
            $donation['blood_type'] = $donor_blood_type;
        }
        
        // Log the status before building tracker data
        error_log("Blood Tracker - Donation status before build_tracker_data: " . ($donation['current_status'] ?? 'NULL'));
        
        $tracker_data = build_tracker_data($donation);
        
        // Log the status after building tracker data
        error_log("Blood Tracker - Tracker data current_status: " . ($tracker_data['current_status'] ?? 'NULL'));
        
        // Compute eligibility to support grace reset and visibility
        $eligibility = compute_donation_eligibility($donor_id);
        error_log("Blood Tracker - Found donation record: " . json_encode($donation));
        
        // Get blood bank units data for status tracking
        $blood_bank_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $blood_bank_result = get_records('blood_bank_units', $blood_bank_params);
        $blood_bank_data = null; // Initialize variable
        
        if ($blood_bank_result['success'] && !empty($blood_bank_result['data'])) {
            $blood_bank_data = $blood_bank_result['data'][0];
            error_log("Blood Tracker - Found blood bank unit record: " . json_encode($blood_bank_data));
            error_log("Blood Tracker - status: " . ($blood_bank_data['status'] ?? 'NULL'));
            error_log("Blood Tracker - handed_over_at: " . ($blood_bank_data['handed_over_at'] ?? 'NULL'));
            error_log("Blood Tracker - disposed_at: " . ($blood_bank_data['disposed_at'] ?? 'NULL'));
            error_log("Blood Tracker - hospital_from: " . ($blood_bank_data['hospital_from'] ?? 'NULL'));
        } else {
            error_log("Blood Tracker - No blood bank unit record found for donor_id: $donor_id");
            
            // Use eligibility record we already fetched above (if available)
            if ($eligibility_record) {
                $elig_status = strtolower(trim($eligibility_record['status'] ?? ''));
                $collection_successful = isset($eligibility_record['collection_successful']) && 
                                         ($eligibility_record['collection_successful'] === true || 
                                          $eligibility_record['collection_successful'] === 'true' || 
                                          $eligibility_record['collection_successful'] === 1);
                $has_blood_collection_id = !empty($eligibility_record['blood_collection_id'] ?? null);
                
                // If eligibility shows approved/eligible with blood_collection_id, create synthetic blood_bank_data
                if ((($elig_status === 'approved' || $elig_status === 'eligible') || $collection_successful) && $has_blood_collection_id) {
                    error_log("Blood Tracker - Found approved eligibility record with blood_collection_id, creating synthetic blood_bank_data");
                    
                    // Create synthetic blood_bank_data to indicate Processed status
                    $blood_bank_data = [
                        'status' => 'Valid',
                        'units' => 1,
                        'donor_id' => $donor_id
                    ];
                }
            }
        }
        
        // Always try to get blood collection data for units display
        $collection_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'start_time.desc',
            'limit' => 1
        ];
        
        $collection_result = get_records('blood_collection', $collection_params);
        
        // If no direct match, try to find through physical examination
        if (!$collection_result['success'] || empty($collection_result['data'])) {
            // Get the latest physical examination for this donor
            $exam_params = [
                'donor_id' => 'eq.' . $donor_id,
                'order' => 'created_at.desc',
                'limit' => 1
            ];
            
            $exam_result = get_records('physical_examination', $exam_params);
            if ($exam_result['success'] && !empty($exam_result['data'])) {
                $exam = $exam_result['data'][0];
                $physical_exam_id = $exam['physical_exam_id'];
                
                // Now query blood_collection using physical_exam_id
                $collection_params = [
                    'physical_exam_id' => 'eq.' . $physical_exam_id,
                    'order' => 'start_time.desc',
                    'limit' => 1
                ];
                
                $collection_result = get_records('blood_collection', $collection_params);
                error_log("Blood Tracker - Trying blood collection via physical_exam_id: $physical_exam_id");
            }
        }
        
        if ($collection_result['success'] && !empty($collection_result['data'])) {
            $blood_collection_data = $collection_result['data'][0];
            error_log("Blood Tracker - Found blood collection record: " . json_encode($blood_collection_data));
            error_log("Blood Tracker - amount_taken value: " . ($blood_collection_data['amount_taken'] ?? 'NULL'));
        } else {
            error_log("Blood Tracker - No blood collection record found for donor_id: $donor_id");
        }
        
        // Get medical history status from medical_history table
        $medical_history_status = null;
        if ($donor_id) {
            $medical_params = [
                'donor_id' => 'eq.' . $donor_id,
                'order' => 'created_at.desc',
                'limit' => 1
            ];
            
            $medical_result = get_records('medical_history', $medical_params);
            if ($medical_result['success'] && !empty($medical_result['data'])) {
                $medical_record = $medical_result['data'][0];
                $medical_history_status = $medical_record['medical_approval'];
                error_log("Blood Tracker - Found medical history record, approval status: " . ($medical_history_status ?? 'NULL'));
            } else {
                error_log("Blood Tracker - No medical history record found for donor_id: $donor_id");
            }
        }
    } else {
        error_log("Blood Tracker - No donation record found for donor_id: $donor_id");
    }
}

$donation_started = isset($_GET['donation_started']) && $_GET['donation_started'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donation Tracker</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        
        .tracker-container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #c3e6cb;
        }
        
        .blood-tracker-card {
            background: white;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-title {
            color: #FF0000;
            font-weight: bold;
            font-size: 18px;
            margin: 0;
        }
        
        .card-arrow {
            color: #666;
            font-size: 16px;
            cursor: pointer;
        }
        
        .timeline-container {
            position: relative;
            
            margin: 32px 0 24px 0;
        }
        
        .timeline-line {
            position: absolute;
            top: 28px;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(180deg, #f1f1f1 0%, #eaeaea 100%);
            border-radius: 9999px;
            z-index: 1;
        }
        
        .timeline-progress {
            position: absolute;
            top: 28px;
            left: 0;
            height: 6px;
            background: linear-gradient(90deg, #ff4d4f 0%, #d50000 60%, #b80000 100%);
            border-radius: 9999px;
            box-shadow: 0 2px 8px rgba(184,0,0,0.25);
            z-index: 2;
            transition: width 0.3s ease;
        }
        
        .timeline-stages {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            z-index: 3;
            margin-top: -8px;
        }
        
        .timeline-stage {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            text-align: center;
            gap: 6px;
        }
        
        .stage-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            font-size: 24px;
            border: 2px solid;
            transition: all 0.25s ease;
            background-clip: padding-box;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        
        .stage-icon.completed {
            background: #e8f5e9;
            border-color: #28a745;
            color: #28a745;
        }
        
        .stage-icon.current {
            background: #fff5f5;
            border-color: #FF0000;
            color: #FF0000;
            box-shadow: 0 4px 14px rgba(213,0,0,0.18);
        }
        
        .stage-icon.pending {
            background: white;
            border-color: #dcdcdc;
            color: #999999;
        }
        
        .stage-label {
            font-size: 12px;
            color: #222;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        
        .stage-status {
            font-size: 10px;
            color: #555;
            text-transform: uppercase;
            padding: 4px 8px;
            border-radius: 999px;
            background: #f2f2f2;
            font-weight: 600;
        }

        .stage-status.completed { color: #1e7e34; background: #e8f5e9; }
        .stage-status.current { color: #b80000; background: #ffe6e6; }
        .stage-status.pending { color: #7d7d7d; background: #f4f4f4; }
        
        .status-description {
            text-align: center;
            color: #333;
            font-size: 14px;
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .donation-info {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .donation-details {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
            font-size: 14px;
        }
        
        .detail-item {
            text-align: center;
        }
        
        .detail-value {
            font-weight: bold;
            color: #FF0000;
        }
        
        .form-links {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .form-link {
            display: block;
            margin: 10px 0;
            padding: 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }
        
        .form-link:hover {
            background: #0056b3;
        }
        
        .form-link.completed {
            background: #28a745;
        }
        
        .start-donation-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .start-btn {
            background: #FF0000;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .start-btn:hover {
            background: #d50000;
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
        
        .header h1 {
            margin: 0;
            font-size: 20px;
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

        /* Modal-style step progress (prevents overhangs) */
        .step-indicators {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 0 40px;
        }
        .step {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 3px solid #dcdcdc; /* pending */
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .step.completed { border-color: #28a745; }
        .step.active { border-color: #d50000; }
        .step-connector {
            flex: 1 1 auto;
            height: 6px;
            background: #eaeaea; /* inactive */
        }
        .step-connector.active { background: #d50000; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Blood Donation Tracker</h1>
    </div>
    
    <div class="tracker-container">
        <?php if ($donation_started): ?>
            <div class="success-message">
                🎉 Your blood donation process has started! You can now track your progress below.
            </div>
        <?php endif; ?>
        
        <?php if (!$donor_id): ?>
            <div class="success-message" style="background: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                ⚠️ Unable to find your donor record. Please make sure you have completed the donor registration form first.
            </div>
        <?php elseif ($tracker_data): ?>
            <?php if ($tracker_data['current_status'] === 'Cancelled'): ?>
                <div class="success-message" style="background: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                    ❌ Your blood donation has been cancelled. Reason: <?php echo $tracker_data['notes'] ?? 'Unknown reason'; ?>
                </div>
            <?php else: ?>
                <?php
                    // If latest donation reached Processed and grace period has passed, suggest reset view
                    $hide_tracker_after_grace = false;
                    if ($eligibility && $eligibility['latest_completed_donation'] && !empty($eligibility['grace_until'])) {
                        $hide_tracker_after_grace = (strtotime(date('Y-m-d H:i:s')) > strtotime($eligibility['grace_until']));
                    }
                ?>
                <?php if ($hide_tracker_after_grace): ?>
                    <div class="start-donation-section">
                        <h3>You're eligible to donate again</h3>
                        <p>The last donation cycle has ended. Start a new donation when you're ready.</p>
                        <a href="blood_donation.php" class="start-btn">Start New Donation</a>
                    </div>
                <?php else: ?>
                <div class="donation-info">
                    <div class="donation-details">
                        <div class="detail-item">
                            <div>Date</div>
                            <div class="detail-value"><?php echo date('M j, Y', strtotime($tracker_data['donation_date'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div>Blood Type</div>
                            <div class="detail-value"><?php echo $tracker_data['blood_type'] ?? 'Pending'; ?></div>
                        </div>
                        <div class="detail-item">
                            <div>Units</div>
                            <div class="detail-value">
                                <?php 
                                // Default to 1 unit since only 1 unit can be extracted per person
                                $units_display = 1;
                                
                                if (isset($blood_collection_data) && isset($blood_collection_data['amount_taken']) && $blood_collection_data['amount_taken'] > 0) {
                                    $units_display = $blood_collection_data['amount_taken'];
                                } elseif (isset($blood_bank_data) && isset($blood_bank_data['units']) && $blood_bank_data['units'] > 0) {
                                    $units_display = $blood_bank_data['units'];
                                }
                                
                                echo $units_display;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Blood Tracker Card -->
                <div class="blood-tracker-card">
                    <div class="card-header">
                        <h3 class="card-title">Blood Tracker</h3>
                        <span class="card-arrow">→</span>
                    </div>
                    
                    <div class="timeline-container">
                        <?php 
                        // Define the 4 main stages in order
                        $main_stages = [
                            ['key' => 'Processed',  'icon' => '🧪', 'label' => 'Processed'],
                            ['key' => 'Stored',     'icon' => '🗄️', 'label' => 'Stored'],
                            ['key' => 'Allocated',  'icon' => '📋', 'label' => 'Allocated'],
                            ['key' => 'Used',       'icon' => '🏥', 'label' => 'Used'],
                        ];

                        // Determine current stage index based on donation current_status (which already includes blood_bank_units check)
                        $current_stage_index = 0;
                        $stage_description_extra = '';
                        $current_donation_status = $tracker_data['current_status'] ?? $donation['current_status'] ?? 'Processed';
                        
                        // Get hospital_from from blood_bank_data if available for Used status
                        $hospital_from = null;
                        if (isset($blood_bank_data)) {
                            $hospital_from = $blood_bank_data['hospital_from'] ?? null;
                        }
                        
                        // Map donation status to stage index (status was already determined from blood_bank_units priority)
                        if ($current_donation_status === 'Used') {
                            $current_stage_index = 3; // Used
                            if (!empty($hospital_from)) {
                                $stage_description_extra = ' - Sent to ' . htmlspecialchars($hospital_from);
                            }
                            // Check if it's expired/disposed
                            if (isset($blood_bank_data) && !empty($blood_bank_data['disposed_at'])) {
                                $stage_description_extra = ' (Expired/Disposed)';
                            }
                            error_log("Blood Tracker - Status is Used -> stage index: 3");
                        } elseif ($current_donation_status === 'Stored') {
                            $current_stage_index = 1; // Stored
                            error_log("Blood Tracker - Status is Stored -> stage index: 1");
                        } elseif ($current_donation_status === 'Allocated') {
                            $current_stage_index = 2; // Allocated
                            error_log("Blood Tracker - Status is Allocated -> stage index: 2");
                        } elseif ($current_donation_status === 'Processed' || $current_donation_status === 'Testing Complete' || 
                                  $current_donation_status === 'Testing' || $current_donation_status === 'Registered' || 
                                  $current_donation_status === 'Sample Collected') {
                            $current_stage_index = 0; // Processed
                            error_log("Blood Tracker - Status is {$current_donation_status} -> stage index: 0");
                        } elseif ($current_donation_status === 'Ready for Use') {
                            $current_stage_index = 2; // Allocated (ready means allocated)
                            error_log("Blood Tracker - Status is Ready for Use -> stage index: 2");
                        } else {
                            // Default to Processed
                            $current_stage_index = 0;
                            error_log("Blood Tracker - Unknown status '{$current_donation_status}' -> defaulting to stage index: 0");
                        }
                        ?>

                        <!-- Step indicators (no overhang) -->
                        <div class="step-indicators">
                            <?php for ($i = 0; $i < count($main_stages); $i++): 
                                $is_completed = ($i < $current_stage_index);
                                $is_active = ($i === $current_stage_index);
                            ?>
                                <div class="step <?php echo $is_completed ? 'completed' : ($is_active ? 'active' : ''); ?>" title="<?php echo $main_stages[$i]['label']; ?>">
                                    <?php echo $main_stages[$i]['icon']; ?>
                                </div>
                                <?php if ($i < count($main_stages) - 1): ?>
                                    <div class="step-connector <?php echo ($i < $current_stage_index) ? 'active' : ''; ?>"></div>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>

                        <!-- Labels and mini-info under each stage -->
                        <div class="timeline-stages">
                            <?php for ($i = 0; $i < count($main_stages); $i++): 
                                $is_completed = ($i < $current_stage_index);
                                $is_active = ($i === $current_stage_index);
                                $status_text = $is_completed ? 'Completed' : ($is_active ? 'Current' : 'Pending');
                                $status_class = $is_completed ? 'completed' : ($is_active ? 'current' : 'pending');
                            ?>
                                <div class="timeline-stage">
                                    <div class="stage-label"><?php echo $main_stages[$i]['label']; ?></div>
                                    <div class="stage-status <?php echo $status_class; ?>"><?php echo $status_text; ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="status-description">
                        <?php 
                        if ($current_stage_index === 0) {
                            echo "Your blood is being processed and tested.";
                        } elseif ($current_stage_index === 1) {
                            echo "Your blood is stored and ready for distribution.";
                        } elseif ($current_stage_index === 2) {
                            echo "Your blood is allocated for a hospital request.";
                        } elseif ($current_stage_index === 3) {
                            echo "Your blood has been used to save lives!" . $stage_description_extra;
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="start-donation-section">
                <h3>Start Your Blood Donation Journey</h3>
                <p>Begin by completing your medical history questionnaire to start tracking your donation process.</p>
                <a href="blood_donation.php" class="start-btn">Start Donation Process</a>
            </div>
        <?php endif; ?>
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
    
    <script>
        // Function to check for status updates automatically
        function checkForStatusUpdates() {
            fetch('../api/auto_status_update.php?action=update_by_email&email=<?php echo urlencode($_SESSION['user']['email'] ?? ''); ?>', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.status === 'updated') {
                    // Status was updated - refresh the page to show new status
                    console.log('Status updated automatically:', data.message);
                    location.reload();
                }
            })
            .catch(error => {
                console.log('Auto-status check completed');
            });
        }
        
        // Call immediately on page load to sync status with admin approval (only once)
        checkForStatusUpdates();
        
        // Auto-refresh every 30 seconds to check for status updates (only one interval)
        setInterval(() => {
            checkForStatusUpdates();
        }, 30000);
        
        // Handle refresh status button (if it exists)
        const refreshBtn = document.getElementById('refresh-status');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
            this.textContent = '🔄 Updating...';
            this.disabled = true;
            
            // Call the auto-update API
            fetch('../api/blood_tracker.php?action=auto_update_status', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.status === 'cancelled') {
                        // Show cancellation popup
                        showCancellationPopup(data.reason || 'Unknown reason');
                    } else if (data.status === 'updated') {
                        // Show success message and reload
                        showSuccessMessage(data.message);
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        // No update needed
                        showInfoMessage(data.message);
                        this.textContent = '🔄 Refresh Status';
                        this.disabled = false;
                    }
                } else {
                    showErrorMessage(data.error || 'Failed to update status');
                    this.textContent = '🔄 Refresh Status';
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Network error occurred');
                this.textContent = '🔄 Refresh Status';
                this.disabled = false;
            });
            });
        }
        
        // Show cancellation popup
        function showCancellationPopup(reason) {
            const popup = document.createElement('div');
            popup.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            
            popup.innerHTML = `
                <div style="
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    max-width: 400px;
                    text-align: center;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                ">
                    <div style="font-size: 48px; margin-bottom: 20px;">❌</div>
                    <h3 style="color: #d32f2f; margin-bottom: 15px;">Donation Cancelled</h3>
                    <p style="color: #666; margin-bottom: 20px;">${reason}</p>
                    <button onclick="this.parentElement.parentElement.remove(); location.reload();" style="
                        background: #d32f2f;
                        color: white;
                        border: none;
                        padding: 12px 24px;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 16px;
                    ">OK</button>
                </div>
            `;
            
            document.body.appendChild(popup);
        }
        
        // Show success message
        function showSuccessMessage(message) {
            const msg = document.createElement('div');
            msg.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #4caf50;
                color: white;
                padding: 15px 20px;
                border-radius: 6px;
                z-index: 10000;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            `;
            msg.textContent = message;
            document.body.appendChild(msg);
            
            setTimeout(() => {
                msg.remove();
            }, 3000);
        }
        
        // Show info message
        function showInfoMessage(message) {
            const msg = document.createElement('div');
            msg.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #2196f3;
                color: white;
                padding: 15px 20px;
                border-radius: 6px;
                z-index: 10000;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            `;
            msg.textContent = message;
            document.body.appendChild(msg);
            
            setTimeout(() => {
                msg.remove();
            }, 3000);
        }
        
        // Show error message
        function showErrorMessage(message) {
            const msg = document.createElement('div');
            msg.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #f44336;
                color: white;
                padding: 15px 20px;
                border-radius: 6px;
                z-index: 10000;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            `;
            msg.textContent = message;
            document.body.appendChild(msg);
            
            setTimeout(() => {
                msg.remove();
            }, 3000);
        }
    </script>
</body>
</html>
