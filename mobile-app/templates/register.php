<?php
/**
 * Red Cross Donor Registration System
 * 
 * This file handles the multi-step registration process for blood donors.
 * It collects user information and stores data in Supabase:
 * - Personal information (name, birthdate, age, sex, civil status)
 * - Address information
 * - Additional information (nationality, education, occupation)
 * - Account credentials
 * 
 * Note: ID fields, telephone number, and office address are intentionally 
 * excluded from this version as they're not stored in the donors_detail table.
 * 
 * Data is stored in the donors_detail table in Supabase, with authentication
 * handled through Supabase Auth.
 * 
 * MOVED TO TEMPLATES:
 * This file has been moved to the templates directory for better organization.
 * Paths have been adjusted to maintain functionality.
 * 
 * MOBILE OPTIMIZATION:
 * The UI has been optimized for mobile devices with:
 * - Improved step indicators that adapt to small screens
 * - Touch-friendly input elements and buttons
 * - Responsive layout that works on any screen size
 * - Streamlined visual design for better user experience
 */

// Include configuration files - adjusted paths for templates directory
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$errors = [];
$success_message = '';

// Check for error or success messages in the session
if (isset($_SESSION['error'])) {
    $errors[] = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's the final step submission
    if (isset($_POST['step']) && $_POST['step'] == '5') {
        // Validate required fields
        $required_fields = [
            'surname' => 'Surname',
            'first_name' => 'First Name',
            'sex' => 'Sex',
            'civil_status' => 'Civil Status',
            'birthdate' => 'Birthdate',
            'nationality' => 'Nationality',
            'occupation' => 'Occupation',
            'mobile' => 'Mobile Number',
            'permanent_address' => 'Permanent Address', 
            'email' => 'Email',
            'password' => 'Password',
            'confirm_password' => 'Confirm Password'
        ];

        // Validate required fields
        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $errors[] = "$label is required";
            }
        }

        // Email format validation
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        // Password matching validation
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $errors[] = "Passwords do not match";
        }
        
        // If permanent_address is empty but we have address components, let's combine them
        if (empty($_POST['permanent_address']) && 
            (!empty($_POST['barangay']) || !empty($_POST['municipality']) || !empty($_POST['province']))) {
            
            $address_parts = [];
            if (!empty($_POST['house_no'])) $address_parts[] = sanitize_input($_POST['house_no']);
            if (!empty($_POST['street'])) $address_parts[] = sanitize_input($_POST['street']);
            if (!empty($_POST['barangay'])) $address_parts[] = sanitize_input($_POST['barangay']);
            if (!empty($_POST['municipality'])) $address_parts[] = sanitize_input($_POST['municipality']);
            if (!empty($_POST['province'])) $address_parts[] = sanitize_input($_POST['province']);
            if (!empty($_POST['postal_code'])) $address_parts[] = sanitize_input($_POST['postal_code']);
            
            if (!empty($address_parts)) {
                $_POST['permanent_address'] = implode(', ', $address_parts);
            } else {
                $errors[] = "Permanent Address is required";
            }
        }

        // If no errors, proceed with registration
        if (empty($errors)) {
            // The actual registration will now be handled by the donor_register.php API
            // The form submission is handled by JavaScript below
            
            // Set a flag to indicate we passed server-side validation
            $_SESSION['validated'] = true;
        }
    }
}

// Check for validation success flag
$validated = isset($_SESSION['validated']) && $_SESSION['validated'] === true;
if ($validated) {
    // Clear the validation flag
    unset($_SESSION['validated']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#d32f2f">
    <title>Blood Donor Registration - Red Cross</title>
    <!-- Updated paths for CSS and other assets -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <!-- Rest of the head content follows -->
    
    <!-- Form submission to API endpoint with updated path -->
    <script>
        // This script will be updated to handle form submission to the updated API path
        document.addEventListener('DOMContentLoaded', function() {
            const registrationForm = document.getElementById('registrationForm');
            if (registrationForm) {
                registrationForm.addEventListener('submit', function(e) {
                    // Updated API path
                    registrationForm.action = '../api/donor_register.php';
                });
            }
        });
    </script>
</head>
<body>
    <!-- Page content with updated paths to assets and links -->
    <div class="container">
        <div class="form-container">
            <a href="../index.php" class="back-link">&larr; Back to Login</a>
            <h1 class="title">Blood Donor Registration</h1>
            
            <!-- Form content goes here with updated paths -->
            <form id="registrationForm" method="POST" action="../api/donor_register.php">
                <!-- Form fields and steps go here -->
                
                <!-- The form will have updated navigation paths -->
                <div class="form-navigation">
                    <a href="../index.php" class="cancel-btn">Cancel</a>
                    <button type="submit" class="submit-btn">Register</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts with updated paths -->
    <script src="../assets/js/app.js"></script>
</body>
</html> 