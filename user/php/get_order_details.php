<?php
session_start();
require_once '../include/db.php';
require_once '../include/product_utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['member_id']) || !isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$member_id = $_SESSION['member_id'];
$order_id = $_GET['order_id'];

try {
    $sql = "
        SELECT 
            o.*, 
            s.shipping_method, 
            s.shipping_fee, 
            ma.recipient_name, 
            ma.recipient_phone, 
            ma.address_line1, 
            ma.address_line2, 
            ma.city, 
            ma.state, 
            ma.postcode 
        FROM orders o
        LEFT JOIN shipping s ON o.order_id = s.order_id
        LEFT JOIN member_addresses ma ON s.address_id = ma.address_id
        WHERE o.order_id = ? AND o.member_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id, $member_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    if (!empty($order['recipient_name'])) {
        $order['full_shipping_address'] = $order['address_line1'] . ', ' . 
                                          ($order['address_line2'] ? $order['address_line2'] . ', ' : '') . 
                                          $order['postcode'] . ' ' . $order['city'] . ', ' . 
                                          $order['state'] . ', Malaysia';
    } else {
        $order['recipient_name'] = "N/A";
        $order['recipient_phone'] = "N/A";
        $order['full_shipping_address'] = "Address details not available (Deleted or Missing)";
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

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>