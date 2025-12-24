<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$catId = (int)($_POST['category_id'] ?? 0);
$name  = trim($_POST['name'] ?? '');

if ($catId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Parent Category ID is missing.']);
    exit;
}
if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Subcategory Name is required.']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT COUNT(*) FROM sub_categories WHERE category_id = ? AND name = ?");
    $check->execute([$catId, $name]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Subcategory already exists in this category.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO sub_categories (category_id, name) VALUES (?, ?)");
    $stmt->execute([$catId, $name]);
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>