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

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'The email format is incorrect. Please check again.';
    echo json_encode($response);
    exit;
}

// Check if email is already registered (opposite of forgot_password - we want it NOT to exist)
$stmt = $pdo->prepare("SELECT member_id FROM members WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $response['message'] = 'This email is already registered.';
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

