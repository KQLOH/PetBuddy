<?php
// user/php/wishlist_action.php
session_start();
require "../include/db.php";

header('Content-Type: application/json');

// 1. 检查登录
if (!isset($_SESSION['member_id'])) {
    echo json_encode(['status' => 'login_required']);
    exit;
}

$member_id = $_SESSION['member_id'];
$product_id = $_POST['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Product ID']);
    exit;
}

try {
    // 2. 检查是否已经收藏
    $checkSql = "SELECT wishlist_id FROM wishlist WHERE member_id = ? AND product_id = ?";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute([$member_id, $product_id]);
    
    if ($stmt->rowCount() > 0) {
        // A. 已经收藏 -> 执行删除 (Unlike)
        $delSql = "DELETE FROM wishlist WHERE member_id = ? AND product_id = ?";
        $delStmt = $pdo->prepare($delSql);
        $delStmt->execute([$member_id, $product_id]);
        echo json_encode(['status' => 'removed']);
    } else {
        // B. 没收藏 -> 执行添加 (Like)
        $insSql = "INSERT INTO wishlist (member_id, product_id) VALUES (?, ?)";
        $insStmt = $pdo->prepare($insSql);
        $insStmt->execute([$member_id, $product_id]);
        echo json_encode(['status' => 'added']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>