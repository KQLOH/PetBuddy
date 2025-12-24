<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

$smtp_username = 'BMIT2013IsaacLing@gmail.com';
$smtp_password = 'ndsf gvpz niaw czrk';
$recipient_email = 'BMIT2013IsaacLing@gmail.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name)) {
        header('Location: contact.php?status=error&field=name&msg=' . urlencode('Please enter your name.'));
        exit;
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: contact.php?status=error&field=email&msg=' . urlencode('Please enter a valid email address.'));
        exit;
    }

    if (empty($subject)) {
        header('Location: contact.php?status=error&field=subject&msg=' . urlencode('Please enter a subject.'));
        exit;
    }

    if (empty($message)) {
        header('Location: contact.php?status=error&field=message&msg=' . urlencode('Message content cannot be empty.'));
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom($smtp_username, 'PetBuddy Contact Form');
        $mail->addAddress($recipient_email, 'PetBuddy Support');
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'New PetBuddy Website Inquiry: ' . htmlspecialchars($subject);

        $body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h2 style='color: #FFB774;'>New Contact Information from PetBuddy Website</h2>
                <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <h3>Message Content:</h3>
                <div style='background-color: #f7f7f7; padding: 15px; border-radius: 5px;'>
                    <p style='white-space: pre-wrap; margin: 0;'>" . htmlspecialchars($message) . "</p>
                </div>
            </body>
            </html>
        ";

        $mail->Body    = $body;
        $mail->AltBody = "Name: " . $name . "\nEmail: " . $email . "\nSubject: " . $subject . "\nMessage Content: " . $message;

        $mail->send();

        header('Location: contact.php?status=success&msg=' . urlencode('Your message has been successfully sent to the PetBuddy team!'));
        exit;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage() . " | Info: " . $mail->ErrorInfo);
        header('Location: contact.php?status=error&msg=' . urlencode('Email failed to send. Please try again later or contact us directly by phone.'));
        exit;
    }
} else {
    header('Location: contact.php');
    exit;
}
