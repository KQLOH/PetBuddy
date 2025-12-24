<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id   = (int)($_POST['category_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($id <= 0 || $name === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid ID or Name missing.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE product_categories SET name = ?, description = ? WHERE category_id = ?");
    $stmt->execute([$name, $desc, $id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>