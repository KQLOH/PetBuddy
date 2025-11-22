<?php
session_start();
require '../include/db.php';

$register_error = "";
$register_success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $salutation = $_POST['salutation'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $full_name = $first_name . ' ' . $last_name;

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $register_error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $register_error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $register_error = "Email is already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO members (email, password_hash, full_name, phone) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$email, $password_hash, $full_name, $phone])) {
                $register_success = "Account created successfully! You can now login.";
            } else {
                $register_error = "Registration failed. Please try again.";
            }
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
    --text-dark: #333333;
    --text-light: #ffffff;
    --bg-light: #f9f9f9;
    --border-color: #e0e0e0;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

body {
    background-color: #ffffff;
    color: var(--text-dark);
}

a {
    text-decoration: none;
    color: inherit;
}

h1 {
    text-align: center;
    margin-top: 30px;
}

.hero-register {
    background-color: var(--primary-dark);
    padding: 20px 25px; 
    border-radius: 4px; 
    margin-bottom: 30px;
    max-width: 420px;
    margin-left: auto;
    margin-right: auto;
}

p {
    font-weight: bold; 
    margin-bottom: 10px;
}

ul {
    margin-left: 20px; 
    line-height: 1.7;
}

form {
    max-width: 420px;
    margin: 0 auto;
}

.form-group {
    position: relative;
    margin-bottom: 20px;
}

.form-input {
    width: 100%;
    height: 52px;
    padding: 18px 12px 6px;
    font-size: 16px;
    border: 1px solid #ccc;
    transition: 0.2s;
    background: none;
    box-sizing: border-box;
}

.form-input:focus {
    border-color: #000;
    outline: none;
}

.form-group label {
    position: absolute;
    top: 16px;
    left: 12px;
    color: #7a7a7a;
    font-size: 16px;
    pointer-events: none;
    transition: 0.2s ease;
}

.form-input:focus + label,
.form-input:not(:placeholder-shown) + label {
    top: 4px;
    font-size: 12px;
    color: #000;
}

.form-submit {
    width: 100%;
    background: #000;
    color: #fff;
    border: none;
    padding: 15px;
    font-size: 16px;
    cursor: pointer;
}

.google-btn {
    width: 100%;
    max-width: 420px;
    margin: 0 auto 15px;
    display: block;
    padding: 12px;
    border: 1px solid #000;
    background: #ffffff;
    cursor: pointer;
    font-size: 16px;
}

.dividingline {
    margin: 25px auto; 
    text-align: center; 
    color: #999;
}

.label-bold {
    font-weight: bold;
}

.salutation-group {
    margin: 10px 0 20px;
    color: black;
}

.salutation-option {
    margin-right: 20px;
}

.salutation-option input[type="radio"] {
    accent-color: black;
}

.mobile-row {
    display: flex;
    gap: 10px;
}

.mobile-left {
    flex: 0 0 60px;
}

.mobile-left .form-input {
    text-align: center;
}

.mobile-right {
    flex: 1;
    position: relative;
}

