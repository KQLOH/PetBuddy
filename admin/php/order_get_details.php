<?php
session_start();

require_once '../../user/include/db.php';
require_once '../../user/include/product_utils.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Order ID']);
    exit;
}

try {
    $sqlOrder = "
        SELECT 
            o.order_id, o.total_amount, o.status, o.order_date,
            m.full_name, m.email, m.phone,
            a.recipient_name, a.recipient_phone, a.address_line1, a.address_line2, a.city, a.state, a.postcode
        FROM orders o
        JOIN members m ON o.member_id = m.member_id
        LEFT JOIN shipping s ON o.order_id = s.order_id
        LEFT JOIN member_addresses a ON s.address_id = a.address_id
        WHERE o.order_id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlOrder);
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }

    $sqlItems = "
        SELECT 
            oi.quantity, oi.unit_price,
            p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ";
    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute([$orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $img = productImageUrl($item['image']);

        if (strpos($img, 'http') === 0) {
            $item['image'] = $img;
        } else {
            $item['image'] = '../../user/php/' . ltrim($img, '/');
        }
    }

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
