<?php
// Start the session to maintain state
session_start();
require_once '../../config/database.php';

// Set default values for address fields
$defaultBarangay = 'Botong';
$defaultTown = 'Oton';
$defaultProvince = 'Iloilo';
$defaultZipCode = '5020';

// Store the referrer URL to use it for the return button
$referrer = '';

// Check HTTP_REFERER first
if (isset($_SERVER['HTTP_REFERER'])) {
    $referrer = $_SERVER['HTTP_REFERER'];
}

// If no referrer or it's not from a dashboard, check for a passed parameter
if (!$referrer || !stripos($referrer, 'dashboard')) {
    if (isset($_GET['source'])) {
        $referrer = $_GET['source'];
    }
}

// Default fallback
if (!$referrer) {
    $referrer = '../../templates/dashboard.php';
}

// Store the referrer in a session variable to maintain it across form submissions
if (!isset($_SESSION['donor_form_referrer'])) {
    $_SESSION['donor_form_referrer'] = $referrer;
    error_log("Setting new donor_form_referrer in session: " . $referrer);
} else {
    // Use the stored referrer if available
    $referrer = $_SESSION['donor_form_referrer'];
    error_log("Using existing donor_form_referrer from session: " . $referrer);
}

// Include required files for form autofill functionality
require_once '../../includes/functions.php';

// Get donor information for autofill
$user = $_SESSION['user'] ?? null;
$donor_details = $_SESSION['donor_details'] ?? null;

// If donor details are not in session but user is logged in, try to fetch them
if (!$donor_details && $user) {
    // Get donor details from donors_detail table to match logged-in user
    $donor_data = get_record('donors_detail', $user['id']);
    if ($donor_data['success'] && !empty($donor_data['data'])) {
        $_SESSION['donor_details'] = $donor_data['data'][0];
        $donor_details = $_SESSION['donor_details'];
    }
}

// Clean up abandoned donor form data (older than 30 minutes)
if (isset($_SESSION['donor_form_data']) && isset($_SESSION['donor_form_timestamp'])) {
    $currentTime = time();
    $formSubmitTime = $_SESSION['donor_form_timestamp'];
    $timeDifference = $currentTime - $formSubmitTime;
    
    // If form data is older than 30 minutes, remove it
    if ($timeDifference > 1800) { // 1800 seconds = 30 minutes
        error_log("Removing stale donor form data (older than 30 minutes)");
        unset($_SESSION['donor_form_data']);
        unset($_SESSION['donor_form_timestamp']);
    }
}

// Extract address parts for form autofill - keep street empty by default
$addressParts = [
    'street' => '', // Street should be empty by default
    'barangay' => $defaultBarangay,
    'town_municipality' => $defaultTown,
    'province_city' => $defaultProvince
];

// Parse permanent_address if available, but prefer default values
if ($donor_details && !empty($donor_details['permanent_address'])) {
    $address = $donor_details['permanent_address'];
    
    // Attempt to split address by commas
    $parts = explode(',', $address);
    $partsCount = count($parts);
    
    // Parse address parts but don't set street, to keep it empty
    if ($partsCount >= 4) {
        // Skip setting street field to keep it empty
        // $addressParts['street'] = trim($parts[0]);
        $addressParts['barangay'] = trim($parts[1]);
        $addressParts['town_municipality'] = trim($parts[2]);
        $addressParts['province_city'] = trim($parts[3]);
    } elseif ($partsCount == 3) {
        $addressParts['barangay'] = trim($parts[0]);
        $addressParts['town_municipality'] = trim($parts[1]);
        $addressParts['province_city'] = trim($parts[2]);
    } elseif ($partsCount == 2) {
        $addressParts['town_municipality'] = trim($parts[0]);
        $addressParts['province_city'] = trim($parts[1]);
    } elseif ($partsCount == 1) {
        $addressParts['province_city'] = trim($parts[0]);
    }
}

// Ensure default values take precedence if empty or overridden
if (empty($addressParts['barangay']) || $addressParts['barangay'] != $defaultBarangay) {
    $addressParts['barangay'] = $defaultBarangay;
}

if (empty($addressParts['town_municipality']) || $addressParts['town_municipality'] != $defaultTown) {
    $addressParts['town_municipality'] = $defaultTown;
}

