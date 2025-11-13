<?php
// Start the session to maintain state
session_start();
require_once '../../config/database.php';

// Store the referrer URL to use it for the return button
$referrer = '';

// Check HTTP_REFERER first
if (isset($_SERVER['HTTP_REFERER'])) {
    $referrer = $_SERVER['HTTP_REFERER'];
}

// If no referrer or it's not from donor form, check for a passed parameter
if (!$referrer || !stripos($referrer, 'donor-form-modal.php')) {
    // Check if the donor form referrer is stored in session
    if (isset($_SESSION['donor_form_referrer'])) {
        $referrer = $_SESSION['donor_form_referrer'];
    }
}

// Store the referrer in the session for use when redirecting back
$_SESSION['medical_history_referrer'] = $referrer;

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../templates/login.php");
    exit();
}

// Get email from session or GET, normalize it
$email = null;
if (isset($_GET['email'])) {
    $email = trim(strtolower($_GET['email']));
} elseif (isset($_SESSION['user']['email'])) {
    $email = trim(strtolower($_SESSION['user']['email']));
}

if (!$email) {
    error_log("No email found in GET or session");
    echo '<div style="padding:2em;text-align:center;color:red;"><h2>Error</h2><p>Unable to find your donor record. Please try again or contact support.</p></div>';
    exit();
}

// Fetch donor_form record by email
require_once '../../includes/functions.php';
$donorFormResp = get_records('donor_form', ['email' => 'eq.' . $email]);
if (!$donorFormResp['success'] || empty($donorFormResp['data'])) {
    error_log("Missing donor_form record for email: $email");
    $_SESSION['error_message'] = "Error retrieving donor form. Please restart the donation process.";
    header('Location: ../../templates/blood_donation.php?error=Missing donor form record');
    exit();
}
$donorForm = $donorFormResp['data'][0];
$donor_id = $donorForm['donor_id']; // Use this for medical history linkage

// CRITICAL FIX: Store donor_id in session to ensure it persists across form submissions
if ($donor_id) {
    $_SESSION['donor_id'] = $donor_id;
    error_log("Stored donor_id in session: " . $donor_id);
} else {
    // Try to get donor_id from session if not found in query
    if (isset($_SESSION['donor_id']) && $_SESSION['donor_id']) {
        $donor_id = $_SESSION['donor_id'];
        error_log("Retrieved donor_id from session: " . $donor_id);
    }
}

// Debug logging
error_log("Donor form data: " . json_encode($donorForm));
error_log("Donor ID extracted: " . $donor_id);
error_log("Session data: " . json_encode($_SESSION));

// Fetch donor information to determine gender for showing female-specific questions
$donorData = $donorForm;

