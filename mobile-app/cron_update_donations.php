<?php
/**
 * Cron Job Script for Automatic Donation Status Updates
 * 
 * This script can be set up as a cron job to automatically update
 * all donation statuses every few minutes, ensuring real-time updates
 * when staff modify forms in the separate blood management system.
 * 
 * Usage:
 * 1. Set up a cron job to run this script every 2-5 minutes
 * 2. Example cron: */2 * * * * /usr/bin/php /path/to/cron_update_donations.php
 * 3. Or call via HTTP: curl "https://yoursite.com/mobile-app/cron_update_donations.php"
 */

// Set execution time limit for long-running updates
set_time_limit(300); // 5 minutes

// Include required files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Log file for tracking updates
$log_file = 'cron_donation_updates.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    echo $log_entry;
}

// Start the update process
log_message("Starting automatic donation status update process...");

try {
    // Update all active donations automatically
    $result = update_all_active_donations_automatically();
    
    if ($result['success']) {
        log_message("Update process completed successfully!");
        log_message("Total donations checked: " . $result['total_checked']);
        log_message("Total donations updated: " . $result['total_updated']);
        log_message("Total errors: " . $result['total_errors']);
        
        // Log details of updates
        if (!empty($result['details'])) {
            log_message("Update details:");
            foreach ($result['details'] as $detail) {
                if (isset($detail['new_status'])) {
                    log_message("  - Donor ID {$detail['donor_id']}: {$detail['old_status']} → {$detail['new_status']}");
                } else {
                    log_message("  - Donor ID {$detail['donor_id']}: Error - {$detail['error']}");
                }
            }
        }
        
        // Return success response
        if (php_sapi_name() === 'cli') {
            // CLI mode
            exit(0);
        } else {
            // Web mode
            header('Content-Type: application/json');
            echo json_encode($result);
        }
        
    } else {
        log_message("Update process failed: " . ($result['error'] ?? 'Unknown error'));
        
        if (php_sapi_name() === 'cli') {
            exit(1);
        } else {
            header('Content-Type: application/json');
            echo json_encode($result);
        }
    }
    
} catch (Exception $e) {
    log_message("Exception occurred: " . $e->getMessage());
    
    if (php_sapi_name() === 'cli') {
        exit(1);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
}

log_message("Automatic donation status update process finished.");
?>
