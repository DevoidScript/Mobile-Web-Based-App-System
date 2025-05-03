<?php
// Start the session to maintain state
session_start();

// Set header to return JSON response
header('Content-Type: application/json');

// Process the POST request
$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $postData = json_decode(file_get_contents('php://input'), true);
    
    // Check if we're canceling from a specific form
    $action = isset($postData['action']) ? $postData['action'] : 'general_cancel';
    
    // Session data to clear based on action
    switch ($action) {
        case 'cancel_from_donor_form':
            // If canceling from donor form, just clear the form data
            unset($_SESSION['donor_form_data']);
            unset($_SESSION['donor_form_timestamp']);
            break;
            
        case 'cancel_from_medical_history':
            // If canceling from medical history, may need to keep the donor_id but clear medical form data
            unset($_SESSION['medical_history_data']);
            break;
            
        case 'cancel_from_declaration':
            // If canceling from declaration, we keep donor_id and medical_history_id for now
            // Could be used to resume later
            break;
            
        case 'complete_cancel':
            // Complete cancellation - clear all registration related session data
            unset($_SESSION['donor_id']);
            unset($_SESSION['donor_form_data']);
            unset($_SESSION['donor_form_timestamp']);
            unset($_SESSION['medical_history_id']);
            unset($_SESSION['medical_history_data']);
            unset($_SESSION['declaration_completed']);
            break;
            
        default:
            // General cancellation - clear most session data but don't log out user
            unset($_SESSION['donor_id']);
            unset($_SESSION['donor_form_data']);
            unset($_SESSION['donor_form_timestamp']);
            unset($_SESSION['medical_history_id']);
            unset($_SESSION['medical_history_data']);
            break;
    }
    
    $response = [
        'success' => true,
        'message' => 'Session data cleared successfully',
        'action' => $action
    ];
}

// Send JSON response back
echo json_encode($response);
?> 