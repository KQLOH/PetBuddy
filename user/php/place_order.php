<?php
session_start();
require "../include/db.php";
require_once "cart_function.php";


if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('Please login to continue.'); window.location.href='login.php';</script>";
    exit;
}
$member_id = $_SESSION['member_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit;
}


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


$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}


if ($subtotal >= 50) {
    $shipping_fee = 0.00;
} else {
    $shipping_fee = 15.00;
}

$discount_amount = 0.00; 
$voucher_id = null; 


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


$first_name = $_POST['first_name'] ?? '';
$last_name  = $_POST['last_name'] ?? '';
$phone      = $_POST['phone'] ?? '';
$addr1 = $_POST['address'] ?? '';        
$addr2 = $_POST['address_line_2'] ?? ''; 

$city     = $_POST['city'] ?? '';
$state    = $_POST['state'] ?? '';
$postcode = $_POST['postcode'] ?? '';
$country  = $_POST['country'] ?? 'Malaysia';

$shipping_name = trim("$first_name $last_name");
$full_address_string = "$addr1, $addr2, $postcode $city, $state, $country";


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


try {
    $pdo->beginTransaction();


    if ($payment_base_method === 'Cash') {
        $order_status = 'pending'; 
    } else {
        $order_status = 'paid';
    }

    
    $sql_order = "INSERT INTO orders (member_id, total_amount, discount_amount, shipping_fee, status, shipping_name, shipping_address, shipping_phone, voucher_id, order_date) 
                  VALUES (:mid, :total, :discount, :ship, :status, :name, :addr, :phone, :vid, NOW())";
    
    $stmt = $pdo->prepare($sql_order);
    $stmt->execute([
        ':mid'      => $member_id,
        ':total'    => $total_amount,
        ':discount' => $discount_amount,
        ':ship'     => $shipping_fee, 
        ':status'   => $order_status,
        ':name'     => $shipping_name,
        ':addr'     => $full_address_string,
        ':phone'    => $phone,
        ':vid'      => $voucher_id 
    ]);

    $order_id = $pdo->lastInsertId();

   
    
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


    $sql_ship = "INSERT INTO shipping (order_id, address_id, shipping_fee, shipping_method, shipping_status) 
                 VALUES (:oid, :aid, :fee, :method, 'pending')";
    
    $stmt_ship = $pdo->prepare($sql_ship);
    $stmt_ship->execute([
        ':oid' => $order_id,
        ':aid' => $address_id,
        ':fee' => $shipping_fee,
        ':method' => 'Standard Delivery'
    ]);


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


    if (isset($_POST['save_info'])) {
        
        $sql_update_user = "UPDATE members SET 
                            first_name = :fname, last_name = :lname, phone = :phone
                            WHERE member_id = :mid";
        $stmt_user = $pdo->prepare($sql_update_user);
        $stmt_user->execute([
            ':fname' => $first_name, ':lname' => $last_name, ':phone' => $phone, ':mid' => $member_id
        ]);
        
       
        $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?")->execute([$member_id]);
        $pdo->prepare("UPDATE member_addresses SET is_default = 1 WHERE address_id = ?")->execute([$address_id]);
    }


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
    echo "Processing Error: " . $e->getMessage();
    exit;
}
?>