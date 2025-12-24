<?php
session_start();
require "../include/db.php";
require_once "cart_function.php";

// --- 1. 权限与请求验证 ---
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('Please login to continue.'); window.location.href='login.php';</script>";
    exit;
}
$member_id = $_SESSION['member_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit;
}

// --- 2. 处理订单商品数据 ---
$all_cart_items = getCartItems($pdo, $member_id);
$selected_str = $_POST['selected_products'] ?? '';
$cart_items = [];

if (!empty($selected_str)) {
    $selected_ids = explode(',', $selected_str);
    foreach ($all_cart_items as $item) {
        if (in_array($item['product_id'], $selected_ids)) {
            $cart_items[] = $item;
        }
    }
} else {
    $cart_items = $all_cart_items;
}

if (empty($cart_items)) {
    echo "<script>alert('Your cart is empty.'); window.location.href='cart.php';</script>";
    exit;
}

// --- 3. 计算金额 ---
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}

// 🔥 运费逻辑：超过 50 免邮，否则 15
if ($subtotal >= 50) {
    $shipping_fee = 0.00;
} else {
    $shipping_fee = 15.00;
}

$discount_amount = 0.00;
$voucher_id = null;

// 验证优惠券
$voucher_code = $_POST['voucher_code'] ?? '';
if (!empty($voucher_code)) {
    $stmt_v = $pdo->prepare("SELECT * FROM vouchers WHERE code = ?");
    $stmt_v->execute([$voucher_code]);
    $voucher = $stmt_v->fetch(PDO::FETCH_ASSOC);

    if ($voucher) {
        $today = date('Y-m-d');
        if ($today >= $voucher['start_date'] && $today <= $voucher['end_date']) {
            if ($subtotal >= $voucher['min_amount']) {
                $discount_amount = floatval($voucher['discount_amount']);
                $voucher_id = $voucher['voucher_id'];
            }
        }
    }
}

$total_amount = max(0, $subtotal + $shipping_fee - $discount_amount);

// --- 4. 获取地址信息 ---
$full_name = trim($_POST['full_name'] ?? '');
$phone      = $_POST['phone'] ?? '';
$addr1 = $_POST['address'] ?? '';
$addr2 = $_POST['apartment'] ?? '';
$city     = $_POST['city'] ?? '';
$state    = $_POST['state'] ?? '';
$postcode = $_POST['postcode'] ?? '';
$country  = $_POST['country'] ?? 'Malaysia';

$shipping_name = $full_name;
// 注意：$full_address_string 不再直接存入 orders 表，而是存入 member_addresses 表

// --- 5. 支付方式 ---
$payment_base_method = $_POST['payment_method'] ?? 'Unknown';
if ($payment_base_method === 'FPX') {
    $bank = $_POST['fpx_bank'] ?? 'Unknown Bank';
    $payment_method_record = "FPX - $bank";
} elseif ($payment_base_method === 'TNG') {
    $tng_phone = $_POST['tng_phone'] ?? '';
    $payment_method_record = "TNG eWallet ($tng_phone)";
} elseif ($payment_base_method === 'Credit Card') {
    $card_last4 = substr($_POST['card_number'] ?? '0000', -4);
    $payment_method_record = "Credit Card (Ends $card_last4)";
} else {
    $payment_method_record = $payment_base_method;
}

