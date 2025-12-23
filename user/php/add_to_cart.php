<?php
session_start();
include "../include/db.php";
include_once __DIR__ . "/cart_function.php";


if (!isset($_SESSION['member_id'])) {
    echo "login_required";
    exit;
}

$product_id = $_POST['product_id'] ?? $_GET['product_id'] ?? null;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if (!$product_id) {
    echo "invalid_id";
    exit;
}

if ($quantity < 1) {
    $quantity = 1;
}

$member_id = $_SESSION['member_id'];
$result = addToCart($pdo, $member_id, intval($product_id), $quantity);

echo $result;
?>