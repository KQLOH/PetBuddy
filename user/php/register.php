<?php
session_start();
require '../include/db.php';

$register_error = "";
$register_success = "";

$gender = $_POST['gender'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$dob_day = $_POST['dob_day'] ?? '';
$dob_month = $_POST['dob_month'] ?? '';
$dob_year = $_POST['dob_year'] ?? '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Capture and Sanitize Inputs
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Sanitize non-password fields
    $first_name = trim($first_name);
    $last_name = trim($last_name);
    $email = trim($email);
    $phone = trim($phone);

    $full_name = $first_name . ' ' . $last_name;

    // Determine Gender for DB insertion
    $db_gender = null;
    $submitted_gender_lower = strtolower($gender);

    if ($submitted_gender_lower === 'male' || $submitted_gender_lower === 'female') {
        $db_gender = $submitted_gender_lower;
    }

    // Format DOB: YYYY-MM-DD
    $dob = null;
    if ($dob_day && $dob_month && $dob_year) {
        $dob_date_string = "$dob_year-$dob_month-$dob_day";
        // Simple check for valid date format
        if (checkdate((int)$dob_month, (int)$dob_day, (int)$dob_year)) {
            $dob = $dob_date_string;
        }
    }

    // 2. Server-Side Validation (Minimal check before DB query/insertion)
    $server_side_valid = true;

    // Check required fields (basic PHP side check)
    if (empty($gender) || empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($phone)) {
        $register_error = "Please fill in all required fields.";
        $server_side_valid = false;
    } elseif ($password !== $confirm_password) {
        $register_error = "Passwords do not match.";
        $server_side_valid = false;
    } elseif (strlen($password) < 8) {
        $register_error = "Password must be at least 8 characters long.";
        $server_side_valid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Invalid email format.";
        $server_side_valid = false;
    } elseif (!$dob) {
        $register_error = "Please select a valid Date of Birth.";
        $server_side_valid = false;
    }
    // Additional server-side check for password complexity (as requested):
    // Requires: mixed case, 1 digit, 1 special character
    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
        $register_error = "Password must be at least 8 characters long, contain mixed letter cases, 1 digit, and 1 special character.";
        $server_side_valid = false;
    }

    if ($server_side_valid) {
        // 3. Check for existing email
        try {
            $stmt = $pdo->prepare("SELECT email FROM members WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $register_error = "Email is already registered.";
            } else {
                // 4. Register User
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Updated statement to include gender and dob
                $sql = "INSERT INTO members (email, password_hash, full_name, phone, gender, dob) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);

                // Use $db_gender for insertion
                if ($stmt->execute([$email, $password_hash, $full_name, $phone, $db_gender, $dob])) {
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

    <style>
        :root {
            --primary-color: #F4A261;
            --primary-dark: #E68E3F;
            --bg-light: #f9f9f9;
            --text-dark: #333333;
            --border-color: #e0e0e0;
            --danger-color: #e53935;
            --secondary-color: #2EC4B6;
            --text-gray: #666666;
            --white: #ffffff;
            --danger-bg: #fee2e2;
            --danger-text: #b91c1c;
            --success-bg: #d1fae5;
            --success-border: #10b981;
            --success-text: #065f46;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #fff7ec, #ffffff);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
        }

        .page-wrapper {
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
            flex-grow: 1;
        }

        .w-full {
            width: 100%;
        }

        .max-w-md {
            max-width: 450px;
            width: 100%;
        }

        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .mb-6 {
            margin-bottom: 1.5rem;
        }

        .card {
            background-color: var(--white);
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .card-body {
            padding: 2rem;
        }

        .alert-error {
            background-color: var(--danger-bg);
            border-left: 4px solid #ef4444;
            color: var(--danger-text);
            padding: 1rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        /* --- ADDED STYLE FOR SUCCESS MESSAGE --- */
        .alert-success {
            background-color: var(--success-bg);
            border-left: 4px solid var(--success-border);
            color: var(--success-text);
            padding: 1rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        /* --- ADDED STYLE FOR WELCOME MESSAGE BOX --- */
        .welcome-info-box {
            background-color: var(--bg-light);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .welcome-info-box p {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .welcome-info-box ul {
            list-style: disc;
            margin-left: 20px;
            color: #555;
            font-size: 14px;
        }

        /* --- ADDED STYLE FOR OR DIVIDER --- */
        .divider-or {
            margin: 1.5rem 0;
            color: #999;
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

        .link-muted {
            color: #9ca3af;
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .link-muted:hover {
            color: var(--text-dark);
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

        .form-group-float {
            position: relative;
            margin-bottom: 20px;
        }

        .form-input-float {
            width: 100%;
            height: 52px;
            padding: 18px 12px 6px;
            font-size: 16px;
            border: 1px solid #ccc;
            transition: 0.2s;
            background: none;
            box-sizing: border-box;
            border-radius: 0.5rem;
        }

        .form-input-float:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(244, 162, 97, 0.3);
            outline: none;
        }

        .form-group-float label {
            position: absolute;
            top: 16px;
            left: 12px;
            color: #7a7a7a;
            font-size: 16px;
            pointer-events: none;
            transition: 0.2s ease;
        }

        .form-input-float:focus+label,
        .form-input-float:not(:placeholder-shown)+label:not(.ck-selected) {
            top: 4px;
            font-size: 12px;
            color: var(--primary-dark);
            font-weight: 600;
        }

        .mobile-row {
            display: flex;
        }

        .mobile-left {
            flex: 0 0 55px;
        }

        .mobile-row .mobile-left {
            position: relative;
            left: -4px;
        }

        .mobile-left .form-input-float {
            padding: 0;
            text-align: center;
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

        /* --- ADDED STYLE FOR PASSWORD HINT LIST --- */
        .password-hint ul {
            margin-left: 20px;
            color: #555;
            font-size: 12px;
        }

        .dob-row {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .ck-select {
            flex: 1;
            height: 52px;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0 12px;
            display: flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
            background: #fff;
            box-sizing: border-box;
            font-size: 16px;
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
            color: var(--text-dark);
        }

        .ck-select[data-placeholder="Day"] .ck-selected,
        .ck-select[data-placeholder="Month"] .ck-selected,
        .ck-select[data-placeholder="Year"] .ck-selected {
            color: #7a7a7a;
        }

        .ck-options {
            position: absolute;
            top: 52px;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ccc;
            max-height: 120px;
            overflow-y: auto;
            display: none;
            z-index: 100;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .ck-option {
            padding: 10px 12px;
            color: var(--text-dark);
            font-size: 16px;
            cursor: pointer;
        }

        .ck-option:hover {
            background: var(--bg-light);
        }

        /* --- VALIDATION STYLES --- */
        .error-message {
            font-size: 0.75rem;
            color: var(--danger-color);
            margin-top: -10px;
            margin-bottom: 20px;
            min-height: 15px;
        }

        .error-border {
            border-color: var(--danger-color) !important;
        }

        .error-label {
            color: var(--danger-color) !important;
        }
    </style>
</head>

<body>
    <?php include '../include/header.php'; ?>

    <div class="page-wrapper">
        <div class="max-w-md w-full">
            <div class="card">

                <div class="card-body">

                    <?php if ($register_error): ?>
                        <div class="alert-error" role="alert"><?= htmlspecialchars($register_error) ?></div>
                    <?php endif; ?>

                    <?php if ($register_success): ?>
                        <div class="alert-success" role="alert"><?= htmlspecialchars($register_success) ?></div>
                    <?php endif; ?>

                    <div class="welcome-info-box">
                        <p>Join us now!! Members get:</p>
                        <ul>
                            <li>Welcome Offer</li>
                            <li>Birthday Privilege</li>
                            <li>Exclusive Invites & News</li>
                            <li>Fast, easy checkout</li>
                        </ul>
                    </div>

                    <div class="text-center divider-or">
                        <span>——————————</span>
                    </div>

                    <form method="POST" action="" id="registration-form" novalidate>

                        <label class="form-label">Gender*</label>
                        <div class="salutation-group" id="gender-group">
                            <label class="salutation-option">
                                <input type="radio" name="gender" value="male" required <?php if ($gender === 'male') echo 'checked'; ?>> Male
                            </label>
                            <label class="salutation-option">
                                <input type="radio" name="gender" value="female" required <?php if ($gender === 'female') echo 'checked'; ?>> Female
                            </label>
                            <label class="salutation-option">
                                <input type="radio" name="gender" value="prefer not to say" required <?php if ($gender === 'prefer not to say') echo 'checked'; ?>> Prefer not to say
                            </label>
                        </div>
                        <div class="error-message" id="gender-error"></div>

                        <div class="form-group-float">
                            <input type="text" class="form-input-float" name="first_name" required placeholder=" " value="<?= htmlspecialchars($first_name) ?>">
                            <label>First Name*</label>
                        </div>
                        <div class="error-message" id="first_name-error"></div>

                        <div class="form-group-float">
                            <input type="text" class="form-input-float" name="last_name" required placeholder=" " value="<?= htmlspecialchars($last_name) ?>">
                            <label>Last Name*</label>
                        </div>
                        <div class="error-message" id="last_name-error"></div>

                        <div class="form-group-float">
                            <input type="email" class="form-input-float" name="email" required placeholder=" " value="<?= htmlspecialchars($email) ?>">
                            <label>Email*</label>
                        </div>
                        <div class="error-message" id="email-error"></div>

                        <label class="form-label">Mobile Number*</label>
                        <div class="mobile-row">  
                            <div class="form-group-float mobile-left">
                                <input type="text" class="form-input-float" value="+60" readonly>
                            </div>
                            <div class="form-group-float mobile-right">
                                <input type="text" class="form-input-float" name="phone" required placeholder=" " value="<?= htmlspecialchars($phone) ?>">
                                <label>Mobile Number*</label>
                            </div>
                        </div>
                        <div class="error-message" id="phone-error"></div>

                        <div class="form-group-float password-group">
                            <input type="password" class="form-input-float" name="password" id="password" required placeholder=" ">
                            <label>Password*</label>
                            <div class="password-hint">
                                <ul>
                                    <li>Min. of 8 characters</li>
                                    <li>Mixed letter cases</li>
                                    <li>1 digit</li>
                                    <li>1 special character</li>
                                </ul>
                            </div>
                        </div>
                        <div class="error-message" id="password-error"></div>

                        <div class="form-group-float">
                            <input type="password" class="form-input-float" name="confirm_password" id="confirm_password" required placeholder=" ">
                            <label>Confirm Password*</label>
                        </div>
                        <div class="error-message" id="confirm_password-error"></div>

                        <label class="form-label">Date of Birth*</label>
                        <div class="dob-row" id="dob-row">

                            <div class="ck-select" data-placeholder="Day">
                                <div class="ck-selected">Day</div>
                                <input type="hidden" name="dob_day" class="ck-hidden-input" required value="<?= htmlspecialchars($dob_day) ?>">
                                <div class="ck-options" id="ck-day-options">
                                </div>
                            </div>

                            <div class="ck-select" data-placeholder="Month">
                                <div class="ck-selected">Month</div>
                                <input type="hidden" name="dob_month" class="ck-hidden-input" required value="<?= htmlspecialchars($dob_month) ?>">
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
                                <input type="hidden" name="dob_year" class="ck-hidden-input" required value="<?= htmlspecialchars($dob_year) ?>">
                                <div class="ck-options" id="ck-year-options">
                                </div>
                            </div>

                        </div>
                        <div class="error-message" id="dob-error"></div>

                        <button type="submit" class="btn-primary btn-hover">Create Account</button>
                    </form>

                    <div class="mt-4 text-center">
                        <a href="login.php" class="link-muted">Already have an account? Login here.</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <?php include '../include/footer.php'; ?>


    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // --- 1. SETUP (Populate Days and Years, Custom Select Logic) ---
            // (This section remains largely the same)

            // Populate Days (1-31)
            const dayOptions = document.getElementById('ck-day-options');
            let dayHtml = '';
            for (let i = 1; i <= 31; i++) {
                const dayValue = i < 10 ? `0${i}` : `${i}`;
                dayHtml += `<div class="ck-option" data-value="${dayValue}">${i}</div>`;
            }
            if (dayOptions) dayOptions.innerHTML = dayHtml;

            // Populate Years (Current Year down to 1900)
            const yearOptions = document.getElementById('ck-year-options');
            let yearHtml = '';
            for (let y = new Date().getFullYear(); y >= 1900; y--) {
                yearHtml += `<div class="ck-option" data-value="${y}">${y}</div>`;
            }
            if (yearOptions) yearOptions.innerHTML = yearHtml;


            // Initialize Custom Select Logic (ck-select)
            document.querySelectorAll('.ck-select').forEach(select => {
                const selected = select.querySelector('.ck-selected');
                const optionsBox = select.querySelector('.ck-options');
                const hiddenInput = select.querySelector('.ck-hidden-input');
                const placeholder = select.getAttribute('data-placeholder');

                const setSelectedValue = (value, text) => {
                    hiddenInput.value = value;
                    selected.textContent = text;
                    selected.style.color = 'var(--text-dark)';

                    select.querySelectorAll('.ck-option').forEach(o => o.classList.remove('selected'));
                    select.querySelector(`.ck-option[data-value="${value}"]`)?.classList.add('selected');
                };

                // Handle pre-selected values (for sticky form on error)
                if (hiddenInput.value) {
                    let selectedOption = select.querySelector(`.ck-option[data-value="${hiddenInput.value}"]`);
                    if (selectedOption) {
                        setSelectedValue(hiddenInput.value, selectedOption.textContent);
                    } else {
                        if (select.getAttribute('data-placeholder') === 'Month') {
                            const monthMap = {
                                '01': 'January',
                                '02': 'February',
                                '03': 'March',
                                '04': 'April',
                                '05': 'May',
                                '06': 'June',
                                '07': 'July',
                                '08': 'August',
                                '09': 'September',
                                '10': 'October',
                                '11': 'November',
                                '12': 'December'
                            };
                            selected.textContent = monthMap[hiddenInput.value] || placeholder;
                            selected.style.color = monthMap[hiddenInput.value] ? 'var(--text-dark)' : '#7a7a7a';
                        } else {
                            selected.textContent = hiddenInput.value;
                            selected.style.color = 'var(--text-dark)';
                        }
                    }
                } else {
                    selected.textContent = placeholder;
                    selected.style.color = '#7a7a7a';
                }

                select.addEventListener('click', (e) => {
                    const isOpen = optionsBox.style.display === 'block';
                    document.querySelectorAll('.ck-options').forEach(opt => opt.style.display = 'none');
                    document.querySelectorAll('.ck-select').forEach(sel => sel.classList.remove('active'));

                    optionsBox.style.display = isOpen ? 'none' : 'block';
                    if (!isOpen) select.classList.add('active');

                    e.stopPropagation();
                });

                select.querySelectorAll('.ck-option').forEach(option => {
                    option.onclick = (e) => {
                        const value = option.getAttribute('data-value') || option.textContent;
                        setSelectedValue(value, option.textContent);

                        optionsBox.style.display = 'none';
                        select.classList.remove('active');

                        // *** ADD THIS LINE TO STOP THE CLICK FROM REOPENING THE BOX ***
                        if (e) e.stopPropagation();

                        // --- Validation logic ---
                        if (form.classList.contains('submitted')) {
                            checkSpecificField(hiddenInput);
                        }
                    };
                });
            });

            document.addEventListener('click', e => {
                if (!e.target.closest('.ck-select')) {
                    document.querySelectorAll('.ck-options').forEach(opt => opt.style.display = 'none');
                    document.querySelectorAll('.ck-select').forEach(sel => sel.classList.remove('active'));
                }
            });

            // --- 2. VALIDATION UTILITIES ---

            const form = document.getElementById('registration-form');
            let hasSubmitted = false; // Tracks if the form has ever been submitted and failed

            const displayError = (field, message, isSelect = false) => {
                const errorDiv = document.getElementById(`${field}-error`);
                const input = document.querySelector(`[name="${field}"]`);

                if (errorDiv) {
                    errorDiv.textContent = message;
                }

                if (isSelect) {
                    const dobRow = document.getElementById('dob-row');
                    if (dobRow) {
                        if (message) {
                            dobRow.classList.add('error-border');
                        } else {
                            dobRow.classList.remove('error-border');
                        }
                    }
                } else if (field === 'gender') {
                    const genderGroup = document.getElementById('gender-group');
                    if (genderGroup) {
                        if (message) {
                            genderGroup.style.border = '1px solid var(--danger-color)';
                            genderGroup.style.borderRadius = '0.5rem';
                            genderGroup.style.padding = '5px';
                        } else {
                            genderGroup.style.border = 'none';
                            genderGroup.style.padding = '0';
                        }
                    }
                } else if (input) {
                    if (message) {
                        input.classList.add('error-border');
                    } else {
                        input.classList.remove('error-border');
                    }
                }
                return !!message; // Returns true if there is an error message
            };

            const validatePassword = (password) => {
                let errors = [];
                if (password.length < 8) errors.push("Min. of 8 characters");
                if (!/(?=.*[a-z])(?=.*[A-Z])/.test(password)) errors.push("Mixed letter cases");
                if (!/(?=.*\d)/.test(password)) errors.push("1 digit");
                if (!/(?=.*[^A-Za-z0-9])/.test(password)) errors.push("1 special character");
                return errors;
            };

            // Function to check a single field or group
            const checkSpecificField = (inputElement) => {
                const fieldName = inputElement.name;
                const value = inputElement.value ? inputElement.value.trim() : '';
                let errorMessage = '';

                // 1. Gender (special handling for group)
                if (fieldName === 'gender') {
                    if (!form.querySelector('[name="gender"]:checked')) {
                        errorMessage = 'Please select your gender.';
                    }
                    displayError('gender', errorMessage, false);
                    return !errorMessage;

                    // 2. First/Last Name
                } else if (fieldName === 'first_name' || fieldName === 'last_name') {
                    if (value === '') {
                        errorMessage = `${fieldName.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')} is required.`;
                    }
                    displayError(fieldName, errorMessage, false);
                    return !errorMessage;

                    // 3. Email
                } else if (fieldName === 'email') {
                    if (value === '') {
                        errorMessage = 'Email is required.';
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        errorMessage = 'Invalid email format (e.g., user@domain.com).';
                    }
                    displayError('email', errorMessage, false);
                    return !errorMessage;

                    // 4. Phone
                } else if (fieldName === 'phone') {
                    if (value === '') {
                        errorMessage = 'Mobile number is required.';
                    } else if (!/^\d{7,15}$/.test(value)) {
                        errorMessage = 'Invalid mobile number format (digits only).';
                    }
                    displayError('phone', errorMessage, false);
                    return !errorMessage;

                    // 5. Password
                } else if (fieldName === 'password') {
                    const passwordErrors = validatePassword(value);
                    if (value === '') {
                        errorMessage = 'Password is required.';
                    } else if (passwordErrors.length > 0) {
                        errorMessage = 'Password is too weak. Must meet criteria: ' + passwordErrors.join(', ');
                    }
                    displayError('password', errorMessage, false);
                    // Also re-check confirm password if password is changed
                    checkSpecificField(form.querySelector('[name="confirm_password"]'));
                    return !errorMessage;

                    // 6. Confirm Password
                } else if (fieldName === 'confirm_password') {
                    const password = form.querySelector('[name="password"]').value;
                    if (value === '') {
                        errorMessage = 'Confirm password is required.';
                    } else if (password !== value) {
                        errorMessage = 'Passwords do not match.';
                    }
                    displayError('confirm_password', errorMessage, false);
                    return !errorMessage;

                    // 7. DOB (Need to check all three inputs together)
                } else if (fieldName === 'dob_day' || fieldName === 'dob_month' || fieldName === 'dob_year') {
                    const day = form.querySelector('[name="dob_day"]').value;
                    const month = form.querySelector('[name="dob_month"]').value;
                    const year = form.querySelector('[name="dob_year"]').value;

                    if (!day || !month || !year) {
                        errorMessage = 'Please select a complete Date of Birth.';
                    } else {
                        const dateStr = `${year}-${month}-${day}`;
                        const dob = new Date(dateStr);
                        if (isNaN(dob.getTime()) || dob.getDate() != parseInt(day) || dob.getMonth() + 1 != parseInt(month)) {
                            errorMessage = 'Invalid calendar date selected (e.g., February 30).';
                        } else if (dob > new Date()) {
                            errorMessage = "Date of Birth cannot be in the future.";
                        }
                    }
                    displayError('dob', errorMessage, true);
                    return !errorMessage;
                }
                return true;
            };


            // --- 3. CORE SUBMISSION LOGIC (Checks ALL fields) ---

            const runValidation = (e) => {
                let isValid = true;

                // 1. Set the flag: the form has been submitted and validation is now active.
                form.classList.add('submitted');

                // 2. Clear all previous errors (before running checks)
                document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
                document.querySelectorAll('.form-input-float').forEach(el => el.classList.remove('error-border'));
                document.getElementById('dob-row').classList.remove('error-border');
                document.getElementById('gender-group').style.border = 'none';
                document.getElementById('gender-group').style.padding = '0';

                // 3. Run all specific field checks
                // Check all floating inputs
                document.querySelectorAll('.form-input-float').forEach(input => {
                    // Pass the input element to checkSpecificField
                    if (!checkSpecificField(input)) {
                        isValid = false;
                    }
                });

                // Check Gender
                if (!checkSpecificField(form.querySelector('[name="gender"]'))) {
                    isValid = false;
                }

                // Check DOB (checkSpecificField handles the three parts)
                if (!checkSpecificField(form.querySelector('[name="dob_day"]'))) {
                    isValid = false;
                }

                if (e && e.preventDefault && !isValid) {
                    e.preventDefault(); // Stop the form from submitting on button click
                }

                return isValid;
            };


            // --- 4. ATTACH LISTENERS ---

            // A. Submit Listener (Main Trigger)
            form.addEventListener('submit', runValidation);

            // B. Fix-to-Clear Listeners (Micro-Validation)
            // Run single field validation, but only if the form has been submitted and failed once.

            // Floating Inputs (Text, Email, Phone, Password)
            document.querySelectorAll('.form-input-float').forEach(input => {
                input.addEventListener('input', () => {
                    if (form.classList.contains('submitted')) {
                        checkSpecificField(input);
                    }
                });
            });

            // Radio Buttons (Gender)
            document.querySelectorAll('[name="gender"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    if (form.classList.contains('submitted')) {
                        checkSpecificField(radio);
                    }
                });
            });

            // Password hint show/hide logic
            document.querySelectorAll('.form-input-float[name="password"]').forEach(input => {
                const hint = input.closest('.password-group').querySelector('.password-hint');

                input.addEventListener('focus', () => {
                    if (hint) hint.style.display = 'block';
                });

                input.addEventListener('blur', () => {
                    setTimeout(() => {
                        if (hint && !input.closest('.password-group').querySelector(':focus')) {
                            hint.style.display = 'none';
                        }
                    }, 100);
                });
            });
        });
    </script>

</body>

</html>