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

$id       = (int)($_POST['voucher_id'] ?? 0);
$code     = strtoupper(trim($_POST['code'] ?? ''));
$discount = floatval($_POST['discount_amount'] ?? 0);
$min      = floatval($_POST['min_amount'] ?? 0);
$start    = $_POST['start_date'] ?? '';
$end      = $_POST['end_date'] ?? '';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid voucher ID']);
    exit;
}

if ($code === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Voucher code is required']);
    exit;
}

try {
    // 检查 code 是否已存在（排除当前记录）
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE code = ? AND voucher_id != ?");
    $stmt->execute([$code, $id]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Duplicate voucher code!']);
        exit;
    }

    $sql = "UPDATE vouchers SET code=?, discount_amount=?, min_amount=?, start_date=?, end_date=? WHERE voucher_id=?";
    $pdo->prepare($sql)->execute([$code, $discount, $min, $start, $end, $id]);
    echo json_encode(['success' => true, 'message' => 'Voucher updated successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
