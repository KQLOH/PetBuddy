<?php
session_start();
require "../include/db.php";

require_once '../stripe-php/init.php';

if (!isset($_GET['session_id']) || !isset($_GET['order_id'])) {
    header("Location: ../index.php");
    exit;
}

$session_id = $_GET['session_id'];
$order_id = $_GET['order_id'];

\Stripe\Stripe::setApiKey('sk_test_51ShoQnDJ45XBXeAmyeWDjVJYunQXJtiFqcbnoFcfysaedAflgYJsyvjSlWaVDXsMfLLwTxcrCYu5gedBCZoBXMHS00fHscjanD');

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);

    if ($session->payment_status == 'paid') {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Paid' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        $payment_intent = $session->payment_intent;
        $stmt_pay = $pdo->prepare("UPDATE payments SET reference_no = ? WHERE order_id = ?");
        $stmt_pay->execute([$payment_intent, $order_id]);

        header("Location: payment_success.php?order_id=" . $order_id);
        exit;
    } else {
        echo "Payment not completed.";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>