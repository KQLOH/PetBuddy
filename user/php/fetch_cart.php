<?php
session_start();
require "../include/db.php"; 
include_once "cart_function.php"; 


require_once "../include/product_utils.php";


if (!isset($_SESSION['member_id'])) {
    
    echo '<div style="text-align: center; margin-top: 60px; color: #888;">
            <p>Please Login to view cart.</p>
          </div>';
    echo '<input type="hidden" id="ajax-new-total" value="0.00">';
    exit;
}

$member_id = $_SESSION['member_id'];


try {
    $sql = "SELECT ci.quantity, p.product_id, p.name, p.price, p.image   
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.product_id 
            WHERE ci.member_id = :member_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    include "cart_ui.php";


    if (isset($total_price)) {
        echo '<input type="hidden" id="ajax-new-total" value="' . number_format($total_price, 2) . '">';
    } else {
        echo '<input type="hidden" id="ajax-new-total" value="0.00">';
    }

} catch (PDOException $e) {
    echo "Error";
}
?>