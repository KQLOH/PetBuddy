<?php
/**
 * Verify OTP for Email Verification
 * Used in registration process
 * Matches the implementation pattern from forgot_password.php
 */

session_start();
include '../include/db.php';

$otp = trim($_POST['otp'] ?? '');
$response = ['success' => false, 'message' => ''];

if (empty($otp)) {
    $response['message'] = 'Please enter the verification code.';
    echo json_encode($response);
    exit;
}

// Check if OTP session exists
if (!isset($_SESSION['email_verification'])) {
    $response['message'] = 'No verification session found. Please request a new code.';
    echo json_encode($response);
    exit;
}

$verification = $_SESSION['email_verification'];

// Check if OTP has expired (3 minutes = 180 seconds)
if (time() > $verification['expires_at']) {
    unset($_SESSION['email_verification']);
    $response['message'] = 'Verification code has expired. Please request a new code.';
    echo json_encode($response);
    exit;
}

// Check if too many attempts (optional security feature)
if (isset($verification['attempts']) && $verification['attempts'] >= 5) {
    unset($_SESSION['email_verification']);
    $response['message'] = 'Too many failed attempts. Please request a new code.';
    echo json_encode($response);
    exit;
}

// Verify OTP
if ($otp == $verification['otp']) {
    // OTP is correct - set verified status
    $_SESSION['email_verified'] = true;
    $_SESSION['verified_email'] = $verification['email'];
    
    // Clear the verification session
    unset($_SESSION['email_verification']);
    
    $response['success'] = true;
    $response['message'] = 'Email verified successfully!';
} else {
    // Increment attempts counter
    $_SESSION['email_verification']['attempts'] = ($verification['attempts'] ?? 0) + 1;
    
    $response['message'] = 'Invalid verification code. Please check your email and try again.';
}

echo json_encode($response);
?>

