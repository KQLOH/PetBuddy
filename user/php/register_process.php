<?php
// 设置时区
date_default_timezone_set("Asia/Kuala_Lumpur");

// 引入你的数据库连接文件 (PDO)
require "../include/db.php"; 

// 1. 接收表单数据
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';

// 简单的非空检查
if (empty($name) || empty($email) || empty($password)) {
    echo "<script>alert('❌ Please fill in all required fields.'); window.history.back();</script>";
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