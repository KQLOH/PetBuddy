<?php
session_start();
include '../include/db.php';

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';
$step = isset($_POST['step']) ? $_POST['step'] : 'request_otp';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($step == 'send_otp') {
        $email = trim($_POST['email']);

        if (empty($email)) {
            $error = "Please enter your email address.";
            $step = 'request_otp';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "The email format is incorrect. Please check again.";
            $step = 'request_otp';
        } else {
            $stmt = $pdo->prepare("SELECT member_id FROM members WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $otp = rand(100000, 999999);
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['otp_time'] = time();

$mail = new PHPMailer(true);
try {
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
                    $mail->Subject = 'Your OTP for Password Reset';
                    $mail->Body    = "<div style='font-family: Arial; padding: 20px; border: 1px solid #ddd;'>
                                        <h2>Password Reset OTP</h2>
                                        <p>Your verification code is: <b style='font-size: 24px; color: #f4a261;'>$otp</b></p>
                                        <p>This code will expire in 10 minutes.</p>
                                      </div>";
                    $mail->send();
                    $step = 'verify_otp';
                    $message = "OTP has been sent to your email.";
                } catch (Exception $e) {
                    $error = "Mailer Error: " . $mail->ErrorInfo;
                    $step = 'request_otp';
                }
            } else {
                $error = "We couldn't find an account with that email address.";
                $step = 'request_otp';
            }
        }
    } elseif ($step == 'check_otp') {
        $user_otp = $_POST['otp'];
        if (time() - $_SESSION['otp_time'] > 600) {
            $error = "OTP expired. Please try again.";
            $step = 'request_otp';
        } elseif ($user_otp == $_SESSION['reset_otp']) {
            $step = 'new_password';
        } else {
            $error = "Invalid OTP. Please check your email.";
            $step = 'verify_otp';
        }
    } elseif ($step == 'update_password') {
        $pass = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if ($pass !== $confirm) {
            $error = "Passwords do not match. Please try again.";
            $step = 'new_password';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pass)) {
            $error = "Password must be min 8 chars with mixed cases, 1 digit, and 1 special char.";
            $step = 'new_password';
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE members SET password_hash = ? WHERE email = ?");
            $update->execute([$hashed, $_SESSION['reset_email']]);

            unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['otp_time']);
            $step = 'success';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password - PetBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .page-content-wrapper {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .header-logo {
            height: 1.8rem;
            vertical-align: bottom;
            margin-right: 0.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .card-header {
            text-align: center;
        }

        .btn-success-login {
            display: block;
            text-align: center;
            text-decoration: none;
        }

        .card-footer-nav {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #eee;
            padding-top: 20px;
            margin-top: 2rem;
        }

        .card-footer-nav a {
            font-size: 0.9rem;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <?php include '../include/header.php'; ?>
    <div class="page-content-wrapper">
        <div class="card max-w-md">
            <div class="card-header">
                <h1><img src="../images/pawprint.png" alt="Logo" class="header-logo"> PetBuddy</h1>
            </div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
                <?php if ($message): ?><div class="alert-success"><?php echo $message; ?></div><?php endif; ?>

                <?php if ($step == 'request_otp'): ?>
                    <form method="POST" novalidate>
                        <input type="hidden" name="step" value="send_otp">
                        <label class="form-label">Enter Email</label>
                        <input type="email" name="email" class="form-input" placeholder="you@example.com">
                        <button type="submit" class="btn-primary mt-4">Send OTP</button>
                    </form>

                <?php elseif ($step == 'verify_otp'): ?>
                    <form method="POST" novalidate>
                        <input type="hidden" name="step" value="check_otp">
                        <label class="form-label">Enter 6-Digit OTP</label>
                        <input type="text" name="otp" class="form-input" maxlength="6" placeholder="000000">
                        <button type="submit" class="btn-primary mt-4">Verify OTP</button>
                    </form>

                <?php elseif ($step == 'new_password'): ?>
                    <form method="POST" novalidate>
                        <input type="hidden" name="step" value="update_password">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-input" placeholder="Min 8 characters">

                        <label class="form-label mt-4">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" placeholder="Re-type your password">

                        <button type="submit" class="btn-primary mt-4">Update Password</button>
                    </form>

                <?php elseif ($step == 'success'): ?>
                    <div class="alert-success">Password updated successfully!</div>
                    <a href="login.php" class="btn-primary btn-success-login">Login Now</a>
                <?php endif; ?>

                <div class="card-footer-nav">
                    <a href="login.php" class="link-primary">Back to Login</a>
                    <a href="index.php" class="link-muted">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
    <?php include '../include/footer.php'; ?>
</body>

</html>