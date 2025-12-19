<?php
session_start();
include "../include/db.php"; 
include_once "cart_function.php"; // 如果你需要用到里面的辅助函数

// 1. 检查登录
if (!isset($_SESSION['member_id'])) {
    // 没登录的提示
    echo '<div style="text-align: center; margin-top: 60px; color: #888;">
            <p>Please Login to view cart.</p>
          </div>';
    echo '<input type="hidden" id="ajax-new-total" value="0.00">';
    exit;
}

$member_id = $_SESSION['member_id'];

// 2. 准备数据 (这里只负责查询，不负责 HTML)
try {
    $sql = "SELECT ci.quantity, p.product_id, p.name, p.price, p.image   
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.product_id 
            WHERE ci.member_id = :member_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. 引用统一的 UI 设计文件！！！
    include "cart_ui.php";

    // 4. 传递总价给 JS (cart_ui.php 里已经计算了 total_price)
    echo '<input type="hidden" id="ajax-new-total" value="' . number_format($total_price, 2) . '">';

} catch (PDOException $e) {
    echo "Error";
}
?>