if (empty($addressParts['province_city']) || $addressParts['province_city'] != $defaultProvince) {
    $addressParts['province_city'] = $defaultProvince;
}

// Generate a unique PRC donor number (format: PRC-YYYY-XXXXX)
function generateDonorNumber() {
    $year = date('Y');
    $randomNumber = mt_rand(10000, 99999); // 5-digit random number
    return "PRC-$year-$randomNumber";
}

// Generate a unique DOH NNBNETS barcode (format: DOH-YYYYXXXX)
function generateNNBNetBarcode() {
    $year = date('Y');
    $randomNumber = mt_rand(1000, 9999); // 4-digit random number
    return "DOH-$year$randomNumber";
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../templates/login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_donor_form'])) {
    // Process form data
    // Combine address fields into a single permanent_address field
    $permanent_address = "";
    
    // Use default values if fields are empty
    $street = !empty($_POST['street']) ? $_POST['street'] : '';
    $barangay = !empty($_POST['barangay']) ? $_POST['barangay'] : $defaultBarangay;
    $town = !empty($_POST['town_municipality']) ? $_POST['town_municipality'] : $defaultTown;
    $province = !empty($_POST['province_city']) ? $_POST['province_city'] : $defaultProvince;
    
    // Build permanent address
    if (!empty($street)) {
        $permanent_address .= $street;
    }
    // Always include barangay, town, and province, even if street is empty
    if (!empty($barangay)) {
        $permanent_address .= ($permanent_address ? ", " : "") . $barangay;
    }
    if (!empty($town)) {
        $permanent_address .= ($permanent_address ? ", " : "") . $town;
    }
    if (!empty($province)) {
        $permanent_address .= ($permanent_address ? ", " : "") . $province;
    }
    
    // Log user session information for debugging
    error_log("User session data: " . json_encode($_SESSION['user'] ?? 'Not set'));
    
    // Ensure user ID is correct type
    $userId = 0;
    if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        $userId = $_SESSION['user']['id'];
        // Check if user ID is numeric and convert to integer if needed
        if (is_numeric($userId)) {
            $userId = intval($userId);
        }
        error_log("Using user ID: " . $userId);
    } else {
        error_log("No user ID found in session, using default");
    }
    
    // Prepare minimal data for Supabase to reduce chance of errors
    $formData = [
        'surname' => $_POST['surname'] ?? '',
        'first_name' => $_POST['first_name'] ?? '',
        'sex' => $_POST['sex'] ?? '',
        'permanent_address' => $permanent_address,
        'prc_donor_number' => generateDonorNumber(),
        'doh_nnbnets_barcode' => generateNNBNetBarcode(),
        'registration_channel' => 'Mobile'
    ];
    
    // Add optional fields only if they have values
    if (!empty($_POST['middle_name'])) {
        $formData['middle_name'] = $_POST['middle_name'];
    }
    
    if (!empty($_POST['birthdate'])) {
        $formData['birthdate'] = $_POST['birthdate'];
    }
    
    if (!empty($_POST['age'])) {
        $formData['age'] = intval($_POST['age']);
    }
    
    if (!empty($_POST['civil_status'])) {
        $formData['civil_status'] = $_POST['civil_status'];
    }
    
    // Remove any empty or null values to prevent database errors
    foreach ($formData as $key => $value) {
        if ($value === null || $value === '') {
            unset($formData[$key]);
        }
    }
    
    // Log the data being processed
    error_log("Processing donor form data: " . json_encode($formData));
    
    try {
        // Insert donor data directly into the database
        error_log("Attempting to insert donor data directly into database");
        
        $ch = curl_init(SUPABASE_URL . '/rest/v1/donor_form');
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($formData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . SUPABASE_API_KEY,
            'Authorization: Bearer ' . SUPABASE_API_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        // Log full response for debugging
        error_log("Donor insert response: HTTP " . $http_code);
        error_log("Response data: " . $response);
        
        if ($curl_error) {
            error_log("CURL Error: " . $curl_error);
            throw new Exception("Error connecting to database: " . $curl_error);
        }
        
        if ($http_code >= 200 && $http_code < 300) {
            $insertedData = json_decode($response, true);
            
            if (is_array($insertedData) && !empty($insertedData)) {
                // Store the donor ID in session for the next step
                $_SESSION['donor_id'] = $insertedData[0]['donor_id'];
                
                error_log("Donor inserted successfully with ID: " . $_SESSION['donor_id']);
                
                // Show loading modal before redirecting to medical history form
                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        showLoadingModal();
                        setTimeout(function() {
                            window.location.href = "medical-history-modal.php";
                        }, 1500);
                    });
                </script>';
            } else {
                throw new Exception("Failed to parse inserted donor data");
            }
        } else {
            // Extract error message from response if possible
            $errorMessage = "Failed to insert donor data. HTTP Code: " . $http_code;
            $responseData = json_decode($response, true);
            
            if ($responseData && isset($responseData['message'])) {
                $errorMessage .= " - " . $responseData['message'];
            } elseif ($responseData && isset($responseData['error'])) {
                $errorMessage .= " - " . $responseData['error'];
            }
            
            throw new Exception($errorMessage);
        }
    } catch (Exception $e) {
        error_log("Exception handling donor form: " . $e->getMessage());
        // Add user-facing error message
        echo '<div class="alert alert-danger mt-3" role="alert">
            Error saving donor information: ' . htmlspecialchars($e->getMessage()) . '
        </div>';
        
        // Do not redirect or exit here to allow the form to be displayed again with the error message
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donor Form</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #a00000;
            --primary-dark: #800000;
            --light-gray: #f8f9fa;
            --border-color: #dee2e6;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--light-gray);
            margin: 0;
            padding: 0;
        }

        .modal-dialog {
            max-width: 800px;
            margin: 30px auto;
        }

        .modal-content {
            border-radius: 8px;
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 20px;
            text-align: center;
            display: block;
            position: relative;
        }

        .modal-title {
            color: var(--primary);
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            text-align: center;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            text-decoration: none;
        }

        .steps-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px 0;
        }

        .step-item {
            display: flex;
            align-items: center;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            z-index: 1;
            position: relative;
        }

        .step-number.active {
            background-color: var(--primary);
            color: white;
        }

        .step-number.inactive {
            background-color: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .step-number.completed {
            background-color: var(--primary);
            color: white;
        }

        .step-line {
            width: 80px;
            height: 2px;
            background-color: var(--border-color);
            position: relative;
            top: 0;
        }

        .step-line.active {
            background-color: var(--primary);
        }

        .section-title {
            color: var(--primary);
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .section-details {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .form-section {
            display: none;
        }
        
        .form-section.active {
            display: block;
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 10px;
            height: auto;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(160, 0, 0, 0.25);
            border-color: var(--primary);
        }

        .form-select {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 10px;
            height: auto;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }
        
        .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(160, 0, 0, 0.25);
            border-color: var(--primary);
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            padding-top: 20px;
            margin-top: 30px;
            border-top: 1px solid var(--border-color);
        }

        .btn-navigate {
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .horizontal-line {
            height: 1px;
            background-color: var(--border-color);
            margin: 15px 0;
        }
        
        .completion-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1100;
            justify-content: center;
            align-items: center;
        }
        
        .completion-modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .completion-modal-title {
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .completion-modal-button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="modal show d-block" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Personal Data</h5>
                <a href="javascript:void(0)" class="modal-close" id="closeButton">&times;</a>
            </div>
            
            <div class="modal-body">
                <!-- Error message display if any -->
                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                    ?>
                </div>
                <?php endif; ?>
                
                <!-- Debug information - hidden in production -->
                <?php if (isset($_GET['debug'])): ?>
                <div class="alert alert-info small">
                    <strong>Debug Info:</strong> Return URL: <?php echo htmlspecialchars($referrer); ?>
                </div>
                <?php endif; ?>
                
                <!-- Progress Steps -->
                <div class="steps-container">
                    <div class="step-item">
                        <div class="step-number active" id="step1">1</div>
                    </div>
                    <div class="step-line" id="line1-2"></div>
                    <div class="step-item">
                        <div class="step-number inactive" id="step2">2</div>
                    </div>
                    <div class="step-line" id="line2-3"></div>
                    <div class="step-item">
                        <div class="step-number inactive" id="step3">3</div>
                    </div>
                    <div class="step-line" id="line3-4"></div>
                    <div class="step-item">
                        <div class="step-number inactive" id="step4">4</div>
                    </div>
                    <div class="step-line" id="line4-5"></div>
                    <div class="step-item">
                        <div class="step-number inactive" id="step5">5</div>
                    </div>
                </div>
                
                <!-- Form Starts -->
                <form id="donorForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return handleFormSubmit()" onkeydown="return preventEnterSubmit(event)" data-allow-enter="false">
                    <!-- Section 1: NAME -->
                    <div class="form-section active" id="section1">
                        <h3 class="section-title">NAME</h3>
                        <p class="section-details">Complete the details below.</p>
                        <div class="horizontal-line"></div>
                        
                        <div class="mb-3">
                            <label for="surname" class="form-label">Surname</label>
                            <input type="text" class="form-control" id="surname" name="surname" required value="<?php echo $donor_details['surname'] ?? ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required value="<?php echo $donor_details['first_name'] ?? ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo $donor_details['middle_name'] ?? ''; ?>">
                        </div>
                        
                        <div class="navigation-buttons">
                            <div></div> <!-- Placeholder for alignment -->
                            <button type="button" class="btn btn-primary btn-navigate" onclick="nextSection(1)">Next &gt;</button>
                        </div>
                    </div>
                    
                    <!-- Section 2: PROFILE DETAILS -->
                    <div class="form-section" id="section2">
                        <h3 class="section-title">PROFILE DETAILS</h3>
                        <p class="section-details">Complete the details below.</p>
                        <div class="horizontal-line"></div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="birthdate" class="form-label">Birthdate</label>
                                <input type="date" class="form-control" id="birthdate" name="birthdate" onchange="calculateAge()" required value="<?php echo $donor_details['birthdate'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="age" class="form-label">Age</label>
                                <input type="number" class="form-control" id="age" name="age" readonly value="<?php echo $donor_details['age'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="civil_status" class="form-label">Civil Status</label>
                            <select class="form-select" id="civil_status" name="civil_status" required>
                                <option value="" disabled selected>Select Civil Status</option>
                                <option value="Single" <?php echo $donor_details['civil_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                <option value="Married" <?php echo $donor_details['civil_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                                <option value="Divorced" <?php echo $donor_details['civil_status'] == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                <option value="Widowed" <?php echo $donor_details['civil_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sex" class="form-label">Sex</label>
                            <select class="form-select" id="sex" name="sex" required>
                                <option value="" disabled selected>Select Sex</option>
                                <option value="Male" <?php echo $donor_details['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $donor_details['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Others" <?php echo $donor_details['sex'] == 'Others' ? 'selected' : ''; ?>>Others</option>
                            </select>
                        </div>
                        
                        <div class="navigation-buttons">
                            <button type="button" class="btn btn-secondary btn-navigate" onclick="prevSection(2)">&lt; Previous</button>
                            <button type="button" class="btn btn-primary btn-navigate" onclick="nextSection(2)">Next &gt;</button>
                        </div>
                    </div>
                    
                    <!-- Section 3: PERMANENT ADDRESS -->
                    <div class="form-section" id="section3">
                        <h3 class="section-title">PERMANENT ADDRESS</h3>
                        <p class="section-details">Complete the details below.</p>
                        <div class="horizontal-line"></div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="address_no" class="form-label">No.</label>
                                <input type="text" class="form-control" id="address_no" name="address_no" value="<?php echo $donor_details['address_no'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="zip_code" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo $donor_details['zip_code'] ?? $defaultZipCode; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="street" class="form-label">Street</label>
                            <input type="text" class="form-control" id="street" name="street" value="" placeholder="e.g. Main St.">
                        </div>
                        
                        <div class="mb-3">
                            <label for="barangay" class="form-label">Barangay</label>
                            <input type="text" class="form-control" id="barangay" name="barangay" required value="<?php echo $addressParts['barangay']; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="town_municipality" class="form-label">Town/Municipality</label>
                            <input type="text" class="form-control" id="town_municipality" name="town_municipality" required value="<?php echo $addressParts['town_municipality']; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="province_city" class="form-label">Province/City</label>
                            <input type="text" class="form-control" id="province_city" name="province_city" required value="<?php echo $addressParts['province_city']; ?>">
                        </div>
                        
                        <div class="navigation-buttons">
                            <button type="button" class="btn btn-secondary btn-navigate" onclick="prevSection(3)">&lt; Previous</button>
                            <button type="button" class="btn btn-primary btn-navigate" onclick="nextSection(3)">Next &gt;</button>
                        </div>
                    </div>
                    
                    <!-- Section 4: ADDITIONAL INFORMATION -->
                    <div class="form-section" id="section4">
                        <h3 class="section-title">ADDITIONAL INFORMATION</h3>
                        <p class="section-details">Complete the details below.</p>
                        <div class="horizontal-line"></div>
                        
                        <div class="mb-3">
                            <label for="nationality" class="form-label">Nationality</label>
                            <input type="text" class="form-control" id="nationality" name="nationality" value="<?php echo $donor_details['nationality'] ?? 'Filipino'; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="religion" class="form-label">Religion</label>
                            <input type="text" class="form-control" id="religion" name="religion" value="<?php echo $donor_details['religion'] ?? ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="education" class="form-label">Education</label>
                            <select class="form-select" id="education" name="education">
                                <option value="" selected disabled>Select Education Level</option>
                                <option value="Elementary" <?php echo $donor_details['education'] == 'Elementary' ? 'selected' : ''; ?>>Elementary</option>
                                <option value="High School" <?php echo $donor_details['education'] == 'High School' ? 'selected' : ''; ?>>High School</option>
                                <option value="College" <?php echo $donor_details['education'] == 'College' ? 'selected' : ''; ?>>College</option>
                                <option value="Post Graduate" <?php echo $donor_details['education'] == 'Post Graduate' ? 'selected' : ''; ?>>Post Graduate</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="occupation" class="form-label">Occupation</label>
                            <input type="text" class="form-control" id="occupation" name="occupation" value="<?php echo $donor_details['occupation'] ?? ''; ?>">
                        </div>
                        
                        <div class="navigation-buttons">
                            <button type="button" class="btn btn-secondary btn-navigate" onclick="prevSection(4)">&lt; Previous</button>
                            <button type="button" class="btn btn-primary btn-navigate" onclick="nextSection(4)">Next &gt;</button>
                        </div>
                    </div>
                    
                    <!-- Section 5: CONTACT INFORMATION -->
                    <div class="form-section" id="section5">
                        <h3 class="section-title">CONTACT INFORMATION</h3>
                        <p class="section-details">Complete the details below.</p>
                        <div class="horizontal-line"></div>
                        
                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" id="mobile" name="mobile" value="<?php echo $donor_details['mobile'] ?? ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-muted">(optional)</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $donor_details['email'] ?? ''; ?>">
                        </div>
                        
                        <div class="navigation-buttons">
                            <button type="button" class="btn btn-secondary btn-navigate" onclick="prevSection(5)">&lt; Previous</button>
                            <button type="submit" class="btn btn-primary btn-navigate" id="submitButton" name="submit_donor_form">Submit &gt;</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: transparent; border: none; box-shadow: none;">
            <div class="modal-body text-center">
                <div class="spinner-border text-danger" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-white bg-dark p-2 rounded">Proceeding to Medical History Form...</p>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function calculateAge() {
        const birthdateInput = document.getElementById('birthdate');
        const ageInput = document.getElementById('age');
        
        if (birthdateInput.value) {
            const birthdate = new Date(birthdateInput.value);
            const today = new Date();
            
            let age = today.getFullYear() - birthdate.getFullYear();
            const monthDifference = today.getMonth() - birthdate.getMonth();
            
            if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthdate.getDate())) {
                age--;
            }
            
            ageInput.value = age;
        } else {
            ageInput.value = '';
        }
    }
    
    // Function to update the form's data-allow-enter attribute based on the current section
    function updateEnterKeyBehavior(sectionNumber) {
        const form = document.getElementById('donorForm');
        if (form) {
            // Allow Enter key submission only on the last section
            form.setAttribute('data-allow-enter', sectionNumber === 5 ? 'true' : 'false');
        }
    }
    
    function nextSection(currentSection) {
        // Basic validation for required fields in current section
        const section = document.getElementById(`section${currentSection}`);
        const requiredFields = section.querySelectorAll('[required]');
        let valid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                valid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!valid) {
            alert('Please fill all required fields');
            return;
        }
        
        // Special validation for section 3 (permanent address)
        if (currentSection === 3) {
            const barangay = document.getElementById('barangay').value.trim();
            const town = document.getElementById('town_municipality').value.trim();
            const province = document.getElementById('province_city').value.trim();
            
            if (!barangay || !town || !province) {
                if (!barangay) document.getElementById('barangay').classList.add('is-invalid');
                if (!town) document.getElementById('town_municipality').classList.add('is-invalid');
                if (!province) document.getElementById('province_city').classList.add('is-invalid');
                alert('Please fill all required address fields');
                return;
            }
        }
        
        // Hide current section and show next section
        document.getElementById(`section${currentSection}`).classList.remove('active');
        document.getElementById(`section${currentSection + 1}`).classList.add('active');
        
        // Mark current step as completed
        const currentStepEl = document.getElementById(`step${currentSection}`);
        currentStepEl.classList.remove('active');
        currentStepEl.classList.add('completed');
        
        // Then make the next step active
        const nextStepEl = document.getElementById(`step${currentSection + 1}`);
        nextStepEl.classList.remove('inactive');
        nextStepEl.classList.add('active');
        
        // Activate the connecting line
        const lineEl = document.getElementById(`line${currentSection}-${currentSection + 1}`);
        lineEl.classList.add('active');
        
        // Update the modal title
        updateModalTitle(currentSection + 1);
        
        // Update Enter key behavior
        updateEnterKeyBehavior(currentSection + 1);
    }
    
    function prevSection(currentSection) {
        // Hide current section and show previous section
        document.getElementById(`section${currentSection}`).classList.remove('active');
        document.getElementById(`section${currentSection - 1}`).classList.add('active');
        
        // First make the current step inactive
        const currentStepEl = document.getElementById(`step${currentSection}`);
        currentStepEl.classList.remove('active');
        currentStepEl.classList.add('inactive');
        
        // Then make the previous step active (it should already be completed)
        const prevStepEl = document.getElementById(`step${currentSection - 1}`);
        prevStepEl.classList.remove('completed');
        prevStepEl.classList.add('active');
        
        // Deactivate the connecting line
        const lineEl = document.getElementById(`line${currentSection - 1}-${currentSection}`);
        lineEl.classList.remove('active');
        
        // Update the modal title
        updateModalTitle(currentSection - 1);
        
        // Update Enter key behavior
        updateEnterKeyBehavior(currentSection - 1);
    }
    
    function updateModalTitle(sectionNumber) {
        const titleEl = document.querySelector('.modal-title');
        
        // Update modal title based on the section
        switch(sectionNumber) {
            case 1:
                titleEl.textContent = 'Personal Data';
                break;
            case 2:
                titleEl.textContent = 'Personal Data';
                break;
            case 3:
                titleEl.textContent = 'Personal Data';
                break;
            case 4:
                titleEl.textContent = 'Personal Data';
                break;
            case 5:
                titleEl.textContent = 'Personal Data';
                break;
            default:
                titleEl.textContent = 'Personal Data';
        }
    }
    
    function showLoadingModal() {
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
    }
    
    // Handle form submission
    function handleFormSubmit() {
        // Perform any client-side validation here
        
        // Store the current URL to maintain form state and 
        // set a flag indicating form has been submitted
        if (typeof(Storage) !== "undefined") {
            localStorage.setItem("donorFormReferrer", "<?php echo $referrer; ?>");
            localStorage.setItem("donorFormSubmitted", "true");
        }
        
        return true; // Allow the form to submit
    }
    
    // Prevent form submission when Enter key is pressed
    function preventEnterSubmit(event) {
        // Only prevent if Enter key is pressed
        if (event.key !== 'Enter') {
            return true;
        }
        
        // Get form and check if Enter submission is allowed
        const form = document.getElementById('donorForm');
        const allowEnter = form && form.getAttribute('data-allow-enter') === 'true';
        
        // Get the current active section
        const activeSection = document.querySelector('.form-section.active');
        const isLastSection = activeSection && activeSection.id === 'section5';
        
        // If on the last section and form allows enter, or if the target is the submit button, allow submission
        if ((isLastSection && allowEnter) || (event.target && event.target.id === 'submitButton')) {
            return true;
        }
        
        // Prevent form submission on Enter key press in all other cases
        event.preventDefault();
        
        // If focus is on an input, move to the next input
        if (event.target && (event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT')) {
            // Find the next input to focus
            const inputs = Array.from(form.elements);
            const currentIndex = inputs.indexOf(event.target);
            
            if (currentIndex > -1 && currentIndex < inputs.length - 1) {
                const nextInput = inputs[currentIndex + 1];
                if (nextInput) {
                    nextInput.focus();
                }
            }
            
            // If this is the last input in a section other than section5, click the Next button
            if (activeSection && !isLastSection) {
                const isLastInputInSection = Array.from(activeSection.querySelectorAll('input, select')).pop() === event.target;
                if (isLastInputInSection) {
                    const nextButton = activeSection.querySelector('.btn-primary.btn-navigate');
                    if (nextButton) {
                        nextButton.click();
                    }
                }
            }
        }
        
        return false;
    }
    
    // Function to go back to previous page
    function goBackToDashboard(skipConfirmation = false) {
        // First check if the user has entered data
        if (!skipConfirmation) {
            const formInputs = document.querySelectorAll('input[type="text"], input[type="date"], input[type="email"], select');
            let hasData = false;
            
            formInputs.forEach(input => {
                if ((input.type === 'text' || input.type === 'date' || input.type === 'email') && input.value.trim() !== '') {
                    hasData = true;
                } else if (input.tagName === 'SELECT' && input.selectedIndex > 0) {
                    hasData = true;
                }
            });
            
            if (hasData) {
                const confirmLeave = confirm('Are you sure you want to cancel this donation? All data will be discarded.');
                if (!confirmLeave) {
                    return false;
                }
                
                // User confirmed cancellation - call the cancel_registration.php API
                fetch('cancel_registration.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'cancel_from_donor_form' })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Cancellation successful:', data);
                    redirectToDashboard();
                })
                .catch(error => {
                    console.error('Error canceling donation:', error);
                    redirectToDashboard(); // Redirect anyway even if the API call fails
                });
                
                return; // Exit function to prevent immediate redirect
            }
        }
        
        // If no data entered or skipConfirmation is true, just redirect
        redirectToDashboard();
    }
    
    // Function to handle the actual redirect logic
    function redirectToDashboard() {
        // Determine the dashboard URL based on user role
        const userRole = "<?php echo isset($_SESSION['user_staff_roles']) ? strtolower($_SESSION['user_staff_roles']) : ''; ?>";
        console.log("User role for redirect:", userRole);
        
        let dashboardUrl = "";
        
        // Redirect based on user role
        switch (userRole) {
            case 'reviewer':
                dashboardUrl = "../../public/Dashboards/dashboard-staff-medical-history-submissions.php";
                break;
            case 'interviewer':
                dashboardUrl = "../../public/Dashboards/dashboard-staff-donor-submission.php";
                break;
            case 'physician':
                dashboardUrl = "../../public/Dashboards/dashboard-staff-physical-submission.php";
                break;
            case 'phlebotomist':
                dashboardUrl = "../../public/Dashboards/dashboard-staff-blood-collection-submission.php";
                break;
            default:
                // Check if we have a stored referrer as fallback
                if (typeof(Storage) !== "undefined" && localStorage.getItem("donorFormReferrer")) {
                    dashboardUrl = localStorage.getItem("donorFormReferrer");
                } else {
                    // Default fallback if no specific role or referrer
                    dashboardUrl = "<?php echo $referrer; ?>";
                }
                break;
        }
        
        console.log("Redirecting to:", dashboardUrl);
        window.location.href = dashboardUrl;
    }
    
    // Function to highlight sections with pre-filled data
    function highlightPrefilledSections() {
        const sections = document.querySelectorAll('.form-section');
        
        sections.forEach(section => {
            // Check if this section has any filled required fields
            const requiredFields = section.querySelectorAll('[required]');
            let hasPrefilledData = false;
            
            requiredFields.forEach(field => {
                if (field.value && field.value.trim() !== '') {
                    hasPrefilledData = true;
                }
            });
            
            if (hasPrefilledData) {
                // Add a visual indicator that this section has pre-filled data
                const sectionTitle = section.querySelector('.section-title');
                if (sectionTitle) {
                    const indicator = document.createElement('span');
                    indicator.textContent = ' (Auto-filled)';
                    indicator.style.fontSize = '14px';
                    indicator.style.fontWeight = 'normal';
                    indicator.style.color = '#28a745';
                    sectionTitle.appendChild(indicator);
                }
            }
        });
    }
    
    // Auto-calculate age on page load if birthdate already exists
    document.addEventListener('DOMContentLoaded', function() {
        const birthdateInput = document.getElementById('birthdate');
        if (birthdateInput.value) {
            calculateAge();
        }
        
        // Highlight the form sections that contain pre-filled information
        highlightPrefilledSections();
        
        // Initialize step indicators
        document.getElementById('step1').classList.add('active');
        
        // Start with only the first section active
        const sections = document.querySelectorAll('.form-section');
        sections.forEach((section, index) => {
            if (index === 0) {
                section.classList.add('active');
            } else {
                section.classList.remove('active');
            }
        });
        
        // Initialize Enter key behavior - disable on first section
        updateEnterKeyBehavior(1);
        
        // Ensure the submit button triggers the handleFormSubmit function
        const submitButton = document.getElementById('submitButton');
        if (submitButton) {
            submitButton.addEventListener('click', function(e) {
                // Allow regular form submission only from the final section
                const activeSection = document.querySelector('.form-section.active');
                if (activeSection && activeSection.id !== 'section5') {
                    e.preventDefault();
                    return false;
                }
                
                // Final validation of all required fields
                const form = document.getElementById('donorForm');
                const requiredFields = form.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        valid = false;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill all required fields before submitting');
                    return false;
                }
                
                return handleFormSubmit();
            });
        }
        
        // Ensure close button works but doesn't have duplicate event listeners
        const closeButton = document.getElementById('closeButton');
        if (closeButton) {
            // Remove existing listeners (to prevent duplicates)
            const newCloseBtn = closeButton.cloneNode(true);
            closeButton.parentNode.replaceChild(newCloseBtn, closeButton);
            
            // Add a single event listener for the cancellation logic
            newCloseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Show confirmation dialog
                if (confirm('Are you sure you want to cancel this donation? All data will be discarded.')) {
                    console.log("User confirmed cancellation, redirecting to:", "<?php echo $referrer; ?>");
                    
                    // Call the cancellation API to clean up session data
                    fetch('cancel_registration.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ action: 'cancel_from_donor_form' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Cancellation successful:', data);
                        // Direct redirect to the referrer URL
                        window.location.href = "<?php echo $referrer; ?>";
                    })
                    .catch(error => {
                        console.error('Error canceling donation:', error);
                        // Redirect anyway even if the API call fails
                        window.location.href = "<?php echo $referrer; ?>";
                    });
                }
                return false;
            });
        }
        
        // Clear any existing click handlers for other modal-close buttons
        document.querySelectorAll('.modal-close:not(#closeButton)').forEach(button => {
            // Clone and replace to remove existing listeners
            const newBtn = button.cloneNode(true);
            button.parentNode.replaceChild(newBtn, button);
            
            // Add a single event listener
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to cancel this donation? All data will be discarded.')) {
                    console.log("Close button clicked, redirecting to:", "<?php echo $referrer; ?>");
                    
                    // Call the cancellation API to clean up session data
                    fetch('cancel_registration.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ action: 'cancel_from_donor_form' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Cancellation successful:', data);
                        // Direct redirect to the referrer URL
                        window.location.href = "<?php echo $referrer; ?>";
                    })
                    .catch(error => {
                        console.error('Error canceling donation:', error);
                        // Redirect anyway even if the API call fails
                        window.location.href = "<?php echo $referrer; ?>";
                    });
                }
                return false;
            });
        });
        
        // Add ESC key listener to close the form
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Show confirmation dialog before canceling
                if (confirm('Are you sure you want to cancel this donation? All data will be discarded.')) {
                    console.log("ESC key pressed, redirecting to:", "<?php echo $referrer; ?>");
                    
                    // Call the cancellation API to clean up session data
                    fetch('cancel_registration.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ action: 'cancel_from_donor_form' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Cancellation successful:', data);
                        // Direct redirect to the referrer URL
                        window.location.href = "<?php echo $referrer; ?>";
                    })
                    .catch(error => {
                        console.error('Error canceling donation:', error);
                        // Redirect anyway even if the API call fails
                        window.location.href = "<?php echo $referrer; ?>";
                    });
                }
            }
        });
        
        // If this is a page reload after form submission, check localStorage for referrer
        if (window.location.href.includes('donor-form-modal.php') && typeof(Storage) !== "undefined") {
            const storedReferrer = localStorage.getItem("donorFormReferrer");
            if (storedReferrer) {
                // We have a stored referrer from a previous form submission
                console.log("Using stored referrer:", storedReferrer);
            }
        }
    });
</script>
</body>
</html> 