// --- 6. 数据库事务 ---
try {
    $pdo->beginTransaction();

    // A. 订单状态判断
    if ($payment_base_method === 'Cash') {
        $order_status = 'pending';
    } else {
        $order_status = 'paid';
    }

    // =========================================================
    // B. ✨✨✨ 插入 Orders (根据新表结构修改) ✨✨✨
    // =========================================================
    // 移除了 shipping_fee, shipping_name, shipping_address 等字段
    $sql_order = "INSERT INTO orders (member_id, total_amount, status, discount_amount, voucher_id, order_date) 
                  VALUES (:mid, :total, :status, :discount, :vid, NOW())";

    $stmt = $pdo->prepare($sql_order);
    $stmt->execute([
        ':mid'      => $member_id,
        ':total'    => $total_amount,
        ':status'   => $order_status,
        ':discount' => $discount_amount,
        ':vid'      => $voucher_id
    ]);

    $order_id = $pdo->lastInsertId();

    // =========================================================
    // C. 智能处理地址 (防止重复添加)
    // =========================================================

    // 1. 先查询该用户是否已有完全相同的地址
    $stmtCheck = $pdo->prepare("SELECT address_id FROM member_addresses 
        WHERE member_id = :mid 
        AND recipient_name = :rname 
        AND recipient_phone = :rphone 
        AND address_line1 = :addr1 
        AND address_line2 = :addr2 
        AND city = :city 
        AND state = :state 
        AND postcode = :post 
        LIMIT 1");

    $stmtCheck->execute([
        ':mid'    => $member_id,
        ':rname'  => $shipping_name,
        ':rphone' => $phone,
        ':addr1'  => $addr1,
        ':addr2'  => $addr2,
        ':city'   => $city,
        ':state'  => $state,
        ':post'   => $postcode
    ]);

    $existing_id = $stmtCheck->fetchColumn();

    if ($existing_id) {
        // 💡 情况 A: 地址已存在 -> 直接复用旧 ID，不插入新行
        $address_id = $existing_id;
    } else {
        // 💡 情况 B: 是全新地址 -> 必须插入才能发货 (生成 FK ID)
        $sql_addr = "INSERT INTO member_addresses (member_id, recipient_name, recipient_phone, address_line1, address_line2, city, state, postcode, country, is_default) 
                     VALUES (:mid, :rname, :rphone, :addr1, :addr2, :city, :state, :post, :country, 0)";
        $stmt_addr = $pdo->prepare($sql_addr);
        $stmt_addr->execute([
            ':mid' => $member_id,
            ':rname' => $shipping_name,
            ':rphone' => $phone,
            ':addr1' => $addr1,
            ':addr2' => $addr2,
            ':city' => $city,
            ':state' => $state,
            ':post' => $postcode,
            ':country' => $country
        ]);

        $address_id = $pdo->lastInsertId();
    }

    // =========================================================
    // D. 插入 Shipping (运费存在这里)
    // =========================================================
    $sql_ship = "INSERT INTO shipping (order_id, address_id, shipping_fee, shipping_method, shipping_status) 
                 VALUES (:oid, :aid, :fee, :method, 'pending')";
    $stmt_ship = $pdo->prepare($sql_ship);
    $stmt_ship->execute([
        ':oid' => $order_id,
        ':aid' => $address_id,
        ':fee' => $shipping_fee, // 0.00 或 15.00
        ':method' => 'Standard Delivery'
    ]);

    // =========================================================
    // E. 插入 Order Items 并 扣减库存
    // =========================================================
    $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (:oid, :pid, :qty, :price)";
    $stmt_item = $pdo->prepare($sql_item);

    // 扣减库存 SQL (使用 :qty_check 修复 HY093 错误)
    $sql_deduct = "UPDATE products SET stock_qty = stock_qty - :qty WHERE product_id = :pid AND stock_qty >= :qty_check";
    $stmt_deduct = $pdo->prepare($sql_deduct);

    foreach ($cart_items as $item) {
        // 1. 插入订单明细
        $stmt_item->execute([
            ':oid'   => $order_id,
            ':pid'   => $item['product_id'],
            ':qty'   => $item['quantity'],
            ':price' => $item['price']
        ]);

        // 2. 扣减库存
        $stmt_deduct->execute([
            ':qty'       => $item['quantity'],
            ':qty_check' => $item['quantity'],
            ':pid'       => $item['product_id']
        ]);

        // 3. 检查库存是否足够
        if ($stmt_deduct->rowCount() == 0) {
            throw new Exception("Product ID " . $item['product_id'] . " (" . $item['name'] . ") is out of stock.");
        }
    }

    // F. 插入 Payments
    $payment_ref = strtoupper(uniqid("PAY-"));
    $sql_payment = "INSERT INTO payments (order_id, amount, method, reference_no, payment_date) 
                    VALUES (:oid, :amt, :method, :ref, NOW())";
    $stmt_payment = $pdo->prepare($sql_payment);
    $stmt_payment->execute([
        ':oid'    => $order_id,
        ':amt'    => $total_amount,
        ':method' => $payment_method_record,
        ':ref'    => $payment_ref
    ]);

    // G. 更新用户资料
    // Inside place_order.php

    if (isset($_POST['save_info'])) {
        // 1. Update Member Profile (Using 'full_name' to match your DB)
        $sql_update_user = "UPDATE members SET full_name = :fname, phone = :phone WHERE member_id = :mid";
        $stmt_user = $pdo->prepare($sql_update_user);
        $stmt_user->execute([
            ':fname' => $_POST['full_name'],
            ':phone' => $_POST['phone'],
            ':mid'   => $member_id
        ]);

        // 2. Set this address as Default
        // First, reset all addresses to non-default
        $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?")->execute([$member_id]);

        // Then, set the CURRENT address ID (generated above) as default
        $pdo->prepare("UPDATE member_addresses SET is_default = 1 WHERE address_id = ?")->execute([$address_id]);
    }

    // H. 清空购物车
    if (!empty($cart_items)) {
        $purchased_ids = array_column($cart_items, 'product_id');
        $placeholders = implode(',', array_fill(0, count($purchased_ids), '?'));
        $sql_clear = "DELETE FROM cart_items WHERE member_id = ? AND product_id IN ($placeholders)";
        $stmt_clear = $pdo->prepare($sql_clear);
        $params = array_merge([$member_id], $purchased_ids);
        $stmt_clear->execute($params);
    }

    $pdo->commit();

    header("Location: payment_success.php?order_id=" . $order_id);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div style='padding:50px; text-align:center; font-family:sans-serif;'>";
    echo "<h1 style='color:red;'>Order Failed</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='cart.php'>Return to Cart</a>";
    echo "</div>";
    exit;
}
