<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = $_POST['voucher_id'];
    $code     = strtoupper(trim($_POST['code']));
    $discount = floatval($_POST['discount_amount']);
    $min      = floatval($_POST['min_amount']);
    $start    = $_POST['start_date'];
    $end      = $_POST['end_date'];

    try {
        $sql = "UPDATE vouchers SET code=?, discount_amount=?, min_amount=?, start_date=?, end_date=? WHERE voucher_id=?";
        $pdo->prepare($sql)->execute([$code, $discount, $min, $start, $end, $id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
