<?php
session_start();
require '../include/db.php';
require_once '../include/product_utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['member_id']) || !isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$member_id = $_SESSION['member_id'];
$order_id = $_GET['order_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND member_id = ?");
    $stmt->execute([$order_id, $member_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    $stmtItems = $pdo->prepare("
        SELECT oi.*, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?
    ");
    $stmtItems->execute([$order_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $item['image_url'] = productImageUrl($item['image']);
    }

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>