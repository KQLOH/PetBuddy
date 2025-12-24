<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id   = (int)($_POST['sub_category_id'] ?? 0);
$name = trim($_POST['name'] ?? '');

if ($id <= 0 || $name === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid ID or Name missing.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE sub_categories SET name = ? WHERE sub_category_id = ?");
    $stmt->execute([$name, $id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}