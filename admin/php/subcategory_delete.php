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
    $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sub_category_id = ?");
    $check->execute([$id]);
    $count = $check->fetchColumn();

    if ($count > 0) {
        echo json_encode(['success' => false, 'error' => "Cannot delete: $count products are listed under this subcategory."]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM sub_categories WHERE sub_category_id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
?>