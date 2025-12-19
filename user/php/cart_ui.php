<?php
// php/cart_ui.php
// 这个文件只负责显示，假设引入它的文件已经准备好了 $cart_items 数组

$total_price = 0; // 初始化总价

if (!empty($cart_items) && count($cart_items) > 0): 
    foreach ($cart_items as $row):
        $subtotal = $row['price'] * $row['quantity'];
        $total_price += $subtotal;
?>
        <div class="cart-item">
            <img src="<?= htmlspecialchars($row['image']) ?>" alt="Product">
            
            <div class="cart-item-info">
                <span class="cart-item-title"><?= htmlspecialchars($row['name']) ?></span>
                <div class="cart-item-price">RM <?= number_format($row['price'], 2) ?></div>
                
                <div class="qty-control-wrapper">
                    <button class="qty-btn decrease" data-id="<?= $row['product_id'] ?>">-</button>
                    <span class="qty-display"><?= $row['quantity'] ?></span>
                    <button class="qty-btn increase" data-id="<?= $row['product_id'] ?>">+</button>
                </div>
            </div>

            <button class="remove-btn" data-id="<?= $row['product_id'] ?>">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
<?php 
    endforeach; 
else: 
?>
    <div style="text-align: center; margin-top: 60px; color: #999;">
        <p>Your cart is empty.</p>
        <a href="products.php" class="primary-link" style="display:block; margin-top:10px;">Go Shopping</a>
    </div>
<?php 
endif; 
?>