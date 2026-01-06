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
 * MOBILE OPTIMIZATION:
 * The UI has been optimized for mobile devices with:
 * - Improved step indicators that adapt to small screens
 * - Touch-friendly input elements and buttons
 * - Responsive layout that works on any screen size
 * - Streamlined visual design for better user experience
 */

// Include configuration files
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

        // Strict PH mobile validation: 10 digits starting with 9 (equivalent to 09 when used with +63)
        if (!empty($_POST['mobile'])) {
            $mobile_digits = preg_replace('/[^0-9]/', '', $_POST['mobile']);
            if (!preg_match('/^9\d{9}$/', $mobile_digits)) {
                $errors[] = "Invalid Philippine mobile number. Enter 10 digits starting with 9 (e.g., 9123456789).";
            } else {
                // Normalize to 11-digit local format starting with 0 for donor_table storage
                $_POST['mobile'] = '0' . $mobile_digits;
            }
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
    <style>
        /* Base styles optimized for mobile */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 16px;
            box-sizing: border-box;
        }
        
        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            position: relative;
        }
        
        .title {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin: 16px 0 24px;
            color: #d32f2f;
            letter-spacing: 0.5px;
        }
        
        .label {
            font-size: 15px;
            font-weight: 600;
            margin-top: 16px;
            margin-bottom: 6px;
            display: block;
        }
        
        .section-title {
            font-size: 17px;
            font-weight: bold;
            margin: 0 0 20px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            color: #d32f2f;
            text-transform: uppercase;
        }
        
        .input {
            width: 100%;
            padding: 13px 16px;
            border-radius: 8px;
            margin: 0 0 16px;
            border: 1px solid #ddd;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
        }
        
        .input:focus, .select:focus {
            outline: none;
            border-color: #d32f2f;
            box-shadow: 0 0 0 2px rgba(211, 47, 47, 0.2);
        }
        
        .input-error-text {
            color: #d32f2f;
            font-size: 13px;
            margin-top: 6px;
            padding-left: 2px;
            display: block;
            animation: fadeIn 0.2s ease;
        }
        
        .field-group.has-error .input,
        .field-group.has-error .select {
            border-color: #d32f2f;
            background-color: rgba(211, 47, 47, 0.05);
        }
        
        .field-group.has-error {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-8px); }
            40%, 80% { transform: translateX(8px); }
        }
        
        .select {
            width: 100%;
            padding: 13px 16px;
            border-radius: 8px;
            margin: 0 0 16px;
            border: 1px solid #ddd;
            background-color: white;
            box-sizing: border-box;
            font-size: 16px;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 8.825L1.175 4 2.25 2.925 6 6.675 9.75 2.925 10.825 4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }
        
        /* Password field with toggle */
        .password-wrapper { position: relative; }
        .input.input--with-toggle { padding-right: 52px; }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
            cursor: pointer;
            user-select: none;
        }
        
        .submit-button {
            background-color: #c01f1f; /* Match the next button color */
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            min-width: 100px;
            text-align: center;
            transition: background-color 0.2s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            -webkit-tap-highlight-color: transparent;
            margin-top: 0;
        }
        
        .submit-button:hover, .submit-button:focus {
            background-color: #a51a1a; /* Darker on hover */
        }
        
        .submit-button:active {
            transform: translateY(1px);
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ef9a9a;
            font-size: 14px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #a5d6a7;
            font-size: 14px;
        }
        
        .required::after {
            content: ' *';
            color: #d32f2f;
        }
        
        /**
         * Improved Progress Indicator Styling
         * - Fixed step 4 line alignment issue
         * - Precise positioning of progress line
         * - Enhanced visual appearance for mobile
         */
        
        /* Improved progress container */
        .progress-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
            padding: 0 16px;
        }
        
        /* Background line connecting steps */
        .progress-container::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 16px; /* From first step center */
            right: 16px; /* To last step center */
            height: 3px;
            background-color: #d9d9d9;
            z-index: 1;
            transform: translateY(-50%);
        }
        
        /* Colored progress line */
        .progress-line {
            position: absolute;
            top: 50%;
            left: 16px;
            height: 3px;
            background-color: #c01f1f;
            z-index: 2;
            transform: translateY(-50%);
            transform-origin: left;
            transition: width 0.5s ease;
        }
        
        /* Progress indicators - Updated for mobile */
        /* No longer needed - this section was replaced above */
        
        /* Step styling with better positioning */
        .step {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #d9d9d9; 
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 3; /* Higher than both line elements */
            font-size: 14px;
            color: #444;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .step.active {
            background-color: #c01f1f; /* Darker active color */
            color: white;
            box-shadow: 0 2px 8px rgba(192, 31, 31, 0.4);
            transform: scale(1.1);
        }
        
        .step.completed {
            background-color: #c01f1f; /* Darker completed color */
            color: white;
            opacity: 0.9; /* Less transparent for stronger color */
        }
        
        /* Step connecting lines */
        .step::after {
            display: none; /* Hide default connectors */
        }
        
        /* Form steps */
        .form-step {
            display: none;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-step.active {
            display: block;
        }
        
        /* Navigation buttons */
        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            gap: 10px;
        }
        
        .nav-button {
            padding: 14px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s;
            min-width: 90px;
            text-align: center;
            -webkit-tap-highlight-color: transparent;
            text-decoration: none;
        }
        
        /* For steps with three buttons (previous, cancel, next) */
        .form-navigation .prev-button {
            flex: 1;
        }
        
        .form-navigation .cancel-button {
            flex: 1;
        }
        
        .form-navigation .next-button, 
        .form-navigation .submit-button {
            flex: 1;
        }
        
        .placeholder-button {
            flex: 1;
            visibility: hidden;
        }
        
        .prev-button {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            color: #555;
        }
        
        .prev-button:active {
            background-color: #e0e0e0;
        }
        
        .next-button {
            background-color: #c01f1f; /* Darker color to match step indicators */
            color: white;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .next-button:hover, .next-button:focus {
            background-color: #a51a1a; /* Even darker on hover */
        }
        
        .next-button:active {
            transform: translateY(1px);
        }
        
        /* Loading overlay - optimized for mobile */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            -webkit-backdrop-filter: blur(3px);
            backdrop-filter: blur(3px);
        }
        
        .loading-content {
            background-color: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            text-align: center;
            width: 85%;
            max-width: 300px;
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #d32f2f;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            margin: 0 auto 16px;
            animation: spin 1.2s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Mobile-specific adjustments */
        @media (max-width: 480px) {
            .container {
            padding: 12px;
            }
            
            .form-container {
                padding: 16px;
                border-radius: 10px;
            }
            
            .title {
                font-size: 20px;
                margin: 10px 0 20px;
            }
            
            .step {
                width: 28px;
                height: 28px;
                font-size: 13px;
            }
            
            .nav-button {
                padding: 12px 15px;
                font-size: 14px;
                min-width: 90px;
            }
            
            .section-title {
            font-size: 16px;
            }
            
            /* Address fields mobile adjustments */
            .address-row {
                flex-direction: column;
                gap: 0;
            }
            
            .address-row .field-group {
                flex: 1 1 100%;
                width: 100%;
                margin-bottom: 16px;
            }
            
            .address-row .field-group:last-child {
                margin-bottom: 0;
            }
            
            .field-group .input {
                margin-bottom: 0;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .field-group label {
                font-size: 13px;
                margin-bottom: 6px;
            }
            
            /* Phone input mobile adjustments */
            .phone-input {
                max-width: 100% !important;
                width: 100% !important;
            }
            
            .phone-prefix {
                font-size: 14px;
                padding: 0 10px !important;
            }
            
            #mobile {
                font-size: 16px !important; /* Prevents zoom on iOS */
            }
            
            .address-title {
                font-size: 14px;
            }
            
            .address-note {
                font-size: 12px;
                margin-bottom: 12px;
            }
            
            .combined-address-preview {
                font-size: 13px;
                padding: 8px 12px;
                margin: 12px 0;
            }
            
            /* Ensure proper spacing between address sections */
            .address-fields {
                margin-bottom: 12px;
            }
            
            /* Postal code autofill indicator on mobile */
            .postal-code-autofilled::after {
                font-size: 10px;
                padding: 2px 6px;
                right: 8px;
            }
        }
        
        /**
         * Detailed Address Form Styling
         * - Provides structured fields for Philippine addresses
         * - Layout optimized for mobile viewing
         * - Groups related address fields for better organization
         */
        .address-title {
            font-weight: 600;
            color: #c01f1f;
            margin: 10px 0 5px;
            font-size: 15px;
        }
        
        .address-note {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .address-fields {
            margin-bottom: 15px;
        }
        
        .address-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .address-row .field-group {
            flex: 1;
            min-width: 0;
        }
        
        .field-group label {
            display: block;
            font-size: 14px;
            margin-bottom: 4px;
            color: #444;
            font-weight: 500;
        }
        
        .combined-address-preview {
            background-color: #f9f9f9;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin: 15px 0;
                font-size: 14px;
            line-height: 1.4;
        }
        
        /* Postal code autofill indicator */
        .postal-code-autofilled {
            position: relative;
        }
        
        .postal-code-autofilled::after {
            content: '✓ Auto-filled';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 11px;
            color: #4CAF50;
            font-weight: 600;
            background-color: #E8F5E9;
            padding: 2px 8px;
            border-radius: 4px;
            pointer-events: none;
        }
        
        /* Cancel Registration Button Styling */
        .cancel-registration {
            text-align: right;
            margin-bottom: 15px;
        }
        
        .cancel-button {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            color: #555;
        }
        
        .cancel-button:hover, .cancel-button:focus {
            background-color: #e0e0e0;
            color: #333;
        }
        
        /* Password hint styling */
        .password-hint {
            font-size: 13px;
            color: #666;
            margin-top: -12px;
            margin-bottom: 16px;
            font-style: italic;
        }
        
        /* Friendly Notification Modal Styling - Replaces error modal */
        .notification-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            padding: 20px;
            box-sizing: border-box;
            animation: fadeIn 0.3s ease;
        }
        
        .notification-modal {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
            position: relative;
            border-top: 4px solid #2196F3;
        }
        
        .notification-modal.info {
            border-top-color: #2196F3;
        }
        
        .notification-modal.warning {
            border-top-color: #FF9800;
        }
        
        .notification-modal.error {
            border-top-color: #F44336;
        }
        
        .notification-modal.success {
            border-top-color: #4CAF50;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification-modal-header {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            padding: 20px 24px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .notification-modal-header.warning {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        }
        
        .notification-modal-header.error {
            background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);
        }
        
        .notification-modal-header.success {
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
        }
        
        .notification-modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .notification-modal-header .notification-icon {
            font-size: 28px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
        }
        
        .notification-modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .notification-modal-close:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .notification-modal-body {
            padding: 24px;
        }
        
        .notification-modal-body .notification-type {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .notification-modal-body .notification-message {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        
        .notification-modal-body .notification-details {
            background-color: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
            color: #666;
            margin-bottom: 16px;
            font-family: monospace;
            word-break: break-word;
        }
        
        .notification-modal-body .notification-help {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
            padding: 14px;
            background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
            border-left: 4px solid #2196F3;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .notification-modal-body .notification-help.warning {
            background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);
            border-left-color: #FF9800;
        }
        
        .notification-modal-body .notification-help.error {
            background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);
            border-left-color: #F44336;
        }
        
        .notification-modal-footer {
            padding: 0 24px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .notification-modal-button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 100px;
        }
        
        .notification-modal-button-primary {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
        }
        
        .notification-modal-button-primary:hover {
            background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
        }
        
        .notification-modal-button-secondary {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .notification-modal-button-secondary:hover {
            background-color: #e0e0e0;
        }
        
        /* Mobile-specific adjustment for the cancel button */
        @media (max-width: 480px) {
            .cancel-button {
                padding: 6px 12px;
                font-size: 13px;
            }
            
            .notification-modal {
                max-width: 100%;
                margin: 10px;
            }
            
            .notification-modal-header {
                padding: 16px;
            }
            
            .notification-modal-header h3 {
                font-size: 18px;
            }
            
            .notification-modal-body {
                padding: 20px;
            }
            
            .notification-modal-footer {
                flex-direction: column;
                padding: 0 20px 20px;
            }
            
            .notification-modal-button {
                width: 100%;
            }
        }
        
        /* Medium mobile screens (481px to 600px) */
        @media (min-width: 481px) and (max-width: 600px) {
            .address-row {
                gap: 8px;
            }
            
            .address-row .field-group {
                flex: 1 1 calc(50% - 4px);
                min-width: 150px;
            }
            
            /* Stack three-column layout into two columns on medium screens */
            .address-row .field-group:nth-child(3) {
                flex: 1 1 100%;
            }
            
            .phone-input {
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">BLOOD DONOR REGISTRATION</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <h3>Registration Error:</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><small>If this error persists, please contact technical support with the error details above.</small></p>
                </div>
            <?php endif; ?>
            
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
        <div class="form-container">
            <!-- Progress indicators with colored line -->
            <div class="progress-container">
                <div class="progress-line" id="progress-line"></div>
                <div class="step active" data-step="1">1</div>
                <div class="step" data-step="2">2</div>
                <div class="step" data-step="3">3</div>
                <div class="step" data-step="4">4</div>
                <div class="step" data-step="5">5</div>
            </div>
            
            <form method="POST" action="" id="registrationForm" novalidate>
                <input type="hidden" name="step" id="step" value="1">
                
                <!-- Step 1: Personal Information -->
                <div class="form-step active" data-step="1">
                    <div class="section-title">PERSONAL INFORMATION</div>
                    
                    <label class="label required" for="surname">Surname:</label>
                    <input type="text" id="surname" name="surname" class="input" value="<?php echo $_POST['surname'] ?? ''; ?>" placeholder="Enter your surname" required>
                    
                    <label class="label required" for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" class="input" value="<?php echo $_POST['first_name'] ?? ''; ?>" placeholder="Enter your first name" required>
                    
                    <label class="label" for="middle_name">Middle Name:</label>
                    <input type="text" id="middle_name" name="middle_name" class="input" value="<?php echo $_POST['middle_name'] ?? ''; ?>" placeholder="Enter your middle name (optional)">
                    
                    <div class="form-navigation">
                        <a href="../index.php" class="nav-button cancel-button">Cancel</a>
                        <div class="placeholder-button"></div> <!-- Placeholder for spacing -->
                        <button type="button" class="nav-button next-button" data-next="2">Next</button>
                    </div>
                </div>
                
                <!-- Step 2: Birth Information -->
                <div class="form-step" data-step="2">
                    <div class="section-title">BIRTH INFORMATION</div>
                    
                    <label class="label required" for="birthdate">Birthdate:</label>
                    <input type="date" id="birthdate" name="birthdate" class="input" value="<?php echo $_POST['birthdate'] ?? ''; ?>" required>
                    
                    <label class="label required" for="age">Age:</label>
                    <input type="number" id="age" name="age" class="input" value="<?php echo $_POST['age'] ?? ''; ?>" placeholder="Your age will be calculated" required>
                    
                    <label class="label required" for="sex">Sex:</label>
                    <select id="sex" name="sex" class="select" required>
                            <option value="">Select Sex</option>
                        <option value="Male" <?php if (isset($_POST['sex']) && $_POST['sex'] == 'Male') echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if (isset($_POST['sex']) && $_POST['sex'] == 'Female') echo 'selected'; ?>>Female</option>
                        <option value="Others" <?php if (isset($_POST['sex']) && $_POST['sex'] == 'Others') echo 'selected'; ?>>Others</option>
                        </select>
                    
                    <label class="label required" for="civil_status">Civil Status:</label>
                    <select id="civil_status" name="civil_status" class="select" required>
                            <option value="">Select Civil Status</option>
                        <option value="Single" <?php if (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Single') echo 'selected'; ?>>Single</option>
                        <option value="Married" <?php if (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Married') echo 'selected'; ?>>Married</option>
                        <option value="Widowed" <?php if (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Widowed') echo 'selected'; ?>>Widowed</option>
                        <option value="Divorced" <?php if (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Divorced') echo 'selected'; ?>>Divorced</option>
                        </select>
                    
                    <div class="form-navigation">
                        <a href="../index.php" class="nav-button cancel-button">Cancel</a>
                        <button type="button" class="nav-button prev-button" data-prev="1">Previous</button>
                        <button type="button" class="nav-button next-button" data-next="3">Next</button>
                    </div>
                </div>
                
                <!-- Step 3: Address Information -->
                <div class="form-step" data-step="3">
                    <div class="section-title">ADDRESS INFORMATION</div>
                    
                    <!-- Detailed address form -->
                    <div class="address-title">PERMANENT ADDRESS</div>
                    <p class="address-note">Please provide your complete permanent address information below.</p>
                    
                    <div class="address-fields">
                        <div class="address-row">
                            <div class="field-group">
                                <label for="house_no">House/Lot/Apt No.</label>
                                <input type="text" id="house_no" name="house_no" class="input" value="<?php echo $_POST['house_no'] ?? ''; ?>" placeholder="e.g. 123">
                    </div>
                            <div class="field-group">
                                <label for="street">Street</label>
                                <input type="text" id="street" name="street" class="input" value="<?php echo $_POST['street'] ?? ''; ?>" placeholder="e.g. Main St.">
                            </div>
                    </div>
                    
						<!-- Province, Municipality/City, Barangay in one compact row -->
						<div class="address-row">
						<!-- Province first (with autofill) -->
						<div class="field-group">
							<label for="province" class="required">Province</label>
							<input type="text" id="province" name="province" class="input" value="<?php echo $_POST['province'] ?? ''; ?>" placeholder="Enter your province" list="provinceList" autocomplete="off" required>
							<datalist id="provinceList"></datalist>
						</div>
					
						<!-- Municipality/City (autofill filtered by Province) -->
						<div class="field-group">
							<label for="municipality" class="required">Municipality/City</label>
							<input type="text" id="municipality" name="municipality" class="input" value="<?php echo $_POST['municipality'] ?? ''; ?>" placeholder="Enter your municipality or city" list="municipalityList" autocomplete="off" required>
							<datalist id="municipalityList"></datalist>
						</div>
					
						<!-- Barangay (autofill filtered by Municipality/City) -->
						<div class="field-group">
							<label for="barangay" class="required">Barangay</label>
							<input type="text" id="barangay" name="barangay" class="input" value="<?php echo $_POST['barangay'] ?? ''; ?>" placeholder="Enter your barangay" list="barangayList" autocomplete="off" required>
							<datalist id="barangayList"></datalist>
						</div>
						</div>
                    
                        <div class="address-row">
                            <div class="field-group">
                                <label for="postal_code">Enter your Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code" class="input" value="<?php echo $_POST['postal_code'] ?? ''; ?>">
                </div>
                    </div>
                    </div>
                    
                    <!-- Hidden field to store the combined address -->
                    <input type="hidden" id="permanent_address" name="permanent_address" value="<?php echo $_POST['permanent_address'] ?? ''; ?>">
                    
                    <!-- Combined address preview -->
                    <div class="combined-address-preview" id="addressPreview">
                        Your complete address will appear here as you type.
                    </div>
                    
					<label class="label required" for="mobile">Mobile Number:</label>
					<div class="phone-input" style="display:flex; align-items:stretch; max-width:420px; width:100%; height:44px; border:1px solid #ccc; border-radius:6px; overflow:hidden; box-sizing:border-box;">
						<span class="phone-prefix" style="display:flex; align-items:center; padding:0 12px; background:#f5f5f5; font-weight:600; color:#333; border-right:1px solid #ccc; height:100%; flex-shrink:0;">+63</span>
						<input type="tel" id="mobile" name="mobile" class="input" value="<?php echo isset($_POST['mobile']) ? preg_replace('/[^0-9]/', '', $_POST['mobile']) : ''; ?>" placeholder="9123456789" inputmode="numeric" pattern="9[0-9]{9}" title="Enter 10 digits starting with 9 (e.g., 9123456789)" maxlength="10" style="height:100%; border:0; outline:none; border-radius:0; padding:0 12px; flex:1 1 auto; margin:0;" required oninput="sanitizePhMobile(this)">
					</div>
                    
                    <div class="form-navigation">
                        <a href="../index.php" class="nav-button cancel-button">Cancel</a>
                        <button type="button" class="nav-button prev-button" data-prev="2">Previous</button>
                        <button type="button" class="nav-button next-button" data-next="4">Next</button>
                    </div>
                </div>
                
                <!-- Step 4: Additional Information -->
                <div class="form-step" data-step="4">
                    <div class="section-title">ADDITIONAL INFORMATION</div>
                    
					<label class="label required" for="nationality">Nationality:</label>
					<select id="nationality" name="nationality" class="input" required style="appearance:none;-webkit-appearance:none;-moz-appearance:none;padding-right:36px;background:
						url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2214%22 height=%2214%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>')
						no-repeat right 12px center/14px;">
						<?php $nat = $_POST['nationality'] ?? ''; ?>
						<option value="">Select Nationality</option>
						<option value="Filipino" <?php echo ($nat==='Filipino'?'selected':''); ?>>Filipino</option>
						<option value="American" <?php echo ($nat==='American'?'selected':''); ?>>American</option>
						<option value="Chinese" <?php echo ($nat==='Chinese'?'selected':''); ?>>Chinese</option>
						<option value="Japanese" <?php echo ($nat==='Japanese'?'selected':''); ?>>Japanese</option>
						<option value="Malaysian" <?php echo ($nat==='Malaysian'?'selected':''); ?>>Malaysian</option>
						<option value="Others" <?php echo ($nat==='Others'?'selected':''); ?>>Others</option>
					</select>
                    
					<label class="label" for="religion">Religion:</label>
					<select id="religion" name="religion" class="input" style="appearance:none;-webkit-appearance:none;-moz-appearance:none;padding-right:36px;background:
						url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2214%22 height=%2214%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>')
						no-repeat right 12px center/14px;">
						<?php $rel = $_POST['religion'] ?? ''; ?>
						<option value="">Select Religion (optional)</option>
						<option value="Roman Catholic" <?php echo ($rel==='Roman Catholic'?'selected':''); ?>>Roman Catholic</option>
						<option value="Christianity" <?php echo ($rel==='Christianity'?'selected':''); ?>>Christianity</option>
						<option value="Islam" <?php echo ($rel==='Islam'?'selected':''); ?>>Islam</option>
						<option value="Others" <?php echo ($rel==='Others'?'selected':''); ?>>Others</option>
					</select>
                    
                    <label class="label" for="education">Education</label>
					<select class="input" name="education" id="education" required style="appearance:none;-webkit-appearance:none;-moz-appearance:none;padding-right:36px;background:
						url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2214%22 height=%2214%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>')
						no-repeat right 12px center/14px;">
                        <option value="">Select Education</option>
                        <option value="Elementary">Elementary</option>
                        <option value="High School">High School</option>
						<option value="College">College</option>
						<option value="Graduate">Graduate</option>
                        <option value="Post Graduate">Post Graduate</option>
                    </select>
                    
					<label class="label required" for="occupation">Occupation:</label>
					<select id="occupation" name="occupation" class="input" required style="appearance:none;-webkit-appearance:none;-moz-appearance:none;padding-right:36px;background:
						url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2214%22 height=%2214%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23666%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><polyline points=%226 9 12 15 18 9%22/></svg>')
						no-repeat right 12px center/14px;">
						<?php $occ = $_POST['occupation'] ?? ''; ?>
						<option value="">Select Occupation</option>
						<option value="Employed" <?php echo ($occ==='Employed'?'selected':''); ?>>Employed</option>
						<option value="Unemployed" <?php echo ($occ==='Unemployed'?'selected':''); ?>>Unemployed</option>
						<option value="Self-Employed" <?php echo ($occ==='Self-Employed'?'selected':''); ?>>Self-Employed</option>
						<option value="Student" <?php echo ($occ==='Student'?'selected':''); ?>>Student</option>
						<option value="Retired" <?php echo ($occ==='Retired'?'selected':''); ?>>Retired</option>
					</select>
                    
                    <div class="form-navigation">
                        <a href="../index.php" class="nav-button cancel-button">Cancel</a>
                        <button type="button" class="nav-button prev-button" data-prev="3">Previous</button>
                        <button type="button" class="nav-button next-button" data-next="5">Next</button>
                    </div>
                </div>
                
                <!-- Step 5: Account Information -->
                <div class="form-step" data-step="5">
                    <div class="section-title">ACCOUNT INFORMATION</div>
                    
                    <label class="label required" for="email">Email Address:</label>
                    <input type="email" id="email" name="email" class="input" value="<?php echo $_POST['email'] ?? ''; ?>" placeholder="youremail@example.com" required>
                    
					<label class="label required" for="password">Password:</label>
					<div class="password-wrapper">
						<input type="password" id="password" name="password" class="input input--with-toggle" placeholder="Enter a secure password" required minlength="8">
						<span class="toggle-password" title="Show/Hide" aria-label="Show password"></span>
					</div>
					<p class="password-hint">Password must be at least 8 characters long</p>
                    
					<label class="label required" for="confirm_password">Confirm Password:</label>
					<div class="password-wrapper">
						<input type="password" id="confirm_password" name="confirm_password" class="input input--with-toggle" placeholder="Confirm your password" required minlength="8">
						<span class="toggle-password" title="Show/Hide" aria-label="Show password"></span>
					</div>
					<p class="password-hint">Password must be at least 8 characters long</p>
                    
                    <div class="form-navigation">
                        <a href="../index.php" class="nav-button cancel-button">Cancel</a>
                        <button type="button" class="nav-button prev-button" data-prev="4">Previous</button>
                        <button type="submit" class="nav-button next-button submit-button">Register</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Step navigation
            const steps = document.querySelectorAll('.step');
            const formSteps = document.querySelectorAll('.form-step');
            const nextButtons = document.querySelectorAll('.next-button');
            const prevButtons = document.querySelectorAll('.prev-button');
            const stepInput = document.getElementById('step');
            const progressLine = document.getElementById('progress-line');
            
            // Calculate age from birthdate
            const birthdateInput = document.getElementById('birthdate');
            const ageInput = document.getElementById('age');
            
            birthdateInput.addEventListener('change', function() {
                const birthdate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthdate.getFullYear();
                
                const monthDiff = today.getMonth() - birthdate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
                    age--;
                }
                
                ageInput.value = age;
            });
            
            // Function to calculate line width based on step number
            function updateProgressLine(stepNumber) {
                // Calculate the total container width (excluding padding)
                const containerWidth = document.querySelector('.progress-container').offsetWidth - 32; // 32px for left+right padding
                
                // For a 5-step process, each step represents 25% progress (4 segments)
                const totalSegments = 4; // Total steps minus 1
                
                // Calculate the right width percentage for this step
                let segmentsCompleted = stepNumber - 1;
                let progressWidth = (segmentsCompleted / totalSegments) * containerWidth;
                
                // Set the line width directly in pixels for more precision
                progressLine.style.width = progressWidth + 'px';
            }
            
            // Handle window resize to recalculate line positions
            window.addEventListener('resize', function() {
                updateProgressLine(parseInt(stepInput.value));
            });
            
            // Function to navigate between steps
            function navigateToStep(stepNumber) {
                // Update hidden step input
                stepInput.value = stepNumber;
                
                // Update progress line
                updateProgressLine(stepNumber);
                
                // Update step indicators
                steps.forEach(step => {
                    const stepNum = parseInt(step.dataset.step);
                    if (stepNum === stepNumber) {
                        step.classList.add('active');
                        step.classList.remove('completed');
                    } else if (stepNum < stepNumber) {
                        step.classList.remove('active');
                        step.classList.add('completed');
                } else {
                        step.classList.remove('active', 'completed');
                    }
                });
                
                // Show/hide form steps with smooth transitions
                formSteps.forEach(formStep => {
                    if (parseInt(formStep.dataset.step) === stepNumber) {
                        formStep.classList.add('active');
                    } else {
                        formStep.classList.remove('active');
                    }
                });
                
                // Scroll to top of form for better mobile experience
                document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            
            // Function to validate current step with visual feedback
            function validateStep(stepNumber) {
                const currentStep = document.querySelector(`.form-step[data-step="${stepNumber}"]`);
                const requiredFields = currentStep.querySelectorAll('[required]');
                let isValid = true;

                // Special validation for PH mobile number when on Address step (3) or later
                const mobileField = document.getElementById('mobile');
                if (mobileField) {
                    const digits = (mobileField.value || '').replace(/[^0-9]/g, '');
                    if (digits.length !== 10 || digits.charAt(0) !== '9') {
                        if (stepNumber >= 3) {
                            isValid = false;
                            showFieldError('mobile', 'Please enter a valid Philippine mobile number (10 digits starting with 9)');
                        }
                    } else {
                        mobileField.style.borderColor = '#ccc';
                        mobileField.style.backgroundColor = 'white';
                    }
                }
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = '#d32f2f';
                        field.style.backgroundColor = 'rgba(211, 47, 47, 0.05)';
                        field.classList.add('shake');
                        
                        // Remove shake animation after it completes
                        setTimeout(() => {
                            field.classList.remove('shake');
                        }, 500);
                        
                        isValid = false;
                    } else {
                        field.style.borderColor = '#ccc';
                        field.style.backgroundColor = 'white';
                    }
                });
                
                if (!isValid) {
                    // Errors are already shown inline below each field
                }
                
                // Special case for step 5 (the final step)
                if (stepNumber === 5 && isValid) {
                    // Password length validation
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    // Check password length first
                    const passwordField = document.getElementById('password');
                    if (!password || password.length < 8) {
                        showFieldError('password', 'Password must be at least 8 characters long');
                        isValid = false;
                    } else {
                        clearFieldError('password');
                        passwordField.style.borderColor = '#ccc';
                        passwordField.style.backgroundColor = 'white';
                    }
                    
                    // Check confirm password length
                    const confirmPasswordField = document.getElementById('confirm_password');
                    if (confirmPassword && confirmPassword.length < 8) {
                        showFieldError('confirm_password', 'Confirm password must be at least 8 characters long');
                        isValid = false;
                    }
                    
                    // Password matching validation (only if both passwords are valid length)
                    if (isValid && password !== confirmPassword) {
                        showFieldError('password', 'Passwords do not match');
                        showFieldError('confirm_password', 'Passwords do not match');
                        isValid = false;
                    } else if (isValid && password === confirmPassword) {
                        clearFieldError('password');
                        clearFieldError('confirm_password');
                        confirmPasswordField.style.borderColor = '#ccc';
                        confirmPasswordField.style.backgroundColor = 'white';
                    }
                }
                
                return isValid;
            }
            
            /**
             * Show inline error text below input field
             */
            function showFieldError(fieldId, message) {
                const field = document.getElementById(fieldId);
                if (!field) return;
                
                // Find the parent container (could be field-group or direct parent)
                let container = field.closest('.field-group');
                if (!container) {
                    container = field.parentElement;
                }
                
                // Add shake animation
                container.classList.add('has-error');
                setTimeout(() => {
                    // Keep error styling but remove shake animation class
                    if (container.classList.contains('has-error')) {
                        container.style.animation = 'none';
                        setTimeout(() => {
                            container.style.animation = '';
                        }, 10);
                    }
                }, 500);
                
                // Find or create error text element - place it right after the input
                let errorText = container.querySelector('.input-error-text');
                if (!errorText) {
                    errorText = document.createElement('div');
                    errorText.className = 'input-error-text';
                    // Insert after the input field
                    field.parentElement.insertBefore(errorText, field.nextSibling);
                }
                errorText.textContent = message;
                
                // Style the field
                field.style.borderColor = '#d32f2f';
                field.style.backgroundColor = 'rgba(211, 47, 47, 0.05)';
            }
            
            /**
             * Clear error from a specific field
             */
            function clearFieldError(fieldId) {
                const field = document.getElementById(fieldId);
                if (!field) return;
                
                let container = field.closest('.field-group');
                if (!container) {
                    container = field.parentElement;
                }
                
                container.classList.remove('has-error');
                const errorText = container.querySelector('.input-error-text');
                if (errorText) {
                    errorText.remove();
                }
                
                field.style.borderColor = '';
                field.style.backgroundColor = '';
            }
            
            // Clear errors when user starts typing
            document.addEventListener('input', function(e) {
                if (e.target && e.target.id) {
                    clearFieldError(e.target.id);
                }
            });
            
            /**
             * Clear all field errors
             */
            function clearAllFieldErrors() {
                document.querySelectorAll('.has-error').forEach(container => {
                    container.classList.remove('has-error');
                    const errorText = container.querySelector('.input-error-text');
                    if (errorText) {
                        errorText.remove();
                    }
                });
                
                document.querySelectorAll('.input, .select').forEach(field => {
                    if (field.style.borderColor === 'rgb(211, 47, 47)' || field.style.borderColor === '#d32f2f') {
                        field.style.borderColor = '';
                        field.style.backgroundColor = '';
                    }
                });
            }
            
            /**
             * Friendly Notification Modal System
             * Displays user-friendly messages in an intuitive, non-threatening modal dialog
             */
            function showNotificationModal(notificationData) {
                // Remove any existing modals
                const existingModal = document.querySelector('.notification-modal-overlay');
                if (existingModal) {
                    existingModal.remove();
                }
                
                // Determine notification type and styling
                const notificationType = notificationData.type || 'info';
                const notificationTitle = notificationData.title || notificationData.type || 'Information';
                const notificationMessage = notificationData.message || 'An unknown issue occurred';
                const notificationDetails = notificationData.details || null;
                const notificationHelp = notificationData.help || 'Please check your information and try again. If the problem persists, contact technical support.';
                
                // Choose icon and modal class based on type
                let icon = 'ℹ️';
                let modalClass = 'info';
                if (notificationType === 'warning' || notificationData.category === 'Validation Error') {
                    icon = '⚠️';
                    modalClass = 'warning';
                } else if (notificationType === 'error' || notificationData.category === 'Server Error' || notificationData.category === 'Network Error') {
                    icon = '❌';
                    modalClass = 'error';
                } else if (notificationType === 'success') {
                    icon = '✅';
                    modalClass = 'success';
                }
                
                // Create modal overlay
                const overlay = document.createElement('div');
                overlay.className = 'notification-modal-overlay';
                
                // Create modal
                const modal = document.createElement('div');
                modal.className = `notification-modal ${modalClass}`;
                
                // Modal header
                const header = document.createElement('div');
                header.className = `notification-modal-header ${modalClass}`;
                header.innerHTML = `
                    <h3>
                        <span class="notification-icon">${icon}</span>
                        <span>${notificationTitle}</span>
                    </h3>
                    <button class="notification-modal-close" aria-label="Close">&times;</button>
                `;
                
                // Modal body
                const body = document.createElement('div');
                body.className = 'notification-modal-body';
                
                const typeLabel = document.createElement('div');
                typeLabel.className = 'notification-type';
                typeLabel.textContent = notificationData.category || 'Information';
                
                const message = document.createElement('div');
                message.className = 'notification-message';
                message.textContent = notificationMessage;
                
                body.appendChild(typeLabel);
                body.appendChild(message);
                
                // Add details if available
                if (notificationDetails) {
                    const details = document.createElement('div');
                    details.className = 'notification-details';
                    details.textContent = notificationDetails;
                    body.appendChild(details);
                }
                
                // Add help text
                const help = document.createElement('div');
                help.className = `notification-help ${modalClass}`;
                help.textContent = notificationHelp;
                body.appendChild(help);
                
                // Modal footer
                const footer = document.createElement('div');
                footer.className = 'notification-modal-footer';
                footer.innerHTML = `
                    <button class="notification-modal-button notification-modal-button-primary" onclick="this.closest('.notification-modal-overlay').remove()">
                        Got it, thanks!
                    </button>
                `;
                
                // Assemble modal
                modal.appendChild(header);
                modal.appendChild(body);
                modal.appendChild(footer);
                overlay.appendChild(modal);
                
                // Add to document
                document.body.appendChild(overlay);
                
                // Close on overlay click (outside modal)
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        overlay.remove();
                    }
                });
                
                // Close on close button click
                header.querySelector('.notification-modal-close').addEventListener('click', function() {
                    overlay.remove();
                });
                
                // Close on Escape key
                const escapeHandler = function(e) {
                    if (e.key === 'Escape') {
                        overlay.remove();
                        document.removeEventListener('keydown', escapeHandler);
                    }
                };
                document.addEventListener('keydown', escapeHandler);
                
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            
            // Alias for backward compatibility
            function showErrorModal(errorData) {
                // Convert error data to notification format
                const notificationData = {
                    type: 'warning',
                    title: errorData.type || 'Please Check',
                    message: errorData.message || 'An issue occurred',
                    category: errorData.category || 'Information',
                    details: errorData.details || null,
                    help: errorData.help || 'Please check your information and try again.'
                };
                
                // Use error type for critical errors
                if (errorData.category === 'Server Error' || errorData.category === 'Network Error') {
                    notificationData.type = 'error';
                }
                
                showNotificationModal(notificationData);
            }
            
            /**
             * Categorize and format errors for better user experience
             */
            function categorizeError(error, responseStatus = null) {
                let category = 'Error';
                let type = 'Registration Error';
                let message = error.message || 'An unexpected error occurred';
                let details = null;
                let help = 'Please check your information and try again. If the problem persists, contact technical support.';
                
                // Network errors
                if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError') || error.message.includes('network')) {
                    category = 'Network Error';
                    type = 'Connection Problem';
                    message = 'Unable to connect to the server. Please check your internet connection and try again.';
                    help = 'Make sure you have a stable internet connection. If you\'re using mobile data, try switching to Wi-Fi.';
                }
                // Timeout errors
                else if (error.message.includes('timeout') || error.message.includes('taking too long')) {
                    category = 'Timeout Error';
                    type = 'Request Timeout';
                    message = 'The server is taking too long to respond. Please try again in a moment.';
                    help = 'The server might be experiencing high traffic. Please wait a few moments and try again.';
                }
                // Validation errors
                else if (error.message.includes('Missing required field') || error.message.includes('Invalid') || error.message.includes('required')) {
                    category = 'Validation Error';
                    type = 'Invalid Information';
                    message = error.message;
                    help = 'Please review all the fields and make sure they are filled correctly. Required fields are marked with an asterisk (*).';
                }
                // Email already exists
                else if (error.message.includes('Email already registered') || error.message.includes('already exists') || error.message.includes('duplicate')) {
                    category = 'Account Error';
                    type = 'Email Already Registered';
                    message = 'This email address is already registered. Please use a different email or try logging in.';
                    help = 'If you already have an account, try logging in instead. If you forgot your password, use the "Forgot Password" option.';
                }
                // Server errors (5xx)
                else if (responseStatus >= 500) {
                    category = 'Server Error';
                    type = 'Server Problem';
                    message = 'The server encountered an error processing your request. Please try again later.';
                    help = 'This is a temporary server issue. Please try again in a few minutes. If the problem continues, contact technical support.';
                    details = error.message;
                }
                // Client errors (4xx)
                else if (responseStatus >= 400 && responseStatus < 500) {
                    category = 'Request Error';
                    type = 'Invalid Request';
                    message = error.message || 'Your request could not be processed. Please check your information.';
                    help = 'Please review your information and make sure all fields are correct.';
                }
                // JSON parse errors
                else if (error.message.includes('JSON') || error.message.includes('parse')) {
                    category = 'Data Error';
                    type = 'Invalid Response';
                    message = 'The server returned an invalid response. Please try again.';
                    help = 'This might be a temporary issue. Please refresh the page and try again.';
                    details = error.message;
                }
                // Generic errors
                else {
                    details = error.message;
                }
                
                return {
                    category: category,
                    type: type,
                    message: message,
                    details: details,
                    help: help
                };
            }
            
            // Add animation class for shake effect
            const style = document.createElement('style');
            style.textContent = `
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    20%, 60% { transform: translateX(-5px); }
                    40%, 80% { transform: translateX(5px); }
                }
                .shake {
                    animation: shake 0.5s ease-in-out;
                }
            `;
            document.head.appendChild(style);
            
            // Next button click
            nextButtons.forEach(button => {
                    button.addEventListener('click', function() {
                    const currentStep = parseInt(this.closest('.form-step').dataset.step);
                    if (validateStep(currentStep)) {
                        navigateToStep(parseInt(this.dataset.next));
                    }
                });
            });
            
            // Previous button click
            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    navigateToStep(parseInt(this.dataset.prev));
                });
            });
            
            // Initialize progress line
            updateProgressLine(1);
            
            // Add address-related functionality
            // Function to combine address fields and update the hidden permanent_address field
            function updateCombinedAddress() {
                const houseNo = document.getElementById('house_no').value.trim();
                const street = document.getElementById('street').value.trim();
                const barangay = document.getElementById('barangay').value.trim();
                const municipality = document.getElementById('municipality').value.trim();
                const province = document.getElementById('province').value.trim();
                const postalCode = document.getElementById('postal_code').value.trim();
                
                // Build address parts
                const addressParts = [];
                
                if (houseNo) addressParts.push(houseNo);
                if (street) addressParts.push(street);
                if (barangay) addressParts.push(barangay);
                if (municipality) addressParts.push(municipality);
                if (province) addressParts.push(province);
                if (postalCode) addressParts.push(postalCode);
                
                // Combine address parts
                const combinedAddress = addressParts.join(', ');
                
                // Update hidden field
                document.getElementById('permanent_address').value = combinedAddress;
                
                // Update address preview
                const previewElement = document.getElementById('addressPreview');
                if (combinedAddress) {
                    previewElement.textContent = combinedAddress;
                    } else {
                    previewElement.textContent = 'Your complete address will appear here as you type.';
                }
                
                return combinedAddress;
            }
            
            // Add event listeners to all address fields
            const addressFields = ['house_no', 'street', 'barangay', 'municipality', 'province', 'postal_code'];
            addressFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', updateCombinedAddress);
                    field.addEventListener('change', updateCombinedAddress);
                }
            });

			// ---- Address Autofill (Province -> Municipality/City -> Barangay) ----
			(function initAddressAutofill() {
				const provinceInput = document.getElementById('province');
				const municipalityInput = document.getElementById('municipality');
				const barangayInput = document.getElementById('barangay');

				const provinceList = document.getElementById('provinceList');
				const municipalityList = document.getElementById('municipalityList');
				const barangayList = document.getElementById('barangayList');

				// Caches for lookups
				let provinces = []; // [{name, code, isHUC?}]
				let muniByProvCode = {}; // {provCode: [{name, code, isHUC?}]}
				let brgyByCityMuniCode = {}; // {cityMuniCode: [name]}

				// Helper: cached fetch with localStorage
				async function cachedFetch(url, cacheKey, ttlMinutes = 1440) {
					try {
						const now = Date.now();
						const cached = localStorage.getItem(cacheKey);
						if (cached) {
							const { savedAt, data } = JSON.parse(cached);
							if (now - savedAt < ttlMinutes * 60 * 1000) {
								return data;
							}
						}
						const res = await fetch(url);
						if (!res.ok) throw new Error('Network error');
						const data = await res.json();
						localStorage.setItem(cacheKey, JSON.stringify({ savedAt: now, data }));
						return data;
					} catch (e) {
						console.error('cachedFetch error for', url, e);
						return null;
					}
				}

				// Load provinces (plus HUCs)
				async function loadProvinces() {
					const provs = await cachedFetch('https://psgc.gitlab.io/api/provinces/', 'psgc_provinces');
					const hucs = await cachedFetch('https://psgc.gitlab.io/api/huc/', 'psgc_huc');

					provinces = [];
					if (Array.isArray(provs)) provs.forEach(p => provinces.push({ name: p.name, code: p.code }));
					if (Array.isArray(hucs)) hucs.forEach(c => provinces.push({ name: c.name, code: c.code, isHUC: true }));

					// Populate datalist
					provinceList.innerHTML = '';
					provinces
						.sort((a, b) => a.name.localeCompare(b.name))
						.forEach(p => {
							const opt = document.createElement('option');
							opt.value = p.name;
							opt.label = p.name;
							provinceList.appendChild(opt);
						});
				}

				function findProvinceByName(name) {
					if (!name) return null;
					const n = name.trim().toLowerCase();
					return provinces.find(p => p.name.toLowerCase() === n) || null;
				}

				async function loadMunicipalitiesForProvinceName(provinceName) {
					municipalityList.innerHTML = '';
					barangayList.innerHTML = '';
					barangayInput.value = '';

					const prov = findProvinceByName(provinceName);
					if (!prov) return;

					if (prov.isHUC) {
						muniByProvCode[prov.code] = [{ name: prov.name, code: prov.code, isHUC: true }];
					} else if (!muniByProvCode[prov.code]) {
						const url = `https://psgc.gitlab.io/api/provinces/${prov.code}/cities-municipalities/`;
						const data = await cachedFetch(url, `psgc_muni_${prov.code}`);
						if (Array.isArray(data)) {
							muniByProvCode[prov.code] = data.map(m => ({ name: m.name, code: m.code }));
						} else {
							muniByProvCode[prov.code] = [];
						}
					}

					(muniByProvCode[prov.code] || [])
						.sort((a, b) => a.name.localeCompare(b.name))
						.forEach(m => {
							const opt = document.createElement('option');
							opt.value = m.name;
							opt.label = m.name;
							municipalityList.appendChild(opt);
						});
				}

				function findMunicipalityByName(provinceName, muniName) {
					const prov = findProvinceByName(provinceName);
					if (!prov) return null;
					const list = muniByProvCode[prov.code] || [];
					const n = (muniName || '').trim().toLowerCase();
					return list.find(m => m.name.toLowerCase() === n) || null;
				}

				async function loadBarangaysForMunicipality(provinceName, muniName) {
					barangayList.innerHTML = '';
					const muni = findMunicipalityByName(provinceName, muniName);
					if (!muni) return;

					if (!brgyByCityMuniCode[muni.code]) {
						const url = `https://psgc.gitlab.io/api/cities-municipalities/${muni.code}/barangays/`;
						const data = await cachedFetch(url, `psgc_brgy_${muni.code}`);
						if (Array.isArray(data)) {
							brgyByCityMuniCode[muni.code] = data.map(b => b.name);
						} else {
							brgyByCityMuniCode[muni.code] = [];
						}
					}

					(brgyByCityMuniCode[muni.code] || [])
						.sort((a, b) => a.localeCompare(b))
						.forEach(name => {
							const opt = document.createElement('option');
							opt.value = name;
							opt.label = name;
							barangayList.appendChild(opt);
						});
				}

				// Wire up events
				if (provinceInput) {
					provinceInput.addEventListener('change', () => {
						loadMunicipalitiesForProvinceName(provinceInput.value);
						updateCombinedAddress();
						// Trigger postal code autofill after a short delay
						setTimeout(() => {
							if (window.tryFillPostalCode) {
								window.tryFillPostalCode();
							}
						}, 300);
					});
				}
				if (municipalityInput) {
					municipalityInput.addEventListener('change', () => {
						loadBarangaysForMunicipality(provinceInput.value, municipalityInput.value);
						updateCombinedAddress();
						// Trigger postal code autofill after a short delay
						setTimeout(() => {
							if (window.tryFillPostalCode) {
								window.tryFillPostalCode();
							}
						}, 300);
					});
				}
				if (barangayInput) {
					barangayInput.addEventListener('change', () => {
						updateCombinedAddress();
						// Trigger postal code autofill after a short delay
						setTimeout(() => {
							if (window.tryFillPostalCode) {
								window.tryFillPostalCode();
							}
						}, 300);
					});
				}

				// Initialize
				loadProvinces().then(() => {
					// If existing value is present (e.g., returning to form), pre-load dependent lists
					if (provinceInput && provinceInput.value) {
						loadMunicipalitiesForProvinceName(provinceInput.value).then(() => {
							if (municipalityInput && municipalityInput.value) {
                                loadBarangaysForMunicipality(provinceInput.value, municipalityInput.value);
							}
						});
					}
				});
			})();

			// --- PH Mobile helper: enforce 10 digits (no leading 0) after +63 ---
			function sanitizePhMobile(inputEl) {
				// Keep digits only
				let digits = (inputEl.value || '').replace(/[^0-9]/g, '');
				// Enforce first digit must be 9 (equivalent to 09 when using +63 prefix)
				if (digits.length > 0 && digits.charAt(0) !== '9') {
					// If user typed 0 first, drop it so the sequence begins with 9 as required for PH mobiles
					digits = digits.replace(/^0+/, '');
				}
				// Limit to 10 digits
				if (digits.length > 10) digits = digits.slice(0, 10);
				inputEl.value = digits;

				// Realtime validity styling
				if (digits.length === 10 && digits.charAt(0) === '9') {
					inputEl.setCustomValidity('');
					inputEl.style.borderColor = '#ccc';
					inputEl.style.backgroundColor = 'white';
				} else {
					inputEl.setCustomValidity('Enter 10 digits starting with 9');
				}
			}

			// --- Toggle password visibility buttons (SVG eye/eye-off) ---
			(function passwordToggles(){
				const svgEye = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
				const svgEyeOff = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.06-5.94"/><path d="M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a21.77 21.77 0 0 1-3.22 4.2"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
				
				document.querySelectorAll('.password-wrapper .toggle-password').forEach(function(btn){
					// Initialize with eye icon
					btn.innerHTML = svgEye;
					btn.addEventListener('click', function(){
						const input = this.previousElementSibling;
						if (!input) return;
						const isText = input.type === 'text';
						input.type = isText ? 'password' : 'text';
						this.innerHTML = isText ? svgEye : svgEyeOff;
						this.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
						this.title = isText ? 'Show' : 'Hide';
					});
				});
			})();

			// --- Enhanced Postal Code Auto-fill ---
			// Automatically fills postal code when address fields are selected
			// Only fills if postal code field is empty (allows user override)
			// Expose tryFillPostal globally for integration with address autofill
			window.tryFillPostalCode = null;
			(async function initPostalAutoFill() {
				const postalInput = document.getElementById('postal_code');
				const provinceInput = document.getElementById('province');
				const municipalityInput = document.getElementById('municipality');
				const barangayInput = document.getElementById('barangay');

				if (!postalInput) return;

				// Best-effort dataset source (may change over time). Cached in localStorage.
				const ZIP_DATA_URL = 'https://raw.githubusercontent.com/erwinsie/ph-zipcodes/master/zipcodes.json';
				let zipIndex = null; // { "Province|City/Municipality|Barangay" : "Zip" } and fallbacks
				let wasAutoFilled = false; // Track if postal code was auto-filled

				async function loadZipDataset() {
					try {
						const cacheKey = 'ph_zipcodes_dataset_v1';
						const now = Date.now();
						const cached = localStorage.getItem(cacheKey);
						if (cached) {
							const { savedAt, data } = JSON.parse(cached);
							if (now - savedAt < 7 * 24 * 60 * 60 * 1000) { // 7 days
								return data;
							}
						}
						const res = await fetch(ZIP_DATA_URL, { cache: 'force-cache' });
						if (!res.ok) throw new Error('Zip dataset fetch failed');
						const data = await res.json();
						localStorage.setItem(cacheKey, JSON.stringify({ savedAt: now, data }));
						return data;
					} catch (e) {
						console.warn('Postal dataset unavailable:', e.message);
						return null;
					}
				}

				function buildZipIndex(raw) {
					const idx = { exact: {}, byCity: {} };
					if (!Array.isArray(raw)) return idx;
					raw.forEach(row => {
						// Expecting fields like: province, city, barangay, zipcode (varies by dataset)
						const province = (row.province || row.Province || '').trim().toLowerCase();
						const city = (row.city || row.municipality || row.City || '').trim().toLowerCase();
						const barangay = (row.barangay || row.Barangay || '').trim().toLowerCase();
						const zip = (row.zipcode || row.ZipCode || row.zip || '').toString().trim();
						if (!zip) return;
						if (province && city && barangay) {
							idx.exact[`${province}|${city}|${barangay}`] = zip;
						}
						if (province && city && !idx.byCity[`${province}|${city}`]) {
							idx.byCity[`${province}|${city}`] = zip; // fallback zip at city level
						}
					});
					return idx;
				}

				function tryFillPostal() {
					if (!zipIndex) return;
					
					// Only auto-fill if postal code is empty (allows user to override)
					if (postalInput.value.trim() !== '' && !wasAutoFilled) {
						return;
					}
					
					const p = (provinceInput.value || '').trim().toLowerCase();
					const m = (municipalityInput.value || '').trim().toLowerCase();
					const b = (barangayInput.value || '').trim().toLowerCase();

					let found = null;
					// Try exact match first (province + municipality + barangay)
					if (p && m && b) {
						found = zipIndex.exact[`${p}|${m}|${b}`] || null;
					}
					// Fallback to municipality level
					if (!found && p && m) {
						found = zipIndex.byCity[`${p}|${m}`] || null;
					}
					
					if (found) {
						postalInput.value = found;
						wasAutoFilled = true;
						postalInput.classList.add('postal-code-autofilled');
						
						// Remove indicator after 3 seconds
						setTimeout(() => {
							postalInput.classList.remove('postal-code-autofilled');
						}, 3000);
						
						// Update address preview
						if (typeof updateCombinedAddress === 'function') {
							updateCombinedAddress();
						}
					} else {
						wasAutoFilled = false;
						postalInput.classList.remove('postal-code-autofilled');
					}
				}
				
				// Expose function globally for integration
				window.tryFillPostalCode = tryFillPostal;

				// Track manual input to remove auto-fill indicator
				postalInput.addEventListener('input', function() {
					if (this.value.trim() === '') {
						wasAutoFilled = false;
					} else {
						// If user manually types, remove auto-fill class
						if (!this.classList.contains('postal-code-autofilled')) {
							wasAutoFilled = false;
						}
					}
					this.classList.remove('postal-code-autofilled');
				});

				// Load dataset and set up event listeners
				const dataset = await loadZipDataset();
				if (dataset) {
					zipIndex = buildZipIndex(dataset);

					// Listen to all address field changes
					if (provinceInput) {
						provinceInput.addEventListener('change', tryFillPostal);
						provinceInput.addEventListener('input', function() {
							// Clear postal code when province changes if it was auto-filled
							if (wasAutoFilled) {
								postalInput.value = '';
								wasAutoFilled = false;
								postalInput.classList.remove('postal-code-autofilled');
							}
						});
					}
					if (municipalityInput) {
						municipalityInput.addEventListener('change', tryFillPostal);
						municipalityInput.addEventListener('input', function() {
							// Clear postal code when municipality changes if it was auto-filled
							if (wasAutoFilled) {
								postalInput.value = '';
								wasAutoFilled = false;
								postalInput.classList.remove('postal-code-autofilled');
							}
						});
					}
					if (barangayInput) {
						barangayInput.addEventListener('change', tryFillPostal);
						barangayInput.addEventListener('input', function() {
							// Clear postal code when barangay changes if it was auto-filled
							if (wasAutoFilled) {
								postalInput.value = '';
								wasAutoFilled = false;
								postalInput.classList.remove('postal-code-autofilled');
							}
						});
					}

					// Try on load in case fields already filled
					setTimeout(tryFillPostal, 500);
				}
			})();
            
            // Update the combined address on page load
            window.addEventListener('DOMContentLoaded', function() {
                // If there's an existing permanent_address but no individual fields,
                // we can try to parse it (for returning users)
                const permanentAddress = document.getElementById('permanent_address').value;
                if (permanentAddress && !document.getElementById('barangay').value) {
                    // We won't implement parsing logic here, just show the address
                    document.getElementById('addressPreview').textContent = permanentAddress;
                    } else {
                    // Otherwise, build from individual fields
                    updateCombinedAddress();
                }
            });
            
            // Function to validate step 3 specifically for address fields
            function validateStep3() {
                const requiredFields = ['barangay', 'municipality', 'province'];
                let isValid = true;
                
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value.trim()) {
                        showFieldError(fieldId, 'This field is required');
                        isValid = false;
                    } else {
                        clearFieldError(fieldId);
                    }
                });
                
                if (!isValid) {
                    return false;
                }
                
                // Ensure the address is combined before proceeding
                updateCombinedAddress();
                
                // Validate if we have a combined address now
                if (!document.getElementById('permanent_address').value) {
                    showFieldError('barangay', 'Please provide a complete address');
                    return false;
                }
                
                return true;
            }
            
            // Override next button click for step 3
            document.querySelector('.form-step[data-step="3"] .next-button').addEventListener('click', function(e) {
                if (!validateStep3()) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                
                // Continue with normal next button behavior
                navigateToStep(parseInt(this.dataset.next));
            });
            
            // Before form submission, ensure address is combined
            document.getElementById('registrationForm').addEventListener('submit', function() {
                updateCombinedAddress();
            });
            
            // Form submission handler
            document.getElementById('registrationForm').addEventListener('submit', function(e) {
                const currentStepNum = parseInt(stepInput.value);
                
                // Always prevent default form submission - we handle it via AJAX
                e.preventDefault();
                e.stopPropagation();
                
                // Always ensure we're on step 5 when submitting
                if (currentStepNum !== 5) {
                    // If we're not on the final step, prevent submission
                    // Navigate to step 5 to show the form
                    navigateToStep(5);
                    return false;
                }
                
                // Validate the current step fields - this includes password length check
                if (!validateStep(currentStepNum)) {
                    // Validation failed - error modal already shown by validateStep
                    return false;
                }
                
                // Additional password validation before submission (double-check)
                clearAllFieldErrors();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (!password || password.length < 8) {
                    showFieldError('password', 'Password must be at least 8 characters long');
                    return false;
                }
                
                if (password !== confirmPassword) {
                    showFieldError('password', 'Passwords do not match');
                    showFieldError('confirm_password', 'Passwords do not match');
                    return false;
                }
                
                // Handle form submission via AJAX instead of traditional form submission
                
                // Make sure the step is set to 5 when submitting
                stepInput.value = 5;
                
                // Show loading indicator
                const formContainer = document.querySelector('.form-container');
                const loadingOverlay = document.createElement('div');
                loadingOverlay.className = 'loading-overlay';
                loadingOverlay.innerHTML = `
                    <div class="loading-content">
                        <div class="loading-spinner"></div>
                        <p>Processing registration...</p>
                    </div>
                `;
                document.body.appendChild(loadingOverlay);
                
                // Get form data
                const formData = new FormData(this);

                // Normalize mobile to 11 digits starting with 0 before sending to API
                (function normalizeMobileForApi(fd){
                    const raw = (fd.get('mobile') || '').replace(/[^0-9]/g, '');
                    if (/^9\d{9}$/.test(raw)) {
                        fd.set('mobile', '0' + raw);
                    } else if (/^0\d{10}$/.test(raw)) {
                        fd.set('mobile', raw);
                    }
                })(formData);
                
                /**
                 * Fix registration submission error
                 * - Added error debugging and improved error handling
                 * - Added loading overlay timeout to prevent indefinite loading
                 * - Properly handling network errors and JSON parse failures
                 */
                // Set a timeout to remove the loading overlay after 15 seconds
                // in case the server doesn't respond
                const loadingTimeout = setTimeout(() => {
                    if (document.body.contains(loadingOverlay)) {
                        loadingOverlay.remove();
                        const timeoutError = categorizeError(
                            new Error('The server is taking too long to respond'),
                            null
                        );
                        showErrorModal(timeoutError);
                    }
                }, 15000);
                
                // Send data to the API endpoint with corrected path
                fetch('../api/donor_register.php', {
                    method: 'POST',
                    body: formData,
                    // Adding credentials to ensure cookies are sent with the request
                    credentials: 'same-origin'
                })
                .then(response => {
                    // Store response status for error categorization
                    const responseStatus = response.status;
                    
                    // Check if response is OK
                    if (!response.ok) {
                        // Log the response for debugging
                        console.error('Server response error:', response.status, response.statusText);
                        return response.text().then(text => {
                            // Try to parse as JSON, but if it fails, return as text
                            let errorMessage;
                            try {
                                const jsonData = JSON.parse(text);
                                errorMessage = jsonData.message || `Server responded with status: ${response.status}`;
                            } catch (e) {
                                if (e instanceof SyntaxError) {
                                    // This is a parsing error, return the raw text
                                    errorMessage = `Server error (${response.status}): ${text || 'No response details'}`;
                                } else {
                                    errorMessage = text || `Server error (${response.status})`;
                                }
                            }
                            
                            // Create error object with status
                            const error = new Error(errorMessage);
                            error.status = responseStatus;
                            throw error;
                        });
                    }
                    
                    return response.text().then(text => {
                        // Log the raw response for debugging
                        console.log('Raw server response:', text);
                        
                        // Try to parse JSON
                        try {
                            return JSON.parse(text);
                        } catch (error) {
                            console.error('JSON parse error:', error);
                            const parseError = new Error('Invalid response format: ' + text.substring(0, 100));
                            parseError.status = responseStatus;
                            throw parseError;
                        }
                    });
                })
                .then(data => {
                    // Remove loading timeout and overlay
                    clearTimeout(loadingTimeout);
                    if (document.body.contains(loadingOverlay)) {
                        loadingOverlay.remove();
                    }
                    
                    if (data.success) {
                        // Redirect immediately on success
                        window.location.href = data.data.redirect;
                    } else {
                        // Check if it's a validation error (email already exists, etc.)
                        const errorMessage = data.message || 'Unknown error occurred';
                        const errorCategory = categorizeError(
                            new Error(errorMessage),
                            data.status || 400
                        );
                        
                        // Show modal only for critical server/network errors
                        if (errorCategory.category === 'Server Error' || errorCategory.category === 'Network Error' || errorCategory.category === 'Timeout Error') {
                            showErrorModal(errorCategory);
                        } else {
                            // For validation errors (email exists, invalid data, etc.), show inline error text
                            clearAllFieldErrors();
                            if (errorMessage.toLowerCase().includes('email') && errorMessage.toLowerCase().includes('already')) {
                                showFieldError('email', 'This email is already registered. Please use a different email or try logging in.');
                            } else if (errorMessage.toLowerCase().includes('mobile')) {
                                showFieldError('mobile', errorMessage);
                            } else {
                                // Try to find the relevant field or show on email as fallback
                                showFieldError('email', errorMessage);
                            }
                        }
                    }
                })
                .catch(error => {
                    // Remove loading timeout and overlay
                    clearTimeout(loadingTimeout);
                    if (document.body.contains(loadingOverlay)) {
                        loadingOverlay.remove();
                    }
                    
                    // Log error for debugging
                    console.error('Registration error:', error);
                    
                    // Categorize error
                    const errorData = categorizeError(error, error.status || null);
                    
                    // Show modal only for critical server/network errors
                    if (errorData.category === 'Server Error' || errorData.category === 'Network Error' || errorData.category === 'Timeout Error') {
                        showErrorModal(errorData);
                    } else {
                        // For other errors, show inline error text
                        clearAllFieldErrors();
                        showFieldError('email', errorData.message || 'An error occurred. Please try again.');
                    }
                });
            });
            
            // Add a specific handler for the register button (submit button) to ensure it works properly
            document.querySelector('.submit-button').addEventListener('click', function(e) {
                /**
                 * Improved validation for form submission
                 * - Explicitly prevents default behavior to ensure AJAX handling
                 * - Forces validation of step 5 before proceeding
                 * - Ensures the correct hidden step value is set
                 * - Added extra verification of password field
                 */
                // Prevent the default submit behavior
                e.preventDefault();
                
                // Make sure we're on step 5 when clicking the register button
                stepInput.value = 5;
                
                // Validate the current step
                if (!validateStep(5)) {
                    return false;
                }
                
                // Clear all previous errors
                clearAllFieldErrors();
                
                // Verify password fields explicitly
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (!password || password.length < 8) {
                    showFieldError('password', 'Password must be at least 8 characters long');
                    return false;
                }
                
                if (password !== confirmPassword) {
                    showFieldError('password', 'Passwords do not match');
                    showFieldError('confirm_password', 'Passwords do not match');
                    return false;
                }
                
                // Run a final verification on all required fields
                const requiredFields = [
                    'surname', 'first_name', 'sex', 'civil_status', 'birthdate', 
                    'age', 'nationality', 'occupation', 'mobile', 'email', 
                    'password', 'confirm_password'
                ];
                
                let missingFields = [];
                requiredFields.forEach(fieldId => {
                    const fieldElement = document.getElementById(fieldId);
                    if (fieldElement && !fieldElement.value.trim()) {
                        missingFields.push(fieldId);
                        showFieldError(fieldId, 'This field is required');
                    }
                });
                
                if (missingFields.length > 0) {
                    return false;
                }
                
                // Ensure the permanent address is combined and set
                updateCombinedAddress();
                const addressField = document.getElementById('permanent_address');
                if (!addressField || !addressField.value) {
                    // Show errors on address fields
                    const addressFields = ['barangay', 'municipality', 'province'];
                    addressFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (field && !field.value.trim()) {
                            showFieldError(fieldId, 'This field is required');
                        }
                    });
                    return false;
                }
                
                // Trigger the form submission event if validation passes
                document.getElementById('registrationForm').dispatchEvent(new Event('submit'));
                
                return false; // Ensure we don't double-submit
            });
        });
    </script>
</body>
</html> 