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
        }
        
        .address-row .field-group {
            flex: 1;
        }
        
        .field-group label {
            display: block;
            font-size: 14px;
            margin-bottom: 4px;
            color: #444;
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
        
        /* Mobile-specific adjustment for the cancel button */
        @media (max-width: 480px) {
            .cancel-button {
                padding: 6px 12px;
                font-size: 13px;
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
            
            <form method="POST" action="" id="registrationForm">
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
                        <a href="../login.php" class="nav-button cancel-button">Cancel</a>
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
                    
                        <div class="field-group">
                            <label for="barangay" class="required">Barangay</label>
                            <input type="text" id="barangay" name="barangay" class="input" value="<?php echo $_POST['barangay'] ?? ''; ?>" placeholder="Enter your barangay" required>
                    </div>
                    
                        <div class="field-group">
                            <label for="municipality" class="required">Municipality/City</label>
                            <input type="text" id="municipality" name="municipality" class="input" value="<?php echo $_POST['municipality'] ?? ''; ?>" placeholder="Enter your municipality or city" required>
                    </div>
                    
                        <div class="address-row">
                            <div class="field-group">
                                <label for="province" class="required">Province</label>
                                <input type="text" id="province" name="province" class="input" value="<?php echo $_POST['province'] ?? ''; ?>" placeholder="Enter your province" required>
                    </div>
                            <div class="field-group">
                                <label for="postal_code">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code" class="input" value="<?php echo $_POST['postal_code'] ?? ''; ?>" placeholder="e.g. 1234">
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
                    <input type="tel" id="mobile" name="mobile" class="input" value="<?php echo $_POST['mobile'] ?? ''; ?>" placeholder="e.g. 09123456789" required>
                    
                    <div class="form-navigation">
                        <a href="../login.php" class="nav-button cancel-button">Cancel</a>
                        <button type="button" class="nav-button prev-button" data-prev="2">Previous</button>
                        <button type="button" class="nav-button next-button" data-next="4">Next</button>
                    </div>
                </div>
                
                <!-- Step 4: Additional Information -->
                <div class="form-step" data-step="4">
                    <div class="section-title">ADDITIONAL INFORMATION</div>
                    
                    <label class="label required" for="nationality">Nationality:</label>
                    <input type="text" id="nationality" name="nationality" class="input" value="<?php echo $_POST['nationality'] ?? ''; ?>" placeholder="e.g. Filipino" required>
                    
                    <label class="label" for="religion">Religion:</label>
                    <input type="text" id="religion" name="religion" class="input" value="<?php echo $_POST['religion'] ?? ''; ?>" placeholder="e.g. Catholic (optional)">
                    
                    <label class="label" for="education">Education:</label>
                    <input type="text" id="education" name="education" class="input" value="<?php echo $_POST['education'] ?? ''; ?>" placeholder="Highest educational attainment (optional)">
                    
                    <label class="label required" for="occupation">Occupation:</label>
                    <input type="text" id="occupation" name="occupation" class="input" value="<?php echo $_POST['occupation'] ?? ''; ?>" placeholder="e.g. Student, Engineer, Teacher" required>
                    
                    <div class="form-navigation">
                        <a href="../login.php" class="nav-button cancel-button">Cancel</a>
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
                    <input type="password" id="password" name="password" class="input" placeholder="Enter a secure password" required>
                    
                    <label class="label required" for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="input" placeholder="Confirm your password" required>
                    
                    <div class="form-navigation">
                        <a href="login.php" class="nav-button cancel-button">Cancel</a>
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
                    // Show toast notification instead of alert for better mobile UX
                    showToast('Please fill in all required fields.');
                }
                
                // Special case for step 5 (the final step)
                if (stepNumber === 5 && isValid) {
                    // Password matching validation
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (password !== confirmPassword) {
                        showToast('Passwords do not match!');
                        document.getElementById('confirm_password').style.borderColor = '#d32f2f';
                        document.getElementById('confirm_password').style.backgroundColor = 'rgba(211, 47, 47, 0.05)';
                return false;
                    }
                }
                
                return isValid;
            }
            
            // Toast notification for better mobile experience
            function showToast(message) {
                const toast = document.createElement('div');
                toast.className = 'toast-message';
                toast.textContent = message;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.classList.add('show');
                }, 100);
                
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(toast);
                    }, 300);
                }, 3000);
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
                .toast-message {
                    position: fixed;
                    bottom: -60px;
                    left: 50%;
                    transform: translateX(-50%);
                    background-color: rgba(33, 33, 33, 0.9);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 24px;
                    font-size: 14px;
                    z-index: 9999;
                    transition: bottom 0.3s ease-in-out;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                    text-align: center;
                    max-width: 85%;
                }
                .toast-message.show {
                    bottom: 30px;
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
                        field.style.borderColor = '#d32f2f';
                        field.style.backgroundColor = 'rgba(211, 47, 47, 0.05)';
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    showToast('Please fill in all required address fields.');
                    return false;
                }
                
                // Ensure the address is combined before proceeding
                updateCombinedAddress();
                
                // Validate if we have a combined address now
                if (!document.getElementById('permanent_address').value) {
                    showToast('Please provide a valid address.');
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
                
                // Always ensure we're on step 5 when submitting
                if (currentStepNum !== 5) {
                    // If we're not on the final step, prevent submission
                e.preventDefault();
                    return;
                }
                
                // Validate the current step fields
                if (!validateStep(currentStepNum)) {
                    e.preventDefault();
                    return;
                }
                
                // Handle form submission via AJAX instead of traditional form submission
                e.preventDefault();
                
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
                        showToast('The server is taking too long to respond. Please try again later.');
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
                    // Check if response is OK
                    if (!response.ok) {
                        // Log the response for debugging
                        console.error('Server response error:', response.status, response.statusText);
                        return response.text().then(text => {
                            // Try to parse as JSON, but if it fails, return as text
                            try {
                                const jsonData = JSON.parse(text);
                                throw new Error(jsonData.message || `Server responded with status: ${response.status}`);
                            } catch (e) {
                                if (e instanceof SyntaxError) {
                                    // This is a parsing error, return the raw text
                                    throw new Error(`Server error (${response.status}): ${text || 'No response details'}`);
                                }
                                // Otherwise this is our constructed error, so rethrow it
                                throw e;
                            }
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
                            throw new Error('Invalid response format: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {
                    // Remove loading timeout and overlay
                    clearTimeout(loadingTimeout);
                    loadingOverlay.remove();
                    
                    if (data.success) {
                        // Show success message and redirect
                        showToast('Registration successful! Redirecting...');
                        
                        /**
                         * Enhanced Redirect Handling
                         * - Using server-provided redirect or relative fallback
                         * - Added delay for toast visibility
                         */
                        setTimeout(() => {
                            // Use the redirect path from server if available,
                            // otherwise fall back to relative path
                            if (data.data && data.data.redirect) {
                                // Check if redirect is absolute or relative
                                if (data.data.redirect.startsWith('/')) {
                                    // For absolute paths, ensure they work in the current environment
                                    const basePath = window.location.pathname.split('/mobile-app/')[0];
                                    window.location.href = basePath + data.data.redirect.substring(1);
                                } else {
                                    window.location.href = data.data.redirect;
                                }
                            } else {
                                // Default redirect path updated to use relative path from templates directory
                                window.location.href = '../index.php';
                            }
                        }, 1500);
                    } else {
                        // Show error message
                        showToast('Registration failed: ' + (data.message || 'Unknown error'));
                        
                        // Add error message to the page
                        const errorContainer = document.createElement('div');
                        errorContainer.className = 'error-message';
                        errorContainer.innerHTML = `
                            <h3>Registration Error:</h3>
                            <p>${data.message || 'Unknown error'}</p>
                            <p><small>If this error persists, please contact technical support with the error details above.</small></p>
                        `;
                        
                        // Insert at the top of the form container
                        formContainer.insertBefore(errorContainer, formContainer.firstChild);
                        
                        // Scroll to the top to show the error
                        window.scrollTo(0, 0);
                    }
                })
                .catch(error => {
                    // Remove loading timeout and overlay
                    clearTimeout(loadingTimeout);
                    if (document.body.contains(loadingOverlay)) {
                        loadingOverlay.remove();
                    }
                    
                    // Show detailed error message
                    const errorMsg = 'Registration error: ' + error.message;
                    console.error(errorMsg);
                    showToast(errorMsg);
                    
                    // Add detailed error message to the page
                    const errorContainer = document.createElement('div');
                    errorContainer.className = 'error-message';
                    errorContainer.innerHTML = `
                        <h3>Technical Error:</h3>
                        <p>${error.message}</p>
                        <p><small>Please try again later or contact technical support with this error message.</small></p>
                    `;
                    
                    // Insert at the top of the form container
                    formContainer.insertBefore(errorContainer, formContainer.firstChild);
                    
                    // Scroll to the top to show the error
                    window.scrollTo(0, 0);
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
                
                // Verify password fields explicitly
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (!password || password.length < 6) {
                    showToast('Password must be at least 6 characters long');
                    document.getElementById('password').style.borderColor = '#d32f2f';
                    return false;
                }
                
                if (password !== confirmPassword) {
                    showToast('Passwords do not match');
                    document.getElementById('confirm_password').style.borderColor = '#d32f2f';
                    return false;
                }
                
                // Run a final verification on all required fields
                const requiredFields = [
                    'surname', 'first_name', 'sex', 'civil_status', 'birthdate', 
                    'age', 'nationality', 'occupation', 'mobile', 'email', 
                    'password', 'confirm_password'
                ];
                
                let missingFields = [];
                requiredFields.forEach(field => {
                    const fieldElement = document.getElementById(field);
                    if (fieldElement && !fieldElement.value.trim()) {
                        missingFields.push(field);
                        fieldElement.style.borderColor = '#d32f2f';
                    }
                });
                
                if (missingFields.length > 0) {
                    showToast('Please fill in all required fields before submitting');
                    return false;
                }
                
                // Ensure the permanent address is combined and set
                updateCombinedAddress();
                if (!document.getElementById('permanent_address').value) {
                    showToast('Please provide a valid address');
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