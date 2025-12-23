<?php
require "../include/db.php";
header('Content-Type: application/json');


$code = $_POST['code'] ?? '';
$subtotal = floatval($_POST['subtotal'] ?? 0);


if (empty($code)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a code']);
    exit;
}


$stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = :code");
$stmt->execute(['  code' => $code]);
$voucher = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$voucher) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid discount code']);
    exit;
}


$today = date('Y-m-d');
if (($voucher['start_date'] && $today < $voucher['start_date']) || 
    ($voucher['end_date'] && $today > $voucher['end_date'])) {
    echo json_encode(['status' => 'error', 'message' => 'This voucher has expired']);
    exit;
}


if ($subtotal < $voucher['min_amount']) {
    echo json_encode(['status' => 'error', 'message' => 'Min spend RM ' . number_format($voucher['min_amount'], 2) . ' required']);
    exit;
}


echo json_encode([
    'status' => 'success',
    'discount_amount' => floatval($voucher['discount_amount']),
    'message' => 'Code applied!'
]);
?>