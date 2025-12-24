<?php
session_start();
require_once '../../user/include/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $checkProd = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $checkProd->execute([$id]);
    $productCount = $checkProd->fetchColumn();

    if ($productCount > 0) {
        echo json_encode(['success' => false, 'error' => "Cannot delete: This category contains $productCount products. Please move or delete them first."]);
        exit;
    }


    $checkSub = $pdo->prepare("SELECT COUNT(*) FROM sub_categories WHERE category_id = ?");
    $checkSub->execute([$id]);
    $subCount = $checkSub->fetchColumn();

    if ($subCount > 0) {
        echo json_encode(['success' => false, 'error' => "Cannot delete: This category has $subCount subcategories. Please remove them first."]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM product_categories WHERE category_id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>