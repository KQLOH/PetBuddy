<?php
session_start();
require '../include/db.php';

$register_error = "";
$register_success = false;
$register_success_message = '';
$step = isset($_SESSION['email_verified']) && $_SESSION['email_verified'] ? 2 : 1;
$verified_email = $_SESSION['verified_email'] ?? '';

if ((isset($_SESSION['registration_success']) && $_SESSION['registration_success']) || (isset($_GET['success']) && $_GET['success'] == '1')) {
    $register_success = true;
    $register_success_message = $_SESSION['registration_success_message'] ?? 'Registration successful! Redirecting to login page...';
    unset($_SESSION['registration_success']);
    unset($_SESSION['registration_success_message']);
}

$form_data = $_SESSION['form_data'] ?? [
    'gender' => '',
    'full_name' => '',
    'phone' => '',
    'dob_day' => '',
    'dob_month' => '',
    'dob_year' => ''
];

$field_errors = $_SESSION['field_errors'] ?? [
    'gender' => '',
    'full_name' => '',
    'phone' => '',
    'password' => '',
    'confirm_password' => '',
    'dob' => '',
    'profile_image' => ''
];

if (isset($_GET['cancel']) && $_GET['cancel'] === 'true') {
    unset($_SESSION['email_verified']);
    unset($_SESSION['verified_email']);
    header("Location: register.php");
    exit;
}

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $gender = $_POST['gender'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $dob_day = $_POST['dob_day'] ?? '';
    $dob_month = $_POST['dob_month'] ?? '';
    $dob_year = $_POST['dob_year'] ?? '';
    
    $form_data = [
        'gender' => $gender,
        'full_name' => $full_name,
        'phone' => $phone,
        'dob_day' => $dob_day,
        'dob_month' => $dob_month,
        'dob_year' => $dob_year
    ];
    
    $db_gender = null;
    $submitted_gender_lower = strtolower($gender);
    if ($submitted_gender_lower === 'male' || $submitted_gender_lower === 'female') {
        $db_gender = $submitted_gender_lower;
    }
    
    $dob = null;
    if ($dob_day && $dob_month && $dob_year) {
        $dob_date_string = "$dob_year-$dob_month-$dob_day";
        if (checkdate((int)$dob_month, (int)$dob_day, (int)$dob_year)) {
            $dob = $dob_date_string;
        }
    }
    
    $server_side_valid = true;

    if (!empty($verified_email)) {
        if (!filter_var($verified_email, FILTER_VALIDATE_EMAIL)) {
            $register_error = "Invalid email format. Please verify your email again.";
            $server_side_valid = false;
        } else {
            $emailParts = explode('@', $verified_email);
            if (count($emailParts) !== 2) {
                $register_error = "Invalid email format. Please verify your email again.";
                $server_side_valid = false;
            } else {
                $localPart = $emailParts[0];
                $domain = strtolower($emailParts[1]);
                
                if (strlen($localPart) > 64) {
                    $register_error = "Email username is too long. Maximum length is 64 characters.";
                    $server_side_valid = false;
                }
                
                if (strpos($verified_email, '..') !== false) {
                    $register_error = "Invalid email format. Cannot have consecutive dots.";
                    $server_side_valid = false;
                }
                
                if (substr($localPart, 0, 1) === '.' || substr($localPart, -1) === '.') {
                    $register_error = "Invalid email format. Email cannot start or end with a dot.";
                    $server_side_valid = false;
                }
                
                $domainParts = explode('.', $domain);
                if (count($domainParts) < 2) {
                    $register_error = "Invalid email domain. Please check your email address.";
                    $server_side_valid = false;
                }
                
                $tld = end($domainParts);
                if (strlen($tld) < 2 || !preg_match('/^[a-zA-Z]+$/', $tld)) {
                    $register_error = "Invalid email domain. Please check your email address.";
                    $server_side_valid = false;
                }
                
                $commonTypos = [
                    'gmali.com' => 'gmail.com',
                    'gmal.com' => 'gmail.com',
                    'gmial.com' => 'gmail.com',
                    'gmaill.com' => 'gmail.com',
                    'gmai.com' => 'gmail.com',
                    'hotmial.com' => 'hotmail.com',
                    'hotmai.com' => 'hotmail.com',
                    'hotmali.com' => 'hotmail.com',
                    'yahooo.com' => 'yahoo.com',
                    'yaho.com' => 'yahoo.com',
                    'outlok.com' => 'outlook.com'
                ];
                
                if (isset($commonTypos[$domain])) {
                    $register_error = "Did you mean \"" . $commonTypos[$domain] . "\"? Please check your email address.";
                    $server_side_valid = false;
                }
                
                $validDomains = [
                    'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com',
                    'msn.com', 'ymail.com', 'icloud.com', 'me.com', 'mac.com',
                    'protonmail.com', 'proton.me', 'mail.com', 'aol.com', 'zoho.com',
                    'gmx.com', 'yandex.com', 'qq.com', '163.com', '126.com',
                    'sina.com', 'sohu.com', 'rediffmail.com', 'inbox.com', 'fastmail.com',
                    'student.tarc.edu.my'
                ];
                
                if (!in_array($domain, $validDomains)) {
                    $register_error = "Please use a valid email provider (Gmail, Hotmail, Yahoo, Outlook, iCloud, etc.).";
                    $server_side_valid = false;
                }
                
                if (strlen($verified_email) > 254) {
                    $register_error = "Email address is too long. Maximum length is 254 characters.";
                    $server_side_valid = false;
                }
            }
        }
    } else {
        $register_error = "Email verification required. Please complete Step 1 first.";
        $server_side_valid = false;
    }
    
    if ($server_side_valid) {
        if (empty($gender)) {
            $field_errors['gender'] = "Please select your gender.";
            $server_side_valid = false;
        }
        
        if (empty($full_name)) {
            $field_errors['full_name'] = "Full name is required.";
            $server_side_valid = false;
        } elseif (strlen($full_name) < 2) {
            $field_errors['full_name'] = "Full name must be at least 2 characters.";
            $server_side_valid = false;
        } elseif (strlen($full_name) > 100) {
            $field_errors['full_name'] = "Full name is too long. Maximum length is 100 characters.";
            $server_side_valid = false;
        }
        
        if (empty($phone)) {
            $field_errors['phone'] = "Phone number is required.";
            $server_side_valid = false;
        } elseif (!preg_match('/^[0-9]{9,15}$/', preg_replace('/[^0-9]/', '', $phone))) {
            $field_errors['phone'] = "Please enter a valid phone number (9-15 digits).";
            $server_side_valid = false;
        }
        
        if (empty($password)) {
            $field_errors['password'] = "Password is required.";
            $server_side_valid = false;
        } elseif (strlen($password) < 8) {
            $field_errors['password'] = "Password must be at least 8 characters long.";
            $server_side_valid = false;
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
            $field_errors['password'] = "Password must contain: uppercase, lowercase, 1 digit, and 1 special character.";
            $server_side_valid = false;
        }
        
        if (empty($confirm_password)) {
            $field_errors['confirm_password'] = "Please confirm your password.";
            $server_side_valid = false;
        } elseif ($password !== $confirm_password) {
            $field_errors['confirm_password'] = "Passwords do not match.";
            $server_side_valid = false;
        }
        
        if (!$dob) {
            $field_errors['dob'] = "Please select a valid Date of Birth.";
            $server_side_valid = false;
        }
    }
    
    if (!$server_side_valid) {
        $register_error = "Please correct the errors below and try again.";
    }
    
    $image_path = null;
    if ($server_side_valid && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check !== false && in_array($file_ext, $allowed_exts)) {
            if ($_FILES["profile_image"]["size"] <= 5000000) {
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
    
    if ($server_side_valid && $image_path === null) {
        if ($db_gender === 'male') {
            $image_path = "images/boy.png";
        } elseif ($db_gender === 'female') {
            $image_path = "images/woman.png";
        } else {
            $image_path = "images/boy.png";
        }
    }
    
    if ($server_side_valid && !empty($verified_email)) {
        try {
            $stmt = $pdo->prepare("SELECT email FROM members WHERE email = ?");
            $stmt->execute([$verified_email]);
            if ($stmt->rowCount() > 0) {
                $register_error = "Email is already registered.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO members (email, password_hash, full_name, phone, gender, dob, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$verified_email, $password_hash, $full_name, $phone, $db_gender, $dob, $image_path])) {
                    if ($image_path && strpos($image_path, 'uploads/') === 0) {
                        $new_member_id = $pdo->lastInsertId();
                        $old_path = "../" . $image_path;
                        
                        if (file_exists($old_path)) {
                            $file_ext = pathinfo($old_path, PATHINFO_EXTENSION);
                            $new_image_path = "uploads/mem_" . $new_member_id . "_" . time() . "." . $file_ext;
                            $new_full_path = "../" . $new_image_path;
                            
                            if (rename($old_path, $new_full_path)) {
                                $update_stmt = $pdo->prepare("UPDATE members SET image = ? WHERE member_id = ?");
                                $update_stmt->execute([$new_image_path, $new_member_id]);
                            }
                        }
                    }
                    
                    unset($_SESSION['email_verified']);
                    unset($_SESSION['verified_email']);
                    
                    $_SESSION['registration_success'] = true;
                    $_SESSION['registration_success_message'] = 'Registration successful! Redirecting to login page...';
                    
                    header("Location: register.php?success=1");
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
            display: block;
        }

        .error-border {
            border-color: #e53935 !important;
        }
        
        .input-error {
            border-color: #e53935 !important;
            background-color: #fff5f5 !important;
            box-shadow: 0 0 0 3px rgba(229, 57, 53, 0.1) !important;
        }
        
        .input-error:focus {
            border-color: #e53935 !important;
            box-shadow: 0 0 0 3px rgba(229, 57, 53, 0.2) !important;
        }
        
        .ck-select-error {
            border-color: #e53935 !important;
            background-color: #fff5f5 !important;
        }
        
        .ck-select-error:hover {
            border-color: #e53935 !important;
        }
        
        .salutation-group:has(input:invalid) + .error-message {
            display: block;
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

        /* Custom Alert/Confirm Modal Styles */
        .custom-modal-overlay {
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
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .custom-modal-overlay.show {
            opacity: 1;
        }

        .custom-modal {
            background: white;
            border-radius: 12px;
            padding: 0;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .custom-modal-overlay.show .custom-modal {
            transform: scale(1);
        }

        .custom-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .custom-modal-header.success {
            border-bottom-color: #10b981;
        }

        .custom-modal-header.error {
            border-bottom-color: #ef4444;
        }

        .custom-modal-header.warning {
            border-bottom-color: #f59e0b;
        }

        .custom-modal-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .custom-modal-icon img {
            width: 20px;
            height: 20px;
            object-fit: contain;
        }

        .custom-modal-icon.success {
            background-color: #d1fae5;
            color: #10b981;
        }

        .custom-modal-icon.error {
            background-color: #fee2e2;
            color: #ef4444;
        }

        .custom-modal-icon.warning {
            background-color: #fef3c7;
            color: #f59e0b;
        }

        .custom-modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .custom-modal-body {
            padding: 24px;
            color: #4b5563;
            line-height: 1.6;
        }

        .custom-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .custom-modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .custom-modal-btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .custom-modal-btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .custom-modal-btn-secondary {
            background-color: #6b7280;
            color: white;
        }

        .custom-modal-btn-secondary:hover {
            background-color: #4b5563;
        }

        .custom-modal-btn-danger {
            background-color: #dc2626;
            color: white;
        }

        .custom-modal-btn-danger:hover {
            background-color: #b91c1c;
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
                                    <div class="error-message" id="profile-image-error"><?php echo !empty($field_errors['profile_image']) ? htmlspecialchars($field_errors['profile_image']) : ''; ?></div>
                                </div>
                            </div>

                            <label class="form-label">Gender</label>
                            <div class="salutation-group" id="gender-group">
                                <label class="salutation-option">
                                    <input type="radio" name="gender" value="male" required <?php echo ($form_data['gender'] === 'male') ? 'checked' : ''; ?>> Male
                                </label>
                                <label class="salutation-option">
                                    <input type="radio" name="gender" value="female" required <?php echo ($form_data['gender'] === 'female') ? 'checked' : ''; ?>> Female
                                </label>
                                <label class="salutation-option">
                                    <input type="radio" name="gender" value="prefer not to say" required <?php echo ($form_data['gender'] === 'prefer not to say') ? 'checked' : ''; ?>> Prefer not to say
                                </label>
                            </div>
                            <div class="error-message" id="gender-error"><?php echo !empty($field_errors['gender']) ? htmlspecialchars($field_errors['gender']) : ''; ?></div>

                            <div class="mb-4">
                                <label class="form-label" for="full_name">Full Name</label>
                                <div class="input-group">
                                    <input type="text" class="form-input <?php echo !empty($field_errors['full_name']) ? 'input-error' : ''; ?>" name="full_name" id="full_name" placeholder="John Doe" value="<?php echo htmlspecialchars($form_data['full_name']); ?>" required>
                                </div>
                                <div class="error-message" id="full_name-error"><?php echo !empty($field_errors['full_name']) ? htmlspecialchars($field_errors['full_name']) : ''; ?></div>
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
                                            <input type="text" class="form-input <?php echo !empty($field_errors['phone']) ? 'input-error' : ''; ?>" name="phone" placeholder="123456789" value="<?php echo htmlspecialchars($form_data['phone']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="error-message" id="phone-error"><?php echo !empty($field_errors['phone']) ? htmlspecialchars($field_errors['phone']) : ''; ?></div>
                            </div>

                            <div class="mb-4 password-group">
                                <label class="form-label" for="password">Password</label>
                                <div class="input-group">
                                    <img src="../images/padlock.png" alt="Lock" class="input-icon">
                                    <input type="password" class="form-input <?php echo !empty($field_errors['password']) ? 'input-error' : ''; ?>" name="password" id="password" placeholder="••••••••" style="padding-right: 3rem;" required>
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
                                <div class="error-message" id="password-error"><?php echo !empty($field_errors['password']) ? htmlspecialchars($field_errors['password']) : ''; ?></div>
                            </div>

                            <div class="mb-6">
                                <label class="form-label" for="confirm_password">Confirm Password</label>
                                <div class="input-group">
                                    <img src="../images/padlock.png" alt="Lock" class="input-icon">
                                    <input type="password" class="form-input <?php echo !empty($field_errors['confirm_password']) ? 'input-error' : ''; ?>" name="confirm_password" id="confirm_password" placeholder="••••••••" style="padding-right: 3rem;" required>
                                    <img src="../images/show.png" id="toggleConfirmPassword" class="password-toggle" alt="Show Password">
                                </div>
                                <div class="error-message" id="confirm_password-error"><?php echo !empty($field_errors['confirm_password']) ? htmlspecialchars($field_errors['confirm_password']) : ''; ?></div>
                            </div>

                            <div class="mb-6">
                                <label class="form-label">Date of Birth</label>
                                <div class="dob-row" id="dob-row">
                                    <div class="ck-select <?php echo !empty($field_errors['dob']) ? 'ck-select-error' : ''; ?>" data-placeholder="Day">
                                        <div class="ck-selected"><?php echo !empty($form_data['dob_day']) ? $form_data['dob_day'] : 'Day'; ?></div>
                                        <input type="hidden" name="dob_day" class="ck-hidden-input" value="<?php echo htmlspecialchars($form_data['dob_day']); ?>" required>
                                        <div class="ck-options" id="ck-day-options"></div>
                                    </div>
                                    <div class="ck-select <?php echo !empty($field_errors['dob']) ? 'ck-select-error' : ''; ?>" data-placeholder="Month">
                                        <div class="ck-selected"><?php 
                                            if (!empty($form_data['dob_month'])) {
                                                $months = ['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
                                                echo $months[$form_data['dob_month']] ?? 'Month';
                                            } else {
                                                echo 'Month';
                                            }
                                        ?></div>
                                        <input type="hidden" name="dob_month" class="ck-hidden-input" value="<?php echo htmlspecialchars($form_data['dob_month']); ?>" required>
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
                                    <div class="ck-select <?php echo !empty($field_errors['dob']) ? 'ck-select-error' : ''; ?>" data-placeholder="Year">
                                        <div class="ck-selected"><?php echo !empty($form_data['dob_year']) ? $form_data['dob_year'] : 'Year'; ?></div>
                                        <input type="hidden" name="dob_year" class="ck-hidden-input" value="<?php echo htmlspecialchars($form_data['dob_year']); ?>" required>
                                        <div class="ck-options" id="ck-year-options"></div>
                                    </div>
                                </div>
                                <div class="error-message" id="dob-error"><?php echo !empty($field_errors['dob']) ? htmlspecialchars($field_errors['dob']) : ''; ?></div>
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
                            <span class="icon-arrow"><</span> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>

    <script>
        <?php if ($register_success): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showAlert('Registration Successful!', 'Your account has been created successfully. You will be redirected to the login page shortly.', 'success', 3000).then(function() {
                window.location.href = 'login.php?registration_success=true';
            });
        });
        <?php endif; ?>
        
        function showAlert(title, text, icon = 'success', timer = null) {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.className = 'custom-modal-overlay';
                overlay.id = 'custom-modal-overlay';
                
                const iconClass = icon === 'success' ? 'success' : (icon === 'error' ? 'error' : 'warning');
                let iconContent = '';
                if (icon === 'success') {
                    iconContent = '<img src="../images/success.png" alt="Success" style="width: 20px; height: 20px;">';
                } else if (icon === 'error') {
                    iconContent = '✕';
                } else {
                    iconContent = '!';
                }
                
                overlay.innerHTML = `
                    <div class="custom-modal">
                        <div class="custom-modal-header ${iconClass}">
                            <div class="custom-modal-icon ${iconClass}">${iconContent}</div>
                            <h3 class="custom-modal-title">${title}</h3>
                        </div>
                        <div class="custom-modal-body">
                            ${text}
                        </div>
                        <div class="custom-modal-footer">
                            <button class="custom-modal-btn custom-modal-btn-primary" id="custom-modal-ok">OK</button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(overlay);
                
                setTimeout(() => overlay.classList.add('show'), 10);
                
                const okBtn = overlay.querySelector('#custom-modal-ok');
                const closeModal = () => {
                    overlay.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(overlay);
                        resolve();
                    }, 300);
                };
                
                okBtn.addEventListener('click', closeModal);
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        closeModal();
                    }
                });
                
                if (timer) {
                    setTimeout(closeModal, timer);
                }
            });
        }

        function showConfirm(title, text, confirmText = 'Yes', cancelText = 'No', confirmColor = 'danger') {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.className = 'custom-modal-overlay';
                overlay.id = 'custom-modal-overlay';
                
                overlay.innerHTML = `
                    <div class="custom-modal">
                        <div class="custom-modal-header warning">
                            <div class="custom-modal-icon warning">!</div>
                            <h3 class="custom-modal-title">${title}</h3>
                        </div>
                        <div class="custom-modal-body">
                            ${text}
                        </div>
                        <div class="custom-modal-footer">
                            <button class="custom-modal-btn custom-modal-btn-secondary" id="custom-modal-cancel">${cancelText}</button>
                            <button class="custom-modal-btn custom-modal-btn-${confirmColor}" id="custom-modal-confirm">${confirmText}</button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(overlay);
                
                setTimeout(() => overlay.classList.add('show'), 10);
                
                const cancelBtn = overlay.querySelector('#custom-modal-cancel');
                const confirmBtn = overlay.querySelector('#custom-modal-confirm');
                
                const closeModal = (result) => {
                    overlay.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(overlay);
                        resolve(result);
                    }, 300);
                };
                
                cancelBtn.addEventListener('click', () => closeModal(false));
                confirmBtn.addEventListener('click', () => closeModal(true));
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        closeModal(false);
                    }
                });
            });
        }

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

        function validateEmail(email) {
            const emailRegex = /^[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]@[a-zA-Z0-9][a-zA-Z0-9.-]*\.[a-zA-Z]{2,}$/;
            
            if (!emailRegex.test(email)) {
                return { valid: false, message: 'Invalid email format. Please enter a valid email address.' };
            }
            
            const domain = email.split('@')[1].toLowerCase();
            
            const validDomains = [
                'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com',
                'msn.com', 'ymail.com', 'icloud.com', 'me.com', 'mac.com',
                'protonmail.com', 'proton.me', 'mail.com', 'aol.com', 'zoho.com',
                'gmx.com', 'yandex.com', 'qq.com', '163.com', '126.com',
                'sina.com', 'sohu.com', 'rediffmail.com', 'inbox.com', 'fastmail.com',
                'student.tarc.edu.my'
            ];
            
            const domainParts = domain.split('.');
            if (domainParts.length < 2) {
                return { valid: false, message: 'Invalid email domain. Please check your email address.' };
            }
            
            const tld = domainParts[domainParts.length - 1];
            if (tld.length < 2 || !/^[a-zA-Z]+$/.test(tld)) {
                return { valid: false, message: 'Invalid email domain. Please check your email address.' };
            }
            
            const commonTypos = {
                'gmali.com': 'gmail.com',
                'gmal.com': 'gmail.com',
                'gmial.com': 'gmail.com',
                'gmaill.com': 'gmail.com',
                'gmai.com': 'gmail.com',
                'hotmial.com': 'hotmail.com',
                'hotmai.com': 'hotmail.com',
                'hotmali.com': 'hotmail.com',
                'hotmial.com': 'hotmail.com',
                'yahooo.com': 'yahoo.com',
                'yaho.com': 'yahoo.com',
                'outlok.com': 'outlook.com',
                'outlok.com': 'outlook.com'
            };
            
            if (commonTypos[domain]) {
                return { valid: false, message: `Did you mean "${commonTypos[domain]}"? Please check your email address.` };
            }
            
            if (!validDomains.includes(domain)) {
                return { valid: false, message: 'Please use a valid email provider (Gmail, Hotmail, Yahoo, Outlook, iCloud, etc.).' };
            }
            
            if (email.length > 254) {
                return { valid: false, message: 'Email address is too long. Maximum length is 254 characters.' };
            }
            
            if (email.split('@')[0].length > 64) {
                return { valid: false, message: 'Email username is too long. Maximum length is 64 characters.' };
            }
            
            if (email.includes('..')) {
                return { valid: false, message: 'Invalid email format. Cannot have consecutive dots.' };
            }
            
            const localPart = email.split('@')[0];
            if (localPart.startsWith('.') || localPart.endsWith('.')) {
                return { valid: false, message: 'Invalid email format. Email cannot start or end with a dot.' };
            }
            
            return { valid: true, message: '', checkRegistered: true };
        }

        function checkEmailRegistered(email, callback) {
            $.ajax({
                url: 'check_email_exists.php',
                type: 'POST',
                data: { email: email },
                dataType: 'json',
                success: function(response) {
                    callback(response.exists);
                },
                error: function() {
                    callback(false);
                }
            });
        }

        function sendOTP() {
            const email = document.getElementById('email-input').value.trim();
            const emailError = document.getElementById('email-error');
            
            if (!email) {
                emailError.textContent = 'Please enter your email address.';
                return;
            }

            const validation = validateEmail(email);
            if (!validation.valid) {
                emailError.textContent = validation.message;
                return;
            }
            
            emailError.textContent = 'Checking email...';
            emailError.style.color = '#666';
            document.getElementById('send-otp-btn').disabled = true;
            document.getElementById('send-otp-btn').textContent = 'Checking...';
            
            checkEmailRegistered(email, function(isRegistered) {
                if (isRegistered) {
                    emailError.textContent = 'This email is already registered. Please use a different email or try logging in.';
                    emailError.style.color = '#e53935';
                    document.getElementById('send-otp-btn').disabled = false;
                    document.getElementById('send-otp-btn').textContent = 'Send Verification Code';
                    return;
                }
                
                emailError.textContent = '';
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
                            
                            startCountdown(180);
                            
                            showAlert('Code Sent!', response.message || 'Please check your email for the verification code.', 'success', 3000);
                        } else {
                            emailError.textContent = response.message || 'Failed to send verification code. Please try again.';
                            emailError.style.color = '#e53935';
                            showAlert('Error', response.message || 'Failed to send verification code. Please try again.', 'error');
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
                        
                        emailError.textContent = errorMsg;
                        emailError.style.color = '#e53935';
                        showAlert('Error', errorMsg, 'error');
                        
                        document.getElementById('send-otp-btn').disabled = false;
                        document.getElementById('send-otp-btn').textContent = 'Send Verification Code';
                    }
                });
            });
        }

        function verifyOTP() {
            const otp = document.getElementById('otp1').value +
                       document.getElementById('otp2').value +
                       document.getElementById('otp3').value +
                       document.getElementById('otp4').value +
                       document.getElementById('otp5').value +
                       document.getElementById('otp6').value;
            
            if (otp.length !== 6) {
                showAlert('Error', 'Please enter the complete 6-digit code.', 'error');
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
                        showAlert('Email Verified!', 'Please complete your registration.', 'success', 2000).then(() => {
                            window.location.reload();
                        });
                    } else {
                        showAlert('Error', response.message, 'error');
                        for (let i = 1; i <= 6; i++) {
                            document.getElementById('otp' + i).value = '';
                        }
                        document.getElementById('otp1').focus();
                    }
                    document.getElementById('verify-otp-btn').disabled = false;
                    document.getElementById('verify-otp-btn').textContent = 'Verify Code';
                },
                error: function() {
                    showAlert('Error', 'Verification failed. Please try again.', 'error');
                    document.getElementById('verify-otp-btn').disabled = false;
                    document.getElementById('verify-otp-btn').textContent = 'Verify Code';
                }
            });
        }

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

        document.addEventListener('DOMContentLoaded', function() {
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

            document.querySelectorAll('.ck-select').forEach(select => {
                const selected = select.querySelector('.ck-selected');
                const optionsBox = select.querySelector('.ck-options');
                const hiddenInput = select.querySelector('.ck-hidden-input');
                const placeholder = select.getAttribute('data-placeholder');

                const setSelectedValue = (value, text) => {
                    hiddenInput.value = value;
                    selected.textContent = text;
                    selected.style.color = 'var(--text-dark)';
                    select.classList.remove('ck-select-error');
                    if (hiddenInput.name.includes('dob_')) {
                        const dobDay = document.querySelector('[name="dob_day"]').value;
                        const dobMonth = document.querySelector('[name="dob_month"]').value;
                        const dobYear = document.querySelector('[name="dob_year"]').value;
                        const errorElement = document.getElementById('dob-error');
                        if (errorElement && dobDay && dobMonth && dobYear) {
                            errorElement.textContent = '';
                        }
                    }
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
                
                if (hiddenInput && hiddenInput.value) {
                    const placeholder = select.getAttribute('data-placeholder');
                    if (selected.textContent === placeholder || selected.textContent === 'Day' || selected.textContent === 'Month' || selected.textContent === 'Year') {
                        const matchingOption = Array.from(select.querySelectorAll('.ck-option')).find(opt => 
                            opt.getAttribute('data-value') === hiddenInput.value
                        );
                        if (matchingOption) {
                            setSelectedValue(hiddenInput.value, matchingOption.textContent);
                        } else if (hiddenInput.name === 'dob_month') {
                            const monthNames = {
                                '01': 'January', '02': 'February', '03': 'March', '04': 'April',
                                '05': 'May', '06': 'June', '07': 'July', '08': 'August',
                                '09': 'September', '10': 'October', '11': 'November', '12': 'December'
                            };
                            if (monthNames[hiddenInput.value]) {
                                setSelectedValue(hiddenInput.value, monthNames[hiddenInput.value]);
                            }
                        } else if (hiddenInput.value) {
                            setSelectedValue(hiddenInput.value, hiddenInput.value);
                        }
                    }
                }
            });

            document.addEventListener('click', e => {
                if (!e.target.closest('.ck-select')) {
                    document.querySelectorAll('.ck-options').forEach(opt => opt.style.display = 'none');
                }
            });

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
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!allowedTypes.includes(file.type)) {
                            profileImageError.textContent = 'Please upload JPG, PNG, or GIF files only.';
                            profileImageInput.value = '';
                            return;
                        }

                        if (file.size > 5000000) {
                            profileImageError.textContent = 'Image file is too large. Maximum size is 5MB.';
                            profileImageInput.value = '';
                            return;
                        }

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

            function validateField(fieldName, value) {
                const errorElement = document.getElementById(fieldName + '-error');
                const inputElement = document.querySelector('[name="' + fieldName + '"]') || document.getElementById(fieldName);
                
                if (!errorElement || !inputElement) return;
                
                let isValid = true;
                let errorMessage = '';
                
                switch(fieldName) {
                    case 'full_name':
                        if (!value || value.trim() === '') {
                            isValid = false;
                            errorMessage = 'Full name is required.';
                        } else if (value.trim().length < 2) {
                            isValid = false;
                            errorMessage = 'Full name must be at least 2 characters.';
                        } else if (value.trim().length > 100) {
                            isValid = false;
                            errorMessage = 'Full name is too long. Maximum length is 100 characters.';
                        }
                        break;
                    case 'phone':
                        const phoneDigits = value.replace(/[^0-9]/g, '');
                        if (!value || phoneDigits === '') {
                            isValid = false;
                            errorMessage = 'Phone number is required.';
                        } else if (phoneDigits.length < 9 || phoneDigits.length > 15) {
                            isValid = false;
                            errorMessage = 'Please enter a valid phone number (9-15 digits).';
                        }
                        break;
                    case 'password':
                        if (!value || value === '') {
                            isValid = false;
                            errorMessage = 'Password is required.';
                        } else if (value.length < 8) {
                            isValid = false;
                            errorMessage = 'Password must be at least 8 characters long.';
                        } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/.test(value)) {
                            isValid = false;
                            errorMessage = 'Password must contain: uppercase, lowercase, 1 digit, and 1 special character.';
                        }
                        break;
                    case 'confirm_password':
                        const passwordValue = document.querySelector('[name="password"]').value;
                        if (!value || value === '') {
                            isValid = false;
                            errorMessage = 'Please confirm your password.';
                        } else if (value !== passwordValue) {
                            isValid = false;
                            errorMessage = 'Passwords do not match.';
                        }
                        break;
                }
                
                if (isValid) {
                    inputElement.classList.remove('input-error');
                    errorElement.textContent = '';
                } else {
                    inputElement.classList.add('input-error');
                    errorElement.textContent = errorMessage;
                }
                
                return isValid;
            }
            
            const fullNameInput = document.getElementById('full_name');
            const phoneInput = document.querySelector('[name="phone"]');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (fullNameInput) {
                fullNameInput.addEventListener('blur', function() { validateField('full_name', this.value); });
                fullNameInput.addEventListener('input', function() { 
                    if (this.classList.contains('input-error')) {
                        validateField('full_name', this.value);
                    }
                });
            }
            
            if (phoneInput) {
                phoneInput.addEventListener('blur', function() { validateField('phone', this.value); });
                phoneInput.addEventListener('input', function() { 
                    if (this.classList.contains('input-error')) {
                        validateField('phone', this.value);
                    }
                });
            }
            
            if (passwordInput) {
                passwordInput.addEventListener('blur', function() { validateField('password', this.value); });
                passwordInput.addEventListener('input', function() { 
                    if (this.classList.contains('input-error')) {
                        validateField('password', this.value);
                    }
                });
            }
            
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('blur', function() { validateField('confirm_password', this.value); });
                confirmPasswordInput.addEventListener('input', function() { 
                    if (this.classList.contains('input-error')) {
                        validateField('confirm_password', this.value);
                    }
                    if (passwordInput && passwordInput.value) {
                        validateField('password', passwordInput.value);
                    }
                });
            }
            
            const genderInputs = document.querySelectorAll('input[name="gender"]');
            genderInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const errorElement = document.getElementById('gender-error');
                    if (errorElement) {
                        errorElement.textContent = '';
                    }
                });
            });
            
            const dobSelects = document.querySelectorAll('.ck-select');
            dobSelects.forEach(select => {
                const hiddenInput = select.querySelector('.ck-hidden-input');
                if (hiddenInput && hiddenInput.name.includes('dob_')) {
                    select.addEventListener('click', function() {
                        setTimeout(() => {
                            const errorElement = document.getElementById('dob-error');
                            if (errorElement && hiddenInput.value) {
                                errorElement.textContent = '';
                                select.classList.remove('ck-select-error');
                            }
                        }, 100);
                    });
                }
            });

            const form = document.getElementById('registration-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let hasErrors = false;
                    
                    if (fullNameInput && !validateField('full_name', fullNameInput.value)) hasErrors = true;
                    if (phoneInput && !validateField('phone', phoneInput.value)) hasErrors = true;
                    if (passwordInput && !validateField('password', passwordInput.value)) hasErrors = true;
                    if (confirmPasswordInput && !validateField('confirm_password', confirmPasswordInput.value)) hasErrors = true;
                    
                    const genderSelected = document.querySelector('input[name="gender"]:checked');
                    if (!genderSelected) {
                        const errorElement = document.getElementById('gender-error');
                        if (errorElement) {
                            errorElement.textContent = 'Please select your gender.';
                            hasErrors = true;
                        }
                    }
                    
                    const dobDay = document.querySelector('[name="dob_day"]').value;
                    const dobMonth = document.querySelector('[name="dob_month"]').value;
                    const dobYear = document.querySelector('[name="dob_year"]').value;
                    if (!dobDay || !dobMonth || !dobYear) {
                        const errorElement = document.getElementById('dob-error');
                        if (errorElement) {
                            errorElement.textContent = 'Please select a valid Date of Birth.';
                            hasErrors = true;
                        }
                        document.querySelectorAll('.ck-select').forEach(sel => {
                            if (sel.querySelector('.ck-hidden-input').name.includes('dob_')) {
                                sel.classList.add('ck-select-error');
                            }
                        });
                    }

                    if (profileImageInput && profileImageInput.files[0]) {
                        const imageFile = profileImageInput.files[0];
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!allowedTypes.includes(imageFile.type)) {
                            e.preventDefault();
                            showAlert('Error', 'Please upload JPG, PNG, or GIF files only.', 'error');
                            return false;
                        }
                        if (imageFile.size > 5000000) {
                            e.preventDefault();
                            showAlert('Error', 'Image file is too large. Maximum size is 5MB.', 'error');
                            return false;
                        }
                    }
                    
                    if (hasErrors) {
                        e.preventDefault();
                        showAlert('Error', 'Please correct the errors highlighted in red and try again.', 'error');
                        const firstError = document.querySelector('.input-error, .ck-select-error');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return false;
                    }
                });
            }

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

        const cancelBtn = document.getElementById('cancel-registration-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showConfirm(
                    'Cancel Registration?',
                    'Are you sure you want to cancel? Your email verification will be reset and you\'ll need to verify again.',
                    'Yes, Cancel',
                    'No, Continue',
                    'danger'
                ).then((confirmed) => {
                    if (confirmed) {
                        window.location.href = '?cancel=true';
                    }
                });
            });
        }
    </script>
</body>
</html>
