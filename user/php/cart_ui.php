<?php
// cart_ui.php
// 防止直接访问报错
if (!isset($cart_items)) { $cart_items = []; }
if (!function_exists('productImageUrl')) { require_once __DIR__ . "/../include/product_utils.php"; }

$total_price = 0;

if (!empty($cart_items) && count($cart_items) > 0):
    foreach ($cart_items as $row):
        // 计算单行总价
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
    
    // 传递总价给 Sidebar
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
        if(document.getElementById("cartFooter")) {
            document.getElementById("cartFooter").style.display = "none";
        }
        if(document.getElementById("cartSidebarTotal")) {
            document.getElementById("cartSidebarTotal").innerText = "0.00";
        }
        if(typeof updateCartBadge === "function") { updateCartBadge(); }
    </script>
<?php
endif;
?>

<script>
    // ============================================
    // 删除功能 (包含两个弹窗)
    // ============================================
    function deleteCartItem(pid) {
        
        // 执行删除的动作
        const performDelete = () => {
            $.ajax({
                // ✨✨✨ 关键点：这里必须改成 remove_cart.php (对应你的文件名) ✨✨✨
                url: 'remove_cart.php', 
                type: 'POST',
                data: { product_id: pid },
                success: function(response) {
                    if (response.trim() === 'success') {
                        // 1. 刷新侧边栏 (不刷新整个页面)
                        if (typeof refreshCartSidebar === 'function') {
                            refreshCartSidebar();
                        } else {
                            location.reload(); 
                        }

                        // 2. ✨ Pop-up Message 2: Success (删除成功提示)
                        if (typeof safeToast === 'function') {
                            safeToast("Item removed from cart");
                        } else {
                            alert("Item removed from cart");
                        }
                    } else {
                        alert("Error: " + response);
                    }
                },
                error: function() {
                    alert("System error connecting to server.");
                }
            });
        };

        // ✨ Pop-up Message 1: Confirmation (确认提示)
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Remove Item?',
                text: "Are you sure you want to remove this item?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2F2F2F', 
                cancelButtonColor: '#d33',     
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    performDelete();
                }
            });
        } else {
            // 降级处理
            if (confirm("Are you sure you want to remove this item?")) {
                performDelete();
            }
        }
    }

    // 数量更新函数
    function changeQty(pid, action) {
        let display = document.getElementById('qty-val-' + pid);
        if(!display) return;
        
        let currentQty = parseInt(display.innerText);
        let newQty = currentQty;

        if (action === 'plus') newQty++;
        if (action === 'minus' && currentQty > 1) newQty--;

        if (newQty !== currentQty) {
            display.innerText = newQty; 
            $.ajax({
                url: "update_cart_quantity.php",
                type: "POST",
                data: { product_id: pid, quantity: newQty },
                success: function() {
                    if (typeof refreshCartSidebar === 'function') {
                        refreshCartSidebar();
                    } else {
                        location.reload();
                    }
                }
            });
        }
    }
</script>