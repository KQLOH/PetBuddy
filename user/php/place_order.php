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

// 仅允许 POST 请求提交
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit;
}

// --- 2. 处理订单商品数据 ---
$all_cart_items = getCartItems($pdo, $member_id);
$selected_str = $_POST['selected_products'] ?? ''; 
$cart_items = []; 

// 筛选被选中的商品
if (!empty($selected_str)) {
    $selected_ids = explode(',', $selected_str);
    foreach ($all_cart_items as $item) {
        if (in_array($item['product_id'], $selected_ids)) {
            $cart_items[] = $item;
        }
    }
} else {
    // 异常情况：如果没有传选中参数，默认全选 (防止空订单)
    $cart_items = $all_cart_items;
}

if (empty($cart_items)) {
    echo "<script>alert('Your cart is empty or no items selected.'); window.location.href='cart.php';</script>";
    exit;
}

// --- 3. 计算金额与优惠券验证 ---

// 3.1 计算小计 (Subtotal)
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}

$shipping_fee = 15.00;
$discount_amount = 0.00; 
$voucher_id = null; // 默认为空 (NULL)

// 3.2 ✨ 验证优惠券 (后端二次验证，确保安全)
$voucher_code = $_POST['voucher_code'] ?? '';

if (!empty($voucher_code)) {
    // 从数据库查询优惠券信息
    $stmt_v = $pdo->prepare("SELECT * FROM vouchers WHERE code = ?");
    $stmt_v->execute([$voucher_code]);
    $voucher = $stmt_v->fetch(PDO::FETCH_ASSOC);

    if ($voucher) {
        $today = date('Y-m-d');
        
        // 检查 1: 是否在有效期内
        if ($today >= $voucher['start_date'] && $today <= $voucher['end_date']) {
            
            // 检查 2: 是否满足最低消费
            if ($subtotal >= $voucher['min_amount']) {
                
                // 验证通过，应用折扣
                $discount_amount = floatval($voucher['discount_amount']);
                $voucher_id = $voucher['voucher_id']; // 获取 ID 准备存入 orders 表
            }
        }
    }
}

// 3.3 计算最终总价 (Total)
// 确保总价不会变成负数
$total_amount = max(0, $subtotal + $shipping_fee - $discount_amount);


// --- 4. 获取并处理收货地址 ---
$first_name = $_POST['first_name'] ?? '';
$last_name  = $_POST['last_name'] ?? '';
$phone      = $_POST['phone'] ?? '';

// 获取 4 行地址输入
$addr1 = $_POST['address'] ?? '';        // Line 1
$addr2 = $_POST['address_line_2'] ?? ''; // Line 2
$addr3 = $_POST['address_line_3'] ?? ''; // Line 3
$addr4 = $_POST['address_line_4'] ?? ''; // Line 4

$city     = $_POST['city'] ?? '';
$state    = $_POST['state'] ?? '';
$postcode = $_POST['postcode'] ?? '';
$country  = $_POST['country'] ?? 'Malaysia';

// 拼接完整姓名
$shipping_name = trim("$first_name $last_name");

// ✨ 智能拼接地址：过滤掉空的行，用逗号连接
$full_address_parts = array_filter([
    $addr1, 
    $addr2, 
    $addr3, 
    $addr4, 
    "$postcode $city", 
    "$state, $country"
]);
$shipping_address = implode(", ", $full_address_parts);


// --- 5. 处理支付详情 ---
$payment_base_method = $_POST['payment_method'] ?? 'Unknown';
$payment_details = "";

if ($payment_base_method === 'FPX') {
    $bank = $_POST['fpx_bank'] ?? 'Unknown Bank';
    $payment_method_record = "FPX - $bank";
} 
elseif ($payment_base_method === 'TNG') {
    $tng_phone = $_POST['tng_phone'] ?? '';
    $payment_method_record = "TNG eWallet ($tng_phone)";
} 
elseif ($payment_base_method === 'Credit Card') {
    // 只保存最后4位，保护隐私
    $card_last4 = substr($_POST['card_number'] ?? '0000', -4);
    $payment_method_record = "Credit Card (Ends $card_last4)";
} 
else {
    $payment_method_record = $payment_base_method; // Cash on Delivery
}


// --- 6. 数据库事务处理 (核心) ---
try {
    // 开启事务
    $pdo->beginTransaction();

    // A. 插入订单主表 (orders)
    // ✨ 这里加入了 voucher_id, discount_amount, shipping_fee
    $sql_order = "INSERT INTO orders (member_id, total_amount, discount_amount, shipping_fee, status, shipping_name, shipping_address, shipping_phone, voucher_id, order_date) 
                  VALUES (:mid, :total, :discount, :ship, 'paid', :name, :addr, :phone, :vid, NOW())";
    
    $stmt = $pdo->prepare($sql_order);
    
    $stmt->execute([
        ':mid'      => $member_id,
        ':total'    => $total_amount,
        ':discount' => $discount_amount,
        ':ship'     => $shipping_fee,
        ':name'     => $shipping_name,
        ':addr'     => $shipping_address,
        ':phone'    => $phone,
        ':vid'      => $voucher_id  // 存入 voucher_id (如果是空则存 NULL)
    ]);

    // 获取刚生成的 Order ID
    $order_id = $pdo->lastInsertId();

    // B. 插入订单商品明细 (order_items)
    $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (:oid, :pid, :qty, :price)";
    $stmt_item = $pdo->prepare($sql_item);

    foreach ($cart_items as $item) {
        $stmt_item->execute([
            ':oid'   => $order_id,
            ':pid'   => $item['product_id'],
            ':qty'   => $item['quantity'],
            ':price' => $item['price']
        ]);
    }

    // C. 插入支付记录 (payments)
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

    // D. 更新用户资料 (如果勾选了 Save Info)
    if (isset($_POST['save_info'])) {
        // 只更新第一行地址为默认地址
        $sql_update_user = "UPDATE members SET 
                            first_name = :fname, 
                            last_name = :lname, 
                            phone = :phone, 
                            address = :addr, 
                            postcode = :postcode, 
                            city = :city, 
                            state = :state 
                            WHERE member_id = :mid";
        
        $stmt_user = $pdo->prepare($sql_update_user);
        $stmt_user->execute([
            ':fname'    => $first_name,
            ':lname'    => $last_name,
            ':phone'    => $phone,
            ':addr'     => $addr1, // 只存主要地址
            ':postcode' => $postcode,
            ':city'     => $city,
            ':state'    => $state,
            ':mid'      => $member_id
        ]);
    }

    // E. 清理购物车 (删除已购买的商品)
    if (!empty($cart_items)) {
        $purchased_ids = array_column($cart_items, 'product_id');
        $placeholders = implode(',', array_fill(0, count($purchased_ids), '?'));
        
        $sql_clear = "DELETE FROM cart_items WHERE member_id = ? AND product_id IN ($placeholders)";
        $stmt_clear = $pdo->prepare($sql_clear);
        
        $params = array_merge([$member_id], $purchased_ids);
        $stmt_clear->execute($params);
    }

    // 提交事务
    $pdo->commit();

    // 跳转至成功页面 (记得把 Order ID 传过去)
    header("Location: payment_success.php?order_id=" . $order_id);
    exit;

} catch (Exception $e) {
    // 发生错误，回滚所有操作
    $pdo->rollBack();
    // 记录错误或显示给用户
    echo "Processing Error: " . $e->getMessage();
    exit;
}
?>