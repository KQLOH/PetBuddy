<?php
/**
 * Send OTP for Email Verification
 * Uses exact same implementation as forgot_password.php
 */

session_start();
include '../include/db.php';

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = trim($_POST['email'] ?? '');
$response = ['success' => false, 'message' => ''];

if (empty($email)) {
    $response['message'] = 'Please enter your email address.';
    echo json_encode($response);
    exit;
}

// Enhanced email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'The email format is incorrect. Please check again.';
    echo json_encode($response);
    exit;
}

// Additional validation checks
$emailParts = explode('@', $email);
if (count($emailParts) !== 2) {
    $response['message'] = 'Invalid email format. Please enter a valid email address.';
    echo json_encode($response);
    exit;
}

$localPart = $emailParts[0];
$domain = strtolower($emailParts[1]);

// Check local part length (max 64 characters)
if (strlen($localPart) > 64) {
    $response['message'] = 'Email username is too long. Maximum length is 64 characters.';
    echo json_encode($response);
    exit;
}

// Check for consecutive dots
if (strpos($email, '..') !== false) {
    $response['message'] = 'Invalid email format. Cannot have consecutive dots.';
    echo json_encode($response);
    exit;
}

// Check for dot at start or end of local part
if (substr($localPart, 0, 1) === '.' || substr($localPart, -1) === '.') {
    $response['message'] = 'Invalid email format. Email cannot start or end with a dot.';
    echo json_encode($response);
    exit;
}

// Check domain format
$domainParts = explode('.', $domain);
if (count($domainParts) < 2) {
    $response['message'] = 'Invalid email domain. Please check your email address.';
    echo json_encode($response);
    exit;
}

// Check TLD (should be at least 2 characters and only letters)
$tld = end($domainParts);
if (strlen($tld) < 2 || !preg_match('/^[a-zA-Z]+$/', $tld)) {
    $response['message'] = 'Invalid email domain. Please check your email address.';
    echo json_encode($response);
    exit;
}

// Check total email length (max 254 characters)
if (strlen($email) > 254) {
    $response['message'] = 'Email address is too long. Maximum length is 254 characters.';
    echo json_encode($response);
    exit;
}

// Check for common typos in email domains
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
    'outlok.com' => 'outlook.com',
    'outlok.com' => 'outlook.com'
];

if (isset($commonTypos[$domain])) {
    $response['message'] = 'Did you mean "' . $commonTypos[$domain] . '"? Please check your email address.';
    echo json_encode($response);
    exit;
}

// Validate against common email providers - only allow valid email providers
$validDomains = [
    'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com',
    'msn.com', 'ymail.com', 'icloud.com', 'me.com', 'mac.com',
    'protonmail.com', 'proton.me', 'mail.com', 'aol.com', 'zoho.com',
    'gmx.com', 'yandex.com', 'qq.com', '163.com', '126.com',
    'sina.com', 'sohu.com', 'rediffmail.com', 'inbox.com', 'fastmail.com'
];

if (!in_array($domain, $validDomains)) {
    $response['message'] = 'Please use a valid email provider (Gmail, Hotmail, Yahoo, Outlook, iCloud, etc.).';
    echo json_encode($response);
    exit;
}

// Check if email is already registered (check BEFORE sending OTP to save time)
$stmt = $pdo->prepare("SELECT member_id FROM members WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $response['message'] = 'This email is already registered. Please use a different email or try logging in.';
    echo json_encode($response);
    exit;
}

// Generate 6-digit OTP (same as forgot_password.php)
$otp = rand(100000, 999999);

// Store in Session (valid for 3 minutes)
$_SESSION['email_verification'] = [
    'email' => $email,
    'otp' => $otp,
    'expires_at' => time() + 180, // 3 minutes
    'attempts' => 0
];

// Send email using EXACT same implementation as forgot_password.php
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'BMIT2013IsaacLing@gmail.com';
    $mail->Password   = 'ndsf gvpz niaw czrk';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom('BMIT2013IsaacLing@gmail.com', 'PetBuddy Support');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Your OTP for Email Verification';
    $mail->Body    = "<div style='font-family: Arial; padding: 20px; border: 1px solid #ddd;'>
                        <h2>Email Verification OTP</h2>
                        <p>Your verification code is: <b style='font-size: 24px; color: #f4a261;'>$otp</b></p>
                        <p>This code will expire in 3 minutes.</p>
                      </div>";
    $mail->send();
    
    $response['success'] = true;
    $response['message'] = 'Verification code sent to your email!';
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Mailer Error: " . $mail->ErrorInfo;
}

echo json_encode($response);
?>

