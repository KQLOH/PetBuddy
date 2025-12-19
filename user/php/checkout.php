<?php
session_start();
require "../include/db.php";
require_once "cart_function.php"; 

// 1. 检查登录
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('Please login to continue.'); window.location.href='login.php';</script>";
    exit;
}
$member_id = $_SESSION['member_id'];

// 2. 获取用户详细信息 (用于预填充表单)
$stmt_user = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt_user->execute([$member_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// 用户全名组合
$user_email = $user['email'] ?? ''; 
$user_first_name = $user['first_name'] ?? '';
$user_last_name = $user['last_name'] ?? '';
$user_phone = $user['phone'] ?? '';
$user_address = $user['address'] ?? '';
$user_postcode = $user['postcode'] ?? '';
$user_city = $user['city'] ?? '';
$user_state = $user['state'] ?? '';

// 3. 确定结账商品逻辑 (只获取选中的)
$all_cart_items = getCartItems($pdo, $member_id);
$checkout_items = [];
$selected_ids_str = "";

// 场景 A: 结账所有
if (isset($_GET['items']) && $_GET['items'] === 'all') {
    $checkout_items = $all_cart_items;
    $selected_ids = array_column($all_cart_items, 'product_id');
    $selected_ids_str = implode(',', $selected_ids);

// 场景 B: 结账选中项
} elseif (!empty($_GET['selected'])) {
    $selected_ids_str = $_GET['selected'];
    $selected_ids = array_map('intval', explode(',', $selected_ids_str));

    foreach ($all_cart_items as $item) {
        if (in_array((int)$item['product_id'], $selected_ids)) {
            $checkout_items[] = $item;
        }
    }
}

// 4. 空购物车处理
if (empty($checkout_items)) {
    echo "<script>alert('No items selected for checkout.'); window.location.href='cart.php';</script>";
    exit;
}

// 5. 计算金额 (使用 checkout_subtotal 避免 header 冲突)
$checkout_subtotal = 0;

foreach ($checkout_items as $item) {
    $line_total = (float)$item['price'] * (int)$item['quantity'];
    $checkout_subtotal += $line_total;
}

$shipping_fee = 15.00;
$total = $checkout_subtotal + $shipping_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - PetBuddy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === 统一配色变量 (PetBuddy Theme) === */
        :root {
            --primary-color: #FFB774;       /* 主橙色 */
            --primary-dark: #E89C55;        /* 深橙色 */
            --bg-sidebar: #fff9f2;          /* 侧边栏淡橙色背景 */
            --border-focus: #FFB774;        /* 输入框选中颜色 */
            --accent-bg: #fffbf6;           /* 选中的单选框背景 */
            --text-dark: #2F2F2F;
            --border-color: #e1e1e1;
        }

        /* 全局重置 */
        * { box-sizing: border-box; font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif; }
        
        body { margin: 0; padding: 0; background: #fff; color: var(--text-dark); display: flex; flex-direction: column; min-height: 100vh; }

        /* === 布局：左右分栏 === */
        .checkout-layout {
            display: flex;
            flex-direction: column-reverse; /* 手机端默认：摘要在下，表单在上 */
            width: 100%;
        }

        /* 左侧：表单区域 */
        .main-col {
            padding: 30px 5%;
            background: #fff;
        }

        /* 右侧：订单摘要 */
        .sidebar-col {
            background: #fafafa;
            padding: 30px 5%;
            border-bottom: 1px solid #e1e1e1;
        }

        /* ✨✨✨ 电脑端专属样式 (分屏滚动) ✨✨✨ */
        @media (min-width: 1001px) {
            body {
                height: 100vh;
                overflow: hidden; /* 锁定 Body */
            }

            .checkout-layout {
                flex-direction: row; /* 左右排布 */
                flex: 1;             /* 占满剩余空间 */
                overflow: hidden;
                margin: 0 auto;
                max-width: 1400px;
            }

            /* 左侧 (Main)：允许独立滚动 */
            .main-col {
                flex: 1 1 58%;
                height: 100%;
                overflow-y: auto;    /* 开启滚动 */
                padding: 40px 6%;
                border-right: 1px solid var(--border-color);
                scrollbar-width: thin; 
                scrollbar-color: #ccc transparent;
            }

            /* 右侧 (Sidebar)：智能滚动 */
            .sidebar-col {
                flex: 1 1 42%;
                height: 100%;
                overflow-y: auto;    /* 内容多时允许滚动 */
                background: var(--bg-sidebar); 
                padding: 40px 6%;
                border-bottom: none;
                scrollbar-width: thin;
                scrollbar-color: #ddd transparent;
            }
        }

        /* === 区域标题 === */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; margin-top: 35px; }
        .section-title { font-size: 19px; font-weight: 500; color: #333; }
        .section-header:first-of-type { margin-top: 0; }

        /* === 输入框间距优化 (宽松版) === */
        .form-group { margin-bottom: 25px; }
        .form-row { display: flex; gap: 25px; width: 100%; margin-bottom: 25px; }
        .form-col { flex: 1; min-width: 0; }

        /* 输入框样式 */
        input[type="text"], input[type="email"], input[type="tel"], select, input[type="password"] {
            width: 100%; padding: 13px; border: 1px solid #d9d9d9; border-radius: 5px;
            font-size: 14px; transition: all 0.2s ease-in-out; color: #333; background: #fff;
        }
        input:focus, select:focus {
            border-color: var(--border-focus); outline: none; box-shadow: 0 0 0 1px var(--border-focus); 
        }
        input[readonly] { background-color: #f9f9f9; color: #777; cursor: not-allowed; }
        label { font-size: 14px; color: #555; display:block; margin-bottom: 6px; }

        /* 复选框样式 */
        .checkbox-wrapper { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        .checkbox-wrapper input { width: 18px; height: 18px; accent-color: var(--primary-dark); margin: 0; cursor: pointer; }
        .checkbox-wrapper label { font-size: 14px; color: #555; cursor: pointer; user-select: none; margin-bottom: 0; }

        /* 运费框 */
        .radio-box-group { border: 1px solid #d9d9d9; border-radius: 5px; overflow: hidden; margin-top: 15px; }
        .radio-box { padding: 18px; display: flex; align-items: center; justify-content: space-between; background: #fff; border-bottom: 1px solid #d9d9d9; cursor: pointer; }
        .radio-box:last-child { border-bottom: none; }
        .radio-label { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #333; }

        /* 支付按钮 */
        .btn-pay {
            width: 100%; padding: 20px; background: var(--primary-color); color: #fff;
            border: none; border-radius: 8px; font-size: 18px; font-weight: 700;
            cursor: pointer; margin-top: 30px; transition: opacity 0.2s, transform 0.2s;
            box-shadow: 0 4px 10px rgba(255, 183, 116, 0.4);
        }
        .btn-pay:hover { background: var(--primary-dark); transform: translateY(-2px); }
        
        .return-cart { display: block; text-align: center; margin-top: 20px; color: var(--primary-dark); text-decoration: none; font-size: 14px; font-weight: 500; }
        .return-cart:hover { text-decoration: underline; }

        /* === 侧边栏摘要样式 === */
        .summary-item { display: flex; align-items: center; gap: 15px; margin-bottom: 18px; }
        .img-wrap { position: relative; width: 65px; height: 65px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; background: #fff; }
        .img-wrap img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
        .qty-badge {
            position: absolute; top: -10px; right: -10px; background: #666; color: white;
            border-radius: 50%; width: 21px; height: 21px; font-size: 12px; font-weight: 600;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.15); border: 2px solid #fff; 
        }
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

        /* === 高级支付卡片设计 === */
        .payment-container { display: flex; flex-direction: column; gap: 12px; }
        .payment-card { background: #fff; border: 2px solid #eee; border-radius: 12px; overflow: hidden; transition: all 0.3s ease; position: relative; }
        .payment-card:hover { border-color: #ddd; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .payment-card.selected { border-color: var(--primary-color); background-color: #fffbf6; box-shadow: 0 0 0 1px var(--primary-color); }
        
        .payment-header-row { padding: 20px; display: flex; align-items: center; cursor: pointer; user-select: none; }
        .payment-header-row input[type="radio"] { display: none; }
        
        .custom-radio { width: 22px; height: 22px; border: 2px solid #ccc; border-radius: 50%; margin-right: 15px; position: relative; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .payment-card.selected .custom-radio { border-color: var(--primary-color); background: var(--primary-color); }
        .payment-card.selected .custom-radio::after { content: '\f00c'; font-family: "Font Awesome 6 Free"; font-weight: 900; color: white; font-size: 12px; }
        
        .payment-label { font-weight: 600; font-size: 15px; color: #333; flex: 1; }
        .payment-icons { display: flex; gap: 8px; opacity: 0.7; }
        .payment-icons i { font-size: 22px; }
        
        .payment-details { display: none; padding: 0 20px 20px 20px; margin-top: -5px; animation: slideDown 0.3s ease-out; }
        .payment-card.selected .payment-details { display: block; }
        
        .helper-text { font-size: 13px; color: #666; background: rgba(0,0,0,0.03); padding: 12px; border-radius: 6px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px; }
        
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <?php include '../include/header.php'; ?>

    <div class="checkout-layout">

        <div class="main-col">
            <form action="place_order.php" method="POST" id="checkoutForm">
                
                <input type="hidden" name="selected_products" value="<?= htmlspecialchars($selected_ids_str) ?>">
                <input type="hidden" name="voucher_code" id="hidden_voucher_code" value="">

                <div class="section-header">
                    <div class="section-title">Contact</div>
                </div>
                <div class="form-group">
                    <input type="email" name="email" value="<?= htmlspecialchars($user_email) ?>" placeholder="Email" readonly>
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
                <div class="form-row">
                    <div class="form-col">
                        <input type="text" name="first_name" placeholder="First name" value="<?= htmlspecialchars($user_first_name) ?>" required>
                    </div>
                    <div class="form-col">
                        <input type="text" name="last_name" placeholder="Last name" value="<?= htmlspecialchars($user_last_name) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Phone" value="<?= htmlspecialchars($user_phone) ?>" required>
                </div>
                
<div class="form-group" style="margin-bottom: 15px;">
    <label style="font-size: 14px; color: #555; margin-bottom: 6px; display:block;">Shipping Address</label>
    
    <input type="text" name="address" placeholder="Address Line 1 " value="<?= htmlspecialchars($user_address) ?>" required style="margin-bottom: 10px;">
    
    <input type="text" name="apartment" placeholder="Address Line 2  ">
    

</div>
<div class="form-row">
    <div class="form-col">
        <input type="text" name="postcode" id="billing_postcode" placeholder="Postcode" value="<?= htmlspecialchars($user_postcode) ?>" maxlength="5" required>
    </div>
    <div class="form-col">
        <input type="text" name="city" id="billing_city" placeholder="City" value="<?= htmlspecialchars($user_city) ?>" required>
    </div>
    <div class="form-col">
        <select name="state" id="billing_state" required style="color: #333;">
            <option value="" disabled <?= empty($user_state) ? 'selected' : '' ?>>State</option>
            <?php 
                $states = ["Johor", "Selangor", "Kuala Lumpur", "Penang", "Perak", "Kedah", "Melaka", "Negeri Sembilan", "Pahang", "Terengganu", "Kelantan", "Perlis", "Sabah", "Sarawak", "Labuan", "Putrajaya"];
                foreach($states as $st) {
                    $selected = ($user_state == $st) ? "selected" : "";
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
                        <div style="font-weight: 600;">RM <?= number_format($shipping_fee, 2) ?></div>
                    </div>
                </div>

                <div class="section-header">
                    <div class="section-title">Payment Method</div>
                </div>
                <p style="font-size: 13px; color: #737373; margin-bottom: 15px;">Select a secure payment method:</p>
                
                <div class="payment-container" id="paymentAccordion">

                    <div class="payment-card"> 
                        <label class="payment-header-row">
                            <input type="radio" name="payment_method" value="Credit Card" required onclick="selectPayment(this)">
                            <div class="custom-radio"></div> <span class="payment-label">Credit / Debit Card</span>
                            <div class="payment-icons">
                                <i class="fab fa-cc-visa" style="color:#1a1f71;"></i>
                                <i class="fab fa-cc-mastercard" style="color:#eb001b;"></i>
                            </div>
                        </label>
                        <div class="payment-details">
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">Cardholder Name</label>
                                <input type="text" name="card_name" placeholder="Name on Card" oninput="validateText(this)">
                            </div>
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">Credit / Debit Card No.</label>
                                <div style="position: relative;">
                                    <input type="text" name="card_number" placeholder="16-digit Card Number" maxlength="16" oninput="validateNumber(this)">
                                    <i class="fas fa-credit-card" style="position: absolute; right: 15px; top: 15px; color: #aaa;"></i>
                                </div>
                            </div>
                            <div class="form-row" style="gap: 15px; margin-bottom: 12px;">
                                <div class="form-col">
                                    <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">CVC/CVV2</label>
                                    <input type="password" name="card_cvc" placeholder="3 digits" maxlength="3" oninput="validateNumber(this)">
                                </div>
                                <div class="form-col" style="flex: 2;">
                                    <label style="font-size:12px; color:#555; margin-bottom:4px; display:block;">Expiry Date</label>
                                    <div style="display: flex; gap: 10px;">
                                        <select name="card_exp_month" style="margin-bottom:0;">
                                            <option value="" disabled selected>Month</option>
                                            <?php for($i=1; $i<=12; $i++) { $m=sprintf("%02d",$i); echo "<option value='$m'>$m</option>"; } ?>
                                        </select>
                                        <select name="card_exp_year" style="margin-bottom:0;">
                                            <option value="" disabled selected>Year</option>
                                            <?php $yr=date("Y"); for($i=0; $i<=10; $i++) { $y=$yr+$i; echo "<option value='$y'>$y</option>"; } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="checkbox-wrapper" style="align-items: flex-start; margin-top: 5px;">
                                <input type="checkbox" id="card_auth" style="margin-top: 3px;">
                                <label for="card_auth" style="font-size: 11px; color: #666; line-height: 1.4;">
                                    I authorize PETBUDDY to debit the above net charges from my credit / debit card.
                                </label>
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
                            <div class="helper-text"><i class="fas fa-lock"></i> Securely login to your bank account via FPX.</div>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label style="font-size:12px; color:#555; margin-bottom:6px; display:block;">Select Bank</label>
                                <select name="fpx_bank" style="cursor:pointer; margin-bottom:0;">
                                    <option value="" disabled selected>Choose your bank...</option>
                                    <option value="Maybank2U">Maybank2U</option>
                                    <option value="CIMB Clicks">CIMB Clicks</option>
                                    <option value="Public Bank">Public Bank</option>
                                    <option value="RHB Bank">RHB Bank</option>
                                    <option value="Hong Leong Bank">Hong Leong Bank</option>
                                    <option value="Ambank">Ambank</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="payment-card">
                        <label class="payment-header-row">
                            <input type="radio" name="payment_method" value="TNG" required onclick="selectPayment(this)">
                            <div class="custom-radio"></div>
                            <span class="payment-label">Touch 'n Go eWallet</span>
                            <div class="payment-icons"><i class="fas fa-wallet" style="color:#005eb8;"></i></div>
                        </label>
                        <div class="payment-details">
                            <input type="tel" name="tng_phone" placeholder="TNG Phone Number (e.g. 0123456789)" maxlength="11" oninput="validateNumber(this)">
                        </div>
                    </div>

                    <div class="payment-card">
                        <label class="payment-header-row">
                            <input type="radio" name="payment_method" value="Cash" required onclick="selectPayment(this)">
                            <div class="custom-radio"></div>
                            <span class="payment-label">Cash on Delivery</span>
                            <div class="payment-icons"><i class="fas fa-money-bill-wave" style="color:#4caf50;"></i></div>
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
                ?>
                <div class="summary-item">
                    <div class="img-wrap">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="img">
                        <span class="qty-badge"><?= $item['quantity'] ?></span>
                    </div>
                    <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                    </div>
                    <div class="item-price"><?= $price_display ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="discount-row">
                <input type="text" class="discount-input" placeholder="Discount code or gift card">
                <button type="button" class="btn-apply">Apply</button>
            </div>
            
            <div class="total-line">
                <span>Subtotal • <?= count($checkout_items) ?> items</span>
                <span>RM <span id="display_subtotal"><?= number_format($checkout_subtotal, 2) ?></span></span>
            </div>
            
            <div class="total-line" id="discount_row" style="display:none; color: var(--primary-dark);">
                <span style="display:flex; align-items:center; gap:5px;">
                    Discount <i class="fas fa-tag"></i>
                </span>
                <span>- RM <span id="display_discount">0.00</span></span>
            </div>

            <div class="total-line">
                <span style="display:flex; align-items:center; gap:5px;">
                    Shipping <i class="far fa-question-circle" style="font-size:12px; color:#737373;"></i>
                </span>
                <span>RM <?= number_format($shipping_fee, 2) ?></span>
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
        // 1. 支付方式切换逻辑 (自动处理必填项)
        function selectPayment(radio) {
            // A. 视觉切换
            const cards = document.querySelectorAll('.payment-card');
            cards.forEach(card => card.classList.remove('selected'));
            const parentCard = radio.closest('.payment-card');
            parentCard.classList.add('selected');

            // B. 必填项逻辑
            const method = radio.value;
            
            // 先清空所有支付相关的 required
            const allInputs = document.querySelectorAll('[name^="card_"], [name^="fpx_"], [name^="tng_"], #card_auth');
            allInputs.forEach(input => input.removeAttribute('required'));

            // 根据选项重新添加 required
            if (method === 'Credit Card') {
                setRequired('card_name');
                setRequired('card_number');
                setRequired('card_cvc');
                setRequired('card_exp_month');
                setRequired('card_exp_year');
                document.getElementById('card_auth').setAttribute('required', 'required');
            } 
            else if (method === 'FPX') {
                setRequired('fpx_bank');
            } 
            else if (method === 'TNG') {
                setRequired('tng_phone');
            }
        }

        function setRequired(name) {
            const input = document.querySelector(`[name="${name}"]`);
            if (input) input.setAttribute('required', 'required');
        }

        function validateNumber(input) { input.value = input.value.replace(/[^0-9]/g, ''); }
        function validateText(input) { input.value = input.value.replace(/[^a-zA-Z\s]/g, ''); }

        // 2. 优惠券 AJAX 逻辑
        $(document).ready(function() {
            $(".btn-apply").click(function(e) {
                e.preventDefault();
                
                let code = $(".discount-input").val().trim();
                // 获取 PHP 渲染好的 subtotal
                let subtotal = parseFloat($("#display_subtotal").text().replace(/,/g, ''));
                let shipping = <?= $shipping_fee ?>;

                if(!code) {
                    Swal.fire("Error", "Please enter a code", "error");
                    return;
                }

                $.ajax({
                    url: "apply_voucher.php", // 确保你有这个文件
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

                            Swal.fire({
                                icon: 'success',
                                title: 'Applied!',
                                text: 'Saved RM ' + discount.toFixed(2),
                                toast: true, position: 'top-end', showConfirmButton: false, timer: 2000
                            });
                            
                            $(".discount-input").prop('disabled', true);
                            $(".btn-apply").text("Applied").css("background", "#2F2F2F");

                        } else {
                            Swal.fire("Invalid Code", res.message, "error");
                            $("#hidden_voucher_code").val("");
                            $("#discount_row").hide();
                            $("#display_total").text((subtotal + shipping).toFixed(2));
                        }
                    },
                    error: function() {
                        // 如果还没有创建 apply_voucher.php，这里会报错
                        Swal.fire("Error", "Function not available yet (Missing backend file)", "error");
                    }
                });
            });
        });


        // === 3. 自动填充 Postcode -> City & State ===
            $(document).ready(function() {
                
                $("#billing_postcode").on("keyup change", function() {
                    var postcode = $(this).val();

                    // 马来西亚邮编必须是 5 位数字
                    if (postcode.length === 5 && $.isNumeric(postcode)) {
                        
                        // 显示正在加载...
                        $("#billing_city").attr("placeholder", "Searching...");
                        
                        $.ajax({
                            url: "https://api.zippopotam.us/my/" + postcode,
                            cache: false,
                            dataType: "json",
                            type: "GET",
                            success: function(result, success) {
                                // 1. 获取城市
                                // API 返回的 place name 通常是大写，我们把它转为首字母大写
                                var city = result['places'][0]['place name'];
                                $("#billing_city").val(toTitleCase(city));

                                // 2. 获取州属
                                var state = result['places'][0]['state'];
                                
                                // 3. 匹配下拉菜单 (State Dropdown)
                                // API 返回的可能叫 "Wilayah Persekutuan Kuala Lumpur"，我们要匹配简单的 "Kuala Lumpur"
                                $("#billing_state option").each(function() {
                                    var optionText = $(this).text();
                                    // 如果 API 的州属包含下拉单里的字 (例如 API="Selangor" 包含 Option="Selangor")
                                    if (state.includes(optionText) || optionText.includes(state)) {
                                        $(this).prop('selected', true);
                                    }
                                });

                                // 恢复 placeholder
                                $("#billing_city").attr("placeholder", "City");
                            },
                            error: function(result, success) {
                                // 如果找不到邮编 (比如输入了不存在的号码)
                                console.log("Postcode not found");
                            }
                        });
                    }
                });

                // 辅助函数：把全大写转为首字母大写 (KUALA LUMPUR -> Kuala Lumpur)
                function toTitleCase(str) {
                    return str.replace(/\w\S*/g, function(txt){
                        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                    });
                }
            });


    </script>

</body>
</html>