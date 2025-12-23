<?php
/**
 * Footer Component
 * 包含站点底部导航、版权信息以及全局购物车 AJAX 逻辑
 * 通常被各个页面 include 引用
 */
?>
<footer class="site-footer">
    <div class="footer-container">

        <div class="footer-col">
            <div class="logo">
                <div class="logo-circle">🐾</div>
                <span>PetBuddy</span>
            </div>

            <h4 class="company-name">PetBuddy Online Shop</h4>

            <p class="footer-desc">
                Welcome to PetBuddy, your trusted source for premium pet supplies.  
                We bring high-quality food, toys, and accessories to keep your pets happy and healthy.
            </p>

            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>

        <div class="footer-col">
            <h4 class="footer-title">MY ACCOUNT</h4>
            <ul class="footer-links">
                <li><a href="account.php">My Account</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="wishlist.php">My Wishlist</a></li>
                <li><a href="tracking.php">Order Tracking</a></li>
                <li><a href="cart.php">Shopping Cart</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4 class="footer-title">COMPANY</h4>
            <ul class="footer-links">
                <li><a href="about.php">About Us</a></li>
                <li><a href="products.php">Shop</a></li>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="faq.php">FAQs</a></li>
                <li><a href="../php/policy.php">Shipping Policy</a></li>
                <li><a href="privacy.php">Privacy Notice</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4 class="footer-title">SUBSCRIBE TO OUR EMAIL</h4>

            <form class="subscribe-form">
                <input type="email" placeholder="Your email address" required />
                <div class="underline"></div>
            </form>

            <div class="payment-icons">
                <img src="../images/payments.png" alt="Accepted Payments" onerror="this.style.display='none'">
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        <p>Copyright © <?php echo date("Y"); ?> PetBuddy Online Shop. All rights reserved.</p>
    </div>
</footer>

