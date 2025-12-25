<?php
session_start();
require "../include/db.php";
require_once "cart_function.php";
require_once "../include/product_utils.php";

if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('Please login to continue.'); window.location.href='login.php';</script>";
    exit;
}
$member_id = $_SESSION['member_id'];

$stmt_user = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt_user->execute([$member_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

$user_full_name = $user['full_name'] ?? '';
$user_phone = $user['phone'] ?? '';
$user_email = $user['email'] ?? ''; 

$stmtAddr = $pdo->prepare("SELECT * FROM member_addresses WHERE member_id = ? ORDER BY is_default DESC, created_at DESC");
$stmtAddr->execute([$member_id]);
$saved_addresses = $stmtAddr->fetchAll(PDO::FETCH_ASSOC);

$prefill = [
    'full_name' => '', 
    'phone' => '', 
    'addr1' => '', 'addr2' => '', 
    'postcode' => '', 'city' => '', 'state' => '', 'email' => $user_email
];

if (!empty($saved_addresses)) {
    $def = $saved_addresses[0];
    
    $r_name = !empty($def['recipient_name']) ? $def['recipient_name'] : $user_full_name;
    $r_phone = !empty($def['recipient_phone']) ? $def['recipient_phone'] : $user_phone;

    $prefill['full_name'] = $r_name; 
    $prefill['phone'] = $r_phone;
    $prefill['addr1'] = $def['address_line1'];
    $prefill['addr2'] = $def['address_line2'];
    $prefill['postcode'] = $def['postcode'];
    $prefill['city'] = $def['city'];
    $prefill['state'] = $def['state'];
} 
else {
    $prefill['full_name'] = $user_full_name;
    $prefill['phone'] = $user_phone;
}

$all_cart_items = getCartItems($pdo, $member_id);
$checkout_items = [];
$selected_ids_str = "";

if (isset($_GET['items']) && $_GET['items'] === 'all') {
    $checkout_items = $all_cart_items;
    $selected_ids = array_column($all_cart_items, 'product_id');
    $selected_ids_str = implode(',', $selected_ids);
} elseif (!empty($_GET['selected'])) {
    $selected_ids_str = $_GET['selected'];
    $selected_ids = array_map('intval', explode(',', $selected_ids_str));
    foreach ($all_cart_items as $item) {
        if (in_array((int)$item['product_id'], $selected_ids)) {
            $checkout_items[] = $item;
        }
    }
}

if (empty($checkout_items)) {
    echo "<script>alert('No items selected for checkout.'); window.location.href='cart.php';</script>";
    exit;
}

$checkout_subtotal = 0;
foreach ($checkout_items as $item) {
    $line_total = (float)$item['price'] * (int)$item['quantity'];
    $checkout_subtotal += $line_total;
}

$shipping_fee = ($checkout_subtotal >= 50) ? 0.00 : 15.00;

$today = date('Y-m-d');
$stmt_v = $pdo->prepare("SELECT * FROM vouchers WHERE start_date <= ? AND end_date >= ? ORDER BY min_amount ASC");
$stmt_v->execute([$today, $today]);
$available_vouchers = $stmt_v->fetchAll(PDO::FETCH_ASSOC);

$total = $checkout_subtotal + $shipping_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - PetBuddy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root { --primary-color: #F4A261; --primary-dark: #E89C55; --bg-sidebar: #fff9f2; --border-focus: #F4A261; --accent-bg: #fffbf6; --text-dark: #2F2F2F; --border-color: #e1e1e1; }
        * { box-sizing: border-box; font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif; }
        body { margin: 0; padding: 0; background: #fff; color: var(--text-dark); display: flex; flex-direction: column; min-height: 100vh; }
        .checkout-layout { display: flex; flex-direction: column-reverse; width: 100%; }
        .main-col { padding: 30px 5%; background: #fff; }
        .sidebar-col { background: #fafafa; padding: 30px 5%; border-bottom: 1px solid #e1e1e1; }
        @media (min-width: 1001px) {
            body { height: 100vh; overflow: hidden; }
            .checkout-layout { flex-direction: row; flex: 1; overflow: hidden; margin: 0 auto; max-width: 1400px; }
            .main-col { flex: 1 1 58%; height: 100%; overflow-y: auto; padding: 40px 6%; border-right: 1px solid var(--border-color); scrollbar-width: thin; scrollbar-color: #ccc transparent; }
            .sidebar-col { flex: 1 1 42%; height: 100%; overflow-y: auto; background: var(--bg-sidebar); padding: 40px 6%; border-bottom: none; scrollbar-width: thin; scrollbar-color: #ddd transparent; }
        }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; margin-top: 35px; }
        .section-title { font-size: 19px; font-weight: 500; color: #333; }
        .section-header:first-of-type { margin-top: 0; }
        .form-group { margin-bottom: 25px; }
        .form-row { display: flex; gap: 25px; width: 100%; margin-bottom: 25px; }
        .form-col { flex: 1; min-width: 0; }
        input[type="text"], input[type="email"], input[type="tel"], select, input[type="password"] { width: 100%; padding: 13px; border: 1px solid #d9d9d9; border-radius: 5px; font-size: 14px; transition: all 0.2s ease-in-out; color: #333; background: #fff; }
        input:focus, select:focus { border-color: var(--border-focus); outline: none; box-shadow: 0 0 0 1px var(--border-focus); }
        input[readonly] { background-color: #f9f9f9; color: #777; cursor: not-allowed; }
        label { font-size: 14px; color: #555; display:block; margin-bottom: 6px; }
        .checkbox-wrapper { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        .checkbox-wrapper input { width: 18px; height: 18px; accent-color: var(--primary-dark); margin: 0; cursor: pointer; }
        .checkbox-wrapper label { font-size: 14px; color: #555; cursor: pointer; user-select: none; margin-bottom: 0; }
        .radio-box-group { border: 1px solid #d9d9d9; border-radius: 5px; overflow: hidden; margin-top: 15px; }
        .radio-box { padding: 18px; display: flex; align-items: center; justify-content: space-between; background: #fff; border-bottom: 1px solid #d9d9d9; cursor: pointer; }
        .radio-box:last-child { border-bottom: none; }
        .radio-label { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #333; }
        .btn-pay { width: 100%; padding: 20px; background: var(--primary-color); color: #fff; border: none; border-radius: 8px; font-size: 18px; font-weight: 700; cursor: pointer; margin-top: 30px; transition: opacity 0.2s, transform 0.2s; box-shadow: 0 4px 10px rgba(244, 162, 97, 0.4); }
        .btn-pay:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .return-cart { display: block; text-align: center; margin-top: 20px; color: var(--primary-dark); text-decoration: none; font-size: 14px; font-weight: 500; }
        .return-cart:hover { text-decoration: underline; }
        .summary-item { display: flex; align-items: center; gap: 15px; margin-bottom: 18px; }
        .img-wrap { position: relative; width: 65px; height: 65px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; background: #fff; }
        .img-wrap img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
        .qty-badge { position: absolute; top: -10px; right: -10px; background: #666; color: white; border-radius: 50%; width: 21px; height: 21px; font-size: 12px; font-weight: 600; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.15); border: 2px solid #fff; }
        .item-info { flex: 1; }
        .item-name { font-size: 14px; font-weight: 500; color: #333; margin-bottom: 4px; line-height: 1.3; }
        .item-price { font-size: 14px; font-weight: 500; color: #333; }
        .discount-row { display: flex; gap: 10px; margin: 25px 0; border-top: 1px solid rgba(0,0,0,0.08); border-bottom: 1px solid rgba(0,0,0,0.08); padding: 25px 0; }
        .discount-input { background: #fff !important; }
        .btn-apply { padding: 0 20px; background: #dcdcdc; color: #fff; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-apply:hover { background: #c0c0c0; }
        .total-line { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #555; }
        .grand-total { margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.08); display: flex; justify-content: space-between; align-items: center; }
        .grand-total .label { font-size: 16px; color: #333; font-weight: 500; }
        .grand-total .amount { font-size: 24px; font-weight: 600; color: #333; }
        .currency { font-size: 12px; color: #737373; margin-right: 5px; font-weight: 400; vertical-align: middle; }
        
        .address-selector-wrapper { margin-bottom: 20px; background: #FFF9F5; padding: 15px; border: 1px solid var(--primary-color); border-radius: 8px; }
        .address-selector-wrapper label { font-weight: 600; color: var(--primary-dark); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .address-selector-wrapper select { border-color: var(--primary-color); }
        .payment-container { display: flex; flex-direction: column; gap: 12px; }
        .payment-card { background: #fff; border: 2px solid #eee; border-radius: 12px; overflow: hidden; transition: all 0.3s ease; position: relative; }
        .payment-card:hover { border-color: #ddd; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .payment-card.selected { border-color: var(--primary-color); background-color: #fffbf6; box-shadow: 0 0 0 1px var(--primary-color); }
        .payment-header-row { padding: 20px; display: flex; align-items: center; cursor: pointer; user-select: none; }
        .payment-header-row input[type="radio"] { display: none; }
        .custom-radio { width: 22px; height: 22px; border: 2px solid #ccc; border-radius: 50%; margin-right: 15px; position: relative; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .payment-card.selected .custom-radio { border-color: var(--primary-color); background: var(--primary-color); }
        .payment-card.selected .custom-radio::after { 
            content: ''; 
            width: 6px; height: 10px; 
            border: solid white; 
            border-width: 0 2px 2px 0; 
            transform: rotate(45deg); 
            margin-top: -2px; 
        }
        .payment-label { font-weight: 600; font-size: 15px; color: #333; flex: 1; }
        
        .payment-icons { display: flex; gap: 8px; align-items: center; }
        .payment-icons img { height: 22px; width: auto; object-fit: contain; }

        .payment-details { display: none; padding: 0 20px 20px 20px; margin-top: -5px; animation: slideDown 0.3s ease-out; }
        .payment-card.selected .payment-details { display: block; }
        
        .helper-text { font-size: 13px; color: #666; background: rgba(0,0,0,0.03); padding: 12px; border-radius: 6px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .helper-text img { width: 14px; height: 14px; opacity: 0.6; }
        
        .input-icon-wrapper { position: relative; }
        .input-icon-img { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; object-fit: contain; opacity: 0.5; }

        .inline-icon { width: 16px; height: 16px; object-fit: contain; vertical-align: middle; }

        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .voucher-trigger-btn { background: none; border: none; color: var(--primary-dark); font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: underline; margin-top: 5px; display: inline-flex; align-items: center; gap: 5px; }
        .voucher-trigger-btn img { width: 16px; height: 16px; }
        
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; display: none; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; animation: fadeIn 0.2s; }
        .voucher-modal { background: #fff; width: 90%; max-width: 450px; border-radius: 12px; padding: 25px; position: relative; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 18px; font-weight: 700; color: #333; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
        .voucher-list { display: flex; flex-direction: column; gap: 15px; }
        .voucher-item { border: 2px dashed #ddd; border-radius: 8px; padding: 15px; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; background: #fff; }
        .voucher-item:hover { border-color: var(--primary-color); background: #fffbf6; }
        .v-left { flex: 1; }
        .v-code { font-weight: 700; font-size: 16px; color: var(--primary-dark); font-family: monospace; }
        .v-desc { font-size: 13px; color: #666; margin-top: 4px; }
        .v-min { font-size: 11px; color: #999; }
        .v-btn { background: var(--primary-color); color: #fff; border: none; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .v-btn:hover { background: var(--primary-dark); }
        .voucher-item.disabled { opacity: 0.6; background: #f9f9f9; border-color: #eee; cursor: not-allowed; }
        .voucher-item.disabled .v-btn { background: #ccc; cursor: not-allowed; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .custom-alert-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: none; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
        .custom-alert-overlay.show { opacity: 1; }
        .custom-alert-box { background: white; width: 90%; max-width: 400px; padding: 30px; border-radius: 20px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2); transform: scale(0.9); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .custom-alert-overlay.show .custom-alert-box { transform: scale(1); }
        
        .custom-alert-icon { 
            margin: 0 auto 20px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }

        .custom-alert-title { font-size: 20px; margin-bottom: 10px; color: #333; }
        .custom-alert-text { font-size: 15px; color: #666; margin-bottom: 25px; line-height: 1.5; }
        .custom-alert-buttons { display: flex; gap: 10px; justify-content: center; }
        .btn-alert { padding: 10px 25px; border-radius: 50px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
        .btn-alert-confirm { background: #FFB774; color: white; }
        .btn-alert-confirm:hover { filter: brightness(0.95); }
    </style>
</head>
<body>

    <?php include '../include/header.php'; ?>

    <div class="checkout-layout">

        <div id="customAlert" class="custom-alert-overlay">
            <div class="custom-alert-box">
                <div id="customAlertIcon" class="custom-alert-icon"></div>
                <h3 id="customAlertTitle" class="custom-alert-title"></h3>
                <p id="customAlertText" class="custom-alert-text"></p>
                <div id="customAlertButtons" class="custom-alert-buttons">
                    <button id="customAlertConfirm" class="btn-alert btn-alert-confirm">OK</button>
                </div>
            </div>
        </div>

        <div class="main-col">
            <form action="place_order.php" method="POST" id="checkoutForm">
                
                <input type="hidden" name="selected_products" value="<?= htmlspecialchars($selected_ids_str) ?>">
                <input type="hidden" name="voucher_code" id="hidden_voucher_code" value="">
                <input type="hidden" name="shipping_fee" value="<?= htmlspecialchars($shipping_fee) ?>">

                <div class="section-header">
                    <div class="section-title">Contact</div>
                </div>
                <div class="form-group">
                    <input type="email" name="email" value="<?= htmlspecialchars($prefill['email']) ?>" placeholder="Email" readonly>
                </div>
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="news" name="marketing_opt_in" checked>
                    <label for="news">Get me some paw-some deals!</label>
                </div>

                <div class="section-header">
                    <div class="section-title">Delivery</div>
                </div>
                
                <div class="form-group">
                    <select name="country">
                        <option value="Malaysia">Malaysia</option>
                    </select>
                </div>

                <?php if (!empty($saved_addresses)): ?>
                <div class="address-selector-wrapper">
                    <label>
                        <img src="../images/address_book.png" class="inline-icon"> Select from Address Book
                    </label>
                    <select id="savedAddressSelector" onchange="fillAddress(this.value)">
                        <?php foreach ($saved_addresses as $addr): 
                            $isDef = $addr['is_default'] ? '(Default)' : '';
                            $safe_addr = array_merge([
                                'recipient_name' => $user_full_name,
                                'recipient_phone' => $user_phone,
                                'address_line1' => '',
                                'address_line2' => '',
                                'postcode' => '',
                                'city' => '',
                                'state' => ''
                            ], $addr);
                            
                            $dataVal = htmlspecialchars(json_encode($safe_addr), ENT_QUOTES, 'UTF-8');
                        ?>
                            <option value="<?= $dataVal ?>" <?= $addr['is_default'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($safe_addr['recipient_name']) ?> - <?= htmlspecialchars($safe_addr['city']) ?> <?= $isDef ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="new">+ Use a different address</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <input type="text" name="full_name" id="f_name" placeholder="Full name" value="<?= htmlspecialchars($prefill['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <input type="tel" name="phone" id="phone" placeholder="Phone" value="<?= htmlspecialchars($prefill['phone']) ?>" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-size: 14px; color: #555; margin-bottom: 6px; display:block;">Shipping Address</label>
                    <input type="text" name="address" id="addr1" placeholder="Address Line 1" value="<?= htmlspecialchars($prefill['addr1']) ?>" required style="margin-bottom: 10px;">
                    <input type="text" name="apartment" id="addr2" placeholder="Address Line 2 (Optional)" value="<?= htmlspecialchars($prefill['addr2']) ?>">
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <input type="text" name="postcode" id="billing_postcode" placeholder="Postcode" value="<?= htmlspecialchars($prefill['postcode']) ?>" maxlength="5" required>
                    </div>
                    <div class="form-col">
                        <input type="text" name="city" id="billing_city" placeholder="City" value="<?= htmlspecialchars($prefill['city']) ?>" required>
                    </div>
                    <div class="form-col">
                        <select name="state" id="billing_state" required style="color: #333;">
                            <option value="" disabled <?= empty($prefill['state']) ? 'selected' : '' ?>>State</option>
                            <?php 
                                $states = ["Johor", "Selangor", "Kuala Lumpur", "Penang", "Perak", "Kedah", "Melaka", "Negeri Sembilan", "Pahang", "Terengganu", "Kelantan", "Perlis", "Sabah", "Sarawak", "Labuan", "Putrajaya"];
                                foreach($states as $st) {
                                    $selected = ($prefill['state'] == $st) ? "selected" : "";
                                    echo "<option value='$st' $selected>$st</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="save_info" name="save_info">
                    <label for="save_info">Save this information for next time</label>
                </div>

                <div class="section-header">
                    <div class="section-title">Shipping method</div>
                </div>
                <div class="radio-box-group">
                    <div class="radio-box">
                        <div class="radio-label">Standard Shipping</div>
                        <div style="font-weight: 600;">
                            <?php if($shipping_fee == 0): ?>
                                <span style="color: #28a745; margin-right: 5px;">FREE</span>
                                <span style="text-decoration: line-through; color: #999; font-size: 0.9em;">RM 15.00</span>
                            <?php else: ?>
                                RM <?= number_format($shipping_fee, 2) ?>
                            <?php endif; ?>
                        </div>
                        </div>
                </div>

                <div class="section-header">
                    <div class="section-title">Payment Method</div>
                </div>
                <p style="font-size: 13px; color: #737373; margin-bottom: 15px;">Select a secure payment method:</p>
                
                <div class="payment-container" id="paymentAccordion">

                    <div class="payment-card"> 
                        <label class="payment-header-row">
                            <input type="radio" name="payment_method" value="Credit Card Mock" required onclick="selectPayment(this)">
                            <div class="custom-radio"></div> <span class="payment-label">Credit Card </span>
                            <div class="payment-icons">
                                <img src="../images/visa.png" alt="Visa">
                                <img src="../images/mastercard.png" alt="Mastercard">
                            </div>
                        </label>
                        <div class="payment-details">
                            <div class="helper-text" style="background:#e3f2fd; color:#0d47a1; margin-bottom:15px;">
                                <img src="../images/info.png" style="width:14px; margin-right:5px;"> This is a mock form for data entry testing.
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">Issuing Bank</label>
                                <select name="card_bank" style="cursor:pointer;">
                                    <option value="" disabled selected>Choose your bank...</option>
                                    <option value="Maybank">Maybank</option>
                                    <option value="CIMB">CIMB Bank</option>
                                    <option value="Public Bank">Public Bank</option>
                                    <option value="RHB">RHB Bank</option>
                                    <option value="Hong Leong">Hong Leong Bank</option>
                                    <option value="AmBank">AmBank</option>
                                    <option value="UOB">UOB Bank</option>
                                    <option value="OCBC">OCBC Bank</option>
                                    <option value="HSBC">HSBC Bank</option>
                                    <option value="Standard Chartered">Standard Chartered</option>
                                    <option value="Alliance Bank">Alliance Bank</option>
                                    <option value="Affin Bank">Affin Bank</option>
                                    <option value="Other">Other Bank</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">Cardholder Name</label>
                                <input type="text" name="card_name" placeholder="Name on Card" oninput="validateText(this)">
                            </div>
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">Card Number</label>
                                <div class="input-icon-wrapper">
                                    <input type="text" name="card_number" placeholder="16-digit Card Number" maxlength="16" oninput="validateNumber(this)">
                                    <img src="../images/card_icon.png" class="input-icon-img">
                                </div>
                            </div>
                            <div class="form-row" style="gap: 15px; margin-bottom: 12px;">
                                <div class="form-col">
                                    <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">CVC</label>
                                    <input type="password" name="card_cvc" placeholder="3 digits" maxlength="3" oninput="validateNumber(this)">
                                </div>
                                <div class="form-col" style="flex: 2;">
                                    <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">Expiry</label>
                                    <div style="display: flex; gap: 10px;">
                                        <select name="card_exp_month" style="margin-bottom:0;">
                                            <option value="" disabled selected>MM</option>
                                            <?php for($i=1; $i<=12; $i++) { $m=sprintf("%02d",$i); echo "<option value='$m'>$m</option>"; } ?>
                                        </select>
                                        <select name="card_exp_year" style="margin-bottom:0;">
                                            <option value="" disabled selected>YY</option>
                                            <?php $yr=date("Y"); for($i=0; $i<=10; $i++) { $y=$yr+$i; echo "<option value='$y'>$y</option>"; } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="payment-card">
                        <label class="payment-header-row">
                            <input type="radio" name="payment_method" value="Stripe" required onclick="selectPayment(this)">
                            <div class="custom-radio"></div>
                            <span class="payment-label">Pay with Stripe (Secure)</span>
                            <div class="payment-icons">
                                <img src="../images/stripe.png" alt="Stripe">
                            </div>
                        </label>
                        <div class="payment-details">
                            <div class="helper-text">
                                You will be redirected to the secure Stripe Checkout page to complete your payment.
                            </div>
                        </div>
                    </div>

                    <div class="payment-card">    
                        <label class="payment-header-row">
                            <input type="radio" name="payment_method" value="FPX" required onclick="selectPayment(this)">
                            <div class="custom-radio"></div>
                            <span class="payment-label">FPX Online Banking</span>
                            <div class="payment-icons">
                                <span style="font-weight:900; color:#f7931e; font-style:italic; margin-right:2px;">F</span><span style="font-weight:900; color:#2e3192; font-style:italic;">PX</span>
                            </div>
                        </label>
                        <div class="payment-details">
                            <div class="helper-text"><img src="../images/lock.png" class="inline-icon"> Securely login to your bank account via FPX.</div>
                            
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size:12px; color:#555; margin-bottom:6px; display:block;">Select Bank</label>
                                <select name="fpx_bank" style="cursor:pointer; margin-bottom:0;">
                                    <option value="" disabled selected>Choose your bank...</option>
                                    <option value="Maybank">Maybank</option>
                                    <option value="CIMB">CIMB Bank</option>
                                    <option value="Public Bank">Public Bank</option>
                                    <option value="RHB">RHB Bank</option>
                                    <option value="Hong Leong">Hong Leong Bank</option>
                                    <option value="AmBank">AmBank</option>
                                    <option value="UOB">UOB Bank</option>
                                    <option value="OCBC">OCBC Bank</option>
                                    <option value="HSBC">HSBC Bank</option>
                                    <option value="Standard Chartered">Standard Chartered</option>
                                    <option value="Alliance Bank">Alliance Bank</option>
                                    <option value="Affin Bank">Affin Bank</option>
                                    <option value="Other">Other Bank</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">Bank Username</label>
                                <input type="text" name="fpx_username" placeholder="Enter online banking username" autocomplete="off">
                            </div>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">Bank Password</label>
                                <input type="password" name="fpx_password" placeholder="Enter online banking password" autocomplete="new-password">
                            </div>

                        </div>
                    </div>

                    <div class="payment-card">
                        <label class="payment-header-row">
                            <input type="radio" name="payment_method" value="TNG" required onclick="selectPayment(this)">
                            <div class="custom-radio"></div>
                            <span class="payment-label">Touch 'n Go eWallet</span>
                            <div class="payment-icons">
                                <img src="../images/wallet.png" alt="Wallet">
                            </div>
                        </label>
                        <div class="payment-details">
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">TNG Phone Number</label>
                                <input type="tel" name="tng_phone" placeholder="e.g. 0123456789" maxlength="11" oninput="validateNumber(this)">
                            </div>
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">6-Digit PIN</label>
                                <div class="input-icon-wrapper">
                                    <input type="password" name="tng_pin" placeholder="Enter 6-digit PIN" maxlength="6" inputmode="numeric" oninput="validateNumber(this)" style="letter-spacing: 2px;">
                                    <img src="../images/key.png" class="input-icon-img">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="payment-card">
                        <label class="payment-header-row">
                            <input type="radio" name="payment_method" value="Cash" required onclick="selectPayment(this)">
                            <div class="custom-radio"></div>
                            <span class="payment-label">Cash on Delivery</span>
                            <div class="payment-icons">
                                <img src="../images/cash.png" alt="Cash">
                            </div>
                        </label>
                        <div class="payment-details">
                            <div class="helper-text">Please prepare exact change for the rider upon delivery.</div>
                        </div>
                    </div>

                </div>

            </form>
        </div>

        <div class="sidebar-col">
            <div class="order-items">
                <?php foreach ($checkout_items as $item): 
                    $line_total = $item['price'] * $item['quantity'];
                    $price_display = ($item['price'] == 0) ? 'FREE' : 'RM ' . number_format($line_total, 2);
                    $displayImage = productImageUrl($item['image']);
                ?>
                <div class="summary-item">
                    <div class="img-wrap">
                        <img src="<?= htmlspecialchars($displayImage) ?>" alt="img">
                        <span class="qty-badge"><?= $item['quantity'] ?></span>
                    </div>
                    <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                    </div>
                    <div class="item-price"><?= $price_display ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="discount-section" style="margin: 25px 0; border-top: 1px solid rgba(0,0,0,0.08); border-bottom: 1px solid rgba(0,0,0,0.08); padding: 25px 0;">
                <div style="display: flex; gap: 10px;">
                    <input type="text" class="discount-input" placeholder="Discount code or gift card">
                    <button type="button" class="btn-apply">Apply</button>
                </div>
                <button type="button" class="voucher-trigger-btn" onclick="openVoucherModal()">
                    <img src="../images/ticket.png" class="inline-icon"> Select Voucher
                </button>
            </div>
            
            <div id="voucherModalOverlay" class="modal-overlay" onclick="closeVoucherModal(event)">
                <div class="voucher-modal">
                    <div class="modal-header">
                        <div class="modal-title">Select Voucher</div>
                        <button class="modal-close" onclick="closeVoucherModal(null)">&times;</button>
                    </div>
                    
                    <div class="voucher-list">
                        <?php if (empty($available_vouchers)): ?>
                            <div style="text-align: center; color: #999; padding: 20px;">No vouchers available :(</div>
                        <?php else: ?>
                            <?php foreach ($available_vouchers as $v): 
                                $min_spend = (float)$v['min_amount'];
                                $is_eligible = $checkout_subtotal >= $min_spend;
                            ?>
                            <div class="voucher-item <?= $is_eligible ? '' : 'disabled' ?>">
                                <div class="v-left">
                                    <div class="v-code"><?= htmlspecialchars($v['code']) ?></div>
                                    <div class="v-desc">RM <?= number_format($v['discount_amount'], 0) ?> OFF</div>
                                    <?php if($min_spend > 0): ?>
                                        <div class="v-min">Min. spend RM <?= number_format($min_spend, 2) ?></div>
                                    <?php else: ?>
                                        <div class="v-min">No min. spend</div>
                                    <?php endif; ?>
                                </div>
                                <div class="v-right">
                                    <?php if ($is_eligible): ?>
                                        <button class="v-btn" onclick="applySelectedVoucher('<?= $v['code'] ?>')">Use</button>
                                    <?php else: ?>
                                        <button class="v-btn" disabled>Locked</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="total-line">
                <span>Subtotal • <?= count($checkout_items) ?> items</span>
                <span>RM <span id="display_subtotal"><?= number_format($checkout_subtotal, 2) ?></span></span>
            </div>
            
            <div class="total-line" id="discount_row" style="display:none; color: var(--primary-dark);">
                <span style="display:flex; align-items:center; gap:5px;">
                    Discount <img src="../images/tag.png" class="inline-icon">
                </span>
                <span>- RM <span id="display_discount">0.00</span></span>
            </div>

            <div class="total-line">
                <span style="display:flex; align-items:center; gap:5px;">
                    Shipping <img src="../images/info.png" class="inline-icon" style="opacity:0.5;">
                </span>
                
                <span>
                    <?php if($shipping_fee == 0): ?>
                        <span style="color: #28a745; font-weight: bold;">FREE</span>
                        <span style="text-decoration: line-through; color: #999; font-size: 0.9em; margin-left: 5px;">RM 15.00</span>
                    <?php else: ?>
                        RM <?= number_format($shipping_fee, 2) ?>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="grand-total">
                <span class="label">Total</span>
                <div class="amount">
                    <span class="currency">MYR</span>
                    RM <span id="display_total"><?= number_format($total, 2) ?></span>
                </div>
            </div>

            <button type="submit" class="btn-pay" form="checkoutForm">Pay now</button>
            <a href="cart.php" class="return-cart">< Return to cart</a>
        </div>

    </div>

    <script>
        function showCustomAlert(type, title, text, autoClose = false) {
            const overlay = document.getElementById('customAlert');
            const icon = document.getElementById('customAlertIcon');
            const btnConfirm = document.getElementById('customAlertConfirm');

            document.getElementById('customAlertTitle').innerText = title;
            document.getElementById('customAlertText').innerText = text;

            icon.className = 'custom-alert-icon';
            icon.innerHTML = ''; 

            let imgSrc = '';

            if (type === 'success') {
                icon.classList.add('icon-success'); 
                imgSrc = '../images/success.png';
            } else if (type === 'error') {
                icon.classList.add('icon-error'); 
                imgSrc = '../images/error.png';
            } else {
                icon.classList.add('icon-confirm'); 
                imgSrc = '../images/question.png'; 
            }

            icon.innerHTML = `<img src="${imgSrc}" style="width: 64px; height: 64px; object-fit: contain;">`;

            btnConfirm.onclick = closeCustomAlert;

            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('show'), 10);

            if (autoClose) {
                setTimeout(closeCustomAlert, 2000);
            }
        }

        function closeCustomAlert() {
            const overlay = document.getElementById('customAlert');
            overlay.classList.remove('show');
            setTimeout(() => { overlay.style.display = 'none'; }, 300);
        }

        function fillAddress(jsonStr) {
            if (jsonStr === 'new') {
                document.getElementById('f_name').value = '';
                document.getElementById('phone').value = '';
                document.getElementById('addr1').value = '';
                document.getElementById('addr2').value = '';
                document.getElementById('billing_postcode').value = '';
                document.getElementById('billing_city').value = '';
                document.getElementById('billing_state').value = '';
            } else {
                const addr = JSON.parse(jsonStr);
                document.getElementById('f_name').value = addr.recipient_name || '';
                document.getElementById('phone').value = addr.recipient_phone;
                document.getElementById('addr1').value = addr.address_line1;
                document.getElementById('addr2').value = addr.address_line2;
                document.getElementById('billing_postcode').value = addr.postcode;
                document.getElementById('billing_city').value = addr.city;
                document.getElementById('billing_state').value = addr.state;
            }
        }

        function selectPayment(radio) {
            const cards = document.querySelectorAll('.payment-card');
            cards.forEach(card => card.classList.remove('selected'));
            const parentCard = radio.closest('.payment-card');
            parentCard.classList.add('selected');

            const method = radio.value;
            const allInputs = document.querySelectorAll('[name^="card_"], [name^="fpx_"], [name^="tng_"], #card_auth');
            allInputs.forEach(input => input.removeAttribute('required'));

            if (method === 'Credit Card Mock') {
                setRequired('card_bank');
                setRequired('card_name');
                setRequired('card_number');
                setRequired('card_cvc');
                setRequired('card_exp_month');
                setRequired('card_exp_year');
            } 
            else if (method === 'FPX') {
                setRequired('fpx_bank');
                setRequired('fpx_username');
                setRequired('fpx_password');
            } 
            else if (method === 'TNG') {
                setRequired('tng_phone');
                setRequired('tng_pin'); 
            }
        }

        function setRequired(name) {
            const input = document.querySelector(`[name="${name}"]`);
            if (input) input.setAttribute('required', 'required');
        }

        function validateNumber(input) { input.value = input.value.replace(/[^0-9]/g, ''); }
        function validateText(input) { input.value = input.value.replace(/[^a-zA-Z\s]/g, ''); }

        function openVoucherModal() { document.getElementById('voucherModalOverlay').classList.add('active'); }

        function closeVoucherModal(e) {
            if (!e || e.target.classList.contains('modal-overlay') || e.target.classList.contains('modal-close')) {
                document.getElementById('voucherModalOverlay').classList.remove('active');
            }
        }

        function applySelectedVoucher(code) {
            $(".discount-input").val(code);
            document.getElementById('voucherModalOverlay').classList.remove('active');
            $(".btn-apply").click();
        }

        $(document).ready(function() {
            
            $(".btn-apply").click(function(e) {
                e.preventDefault();
                let code = $(".discount-input").val().trim();
                let subtotal = parseFloat($("#display_subtotal").text().replace(/,/g, ''));
                let shipping = <?= $shipping_fee ?>; 

                if(!code) { 
                    showCustomAlert('error', 'Error', 'Please enter a code');
                    return; 
                }

                $.ajax({
                    url: "apply_voucher.php",
                    type: "POST",
                    data: { code: code, subtotal: subtotal },
                    dataType: "json",
                    success: function(res) {
                        if (res.status === "success") {
                            let discount = parseFloat(res.discount_amount);
                            let newTotal = subtotal + shipping - discount;
                            $("#display_discount").text(discount.toFixed(2));
                            $("#discount_row").fadeIn();
                            $("#display_total").text(newTotal.toFixed(2));
                            $("#hidden_voucher_code").val(code);
                            
                            showCustomAlert('success', 'Applied!', 'Saved RM ' + discount.toFixed(2), true);
                            
                            $(".discount-input").prop('disabled', true);
                            $(".btn-apply").text("Applied").css("background", "#2F2F2F");
                        } else {
                            showCustomAlert('error', 'Invalid Code', res.message);
                            
                            $("#hidden_voucher_code").val("");
                            $("#discount_row").hide();
                            $("#display_total").text((subtotal + shipping).toFixed(2));
                        }
                    },
                    error: function() { 
                        showCustomAlert('error', 'Error', 'System error applying voucher');
                    }
                });
            });

            $("#billing_postcode").on("keyup change", function() {
                var postcode = $(this).val();

                if (postcode.length === 5 && $.isNumeric(postcode)) {
                    $("#billing_city").attr("placeholder", "Searching...");
                    
                    $.ajax({
                        url: "get_location.php", 
                        type: "GET",
                        data: { postcode: postcode },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                $("#billing_city").val(response.city);
                                var state = response.state;
                                $("#billing_state option").each(function() {
                                    if ($(this).val() === state || $(this).text() === state) {
                                        $(this).prop('selected', true);
                                    }
                                });
                                $("#billing_city").attr("placeholder", "City");
                            } else {
                                $("#billing_city").val("");
                                $("#billing_city").attr("placeholder", "Not found in local DB");
                            }
                        },
                        error: function() {
                            $("#billing_city").val("");
                            $("#billing_city").attr("placeholder", "Error searching");
                        }
                    });
                }
            });

        });
    </script>

</body>
</html>