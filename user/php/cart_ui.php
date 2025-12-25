<?php
if (!isset($cart_items)) {
    $cart_items = [];
}
if (!function_exists('productImageUrl')) {
    require_once __DIR__ . "/../include/product_utils.php";
}

$total_price = 0;

if (!empty($cart_items) && count($cart_items) > 0):
    foreach ($cart_items as $row):
        $price = isset($row['price']) ? $row['price'] : 0;
        $qty = isset($row['quantity']) ? $row['quantity'] : 0;
        $subtotal = $price * $qty;
        $total_price += $subtotal;
        $displayImage = productImageUrl($row['image']);
?>
        <div class="cart-item" id="cart-item-<?= $row['product_id'] ?>">
            <img src="<?= htmlspecialchars($displayImage) ?>" alt="Product">

            <div class="cart-item-info">
                <a href="product_detail.php?id=<?= $row['product_id'] ?>" class="cart-item-title" style="text-decoration:none; color:inherit;">
                    <?= htmlspecialchars($row['name']) ?>
                </a>
                <div class="cart-item-price">RM <?= number_format($price, 2) ?></div>

                <div class="qty-control-wrapper">
                    <button class="qty-btn" onclick="changeQty(<?= $row['product_id'] ?>, 'minus')">-</button>
                    <span class="qty-display" id="qty-val-<?= $row['product_id'] ?>"><?= $qty ?></span>
                    <button class="qty-btn" onclick="changeQty(<?= $row['product_id'] ?>, 'plus')">+</button>
                </div>
            </div>

            <button type="button" class="remove-btn" onclick="deleteCartItem(<?= $row['product_id'] ?>)" style="border:none; background:none; cursor:pointer;">
                <img src="../images/dusbin.png" alt="delete" class="custom-icon" style="width:20px; height:20px;">
            </button>
        </div>
    <?php
    endforeach;

    echo '<script>
        if(document.getElementById("cartSidebarTotal")) {
            document.getElementById("cartSidebarTotal").innerText = "' . number_format($total_price, 2) . '";
        }
        if(document.getElementById("cartFooter")) {
            document.getElementById("cartFooter").style.display = "block";
        }
        if(typeof updateCartBadge === "function") { updateCartBadge(); }
    </script>';

else:
    ?>
    <div style="text-align: center; margin-top: 60px; color: #999;">
        <img src="../images/shopping-bag.png" style="width:50px; opacity:0.3; margin-bottom:15px;">
        <p>Your cart is empty.</p>
        <a href="product_listing.php" class="primary-link" style="display:block; margin-top:10px; color:#FFB774; text-decoration:none; font-weight:600;">Go Shopping</a>
    </div>
    <script>
        if (document.getElementById("cartFooter")) {
            document.getElementById("cartFooter").style.display = "none";
        }
        if (document.getElementById("cartSidebarTotal")) {
            document.getElementById("cartSidebarTotal").innerText = "0.00";
        }
        if (typeof updateCartBadge === "function") {
            updateCartBadge();
        }
    </script>
<?php
endif;
?>

<div id="customAlert" class="custom-alert-overlay">
    <div class="custom-alert-box">
        <div id="customAlertIcon" class="custom-alert-icon"></div>
        <h3 id="customAlertTitle" class="custom-alert-title"></h3>
        <p id="customAlertText" class="custom-alert-text"></p>
        <div id="customAlertButtons" class="custom-alert-buttons">
            <button id="customAlertCancel" class="btn-alert btn-alert-cancel" style="display:none">Cancel</button>
            <button id="customAlertConfirm" class="btn-alert btn-alert-confirm">OK</button>
        </div>
    </div>
</div>

