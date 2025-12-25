<?php
session_start();
include "../include/db.php";
include_once __DIR__ . "/cart_function.php";

header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['status' => 'login_required', 'message' => 'Please login first.']);
    exit;
}

$product_id = $_POST['product_id'] ?? $_GET['product_id'] ?? null;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if (!$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Product ID']);
    exit;
}

$member_id = $_SESSION['member_id'];
$result = addToCart($pdo, $member_id, intval($product_id), $quantity);

echo json_encode($result);
