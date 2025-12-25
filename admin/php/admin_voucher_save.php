<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$code     = strtoupper(trim($_POST['code']));
$discount = floatval($_POST['discount_amount']);
$min      = floatval($_POST['min_amount']);
$start    = $_POST['start_date'];
$end      = $_POST['end_date'];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE code = ?");
    $stmt->execute([$code]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Duplicate voucher code!']);
        exit;
    }

    $sql = "INSERT INTO vouchers (code, discount_amount, min_amount, start_date, end_date) VALUES (?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$code, $discount, $min, $start, $end]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
