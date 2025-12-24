<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Category Name is required.']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Category already exists.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO product_categories (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $desc]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>