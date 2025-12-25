<?php
session_start();
require "../include/db.php";

if (isset($_SESSION['member_id']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $mid = $_SESSION['member_id'];
    $pid = $_POST['product_id'];
    $qty = intval($_POST['quantity']);

    if ($qty < 1) { $qty = 1; } 

    
    $stmtCheck = $pdo->prepare("SELECT stock_qty FROM products WHERE product_id = ?");
    $stmtCheck->execute([$pid]);
    $stock = $stmtCheck->fetchColumn();

    if ($stock !== false && $qty > $stock) {
        $qty = $stock;
    }

    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE member_id = ? AND product_id = ?");
    if ($stmt->execute([$qty, $mid, $pid])) {
        echo "success";
    } else {
        echo "error";
    }
}
?>