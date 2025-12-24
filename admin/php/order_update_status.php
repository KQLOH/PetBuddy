<?php
session_start();
require_once '../../user/include/db.php'; 

header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$newStatus = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';
$allowedStatuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled', 'return_requested', 'returned'];

if ($orderId <= 0 || !in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $result = $stmt->execute([$newStatus, $orderId]);

    if ($result) {
        if ($newStatus === 'shipped') {
            $pdo->prepare("UPDATE shipping SET shipping_status = 'shipped' WHERE order_id = ?")->execute([$orderId]);
        }
        if ($newStatus === 'completed') {
            $pdo->prepare("UPDATE shipping SET shipping_status = 'delivered' WHERE order_id = ?")->execute([$orderId]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>