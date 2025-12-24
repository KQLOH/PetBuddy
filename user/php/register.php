<?php
session_start();
require '../include/db.php';

$register_error = "";
$step = isset($_SESSION['email_verified']) && $_SESSION['email_verified'] ? 2 : 1; // Step 1: Email + OTP, Step 2: Complete Registration
$verified_email = $_SESSION['verified_email'] ?? '';

// Handle Cancel Registration
if (isset($_GET['cancel']) && $_GET['cancel'] === 'true') {
    unset($_SESSION['email_verified']);
    unset($_SESSION['verified_email']);
    header("Location: register.php");
    exit;
}

// Step 2: Complete Registration (after OTP verification)
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $gender = $_POST['gender'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $dob_day = $_POST['dob_day'] ?? '';
    $dob_month = $_POST['dob_month'] ?? '';
    $dob_year = $_POST['dob_year'] ?? '';
    
    $full_name = $first_name . ' ' . $last_name;
    
    // Determine Gender
    $db_gender = null;
    $submitted_gender_lower = strtolower($gender);
    if ($submitted_gender_lower === 'male' || $submitted_gender_lower === 'female') {
        $db_gender = $submitted_gender_lower;
    }
    
    // Format DOB
    $dob = null;
    if ($dob_day && $dob_month && $dob_year) {
        $dob_date_string = "$dob_year-$dob_month-$dob_day";
        if (checkdate((int)$dob_month, (int)$dob_day, (int)$dob_year)) {
            $dob = $dob_date_string;
        }
    }
    
    // Validation
    $server_side_valid = true;
    
    if (empty($gender) || empty($first_name) || empty($last_name) || empty($password) || empty($phone)) {
        $register_error = "Please fill in all required fields.";
        $server_side_valid = false;
    } elseif ($password !== $confirm_password) {
        $register_error = "Passwords do not match.";
        $server_side_valid = false;
    } elseif (strlen($password) < 8) {
        $register_error = "Password must be at least 8 characters long.";
        $server_side_valid = false;
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
        $register_error = "Password must be at least 8 characters long, contain mixed letter cases, 1 digit, and 1 special character.";
        $server_side_valid = false;
    } elseif (!$dob) {
        $register_error = "Please select a valid Date of Birth.";
        $server_side_valid = false;
    }
    
    // Handle Profile Image Upload
    $image_path = null;
    if ($server_side_valid && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Validate file type
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check !== false && in_array($file_ext, $allowed_exts)) {
            // Validate file size (max 5MB)
            if ($_FILES["profile_image"]["size"] <= 5000000) {
                // Create unique filename using email hash and timestamp
                $email_hash = substr(md5($verified_email), 0, 8);
                $new_filename = "mem_reg_" . $email_hash . "_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                    $image_path = "uploads/" . $new_filename;
                } else {
                    $register_error = "Failed to upload profile image. Please try again.";
                    $server_side_valid = false;
                }
            } else {
                $register_error = "Image file is too large. Maximum size is 5MB.";
                $server_side_valid = false;
            }
        } else {
            $register_error = "Invalid image file. Please upload JPG, JPEG, PNG, or GIF files only.";
            $server_side_valid = false;
        }
    }
    
    if ($server_side_valid && !empty($verified_email)) {
        try {
            // Check if email already registered (double check)
            $stmt = $pdo->prepare("SELECT email FROM members WHERE email = ?");
            $stmt->execute([$verified_email]);
            if ($stmt->rowCount() > 0) {
                $register_error = "Email is already registered.";
            } else {
                // Register User
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user and get the new member_id
                $sql = "INSERT INTO members (email, password_hash, full_name, phone, gender, dob, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$verified_email, $password_hash, $full_name, $phone, $db_gender, $dob, $image_path])) {
                    // If image was uploaded, rename it with the new member_id
                    if ($image_path) {
                        $new_member_id = $pdo->lastInsertId();
                        $old_path = "../" . $image_path;
                        
                        if (file_exists($old_path)) {
                            $file_ext = pathinfo($old_path, PATHINFO_EXTENSION);
                            $new_image_path = "uploads/mem_" . $new_member_id . "_" . time() . "." . $file_ext;
                            $new_full_path = "../" . $new_image_path;
                            
                            if (rename($old_path, $new_full_path)) {
                                // Update database with new path
                                $update_stmt = $pdo->prepare("UPDATE members SET image = ? WHERE member_id = ?");
                                $update_stmt->execute([$new_image_path, $new_member_id]);
                            }
                        }
                    }
                    
                    // Clear session
                    unset($_SESSION['email_verified']);
                    unset($_SESSION['verified_email']);
                    
                    header("Location: login.php?registration_success=true");
                    exit;
                } else {
                    $register_error = "Registration failed. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $register_error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetBuddy Online Pet Shop | Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .header-logo {
            height: 1.8rem;
            width: auto;
            margin-right: 0.5rem;
            vertical-align: bottom;
        }

        .input-group .input-icon {
            width: auto;
            height: 1.25rem;
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            pointer-events: none;
            opacity: 0.7;
        }

        .form-input {
            padding-left: 3rem !important;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            width: 1.25rem;
            height: auto;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        .page-content-wrapper {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 0;
            width: 100%;
        }

        .max-w-lg {
            max-width: 800px;
            width: 100%;
        }

        .alert-success {
            background-color: rgba(244, 162, 97, 0.12);
            color: var(--primary-dark);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .form-footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .footer-link {
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .warning-link {
            color: #e67e22;
        }

        .warning-link:hover {
            color: #d35400;
            text-decoration: underline;
        }

        .muted-link {
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .muted-link:hover {
            color: #343a40;
        }

        .icon-arrow {
            font-size: 1.1em;
            line-height: 1;
            transition: transform 0.2s;
        }

        .muted-link:hover .icon-arrow {
            transform: translateX(-3px);
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 2rem;
        }

        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            background: #e5e7eb;
            color: #6b7280;
        }

        .step.active {
            background: var(--primary-color);
            color: white;
        }

        .step.completed {
            background: #10b981;
            color: white;
        }

        .step-line {
            width: 40px;
            height: 2px;
            background: #e5e7eb;
            margin-top: 14px;
        }

        .step-line.completed {
            background: #10b981;
        }

        /* Step 1: Email + OTP */
        .step-1-container {
            display: <?php echo $step === 1 ? 'block' : 'none'; ?>;
        }

        .step-2-container {
            display: <?php echo $step === 2 ? 'block' : 'none'; ?>;
        }

        .otp-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .otp-input {
            flex: 1;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            border: 2px solid #ccc;
            border-radius: 8px;
            transition: 0.2s;
        }

        .otp-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(244, 162, 97, 0.2);
        }

        .btn-primary {
            display: block;
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 0.5rem;
            background-color: var(--primary-color);
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #6b7280;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-cancel {
            display: block;
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #dc2626;
            border-radius: 0.5rem;
            background-color: transparent;
            color: #dc2626;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .btn-cancel:hover {
            background-color: #dc2626;
            color: white;
        }

        .resend-otp {
            text-align: center;
            margin-top: 15px;
            color: #666;
            font-size: 14px;
        }

        .resend-otp a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .resend-otp a:hover {
            text-decoration: underline;
        }

        .countdown {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Include existing styles for Step 2 form elements */
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .salutation-group {
            margin: 10px 0 20px;
            display: flex;
            gap: 20px;
        }

        .salutation-option input[type="radio"] {
            accent-color: var(--primary-color);
        }

        .mobile-row {
            display: flex;
            gap: 10px;
        }

        .mobile-left {
            flex: 0 0 80px;
        }

        .mobile-left .form-input {
            text-align: center;
            padding-left: 1rem !important;
        }

        .mobile-right {
            flex: 1;
        }

        .password-hint {
            display: none;
            font-size: 12px;
            color: #555;
            margin-top: 5px;
            line-height: 1.4;
        }

        .password-group:focus-within .password-hint {
            display: block;
        }

        .password-hint ul {
            margin-left: 20px;
            color: #555;
            font-size: 12px;
        }

        .dob-row {
            display: flex;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .ck-select {
            flex: 1;
            height: 48px;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0 12px;
            display: flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
            background: #fff;
            box-sizing: border-box;
            font-size: 1rem;
            color: #7a7a7a;
            position: relative;
        }

        .ck-select:hover {
            border-color: var(--primary-color);
        }

        .ck-select::after {
            content: "";
            position: absolute;
            right: 12px;
            width: 14px;
            height: 14px;
            background-image: url('data:image/svg+xml;utf8,<svg fill="%237a7a7a" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
            background-size: cover;
        }

        .ck-selected {
            color: #1f2937;
        }

        .ck-options {
            position: absolute;
            top: 48px;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ccc;
            max-height: 120px;
            overflow-y: auto;
            display: none;
            z-index: 100;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .ck-option {
            padding: 10px 12px;
            color: #1f2937;
            font-size: 1rem;
            cursor: pointer;
        }

        .ck-option:hover {
            background: #f9f9f9;
        }

        .error-message {
            font-size: 0.75rem;
            color: #e53935;
            margin-top: 0.25rem;
            margin-bottom: 1rem;
            min-height: 15px;
        }

        .error-border {
            border-color: #e53935 !important;
        }

        .verified-email-display {
            background: #d1fae5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            color: #065f46;
            font-weight: 600;
        }

        /* Profile Image Upload Styles */
        .profile-image-upload {
            text-align: center;
        }

        .profile-image-preview {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #e5e7eb;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: border-color 0.3s ease;
        }

        .profile-image-preview:hover {
            border-color: var(--primary-color);
        }

        .profile-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 14px;
        }

        .profile-placeholder i {
            font-size: 48px;
            margin-bottom: 8px;
            color: #d1d5db;
        }

        .btn-upload-image,
        .btn-remove-image {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
            margin: 0 5px;
        }

        .btn-upload-image:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-remove-image {
            background: #dc2626;
        }

        .btn-remove-image:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        .image-upload-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
        }
    </style>
</head>

<body>
    <?php include '../include/header.php'; ?>

    <div class="page-content-wrapper">
        <div class="card max-w-lg">
            <div class="card-header">
                <h1>
                    <img src="../images/pawprint.png" alt="Logo" class="header-logo">
                    PetBuddy
                </h1>
                <p><?php echo $step === 1 ? 'Create your account!' : 'Complete your registration!'; ?></p>
            </div>

            <div class="card-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">1</div>
                    <div class="step-line <?php echo $step > 1 ? 'completed' : ''; ?>"></div>
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
                </div>

                <?php if ($register_error): ?>
                    <div class="alert-error" role="alert"><?= htmlspecialchars($register_error) ?></div>
                <?php endif; ?>

                <!-- Step 1: Email + OTP Verification -->
                <div class="step-1-container">
                    <h2 class="card-title">Email Verification</h2>
                    
                    <div class="mb-4">
                        <label class="form-label" for="email-input">Email Address</label>
                        <div class="input-group">
                            <img src="../images/mail.png" alt="Email" class="input-icon">
                            <input type="email" class="form-input" id="email-input" placeholder="you@example.com" required>
                        </div>
                        <div class="error-message" id="email-error"></div>
                    </div>

                    <button type="button" class="btn-primary" id="send-otp-btn" onclick="sendOTP()">Send Verification Code</button>

                        <div id="otp-section" style="display: none; margin-top: 30px;">
                            <p style="text-align: center; margin-bottom: 15px; color: #666;">
                                Enter the 6-digit code sent to <strong id="otp-email-display"></strong>
                            </p>
                            
                            <div class="otp-input-group">
                                <input type="text" class="otp-input" id="otp1" maxlength="1" pattern="[0-9]">
                                <input type="text" class="otp-input" id="otp2" maxlength="1" pattern="[0-9]">
                                <input type="text" class="otp-input" id="otp3" maxlength="1" pattern="[0-9]">
                                <input type="text" class="otp-input" id="otp4" maxlength="1" pattern="[0-9]">
                                <input type="text" class="otp-input" id="otp5" maxlength="1" pattern="[0-9]">
                                <input type="text" class="otp-input" id="otp6" maxlength="1" pattern="[0-9]">
                            </div>

                            <button type="button" class="btn-primary" id="verify-otp-btn" onclick="verifyOTP()">Verify Code</button>
                            
                            <div class="resend-otp">
                                <span>Didn't receive the code? </span>
                                <a href="#" id="resend-link" onclick="sendOTP(); return false;">Resend</a>
                                <span id="countdown-text" style="display: none;"> (<span class="countdown" id="countdown">180</span>s)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Complete Registration -->
                    <div class="step-2-container">
                        <h2 class="card-title">Complete Registration</h2>
                        
                        <div class="verified-email-display">
                            Verified: <?php echo htmlspecialchars($verified_email); ?>
                        </div>

                        <form method="POST" action="" id="registration-form" enctype="multipart/form-data" novalidate>
                            <!-- Profile Image Upload -->
                            <div class="mb-4">
                                <label class="form-label">Profile Picture (Optional)</label>
                                <div class="profile-image-upload">
                                    <div class="profile-image-preview" id="profile-image-preview">
                                        <img id="profile-preview-img" src="" alt="Profile preview" style="display: none;">
                                        <div class="profile-placeholder" id="profile-placeholder">
                                            <i class="fas fa-user"></i>
                                            <span>No image selected</span>
                                        </div>
                                    </div>
                                    <input type="file" name="profile_image" id="profile_image" accept="image/*" style="display: none;">
                                    <button type="button" class="btn-upload-image" onclick="document.getElementById('profile_image').click();">
                                        <i class="fas fa-camera"></i> Choose Photo
                                    </button>
                                    <button type="button" class="btn-remove-image" id="btn-remove-image" style="display: none;" onclick="removeProfileImage();">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                    <div class="image-upload-hint">
                                        JPG, PNG or GIF. Max size 5MB.
                                    </div>
                                    <div class="error-message" id="profile-image-error"></div>
                                </div>
                            </div>

                            <label class="form-label">Gender</label>
                            <div class="salutation-group" id="gender-group">
                                <label class="salutation-option">
                                    <input type="radio" name="gender" value="male" required> Male
                                </label>
                                <label class="salutation-option">
                                    <input type="radio" name="gender" value="female" required> Female
                                </label>
                                <label class="salutation-option">
                                    <input type="radio" name="gender" value="prefer not to say" required> Prefer not to say
                                </label>
                            </div>
                            <div class="error-message" id="gender-error"></div>

                            <div class="mb-4">
                                <label class="form-label" for="first_name">First Name</label>
                                <div class="input-group">
                                    <input type="text" class="form-input" name="first_name" id="first_name" placeholder="John" required>
                                </div>
                                <div class="error-message" id="first_name-error"></div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="last_name">Last Name</label>
                                <div class="input-group">
                                    <input type="text" class="form-input" name="last_name" id="last_name" placeholder="Doe" required>
                                </div>
                                <div class="error-message" id="last_name-error"></div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Mobile Number</label>
                                <div class="mobile-row">
                                    <div class="mobile-left">
                                        <div class="input-group">
                                            <input type="text" class="form-input" value="+60" readonly>
                                        </div>
                                    </div>
                                    <div class="mobile-right">
                                        <div class="input-group">
                                            <input type="text" class="form-input" name="phone" placeholder="123456789" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="error-message" id="phone-error"></div>
                            </div>

                            <div class="mb-4 password-group">
                                <label class="form-label" for="password">Password</label>
                                <div class="input-group">
                                    <img src="../images/padlock.png" alt="Lock" class="input-icon">
                                    <input type="password" class="form-input" name="password" id="password" placeholder="••••••••" style="padding-right: 3rem;" required>
                                    <img src="../images/show.png" id="togglePassword" class="password-toggle" alt="Show Password">
                                </div>
                                <div class="password-hint">
                                    <ul>
                                        <li>Min. of 8 characters</li>
                                        <li>Mixed letter cases</li>
                                        <li>1 digit</li>
                                        <li>1 special character</li>
                                    </ul>
                                </div>
                                <div class="error-message" id="password-error"></div>
                            </div>

                            <div class="mb-6">
                                <label class="form-label" for="confirm_password">Confirm Password</label>
                                <div class="input-group">
                                    <img src="../images/padlock.png" alt="Lock" class="input-icon">
                                    <input type="password" class="form-input" name="confirm_password" id="confirm_password" placeholder="••••••••" style="padding-right: 3rem;" required>
                                    <img src="../images/show.png" id="toggleConfirmPassword" class="password-toggle" alt="Show Password">
                                </div>
                                <div class="error-message" id="confirm_password-error"></div>
                            </div>

                            <div class="mb-6">
                                <label class="form-label">Date of Birth</label>
                                <div class="dob-row" id="dob-row">
                                    <div class="ck-select" data-placeholder="Day">
                                        <div class="ck-selected">Day</div>
                                        <input type="hidden" name="dob_day" class="ck-hidden-input" required>
                                        <div class="ck-options" id="ck-day-options"></div>
                                    </div>
                                    <div class="ck-select" data-placeholder="Month">
                                        <div class="ck-selected">Month</div>
                                        <input type="hidden" name="dob_month" class="ck-hidden-input" required>
                                        <div class="ck-options">
                                            <div class="ck-option" data-value="01">January</div>
                                            <div class="ck-option" data-value="02">February</div>
                                            <div class="ck-option" data-value="03">March</div>
                                            <div class="ck-option" data-value="04">April</div>
                                            <div class="ck-option" data-value="05">May</div>
                                            <div class="ck-option" data-value="06">June</div>
                                            <div class="ck-option" data-value="07">July</div>
                                            <div class="ck-option" data-value="08">August</div>
                                            <div class="ck-option" data-value="09">September</div>
                                            <div class="ck-option" data-value="10">October</div>
                                            <div class="ck-option" data-value="11">November</div>
                                            <div class="ck-option" data-value="12">December</div>
                                        </div>
                                    </div>
                                    <div class="ck-select" data-placeholder="Year">
                                        <div class="ck-selected">Year</div>
                                        <input type="hidden" name="dob_year" class="ck-hidden-input" required>
                                        <div class="ck-options" id="ck-year-options"></div>
                                    </div>
                                </div>
                                <div class="error-message" id="dob-error"></div>
                            </div>

                            <button type="submit" class="btn-primary">Create Account</button>
                            
                            <div style="margin-top: 1rem;">
                                <a href="#" class="btn-cancel" id="cancel-registration-btn">Cancel Registration</a>
                            </div>
                        </form>
                    </div>

                    <div class="mt-6 text-center link-muted">
                        Already have an account? <a href="login.php" class="link-primary">Login here</a>
                    </div>

                    <div class="form-footer-actions">
                        <a href="home.php" class="footer-link muted-link">
                            <span class="icon-arrow">←</span> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>

    <script>
        // OTP Input Auto-focus
        document.querySelectorAll('.otp-input').forEach((input, index) => {
            input.addEventListener('input', function(e) {
                if (this.value.length === 1 && index < 5) {
                    document.getElementById('otp' + (index + 2)).focus();
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    document.getElementById('otp' + index).focus();
                }
            });
        });

        // Send OTP
        function sendOTP() {
            const email = document.getElementById('email-input').value.trim();
            const emailError = document.getElementById('email-error');
            
            if (!email) {
                emailError.textContent = 'Please enter your email address.';
                return;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                emailError.textContent = 'Invalid email format.';
                return;
            }
            
            emailError.textContent = '';
            document.getElementById('send-otp-btn').disabled = true;
            document.getElementById('send-otp-btn').textContent = 'Sending...';
            
            $.ajax({
                url: 'send_otp.php',
                type: 'POST',
                data: { email: email },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        document.getElementById('otp-section').style.display = 'block';
                        document.getElementById('otp-email-display').textContent = email;
                        document.getElementById('email-input').disabled = true;
                        
                        // Start countdown
                        startCountdown(180);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Code Sent!',
                            text: response.message || 'Please check your email for the verification code.',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to send verification code. Please try again.', 'error');
                    }
                    document.getElementById('send-otp-btn').disabled = false;
                    document.getElementById('send-otp-btn').textContent = 'Send Verification Code';
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    let errorMsg = 'Failed to send code. ';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMsg = response.message;
                        }
                        if (response.error) {
                            console.error('Server Error:', response.error);
                        }
                    } catch (e) {
                        errorMsg += 'Please check browser console and server logs for details.';
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg,
                        confirmButtonText: 'OK'
                    });
                    
                    document.getElementById('send-otp-btn').disabled = false;
                    document.getElementById('send-otp-btn').textContent = 'Send Verification Code';
                }
            });
        }

        // Verify OTP
        function verifyOTP() {
            const otp = document.getElementById('otp1').value +
                       document.getElementById('otp2').value +
                       document.getElementById('otp3').value +
                       document.getElementById('otp4').value +
                       document.getElementById('otp5').value +
                       document.getElementById('otp6').value;
            
            if (otp.length !== 6) {
                Swal.fire('Error', 'Please enter the complete 6-digit code.', 'error');
                return;
            }
            
            document.getElementById('verify-otp-btn').disabled = true;
            document.getElementById('verify-otp-btn').textContent = 'Verifying...';
            
            $.ajax({
                url: 'verify_otp.php',
                type: 'POST',
                data: { otp: otp },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Email Verified!',
                            text: 'Please complete your registration.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                        // Clear OTP inputs
                        for (let i = 1; i <= 6; i++) {
                            document.getElementById('otp' + i).value = '';
                        }
                        document.getElementById('otp1').focus();
                    }
                    document.getElementById('verify-otp-btn').disabled = false;
                    document.getElementById('verify-otp-btn').textContent = 'Verify Code';
                },
                error: function() {
                    Swal.fire('Error', 'Verification failed. Please try again.', 'error');
                    document.getElementById('verify-otp-btn').disabled = false;
                    document.getElementById('verify-otp-btn').textContent = 'Verify Code';
                }
            });
        }

        // Countdown Timer
        function startCountdown(seconds) {
            const countdownEl = document.getElementById('countdown');
            const countdownText = document.getElementById('countdown-text');
            const resendLink = document.getElementById('resend-link');
            
            countdownText.style.display = 'inline';
            resendLink.style.pointerEvents = 'none';
            resendLink.style.opacity = '0.5';
            
            let remaining = seconds;
            countdownEl.textContent = remaining;
            
            const timer = setInterval(() => {
                remaining--;
                countdownEl.textContent = remaining;
                
                if (remaining <= 0) {
                    clearInterval(timer);
                    countdownText.style.display = 'none';
                    resendLink.style.pointerEvents = 'auto';
                    resendLink.style.opacity = '1';
                }
            }, 1000);
        }

        // Step 2: Custom Select and Form Validation (same as before)
        document.addEventListener('DOMContentLoaded', function() {
            // Populate Days and Years
            const dayOptions = document.getElementById('ck-day-options');
            if (dayOptions) {
                let dayHtml = '';
                for (let i = 1; i <= 31; i++) {
                    const dayValue = i < 10 ? `0${i}` : `${i}`;
                    dayHtml += `<div class="ck-option" data-value="${dayValue}">${i}</div>`;
                }
                dayOptions.innerHTML = dayHtml;
            }

            const yearOptions = document.getElementById('ck-year-options');
            if (yearOptions) {
                let yearHtml = '';
                for (let y = new Date().getFullYear(); y >= 1900; y--) {
                    yearHtml += `<div class="ck-option" data-value="${y}">${y}</div>`;
                }
                yearOptions.innerHTML = yearHtml;
            }

            // Custom Select Logic
            document.querySelectorAll('.ck-select').forEach(select => {
                const selected = select.querySelector('.ck-selected');
                const optionsBox = select.querySelector('.ck-options');
                const hiddenInput = select.querySelector('.ck-hidden-input');
                const placeholder = select.getAttribute('data-placeholder');

                const setSelectedValue = (value, text) => {
                    hiddenInput.value = value;
                    selected.textContent = text;
                    selected.style.color = 'var(--text-dark)';
                };

                select.addEventListener('click', (e) => {
                    const isOpen = optionsBox.style.display === 'block';
                    document.querySelectorAll('.ck-options').forEach(opt => opt.style.display = 'none');
                    optionsBox.style.display = isOpen ? 'none' : 'block';
                    e.stopPropagation();
                });

                select.querySelectorAll('.ck-option').forEach(option => {
                    option.onclick = (e) => {
                        const value = option.getAttribute('data-value') || option.textContent;
                        setSelectedValue(value, option.textContent);
                        optionsBox.style.display = 'none';
                        e.stopPropagation();
                    };
                });
            });

            document.addEventListener('click', e => {
                if (!e.target.closest('.ck-select')) {
                    document.querySelectorAll('.ck-options').forEach(opt => opt.style.display = 'none');
                }
            });

            // Profile Image Preview
            const profileImageInput = document.getElementById('profile_image');
            const profilePreview = document.getElementById('profile-preview-img');
            const profilePlaceholder = document.getElementById('profile-placeholder');
            const btnRemoveImage = document.getElementById('btn-remove-image');
            const profileImageError = document.getElementById('profile-image-error');

            if (profileImageInput) {
                profileImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    profileImageError.textContent = '';

                    if (file) {
                        // Validate file type
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!allowedTypes.includes(file.type)) {
                            profileImageError.textContent = 'Please upload JPG, PNG, or GIF files only.';
                            profileImageInput.value = '';
                            return;
                        }

                        // Validate file size (5MB)
                        if (file.size > 5000000) {
                            profileImageError.textContent = 'Image file is too large. Maximum size is 5MB.';
                            profileImageInput.value = '';
                            return;
                        }

                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            profilePreview.src = e.target.result;
                            profilePreview.style.display = 'block';
                            profilePlaceholder.style.display = 'none';
                            btnRemoveImage.style.display = 'inline-block';
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Remove Profile Image
            window.removeProfileImage = function() {
                if (profileImageInput) profileImageInput.value = '';
                if (profilePreview) {
                    profilePreview.src = '';
                    profilePreview.style.display = 'none';
                }
                if (profilePlaceholder) profilePlaceholder.style.display = 'flex';
                if (btnRemoveImage) btnRemoveImage.style.display = 'none';
                if (profileImageError) profileImageError.textContent = '';
            };

            // Form Validation (Step 2)
            const form = document.getElementById('registration-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Basic validation - you can add more if needed
                    const password = document.querySelector('[name="password"]').value;
                    const confirmPassword = document.querySelector('[name="confirm_password"]').value;
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        Swal.fire('Error', 'Passwords do not match.', 'error');
                        return false;
                    }

                    // Validate image if selected
                    if (profileImageInput && profileImageInput.files[0]) {
                        const imageFile = profileImageInput.files[0];
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!allowedTypes.includes(imageFile.type)) {
                            e.preventDefault();
                            Swal.fire('Error', 'Please upload JPG, PNG, or GIF files only.', 'error');
                            return false;
                        }
                        if (imageFile.size > 5000000) {
                            e.preventDefault();
                            Swal.fire('Error', 'Image file is too large. Maximum size is 5MB.', 'error');
                            return false;
                        }
                    }
                });
            }

            // Password toggle for Step 2
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');
            if (togglePassword && password) {
                togglePassword.addEventListener('click', function(e) {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    if (type === 'text') {
                        this.src = '../images/hide.png';
                    } else {
                        this.src = '../images/show.png';
                    }
                });
            }

            const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
            const confirmPassword = document.querySelector('#confirm_password');
            if (toggleConfirmPassword && confirmPassword) {
                toggleConfirmPassword.addEventListener('click', function(e) {
                    const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmPassword.setAttribute('type', type);
                    if (type === 'text') {
                        this.src = '../images/hide.png';
                    } else {
                        this.src = '../images/show.png';
                    }
                });
            }
        });

        // Confirm Cancel Registration
        const cancelBtn = document.getElementById('cancel-registration-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Cancel Registration?',
                    text: 'Are you sure you want to cancel? Your email verification will be reset and you\'ll need to verify again.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, Cancel',
                    cancelButtonText: 'No, Continue'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '?cancel=true';
                    }
                });
            });
        }
    </script>
</body>
</html>
