<?php
session_start();
require_once '../../user/include/db.php';

// 设置返回头为 JSON 格式
header('Content-Type: application/json');

// 权限检查
if (
    empty($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'super_admin'], true)
) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$productId = (int)($_POST['id'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $result = $stmt->execute([$productId]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Product not found or already deleted']);
    }
    exit;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}