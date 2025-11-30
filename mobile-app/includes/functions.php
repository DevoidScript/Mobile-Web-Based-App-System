<?php
/**
 * Common helper functions for the PWA
 */

// Include email configuration
require_once __DIR__ . '/../config/email.php';

/**
 * Sanitize input to prevent XSS
 * 
 * @param string $data Input to sanitize
 * @return string Sanitized input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool Whether the email is valid
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a secure random token
 * 
 * @param int $length Length of the token
 * @return string Random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if the request is AJAX
 * 
 * @return bool Whether the request is AJAX
 */
function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get the base URL of the application
 * 
 * @return string Base URL
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    
    return "$protocol://$host$script";
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Get current user from session
 * Renamed from get_current_user() to get_logged_in_user() to avoid conflicts with PHP's built-in function
 * 
 * @return array|null User data or null if not logged in
 */
function get_logged_in_user() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

/**
 * Check if user is logged in
 * 
 * @return bool Whether the user is logged in
 */
function is_logged_in() {
    return get_logged_in_user() !== null;
}

/**
 * Set flash message
 * 
 * @param string $type Message type (success, error, info, warning)
 * @param string $message Message content
 * @return void
 */
function set_flash_message($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_messages'][$type] = $message;
}

/**
 * Get flash messages and clear them
 * 
 * @return array Flash messages
 */
function get_flash_messages() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
    
    // Clear flash messages
    $_SESSION['flash_messages'] = [];
    
    return $messages;
}

/**
 * Check if a donor has a successful donation in the eligibility table
 * Returns an array: [bool has_donated, array|null latest_donation]
 *
 * @param int $donor_id
 * @return array [bool, array|null]
 */
function has_successful_donation($donor_id) {
    $params = [
        'donor_id' => 'eq.' . $donor_id,
        'collection_successful' => 'eq.true',
        'order' => 'collection_start_time.desc'
    ];
    $result = get_records('eligibility', $params);
    if ($result['success'] && !empty($result['data'])) {
        return [true, $result['data'][0]];
    }
    return [false, null];
}

/**
 * Get the latest donor_form record for a donor by donor_id
 * @param string $donor_id Donor UUID
 * @return array Supabase response
 */
function get_donor_form_by_donor_id($donor_id) {
    $params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'id.desc',
        'limit' => 1
    ];
    return get_records('donor_form', $params);
}

/**
 * Build tracker data for a donation
 * 
 * @param array $donation The donation record
 * @return array The tracker data
 */
function build_tracker_data($donation) {
    $donation_id = $donation['donation_id'];
    $current_status = $donation['current_status'];
    
    // Get status history for this donation
    $history_params = [
        'donation_id' => 'eq.' . $donation_id,
        'order' => 'changed_at.asc'
    ];
    
    $history_result = get_records('donation_status_history', $history_params);
    $status_history = $history_result['success'] ? $history_result['data'] : [];
    
    // Define stage mapping and progress
    $stages = [
        'Registered' => [
            'name' => 'Registration',
            'description' => 'Medical history completed - donation registered',
            'icon' => '📝',
            'progress' => 10,
            'next_stage' => 'Sample Collected'
        ],
        'Sample Collected' => [
            'name' => 'Sample Collection',
            'description' => 'Blood sample collected from donor',
            'icon' => '🩸',
            'progress' => 25,
            'next_stage' => 'Testing',
            'form_required' => 'physical_examination'
        ],
        'Testing' => [
            'name' => 'Blood Testing',
            'description' => 'Laboratory testing and analysis',
            'icon' => '🧪',
            'progress' => 60,
            'next_stage' => 'Testing Complete',
            'form_required' => 'blood_collection'
        ],
        'Testing Complete' => [
            'name' => 'Testing Complete',
            'description' => 'All tests completed successfully',
            'icon' => '✅',
            'progress' => 80,
            'next_stage' => 'Processed'
        ],
        'Processed' => [
            'name' => 'Processing Complete',
            'description' => 'Blood processed and prepared',
            'icon' => '⚙️',
            'progress' => 90,
            'next_stage' => 'Ready for Use'
        ],
        'Ready for Use' => [
            'name' => 'Ready for Use',
            'description' => 'Blood is ready for distribution',
            'icon' => '🚀',
            'progress' => 100,
            'next_stage' => null
        ]
    ];
    
    // Build stage display data
    $stage_display = [];
    $current_progress = 0;
    
    foreach ($stages as $status_name => $stage_info) {
        $stage_status = 'pending';
        $completed_at = null;
        
        // Check if this stage is completed
        foreach ($status_history as $history_item) {
            if ($history_item['status'] === $status_name) {
                $stage_status = 'completed';
                $completed_at = $history_item['changed_at'];
                $current_progress = max($current_progress, $stage_info['progress']);
                break;
            }
        }
        
        // Check if this is the current stage
        if ($status_name === $current_status) {
            $stage_status = 'current';
            $current_progress = $stage_info['progress'];
        }
        
        $stage_display[] = [
            'name' => $stage_info['name'],
            'description' => $stage_info['description'],
            'icon' => $stage_info['icon'],
            'status' => $stage_status,
            'completed_at' => $completed_at,
            'progress' => $stage_info['progress'],
            'next_stage' => $stage_info['next_stage']
        ];
    }
    
    return [
        'donation_id' => $donation_id,
        'current_status' => $current_status,
        'progress' => $current_progress,
        'donation_date' => $donation['donation_date'],
        'blood_type' => $donation['blood_type'],
        'units_collected' => $donation['units_collected'],
        'stages' => $stage_display,
        'status_history' => $status_history,
        'form_status' => [
            'medical_history' => $donation['medical_history_completed'] ?? false,
            'physical_examination' => $donation['physical_examination_completed'] ?? false,
            'screening' => $donation['screening_completed'] ?? false,
            'blood_collection' => $donation['blood_collection_completed'] ?? false
        ]
    ];
}

/**
 * Start donation process for a donor
 * 
 * @param int $donor_id The donor ID
 * @return array The result of starting the donation
 */
