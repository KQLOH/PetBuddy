<?php
session_start();
require "../include/db.php";
require_once "cart_function.php";

// 1. 检查登录状态
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('Please login to view your cart.'); window.location.href='home.php';</script>";
    exit;
}

$member_id = $_SESSION['member_id'];
$cart_items = getCartItems($pdo, $member_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetBuddy | My Shopping Cart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* 页面专属样式 */
        .cart-container { max-width: 1150px; margin: 40px auto; padding: 0 20px; display: flex; gap: 30px; align-items: flex-start; }
        
        .cart-list { flex: 2; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 25px; }
        
        .cart-header-row { display: flex; border-bottom: 2px solid #f5f5f5; padding-bottom: 15px; margin-bottom: 15px; font-weight: 700; color: #555; align-items: center; }
        
        .col-check { width: 40px; text-align: center; }
        .col-product { flex: 3; }
        .col-price { flex: 1; text-align: center; }
        .col-qty { flex: 1; text-align: center; }
        .col-total { flex: 1; text-align: right; }
        .col-action { width: 50px; text-align: right; }

        .cart-row { 
            display: flex; 
            align-items: center; 
            border-bottom: 1px solid #eee; 
            padding: 20px 0; 
            transition: background 0.2s; 
            cursor: pointer; 
        }
        .cart-row:last-child { border-bottom: none; }
        .cart-row.selected { background-color: #fffbf6; } 
        
        .product-info { display: flex; gap: 15px; align-items: center; }
        .product-info img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
        .product-name { font-weight: 600; color: #333; font-size: 16px; }

        input[type="checkbox"].cart-checkbox {
            width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-dark);
        }

        .page-qty-wrapper { display: flex; align-items: center; justify-content: center; background: #f9f9f9; border-radius: 6px; padding: 5px; width: fit-content; margin: auto; }
        .page-qty-btn { border: none; background: transparent; width: 30px; height: 30px; font-size: 18px; cursor: pointer; color: #555; transition: 0.2s; display: flex; align-items: center; justify-content: center;}
        .page-qty-btn:hover { background: #e0e0e0; border-radius: 4px; color: #000; }
        .page-qty-display { min-width: 30px; text-align: center; font-weight: 600; font-size: 15px; }

        .item-subtotal { font-weight: 700; color: var(--primary-dark); }
        .page-remove-btn { color: #aaa; background: none; border: none; cursor: pointer; font-size: 18px; transition: 0.2s; }
        .page-remove-btn:hover { color: #ff4d4d; }

        .cart-summary { flex: 1; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 25px; position: sticky; top: 100px; }
        .summary-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; color: #555; }
        .summary-total { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 15px; border-top: 2px solid #f5f5f5; font-size: 20px; font-weight: 800; color: #333; }
        
        .checkout-btn { display: block; width: 100%; background: var(--primary-color); color: white; text-align: center; padding: 15px; border-radius: 8px; font-size: 16px; font-weight: 600; margin-top: 25px; border: none; cursor: pointer; text-decoration: none; transition: 0.3s; box-shadow: 0 5px 15px rgba(255, 183, 116, 0.4); }
        .checkout-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .checkout-btn.disabled { background: #ccc; cursor: not-allowed; box-shadow: none; }

        .empty-cart-msg { text-align: center; padding: 60px; color: #777; font-size: 18px; }
        .empty-cart-msg a { color: var(--primary-color); font-weight: 700; text-decoration: none; }

        .processing { opacity: 0.5; pointer-events: none; position: relative; }
        .continue-link { display: block; text-align: center; margin-top: 15px; font-size: 14px; color: #888; text-decoration: none; transition: 0.3s; font-weight: 500; }
        .continue-link:hover { color: var(--primary-dark); text-decoration: underline; }

        @media (max-width: 768px) {
            .cart-header-row { display: none; }
            .cart-row { flex-wrap: wrap; gap: 15px; position: relative; padding-left: 40px; }
            .col-check { position: absolute; top: 20px; left: 0; }
            .col-product { width: 100%; flex: none; }
            .col-price, .col-qty, .col-total { flex: auto; text-align: left; }
            .col-action { position: absolute; top: 20px; right: 0; }
        }
    </style>
</head>
<body>

<?php include "../include/header.php"; ?>

<div class="cart-container">
    
    <div class="cart-list">
        <h2 style="margin-bottom: 25px;">Shopping Cart</h2>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart-msg">
                <p>Your cart is currently empty.</p>
                <br>
                <a href="home.php">← Continue Shopping</a>
            </div>
        <?php else: ?>
            
            <div class="cart-header-row">
                <div class="col-check">
                    <input type="checkbox" id="selectAll" class="cart-checkbox">
                </div>
                <div class="col-product">Product</div>
                <div class="col-price">Price</div>
                <div class="col-qty">Quantity</div>
                <div class="col-total">Total</div>
                <div class="col-action"></div>
            </div>

            <div id="cartPageItems">
                <?php foreach ($cart_items as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                ?>
                <div class="cart-row" data-id="<?= $item['product_id'] ?>" data-price="<?= $item['price'] ?>">
                    
                    <div class="col-check">
                        <input type="checkbox" class="item-check cart-checkbox">
                    </div>
                    
                    <div class="col-product">
                        <div class="product-info">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="img">
                            <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
                        </div>
                    </div>
                    <div class="col-price">RM <?= number_format($item['price'], 2) ?></div>
                    <div class="col-qty">
                        <div class="page-qty-wrapper">
                            <button class="page-qty-btn decrease">-</button>
                            <span class="page-qty-display"><?= $item['quantity'] ?></span>
                            <button class="page-qty-btn increase">+</button>
                        </div>
                    </div>
                    <div class="col-total">
                        RM <span class="row-subtotal"><?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="col-action">
                        <button class="page-remove-btn">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <?php if (!empty($cart_items)): ?>
    <div class="cart-summary">
        <div class="summary-title">Cart Totals</div>
        
        <div class="summary-row">
            <span id="selectedCount">0 items selected</span>
        </div>

        <div class="summary-row">
            <span>Subtotal</span>
            <span>RM <span id="pageSubtotal">0.00</span></span>
        </div>
        <div class="summary-row">
            <span>Shipping</span>
            <span>Free</span>
        </div>
        
        <div class="summary-total">
            <span>Total</span>
            <span style="color: var(--primary-dark);">RM <span id="pageTotal">0.00</span></span>
        </div>

        <button id="btnCheckout" class="checkout-btn disabled" disabled>Proceed to Checkout</button>
        
        <a href="home.php" class="continue-link">Or Continue Shopping</a>
        
    </div>
    <?php endif; ?>

</div>

<?php include "../include/footer.php"; ?>
<?php include '../include/chat_widget.php'; ?>
<script>
$(document).ready(function() {
    
    // === 1. 计算总价 ===
    function updatePageTotals() {
        let total = 0;
        let count = 0;

        $(".cart-row").each(function() {
            if ($(this).find(".item-check").is(":checked")) {
                let price = parseFloat($(this).data("price"));
                let qty = parseInt($(this).find(".page-qty-display").text());
                total += price * qty;
                count++;
                $(this).addClass("selected");
            } else {
                $(this).removeClass("selected");
            }
        });

        $("#pageSubtotal").text(total.toFixed(2));
        $("#pageTotal").text(total.toFixed(2));
        $("#selectedCount").text(count + (count === 1 ? " item" : " items") + " selected");

        if (count === 0) {
            $("#btnCheckout").addClass("disabled").prop("disabled", true);
        } else {
            $("#btnCheckout").removeClass("disabled").prop("disabled", false);
        }
    }

    updatePageTotals(); 

    // === 2. 点击整行选择商品 ===
    $(".cart-row").click(function(e) {
        if ($(e.target).is("input[type='checkbox']") || 
            $(e.target).closest("button").length > 0) {
            return;
        }
        let $checkbox = $(this).find(".item-check");
        $checkbox.prop("checked", !$checkbox.prop("checked"));
        $checkbox.trigger("change");
    });

    // === 3. 全选/反选 ===
    $("#selectAll").change(function() {
        let isChecked = $(this).is(":checked");
        $(".item-check").prop("checked", isChecked);
        updatePageTotals();
    });

    // === 4. 单个复选框变化 ===
    $(".cart-list").on("change", ".item-check", function() {
        if ($(".item-check:not(:checked)").length > 0) {
            $("#selectAll").prop("checked", false);
        } else {
            $("#selectAll").prop("checked", true);
        }
        updatePageTotals();
    });

    // === 5. 数量变更 ===
    $(document).off("click", ".page-qty-btn").on("click", ".page-qty-btn", function() {
        let $btn = $(this);
        let $row = $btn.closest(".cart-row");
        let pid = $row.data("id");
        let price = parseFloat($row.data("price"));
        let $display = $row.find(".page-qty-display");
        let currentQty = parseInt($display.text());
        let action = $btn.hasClass("increase") ? "increase" : "decrease";

        if (action === "decrease" && currentQty <= 1) return;

        $row.addClass("processing");

        $.ajax({
            url: "update_cart_quantity.php",
            type: "POST",
            data: { product_id: pid, action: action },
            success: function(response) {
                $row.removeClass("processing");
                if (response.trim() === "success") {
                    let newQty = (action === "increase") ? currentQty + 1 : currentQty - 1;
                    $display.text(newQty);
                    let newSubtotal = (price * newQty).toFixed(2);
                    $row.find(".row-subtotal").text(newSubtotal);
                    updatePageTotals();
                    if (typeof refreshCartSidebar === "function") refreshCartSidebar();
                }
            }
        });
    });

    // === 6. 删除商品 ===
    $(document).off("click", ".page-remove-btn").on("click", ".page-remove-btn", function() {
        let $row = $(this).closest(".cart-row");
        let pid = $row.data("id");

        Swal.fire({
            title: 'Remove item?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "remove_cart.php",
                    type: "POST",
                    data: { product_id: pid },
                    success: function(response) {
                        if (response.trim() === "success") {
                            $row.fadeOut(300, function() { 
                                $(this).remove(); 
                                updatePageTotals();
                                if ($(".cart-row").length === 0) location.reload(); 
                            });
                            if (typeof refreshCartSidebar === "function") refreshCartSidebar();
                        }
                    }
                });
            }
        });
    });

    // === 7. 跳转结算 (携带选中的ID) ===
    // 用户点击 "Proceed to Checkout" 按钮时
    $("#btnCheckout").click(function() {
        let selectedIds = [];
        $(".item-check:checked").each(function() {
            selectedIds.push($(this).closest(".cart-row").data("id"));
        });
        // 跳转到 checkout.php，只传递选中的商品ID
        window.location.href = "checkout.php?selected=" + selectedIds.join(",");
    });

});
</script>

</body>
</html>