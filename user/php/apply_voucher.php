<?php
require "../include/db.php";
header('Content-Type: application/json');

// 获取参数
$code = $_POST['code'] ?? '';
$subtotal = floatval($_POST['subtotal'] ?? 0);

// 1. 基础检查
if (empty($code)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a code']);
    exit;
}

// 2. 查询数据库
$stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = :code");
$stmt->execute(['code' => $code]);
$voucher = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. 验证逻辑
if (!$voucher) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid discount code']);
    exit;
}

// 检查日期 (如果设置了开始/结束日期)
$today = date('Y-m-d');
if (($voucher['start_date'] && $today < $voucher['start_date']) || 
    ($voucher['end_date'] && $today > $voucher['end_date'])) {
    echo json_encode(['status' => 'error', 'message' => 'This voucher has expired']);
    exit;
}

// 检查最低消费
if ($subtotal < $voucher['min_amount']) {
    echo json_encode(['status' => 'error', 'message' => 'Min spend RM ' . number_format($voucher['min_amount'], 2) . ' required']);
    exit;
}

// 4. 验证通过，返回折扣金额
echo json_encode([
    'status' => 'success',
    'discount_amount' => floatval($voucher['discount_amount']),
    'message' => 'Code applied!'
]);
?>