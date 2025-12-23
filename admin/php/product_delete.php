<?php
// admin/product_delete.php
session_start();
require_once '../user/include/db.php';

// 1. 权限检查
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// 2. 获取 ID
$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // 先获取图片文件名，以便删除文件
        $stmt = $pdo->prepare("SELECT image FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // 删除数据库记录
        $delStmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $delStmt->execute([$id]);

        // 如果数据库删除成功，且有图片，则删除物理图片文件
        if ($product && !empty($product['image'])) {
            $filePath = "../uploads/products/" . $product['image'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

    } catch (PDOException $e) {
        // 如果删除失败（例如因为有订单关联），这里可以处理错误
        // 为了简单起见，通常直接跳回列表
        // die("Error deleting product: " . $e->getMessage());
    }
}

// 3. 跳回列表
header("Location: products_list.php");
exit;
?>