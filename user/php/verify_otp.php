<?php


session_start();
include '../include/db.php';

$otp = trim($_POST['otp'] ?? '');
$response = ['success' => false, 'message' => ''];

if (empty($otp)) {
    $response['message'] = 'Please enter the verification code.';
    echo json_encode($response);
    exit;
}


if (!isset($_SESSION['email_verification'])) {
    $response['message'] = 'No verification session found. Please request a new code.';
    echo json_encode($response);
    exit;
}

$verification = $_SESSION['email_verification'];


if (time() > $verification['expires_at']) {
    unset($_SESSION['email_verification']);
    $response['message'] = 'Verification code has expired. Please request a new code.';
    echo json_encode($response);
    exit;
}


if (isset($verification['attempts']) && $verification['attempts'] >= 5) {
    unset($_SESSION['email_verification']);
    $response['message'] = 'Too many failed attempts. Please request a new code.';
    echo json_encode($response);
    exit;
}


if ($otp == $verification['otp']) {
    
    $_SESSION['email_verified'] = true;
    $_SESSION['verified_email'] = $verification['email'];
    
   
    unset($_SESSION['email_verification']);
    
    $response['success'] = true;
    $response['message'] = 'Email verified successfully!';
} else {
    
    $_SESSION['email_verification']['attempts'] = ($verification['attempts'] ?? 0) + 1;
    
    $response['message'] = 'Invalid verification code. Please check your email and try again.';
}

echo json_encode($response);
?>

