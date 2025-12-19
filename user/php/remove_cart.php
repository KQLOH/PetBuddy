<?php
session_start();
require_once "../include/db.php"; 

// 验证请求参数
if (!isset($_SESSION['member_id']) || !isset($_POST['product_id'])) {
    echo "error"; // 参数缺失
    exit;
}

$member_id = $_SESSION['member_id'];
$product_id = intval($_POST['product_id']);

try {
    // 执行删除操作
    $sql = "DELETE FROM cart_items WHERE member_id = :member_id AND product_id = :product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id, 'product_id' => $product_id]);
    
    echo "success";
} catch (PDOException $e) {
    // 记录错误日志或返回错误信息
    echo "error: " . $e->getMessage();
}
?>