.mobile-right .form-input {
    text-align: left;
    height: 52px;
    padding: 18px 12px 6px;
    box-sizing: border-box;
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

.dob-row {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.ck-select {
    position: relative;
    flex: 1;
    height: 52px;
    border: 1px solid #ccc;
    padding: 0 12px;
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
    background: #fff;
    box-sizing: border-box;
}

.ck-select::after {
    content: "";
    position: absolute;
    right: 12px;
    width: 12px;
    height: 12px;
    background-image: url('data:image/svg+xml;utf8,<svg fill="%23333" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
    background-size: cover;
}

.ck-options {
    position: absolute;
    top: 52px;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ccc;
    max-height: 220px;
    overflow-y: auto;
    display: none;
    z-index: 99;
}

.ck-option {
    padding: 12px;
    cursor: pointer;
}

.ck-option:hover {
    background: #f2f2f2;
}
</style>
</head>
<body>

<?php include '../include/header.php'; ?>
<?php if($register_error): ?>
    <div style="color: red; text-align:center; margin-bottom: 15px;"><?= $register_error ?></div>
<?php endif; ?>

<?php if($register_success): ?>
    <div style="color: green; text-align:center; margin-bottom: 15px;"><?= $register_success ?></div>
<?php endif; ?>

<h1>Create an Account</h1>
<div class="hero-register">
    <p>Join us now!! Members get:</p>
    <ul>
        <li>Welcome Offer</li>
        <li>Birthday Privilege</li>
        <li>Exclusive Invites & News</li>
        <li>Fast, easy checkout</li>
    </ul>
</div>

<div class="dividingline">
    <span>────────────────────────────────────</span>
</div>

<button class="google-btn">
    <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="20" style="vertical-align: middle; margin-right: 8px;">
    Sign Up with Google
</button>

<div class="dividingline">
    <span>──────────────── OR ────────────────</span>
</div>

<form method="POST" action="">

<label class="label-bold">Salutation*</label>
<div class="salutation-group">
    <label class="salutation-option">
        <input type="radio" name="salutation"> Mr.
    </label>
    <label class="salutation-option">
        <input type="radio" name="salutation"> Ms.
    </label>
</div>

<div class="form-group">
    <input type="text" class="form-input" name="first_name" required placeholder=" ">
    <label>First Name*</label>
</div>

<div class="form-group">
    <input type="text" class="form-input" name="last_name" required placeholder=" ">
    <label>Last Name*</label>
</div>

<div class="form-group">
    <input type="email" class="form-input" name="email" required placeholder=" ">
    <label>Email*</label>
</div>

<label class="label-bold">Mobile Number*</label>
<div class="mobile-row">  
    <div class="form-group mobile-left">
        <input type="text" class="form-input" value="+60" readonly>
    </div>
    <div class="form-group mobile-right">
        <input type="text" class="form-input" name="phone" required placeholder=" ">
        <label>Mobile Number*</label>
    </div>
</div>

<div class="form-group password-group">
    <input type="password" class="form-input" name="password" required placeholder=" ">
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

<div class="form-group">
    <input type="password" class="form-input" name="confirm_password" required placeholder=" ">
    <label>Confirm Password*</label>
</div>

<label class="label-bold">Date of Birth*</label>
<div class="dob-row">

    <div class="ck-select" data-placeholder="Day">
        <div class="ck-selected">Day</div>
        <div class="ck-options">
            <script>
                for (let i = 1; i <= 31; i++) {
                    document.write(`<div class="ck-option">${i}</div>`);
                }
            </script>
        </div>
    </div>

    <div class="ck-select" data-placeholder="Month">
        <div class="ck-selected">Month</div>
        <div class="ck-options">
            <div class="ck-option">January</div>
            <div class="ck-option">February</div>
            <div class="ck-option">March</div>
            <div class="ck-option">April</div>
            <div class="ck-option">May</div>
            <div class="ck-option">June</div>
            <div class="ck-option">July</div>
            <div class="ck-option">August</div>
            <div class="ck-option">September</div>
            <div class="ck-option">October</div>
            <div class="ck-option">November</div>
            <div class="ck-option">December</div>
        </div>
    </div>

    <div class="ck-select" data-placeholder="Year">
        <div class="ck-selected">Year</div>
        <div class="ck-options">
            <script>
                for (let y = 2025; y >= 1900; y--) {
                    document.write(`<div class="ck-option">${y}</div>`);
                }
            </script>
        </div>
    </div>

</div>

<button class="form-submit">Create Account</button>
</form>

<?php include '../include/footer.php'; ?>

<script>
document.querySelectorAll('.ck-select').forEach(select => {
    const selected = select.querySelector('.ck-selected');
    const optionsBox = select.querySelector('.ck-options');
    const options = select.querySelectorAll('.ck-option');

    select.onclick = (e) => {
        if (e.target.classList.contains('ck-option')) return;

        const isOpen = optionsBox.style.display === 'block';
        document.querySelectorAll('.ck-options').forEach(opt => opt.style.display = 'none');
        optionsBox.style.display = isOpen ? 'none' : 'block';
    };

    options.forEach(option => {
        option.onclick = () => {
            options.forEach(o => o.classList.remove('selected'));
            option.classList.add('selected');
            selected.textContent = option.textContent;
            optionsBox.style.display = 'none';
        };
    });
});

document.addEventListener('click', e => {
    if (!e.target.closest('.ck-select')) {
        document.querySelectorAll('.ck-options').forEach(opt => opt.style.display = 'none');
    }
});
</script>

</body>
</html>