function start_donation_process($donor_id) {
    // Ensure donor_id is an integer
    $donor_id = intval($donor_id);
    
    if ($donor_id <= 0) {
        error_log("Invalid donor_id: " . $donor_id);
        return ['success' => false, 'error' => 'Invalid donor ID'];
    }
    
    // Check if donor already has an active donation
    $existing_params = [
        'donor_id' => 'eq.' . $donor_id,
        'current_status' => 'not.eq.Ready for Use',
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $existing_result = get_records('donations', $existing_params);
    
    if ($existing_result['success'] && !empty($existing_result['data'])) {
        return ['success' => false, 'error' => 'Donor already has an active donation'];
    }
    
    // Create new donation record
    $donation_data = [
        'donor_id' => $donor_id,
        'current_status' => 'Registered',
        'donation_date' => date('Y-m-d'),
        'blood_type' => null,
        'units_collected' => 1.0,
        'notes' => 'Donation process initiated via medical history form',
        'medical_history_completed' => false,
        'physical_examination_completed' => false,
        'screening_completed' => false,
        'blood_collection_completed' => false
    ];
    
    error_log("Creating donation record with data: " . json_encode($donation_data));
    $result = create_record('donations', $donation_data);
    error_log("Create record result: " . json_encode($result));
    
    if ($result['success']) {
        return [
            'success' => true,
            'donation_id' => $result['data'][0]['donation_id'],
            'message' => 'Donation process started successfully'
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to start donation process: ' . ($result['error'] ?? 'Unknown error')];
    }
} 

/**
 * Update donation status when screening form is completed
 * 
 * @param int $donor_id The donor ID
 * @return array The result of the update
 */
function update_donation_status_after_screening($donor_id) {
    // First, get the donor form record to find the correct donor_form_id
    $donor_form_params = ['donor_id' => 'eq.' . $donor_id];
    $donor_form_result = get_records('donor_form', $donor_form_params);
    if (!$donor_form_result['success'] || empty($donor_form_result['data'])) {
        return ['success' => false, 'error' => 'No donor form found for donor ID: ' . $donor_id];
    }
    
    $donor_form = $donor_form_result['data'][0];
    $donor_form_id = $donor_form['donor_id']; // This is the actual donor_form_id
    
    // Get the latest screening form for this donor using donor_form_id
    $screening_params = [
        'donor_form_id' => 'eq.' . $donor_form_id,
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $screening_result = get_records('screening_form', $screening_params);
    if (!$screening_result['success'] || empty($screening_result['data'])) {
        return ['success' => false, 'error' => 'No screening form found for donor ID: ' . $donor_id];
    }
    
    $screening = $screening_result['data'][0];
    
    // Check if there's a disapproval reason
    if (!empty($screening['disapproval_reason'])) {
        // Donor is disapproved - set to Ready for Use (cancelled)
        $donation_params = [
            'donor_id' => 'eq.' . $donor_id,
            'current_status' => 'not.eq.Ready for Use',
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $donation_result = get_records('donations', $donation_params);
        if ($donation_result['success'] && !empty($donation_result['data'])) {
            $donation = $donation_result['data'][0];
            
            // Update donation status to Ready for Use (cancelled)
            $update_data = [
                'current_status' => 'Ready for Use',
                'notes' => $screening['disapproval_reason'] // Use disapproval_reason as notes
            ];
            
            $update_result = update_record('donations', $donation['donation_id'], $update_data, 'donation_id');
            if ($update_result['success']) {
                return [
                    'success' => true,
                    'status' => 'cancelled',
                    'message' => 'Donation cancelled due to screening disapproval',
                    'reason' => $screening['disapproval_reason']
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Failed to cancel donation'];
    }
    
    // No disapproval - proceed to next status
    $donation_params = [
        'donor_id' => 'eq.' . $donor_id,
        'current_status' => 'not.eq.Ready for Use',
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $donation_result = get_records('donations', $donation_params);
    if (!$donation_result['success'] || empty($donation_result['data'])) {
        return ['success' => false, 'error' => 'No active donation found for donor ID: ' . $donor_id];
    }
    
    $donation = $donation_result['data'][0];
    
    // Update donation status to Sample Collected
    $update_data = [
        'current_status' => 'Sample Collected',
        'screening_completed' => true,
        'notes' => 'Screening completed successfully - proceeding to next stage'
    ];
    
    // Update blood_type if available in screening form
    if (!empty($screening['blood_type'])) {
        $update_data['blood_type'] = $screening['blood_type'];
    }
    
    $update_result = update_record('donations', $donation['donation_id'], $update_data, 'donation_id');
    if ($update_result['success']) {
        return [
            'success' => true,
            'status' => 'updated',
            'message' => 'Donation status updated to Sample Collected',
            'blood_type' => $screening['blood_type'] ?? null
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to update donation status'];
} 

/**
 * Update donation status when physical examination is completed
 * 
 * @param int $donor_id The donor ID
 * @return array The result of the update
 */
function update_donation_status_after_physical_exam($donor_id) {
    // Get the latest physical examination for this donor
    $exam_params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $exam_result = get_records('physical_examination', $exam_params);
    if (!$exam_result['success'] || empty($exam_result['data'])) {
        return ['success' => false, 'error' => 'No physical examination found for donor ID: ' . $donor_id];
    }
    
    $exam = $exam_result['data'][0];
    
    // Check if there are deferral remarks
    $remarks = strtolower($exam['remarks'] ?? '');
    if (strpos($remarks, 'permanently deferred') !== false || strpos($remarks, 'temporarily deferred') !== false) {
        // Donor is deferred - set to Ready for Use (cancelled)
        $donation_params = [
            'donor_id' => 'eq.' . $donor_id,
            'current_status' => 'not.eq.Ready for Use',
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $donation_result = get_records('donations', $donation_params);
        if ($donation_result['success'] && !empty($donation_result['data'])) {
            $donation = $donation_result['data'][0];
            
            // Update donation status to Ready for Use (cancelled)
            $update_data = [
                'current_status' => 'Ready for Use',
                'notes' => $exam['remarks'] // Use remarks as notes
            ];
            
            $update_result = update_record('donations', $donation['donation_id'], $update_data, 'donation_id');
            if ($update_result['success']) {
                return [
                    'success' => true,
                    'status' => 'cancelled',
                    'message' => 'Donation cancelled due to physical examination deferral',
                    'reason' => $exam['remarks']
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Failed to cancel donation'];
    }
    
    // No deferral - proceed to next status
    $donation_params = [
        'donor_id' => 'eq.' . $donor_id,
        'current_status' => 'not.eq.Ready for Use',
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $donation_result = get_records('donations', $donation_params);
    if (!$donation_result['success'] || empty($donation_result['data'])) {
        return ['success' => false, 'error' => 'No active donation found for donor ID: ' . $donor_id];
    }
    
    $donation = $donation_result['data'][0];
    
    // Update donation status to Testing
    $update_data = [
        'current_status' => 'Testing',
        'physical_examination_completed' => true,
        'notes' => 'Physical examination completed successfully - proceeding to testing stage'
    ];
    
    $update_result = update_record('donations', $donation['donation_id'], $update_data, 'donation_id');
    if ($update_result['success']) {
        return [
            'success' => true,
            'status' => 'updated',
            'message' => 'Donation status updated to Testing'
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to update donation status'];
} 

/**
 * Update donation status when blood collection is completed
 * 
 * @param int $donor_id The donor ID
 * @return array The result of the update
 */
function update_donation_status_after_blood_collection($donor_id) {
    // First, get the donor form record to find the correct donor_form_id
    $donor_form_params = ['donor_id' => 'eq.' . $donor_id];
    $donor_form_result = get_records('donor_form', $donor_form_params);
    if (!$donor_form_result['success'] || empty($donor_form_result['data'])) {
        return ['success' => false, 'error' => 'No donor form found for donor ID: ' . $donor_id];
    }
    
    $donor_form = $donor_form_result['data'][0];
    $donor_form_id = $donor_form['donor_id']; // This is the actual donor_form_id
    
    // Get the latest physical examination to find the physical_exam_id
    $exam_params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $exam_result = get_records('physical_examination', $exam_params);
    if (!$exam_result['success'] || empty($exam_result['data'])) {
        return ['success' => false, 'error' => 'No physical examination found for donor ID: ' . $donor_id];
    }
    
    $exam = $exam_result['data'][0];
    $physical_exam_id = $exam['physical_exam_id'];
    
    // Get the latest blood bank unit for this donor
    $blood_bank_params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $blood_bank_result = get_records('blood_bank_units', $blood_bank_params);
    if ($blood_bank_result['success'] && !empty($blood_bank_result['data'])) {
        $blood_bank_unit = $blood_bank_result['data'][0];
        $unit_status = $blood_bank_unit['status'] ?? '';
        $units = floatval($blood_bank_unit['units'] ?? 0);
        
        // Get the active donation for this donor
        $donation_params = [
            'donor_id' => 'eq.' . $donor_id,
            'current_status' => 'not.eq.Ready for Use',
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $donation_result = get_records('donations', $donation_params);
        if (!$donation_result['success'] || empty($donation_result['data'])) {
            return ['success' => false, 'error' => 'No active donation found for donor ID: ' . $donor_id];
        }
        
        $donation = $donation_result['data'][0];
        
        // Check blood bank unit status to determine final status
        if ($unit_status === 'Valid') {
            // Blood unit is valid - status becomes Processed
            $update_data = [
                'current_status' => 'Processed',
                'blood_collection_completed' => true,
                'units_collected' => $units,
                'notes' => 'Blood unit status is Valid - ' . $units . ' units processed'
            ];
        } elseif ($unit_status === 'Disposed' || $unit_status === 'Expired') {
            // Blood unit is disposed or expired - status becomes Ready for Use
            $update_data = [
                'current_status' => 'Ready for Use',
                'blood_collection_completed' => true,
                'units_collected' => $units,
                'notes' => 'Blood unit status is ' . $unit_status . ' - ' . $units . ' units'
            ];
        } else {
            // Invalid status
            return ['success' => false, 'error' => 'Invalid blood bank unit status: ' . $unit_status];
        }
    } else {
        // Fallback to blood_collection table if blood_bank_units not found
        $collection_params = [
            'physical_exam_id' => 'eq.' . $physical_exam_id,
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $collection_result = get_records('blood_collection', $collection_params);
        if (!$collection_result['success'] || empty($collection_result['data'])) {
            return ['success' => false, 'error' => 'No blood collection or blood bank unit record found for donor ID: ' . $donor_id];
        }
        
        $collection = $collection_result['data'][0];
        
        // Get the active donation for this donor
        $donation_params = [
            'donor_id' => 'eq.' . $donor_id,
            'current_status' => 'not.eq.Ready for Use',
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $donation_result = get_records('donations', $donation_params);
        if (!$donation_result['success'] || empty($donation_result['data'])) {
            return ['success' => false, 'error' => 'No active donation found for donor ID: ' . $donor_id];
        }
        
        $donation = $donation_result['data'][0];
        
        // Check amount_taken to determine final status (fallback)
        $amount_taken = floatval($collection['amount_taken'] ?? 0);
        
        if ($amount_taken >= 1) {
            // Blood was collected - status becomes Processed
            $update_data = [
                'current_status' => 'Processed',
                'blood_collection_completed' => true,
                'units_collected' => $amount_taken,
                'notes' => 'Blood collection completed successfully - ' . $amount_taken . ' units collected'
            ];
        } elseif ($amount_taken == 0) {
            // No blood collected - status becomes Ready for Use (cancelled)
            $update_data = [
                'current_status' => 'Ready for Use',
                'blood_collection_completed' => true,
                'units_collected' => 0,
                'notes' => 'Blood collection cancelled - no units collected'
            ];
        } else {
            // Invalid amount
            return ['success' => false, 'error' => 'Invalid amount_taken value: ' . $amount_taken];
        }
    }
    
    $update_result = update_record('donations', $donation['donation_id'], $update_data, 'donation_id');
    if ($update_result['success']) {
        return [
            'success' => true,
            'status' => 'updated',
            'message' => 'Donation status updated to ' . $update_data['current_status'],
            'final_status' => $update_data['current_status'],
            'units_collected' => $update_data['units_collected']
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to update donation status'];
} 

/**
 * Automatically update donation status when medical history is completed
 * This function should be called immediately after medical history submission
 * 
 * @param int $donor_id The donor ID
 * @param bool $is_update Whether this is an update to existing medical history (true) or new insert (false)
 * @return array The result of the status update
 */
function auto_update_donation_status_after_medical_history($donor_id, $is_update = false) {
    // Ensure donor_id is valid
    $donor_id = intval($donor_id);
    if ($donor_id <= 0) {
        return ['success' => false, 'error' => 'Invalid donor ID'];
    }
    
    // Get the active donation for this donor
    $donation_params = [
        'donor_id' => 'eq.' . $donor_id,
        'current_status' => 'not.eq.Ready for Use',
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $donation_result = get_records('donations', $donation_params);
    if (!$donation_result['success'] || empty($donation_result['data'])) {
        return ['success' => false, 'error' => 'No active donation found for donor'];
    }
    
    $donation = $donation_result['data'][0];
    $donation_id = $donation['donation_id'];
    $current_status = $donation['current_status'];
    
    // If this is an update to existing medical history, check if PE forms are still valid
    // by comparing their updated_at dates with the medical history updated_at
    if ($is_update) {
        error_log("Medical history was updated - checking if PE forms are still valid for donor_id: " . $donor_id);
        
        // Get the medical history updated_at timestamp
        $medical_history_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'updated_at.desc',
            'limit' => 1
        ];
        $medical_history_result = get_records('medical_history', $medical_history_params);
        
        $medical_history_updated_at = null;
        if ($medical_history_result['success'] && !empty($medical_history_result['data'])) {
            $medical_history_updated_at = $medical_history_result['data'][0]['updated_at'] ?? null;
            error_log("Medical history updated_at: " . ($medical_history_updated_at ?? 'NULL'));
        }
        
        // Check if physical examination exists and when it was last updated
        $exam_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'updated_at.desc',
            'limit' => 1
        ];
        $exam_result = get_records('physical_examination', $exam_params);
        
        $pe_is_valid = false;
        if ($exam_result['success'] && !empty($exam_result['data'])) {
            $exam = $exam_result['data'][0];
            $pe_updated_at = $exam['updated_at'] ?? null;
            
            // PE is valid only if it was updated AFTER the medical history update
            if ($medical_history_updated_at && $pe_updated_at) {
                $mh_timestamp = strtotime($medical_history_updated_at);
                $pe_timestamp = strtotime($pe_updated_at);
                $pe_is_valid = ($pe_timestamp > $mh_timestamp);
                error_log("PE updated_at: $pe_updated_at, MH updated_at: $medical_history_updated_at, PE is valid: " . ($pe_is_valid ? 'true' : 'false'));
            }
        }
        
        // If PE is not valid (doesn't exist or was updated before MH), reset status
        if (!$pe_is_valid) {
            error_log("PE form is outdated or missing - resetting donation status to Registered");
            $reset_data = [
                'current_status' => 'Registered',
                'screening_completed' => false,
                'physical_examination_completed' => false,
                'notes' => 'Medical history updated - status reset to Registered. Physical examination needs to be re-evaluated.'
            ];
            
            $reset_result = update_record('donations', $donation_id, $reset_data, 'donation_id');
            if ($reset_result['success']) {
                return [
                    'success' => true,
                    'status' => 'reset',
                    'message' => 'Donation status reset to Registered. Please complete physical examination again.'
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to reset donation status'];
            }
        } else {
            // PE is valid (updated after MH), so we can proceed with normal status check
            error_log("PE form is valid (updated after medical history) - proceeding with normal status check");
            // Continue with the normal flow below
        }
    }
    
    // If status is already beyond Registered, no need to update
    if ($current_status !== 'Registered') {
        return [
            'success' => true, 
            'status' => 'no_update_needed',
            'message' => 'Donation status is already beyond Registered stage'
        ];
    }
    
    // Check if screening form exists and is completed
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
            
            // Check for disapproval
            if (!empty($screening['disapproval_reason'])) {
                // Donor is disapproved - set to Ready for Use
                $update_data = [
                    'current_status' => 'Ready for Use',
                    'notes' => 'Donation cancelled due to screening disapproval: ' . $screening['disapproval_reason']
                ];
                
                $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
                if ($update_result['success']) {
                    return [
                        'success' => true,
                        'status' => 'cancelled',
                        'message' => 'Donation cancelled due to screening disapproval',
                        'reason' => $screening['disapproval_reason']
                    ];
                }
            } else {
                // No disapproval - update to Sample Collected
                $update_data = [
                    'current_status' => 'Sample Collected',
                    'screening_completed' => true,
                    'notes' => 'Screening completed successfully - proceeding to next stage'
                ];
                
                if (!empty($screening['blood_type'])) {
                    $update_data['blood_type'] = $screening['blood_type'];
                }
                
                $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
                if ($update_result['success']) {
                    return [
                        'success' => true,
                        'status' => 'updated',
                        'message' => 'Donation status updated to Sample Collected',
                        'blood_type' => $screening['blood_type'] ?? null
                    ];
                }
            }
        }
    }
    
    // If no screening form found, check if physical examination exists
    $exam_params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $exam_result = get_records('physical_examination', $exam_params);
    if ($exam_result['success'] && !empty($exam_result['data'])) {
        $exam = $exam_result['data'][0];
        
        // Check for deferrals
        $remarks = strtolower($exam['remarks'] ?? '');
        if (strpos($remarks, 'permanently deferred') !== false || strpos($remarks, 'temporarily deferred') !== false) {
            // Donor is deferred - set to Ready for Use
            $update_data = [
                'current_status' => 'Ready for Use',
                'notes' => 'Donation cancelled due to physical examination deferral: ' . $exam['remarks']
            ];
            
            $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
            if ($update_result['success']) {
                return [
                    'success' => true,
                    'status' => 'cancelled',
                    'message' => 'Donation cancelled due to physical examination deferral',
                    'reason' => $exam['remarks']
                ];
            }
        } else {
            // No deferral - update to Testing
            $update_data = [
                'current_status' => 'Testing',
                'physical_examination_completed' => true,
                'notes' => 'Physical examination completed successfully - proceeding to testing stage'
            ];
            
            $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
            if ($update_result['success']) {
                return [
                    'success' => true,
                    'status' => 'updated',
                    'message' => 'Donation status updated to Testing'
                ];
            }
        }
    }
    
    return [
        'success' => true,
        'status' => 'no_update_needed',
        'message' => 'No status update needed at this time'
    ];
}

/**
 * Automatically check and update donation status based on form completion
 * This function should be called after each form is submitted
 * 
 * @param int $donor_id The donor ID
 * @return array The result of the status update
 */
function auto_update_donation_status($donor_id) {
    // Ensure donor_id is valid
    $donor_id = intval($donor_id);
    if ($donor_id <= 0) {
        return ['success' => false, 'error' => 'Invalid donor ID'];
    }
    
    // Get the active donation for this donor
    $donation_params = [
        'donor_id' => 'eq.' . $donor_id,
        'current_status' => 'not.eq.Ready for Use',
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $donation_result = get_records('donations', $donation_params);
    if (!$donation_result['success'] || empty($donation_result['data'])) {
        return ['success' => false, 'error' => 'No active donation found for donor'];
    }
    
    $donation = $donation_result['data'][0];
    $current_status = $donation['current_status'];
    $donation_id = $donation['donation_id'];
    
    // Check each stage in order and update accordingly
    if ($current_status === 'Registered') {
        // Check screening form first
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
                
                // Check for disapproval
                if (!empty($screening['disapproval_reason'])) {
                    // Donor is disapproved - set to Ready for Use
                    $update_data = [
                        'current_status' => 'Ready for Use',
                        'notes' => 'Donation cancelled due to screening disapproval: ' . $screening['disapproval_reason']
                    ];
                    
                    $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
                    if ($update_result['success']) {
                        return [
                            'success' => true,
                            'status' => 'cancelled',
                            'message' => 'Donation cancelled due to screening disapproval',
                            'reason' => $screening['disapproval_reason']
                        ];
                    }
                } else {
                    // No disapproval - update to Sample Collected
                    $update_data = [
                        'current_status' => 'Sample Collected',
                        'screening_completed' => true,
                        'notes' => 'Screening completed successfully - proceeding to next stage'
                    ];
                    
                    if (!empty($screening['blood_type'])) {
                        $update_data['blood_type'] = $screening['blood_type'];
                    }
                    
                    $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
                    if ($update_result['success']) {
                        return [
                            'success' => true,
                            'status' => 'updated',
                            'message' => 'Donation status updated to Sample Collected',
                            'blood_type' => $screening['blood_type'] ?? null
                        ];
                    }
                }
            }
        }
        
        // If screening is not completed, check physical examination
        $exam_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $exam_result = get_records('physical_examination', $exam_params);
        if ($exam_result['success'] && !empty($exam_result['data'])) {
            $exam = $exam_result['data'][0];
            
            // Check for deferrals
            $remarks = strtolower($exam['remarks'] ?? '');
            if (strpos($remarks, 'permanently deferred') !== false || strpos($remarks, 'temporarily deferred') !== false) {
                // Donor is deferred - set to Ready for Use
                $update_data = [
                    'current_status' => 'Ready for Use',
                    'notes' => 'Donation cancelled due to physical examination deferral: ' . $exam['remarks']
                ];
                
                $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
                if ($update_result['success']) {
                    return [
                        'success' => true,
                        'status' => 'cancelled',
                        'message' => 'Donation cancelled due to physical examination deferral',
                        'reason' => $exam['remarks']
                    ];
                }
            } else {
                // No deferral - update to Testing
                $update_data = [
                    'current_status' => 'Testing',
                    'physical_examination_completed' => true,
                    'notes' => 'Physical examination completed successfully - proceeding to testing stage'
                ];
                
                $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
                if ($update_result['success']) {
                    return [
                        'success' => true,
                        'status' => 'updated',
                        'message' => 'Donation status updated to Testing'
                    ];
                }
            }
        }
    }
    
    if ($current_status === 'Sample Collected') {
        // Check if physical examination is completed
        $exam_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $exam_result = get_records('physical_examination', $exam_params);
        if ($exam_result['success'] && !empty($exam_result['data'])) {
            $exam = $exam_result['data'][0];
            
            // Check for deferrals
            $remarks = strtolower($exam['remarks'] ?? '');
            if (strpos($remarks, 'permanently deferred') !== false || strpos($remarks, 'temporarily deferred') !== false) {
                // Donor is deferred - set to Ready for Use
                $update_data = [
                    'current_status' => 'Ready for Use',
                    'notes' => 'Donation cancelled due to physical examination deferral: ' . $exam['remarks']
                ];
                
                $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
                if ($update_result['success']) {
                    return [
                        'success' => true,
                        'status' => 'cancelled',
                        'message' => 'Donation cancelled due to physical examination deferral',
                        'reason' => $exam['remarks']
                    ];
                }
            } else {
                // No deferral - update to Testing
                $update_data = [
                    'current_status' => 'Testing',
                    'physical_examination_completed' => true,
                    'notes' => 'Physical examination completed successfully - proceeding to testing stage'
                ];
                
                $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
                if ($update_result['success']) {
                    return [
                        'success' => true,
                        'status' => 'updated',
                        'message' => 'Donation status updated to Testing'
                    ];
                }
            }
        }
    }
    
    if ($current_status === 'Testing') {
        // Check if blood bank units are available
        $blood_bank_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $blood_bank_result = get_records('blood_bank_units', $blood_bank_params);
        if ($blood_bank_result['success'] && !empty($blood_bank_result['data'])) {
            $blood_bank_unit = $blood_bank_result['data'][0];
            $unit_status = $blood_bank_unit['status'] ?? '';
            $units = floatval($blood_bank_unit['units'] ?? 0);
            
            if ($unit_status === 'Valid') {
                // Blood unit is valid - update to Processed
                $update_data = [
                    'current_status' => 'Processed',
                    'blood_collection_completed' => true,
                    'units_collected' => $units,
                    'notes' => 'Blood unit status is Valid - ' . $units . ' units processed'
                ];
            } elseif ($unit_status === 'Disposed' || $unit_status === 'Expired') {
                // Blood unit is disposed or expired - update to Ready for Use
                $update_data = [
                    'current_status' => 'Ready for Use',
                    'blood_collection_completed' => true,
                    'units_collected' => $units,
                    'notes' => 'Blood unit status is ' . $unit_status . ' - ' . $units . ' units'
                ];
            } else {
                return ['success' => false, 'error' => 'Invalid blood bank unit status: ' . $unit_status];
            }
            
            $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
            if ($update_result['success']) {
                return [
                    'success' => true,
                    'status' => 'updated',
                    'message' => 'Donation status updated to ' . $update_data['current_status'],
                    'final_status' => $update_data['current_status'],
                    'units_collected' => $update_data['units_collected']
                ];
            }
        } else {
            // Fallback to blood_collection table if blood_bank_units not found
            $exam_params = [
                'donor_id' => 'eq.' . $donor_id,
                'order' => 'created_at.desc',
                'limit' => 1
            ];
            
            $exam_result = get_records('physical_examination', $exam_params);
            if ($exam_result['success'] && !empty($exam_result['data'])) {
                $exam = $exam_result['data'][0];
                $physical_exam_id = $exam['physical_exam_id'];
                
                $collection_params = [
                    'physical_exam_id' => 'eq.' . $physical_exam_id,
                    'order' => 'created_at.desc',
                    'limit' => 1
                ];
                
                $collection_result = get_records('blood_collection', $collection_params);
                if ($collection_result['success'] && !empty($collection_result['data'])) {
                    $collection = $collection_result['data'][0];
                    
                    $amount_taken = floatval($collection['amount_taken'] ?? 0);
                    
                    if ($amount_taken >= 1) {
                        // Blood collected - update to Processed
                        $update_data = [
                            'current_status' => 'Processed',
                            'blood_collection_completed' => true,
                            'units_collected' => $amount_taken,
                            'notes' => 'Blood collection completed successfully - ' . $amount_taken . ' units collected'
                        ];
                    } elseif ($amount_taken == 0) {
                        // No blood collected - update to Ready for Use
                        $update_data = [
                            'current_status' => 'Ready for Use',
                            'blood_collection_completed' => true,
                            'units_collected' => 0,
                            'notes' => 'Blood collection cancelled - no units collected'
                        ];
                    } else {
                        return ['success' => false, 'error' => 'Invalid amount_taken value: ' . $amount_taken];
                    }
                    
                    $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
                    if ($update_result['success']) {
                        return [
                            'success' => true,
                            'status' => 'updated',
                            'message' => 'Donation status updated to ' . $update_data['current_status'],
                            'final_status' => $update_data['current_status'],
                            'units_collected' => $update_data['units_collected']
                        ];
                    }
                }
            }
        }
    }
    
    return [
        'success' => true,
        'status' => 'no_update_needed',
        'message' => 'No status update needed at this time'
    ];
} 

/**
 * Get donor ID from email (helper function for donor identification)
 * 
 * @param string $email The donor's email address
 * @return int|false The donor ID or false if not found
 */
function get_donor_id_from_email($email) {
    if (empty($email)) {
        return false;
    }
    
    $email = trim(strtolower($email));
    $donor_form_params = ['email' => 'eq.' . $email];
    $donor_form_result = get_records('donor_form', $donor_form_params);
    
    if ($donor_form_result['success'] && !empty($donor_form_result['data'])) {
        $donor_form = $donor_form_result['data'][0];
        return intval($donor_form['donor_id']);
    }
    
    return false;
} 

/**
 * Check and update donation status automatically when forms are modified
 * This function should be called periodically or triggered by database changes
 * 
 * @param int $donor_id The donor ID to check
 * @return array The result of the status update
 */
function check_and_update_donation_status_automatically($donor_id) {
    // Ensure donor_id is valid
    $donor_id = intval($donor_id);
    if ($donor_id <= 0) {
        return ['success' => false, 'error' => 'Invalid donor ID'];
    }
    
    // Get the active donation for this donor (get all and filter in PHP to avoid query limitations)
    $donation_params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'created_at.desc',
        'limit' => 10  // Get multiple to filter completed ones
    ];
    
    $donation_result = get_records('donations', $donation_params);
    if (!$donation_result['success'] || empty($donation_result['data'])) {
        return ['success' => false, 'error' => 'No donation found for donor'];
    }
    
    // Filter out completed statuses (Used, Expired, Ready for Use) - find the first non-completed one
    $completed_statuses = ['Used', 'Expired', 'Ready for Use'];
    $donation = null;
    foreach ($donation_result['data'] as $don) {
        if (!in_array($don['current_status'], $completed_statuses)) {
            $donation = $don;
            break;
        }
    }
    
    // If all donations are completed, use the latest one anyway (for status updates)
    if (!$donation) {
        $donation = $donation_result['data'][0];
    }
    
    $donation_id = $donation['donation_id'];
    $current_status = $donation['current_status'];
    
    // If status is already "Used", "Expired", or "Ready for Use", don't update (prevent unnecessary updates)
    if (in_array($current_status, ['Used', 'Expired', 'Ready for Use'])) {
        return [
            'success' => true,
            'status' => 'no_update_needed',
            'message' => 'Donation already completed - no update needed'
        ];
    }
    
    $status_changed = false;
    $update_data = [];
    
    // Check screening form for updates
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
            
            // Check if screening was just completed and status needs update
            if ($current_status === 'Registered' && empty($screening['disapproval_reason'])) {
                $update_data = [
                    'current_status' => 'Sample Collected',
                    'screening_completed' => true,
                    'notes' => 'Screening completed automatically - proceeding to next stage'
                ];
                
                if (!empty($screening['blood_type'])) {
                    $update_data['blood_type'] = $screening['blood_type'];
                }
                
                $status_changed = true;
            } elseif ($current_status === 'Registered' && !empty($screening['disapproval_reason'])) {
                // Donor disapproved
                $update_data = [
                    'current_status' => 'Ready for Use',
                    'notes' => 'Donation cancelled automatically due to screening disapproval: ' . $screening['disapproval_reason']
                ];
                $status_changed = true;
            }
        }
    }
    
    // Check physical examination for updates
    if (!$status_changed) {
        $exam_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $exam_result = get_records('physical_examination', $exam_params);
        if ($exam_result['success'] && !empty($exam_result['data'])) {
            $exam = $exam_result['data'][0];
            
            // Check if physical examination was just completed
            if ($current_status === 'Sample Collected' || $current_status === 'Registered') {
                $remarks = strtolower($exam['remarks'] ?? '');
                
                if (strpos($remarks, 'permanently deferred') !== false || strpos($remarks, 'temporarily deferred') !== false) {
                    // Donor deferred
                    $update_data = [
                        'current_status' => 'Ready for Use',
                        'notes' => 'Donation cancelled automatically due to physical examination deferral: ' . $exam['remarks']
                    ];
                    $status_changed = true;
                } elseif ($current_status === 'Sample Collected') {
                    // Update to Testing
                    $update_data = [
                        'current_status' => 'Testing',
                        'physical_examination_completed' => true,
                        'notes' => 'Physical examination completed automatically - proceeding to testing stage'
                    ];
                    $status_changed = true;
                }
            }
        }
    }
    
    // Check blood bank units for updates
    if (!$status_changed) {
        // Get the latest blood bank unit record for this donor
        $blood_bank_params = [
            'donor_id' => 'eq.' . $donor_id,
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $blood_bank_result = get_records('blood_bank_units', $blood_bank_params);
        if ($blood_bank_result['success'] && !empty($blood_bank_result['data'])) {
            $blood_bank_unit = $blood_bank_result['data'][0];
            $unit_status = strtolower(trim($blood_bank_unit['status'] ?? ''));
            $handed_over_at = $blood_bank_unit['handed_over_at'] ?? null;
            $disposed_at = $blood_bank_unit['disposed_at'] ?? null;
            $hospital_from = $blood_bank_unit['hospital_from'] ?? null;
            
            // PRIORITY 1: Check handed_over_at or disposed_at FIRST (highest priority - indicates Used status)
            // This should update regardless of current_status (including Buffer, Processed, Stored, Allocated, etc.)
            if (!empty($disposed_at)) {
                // Unit was disposed - status should be "Used" (will show as Expired in history)
                if ($current_status !== 'Used') {
                    $update_data = [
                        'current_status' => 'Used',
                        'blood_collection_completed' => true,
                        'units_collected' => $blood_bank_unit['units'] ?? 1,
                        'notes' => 'Blood unit disposed at ' . $disposed_at . ' - donation cycle complete (Expired)'
                    ];
                    $status_changed = true;
                    error_log("Auto Status Update - Unit disposed at {$disposed_at}, updating to Used status (current_status was: {$current_status})");
                }
            } elseif (!empty($handed_over_at)) {
                // Unit was handed over - status should be "Used"
                if ($current_status !== 'Used') {
                    $hospital_note = !empty($hospital_from) ? ' - Sent to ' . $hospital_from : '';
                    $update_data = [
                        'current_status' => 'Used',
                        'blood_collection_completed' => true,
                        'units_collected' => $blood_bank_unit['units'] ?? 1,
                        'notes' => 'Blood unit handed over at ' . $handed_over_at . $hospital_note . ' - donation cycle complete'
                    ];
                    $status_changed = true;
                    error_log("Auto Status Update - Unit handed over at {$handed_over_at} to " . ($hospital_from ?? 'N/A') . ", updating to Used status (current_status was: {$current_status})");
                }
            }
            // PRIORITY 2: Check status field if handed_over_at/disposed_at not set
            elseif ($current_status === 'Testing' || $current_status === 'Processed' || $current_status === 'Ready for Use' || $current_status === 'Buffer' || $current_status === 'Stored' || $current_status === 'Allocated') {
                if ($unit_status === 'used' || $unit_status === 'transfused' || $unit_status === 'buffer') {
                    // Blood unit is used/transfused/buffer - update to Used
                    // Buffer is used by admin system as a way to update the blood bank, so it should be treated as Used
                    if ($current_status !== 'Used') {
                        $update_data = [
                            'current_status' => 'Used',
                            'blood_collection_completed' => true,
                            'units_collected' => $blood_bank_unit['units'] ?? 1,
                            'notes' => 'Blood unit status is ' . ucfirst($unit_status) . ' - donation cycle complete'
                        ];
                        $status_changed = true;
                        error_log("Auto Status Update - Unit status is {$unit_status}, updating to Used status");
                    }
                } elseif ($unit_status === 'valid') {
                    // Blood unit is valid
                    if ($current_status === 'Testing') {
                        // Update from Testing to Processed
                        $update_data = [
                            'current_status' => 'Processed',
                            'blood_collection_completed' => true,
                            'units_collected' => $blood_bank_unit['units'] ?? 1,
                            'notes' => 'Blood unit status is Valid - updating to Processed status'
                        ];
                        $status_changed = true;
                    }
                    // If already Processed and unit_status is valid, don't update (prevents infinite loop)
                } elseif ($unit_status === 'disposed' || $unit_status === 'expired') {
                    // Blood unit is disposed or expired, update to Used (will show as Expired in history)
                    if ($current_status !== 'Used') {
                        $update_data = [
                            'current_status' => 'Used',
                            'blood_collection_completed' => true,
                            'units_collected' => $blood_bank_unit['units'] ?? 1,
                            'notes' => 'Blood unit status is ' . ucfirst($unit_status) . ' - donation cycle complete (Expired)'
                        ];
                        $status_changed = true;
                    }
                } elseif ($unit_status === 'stored') {
                    // Blood unit is stored - only update if not already Stored
                    if ($current_status !== 'Stored') {
                        $update_data = [
                            'current_status' => 'Stored',
                            'blood_collection_completed' => true,
                            'units_collected' => $blood_bank_unit['units'] ?? 1,
                            'notes' => 'Blood unit status is Stored'
                        ];
                        $status_changed = true;
                    }
                } elseif ($unit_status === 'allocated') {
                    // Blood unit is allocated - only update if not already Allocated
                    if ($current_status !== 'Allocated') {
                        $update_data = [
                            'current_status' => 'Allocated',
                            'blood_collection_completed' => true,
                            'units_collected' => $blood_bank_unit['units'] ?? 1,
                            'notes' => 'Blood unit status is Allocated'
                        ];
                        $status_changed = true;
                    }
                }
            }
        } else {
            // Fallback to blood_collection table if blood_bank_units not found
            $exam_params = [
                'donor_id' => 'eq.' . $donor_id,
                'order' => 'created_at.desc',
                'limit' => 1
            ];
            
            $exam_result = get_records('physical_examination', $exam_params);
            if ($exam_result['success'] && !empty($exam_result['data'])) {
                $exam = $exam_result['data'][0];
                $physical_exam_id = $exam['physical_exam_id'];
                
                $collection_params = [
                    'physical_exam_id' => 'eq.' . $physical_exam_id,
                    'order' => 'created_at.desc',
                    'limit' => 1
                ];
                
                $collection_result = get_records('blood_collection', $collection_params);
                if ($collection_result['success'] && !empty($collection_result['data'])) {
                    $collection = $collection_result['data'][0];
                    
                    if ($current_status === 'Testing') {
                        $amount_taken = floatval($collection['amount_taken'] ?? 0);
                        
                        if ($amount_taken >= 1) {
                            // Blood collected
                            $update_data = [
                                'current_status' => 'Processed',
                                'blood_collection_completed' => true,
                                'units_collected' => $amount_taken,
                                'notes' => 'Blood collection completed automatically - ' . $amount_taken . ' units collected'
                            ];
                            $status_changed = true;
                        } elseif ($amount_taken == 0) {
                            // No blood collected
                            $update_data = [
                                'current_status' => 'Ready for Use',
                                'blood_collection_completed' => true,
                                'units_collected' => 0,
                                'notes' => 'Blood collection cancelled automatically - no units collected'
                            ];
                            $status_changed = true;
                        }
                    }
                }
            }
        }
    }
    
    // Apply the status update if needed
    if ($status_changed && !empty($update_data)) {
        $update_result = update_record('donations', $donation_id, $update_data, 'donation_id');
        if ($update_result['success']) {
            return [
                'success' => true,
                'status' => 'updated',
                'message' => 'Donation status updated automatically to ' . $update_data['current_status'],
                'new_status' => $update_data['current_status'],
                'blood_type' => $update_data['blood_type'] ?? null,
                'units_collected' => $update_data['units_collected'] ?? null
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to update donation status automatically'
            ];
        }
    }
    
    return [
        'success' => true,
        'status' => 'no_update_needed',
        'message' => 'No status update needed at this time'
    ];
}

/**
 * Check and update ALL active donations automatically
 * This function can be called periodically to keep all donations up to date
 * 
 * @return array Summary of updates performed
 */
function update_all_active_donations_automatically() {
    $summary = [
        'total_checked' => 0,
        'total_updated' => 0,
        'total_errors' => 0,
        'details' => []
    ];
    
    // Get all active donations
    $donations_params = [
        'current_status' => 'not.eq.Ready for Use',
        'order' => 'created_at.desc',
        'limit' => 100
    ];
    
    $donations_result = get_records('donations', $donations_params);
    if (!$donations_result['success'] || empty($donations_result['data'])) {
        return $summary;
    }
    
    foreach ($donations_result['data'] as $donation) {
        $summary['total_checked']++;
        $donor_id = $donation['donor_id'];
        
        $update_result = check_and_update_donation_status_automatically($donor_id);
        
        if ($update_result['success'] && $update_result['status'] === 'updated') {
            $summary['total_updated']++;
            $summary['details'][] = [
                'donor_id' => $donor_id,
                'donation_id' => $donation['donation_id'],
                'old_status' => $donation['current_status'],
                'new_status' => $update_result['new_status'],
                'message' => $update_result['message']
            ];
        } elseif (!$update_result['success']) {
            $summary['total_errors']++;
            $summary['details'][] = [
                'donor_id' => $donor_id,
                'donation_id' => $donation['donation_id'],
                'error' => $update_result['error']
            ];
        }
    }
    
    return $summary;
} 

/**
 * Get latest completed donation and eligibility info with 7-day grace window
 *
 * - Eligibility: 3 months after the latest 'Processed' donation
 * - Grace period: Keep tracker visible for 7 days after reaching 'Processed'
 *
 * @param int $donor_id
 * @return array {
 *   success: bool,
 *   latest_completed_donation: array|null,
 *   next_donation_date: string|null (Y-m-d H:i:s),
 *   can_donate_now: bool,
 *   remaining_days: int, // days until eligible (>=0)
 *   remaining_months: int, // rough months until eligible (>=0)
 *   processed_at: string|null,
 *   grace_until: string|null // processed_at + 7 days
 * }
 */
function compute_donation_eligibility($donor_id) {
    $response = [
        'success' => true,
        'latest_completed_donation' => null,
        'next_donation_date' => null,
        'can_donate_now' => false,
        'remaining_days' => 0,
        'remaining_months' => 0,
        'processed_at' => null,
        'grace_until' => null
    ];

    $donor_id = intval($donor_id);
    if ($donor_id <= 0) {
        return ['success' => false, 'error' => 'Invalid donor ID'] + $response;
    }

    // Fetch latest "Processed" or "Ready for Use" donation (both count as completed)
    $donation_params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'created_at.desc'
    ];

    $donation_result = get_records('donations', $donation_params);
    if ($donation_result['success'] && !empty($donation_result['data'])) {
        foreach ($donation_result['data'] as $donation) {
            $status = $donation['current_status'] ?? '';
            // Check for both "Processed" and "Ready for Use" statuses
            if ($status === 'Processed' || $status === 'Ready for Use') {
                $response['latest_completed_donation'] = $donation;
                break;
            }
        }
    }

    if (!$response['latest_completed_donation']) {
        return $response; // No completed donations yet
    }

    $completed = $response['latest_completed_donation'];

    // Determine when it became Processed or Ready for Use from history if available
    $processed_at = $completed['created_at'] ?? null;
    $completed_status = $completed['current_status'] ?? '';
    
    // Check history for when it reached "Processed" or "Ready for Use" status
    $history_params = [
        'donation_id' => 'eq.' . $completed['donation_id'],
        'order' => 'changed_at.desc'
    ];
    $history_result = get_records('donation_status_history', $history_params);
    if ($history_result['success'] && !empty($history_result['data'])) {
        // Find the most recent "Processed" or "Ready for Use" status change
        foreach ($history_result['data'] as $history_item) {
            $status = $history_item['status'] ?? '';
            if ($status === 'Processed' || $status === 'Ready for Use') {
                $processed_at = $history_item['changed_at'] ?? $processed_at;
                break;
            }
        }
    }
    $response['processed_at'] = $processed_at;

    // Next eligible is 3 months from processed/created date
    if ($processed_at) {
        $next_eligible = date('Y-m-d H:i:s', strtotime($processed_at . ' +3 months'));
        $response['next_donation_date'] = $next_eligible;

        $now = date('Y-m-d H:i:s');
        if (strtotime($now) >= strtotime($next_eligible)) {
            $response['can_donate_now'] = true;
        } else {
            $diff_seconds = strtotime($next_eligible) - strtotime($now);
            $total_days = (int) floor($diff_seconds / (60 * 60 * 24));
            $response['remaining_days'] = max(0, $total_days);
            $response['remaining_months'] = max(0, (int) floor($total_days / 30));
        }

        // Grace period to keep tracker visible after completion
        $grace_until = date('Y-m-d H:i:s', strtotime($processed_at . ' +7 days'));
        $response['grace_until'] = $grace_until;
    }

    return $response;
}

/**
 * Email Verification Functions
 * These functions handle email verification for user registration
 */

/**
 * Create email verification record in database
 * 
 * @param string $email User's email address
 * @param string $user_id User's ID from Supabase Auth
 * @param string $verification_code 6-digit verification code
 * @return array Result array with success status and data
 */
function create_email_verification($email, $user_id, $verification_code) {
    try {
        // Calculate expiry time (15 minutes from now)
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . EMAIL_VERIFICATION_EXPIRY_MINUTES . ' minutes'));
        
        $verification_data = [
            'email' => $email,
            'verification_code' => $verification_code,
            'user_id' => $user_id,
            'expires_at' => $expires_at,
            'verified' => false
        ];
        
        $result = create_record('email_verifications', $verification_data);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Verification record created successfully',
                'data' => $result['data']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create verification record: ' . json_encode($result['data']),
                'data' => null
            ];
        }
        
    } catch (Exception $e) {
        error_log("Create email verification error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error creating verification record: ' . $e->getMessage(),
            'data' => null
        ];
    }
}

/**
 * Verify email verification code
 * 
 * @param string $email User's email address
 * @param string $verification_code 6-digit verification code
 * @return array Result array with success status and message
 */
function verify_email_code($email, $verification_code) {
    try {
        // Get the verification record
        $params = [
            'email' => 'eq.' . $email,
            'verification_code' => 'eq.' . $verification_code,
            'verified' => 'eq.false',
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $result = get_records('email_verifications', $params);
        
        if (!$result['success'] || empty($result['data'])) {
            return [
                'success' => false,
                'message' => 'Invalid verification code or email not found'
            ];
        }
        
        $verification_record = $result['data'][0];
        
        // Check if code has expired
        $now = date('Y-m-d H:i:s');
        if (strtotime($now) > strtotime($verification_record['expires_at'])) {
            return [
                'success' => false,
                'message' => 'Verification code has expired. Please request a new one.'
            ];
        }
        
        // Mark as verified
        $update_data = [
            'verified' => true,
            'verified_at' => $now
        ];
        
        $update_result = update_record('email_verifications', $verification_record['id'], $update_data);
        
        if (!$update_result['success']) {
            return [
                'success' => false,
                'message' => 'Failed to update verification status'
            ];
        }
        
        // Update donor_form table to mark email as verified
        $donor_update_data = [
            'email_verified' => true,
            'email_verified_at' => $now
        ];
        
        // Find the donor record by user_id
        $donor_params = ['user_id' => 'eq.' . $verification_record['user_id']];
        $donor_result = get_records('donor_form', $donor_params);
        
        if ($donor_result['success'] && !empty($donor_result['data'])) {
            $donor_record = $donor_result['data'][0];
            $donor_update_result = update_record('donor_form', $donor_record['id'], $donor_update_data);
            
            if (!$donor_update_result['success']) {
                error_log("Failed to update donor_form email verification status");
            }
        }
        
        return [
            'success' => true,
            'message' => 'Email verified successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Verify email code error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error verifying email: ' . $e->getMessage()
        ];
    }
}

/**
 * Check if user's email is verified
 * 
 * @param string $user_id User's ID from Supabase Auth
 * @return bool Whether the email is verified
 */
function is_email_verified($user_id) {
    try {
        $params = [
            'user_id' => 'eq.' . $user_id,
            'verified' => 'eq.true',
            'order' => 'verified_at.desc',
            'limit' => 1
        ];
        
        $result = get_records('email_verifications', $params);
        
        return $result['success'] && !empty($result['data']);
        
    } catch (Exception $e) {
        error_log("Check email verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Resend verification email
 * 
 * @param string $email User's email address
 * @param string $user_id User's ID from Supabase Auth
 * @param string $user_name User's name for personalization
 * @return array Result array with success status and message
 */
function resend_verification_email($email, $user_id, $user_name = '') {
    try {
        // Generate new verification code
        $verification_code = generate_verification_code();
        
        // Create new verification record
        $create_result = create_email_verification($email, $user_id, $verification_code);
        
        if (!$create_result['success']) {
            return [
                'success' => false,
                'message' => 'Failed to create verification record: ' . $create_result['message']
            ];
        }
        
        // Send email
        $email_result = send_verification_email($email, $verification_code, $user_name);
        
        if (!$email_result['success']) {
            return [
                'success' => false,
                'message' => 'Failed to send verification email: ' . $email_result['message']
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Verification email sent successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Resend verification email error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error resending verification email: ' . $e->getMessage()
        ];
    }
}
