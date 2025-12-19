<?php
/**
 * 数据库配置文件
 * 负责建立与 MySQL 数据库的 PDO 连接
 */

// 数据库配置参数
$host   = 'localhost';
$dbname = 'petbuddy';   // 请确保此处数据库名称与您的实际环境一致
$user   = 'root';
$pass   = '';

try {
    // 设置 DSN (Data Source Name)
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    // 创建 PDO 实例
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // 开启异常模式，出错时抛出错误
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // 默认以关联数组形式返回数据
        PDO::ATTR_EMULATE_PREPARES   => false,                  // 禁用模拟预处理，防止 SQL 注入
    ]);

} catch (PDOException $e) {
    // 连接失败时终止脚本并显示错误
    die("数据库连接失败: " . $e->getMessage());
}
?>