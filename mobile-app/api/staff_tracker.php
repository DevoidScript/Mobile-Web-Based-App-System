<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];
$action = $_GET['action'] ?? '';

// For now, we'll allow any authenticated user to update stages
// In production, you should add role-based access control
switch ($action) {
    case 'update_stage':
        update_donation_stage($_POST);
        break;
    case 'get_all_donations':
        get_all_donations();
        break;
    case 'get_donation_details':
        get_donation_details($_GET['donation_id']);
        break;
    case 'complete_form':
        complete_form($_POST);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function update_donation_stage($data) {
    $donation_id = $data['donation_id'] ?? null;
    $new_status = $data['new_status'] ?? null;
    $notes = $data['notes'] ?? '';
    
    if (!$donation_id || !$new_status) {
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }
    
    // Validate status
    $valid_statuses = [
        'Registered', 'Sample Collected', 'Testing',
        'Testing Complete', 'Processed', 'Ready for Use'
    ];
    
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['error' => 'Invalid status']);
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

function get_all_donations() {
    $params = [
        'order' => 'created_at.desc',
        'limit' => 100
    ];
    
    $result = get_records('donations', $params);
    
    if ($result['success']) {
        echo json_encode(['data' => $result['data']]);
    } else {
        echo json_encode(['error' => 'Failed to fetch donations']);
    }
}

function get_donation_details($donation_id) {
    if (!$donation_id) {
        echo json_encode(['error' => 'Missing donation ID']);
        return;
    }
    
    $result = get_record('donations', $donation_id);
    
    if ($result['success']) {
        $donation = $result['data'][0];
        
        // Get status history
        $history_params = [
            'donation_id' => 'eq.' . $donation_id,
            'order' => 'changed_at.desc'
        ];
        
        $history_result = get_records('donation_status_history', $history_params);
        $status_history = $history_result['success'] ? $history_result['data'] : [];
        
        // Get donor information
        $donor_params = ['id' => 'eq.' . $donation['donor_id']];
        $donor_result = get_records('donor_form', $donor_params);
        $donor_info = $donor_result['success'] && !empty($donor_result['data']) ? $donor_result['data'][0] : null;
        
        $donation['status_history'] = $status_history;
        $donation['donor_info'] = $donor_info;
        
        echo json_encode(['data' => $donation]);
    } else {
        echo json_encode(['error' => 'Failed to fetch donation details']);
    }
}

function complete_form($data) {
    $donation_id = $data['donation_id'] ?? null;
    $form_type = $data['form_type'] ?? null;
    
    if (!$donation_id || !$form_type) {
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }
    
    // Map form types to database columns
    $form_columns = [
        'medical_history' => 'medical_history_completed',
        'physical_examination' => 'physical_examination_completed',
        'screening' => 'screening_completed',
        'blood_collection' => 'blood_collection_completed'
    ];
    
    if (!isset($form_columns[$form_type])) {
        echo json_encode(['error' => 'Invalid form type']);
        return;
    }
    
    $column = $form_columns[$form_type];
    
    // Update the form completion status
    $update_data = [$column => true];
    $result = update_record('donations', $donation_id, $update_data);
    
    if ($result['success']) {
        // Auto-progress to next stage if appropriate
        auto_progress_stage($donation_id, $form_type);
        
        echo json_encode([
            'success' => true,
            'message' => 'Form marked as completed'
        ]);
    } else {
        echo json_encode(['error' => 'Failed to update form status']);
    }
}

function auto_progress_stage($donation_id, $form_type) {
    // Get current donation
    $result = get_record('donations', $donation_id);
    if (!$result['success']) return;
    
    $donation = $result['data'][0];
    $current_status = $donation['current_status'];
    
    // Define progression rules
    $progression_rules = [
        'medical_history' => 'Sample Collected',
        'physical_examination' => 'Testing',
        'screening' => 'Testing',
        'blood_collection' => 'Testing Complete'
    ];
    
    if (isset($progression_rules[$form_type])) {
        $new_status = $progression_rules[$form_type];
        
        // Update status
        $update_data = ['current_status' => $new_status];
        update_record('donations', $donation_id, $update_data);
    }
}
