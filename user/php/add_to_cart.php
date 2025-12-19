<?php
session_start();
include "../include/db.php";
include_once __DIR__ . "/cart_function.php";

// 1. 登录验证
if (!isset($_SESSION['member_id'])) {
    echo "login_required";
    exit;
}

// 2. 参数验证
// 兼容 POST 和 GET 方式获取 product_id
$product_id = $_POST['product_id'] ?? $_GET['product_id'] ?? null;
// 获取数量，如果未设置则默认为 1
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if (!$product_id) {
    echo "invalid_id";
    exit;
}

// 确保数量至少为 1
if ($quantity < 1) {
    $quantity = 1;
}

$member_id = $_SESSION['member_id'];

// 3. 执行业务逻辑
// 调用 cart_function.php 中的函数，传入数量
$result = addToCart($pdo, $member_id, intval($product_id), $quantity);

// 4. 返回结果给 AJAX 前端
echo $result;
?>