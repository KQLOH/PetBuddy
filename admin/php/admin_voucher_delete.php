<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if (
    empty($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'super_admin'], true)
) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid voucher ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM vouchers WHERE voucher_id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Voucher deleted successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
