<?php
/**
 * CANCEL REGISTRATION UTILITY
 * 
 * DATABASE CONNECTION UPDATE:
 * This file has been modified to use the project's database.php for connection settings
 * instead of external database connections. This ensures consistent database access
 * across the entire application.
 * 
 * PATH UPDATE:
 * Fixed path resolution issues by using proper relative path to database.php
 */

// Include the project's database configuration
require_once '../../../config/database.php';

// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Get referrer for redirection
$redirect_url = isset($_SESSION['donor_form_referrer']) 
    ? $_SESSION['donor_form_referrer'] 
    : '../../dashboard.php';

try {
    // Log the cancellation
    error_log("Cancelling donor registration process. donor_form_data=" . 
              (isset($_SESSION['donor_form_data']) ? 'present' : 'absent') . 
              ", donor_id=" . ($_SESSION['donor_id'] ?? 'not set'));
    
    // Clear all registration-related session data
    if (isset($_SESSION['donor_form_data'])) {
        unset($_SESSION['donor_form_data']);
    }
    
    if (isset($_SESSION['donor_form_timestamp'])) {
        unset($_SESSION['donor_form_timestamp']);
    }
    
    if (isset($_SESSION['donor_id'])) {
        unset($_SESSION['donor_id']);
    }
    
    if (isset($_SESSION['medical_history_id'])) {
        unset($_SESSION['medical_history_id']);
    }
    
    if (isset($_SESSION['screening_id'])) {
        unset($_SESSION['screening_id']);
    }
    
    // Clear any other donor-related session data
    if (isset($_SESSION['donor_registered'])) {
        unset($_SESSION['donor_registered']);
    }
    
    if (isset($_SESSION['donor_registered_id'])) {
        unset($_SESSION['donor_registered_id']);
    }
    
    if (isset($_SESSION['donor_registered_name'])) {
        unset($_SESSION['donor_registered_name']);
    }
    
    if (isset($_SESSION['declaration_completed'])) {
        unset($_SESSION['declaration_completed']);
    }
    
    // Keep donor_form_referrer for navigation purposes
    
    // Log the cleaned state
    error_log("Registration session data cleared. Session now contains keys: " . 
             implode(', ', array_keys($_SESSION)));
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Registration cancelled successfully',
        'redirect_url' => $redirect_url
    ]);
} catch (Exception $e) {
    error_log("Error cancelling registration: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => 'Failed to cancel registration: ' . $e->getMessage(),
        'redirect_url' => $redirect_url
    ]);
}
?> 