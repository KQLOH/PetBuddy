<?php
// 设置时区
date_default_timezone_set("Asia/Kuala_Lumpur");

// 引入你的数据库连接文件 (PDO)
require "../include/db.php"; 

// 1. 接收表单数据
$name = $_POST['name'] ?? '';
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';

// 简单的非空检查
if (empty($name) || empty($email) || empty($password)) {
    echo "<script>alert('❌ Please fill in all required fields.'); window.history.back();</script>";
    exit;
}

// Enhanced Email Validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('❌ Invalid email format. Please enter a valid email address.'); window.history.back();</script>";
    exit;
}

// Additional email validation checks
$emailParts = explode('@', $email);
if (count($emailParts) !== 2) {
    echo "<script>alert('❌ Invalid email format. Please check your email address.'); window.history.back();</script>";
    exit;
}

$localPart = $emailParts[0];
$domain = strtolower($emailParts[1]);

// Check local part length (max 64 characters)
if (strlen($localPart) > 64) {
    echo "<script>alert('❌ Email username is too long. Maximum length is 64 characters.'); window.history.back();</script>";
    exit;
}

// Check for consecutive dots
if (strpos($email, '..') !== false) {
    echo "<script>alert('❌ Invalid email format. Cannot have consecutive dots.'); window.history.back();</script>";
    exit;
}

// Check for dot at start or end of local part
if (substr($localPart, 0, 1) === '.' || substr($localPart, -1) === '.') {
    echo "<script>alert('❌ Invalid email format. Email cannot start or end with a dot.'); window.history.back();</script>";
    exit;
}

// Check domain format
$domainParts = explode('.', $domain);
if (count($domainParts) < 2) {
    echo "<script>alert('❌ Invalid email domain. Please check your email address.'); window.history.back();</script>";
    exit;
}

// Check TLD (should be at least 2 characters and only letters)
$tld = end($domainParts);
if (strlen($tld) < 2 || !preg_match('/^[a-zA-Z]+$/', $tld)) {
    echo "<script>alert('❌ Invalid email domain. Please check your email address.'); window.history.back();</script>";
    exit;
}

// Check total email length (max 254 characters)
if (strlen($email) > 254) {
    echo "<script>alert('❌ Email address is too long. Maximum length is 254 characters.'); window.history.back();</script>";
    exit;
}

try {
    // 2. 检查 Email 是否已存在
    // 使用 PDO 预处理语句 (比 real_escape_string 更安全)
    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE email = :email");
    $stmt->execute(['email' => $email]);

    if ($stmt->rowCount() > 0) {
        echo "<script>alert('❌ Email already exists!'); window.history.back();</script>";
        exit;
    }

    // 3. 处理密码 Hash (这是解决你登录问题的关键！)
    // 这会将 "123456" 转换成类似 "$2y$10$abcdefg..." 的安全字符串
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 4. 插入数据库 (对应你的 members 表结构)
    // 你的表字段是: email, password_hash, full_name, phone, role
    $sql = "INSERT INTO members (full_name, email, phone, password_hash, role) 
            VALUES (:name, :email, :phone, :pass, 'member')";
    
    $insertStmt = $pdo->prepare($sql);
    $insertStmt->execute([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'pass' => $hashedPassword
    ]);

    // 5. 注册成功
    echo "<script>
            alert('🎉 Registration successful! Please login.'); 
            // 如果是在 iframe 或弹窗中，可以刷新父页面或跳转
            window.location.href = 'home.php'; 
          </script>";

} catch (PDOException $e) {
    // 错误处理
    echo "<script>alert('❌ Database Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
}
?>