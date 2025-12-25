<?php
session_start();
require_once "../include/db.php";
require_once "cart_function.php";

header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired']);
    exit;
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

$result = updateCartQuantity($pdo, $_SESSION['member_id'], $product_id, $quantity);

echo json_encode($result);
