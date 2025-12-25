<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM vouchers WHERE voucher_id = ?");
$stmt->execute([$id]);
$voucher = $stmt->fetch(PDO::FETCH_ASSOC);

if ($voucher) {
    echo json_encode(['success' => true, 'voucher' => $voucher]);
} else {
    echo json_encode(['success' => false]);
}
