<?php
session_start();
require_once "../include/db.php"; 


if (!isset($_SESSION['member_id']) || !isset($_POST['product_id'])) {
    echo "error"; 
    exit;
}

$member_id = $_SESSION['member_id'];
$product_id = intval($_POST['product_id']);

try {
   
    $sql = "DELETE FROM cart_items WHERE member_id = :member_id AND product_id = :product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id, 'product_id' => $product_id]);
    
    echo "success";
} catch (PDOException $e) {
    
    echo "error: " . $e->getMessage();
}
?>