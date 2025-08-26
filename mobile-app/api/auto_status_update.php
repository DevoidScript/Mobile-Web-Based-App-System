<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in (optional for system calls)
$user_logged_in = isset($_SESSION['user']);

// Get the action from request
$action = $_GET['action'] ?? $_POST['action'] ?? 'update_all';

// Handle different actions
switch ($action) {
    case 'update_all':
        // Update all active donations automatically
        $result = update_all_active_donations_automatically();
        echo json_encode($result);
        break;
        
    case 'update_specific_donor':
        // Update status for a specific donor
        $donor_id = $_POST['donor_id'] ?? $_GET['donor_id'] ?? null;
        
        if (!$donor_id) {
            echo json_encode(['error' => 'Donor ID is required']);
            break;
        }
        
        $result = check_and_update_donation_status_automatically($donor_id);
        echo json_encode($result);
        break;
        
    case 'update_by_email':
        // Update status for a donor by email
        $email = $_POST['email'] ?? $_GET['email'] ?? null;
        
        if (!$email) {
            echo json_encode(['error' => 'Email is required']);
            break;
        }
        
        $donor_id = get_donor_id_from_email($email);
        if (!$donor_id) {
            echo json_encode(['error' => 'Donor not found for email: ' . $email]);
            break;
        }
        
        $result = check_and_update_donation_status_automatically($donor_id);
        echo json_encode($result);
        break;
        
    case 'check_donor_status':
        // Check current status for a donor
        $donor_id = $_POST['donor_id'] ?? $_GET['donor_id'] ?? null;
        
        if (!$donor_id) {
            echo json_encode(['error' => 'Donor ID is required']);
            break;
        }
        
        // Get current donation status
        $donation_params = [
            'donor_id' => 'eq.' . $donor_id,
            'current_status' => 'not.eq.Ready for Use',
            'order' => 'created_at.desc',
            'limit' => 1
        ];
        
        $donation_result = get_records('donations', $donation_params);
        if ($donation_result['success'] && !empty($donation_result['data'])) {
            $donation = $donation_result['data'][0];
            echo json_encode([
                'success' => true,
                'donor_id' => $donor_id,
                'donation_id' => $donation['donation_id'],
                'current_status' => $donation['current_status'],
                'blood_type' => $donation['blood_type'] ?? null,
                'screening_completed' => $donation['screening_completed'] ?? false,
                'physical_examination_completed' => $donation['physical_examination_completed'] ?? false,
                'blood_collection_completed' => $donation['blood_collection_completed'] ?? false,
                'last_updated' => $donation['last_updated'] ?? null,
                'notes' => $donation['notes'] ?? null
            ]);
        } else {
            echo json_encode(['error' => 'No active donation found for donor']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action. Valid actions: update_all, update_specific_donor, update_by_email, check_donor_status']);
}
?>
