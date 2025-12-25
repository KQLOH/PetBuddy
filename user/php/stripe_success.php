<?php
// user/php/stripe_success.php
session_start();
require "../include/db.php";

// ✨ 关键修改：使用手动下载的路径
require_once '../stripe-php/init.php';

if (!isset($_GET['session_id']) || !isset($_GET['order_id'])) {
    header("Location: ../index.php"); // 如果没有参数，回首页
    exit;
}

$session_id = $_GET['session_id'];
$order_id = $_GET['order_id'];

// 🔑 记得填入你的 Secret Key
\Stripe\Stripe::setApiKey('sk_test_51ShoQnDJ45XBXeAmyeWDjVJYunQXJtiFqcbnoFcfysaedAflgYJsyvjSlWaVDXsMfLLwTxcrCYu5gedBCZoBXMHS00fHscjanD');

try {
    // 向 Stripe 查询订单状态
    $session = \Stripe\Checkout\Session::retrieve($session_id);

    if ($session->payment_status == 'paid') {
        // 1. 更新订单状态为 Paid
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Paid' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // 2. 更新 Payment 表 (可选，把 reference_no 更新为 Stripe 的 Payment Intent ID)
        $payment_intent = $session->payment_intent;
        $stmt_pay = $pdo->prepare("UPDATE payments SET reference_no = ? WHERE order_id = ?");
        $stmt_pay->execute([$payment_intent, $order_id]);

        // 3. 跳转到成功页面
        header("Location: payment_success.php?order_id=" . $order_id);
        exit;
    } else {
        echo "Payment not completed.";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>