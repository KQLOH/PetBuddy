<?php
session_start();
require "../include/db.php"; // ⚠️ 确保你的 db.php 已经是 PDO 版本！

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. 准备 SQL 语句 (使用 :email 作为占位符)
    $sql = "SELECT * FROM members WHERE email = :email";
    
    try {
        // 2. 预处理
        $stmt = $pdo->prepare($sql);
        
        // 3. 绑定参数并执行
        $stmt->execute(['email' => $email]);
        
        // 4. 获取结果 (以关联数组形式)
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 5. 检查是否找到了用户
        if ($user) {
            // 6. 验证密码
            if (password_verify($password, $user['password_hash'])) {
                
                // 登录成功，设置 Session
                $_SESSION['member_id'] = $user['member_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                echo "<p class='success'>Login Successful! Redirecting...</p>";
                header("refresh:1; url=../php/home.php");
                exit;

            } else {
                echo "<p class='error'>Wrong password!</p>";
            }
        } else {
            echo "<p class='error'>Email not found!</p>";
        }

    } catch (PDOException $e) {
        // 处理可能的数据库错误
        echo "Error: " . $e->getMessage();
    }
}
?>