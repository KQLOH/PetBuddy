<?php
session_start();
require_once '../../user/include/db.php';

header('Content-Type: application/json');

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
    $stmtPath = $pdo->prepare("SELECT image FROM products WHERE product_id = ?");
    $stmtPath->execute([$productId]);
    $product = $stmtPath->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit;
    }

    $imagePathInDb = $product['image'];
    $stmtDel = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $result = $stmtDel->execute([$productId]);

    if ($result) {
        if (!empty($imagePathInDb)) {
            $relativePath = ltrim($imagePathInDb, './'); 
            $fullPhysicalPath = "../../user/" . $relativePath;

            if (file_exists($fullPhysicalPath) && is_file($fullPhysicalPath)) {
                if (strpos($imagePathInDb, 'default_product.png') === false) {
                    unlink($fullPhysicalPath);
                }
            }
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete product from database']);
    }
    exit;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}