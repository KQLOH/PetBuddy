<?php
session_start();
require_once "../include/db.php"; 

header('Content-Type: application/json');

if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);

if ($quantity < 1) {
    echo json_encode(['status' => 'error', 'message' => 'Quantity cannot be less than 1']);
    exit;
}

if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id] = $quantity;
}

if (isset($_SESSION['member_id'])) {
    $member_id = $_SESSION['member_id'];
    try {
        $checkSql = "SELECT cart_item_id FROM cart_items WHERE member_id = ? AND product_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$member_id, $product_id]);
        
        if ($checkStmt->rowCount() > 0) {
            $sql = "UPDATE cart_items SET quantity = ? WHERE member_id = ? AND product_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$quantity, $member_id, $product_id]);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'success']);
?>