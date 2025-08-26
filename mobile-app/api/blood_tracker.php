<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_tracker':
        get_donation_tracker($user);
        break;
    case 'start_donation':
        start_donation_process($user);
        break;
    case 'update_stage':
        update_donation_stage($_POST);
        break;
    case 'get_status_history':
        get_status_history($_GET['donation_id']);
        break;
    case 'auto_update_status':
        auto_update_donation_status_for_user($user);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function start_donation_process($user) {
    $donor_id = $user['donor_id'] ?? $user['id'];
    
    // Check if donor already has an active donation
    $existing_params = [
        'donor_id' => 'eq.' . $donor_id,
        'current_status' => 'not.eq.Ready for Use',
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $existing_result = get_records('donations', $existing_params);
    
    if ($existing_result['success'] && !empty($existing_result['data'])) {
        echo json_encode(['error' => 'Donor already has an active donation']);
        return;
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
    
    $result = create_record('donations', $donation_data);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'donation_id' => $result['data'][0]['donation_id'],
            'message' => 'Donation process started successfully'
        ]);
    } else {
        echo json_encode(['error' => 'Failed to start donation process']);
    }
}

function get_donation_tracker($user) {
    $donor_id = $user['donor_id'] ?? $user['id'];
    
    // Get current donation from your donations table
    $params = [
        'donor_id' => 'eq.' . $donor_id,
        'order' => 'created_at.desc',
        'limit' => 1
    ];
    
    $result = get_records('donations', $params);
    
    if (!$result['success'] || empty($result['data'])) {
        echo json_encode(['error' => 'No donation found']);
        return;
    }
    
    $donation = $result['data'][0];
    $tracker_data = build_tracker_data($donation);
    
    echo json_encode($tracker_data);
}

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
            'next_stage' => 'Sample Collected',
            'form_required' => 'medical_history'
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
            'next_stage' => 'Processed',
            'form_required' => null
        ],
        'Processed' => [
            'name' => 'Processing Complete',
            'description' => 'Blood processed and prepared',
            'icon' => '⚙️',
            'progress' => 90,
            'next_stage' => 'Ready for Use',
            'form_required' => null
        ],
        'Ready for Use' => [
            'name' => 'Ready for Use',
            'description' => 'Blood is ready for distribution',
            'icon' => '🚀',
            'progress' => 100,
            'next_stage' => null,
            'form_required' => null
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
            'next_stage' => $stage_info['next_stage'],
            'form_required' => $stage_info['form_required']
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

function update_donation_stage($data) {
    $donation_id = $data['donation_id'] ?? null;
    $new_status = $data['new_status'] ?? null;
    $notes = $data['notes'] ?? '';
    
    if (!$donation_id || !$new_status) {
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }
    
    // Update donation status
    $update_data = ['current_status' => $new_status];
    $result = update_record('donations', $donation_id, $update_data);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Donation status updated successfully'
        ]);
    } else {
        echo json_encode(['error' => 'Failed to update donation status']);
    }
}

function get_status_history($donation_id) {
    $params = [
        'donation_id' => 'eq.' . $donation_id,
        'order' => 'changed_at.desc'
    ];
    
    $result = get_records('donation_status_history', $params);
    
    if ($result['success']) {
        echo json_encode(['data' => $result['data']]);
    } else {
        echo json_encode(['error' => 'Failed to fetch status history']);
    }
}

function auto_update_donation_status_for_user($user) {
    $donor_id = null;
    
    // First try to get donor_id from user data
    if (isset($user['donor_id']) && !empty($user['donor_id'])) {
        $donor_id = intval($user['donor_id']);
    }
    
    // If no donor_id, try to get it from email (same as medical history modal)
    if (!$donor_id && isset($_SESSION['user']['email'])) {
        $email = trim(strtolower($_SESSION['user']['email']));
        $donor_id = get_donor_id_from_email($email);
        
        if ($donor_id) {
            error_log("Blood Tracker API - Found donor_id from email: $donor_id");
        } else {
            error_log("Blood Tracker API - No donor record found for email: $email");
        }
    }
    
    // Fallback to user id if still no donor_id
    if (!$donor_id && isset($user['id'])) {
        $donor_id = intval($user['id']);
        error_log("Blood Tracker API - Using fallback donor_id from user id: $donor_id");
    }
    
    if (!$donor_id) {
        echo json_encode(['error' => 'No donor ID found for current user']);
        return;
    }
    
    // Call the auto-update function
    $result = auto_update_donation_status($donor_id);
    echo json_encode($result);
}
