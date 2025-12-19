<?php
session_start();
require_once "../include/db.php"; 

if (!isset($_SESSION['member_id']) || !isset($_POST['product_id']) || !isset($_POST['action'])) {
    echo "error";
    exit;
}

$member_id = $_SESSION['member_id'];
$product_id = intval($_POST['product_id']);
$action = $_POST['action']; // 'increase' or 'decrease'

try {
    // 1. 先查询当前数量
    $sql = "SELECT quantity FROM cart_items WHERE member_id = :member_id AND product_id = :product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id, 'product_id' => $product_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $current_qty = intval($item['quantity']);
        $new_qty = $current_qty;

        if ($action === 'increase') {
            $new_qty++;
        } elseif ($action === 'decrease') {
            $new_qty--;
        }

        // 如果数量小于 1，什么都不做（或者你可以选择删除）
        if ($new_qty < 1) {
            echo "min_limit"; 
            exit;
        }

        // 2. 更新数据库
        $update_sql = "UPDATE cart_items SET quantity = :qty WHERE member_id = :member_id AND product_id = :product_id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute(['qty' => $new_qty, 'member_id' => $member_id, 'product_id' => $product_id]);

        echo "success";
    }
} catch (PDOException $e) {
    echo "error";
}
?>