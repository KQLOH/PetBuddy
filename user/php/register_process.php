<?php

date_default_timezone_set("Asia/Kuala_Lumpur");


require "../include/db.php"; 


$name = $_POST['name'] ?? '';
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';


if (empty($name) || empty($email) || empty($password)) {
    echo "<script>alert(' Please fill in all required fields.'); window.history.back();</script>";
    exit;
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert(' Invalid email format. Please enter a valid email address.'); window.history.back();</script>";
    exit;
}


$emailParts = explode('@', $email);
if (count($emailParts) !== 2) {
    echo "<script>alert(' Invalid email format. Please check your email address.'); window.history.back();</script>";
    exit;
}

$localPart = $emailParts[0];
$domain = strtolower($emailParts[1]);


if (strlen($localPart) > 64) {
    echo "<script>alert(' Email username is too long. Maximum length is 64 characters.'); window.history.back();</script>";
    exit;
}


if (strpos($email, '..') !== false) {
    echo "<script>alert(' Invalid email format. Cannot have consecutive dots.'); window.history.back();</script>";
    exit;
}


if (substr($localPart, 0, 1) === '.' || substr($localPart, -1) === '.') {
    echo "<script>alert(' Invalid email format. Email cannot start or end with a dot.'); window.history.back();</script>";
    exit;
}


$domainParts = explode('.', $domain);
if (count($domainParts) < 2) {
    echo "<script>alert(' Invalid email domain. Please check your email address.'); window.history.back();</script>";
    exit;
}


$tld = end($domainParts);
if (strlen($tld) < 2 || !preg_match('/^[a-zA-Z]+$/', $tld)) {
    echo "<script>alert(' Invalid email domain. Please check your email address.'); window.history.back();</script>";
    exit;
}


if (strlen($email) > 254) {
    echo "<script>alert(' Email address is too long. Maximum length is 254 characters.'); window.history.back();</script>";
    exit;
}

try {

    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE email = :email");
    $stmt->execute(['email' => $email]);

    if ($stmt->rowCount() > 0) {
        echo "<script>alert('❌ Email already exists!'); window.history.back();</script>";
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);


    $sql = "INSERT INTO members (full_name, email, phone, password_hash, role) 
            VALUES (:name, :email, :phone, :pass, 'member')";
    
    $insertStmt = $pdo->prepare($sql);
    $insertStmt->execute([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'pass' => $hashedPassword
    ]);


    echo "<script>
            alert(' Registration successful! Please login.'); 
            window.location.href = 'home.php'; 
          </script>";

} catch (PDOException $e) {

    echo "<script>alert(' Database Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
}
?>