<style>
    :root {
        --primary-color: #FFB774;
        --primary-dark: #E89C55;
        --text-dark: #2F2F2F;
        --border-color: #e8e8e8;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Inter", system-ui, sans-serif; }
    body { background: #fff; }

    /* Footer Layout */
    .site-footer { padding: 80px 0 40px; background: #fff; color: var(--text-dark); border-top: 1px solid #eee; }
    .footer-container { max-width: 1300px; margin: auto; padding: 0 40px; display: grid; grid-template-columns: 1.7fr 1fr 1fr 1.3fr; gap: 60px; }
    .footer-col { display: flex; flex-direction: column; }
    
    /* Typography */
    .company-name { margin: 20px 0 10px; font-weight: 600; font-size: 18px; }
    .footer-desc { color: #555; line-height: 1.7; font-size: 14px; max-width: 260px; }
    .footer-title { font-size: 15px; font-weight: 700; letter-spacing: 1px; margin-bottom: 20px; color: var(--primary-color); }
    
    /* Social Icons */
    .footer-social { margin-top: 20px; display: flex; gap: 20px; }
    .footer-social a { font-size: 18px; color: #333; transition: 0.2s; }
    .footer-social a:hover { color: var(--primary-dark); }
    
    /* Links List */
    .footer-links { list-style: none; padding: 0; }
    .footer-links li { margin-bottom: 12px; }
    .footer-links a { text-decoration: none; font-size: 14px; color: #555; transition: 0.2s; }
    .footer-links a:hover { color: var(--primary-dark); }
    
    /* Form & Images */
    .subscribe-form input { border: none; outline: none; width: 100%; padding: 8px 0; font-size: 14px; }
    .underline { width: 100%; height: 2px; background: var(--primary-color); margin-top: 5px; }
    .payment-icons img { width: 250px; height: auto; display: block; padding-top: 10px; opacity: 0.9; }
    
    /* Bottom Bar */
    .footer-bottom { margin-top: 40px; text-align: center; color: #777; font-size: 14px; padding-top: 20px; border-top: 1px solid #eee; }
    
    /* Logo */
    .logo { display: flex; align-items: center; gap: 10px; font-size: 22px; font-weight: 700; color: var(--text-dark); }
    .logo-circle { width: 30px; height: 30px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; }

    /* Responsive */
    @media (max-width: 900px) { .footer-container { grid-template-columns: 1fr 1fr; gap: 40px; } }
    @media (max-width: 600px) { .footer-container { grid-template-columns: 1fr; } }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/**
 * 购物车系统全局逻辑 (AJAX)
 * 包含：刷新侧边栏、添加商品、更新数量、删除商品
 */

// 1. 刷新侧边栏购物车内容
function refreshCartSidebar() {
    $.ajax({
        url: "fetch_cart.php", // 获取最新购物车 HTML
        type: "GET",
        success: function(data) {
            // 更新 DOM
            $("#cartBody").html(data);
            
            // 检查隐藏域中的最新总价 (由 fetch_cart.php 返回)
            var newTotal = $("#ajax-new-total").val();
            
            // 如果总价有效且大于0，显示底部结算栏
            if (newTotal && newTotal !== "0.00") {
                $("#cartSidebarTotal").text(newTotal);
                $("#cartFooter").show();
            } else {
                $("#cartFooter").hide();
            }
            
            setTimeout(function() {
                if (typeof updateCartBadge === 'function') {
                    updateCartBadge();
                }
            }, 200);
        },
        error: function() {
            console.error("Failed to refresh cart sidebar.");
        }
    });
}

// 2. 添加商品到购物车 (Add to Cart)
// 使用 .off().on() 确保事件只绑定一次，防止重复触发
$(document).off("click", ".add-btn").on("click", ".add-btn", function(e) {
    e.preventDefault(); 
    
    let $btn = $(this);
    let pid = $btn.data("id");
    
    // 防止快速连点 (简单的防抖锁)
    if($btn.data('loading')) return;
    $btn.data('loading', true);

    $.ajax({
        url: "add_to_cart.php",
        type: "POST",
        data: { product_id: pid },
        success: function(response) {
            $btn.data('loading', false); // 解除锁定
            let res = response.trim();

            if (res.includes("added") || res.includes("increased") || res.includes("success")) {
                // 成功：刷新侧边栏
                refreshCartSidebar();
                
                // 如果页面上有 openCart 函数 (在 header.php 定义)，则自动打开侧边栏
                if (typeof openCart === "function") openCart(); 
                
                // 弹出成功提示 (Toast)
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Added to cart',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true
                });

            } else if (res.includes("login") || res.includes("required")) {
                // 需要登录
                Swal.fire("Please Login", "You need to login to add items.", "warning");
                if (typeof openLogin === "function") openLogin();
            } else {
                // 其他未知错误
                console.error("Add cart error:", res);
                Swal.fire("Error", "Could not add item. Check console.", "error");
            }
        },
        error: function() {
            $btn.data('loading', false);
            Swal.fire("Error", "Connection failed.", "error");
        }
    });
});

// 3. 侧边栏：修改数量 (+/-)
$(document).off("click", ".qty-btn").on("click", ".qty-btn", function() {
    let $btn = $(this);
    let pid = $btn.data("id");
    let action = $btn.hasClass("increase") ? "increase" : "decrease";

    // 获取当前显示的数量，用于前端校验
    let $qtySpan = $btn.siblings(".qty-display");
    let currentQty = parseInt($qtySpan.text());
    
    // 如果是减少且当前数量为1，则阻止操作 (不允许减到0)
    if (action === "decrease" && currentQty <= 1) return;

    // 锁定按钮防止连点
    if($btn.prop('disabled')) return;
    $btn.prop('disabled', true);

    $.ajax({
        url: "update_cart_quantity.php",
        type: "POST",
        data: { product_id: pid, action: action },
        success: function(response) {
            refreshCartSidebar(); // 成功后刷新
            setTimeout(function() { $btn.prop('disabled', false); }, 300); // 300ms 后解锁
        },
        error: function() {
            $btn.prop('disabled', false); // 出错立即解锁
        }
    });
});

// 4. 侧边栏：删除商品
$(document).off("click", ".remove-btn").on("click", ".remove-btn", function() {
    let pid = $(this).data("id");
    
    // 弹出确认框
    Swal.fire({
        title: 'Remove item?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, remove'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "remove_cart.php",
                type: "POST",
                data: { product_id: pid },
                success: function() {
                    refreshCartSidebar();
                }
            });
        }
    });
});
</script>