<style>
    .custom-alert-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        display: none;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .custom-alert-overlay.show {
        opacity: 1;
    }

    .custom-alert-box {
        background: white;
        width: 90%;
        max-width: 400px;
        padding: 30px;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .custom-alert-overlay.show .custom-alert-box {
        transform: scale(1);
    }

    .custom-alert-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        font-weight: bold;
    }

    .icon-success {
        background: #d1fae5;
        color: #10b981;
    }

    .icon-error {
        background: #fee2e2;
        color: #ef4444;
    }

    .icon-confirm {
        background: #fef3c7;
        color: #f59e0b;
    }

    .custom-alert-title {
        font-size: 20px;
        margin-bottom: 10px;
        color: #333;
    }

    .custom-alert-text {
        font-size: 15px;
        color: #666;
        margin-bottom: 25px;
        line-height: 1.5;
    }

    .custom-alert-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .btn-alert {
        padding: 10px 25px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: 0.2s;
    }

    .btn-alert-cancel {
        background: #f3f4f6;
        color: #666;
    }

    .btn-alert-cancel:hover {
        background: #e5e7eb;
    }

    .btn-alert-confirm {
        background: #FFB774;
        color: white;
    }

    .btn-alert-confirm:hover {
        filter: brightness(0.95);
    }
</style>

<script>
    let confirmCallback = null;

    function showCustomAlert(type, title, text, autoClose = false) {
        const overlay = document.getElementById('customAlert');
        const icon = document.getElementById('customAlertIcon');
        const btnCancel = document.getElementById('customAlertCancel');
        const btnConfirm = document.getElementById('customAlertConfirm');

        document.getElementById('customAlertTitle').innerText = title;
        document.getElementById('customAlertText').innerText = text;

        icon.className = 'custom-alert-icon';
        if (type === 'success') {
            icon.classList.add('icon-success');
            icon.innerHTML = '✓';
            btnCancel.style.display = 'none';
            btnConfirm.innerText = 'OK';
            btnConfirm.onclick = closeCustomAlert;
        } else if (type === 'error') {
            icon.classList.add('icon-error');
            icon.innerHTML = '✕';
            btnCancel.style.display = 'none';
            btnConfirm.innerText = 'OK';
            btnConfirm.onclick = closeCustomAlert;
        } else {
            icon.classList.add('icon-confirm');
            icon.innerHTML = '?';
            btnCancel.style.display = 'block';
            btnCancel.onclick = closeCustomAlert;
            btnConfirm.innerText = 'Yes, Delete';
            btnConfirm.onclick = function() {
                if (confirmCallback) confirmCallback();
                closeCustomAlert();
            };
        }

        overlay.style.display = 'flex';
        setTimeout(() => overlay.classList.add('show'), 10);

        if (autoClose) {
            setTimeout(closeCustomAlert, 4000);
        }
    }

    function closeCustomAlert() {
        const overlay = document.getElementById('customAlert');
        overlay.classList.remove('show');
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 300);
    }

    function deleteCartItem(pid) {

        confirmCallback = function() {
            $.ajax({
                url: 'remove_cart.php',
                type: 'POST',
                data: {
                    product_id: pid
                },
                success: function(response) {
                    if (response.trim() === 'success') {

                        if (typeof refreshCartSidebar === 'function') {
                            refreshCartSidebar();
                        } else {
                            location.reload();
                        }

                        showCustomAlert('success', 'Removed', 'Item removed from cart.', true);

                    } else {
                        showCustomAlert('error', 'Error', "Error: " + response);
                    }
                },
                error: function() {
                    showCustomAlert('error', 'System Error', "System error connecting to server.");
                }
            });
        };

        showCustomAlert('confirm', 'Remove Item?', 'Are you sure you want to remove this item?');
    }

    function changeQty(pid, action) {
        let display = document.getElementById('qty-val-' + pid);
        if (!display) return;

        let currentQty = parseInt(display.innerText);
        let newQty = currentQty;

        if (action === 'plus') {
            newQty++;
        } else if (action === 'minus') {
            if (currentQty > 1) {
                newQty--;
            } else {
                if (typeof deleteCartItem === 'function') {
                    deleteCartItem(pid);
                }
                return;
            }
        }

        if (newQty !== currentQty) {
            display.innerText = newQty;

            $.ajax({
                url: "update_cart_quantity.php",
                type: "POST",
                data: {
                    product_id: pid,
                    quantity: newQty
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === 'success') {

                        if (typeof refreshCartSidebar === 'function') {
                            refreshCartSidebar();
                        } else {
                            location.reload();
                        }

                    } else {
                        display.innerText = currentQty;
                        if (typeof showCustomAlert === 'function') {
                            showCustomAlert('error', 'Error', response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                    display.innerText = currentQty;
                }
            });
        }
    }
</script>