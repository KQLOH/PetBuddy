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

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid voucher ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM vouchers WHERE voucher_id = ?");
$stmt->execute([$id]);
$voucher = $stmt->fetch(PDO::FETCH_ASSOC);

if ($voucher) {
    echo json_encode(['success' => true, 'voucher' => $voucher]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Voucher not found']);
}
