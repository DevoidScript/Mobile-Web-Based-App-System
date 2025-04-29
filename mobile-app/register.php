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
 */

// Include configuration files
require_once 'config/database.php';
require_once 'includes/functions.php';

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
        /* Base styles */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f2f2f2;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .form-container {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            color: #d32f2f;
        }
        
        .label {
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
            color: #d32f2f;
        }
        
        .input {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0 15px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        
        .select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0 15px;
            border: 1px solid #ccc;
            background-color: white;
            box-sizing: border-box;
        }
        
        .submit-button {
            background-color: #d32f2f;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            width: 100%;
            margin-top: 20px;
        }
        
        .submit-button:hover {
            background-color: #b71c1c;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #ef9a9a;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #a5d6a7;
        }
        
        .required::after {
            content: ' *';
            color: #d32f2f;
        }
        
        /* Progress indicators */
        .progress-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .progress-container::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #ddd;
            z-index: 1;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }
        
        .step.active {
            background-color: #d32f2f;
            color: white;
        }
        
        .step.completed {
            background-color: #4caf50;
            color: white;
        }
        
        /* Form steps */
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
        }
        
        /* Navigation buttons */
        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .nav-button {
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .prev-button {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }
        
        .next-button {
            background-color: #d32f2f;
            color: white;
            border: none;
        }
        
        .next-button:hover {
            background-color: #b71c1c;
        }
        
        /* Add styles for loading overlay */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #d32f2f;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            margin: 0 auto 15px;
            animation: spin 2s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            <!-- Progress indicators -->
            <div class="progress-container">
                <div class="step active" data-step="1">1</div>
                <div class="step" data-step="2">2</div>
                <div class="step" data-step="3">3</div>
                <div class="step" data-step="4">4</div>
                <div class="step" data-step="5">5</div>
            </div>
            
            <form method="POST" action="register.php" id="registrationForm">
                <input type="hidden" name="step" id="step" value="1">
                
                <!-- Step 1: Personal Information -->
                <div class="form-step active" data-step="1">
                    <div class="section-title">PERSONAL INFORMATION</div>
                    
                    <label class="label required" for="surname">Surname:</label>
                    <input type="text" id="surname" name="surname" class="input" value="<?php echo $_POST['surname'] ?? ''; ?>" required>
                    
                    <label class="label required" for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" class="input" value="<?php echo $_POST['first_name'] ?? ''; ?>" required>
                    
                    <label class="label" for="middle_name">Middle Name:</label>
                    <input type="text" id="middle_name" name="middle_name" class="input" value="<?php echo $_POST['middle_name'] ?? ''; ?>">
                    
                    <div class="form-navigation">
                        <div></div> <!-- Empty div for spacing -->
                        <button type="button" class="nav-button next-button" data-next="2">Next</button>
                    </div>
                </div>
                
                <!-- Step 2: Birth Information -->
                <div class="form-step" data-step="2">
                    <div class="section-title">BIRTH INFORMATION</div>
                    
                    <label class="label required" for="birthdate">Birthdate:</label>
                    <input type="date" id="birthdate" name="birthdate" class="input" value="<?php echo $_POST['birthdate'] ?? ''; ?>" required>
                    
                    <label class="label required" for="age">Age:</label>
                    <input type="number" id="age" name="age" class="input" value="<?php echo $_POST['age'] ?? ''; ?>" required>
                    
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
                        <button type="button" class="nav-button prev-button" data-prev="1">Previous</button>
                        <button type="button" class="nav-button next-button" data-next="3">Next</button>
                    </div>
                </div>
                
                <!-- Step 3: Address Information -->
                <div class="form-step" data-step="3">
                    <div class="section-title">ADDRESS INFORMATION</div>
                    
                    <label class="label required" for="permanent_address">Permanent Address:</label>
                    <input type="text" id="permanent_address" name="permanent_address" class="input" value="<?php echo $_POST['permanent_address'] ?? ''; ?>" required>
                    
                    <label class="label required" for="mobile">Mobile Number:</label>
                    <input type="text" id="mobile" name="mobile" class="input" value="<?php echo $_POST['mobile'] ?? ''; ?>" required>
                    
                    <div class="form-navigation">
                        <button type="button" class="nav-button prev-button" data-prev="2">Previous</button>
                        <button type="button" class="nav-button next-button" data-next="4">Next</button>
                    </div>
                </div>
                
                <!-- Step 4: Additional Information -->
                <div class="form-step" data-step="4">
                    <div class="section-title">ADDITIONAL INFORMATION</div>
                    
                    <label class="label required" for="nationality">Nationality:</label>
                    <input type="text" id="nationality" name="nationality" class="input" value="<?php echo $_POST['nationality'] ?? ''; ?>" required>
                    
                    <label class="label" for="religion">Religion:</label>
                    <input type="text" id="religion" name="religion" class="input" value="<?php echo $_POST['religion'] ?? ''; ?>">
                    
                    <label class="label" for="education">Education:</label>
                    <input type="text" id="education" name="education" class="input" value="<?php echo $_POST['education'] ?? ''; ?>">
                    
                    <label class="label required" for="occupation">Occupation:</label>
                    <input type="text" id="occupation" name="occupation" class="input" value="<?php echo $_POST['occupation'] ?? ''; ?>" required>
                    
                    <div class="form-navigation">
                        <button type="button" class="nav-button prev-button" data-prev="3">Previous</button>
                        <button type="button" class="nav-button next-button" data-next="5">Next</button>
                    </div>
                </div>
                
                <!-- Step 5: Account Information -->
                <div class="form-step" data-step="5">
                    <div class="section-title">ACCOUNT INFORMATION</div>
                    
                    <label class="label required" for="email">Email Address:</label>
                    <input type="email" id="email" name="email" class="input" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                    
                    <label class="label required" for="password">Password:</label>
                    <input type="password" id="password" name="password" class="input" required>
                    
                    <label class="label required" for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="input" required>
                    
                    <div class="form-navigation">
                        <button type="button" class="nav-button prev-button" data-prev="4">Previous</button>
                        <button type="submit" class="submit-button">Register</button>
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
            
            // Function to navigate between steps
            function navigateToStep(stepNumber) {
                // Update hidden step input
                stepInput.value = stepNumber;
                
                // Update step indicators
                steps.forEach(step => {
                    const stepNum = parseInt(step.dataset.step);
                    if (stepNum === stepNumber) {
                        step.classList.add('active');
                    } else if (stepNum < stepNumber) {
                        step.classList.remove('active');
                        step.classList.add('completed');
                    } else {
                        step.classList.remove('active', 'completed');
                    }
                });
                
                // Show/hide form steps
                formSteps.forEach(formStep => {
                    if (parseInt(formStep.dataset.step) === stepNumber) {
                        formStep.classList.add('active');
                    } else {
                        formStep.classList.remove('active');
                    }
                });
            }
            
            // Function to validate current step
            function validateStep(stepNumber) {
                const currentStep = document.querySelector(`.form-step[data-step="${stepNumber}"]`);
                const requiredFields = currentStep.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = '#d32f2f';
                        isValid = false;
                    } else {
                        field.style.borderColor = '#ccc';
                    }
                });
                
                if (!isValid) {
                    alert('Please fill in all required fields before proceeding.');
                }
                
                return isValid;
            }
            
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
            
            // Form submission handler
            document.getElementById('registrationForm').addEventListener('submit', function(e) {
                const currentStepNum = parseInt(stepInput.value);
                if (!validateStep(currentStepNum)) {
                    e.preventDefault();
                    return;
                }
                
                // Password matching validation
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    alert('Passwords do not match!');
                    e.preventDefault();
                    return;
                }
                
                // Handle form submission via AJAX instead of traditional form submission
                e.preventDefault();
                
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
                formContainer.appendChild(loadingOverlay);
                
                // Get form data
                const formData = new FormData(this);
                
                // Send data to the API endpoint
                fetch('api/donor_register.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Remove loading overlay
                    loadingOverlay.remove();
                    
                    if (data.success) {
                        // Show success message and redirect
                        alert('Registration successful! Redirecting to login page.');
                        window.location.href = data.data.redirect || 'login.php';
                    } else {
                        // Show error message
                        alert('Registration failed: ' + data.message);
                        
                        // Add error message to the page
                        const errorContainer = document.createElement('div');
                        errorContainer.className = 'error-message';
                        errorContainer.innerHTML = `
                            <h3>Registration Error:</h3>
                            <p>${data.message}</p>
                            <p><small>If this error persists, please contact technical support with the error details above.</small></p>
                        `;
                        
                        // Insert at the top of the form container
                        formContainer.insertBefore(errorContainer, formContainer.firstChild);
                        
                        // Scroll to the top to show the error
                        window.scrollTo(0, 0);
                    }
                })
                .catch(error => {
                    // Remove loading overlay
                    loadingOverlay.remove();
                    
                    // Show error message
                    alert('An unexpected error occurred. Please try again later.');
                    console.error('Error:', error);
                });
            });
        });
    </script>
</body>
</html> 