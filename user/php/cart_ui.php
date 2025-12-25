<?php


$total_price = 0; 

if (!empty($cart_items) && count($cart_items) > 0): 
    foreach ($cart_items as $row):
        $subtotal = $row['price'] * $row['quantity'];
        $total_price += $subtotal;
        

        $displayImage = productImageUrl($row['image']);
?>
        <div class="cart-item">
            <img src="<?= htmlspecialchars($displayImage) ?>" alt="Product">
            
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
                <img src="../images/dusbin.png" alt="dusbin" class="custom-icon">
            </button>
        </div>
<?php 
    endforeach; 
else: 
?>
    <div style="text-align: center; margin-top: 60px; color: #999;">
        <p>Your cart is empty.</p>
        <a href="product_listing.php" class="primary-link" style="display:block; margin-top:10px;">Go Shopping</a>
    </div>
<?php 
endif; 
?>