// Determine if donor is female for showing female-specific questions
$isFemale = (isset($donorData['sex']) && strtolower($donorData['sex']) === 'female');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_medical_history'])) {
    // CRITICAL FIX: Ensure donor_id is available from session or re-query if needed
    $submission_donor_id = null;
    
    // Priority 1: Try to get donor_id from POST data (hidden field)
    if (isset($_POST['donor_id']) && $_POST['donor_id'] && $_POST['donor_id'] > 0) {
        $submission_donor_id = intval($_POST['donor_id']);
        $_SESSION['donor_id'] = $submission_donor_id; // Store in session
        error_log("Using donor_id from POST data for submission: " . $submission_donor_id);
    }
    // Priority 2: Try to get donor_id from session
    elseif (isset($_SESSION['donor_id']) && $_SESSION['donor_id']) {
        $submission_donor_id = $_SESSION['donor_id'];
        error_log("Using donor_id from session for submission: " . $submission_donor_id);
    }
    // Priority 3: Try to get it from the current $donor_id variable (from page load query)
    elseif ($donor_id) {
        $submission_donor_id = $donor_id;
        $_SESSION['donor_id'] = $donor_id; // Store it for future use
        error_log("Using donor_id from current query for submission: " . $submission_donor_id);
    }
    // Priority 4: Last resort - try to query again using email
    elseif ($email) {
        $retryDonorFormResp = get_records('donor_form', ['email' => 'eq.' . $email]);
        if ($retryDonorFormResp['success'] && !empty($retryDonorFormResp['data'])) {
            $retryDonorForm = $retryDonorFormResp['data'][0];
            $submission_donor_id = $retryDonorForm['donor_id'] ?? null;
            if ($submission_donor_id) {
                $_SESSION['donor_id'] = $submission_donor_id;
                error_log("Retrieved donor_id from retry query: " . $submission_donor_id);
            }
        }
    }
    
    // Validate donor_id before proceeding
    if (!$submission_donor_id || $submission_donor_id <= 0) {
        error_log("ERROR: Invalid or missing donor_id during form submission. Email: " . ($email ?? 'N/A'));
        error_log("Session donor_id: " . ($_SESSION['donor_id'] ?? 'NOT SET'));
        error_log("Current donor_id variable: " . ($donor_id ?? 'NOT SET'));
        $_SESSION['error_message'] = "Error: Unable to identify your donor record. Please restart the donation process from the Blood Donation page.";
        header('Location: ../../templates/blood_donation.php?error=Missing donor ID');
        exit();
    }
    
    // Use the validated donor_id
    $donor_id = $submission_donor_id;
    
    // Extract user ID from session
    $user_id = null; // Use null instead of 0 for optional fields
    if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];
    } else {
        error_log("Warning: User ID not found in session, using null as default");
    }
    
    // Store medical history data in session for recovery in case of failure
    $_SESSION['medical_history_form_data'] = $_POST;
    
    // Build form data following the EXACT pattern of working donor_form submission
    // Start with required fields only - NOTE: created_by does NOT exist in medical_history schema
    $formData = [
        'donor_id' => intval($donor_id)
    ];
    
    // Define boolean fields that must always be included (even if false)
    $booleanFields = [
        'feels_well', 'previously_refused', 'testing_purpose_only', 'understands_transmission_risk',
        'recent_alcohol_consumption', 'recent_aspirin', 'recent_medication', 'recent_donation',
        'zika_travel', 'zika_contact', 'zika_sexual_contact', 'blood_transfusion', 'surgery_dental',
        'tattoo_piercing', 'risky_sexual_contact', 'unsafe_sex', 'hepatitis_contact', 'imprisonment',
        'uk_europe_stay', 'foreign_travel', 'drug_use', 'clotting_factor', 'positive_disease_test',
        'malaria_history', 'std_history', 'cancer_blood_disease', 'heart_disease', 'lung_disease',
        'kidney_disease', 'chicken_pox', 'chronic_illness', 'recent_fever'
    ];
    
    // Add female-specific boolean fields if applicable
    if ($isFemale) {
        $booleanFields = array_merge($booleanFields, [
            'pregnancy_history', 'last_childbirth', 'recent_miscarriage', 'breastfeeding', 'last_menstruation'
        ]);
    }
    
    // Map form questions to database fields
    $questionMap = [
        'q1' => 'feels_well', 'q2' => 'previously_refused', 'q3' => 'testing_purpose_only',
        'q4' => 'understands_transmission_risk', 'q5' => 'recent_alcohol_consumption',
        'q6' => 'recent_aspirin', 'q7' => 'recent_medication', 'q8' => 'recent_donation',
        'q9' => 'zika_travel', 'q10' => 'zika_contact', 'q11' => 'zika_sexual_contact',
        'q12' => 'blood_transfusion', 'q13' => 'surgery_dental', 'q14' => 'tattoo_piercing',
        'q15' => 'risky_sexual_contact', 'q16' => 'unsafe_sex', 'q17' => 'hepatitis_contact',
        'q18' => 'imprisonment', 'q19' => 'uk_europe_stay', 'q20' => 'foreign_travel',
        'q21' => 'drug_use', 'q22' => 'clotting_factor', 'q23' => 'positive_disease_test',
        'q24' => 'malaria_history', 'q25' => 'std_history', 'q26' => 'cancer_blood_disease',
        'q27' => 'heart_disease', 'q28' => 'lung_disease', 'q29' => 'kidney_disease',
        'q30' => 'chicken_pox', 'q31' => 'chronic_illness', 'q32' => 'recent_fever',
        'q33' => 'pregnancy_history', 'q34' => 'last_childbirth', 'q35' => 'recent_miscarriage',
        'q36' => 'breastfeeding', 'q37' => 'last_menstruation'
    ];
    
    // Process all boolean fields - always include them as boolean (true/false)
    foreach ($booleanFields as $field) {
        // Find the question number for this field
        $questionNum = array_search($field, $questionMap);
        if ($questionNum !== false && isset($_POST[$questionNum])) {
            $formData[$field] = ($_POST[$questionNum] === 'Yes');
        } else {
            // If no question mapped or not in POST, default to false
            $formData[$field] = false;
        }
    }
    
    // Process remarks fields - only include if they have values (following donor_form pattern)
    foreach ($questionMap as $questionNum => $field) {
        $remarksKey = $questionNum . '_remarks';
        if (isset($_POST[$remarksKey]) && trim($_POST[$remarksKey]) !== '') {
            $formData[$field . '_remarks'] = trim($_POST[$remarksKey]);
        }
    }
    
    // CRITICAL: Remove any null or empty values - Supabase rejects them (following donor_form pattern)
    foreach ($formData as $key => $value) {
        if ($value === null || $value === '') {
            unset($formData[$key]);
        }
    }
    
    // Validate that donor_id is present and valid BEFORE any other processing
    if (!isset($formData['donor_id']) || !$formData['donor_id'] || $formData['donor_id'] <= 0) {
        error_log("CRITICAL: Invalid donor_id in formData: " . ($formData['donor_id'] ?? 'NOT SET'));
        throw new Exception("Invalid donor ID. Cannot save medical history.");
    }
    
    // Final validation: Ensure all boolean values are actual booleans (not strings)
    foreach ($formData as $key => $value) {
        // Check if this is a boolean field (not remarks) - use substr for PHP 7.x compatibility
        if (substr($key, -8) !== '_remarks' && !is_bool($value)) {
            // If it should be boolean but isn't, check if it's in our boolean fields list
            if (in_array($key, $booleanFields)) {
                $formData[$key] = (bool) $value;
            }
        }
    }
    
    // Log the final payload before sending (with full details for debugging)
    $jsonForLog = json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    error_log("Medical history final payload (formatted):\n" . $jsonForLog);
    error_log("Medical history payload count: " . count($formData) . " fields");
    error_log("Medical history donor_id: " . $formData['donor_id']);
    
    // Verify JSON encoding works correctly
    $testJson = json_encode($formData);
    if ($testJson === false) {
        $jsonError = json_last_error_msg();
        error_log("CRITICAL: JSON encoding failed: " . $jsonError);
        throw new Exception("Failed to encode medical history data: " . $jsonError);
    }
    
    try {
        // Check if a medical history record already exists for this donor_id
        $checkResult = get_records('medical_history', ['donor_id' => 'eq.' . $donor_id]);
        
        $existingRecord = null;
        if ($checkResult['success'] && is_array($checkResult['data']) && !empty($checkResult['data'])) {
            $existingRecord = $checkResult['data'][0];
            error_log("Found existing medical history record: " . ($existingRecord['medical_history_id'] ?? 'N/A'));
        }
        
        // Prepare data for submission
        $submitData = $formData;
        
        // CRITICAL: Remove any fields that don't exist in the schema or are auto-managed
        // Schema fields we CAN send: donor_id, all boolean fields, all _remarks fields
        // DO NOT send: created_by (doesn't exist), medical_history_id (auto-generated), 
        // created_at, updated_at (auto-managed), medical_approval, needs_review, 
        // interviewer, disapproval_reason, is_admin (admin-only fields)
        $allowedFields = [
            'donor_id', 'feels_well', 'feels_well_remarks', 'previously_refused', 'previously_refused_remarks',
            'testing_purpose_only', 'testing_purpose_only_remarks', 'understands_transmission_risk',
            'understands_transmission_risk_remarks', 'recent_alcohol_consumption', 'recent_alcohol_consumption_remarks',
            'recent_aspirin', 'recent_aspirin_remarks', 'recent_medication', 'recent_medication_remarks',
            'recent_donation', 'recent_donation_remarks', 'zika_travel', 'zika_travel_remarks',
            'zika_contact', 'zika_contact_remarks', 'zika_sexual_contact', 'zika_sexual_contact_remarks',
            'blood_transfusion', 'blood_transfusion_remarks', 'surgery_dental', 'surgery_dental_remarks',
            'tattoo_piercing', 'tattoo_piercing_remarks', 'risky_sexual_contact', 'risky_sexual_contact_remarks',
            'unsafe_sex', 'unsafe_sex_remarks', 'hepatitis_contact', 'hepatitis_contact_remarks',
            'imprisonment', 'imprisonment_remarks', 'uk_europe_stay', 'uk_europe_stay_remarks',
            'foreign_travel', 'foreign_travel_remarks', 'drug_use', 'drug_use_remarks',
            'clotting_factor', 'clotting_factor_remarks', 'positive_disease_test', 'positive_disease_test_remarks',
            'malaria_history', 'malaria_history_remarks', 'std_history', 'std_history_remarks',
            'cancer_blood_disease', 'cancer_blood_disease_remarks', 'heart_disease', 'heart_disease_remarks',
            'lung_disease', 'lung_disease_remarks', 'kidney_disease', 'kidney_disease_remarks',
            'chicken_pox', 'chicken_pox_remarks', 'chronic_illness', 'chronic_illness_remarks',
            'recent_fever', 'recent_fever_remarks', 'pregnancy_history', 'pregnancy_history_remarks',
            'last_childbirth', 'last_childbirth_remarks', 'recent_miscarriage', 'recent_miscarriage_remarks',
            'breastfeeding', 'breastfeeding_remarks', 'last_menstruation', 'last_menstruation_remarks'
        ];
        
        // Remove any fields not in the allowed list (prevents sending invalid fields like created_by)
        foreach ($submitData as $key => $value) {
            if (!in_array($key, $allowedFields)) {
                error_log("Removing invalid/auto-managed field from medical_history payload: " . $key);
                unset($submitData[$key]);
            }
        }
        
        if ($existingRecord) {
            $medical_history_id = $existingRecord['medical_history_id'];
            
            error_log("Updating existing medical history record ID: " . $medical_history_id);
            error_log("Update payload: " . json_encode($submitData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            
            // Use update_record helper function for consistency
            $updateResult = supabase_request(
                'rest/v1/medical_history?medical_history_id=eq.' . $medical_history_id,
                'PATCH',
                $submitData,
                ['Prefer: return=representation', 'Content-Profile: public']
            );
            
            if (!$updateResult['success']) {
                error_log("CRITICAL: Update failed. Status: " . ($updateResult['status_code'] ?? 'unknown'));
                error_log("CRITICAL: Update response: " . ($updateResult['raw_response'] ?? 'No response'));
                throw new Exception("Failed to update medical history: " . ($updateResult['raw_response'] ?? 'Unknown error'));
            }
            
            error_log("Successfully updated medical history record: " . $medical_history_id);
            $resultData = $updateResult['data'];
            
        } else {
            // Insert new record using create_record helper function
            error_log("Inserting new medical history record for donor_id: " . $donor_id);
            error_log("Insert payload: " . json_encode($submitData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            
            $insertResult = create_record('medical_history', $submitData);
            
            if (!$insertResult['success']) {
                error_log("CRITICAL: Insert failed. Status: " . ($insertResult['status_code'] ?? 'unknown'));
                error_log("CRITICAL: Insert response: " . ($insertResult['raw_response'] ?? 'No response'));
                error_log("CRITICAL: Insert data: " . json_encode($insertResult['data'] ?? []));
                
                // Extract detailed error message
                $errorMessage = "Failed to save medical history data.";
                if (isset($insertResult['data']) && is_array($insertResult['data'])) {
                    if (isset($insertResult['data']['message'])) {
                        $errorMessage .= " " . $insertResult['data']['message'];
                    }
                    if (isset($insertResult['data']['hint'])) {
                        $errorMessage .= " Hint: " . $insertResult['data']['hint'];
                    }
                }
                
                throw new Exception($errorMessage);
            }
            
            error_log("Successfully inserted medical history record");
            $resultData = $insertResult['data'];
        }
        
        // Validate that we got a valid response with medical_history_id
        $medical_history_id = null;
        if (is_array($resultData) && !empty($resultData)) {
            if (isset($resultData[0]['medical_history_id'])) {
                $medical_history_id = $resultData[0]['medical_history_id'];
            } elseif (isset($resultData['medical_history_id'])) {
                $medical_history_id = $resultData['medical_history_id'];
            } elseif ($existingRecord) {
                // For updates, use existing ID if response doesn't include it
                $medical_history_id = $existingRecord['medical_history_id'];
            }
        } elseif ($existingRecord) {
            // PATCH might return empty array, use existing ID
            $medical_history_id = $existingRecord['medical_history_id'];
        }
        
        if (!$medical_history_id) {
            error_log("ERROR: Could not determine medical_history_id from response.");
            error_log("ERROR: Response data: " . json_encode($resultData));
            throw new Exception("Failed to retrieve medical history ID after save. Please try again.");
        }
        
        // Set the medical_history_id in session for next steps
        $_SESSION['medical_history_id'] = $medical_history_id;
        error_log("Medical history saved successfully. ID: " . $medical_history_id);
        
        // Continue with donation process if we have a valid medical_history_id
        if (isset($_SESSION['medical_history_id']) && $_SESSION['medical_history_id']) {
            
            // Validate donor_id before starting donation process
            if (!$donor_id || $donor_id <= 0) {
                error_log("Invalid donor_id: " . $donor_id);
                $_SESSION['error_message'] = "Invalid donor ID. Please restart the donation process.";
                header('Location: ../../templates/blood_donation.php?error=Invalid donor ID');
                exit();
            }
            
            // Start the donation process
            error_log("Starting donation process for donor_id: " . $donor_id);
            $donation_result = start_donation_process($donor_id);
            error_log("Donation process result: " . json_encode($donation_result));
            
            if ($donation_result['success']) {
                // Store donation ID in session
                $_SESSION['donation_id'] = $donation_result['donation_id'];
                
                // Immediately update the donation status based on existing forms
                // If this was an update (not new insert), reset status and don't check PE forms
                $is_update = isset($existingRecord) && $existingRecord !== null;
                error_log("Automatically updating donation status for donor_id: " . $donor_id . " (is_update: " . ($is_update ? 'true' : 'false') . ")");
                $status_update_result = auto_update_donation_status_after_medical_history($donor_id, $is_update);
                error_log("Status update result: " . json_encode($status_update_result));
                
                if ($status_update_result['success'] && $status_update_result['status'] === 'updated') {
                    $_SESSION['success_message'] = "Medical history submitted successfully! Your donation process has started and status updated to: " . ($status_update_result['blood_type'] ? $status_update_result['blood_type'] . ' - ' : '') . $status_update_result['message'];
                } elseif ($status_update_result['success'] && $status_update_result['status'] === 'reset') {
                    $_SESSION['success_message'] = "Medical history updated successfully! " . $status_update_result['message'];
                } elseif ($status_update_result['success'] && $status_update_result['status'] === 'cancelled') {
                    $_SESSION['warning_message'] = "Medical history submitted successfully, but donation was cancelled: " . $status_update_result['reason'];
                } else {
                    $_SESSION['success_message'] = "Medical history submitted successfully! Your donation process has started.";
                }
                
                // Redirect back to blood donation page
                header('Location: ../../templates/blood_donation.php');
                exit();
            } else {
                // If donation process fails, still redirect to blood donation page with warning
                $_SESSION['warning_message'] = "Medical history submitted successfully, but there was an issue starting the donation process. Please contact staff.";
                error_log("Donation process failed: " . ($donation_result['message'] ?? 'Unknown error'));
                header('Location: ../../templates/blood_donation.php');
                exit();
            }
        } else {
            throw new Exception("Failed to get valid medical_history_id after " . ($existingRecord ? "update" : "insert") . " operation.");
        }
    } catch (Exception $e) {
        // Log the error and redirect back to blood donation page with error message
        $errorMessage = "There was an issue saving your medical history data. Please try again or contact support.";
        $_SESSION['error_message'] = $errorMessage;
        error_log("CRITICAL ERROR in medical history form submission: " . $e->getMessage());
        error_log("Error details - Donor ID: " . ($donor_id ?? 'NOT SET') . ", Email: " . ($email ?? 'NOT SET'));
        
        // Record error details in session for debugging
        $_SESSION['medical_history_error'] = [
            'message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s'),
            'donor_id' => $donor_id ?? null,
            'email' => $email ?? null
        ];
        
        // Redirect back to blood donation page with error
        header('Location: ../../templates/blood_donation.php?error=' . urlencode($errorMessage));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#9c0000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Medical History</title>
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="../../manifest.json">
    <link rel="apple-touch-icon" href="../../images/icons/icon-192x192.png">
    
    <!-- Add mobile-specific enhancements -->
    <script>
      // Check if this is standalone mode (PWA installed)
      const isInStandaloneMode = () => 
        (window.matchMedia('(display-mode: standalone)').matches) || 
        (window.navigator.standalone) || 
        document.referrer.includes('android-app://');
      
      // Add class to body based on standalone mode
      document.addEventListener('DOMContentLoaded', () => {
        if (isInStandaloneMode()) {
          document.body.classList.add('pwa-standalone');
        }
      });
    </script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f0f0f0;
        }
        
        .modal {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 800px;
            max-width: 90%;
            position: relative;
            overflow: hidden;
        }
        
        .modal-header {
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #fff;
            position: relative;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        
        .modal-title {
            color: #9c0000;
            font-size: 26px;
            font-weight: bold;
            margin: 0;
            text-align: center;
            width: 100%;
        }
        
        .close-button {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #888;
            position: absolute;
            right: 15px;
            top: 12px;
        }
        
        .modal-body {
            padding: 20px;
            background-color: #fff;
        }
        
        .step-indicators {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
            gap: 0;
            max-width: 460px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .step {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: white;
            color: #666;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ddd;
            font-weight: bold;
            position: relative;
            z-index: 2;
            margin: 0;
        }
        
        .step.active, .step.completed {
            background-color: #9c0000;
            color: white;
            border-color: #9c0000;
        }
        
        .step-connector {
            height: 1px;
            background-color: #ddd;
            width: 70px;
            flex-grow: 0;
            margin: 0;
            padding: 0;
        }
        
        .step-connector.active {
            background-color: #9c0000;
        }
        
        .step-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 8px;
            color: #9c0000;
            font-size: 26px;
        }
        
        .step-description {
            text-align: center;
            margin-bottom: 12px;
            font-style: italic;
            color: #666;
            font-size: 13px;
        }
        
        .form-group {
            display: grid;
            grid-template-columns: 0.5fr 4fr 0.75fr 0.75fr 2fr;
            gap: 5px;
            margin-bottom: 5px;
            align-items: center;
            width: 100%;
        }
        
        .form-header {
            font-weight: bold;
            text-align: center;
            background-color: #9c0000;
            color: white;
            padding: 8px 5px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            height: 36px;
            font-size: 14px;
        }
        
        .form-section-title {
            grid-column: 1 / span 5;
            font-weight: bold;
            background-color: #f5f5f5;
            padding: 10px;
            margin-top: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .question-number {
            text-align: center;
            font-weight: bold;
            padding: 5px 0;
            font-size: 16px;
        }
        
        .question-text {
            padding: 5px 8px;
            font-size: 15px;
            line-height: 1.4;
        }
        
        .radio-cell {
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 4px 0;
        }
        
        .radio-container {
            width: 16px;
            height: 16px;
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .radio-container input[type="radio"] {
            opacity: 0;
            position: absolute;
            cursor: pointer;
        }
        
        .checkmark {
            width: 16px;
            height: 16px;
            background-color: #fff;
            border: 1px solid #999;
            border-radius: 4px;
            display: inline-block;
            position: relative;
            transition: all 0.2s ease;
        }
        
        .radio-container input[type="radio"]:checked ~ .checkmark {
            background-color: #9c0000;
            border-color: #9c0000;
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 5px;
            top: 2px;
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .radio-container input[type="radio"]:checked ~ .checkmark:after {
            display: block;
        }
        
        .remarks-cell {
            padding: 2px;
        }
        
        .remarks-input {
            width: 100%;
            padding: 3px 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            height: 26px;
            font-size: 14px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            border-top: 1px solid #f0f0f0;
            background-color: #fff;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        
        .prev-button,
        .next-button,
        .submit-button {
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
        }
        
        .prev-button {
            background-color: #f5f5f5;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .next-button,
        .submit-button {
            background-color: #9c0000;
            color: white;
            border: none;
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
        }
        
        #femaleHealthSection {
            display: none; /* Hide by default, will be shown via JS if donor is female */
        }
        
        /* Mobile-specific enhancements */
        @media (max-width: 768px) {
            body {
                font-size: 16px; /* Increase base font size for better readability */
                padding: 0;
                background-color: #f8f8f8;
            }
            
            .modal {
                width: 100%;
                max-width: 100%;
                margin: 0;
                border-radius: 0;
                min-height: 100vh;
            }
            
            .step-indicators {
                max-width: 100%;
                overflow-x: auto;
                padding: 5px 0;
                -webkit-overflow-scrolling: touch;
            }
            
            .step-connector {
                width: 40px;
            }
            
            .form-group {
                grid-template-columns: 0.3fr 2.5fr 0.6fr 0.6fr 1.5fr;
                font-size: 14px;
            }
            
            .question-text {
                font-size: 14px;
                line-height: 1.3;
            }
            
            .remarks-input {
                width: 100%;
                font-size: 14px;
            }
            
            /* Improve touch targets */
            .radio-container {
                width: 24px;
                height: 24px;
            }
            
            .checkmark {
                width: 22px;
                height: 22px;
            }
            
            .checkmark:after {
                left: 7px;
                top: 3px;
                width: 6px;
                height: 11px;
            }
            
            .modal-footer {
                position: sticky;
                bottom: 0;
                background: white;
                box-shadow: 0 -3px 10px rgba(0,0,0,0.1);
                z-index: 100;
            }
            
            .prev-button, .next-button, .submit-button {
                padding: 12px 20px;
                font-size: 16px;
                min-width: 100px;
            }
            
            /* Fix for iOS input zoom */
            input, select, textarea {
                font-size: 16px !important;
            }
            
            /* Enhance for installed PWA experience */
            body.pwa-standalone {
                padding-top: env(safe-area-inset-top, 0);
                padding-bottom: env(safe-area-inset-bottom, 15px);
                padding-left: env(safe-area-inset-left, 0);
                padding-right: env(safe-area-inset-right, 0);
            }
            
            body.pwa-standalone .modal-footer {
                padding-bottom: calc(12px + env(safe-area-inset-bottom, 0));
            }
        }
        
        /* Add offline indicator */
        .offline-indicator {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 8px 16px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1100;
        }
        
        /* Enable form progress saving indicator */
        .auto-save-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.9);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            color: #28a745;
            z-index: 1000;
            display: none;
        }
        
        .auto-save-indicator.saving {
            display: block;
        }
    </style>
</head>
<body>
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Medical History</h2>
            <a href="<?php echo $referrer; ?>" class="close-button" onclick="return confirm('Are you sure you want to cancel this donation? All data will be discarded.')">&times;</a>
        </div>
        
        <!-- Error message display if any -->
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert" style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 20px; border-radius: 5px;">
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
        </div>
        <?php endif; ?>
        
        <div class="modal-body">
            <div class="step-indicators">
                <div class="step active" id="step1" data-step="1">1</div>
                <div class="step-connector" id="line1-2"></div>
                <div class="step" id="step2" data-step="2">2</div>
                <div class="step-connector" id="line2-3"></div>
                <div class="step" id="step3" data-step="3">3</div>
                <div class="step-connector" id="line3-4"></div>
                <div class="step" id="step4" data-step="4">4</div>
                <div class="step-connector" id="line4-5"></div>
                <div class="step" id="step5" data-step="5">5</div>
                <?php if ($isFemale): ?>
                <div class="step-connector" id="line5-6"></div>
                <div class="step" id="step6" data-step="6">6</div>
                <?php endif; ?>
            </div>
            
            <form id="medicalHistoryForm" method="post">
                <input type="hidden" name="donor_id" value="<?php echo $donor_id; ?>">
                
                <!-- Step 1: Health & Risk Assessment -->
                <div class="form-step active" data-step="1">
                    <div class="step-title">HEALTH & RISK ASSESSMENT:</div>
                    <div class="step-description">Tick the appropriate answer.</div>
                    
                    <div class="form-group">
                        <div class="form-header">#</div>
                        <div class="form-header">Question</div>
                        <div class="form-header">YES</div>
                        <div class="form-header">NO</div>
                        <div class="form-header">REMARKS</div>
                        
                        <div class="question-number">1</div>
                        <div class="question-text">Do you feel well and healthy today?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q1" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q1" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q1_remarks">
                                <option value="None">None</option>
                                <option value="Feeling Unwell">Feeling Unwell</option>
                                <option value="Fatigue">Fatigue</option>
                                <option value="Fever">Fever</option>
                                <option value="Other Health Issues">Other Health Issues</option>
                            </select>
                        </div>
                        
                        <div class="question-number">2</div>
                        <div class="question-text">Have you ever been refused as a blood donor?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q2" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q2" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q2_remarks">
                                <option value="None">None</option>
                                <option value="Medical Condition">Medical Condition</option>
                                <option value="Low Hemoglobin">Low Hemoglobin</option>
                                <option value="Recent Donation">Recent Donation</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">3</div>
                        <div class="question-text">Are you donating blood just to get tested for HIV, hepatitis, etc.?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q3" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q3" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q3_remarks">
                                <option value="None">None</option>
                                <option value="Health Check">Health Check</option>
                                <option value="STD Testing">STD Testing</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">4</div>
                        <div class="question-text">Are you aware that HIV, hepatitis, malaria, etc. can be transmitted through blood?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q4" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q4" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q4_remarks">
                                <option value="None">None</option>
                                <option value="Need More Information">Need More Information</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">5</div>
                        <div class="question-text">Did you have alcoholic beverages in the last 24 hours?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q5" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q5" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q5_remarks">
                                <option value="None">None</option>
                                <option value="Beer">Beer</option>
                                <option value="Wine">Wine</option>
                                <option value="Spirits">Spirits</option>
                                <option value="Multiple Types">Multiple Types</option>
                            </select>
                        </div>
                        
                        <div class="question-number">6</div>
                        <div class="question-text">Did you take any medications containing aspirin in the last 48 hours?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q6" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q6" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q6_remarks">
                                <option value="None">None</option>
                                <option value="Aspirin">Aspirin</option>
                                <option value="Pain Reliever">Pain Reliever</option>
                                <option value="Cold Medicine">Cold Medicine</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">7</div>
                        <div class="question-text">Have you taken any medications in the last 30 days?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q7" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q7" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q7_remarks">
                                <option value="None">None</option>
                                <option value="Antibiotics">Antibiotics</option>
                                <option value="Blood Pressure">Blood Pressure</option>
                                <option value="Diabetes">Diabetes</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">8</div>
                        <div class="question-text">Have you donated blood in the last 3 months?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q8" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q8" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q8_remarks">
                                <option value="None">None</option>
                                <option value="Whole Blood">Whole Blood</option>
                                <option value="Plasma">Plasma</option>
                                <option value="Platelets">Platelets</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Past 6 Months -->
                <div class="form-step" data-step="2">
                    <div class="step-title">PAST 6 MONTHS:</div>
                    <div class="step-description">Tick the appropriate answer.</div>
                    
                    <div class="form-group">
                        <div class="form-header">#</div>
                        <div class="form-header">Question</div>
                        <div class="form-header">YES</div>
                        <div class="form-header">NO</div>
                        <div class="form-header">REMARKS</div>
                        
                        <div class="question-number">9</div>
                        <div class="question-text">Have you visited or lived in a Zika-affected area?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q9" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q9" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q9_remarks">
                                <option value="None">None</option>
                                <option value="Caribbean">Caribbean</option>
                                <option value="Central America">Central America</option>
                                <option value="South America">South America</option>
                                <option value="Pacific Islands">Pacific Islands</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">10</div>
                        <div class="question-text">Have you had contact with anyone diagnosed with Zika?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q10" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q10" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q10_remarks">
                                <option value="None">None</option>
                                <option value="Family Member">Family Member</option>
                                <option value="Co-worker">Co-worker</option>
                                <option value="Friend">Friend</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">11</div>
                        <div class="question-text">Have you had sexual contact with anyone who has been diagnosed with Zika?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q11" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q11" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q11_remarks">
                                <option value="None">None</option>
                                <option value="Partner">Partner</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Past 12 Months -->
                <div class="form-step" data-step="3">
                    <div class="step-title">PAST 12 MONTHS:</div>
                    <div class="step-description">Tick the appropriate answer.</div>
                    
                    <div class="form-group">
                        <div class="form-header">#</div>
                        <div class="form-header">Question</div>
                        <div class="form-header">YES</div>
                        <div class="form-header">NO</div>
                        <div class="form-header">REMARKS</div>
                        
                        <div class="question-number">12</div>
                        <div class="question-text">Have you received a blood transfusion, organ transplant, or tissue graft?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q12" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q12" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q12_remarks">
                                <option value="None">None</option>
                                <option value="Blood Transfusion">Blood Transfusion</option>
                                <option value="Organ Transplant">Organ Transplant</option>
                                <option value="Tissue Graft">Tissue Graft</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">13</div>
                        <div class="question-text">Have you had any surgical procedure, dental extraction, or endoscopy?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q13" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q13" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q13_remarks">
                                <option value="None">None</option>
                                <option value="Minor Surgery">Minor Surgery</option>
                                <option value="Major Surgery">Major Surgery</option>
                                <option value="Dental Work">Dental Work</option>
                                <option value="Endoscopy">Endoscopy</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">14</div>
                        <div class="question-text">Have you had tattoos, ear/body piercing, or acupuncture?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q14" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q14" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q14_remarks">
                                <option value="None">None</option>
                                <option value="Tattoo">Tattoo</option>
                                <option value="Ear Piercing">Ear Piercing</option>
                                <option value="Body Piercing">Body Piercing</option>
                                <option value="Acupuncture">Acupuncture</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">15</div>
                        <div class="question-text">Have you engaged in high-risk sexual behaviors?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q15" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q15" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q15_remarks">
                                <option value="None">None</option>
                                <option value="Multiple Partners">Multiple Partners</option>
                                <option value="Commercial Sex">Commercial Sex</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">16</div>
                        <div class="question-text">Have you had unprotected sex with a high-risk partner?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q16" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q16" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q16_remarks">
                                <option value="None">None</option>
                                <option value="Partner with Multiple Partners">Partner with Multiple Partners</option>
                                <option value="Partner with HIV/AIDS">Partner with HIV/AIDS</option>
                                <option value="Partner with Hepatitis">Partner with Hepatitis</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">17</div>
                        <div class="question-text">Have you been in close contact with someone with hepatitis?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q17" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q17" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q17_remarks">
                                <option value="None">None</option>
                                <option value="Hepatitis A">Hepatitis A</option>
                                <option value="Hepatitis B">Hepatitis B</option>
                                <option value="Hepatitis C">Hepatitis C</option>
                                <option value="Unknown Type">Unknown Type</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">18</div>
                        <div class="question-text">Have you been imprisoned or confined in a correctional institution?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q18" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q18" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q18_remarks">
                                <option value="None">None</option>
                                <option value="Prison">Prison</option>
                                <option value="Jail">Jail</option>
                                <option value="Detention Center">Detention Center</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">19</div>
                        <div class="question-text">Have you visited or lived in the UK or Europe?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q19" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q19" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q19_remarks">
                                <option value="None">None</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="France">France</option>
                                <option value="Germany">Germany</option>
                                <option value="Italy">Italy</option>
                                <option value="Spain">Spain</option>
                                <option value="Other European Country">Other European Country</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Step 4: Have you ever -->
                <div class="form-step" data-step="4">
                    <div class="step-title">HAVE YOU EVER:</div>
                    <div class="step-description">Tick the appropriate answer.</div>
                    
                    <div class="form-group">
                        <div class="form-header">#</div>
                        <div class="form-header">Question</div>
                        <div class="form-header">YES</div>
                        <div class="form-header">NO</div>
                        <div class="form-header">REMARKS</div>
                        
                        <div class="question-number">20</div>
                        <div class="question-text">Traveled or lived outside the Philippines?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q20" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q20" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q20_remarks">
                                <option value="None">None</option>
                                <option value="Asia">Asia</option>
                                <option value="North America">North America</option>
                                <option value="South America">South America</option>
                                <option value="Europe">Europe</option>
                                <option value="Africa">Africa</option>
                                <option value="Australia/Oceania">Australia/Oceania</option>
                                <option value="Multiple Regions">Multiple Regions</option>
                            </select>
                        </div>
                        
                        <div class="question-number">21</div>
                        <div class="question-text">Used prohibited or recreational drugs by needles?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q21" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q21" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q21_remarks">
                                <option value="None">None</option>
                                <option value="IV Drug Use">IV Drug Use</option>
                                <option value="Shared Needles">Shared Needles</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">22</div>
                        <div class="question-text">Received clotting factor concentrates for hemophilia?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q22" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q22" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q22_remarks">
                                <option value="None">None</option>
                                <option value="Factor VIII">Factor VIII</option>
                                <option value="Factor IX">Factor IX</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">23</div>
                        <div class="question-text">Tested positive for HIV, hepatitis, malaria, or syphilis?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q23" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q23" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q23_remarks">
                                <option value="None">None</option>
                                <option value="HIV">HIV</option>
                                <option value="Hepatitis">Hepatitis</option>
                                <option value="Malaria">Malaria</option>
                                <option value="Syphilis">Syphilis</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">24</div>
                        <div class="question-text">Had malaria or hepatitis?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q24" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q24" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q24_remarks">
                                <option value="None">None</option>
                                <option value="Malaria">Malaria</option>
                                <option value="Hepatitis A">Hepatitis A</option>
                                <option value="Hepatitis B">Hepatitis B</option>
                                <option value="Hepatitis C">Hepatitis C</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">25</div>
                        <div class="question-text">Been treated for syphilis or gonorrhea?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q25" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q25" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q25_remarks">
                                <option value="None">None</option>
                                <option value="Syphilis">Syphilis</option>
                                <option value="Gonorrhea">Gonorrhea</option>
                                <option value="Both">Both</option>
                                <option value="Other STI">Other STI</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Step 5: Had any conditions -->
                <div class="form-step" data-step="5">
                    <div class="step-title">HAVE YOU HAD ANY OF THE FOLLOWING:</div>
                    <div class="step-description">Tick the appropriate answer.</div>
                    
                    <div class="form-group">
                        <div class="form-header">#</div>
                        <div class="form-header">Question</div>
                        <div class="form-header">YES</div>
                        <div class="form-header">NO</div>
                        <div class="form-header">REMARKS</div>
                        
                        <div class="question-number">26</div>
                        <div class="question-text">Cancer, blood disease, or bleeding disorder?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q26" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q26" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q26_remarks">
                                <option value="None">None</option>
                                <option value="Cancer">Cancer</option>
                                <option value="Leukemia">Leukemia</option>
                                <option value="Hemophilia">Hemophilia</option>
                                <option value="Anemia">Anemia</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">27</div>
                        <div class="question-text">Heart disease or heart problems?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q27" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q27" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q27_remarks">
                                <option value="None">None</option>
                                <option value="Hypertension">Hypertension</option>
                                <option value="Heart Attack">Heart Attack</option>
                                <option value="Arrhythmia">Arrhythmia</option>
                                <option value="Congenital Heart Disease">Congenital Heart Disease</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">28</div>
                        <div class="question-text">Lung disease or breathing problems?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q28" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q28" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q28_remarks">
                                <option value="None">None</option>
                                <option value="Asthma">Asthma</option>
                                <option value="COPD">COPD</option>
                                <option value="Tuberculosis">Tuberculosis</option>
                                <option value="Pneumonia">Pneumonia</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">29</div>
                        <div class="question-text">Kidney disease or kidney problems?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q29" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q29" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q29_remarks">
                                <option value="None">None</option>
                                <option value="Kidney Stones">Kidney Stones</option>
                                <option value="Kidney Infection">Kidney Infection</option>
                                <option value="Chronic Kidney Disease">Chronic Kidney Disease</option>
                                <option value="Dialysis">Dialysis</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">30</div>
                        <div class="question-text">Chicken pox or shingles?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q30" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q30" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q30_remarks">
                                <option value="None">None</option>
                                <option value="Chicken Pox">Chicken Pox</option>
                                <option value="Shingles">Shingles</option>
                                <option value="Both">Both</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">31</div>
                        <div class="question-text">Any other serious medical condition?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q31" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q31" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q31_remarks">
                                <option value="None">None</option>
                                <option value="Diabetes">Diabetes</option>
                                <option value="Thyroid Disease">Thyroid Disease</option>
                                <option value="Autoimmune Disease">Autoimmune Disease</option>
                                <option value="Neurological Condition">Neurological Condition</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">32</div>
                        <div class="question-text">Fever, headache, or body malaise in the past week?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q32" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q32" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q32_remarks">
                                <option value="None">None</option>
                                <option value="Fever">Fever</option>
                                <option value="Headache">Headache</option>
                                <option value="Body Malaise">Body Malaise</option>
                                <option value="Multiple Symptoms">Multiple Symptoms</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Female-specific section (conditional) -->
                <?php if ($isFemale): ?>
                <div class="form-step" data-step="6" id="femaleHealthSection">
                    <div class="step-title">REPRODUCTIVE HEALTH HISTORY:</div>
                    <div class="step-description">Tick the appropriate answer.</div>
                    
                    <div class="form-group">
                        <div class="form-header">#</div>
                        <div class="form-header">Question</div>
                        <div class="form-header">YES</div>
                        <div class="form-header">NO</div>
                        <div class="form-header">REMARKS</div>
                        
                        <div class="question-number">33</div>
                        <div class="question-text">Have you ever been pregnant?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q33" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q33" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q33_remarks">
                                <option value="None">None</option>
                                <option value="Currently Pregnant">Currently Pregnant</option>
                                <option value="Previous Pregnancies">Previous Pregnancies</option>
                                <option value="Multiple Pregnancies">Multiple Pregnancies</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">34</div>
                        <div class="question-text">Have you given birth in the last 6 months?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q34" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q34" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q34_remarks">
                                <option value="None">None</option>
                                <option value="1 month ago">1 month ago</option>
                                <option value="2-3 months ago">2-3 months ago</option>
                                <option value="4-6 months ago">4-6 months ago</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">35</div>
                        <div class="question-text">Have you had a miscarriage or abortion in the last 6 months?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q35" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q35" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q35_remarks">
                                <option value="None">None</option>
                                <option value="Miscarriage">Miscarriage</option>
                                <option value="Abortion">Abortion</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">36</div>
                        <div class="question-text">Are you currently breastfeeding?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q36" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q36" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q36_remarks">
                                <option value="None">None</option>
                                <option value="Exclusive Breastfeeding">Exclusive Breastfeeding</option>
                                <option value="Mixed Feeding">Mixed Feeding</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="question-number">37</div>
                        <div class="question-text">Are you having your menstrual period now?</div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q37" value="Yes" required>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="radio-cell">
                            <label class="radio-container">
                                <input type="radio" name="q37" value="No">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <div class="remarks-cell">
                            <select class="remarks-input" name="q37_remarks">
                                <option value="None">None</option>
                                <option value="Light Flow">Light Flow</option>
                                <option value="Moderate Flow">Moderate Flow</option>
                                <option value="Heavy Flow">Heavy Flow</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Submit button included in the form -->
                <div style="display:none;">
                    <input type="submit" name="submit_medical_history" id="submit_medical_history" value="Submit">
                </div>
            </form>
        </div>
        
        <div class="modal-footer">
            <button class="prev-button" id="prevButton" style="visibility: hidden;">&#8592; Previous</button>
            <button class="next-button" id="nextButton">Next &#8594;</button>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clear localStorage for medical history form on every load
            localStorage.removeItem('medical_history_form_data');
            localStorage.removeItem('medical_history_current_step');

            const form = document.getElementById('medicalHistoryForm');
            const steps = document.querySelectorAll('.form-step');
            const stepIndicators = document.querySelectorAll('.step');
            const prevButton = document.getElementById('prevButton');
            const nextButton = document.getElementById('nextButton');
            const femaleHealthSection = document.getElementById('femaleHealthSection');
            const submitButton = document.getElementById('submit_medical_history');
            
            let currentStep = 1;
            let totalSteps = <?php echo $isFemale ? '6' : '5'; ?>;
            
            // Show a specific step
            function showStep(stepNumber) {
                steps.forEach(step => {
                    step.classList.remove('active');
                });
                
                stepIndicators.forEach(indicator => {
                    indicator.classList.remove('active', 'completed');
                    
                    const indicatorStep = parseInt(indicator.dataset.step);
                    if (indicatorStep === stepNumber) {
                        indicator.classList.add('active');
                    } else if (indicatorStep < stepNumber) {
                        indicator.classList.add('completed');
                    }
                });
                
                // Update connector lines
                const connectors = document.querySelectorAll('.step-connector');
                connectors.forEach(connector => {
                    connector.classList.remove('active');
                    
                    // Extract the step numbers from the connector ID (e.g., line1-2)
                    if (connector.id) {
                        const [from, to] = connector.id.replace('line', '').split('-').map(Number);
                        if (to <= stepNumber) {
                            connector.classList.add('active');
                        }
                    }
                });
                
                // Find and show the current step
                const currentStepElement = document.querySelector(`.form-step[data-step="${stepNumber}"]`);
                if (currentStepElement) {
                    currentStepElement.classList.add('active');
                }
                
                // Update button text for the final step
                if (stepNumber === totalSteps) {
                    nextButton.textContent = 'Submit';
                } else {
                    nextButton.textContent = 'Next \u2192';
                }
                
                // Update the title for the current step
                updateStepTitle(stepNumber);
            }
            
            // Update the step title based on current step number
            function updateStepTitle(stepNumber) {
                const stepTitle = document.querySelector('.step-title');
                if (stepTitle) {
                    switch(stepNumber) {
                        case 1:
                            stepTitle.textContent = 'HEALTH & RISK ASSESSMENT:';
                            break;
                        case 2:
                            stepTitle.textContent = 'PAST 6 MONTHS:';
                            break;
                        case 3:
                            stepTitle.textContent = 'PAST 12 MONTHS:';
                            break;
                        case 4:
                            stepTitle.textContent = 'HAVE YOU EVER:';
                            break;
                        case 5:
                            stepTitle.textContent = 'HAVE YOU HAD ANY OF THE FOLLOWING:';
                            break;
                        case 6:
                            stepTitle.textContent = 'REPRODUCTIVE HEALTH HISTORY:';
                            break;
                    }
                }
            }
            
            // Update the state of navigation buttons
            function updateButtonStates() {
                if (currentStep === 1) {
                    prevButton.style.visibility = 'hidden';
                } else {
                    prevButton.style.visibility = 'visible';
                }
            }
            
            // Validate the current step
            function validateCurrentStep() {
                const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
                const requiredRadios = currentStepElement.querySelectorAll('input[type="radio"]');
                
                // Group radio buttons by name
                const radioGroups = {};
                requiredRadios.forEach(radio => {
                    if (!radioGroups[radio.name]) {
                        radioGroups[radio.name] = [];
                    }
                    radioGroups[radio.name].push(radio);
                });
                
                // Check if at least one radio in each group is selected
                let isValid = true;
                Object.values(radioGroups).forEach(group => {
                    if (!group.some(radio => radio.checked)) {
                        isValid = false;
                    }
                });
                
                return isValid;
            }
            
            // Handle next button click
            nextButton.addEventListener('click', function() {
                if (!validateCurrentStep()) {
                    alert('Please answer all questions before proceeding.');
                    return;
                }
                
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                    updateButtonStates();
                } else {
                    // Form submission
                    if (confirm('Are you sure you want to submit the medical history form?')) {
                        submitButton.click();
                    }
                }
            });
            
            // Handle previous button click
            prevButton.addEventListener('click', function() {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                    updateButtonStates();
                }
            });
            
            // Initialize the form
            showStep(currentStep);
            updateButtonStates();
            
            // Ensure female section is properly handled
            <?php if ($isFemale): ?>
            // Make sure female health section is visible when needed (only on step 6)
            if (femaleHealthSection) {
                // Hide it initially - it will only show on step 6
                femaleHealthSection.style.display = 'none';
                
                // Add mutation observer to watch for active class changes
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'class') {
                            // When the section becomes active (on step 6), display it properly
                            if (femaleHealthSection.classList.contains('active')) {
                                femaleHealthSection.style.display = 'block';
                            } else {
                                femaleHealthSection.style.display = 'none';
                            }
                        }
                    });
                });
                
                observer.observe(femaleHealthSection, { attributes: true });
            }
            <?php endif; ?>
            
            // Auto-save form data to localStorage (PWA enhancement)
            let autoSaveTimer;
            const autoSaveIndicator = document.createElement('div');
            autoSaveIndicator.className = 'auto-save-indicator';
            autoSaveIndicator.textContent = 'Saving...';
            document.body.appendChild(autoSaveIndicator);
            
            // Function to auto-save form data
            function autoSaveForm() {
                if (!form) return;
                
                const formData = new FormData(form);
                const formObject = {};
                
                for (const [key, value] of formData.entries()) {
                    formObject[key] = value;
                }
                
                // Store the form data and current step in localStorage
                try {
                    localStorage.setItem('medical_history_form_data', JSON.stringify(formObject));
                    localStorage.setItem('medical_history_current_step', currentStep.toString());
                    
                    // Show saving indicator
                    autoSaveIndicator.classList.add('saving');
                    setTimeout(() => {
                        autoSaveIndicator.classList.remove('saving');
                    }, 1000);
                    
                    console.log('Form data auto-saved');
                } catch (error) {
                    console.error('Error auto-saving form data:', error);
                }
            }
            
            // Set up auto-save on form changes
            form.addEventListener('change', () => {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(autoSaveForm, 1000);
            });
            
            // Try to restore form data from localStorage
            try {
                const savedFormData = localStorage.getItem('medical_history_form_data');
                const savedStep = localStorage.getItem('medical_history_current_step');
                
                if (savedFormData) {
                    const formObject = JSON.parse(savedFormData);
                    
                    // Populate form fields
                    Object.keys(formObject).forEach(key => {
                        const field = form.elements[key];
                        if (field) {
                            if (field.type === 'radio') {
                                // Find the radio button with the matching value
                                const radio = form.querySelector(`input[name="${key}"][value="${formObject[key]}"]`);
                                if (radio) radio.checked = true;
                            } else {
                                field.value = formObject[key];
                            }
                        }
                    });
                    
                    console.log('Form data restored from local storage');
                }
                
                // Restore current step if available
                if (savedStep) {
                    currentStep = parseInt(savedStep, 10);
                    showStep(currentStep);
                    updateButtonStates();
                }
            } catch (error) {
                console.error('Error restoring form data:', error);
            }
        });
        
        // PWA Offline detection
        function updateOnlineStatus() {
            const offlineIndicator = document.getElementById('offlineIndicator');
            if (!offlineIndicator) return;
            
            if (navigator.onLine) {
                offlineIndicator.style.display = 'none';
            } else {
                offlineIndicator.style.display = 'block';
            }
        }
        
        // Add event listeners for online/offline events
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        
        // Initial check
        document.addEventListener('DOMContentLoaded', updateOnlineStatus);
        
        // Register service worker for PWA functionality
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../../service-worker.js')
                    .then(registration => {
                        console.log('Service Worker registered with scope:', registration.scope);
                    })
                    .catch(error => {
                        console.error('Service Worker registration failed:', error);
                    });
            });
        }
    </script>
    
    <!-- Offline indicator -->
    <div id="offlineIndicator" class="offline-indicator">
        You are currently offline. Your data will be saved locally and submitted when online.
    </div>
</body>
</html> 