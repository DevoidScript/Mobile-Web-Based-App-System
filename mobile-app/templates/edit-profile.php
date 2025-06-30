<?php
/**
 * Edit Profile Page for the Red Cross Mobile App
 *
 * This page allows users to edit their personal information.
 *
 * Path: templates/edit-profile.php
 */

// Set error reporting in development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../index.php?error=Please login to edit your profile');
    exit;
}

// Get user data
$user = $_SESSION['user'] ?? null;
$donorForm = null;
$success_message = '';
$error_message = '';

if ($user) {
    $params = [];
    if (!empty($user['donor_id'])) {
        $params = [ 'id' => 'eq.' . $user['donor_id'], 'limit' => 1 ];
    } elseif (!empty($user['email'])) {
        $params = [ 'email' => 'eq.' . strtolower(trim($user['email'])), 'limit' => 1 ];
    }
    if (!empty($params)) {
        $result = get_records('donor_form', $params);
        if ($result['success'] && !empty($result['data'])) {
            $donorForm = $result['data'][0];
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $mobile = trim($_POST['mobile'] ?? '');
    $profile_picture_url = $donorForm['profile_picture'] ?? '';
    $profile_picture_uploaded = false;
    // Use cropped image if present
    if (!empty($_POST['cropped_profile_picture'])) {
        $profile_picture_url = $_POST['cropped_profile_picture'];
        $profile_picture_uploaded = true;
    } else if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileType = $_FILES['profile_picture']['type'];
        $fileContent = file_get_contents($fileTmpPath);
        $base64 = 'data:' . $fileType . ';base64,' . base64_encode($fileContent);
        $profile_picture_url = $base64;
        $profile_picture_uploaded = true;
    }

    // Validate mobile number (basic validation)
    if (!empty($mobile)) {
        $mobile_clean = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($mobile_clean) < 10) {
            $error_message = 'Mobile number must be at least 10 digits long.';
        } else {
            $updateData = [
                'mobile' => $mobile
            ];
            if ($profile_picture_uploaded) {
                $updateData['profile_picture'] = $profile_picture_url;
            }
            // Update the record
            if ($donorForm && !empty($updateData)) {
                $result = update_record('donor_form', $donorForm['donor_id'], $updateData, 'donor_id');
                if ($result['success']) {
                    $success_message = 'Profile updated successfully!';
                    // Refresh donor form data
                    $result = get_records('donor_form', ['donor_id' => 'eq.' . $donorForm['donor_id'], 'limit' => 1]);
                    if ($result['success'] && !empty($result['data'])) {
                        $donorForm = $result['data'][0];
                    }
                } else {
                    $error_message = 'Failed to update profile. Error: ' . print_r($result['data'], true);
                }
            } else {
                $error_message = 'No donor form found. Please contact support.';
            }
        }
    } else {
        $error_message = 'Mobile number cannot be empty.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF0000">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .edit-profile-header {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #FF0000;
            color: white;
        }

        .back-arrow a {
            font-size: 24px;
            color: white;
            text-decoration: none;
        }

        .header-title {
            flex-grow: 1;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-right: 30px; /* Offset for back arrow */
            color: white;
        }

        .container {
            padding: 20px;
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 10px;
            background-color: #007bff;
            background-image: url('../assets/icons/user-avatar-placeholder.png');
            background-size: cover;
            position: relative;
            overflow: hidden;
        }

        .change-picture-link {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            color: #fff;
            text-align: center;
            font-size: 14px;
            padding: 8px 0;
            cursor: pointer;
            width: 100%;
            border-bottom-left-radius: 50%;
            border-bottom-right-radius: 50%;
        }

        .profile-avatar:hover .change-picture-link,
        .profile-avatar:focus .change-picture-link {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        .email-input-wrapper {
            position: relative;
        }

        .email-input-wrapper .email-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .save-btn {
            width: 100%;
            padding: 15px;
            background-color: #D50000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .save-btn:hover {
            background-color: #B71C1C;
        }
        
        .save-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .field-disabled {
            background-color: #f5f5f5;
            color: #666;
        }

        /* Loader styles */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: none; /* Hidden by default */
            justify-content: center;
            align-items: center;
        }

        .loader {
            border: 8px solid #f3f3f3; /* Light grey */
            border-top: 8px solid #D50000; /* Red */
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="edit-profile-header">
        <div class="back-arrow">
            <a href="profile.php">&#8249;</a>
        </div>
        <div class="header-title">Edit Profile</div>
    </div>

    <div class="container">
        <form action="edit-profile.php" method="POST" id="editProfileForm" enctype="multipart/form-data">
            <div class="profile-avatar" id="profileAvatar" style="background-image: url('<?php echo !empty($donorForm['profile_picture']) ? htmlspecialchars($donorForm['profile_picture']) : '../assets/icons/red-cross-logo.png'; ?>');">
                <span class="change-picture-link" id="changePictureLink">Change Picture</span>
            </div>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display:none;">

            <?php if (!empty($success_message)): ?>
                <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($donorForm['first_name'] ?? ''); ?>" required disabled class="field-disabled">
            </div>
            <div class="form-group">
                <label for="middle_name">Middle Name</label>
                <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($donorForm['middle_name'] ?? ''); ?>" disabled class="field-disabled">
            </div>
            <div class="form-group">
                <label for="surname">Surname</label>
                <input type="text" id="surname" name="surname" value="<?php echo htmlspecialchars($donorForm['surname'] ?? ''); ?>" required disabled class="field-disabled">
            </div>
            <div class="form-group">
                <label for="mobile">Mobile Number</label>
                <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($donorForm['mobile'] ?? ''); ?>" placeholder="Enter your mobile number" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="email-input-wrapper">
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($donorForm['email'] ?? $user['email'] ?? ''); ?>" disabled class="field-disabled">
                    <span class="email-icon">✉️</span>
                </div>
            </div>
            <button type="submit" class="save-btn" id="saveBtn">Save Changes</button>
        </form>
    </div>

    <div class="loader-overlay" id="loader-overlay">
        <div class="loader"></div>
    </div>

    <!-- Cropper Modal -->
    <div id="cropperModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); z-index:10000; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:20px; border-radius:10px; max-width:90vw; max-height:90vh; text-align:center;">
            <h3>Crop your profile picture</h3>
            <img id="cropperImage" src="" style="max-width:300px; max-height:300px; display:block; margin:0 auto;" />
            <div style="margin-top:15px;">
                <button type="button" id="cropBtn" style="padding:8px 20px; background:#D50000; color:#fff; border:none; border-radius:5px; font-weight:bold;">Crop & Use</button>
                <button type="button" id="cancelCropBtn" style="padding:8px 20px; background:#ccc; color:#333; border:none; border-radius:5px; font-weight:bold; margin-left:10px;">Cancel</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editProfileForm');
            const loaderOverlay = document.getElementById('loader-overlay');
            const saveBtn = document.getElementById('saveBtn');
            const mobileInput = document.getElementById('mobile');
            const profileInput = document.getElementById('profile_picture');
            const profileAvatar = document.getElementById('profileAvatar');
            const changePictureLink = document.getElementById('changePictureLink');
            const cropperModal = document.getElementById('cropperModal');
            const cropperImage = document.getElementById('cropperImage');
            const cropBtn = document.getElementById('cropBtn');
            const cancelCropBtn = document.getElementById('cancelCropBtn');
            let cropper = null;
            let croppedDataUrl = null;

            // Mobile number validation
            mobileInput.addEventListener('input', function() {
                // Remove any non-digit characters except + and -
                this.value = this.value.replace(/[^0-9+\-\(\)\s]/g, '');
            });

            // Show file input when clicking the change picture link
            changePictureLink.addEventListener('click', function() {
                profileInput.click();
            });

            // Image cropper logic
            profileInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        cropperImage.src = e.target.result;
                        cropperModal.style.display = 'flex';
                        if (cropper) {
                            cropper.destroy();
                        }
                        cropper = new Cropper(cropperImage, {
                            aspectRatio: 1,
                            viewMode: 1,
                            autoCropArea: 1,
                            responsive: true,
                            background: false
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });

            cropBtn.addEventListener('click', function() {
                if (cropper) {
                    const canvas = cropper.getCroppedCanvas({ width: 300, height: 300 });
                    croppedDataUrl = canvas.toDataURL('image/png');
                    profileAvatar.style.backgroundImage = `url('${croppedDataUrl}')`;
                    cropper.destroy();
                    cropper = null;
                    cropperModal.style.display = 'none';
                }
            });

            cancelCropBtn.addEventListener('click', function() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                cropperModal.style.display = 'none';
                profileInput.value = '';
            });

            form.addEventListener('submit', function(event) {
                // Prevent the form from submitting immediately
                event.preventDefault();

                // Validate mobile number
                const mobile = mobileInput.value.trim();
                if (mobile.length < 10) {
                    alert('Mobile number must be at least 10 digits long.');
                    return;
                }

                // If a cropped image exists, set it as the file to upload
                if (croppedDataUrl) {
                    // Create a hidden input to send the cropped image as base64
                    let hiddenInput = document.getElementById('cropped_profile_picture');
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'cropped_profile_picture';
                        hiddenInput.id = 'cropped_profile_picture';
                        form.appendChild(hiddenInput);
                    }
                    hiddenInput.value = croppedDataUrl;
                }

                // Disable the save button to prevent double submission
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';

                // Show the loader
                if (loaderOverlay) {
                    loaderOverlay.style.display = 'flex';
                }

                // Submit the form after a short delay to show the loading state
                setTimeout(function() {
                    form.submit();
                }, 1000);
            });

            // Auto-hide success messages after 5 seconds
            const successMessage = document.querySelector('.success-message');
            if (successMessage) {
                setTimeout(function() {
                    successMessage.style.display = 'none';
                }, 5000);
            }
        });
    </script>

</body>
</html> 